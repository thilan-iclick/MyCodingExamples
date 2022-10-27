<?php

namespace Config;

use App\Controllers\Admin\AdminController;
use App\Controllers\Candidate\CandidateController;
use App\Controllers\Candidate\CandidateModalsController;
use App\Controllers\CommonActionsController;
use App\Controllers\CommonAjaxController;
use App\Controllers\Employer\EmployerController;
use App\Controllers\Employer\EmployerModalsController;
use App\Controllers\Home;
use App\Controllers\PaymentController;
use App\Controllers\SocialMediaLoginController;
use App\Controllers\ViewsController;

// Create a new instance of our RouteCollection class.
$routes = Services::routes();

// Load the system's routing file first, so that the app and ENVIRONMENT
// can override as needed.
if (is_file(SYSTEMPATH . 'Config/Routes.php')) {
    require SYSTEMPATH . 'Config/Routes.php';
}

/*
 * --------------------------------------------------------------------
 * Router Setup
 * --------------------------------------------------------------------
 */
$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
// The Auto Routing (Legacy) is very dangerous. It is easy to create vulnerable apps
// where controller filters or CSRF protection are bypassed.
// If you don't want to define all routes, please use the Auto Routing (Improved).
// Set `$autoRoutesImproved` to true in `app/Config/Feature.php` and set the following to true.
//$routes->setAutoRoute(false);

/*
 * --------------------------------------------------------------------
 * Route Definitions
 * --------------------------------------------------------------------
 */

// We get a performance increase by specifying the default
// route since we don't have to scan directories.

// -------------------------- marketing pages - start ---------------------------------

// Job listing page
$routes->get('/', [Home::class, 'index'], ['as' => 'main.joblisting']);

// Contact us page
$routes->get('contactus', [Home::class, 'contactus'], ['as' => 'main.contactus']);

// Faq page
$routes->get('faq', [Home::class, 'faqpage'], ['as' => 'main.faqpage']);

// Terms and conditions page
$routes->get('termsandconditions', [Home::class, 'termsandcontions'], ['as' => 'main.terms']);

// Privacy Policy
$routes->get('privacypolicy', [Home::class, 'privacypolicy'], ['as' => 'main.privacy']);

// For candidates
$routes->get('candidates', [Home::class, 'forcandidates'], ['as' => 'main.forcandidates']);

// Candidate Usefull Tools
$routes->get('candidates-usefulltools', [Home::class, 'candidateusefultools'], ['as' => 'main.candidateusefultools']);

// For employer
$routes->get('employers', [Home::class, 'foremployers'], ['as' => 'main.foremployers']);

// Employer usefull Tools
$routes->get('employers-usefulltools', [Home::class, 'employersusefulltools'], ['as' => 'main.employerusefultools']);

// Our partners
$routes->get('ourpartners', [Home::class, 'ourpartners'], ['as' => 'main.ourpartners']);

// Pricing
$routes->get('pricing', [Home::class, 'pricing'], ['as' => 'main.ourpricing']);

// Hr Laws Page
$routes->get('hr-laws', [Home::class, 'hrlawspage'], ['as' => 'main.hrlawspage']);

// -------------------------- marketing pages - end ---------------------------------

// -------------------------- Other Views pages - Start ---------------------------------

$routes->group('view', static function ($routes) {
    $routes->get('job/(:any)', [ViewsController::class, 'viewjob'], ['as' => 'view.job']);
    $routes->get('companies/(:any)', [ViewsController::class, 'viewcompany'], ['as' => 'view.company']);
});

// -------------------------- Other Views pages - End ---------------------------------

// -------------------------- Other actions - Start ---------------------------------

$routes->post('apply/express', [CommonActionsController::class, 'expressApply'], ['as' => 'apply.express']);

// -------------------------- Other actions - End ---------------------------------




// -------------------------- Other Routes - Start ---------------------------------
// Account verified
$routes->get('auth/candidate/verified', [Home::class, 'candidateverified'], ['as' => 'main.candidateverify']);
$routes->get('auth/employer/verified', [Home::class, 'employerverified'], ['as' => 'main.employerverify']);

$routes->post('payment/cardpayresponse', [PaymentController::class, 'cardPayResponse'], ['as' => 'employer.cardpayresponse']);
// -------------------------- Other Routes - End ---------------------------------


// -------------------------- Auth Routes - Start ---------------------------------
service('auth')->routes($routes);
// Facebook login redirect url
$routes->get('fblogin', [SocialMediaLoginController::class, 'fblogin'], ['as' => 'main.fblogin']);
// Google login redirect url
$routes->get('googlelogin', [SocialMediaLoginController::class, 'googlelogin'], ['as' => 'main.googlelogin']);
// Linkedin login redirect url
$routes->get('linkedinlogin', [SocialMediaLoginController::class, 'linkedinlogin'], ['as' => 'main.linkedinlogin']);
// -------------------------- Auth Routes - End ---------------------------------


// -------------------------- Common AJAX Routes - Start ---------------------------------

$routes->group('commonajax', static function ($routes) {

    // ---------- main routes ----------
    $routes->post('fileupload', [CommonAjaxController::class, 's3fileupload'], ['as' => 'ajax.s3fileupload']);
    $routes->get('getcitiesbydistricts/(:num)', [CommonAjaxController::class, 'getcities'], ['as' => 'ajax.getcities']);
    $routes->post('getcompaniesdropdown', [CommonAjaxController::class, 'getcompaniesajax'], ['as' => 'ajax.getcompanies']);
    $routes->post('getfeaturedjobsmainsearch', [CommonAjaxController::class, 'getFeaturedJobsMainSearch'], ['as' => 'ajax.getfeaturedjobsmainsearch']);
    $routes->post('getnormaljobsmainsearch', [CommonAjaxController::class, 'getNormalJobsMainSearch'], ['as' => 'ajax.getnormaljobsmainsearch']);
    $routes->post('makeasfavorite', [CommonAjaxController::class, 'makeJobFavorite'], ['as' => 'ajax.makejobfavorite']);
    $routes->post('makeasfollowed', [CommonAjaxController::class, 'makeCompanyFollowed'], ['as' => 'ajax.makecompanyfollowed']);
    $routes->post('getjobsdropdown', [CommonAjaxController::class, 'getjobsajax'], ['as' => 'ajax.getjobsdropdown']);
    $routes->post('getpricecalculation', [CommonAjaxController::class, 'getBJCCalculation'], ['as' => 'ajax.getpricecalculation']);
    $routes->post('getfepricecalculation', [CommonAjaxController::class, 'getFBJCCalculation'], ['as' => 'ajax.getfepricecalculation']);
});

// -------------------------- Common AJAX Routes - End ---------------------------------


// -------------------------- Employer Routes - Start ---------------------------------

$routes->group('employer', static function ($routes) {

    // ---------- main routes ----------
    $routes->get('dashboard', [EmployerController::class, 'index'], ['as' => 'employer.dashboard']);
    $routes->match(['get', 'post', 'put'], 'profile', [EmployerController::class, 'profile'], ['as' => 'employer.profile']);
    $routes->get('members', [EmployerController::class, 'members'], ['as' => 'employer.members']);
    $routes->match(['get', 'post', 'put'], 'postjob', [EmployerController::class, 'createjobs'], ['as' => 'employer.postjob']);
    $routes->get('followers', [EmployerController::class, 'followers'], ['as' => 'employer.followers']);
    $routes->get('managejobs', [EmployerController::class, 'manageJobs'], ['as' => 'employer.managejobs']);
    $routes->get('applicants', [EmployerController::class, 'allApplicants'], ['as' => 'employer.applicants']);
    $routes->get('savedcandidates', [EmployerController::class, 'savedCandidates'], ['as' => 'employer.savedcandidates']);
    $routes->get('jobcredits', [EmployerController::class, 'jobCredits'], ['as' => 'employer.jobcredits']);
    $routes->get('transactions', [EmployerController::class, 'transactions'], ['as' => 'employer.transactions']);
    $routes->get('physicalinterviews', [EmployerController::class, 'physicalinterview'], ['as' => 'employer.physicalinterviews']);
    $routes->get('onlineinterviews', [EmployerController::class, 'onlineinterview'], ['as' => 'employer.onlineinterviews']);
    $routes->get('join', [EmployerController::class, 'interviewJoin'], ['as' => 'employer.join']);
    $routes->get('record', [EmployerController::class, 'recordinterview'], ['as' => 'employer.record']);
    $routes->get('recordstop', [EmployerController::class, 'stoprecord'], ['as' => 'employer.recordstop']); 

    // Payments
    $routes->post('makepayment', [PaymentController::class, 'getPayment'], ['as' => 'employer.makepayment']);
    $routes->match(['get', 'post'], 'paymentresult', [PaymentController::class, 'paymentResult'], ['as' => 'employer.paymentresult']);


    // --------------- AJAX requests --------------
    $routes->post('duplicatejob', [EmployerController::class, 'duplicateJob'], ['as' => 'employer.duplicatejob']);
    $routes->post('markasfeatured', [EmployerController::class, 'markAsFeatured'], ['as' => 'employer.markasfeatured']);
    $routes->post('markasfilled', [EmployerController::class, 'markAsFilled'], ['as' => 'employer.markasfilled']);
    $routes->post('rejectapplicant', [EmployerController::class, 'rejectApplicant'], ['as' => 'employer.rejectapplicant']);
    $routes->post('shortlistapplicant', [EmployerController::class, 'shortlistApplicant'], ['as' => 'employer.shortlistapplicant']);
    $routes->delete('removesavedcandidate', [EmployerController::class, 'removedSavedApplicant'], ['as' => 'employer.removesavedcandidate']);
    $routes->match(['put', 'delete'], 'interview', [EmployerController::class, 'updateInterview'], ['as' => 'employer.interview']);


    // ---------- Data tables----------
    $routes->get('memberstbl', [EmployerController::class, 'getMemberstbl'], ['as' => 'employer.memberstbl']);
    $routes->post('followersstbl', [EmployerController::class, 'getFollowerstbl'], ['as' => 'employer.followersstbl']);
    $routes->post('managejobstbl', [EmployerController::class, 'getManageJobstbl'], ['as' => 'employer.managejobstbl']);
    $routes->post('allapplicantstbl', [EmployerController::class, 'getAllApplicantsTbl'], ['as' => 'employer.allapplicantstbl']);
    $routes->post('savedcandidatestbl', [EmployerController::class, 'getSavedCandidatestbl'], ['as' => 'employer.savedcandidatestbl']);
    $routes->post('transactionstbl', [EmployerController::class, 'getTransactionstbl'], ['as' => 'employer.transactionstbl']);
    $routes->post('physicalinterviewtbl', [EmployerController::class, 'getPhysicalInterviewtbl'], ['as' => 'employer.physicalinterviewtbl']);
    $routes->post('onlineinterviewtbl', [EmployerController::class, 'getOnlineInterviewtbl'], ['as' => 'employer.onlineinterviewtbl']);



    // ---------- Modals ----------
    $routes->get('membersreg', [EmployerModalsController::class, 'members_add_modal'], ['as' => 'employer.membersregview']);
    $routes->match(['post', 'put', 'delete'], 'membersreg', [EmployerModalsController::class, 'members_add_modal'], ['filter' => 'ajaxfilter', 'as' => 'employer.membersreg']);
    $routes->get('editjob', [EmployerModalsController::class, 'edit_job_modal'], ['as' => 'employer.editjobview']);
    $routes->match(['put', 'delete'], 'editjob', [EmployerModalsController::class, 'edit_job_modal'], ['filter' => 'ajaxfilter', 'as' => 'employer.editjobeditdelete']);
    $routes->get('viewapplication', [CandidateModalsController::class, 'applicationViewModal'], ['as' => 'employer.viewapplication']);
    $routes->get('savecandidate', [EmployerModalsController::class, 'save_candidate'], ['as' => 'employer.savecandidateview']);
    $routes->put('savecandidate', [EmployerModalsController::class, 'save_candidate'], ['filter' => 'ajaxfilter', 'as' => 'employer.savecandidate']);
    $routes->get('addcredits', [EmployerModalsController::class, 'add_credits'], ['as' => 'employer.addcreditsview']);
    $routes->get('addfecredits', [EmployerModalsController::class, 'add_fe_credits'], ['as' => 'employer.addfecreditsview']);
    $routes->match(['get', 'post'], 'interviews', [EmployerModalsController::class, 'add_interviews'], ['as' => 'employer.interviewsview']);
    $routes->get('viewvideo', [EmployerModalsController::class, 'view_interview_video'], ['as' => 'employer.viewvideo']);
});

// -------------------------- Employer Routes - End ---------------------------------

// -------------------------- Candidates Routes - Start ---------------------------------

$routes->group('candidate', static function ($routes) {

    // ---------- main routes ----------
    $routes->get('dashboard', [CandidateController::class, 'index'], ['as' => 'candidate.dashboard']);
    $routes->match(['get', 'put'], 'profile', [CandidateController::class, 'profile'], ['as' => 'candidate.profile']);
    $routes->match(['get', 'post', 'delete'], 'cvmanager', [CandidateController::class, 'cvmanager'], ['as' => 'candidate.cvmanager']);
    $routes->post('markdefault', [CandidateController::class, 'markDefault'], ['as' => 'candidate.markdefault']);
    $routes->match(['get', 'post', 'delete'], 'supportivedocs', [CandidateController::class, 'supportivedocs'], ['as' => 'candidate.supportivedocs']);
    $routes->match(['get', 'post'], 'resume', [CandidateController::class, 'resume'], ['as' => 'candidate.resume']);
    $routes->get('appliedjobs', [CandidateController::class, 'appliedJobs'], ['as' => 'candidate.appliedjobs']);
    $routes->get('savedjobs', [CandidateController::class, 'savedJobs'], ['as' => 'candidate.savedjobs']);
    $routes->get('followedcompanies', [CandidateController::class, 'followingCompanies'], ['as' => 'candidate.followedcompanies']);
    $routes->get('physicalinterviews', [CandidateController::class, 'physicalinterview'], ['as' => 'candidate.physicalinterviews']);
    $routes->get('onlineinterviews', [CandidateController::class, 'onlineinterview'], ['as' => 'candidate.onlineinterviews']);
    $routes->get('join', [CandidateController::class, 'interviewJoin'], ['as' => 'candidate.join']);

    // --------------- AJAX requests --------------
    $routes->get('getdoclist', [CandidateController::class, 'getDocs'], ['as' => 'candidate.getdoclist']);
    $routes->get('getcvlist', [CandidateController::class, 'getCvList'], ['as' => 'candidate.getcvlist']);
    $routes->get('getedulist', [CandidateController::class, 'getEduList'], ['filter' => 'ajaxfilter', 'as' => 'candidate.getedulist']);
    $routes->get('getworklist', [CandidateController::class, 'getWorkList'], ['filter' => 'ajaxfilter', 'as' => 'candidate.getworklist']);
    $routes->get('getportfoliolist', [CandidateController::class, 'getPortfolioList'], ['filter' => 'ajaxfilter', 'as' => 'candidate.getportfoliolist']);
    $routes->get('getskilllist', [CandidateController::class, 'getSkillList'], ['filter' => 'ajaxfilter', 'as' => 'candidate.getskilllist']);
    $routes->get('getlanguagelist', [CandidateController::class, 'getLanguageList'], ['filter' => 'ajaxfilter', 'as' => 'candidate.getlanguagelist']);
    $routes->get('getawardslist', [CandidateController::class, 'getAwardsList'], ['filter' => 'ajaxfilter', 'as' => 'candidate.getawardslist']);
    $routes->get('getcoverletter', [CandidateController::class, 'getCoverLetter'], ['filter' => 'ajaxfilter', 'as' => 'candidate.getcoverletter']);
    $routes->post('apply', [CandidateController::class, 'applyJob'], ['filter' => 'ajaxfilter', 'as' => 'candidate.apply']);
    // ---------- Data tables----------
    $routes->post('appliedjobstbl', [CandidateController::class, 'getAplliedjobstbl'], ['as' => 'candidate.appliedjobstbl']);
    $routes->post('savedjobstbl', [CandidateController::class, 'getSavedjobstbl'], ['as' => 'candidate.savedjobstbl']);
    $routes->post('followedcompaniestbl', [CandidateController::class, 'getFollowedCompaniestbl'], ['as' => 'candidate.followedcompaniestbl']);
    $routes->post('physicalinterviewtbl', [CandidateController::class, 'getPhysicalInterviewtbl'], ['as' => 'candidate.physicalinterviewtbl']);
    $routes->post('onlineinterviewtbl', [CandidateController::class, 'getOnlineInterviewtbl'], ['as' => 'candidate.onlineinterviewtbl']);


    // ---------- Modals ----------
    $routes->get('education', [CandidateModalsController::class, 'educationModal'], ['as' => 'candidate.educationview']);
    $routes->match(['post', 'delete'], 'education', [CandidateModalsController::class, 'educationModal'], ['filter' => 'ajaxfilter', 'as' => 'candidate.education']);

    $routes->get('work', [CandidateModalsController::class, 'workModal'], ['as' => 'candidate.workview']);
    $routes->match(['post', 'delete'], 'work', [CandidateModalsController::class, 'workModal'], ['filter' => 'ajaxfilter', 'as' => 'candidate.work']);

    $routes->get('portfolio', [CandidateModalsController::class, 'portfolioModal'], ['as' => 'candidate.portfolioview']);
    $routes->match(['post', 'delete'], 'portfolio', [CandidateModalsController::class, 'portfolioModal'], ['filter' => 'ajaxfilter', 'as' => 'candidate.portfolio']);

    $routes->get('skills', [CandidateModalsController::class, 'skillsModal'], ['as' => 'candidate.skillsview']);
    $routes->match(['post', 'delete'], 'skills', [CandidateModalsController::class, 'skillsModal'], ['filter' => 'ajaxfilter', 'as' => 'candidate.skills']);

    $routes->get('languages', [CandidateModalsController::class, 'languagesModal'], ['as' => 'candidate.languagesview']);
    $routes->match(['post', 'delete'], 'languages', [CandidateModalsController::class, 'languagesModal'], ['filter' => 'ajaxfilter', 'as' => 'candidate.languages']);

    $routes->get('awards', [CandidateModalsController::class, 'awardsModal'], ['as' => 'candidate.awardsview']);
    $routes->match(['post', 'delete'], 'awards', [CandidateModalsController::class, 'awardsModal'], ['filter' => 'ajaxfilter', 'as' => 'candidate.awards']);

    $routes->get('viewapplication', [CandidateModalsController::class, 'applicationViewModal'], ['as' => 'candidate.viewapplication']);


    $routes->match(['get', 'post'], 'interviews', [CandidateController::class, 'add_interviews'], ['as' => 'candidate.interviewsview']);
});

// -------------------------- Candidates Routes - End ---------------------------------

// -------------------------- Admin Routes - Start ---------------------------------

$routes->group('admin', static function ($routes) {
    // ---------- main routes ----------
    $routes->get('dashboard', [AdminController::class, 'index'], ['as' => 'admin.dashboard']);
    $routes->match(['get', 'post', 'put'], 'postjob', [AdminController::class, 'createjobs'], ['as' => 'admin.postjob']);
    $routes->get('managejobs', [AdminController::class, 'manageJobs'], ['as' => 'admin.managejobs']);


    // --------------- AJAX requests --------------
    $routes->post('getcredits', [AdminController::class, 'getcredits'], ['as' => 'admin.getcredits']);

    // ---------- Data tables----------
    $routes->post('managejobstbl', [AdminController::class, 'getManageJobstbl'], ['as' => 'admin.managejobstbl']);
});

// -------------------------- Admin Routes - End ---------------------------------

/*
 * --------------------------------------------------------------------
 * Additional Routing
 * --------------------------------------------------------------------
 *
 * There will often be times that you need additional routing and you
 * need it to be able to override any defaults in this file. Environment
 * based routes is one such time. require() additional route files here
 * to make that happen.
 *
 * You will have access to the $routes object within that file without
 * needing to reload it.
 */
if (is_file(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
    require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}
