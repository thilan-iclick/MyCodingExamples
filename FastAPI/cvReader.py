from fastapi import Security, Depends, FastAPI, HTTPException
from fastapi.param_functions import Path
from fastapi.security.api_key import APIKeyQuery, APIKeyCookie, APIKeyHeader, APIKey
from fastapi.responses import JSONResponse

from starlette.status import HTTP_204_NO_CONTENT, HTTP_403_FORBIDDEN
from starlette.responses import RedirectResponse, JSONResponse
from pydantic import BaseModel

import urllib
from pdfminer.pdfinterp import PDFResourceManager, PDFPageInterpreter
from pdfminer.converter import TextConverter
from pdfminer.layout import LAParams
from pdfminer.pdfpage import PDFPage
from io import StringIO
from io import BytesIO
import docx2txt
import nltk
import ssl
import subprocess
import re
import csv
import requests
import numpy as np
import pandas as pd
import json
import jsonpickle
from json import JSONEncoder

# nltk.download('punkt')
# nltk.download('averaged_perceptron_tagger')
# nltk.download('maxent_ne_chunker')
# nltk.download('words')

try:
    _create_unverified_https_context = ssl._create_unverified_context
except AttributeError:
    pass
else:
    ssl._create_default_https_context = _create_unverified_https_context

#nltk.download('popular')


# Fast API INITIATE
API_KEY = "209c4ab7-1f86-46a1-8b4c-d8f5c84d0be0"
API_KEY_NAME = "accessToken"
COOKIE_DOMAIN = "localtest.me"


api_key_query = APIKeyQuery(name=API_KEY_NAME, auto_error=False)
api_key_header = APIKeyHeader(name=API_KEY_NAME, auto_error=False)
api_key_cookie = APIKeyCookie(name=API_KEY_NAME, auto_error=False)


async def get_api_key(api_key_header: str = Security(api_key_header)):
    if api_key_header == API_KEY:
        return api_key_header
    else:
        raise HTTPException(
            status_code=HTTP_403_FORBIDDEN, detail="Could not validate credentials"
        )


app = FastAPI()

# extracting text from PDF

#ssl._create_default_https_context = ssl._create_unverified_context


def extract_text_from_pdf(pdf_path):
    rsrcmgr = PDFResourceManager()
    retstr = StringIO()
    codec = 'utf-8'
    laparams = LAParams()
    device = TextConverter(rsrcmgr, retstr, codec=codec, laparams=laparams)
    f = urllib.request.urlopen(pdf_path).read()
    fp = BytesIO(f)
    interpreter = PDFPageInterpreter(rsrcmgr, device)
    password = ""
    maxpages = 0
    caching = True
    pagenos = set()
    for page in PDFPage.get_pages(fp,
                                  pagenos,
                                  maxpages=maxpages,
                                  password=password,
                                  caching=caching,
                                  check_extractable=True):
        interpreter.process_page(page)
    fp.close()
    device.close()
    str = retstr.getvalue()
    retstr.close()
    return str
# extracting text from DOCX


def extract_text_from_docx(docx_path):
    docxfile = BytesIO(requests.get(docx_path).content)
    txt = docx2txt.process(docxfile)
    if txt:
        return txt.replace('\t', ' ')
    return None

# reading skill set


def get_skill_set(csv_path):
    items = []
    with open(csv_path) as csvfile:
        csvReader = csv.reader(csvfile)
        for row in csvReader:
            items.append(row[0].lower())
            # print(row)
    return items


# Extracting names from text
def extract_names(txt):
    person_names = []

    for sent in nltk.sent_tokenize(txt):
        for chunk in nltk.ne_chunk(nltk.pos_tag(nltk.word_tokenize(sent))):
            if hasattr(chunk, 'label') and chunk.label() == 'PERSON':
                person_names.append(
                    ' '.join(chunk_leave[0] for chunk_leave in chunk.leaves())
                )

    return person_names


# Extracting phone numbers
PHONE_REG = re.compile(r'[\+\(]?[1-9][0-9 .\-\(\)]{8,}[0-9]')


def extract_phone_number(resume_text):
    phone = re.findall(PHONE_REG, resume_text)

    if phone:
        number = ''.join(phone[0])

        if resume_text.find(number) >= 0 and len(number) < 16:
            return number
    return None


# Extracting Email Address
EMAIL_REG = re.compile(r'[a-z0-9\.\-+_]+@[a-z0-9\.\-+_]+\.[a-z]+')


def extract_emails(resume_text):
    return re.findall(EMAIL_REG, resume_text)


# Skills API
def skill_exists(skill):
    url = f'https://api.promptapi.com/skills?q={skill}&count=1'
    headers = {'apikey': 'CzjNLzhBuNUVyJk7kRJAgs36Vf99L6Nm'}
    response = requests.request('GET', url, headers=headers)
    result = response.json()

    if response.status_code == 200:
        return len(result) > 0 and result[0].lower() == skill.lower()
    raise Exception(result.get('message'))

# Extracting Skills


def extract_skills(input_text):
    stop_words = set(nltk.corpus.stopwords.words('english'))
    word_tokens = nltk.tokenize.word_tokenize(input_text)

    # remove the stop words
    filtered_tokens = [w for w in word_tokens if w not in stop_words]

    # remove the punctuation
    filtered_tokens = [w for w in word_tokens if w.isalpha()]

    # generate bigrams and trigrams (such as artificial intelligence)
    bigrams_trigrams = list(
        map(' '.join, nltk.everygrams(filtered_tokens, 2, 3)))

    # we create a set to keep the results in.
    found_skills = set()

    # we search for each token in our skills database
    for token in filtered_tokens:
        if skill_exists(token.lower()):
            found_skills.add(token)

    # we search for each bigram and trigram in our skills database
    for ngram in bigrams_trigrams:
        if skill_exists(ngram.lower()):
            found_skills.add(ngram)

    return found_skills


# skill Extracting from csv
def extract_skills_csv(input_text):
    stop_words = set(nltk.corpus.stopwords.words('english'))
    word_tokens = nltk.tokenize.word_tokenize(input_text)
    # remove the stop words
    filtered_tokens = [w for w in word_tokens if w not in stop_words]

    # remove the punctuation
    filtered_tokens = [w for w in word_tokens if w.isalpha()]

    # generate bigrams and trigrams (such as artificial intelligence)
    bigrams_trigrams = list(
        map(' '.join, nltk.everygrams(filtered_tokens, 2, 3)))

    # we create a set to keep the results in.
    found_skills = set()

    # we search for each token in our skills database
    for token in filtered_tokens:
        if token.lower() in get_skill_set("skillset.csv"):
            found_skills.add(token)

    # we search for each bigram and trigram in our skills database
    for ngram in bigrams_trigrams:
        if ngram.lower() in get_skill_set("skillset.csv"):
            found_skills.add(ngram)

    return found_skills


# Education Extracting from Resume

def extract_education(input_text):
    stop_words = set(nltk.corpus.stopwords.words('english'))
    word_tokens = nltk.tokenize.word_tokenize(input_text)
    # remove the stop words
    filtered_tokens = [w for w in word_tokens if w not in stop_words]

    # remove the punctuation
    filtered_tokens = [w for w in word_tokens if w.isalpha()]

    # generate bigrams and trigrams (such as artificial intelligence)
    bigrams_trigrams = list(
        map(' '.join, nltk.everygrams(filtered_tokens, 2, 3)))
    # print(bigrams_trigrams)
    # return None
    # we create a set to keep the results in.
    found_education = set()

    # we search for each bigram and trigram in our skills database
    for ngram in bigrams_trigrams:
        if ngram.lower() in get_skill_set("schools.csv"):
            found_education.add(ngram)

    return found_education


# Fast API INstallation
app = FastAPI()


@app.get("/")
async def homepage():
    return "HR PAL CV Parser"


@app.get("/read_cv", tags=["Processing End Points"])
async def reading_cv(cvlink: str, api_key: APIKey = Depends(get_api_key)):
    text = ""
    if cvlink.lower().endswith('.pdf'):
        text = extract_text_from_pdf(cvlink)
    elif cvlink.lower().endswith('.docx'):
        text = extract_text_from_docx(cvlink)
    if text == "":
        raise HTTPException(
            status_code=HTTP_204_NO_CONTENT
        )
    response = {}
    names = extract_names(text)
    if names:
        response['name'] = names[0]
    phone_number = extract_phone_number(text)
    response['phone'] = phone_number
    emails = extract_emails(text)
    response['emails'] = emails
    skillset = extract_skills_csv(text)
    response['skills'] = list(skillset)
    education = extract_education(text)
    response['education'] = list(education)
    return JSONResponse(content=response)
