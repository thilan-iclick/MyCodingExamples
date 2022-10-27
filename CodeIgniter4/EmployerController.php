<?php

/**
 * This Controller will handle all the Employer functionalities of the BeyondJobs as requires in 
 * the scope
 *
 * Created on: 08/09/2022
 * 
 * Last Modified on:
 * Last Modified by: 
 * 
 * @author Thilan Pathirage
 * @copyright (c) 2022, ICLICK Online Technologies
 */

namespace App\Controllers\Employer;

use App\Controllers\BaseController;
use App\Libraries\AgoraRecording;
use App\Libraries\AgoraToken;
use App\Libraries\AwsS3;
use App\Models\AppliyJobsModel;
use App\Models\CandidateCvsModel;
use App\Models\CompanyModel;
use App\Models\CountriesModel;
use App\Models\DistrictsModel;
use App\Models\ExpericanceLevelsModel;
use App\Models\FeaturedJobsCreditsModel;
use App\Models\FollowedCompaniesModel;
use App\Models\InterviewModel;
use App\Models\JobCreditsModel;
use App\Models\JobsModalitiesModel;
use App\Models\JobsModel;
use App\Models\JobsTypesModel;
use App\Models\QualificationLevelsModel;
use App\Models\SavedCandidatesModel;
use App\Models\SectorsModel;
use App\Models\TransactionsModel;
use CodeIgniter\Shield\Entities\User;
// 3rd party integrations
use \Hermawan\DataTables\DataTable;

class EmployerController extends BaseController
{
    /**
     * -------------------------------------------------------------------------
     * Variables inside the Controller
     * -------------------------------------------------------------------------
     */
    private
        $company,
        $sectors,
        $awsS3,
        $users,
        $user,
        $distritcs,
        $countries,
        $jtypes,
        $jmodalits,
        $explevels,
        $qualilevels,
        $jobs;

    /**
     * -------------------------------------------------------------------------
     * Loading the helpers,Models etc.
     * -------------------------------------------------------------------------
     */
    public function __construct()
    {
        $this->company = new CompanyModel();
        $this->sectors = new SectorsModel();
        $this->awsS3 = new AwsS3();
        $this->users = model('UserModel');
        $this->user = auth()->user();
        $this->distritcs = new DistrictsModel();
        $this->countries = new CountriesModel();
        $this->jtypes = new JobsTypesModel();
        $this->jmodalits = new JobsModalitiesModel();
        $this->explevels = new ExpericanceLevelsModel();
        $this->qualilevels = new QualificationLevelsModel();
        $this->jobs = new JobsModel();
    }

    /**
     * -------------------------------------------------------------------------
     * dashboard page
     * -------------------------------------------------------------------------
     */
    public function index()
    {
        $data = [
            "page" => "dashboard",
            'dashboard' => $this->company->dashboard(),
        ];
        return view('common_includes/admin_includes/head', $data)
            . view('common_includes/admin_includes/top-menu')
            . view('employers/includes/left-menu')
            . view('employers/index')
            . view('common_includes/admin_includes/footer')
            . view('common_includes/admin_includes/jsplugin');
    }

    /**
     * -------------------------------------------------------------------------
     * Profile page
     * -------------------------------------------------------------------------
     */
    public function profile()
    {
        switch ($this->request->getMethod()) {
            case 'get':
                $data = [
                    'page' => "company profile",
                    'sectors' => $this->sectors->dropdown(),
                    'company' => $this->company->companyInfo(),
                    'districts' => $this->distritcs->dropdown(),
                    'countries' => $this->countries->dropdown()
                ];
                return view('common_includes/admin_includes/head', $data)
                    . view('common_includes/admin_includes/top-menu')
                    . view('employers/includes/left-menu')
                    . view('employers/company-profile')
                    . view('common_includes/admin_includes/footer')
                    . view('common_includes/admin_includes/jsplugin');
                break;
            case 'post':
                $frmdata = $this->request->getVar();
                if ($_FILES["profilepic"]["error"] == 0) {
                    $frmdata['pro_pic'] = $this->awsS3->sendFile($_FILES['profilepic'], "images");
                }

                if ($_FILES["coverpic"]["error"] == 0) {
                    $frmdata['cover_pic'] = $this->awsS3->sendFile($_FILES['coverpic'], "images");
                }

                if ($this->validation->run($frmdata, 'companyReg')) {
                    $this->company->save($frmdata);
                    return redirect()->route('employer.profile');
                } else {
                    $this->Web_error_msg($this->validation->getErrors());
                    return redirect()->route('employer.profile')->withInput();
                }
                break;
            case 'put':
                $frmdata = $this->request->getVar();
                $frmdata["company_id"] = $this->company->companyInfo()["company_id"];
                if ($_FILES["profilepic"]["error"] == 0) {
                    $frmdata['pro_pic'] = $this->awsS3->sendFile($_FILES['profilepic'], "images");
                }

                if ($_FILES["coverpic"]["error"] == 0) {
                    $frmdata['cover_pic'] = $this->awsS3->sendFile($_FILES['coverpic'], "images");
                }

                if ($this->validation->run($frmdata, 'companyEdit')) {
                    $this->company->save($frmdata);
                    return redirect()->route('employer.profile');
                } else {
                    $this->Web_error_msg($this->validation->getErrors());
                    return redirect()->route('employer.profile')->withInput();
                }
                break;
        }
    }


    /**
     * -------------------------------------------------------------------------
     *  Memebers page
     * -------------------------------------------------------------------------
     */
    public function members()
    {
        if (!$this->user->inGroup('employer')) {
            $this->Web_error_msg("You do not have the permission");
            return redirect()->route('employer.profile')->withInput();
        }
        $data = [
            'page' => "account members",
        ];
        return view('common_includes/admin_includes/head', $data)
            . view('common_includes/admin_includes/top-menu')
            . view('employers/includes/left-menu')
            . view('employers/company-members')
            . view('common_includes/admin_includes/footer')
            . view('common_includes/admin_includes/jsplugin');
    }

    // ------------ Members page - Data Tables-----------------

    public function getMemberstbl()
    {

        $allusers = $this->company->getMembersDataTbl("users.id as id, user_fname, secret", $this->company->companyInfo()["company_id"]);
        return DataTable::of($allusers)
            ->add('Type', function ($row) {
                $user = $this->users->findById($row->id);
                if ($user->inGroup('employer')) {
                    return 'Main Admin';
                }
                if ($user->inGroup('employermember')) {
                    return 'Normal Admin';
                }
            }, 'last')
            ->add('Actions', function ($row) {
                $btngroupe = '
    <div class="btn-group" role="group">
        <button type="button" class="btn btn-sm btn-info bstooltip editmember" uid="' . $row->id . '" data-placement="top" style="padding: 0rem .5em;" title="View & Update Details"><i style="font-size: 1.5em;" class="mdi mdi-content-save-edit-outline" ></i></button>
        <button type="button" class="btn btn-sm btn-danger bstooltip deletemember" uid="' . $row->id . '" data-placement="top" style="padding: 0rem .5em;" title="Delete Member" ><i style="font-size: 1.5em;" class="mdi mdi-delete" ></i></button>
    </div>
    ';
                return $btngroupe;
            }, 'last')
            ->hide('id')
            ->toJson();
    }


    /**
     * -------------------------------------------------------------------------
     *  Post Job page
     * -------------------------------------------------------------------------
     */
    public function createjobs()
    {
        switch ($this->request->getMethod()) {
            case 'get':
                $fc = new FeaturedJobsCreditsModel();
                $jc = new JobCreditsModel();
                $data = [
                    'page' => "post job",
                    'company' => $this->company->companyInfo(),
                    'sectors' => $this->sectors->dropdown(),
                    'districts' => $this->distritcs->dropdown(),
                    'countries' => $this->countries->dropdown(),
                    'jtypes' => $this->jtypes->dropdown(),
                    'jmodalities' => $this->jmodalits->dropdown(),
                    'explevels' => $this->explevels->dropdown(),
                    'qualifilevels' => $this->qualilevels->dropdown(),
                    'featurecredits' => $fc->availableCredits(),
                    'jobcredits' => $jc->availableCredits()
                ];
                return view('common_includes/admin_includes/head', $data)
                    . view('common_includes/admin_includes/top-menu')
                    . view('employers/includes/left-menu')
                    . view('employers/company-post-job')
                    . view('common_includes/admin_includes/footer')
                    . view('common_includes/admin_includes/jsplugin');
                break;
            case 'post':
                $data = $this->request->getVar();
                $this->jobs->save($data);
                $this->Web_success_msg("Job is successfully submitted for approval.");
                return redirect()->route('employer.postjob');
                break;
        }
    }

    /**
     * -------------------------------------------------------------------------
     *  Manage Jobs page
     * -------------------------------------------------------------------------
     */
    public function manageJobs()
    {
        $data = [
            'page' => "manage jobs",
        ];
        return view('common_includes/admin_includes/head', $data)
            . view('common_includes/admin_includes/top-menu')
            . view('employers/includes/left-menu')
            . view('employers/manage-jobs')
            . view('common_includes/admin_includes/footer')
            . view('common_includes/admin_includes/jsplugin');
    }

    // ------------ Manage Jobs - Data Tables-----------------
    public function getManageJobstbl()
    {

        $tablevar = $this->request->getVar();
        $dbtable = $this->jobs->getManageJobsDataTable($tablevar, $this->company->companyInfo()["company_id"]);
        $tableData = [];
        $applyjobs = new AppliyJobsModel();
        foreach ($dbtable as $item) {

            // Job Title
            $jtitle = '';
            if ($item['edit_job_title']) {
                $item['job_title'] = $item['edit_job_title'];
            }
            if ($item['is_featured']) {
                $jtitle = '<span>' . $item['job_title'] . ' <img src="http://localhost:8080/assets/images/bjc-2.svg" style="width:7%;" alt="bjc-2" class="img-fluid"></span>';
            } else {
                $jtitle = '<span>' . $item['job_title'] . '</span>
                <br/><br/>
                <button class="btn btn-sm btn-light markfe-btn" jid="' . $item['job_id'] . '" style="width:100%;font-size: 0.8em;padding: 1px;" ><img src="http://localhost:8080/assets/images/bjc-2.svg" style="width:7%;" alt="bjc-2" class="img-fluid"> Mark as Featured</button>
                ';
            }


            // locations
            $location = $item['country_name'];
            if ($item['is_local'] == '1') {
                $location = $item['city'] . ', ' . $item['district'];
            }

            // Contact Details
            $dates = '
            <p class="p-0 m-0" ><b>Submitted: </b> ' . date('d/m/Y', strtotime($item['created_at'])) . '</p>
            ';

            // Applicants count

            $applicants = '
              <p class="p-0 m-0" ><b>Applied: </b> ' . $applyjobs->getAllApplicantsCnt($item['job_id'])['applied'] . '</p>
              <p class="p-0 m-0"><b>Shortlisted: </b> ' . $applyjobs->getAllApplicantsCnt($item['job_id'])['shortlist'] . '</p>
              <p class="p-0 m-0"><b>Rejected: </b> ' . $applyjobs->getAllApplicantsCnt($item['job_id'])['reject'] . '</p>
              ';

            $filledbtn = 'disabled';
            // status
            $status = '';
            switch ($item['job_status']) {
                case '1':
                    $status = '<span class="badge badge-soft-warning bstooltip" data-placement="top" title="New Job - Pending Approval" style="font-size:2.2em;" ><i class="mdi mdi-timer-sand" ></i></span>';
                    break;
                case '2':
                    $status = '<span class="badge badge-soft-info bstooltip" data-placement="top" title="Job Published" style="font-size:2.2em;" ><i class="mdi mdi-format-list-checks" ></i></span>';
                    $dates .= '
                <p class="p-0 m-0" ><b>Posted: </b> ' . date('d/m/Y', strtotime($item['approved_on'])) . '</p>
            <p class="p-0 m-0"><b>Expiry: </b> ' . date('d/m/Y', strtotime($item['expire_on'])) . '</p>
                
                ';
                    $filledbtn = '';
                    break;
                case '3':
                    $status = '<span class="badge badge-soft-success bstooltip" data-placement="top" title="Job Filled" style="font-size:2.2em;"><i class="mdi mdi-account-multiple-check" ></i></span>';
                    break;
            }

            if ($item['edit_job_title']) {
                $status = '<span class="badge badge-soft-warning bstooltip" data-placement="top" title="Edit Job - Pending Approval" style="font-size:2.2em;" ><i class="mdi mdi-timer-sand" ></i></span>';
                $filledbtn = 'disabled';
            }

            if ($item['expire_on'] != null && time() > strtotime($item['expire_on'])) {
                $status = '<span class="badge badge-soft-danger bstooltip" data-placement="top" title="Job Expired" style="font-size:2.2em;" ><i class="mdi mdi-clock-alert" ></i></span>';
                $filledbtn = 'disabled';
            }


            // actions
            $actionbtns = '
            <button type="button" data-placement="top" ' . $filledbtn . ' title="Position filled"  class="btn btn-success bstooltip filled-btn" jid="' . $item['job_id'] . '" style="padding: 0rem .5em;width:100%;" ><i style="font-size: 1.5em;"  class="mdi mdi-briefcase-check" ></i></button>
              <hr style="margin:6px;" />
              <div class="btn-group" role="group">
                <a href="' . base_url('view/job/' . urlencode(base64_encode($item['job_id']))) . '" data-placement="top" title="View Job"  class="btn btn-primary bstooltip" style="padding: 0rem .5em;" ><i style="font-size: 1.5em;"  class="mdi mdi-briefcase-search" ></i></a>
                <button type="button" data-placement="top" title="Edit Job"  class="btn btn-info bstooltip editjob-btn" jid="' . $item['job_id'] . '" style="padding: 0rem .5em;" ><i style="font-size: 1.5em;"  class="mdi mdi-briefcase-edit" ></i></button>
                <button type="button" data-placement="top" title="Dulpicate the Job"  class="btn btn-warning bstooltip duplicate-btn" jid="' . $item['job_id'] . '" style="padding: 0rem .5em;" ><i style="font-size: 1.5em;"  class="mdi mdi-briefcase-plus" ></i></button>
                <button type="button" data-placement="top" title="Delete Job"  class="btn btn-danger bstooltip delete-btn" jid="' . $item['job_id'] . '" style="padding: 0rem .5em;" ><i style="font-size: 1.5em;"  class="mdi mdi-briefcase-remove" ></i></button>
              </div>
              
              ';

            $tableData[] = [$jtitle, $dates, $item['sector_name'], $location, $applicants, $status, $actionbtns];
        }

        $drawtable = [
            "draw" => $tablevar['draw'],
            "recordsTotal" => $this->jobs->getManagejobsrows($tablevar, $this->company->companyInfo()["company_id"])->countAllResults(),
            "recordsFiltered" => $this->jobs->getManagejobsrows($tablevar, $this->company->companyInfo()["company_id"])->countAllResults(),
            "data" => $tableData,
        ];

        return $this->response->setJSON($drawtable);
    }

    // duplicate job
    public function duplicateJob()
    {
        $fc = new FeaturedJobsCreditsModel();
        $jc = new JobCreditsModel();

        if ($jc->availableCredits() > 0) {
            $jid = $this->request->getVar('jid');
            $originalJob = $this->jobs->select('
            job_title, 
            description, 
            is_featured, 
            address, 
            city_id, 
            district_id, 
            country_id, 
            postcode, 
            loc_type, 
            company_id, sector_id, job_type_id, job_modality_id, min_q_id, min_ex_id')->where('job_id', $jid)->first();
            if ($originalJob['is_featured'] == 1 & $fc->availableCredits() <= 0) {
                return $this->response->setStatusCode($this::NOT_ALLOWED)->setJSON(array('msg' => $this::DONT_HAVE_FE_CREDITS));
            }
            $this->jobs->save($originalJob);
        } else {
            return $this->response->setStatusCode($this::NOT_ALLOWED)->setJSON(array('msg' => $this::DONT_HAVE_CREDITS));
        }
    }

    // mark as featured
    public function markAsFeatured()
    {
        $jid = $this->request->getVar('jid');
        $fc = new FeaturedJobsCreditsModel();
        if ($fc->availableCredits() > 0) {
            $fc->deduct();
            $this->jobs->save(['is_featured' => 1, 'job_id' => $jid]);
        } else {
            return $this->response->setStatusCode($this::NOT_ALLOWED)->setJSON(array('msg' => $this::DONT_HAVE_FE_CREDITS));
        }
    }

    // mark as filled
    public function markAsFilled()
    {
        $jid = $this->request->getVar('jid');
        $this->jobs->save(['job_status' => 3, 'job_id' => $jid]);
    }



    /**
     * -------------------------------------------------------------------------
     *  All Applicants page
     * -------------------------------------------------------------------------
     */

    public function allApplicants()
    {
        $data = [
            'page' => "all applicants",
        ];
        return view('common_includes/admin_includes/head', $data)
            . view('common_includes/admin_includes/top-menu')
            . view('employers/includes/left-menu')
            . view('employers/company-all-applicants')
            . view('common_includes/admin_includes/footer')
            . view('common_includes/admin_includes/jsplugin');
    }

    // get All applicants tbl
    public function getAllApplicantsTbl()
    {
        $appliedjobs = new AppliyJobsModel();
        $savedcandidate = new SavedCandidatesModel();
        $interview = new InterviewModel();
        $tablevar = $this->request->getVar();
        $otherdata = [
            'cid' => $this->company->companyInfo()["company_id"]
        ];
        $dbtable = $appliedjobs->getAllApllicantsDataTable($tablevar, $otherdata);
        $tableData = [];
        foreach ($dbtable as $item) {
            $actionbtns = '<div class="btn-group" role="group">';
            // Job Title
            $jtitle = '';
            if ($item['is_featured']) {
                $jtitle = '<span>' . $item['job_title'] . ' <img src="http://localhost:8080/assets/images/bjc-2.svg" style="width:4%;" alt="bjc-2" class="img-fluid"></span>';
            } else {
                $jtitle = '<span>' . $item['job_title'] . '</span>
                ';
            }



            // Hire btn

            // Status

            $status = '';
            switch ($item['status']) {
                case 'applied':
                    $status = '
                        <span class="badge badge-soft-warning bstooltip" data-placement="top" title="Applied" style="font-size:1.8em;" ><i class="mdi mdi-timer-sand" ></i></span>
                    ';
                    $actionbtns .= '
                    <button type="button" data-placement="top" title="Shortlist Applicant"  class="btn btn-success bstooltip shortlist-btn" appid="' . $item['apply_jobs_id'] . '" style="padding: 0rem .5em;" ><i style="font-size: 1.5em;"  class="mdi mdi-account-check" ></i></button>
                    ';
                    break;
                case 'short listed':
                    $status = '
                        <span class="badge badge-soft-success bstooltip" data-placement="top" title="Shortlisted" style="font-size:1.8em;" ><i class="mdi mdi-account-check" ></i></span>
                    ';
                    $actionbtns .= '
                    <button type="button" data-placement="top" title="Schedule an Interview"  class="btn btn-info bstooltip interview-btn" appid="' . $item['apply_jobs_id'] . '" style="padding: 0rem .5em;" ><i style="font-size: 1.5em;"  class="fas fa-handshake" ></i></button>
                    ';
                    break;
                case 'interview requested':
                    $intve = $interview->interviewByApplyID($item['apply_jobs_id']);

                    $status = '
                        <span class="badge badge-soft-info bstooltip" data-placement="top" title="Interview requested" style="font-size:1.8em;" ><i class="mdi mdi-tie" ></i></span>
                        
                    ';
                    $disabl = '';
                    if (time() < strtotime($intve['interview_datetime'])) {
                        $disabl = 'disabled';
                    }
                    $actionbtns .= '
                    <button type="button" ' . $disabl . ' data-placement="top" title="Schedule another Interview"  class="btn btn-info bstooltip interview-btn" appid="' . $item['apply_jobs_id'] . '" style="padding: 0rem .5em;" ><i style="font-size: 1.5em;"  class="fas fa-handshake" ></i></button>
                    ';
                    break;
                case 'rejected':
                    $status = '
                        <span class="badge badge-soft-danger bstooltip" data-placement="top" title="Rejected" style="font-size:1.8em;" ><i class="mdi mdi-account-off" ></i></span>
                    ';
                    break;
            }

            // Candidate details
            if ($item['applytype'] == 'registered') {
                $candidate = '
                <p style="font-size:1em;margin:0;" >' . $item['user_fname'] . ' ' . $item['user_lname'] . ' <span style="padding: 4px;" data-placement="top" title="Registered Applicant" class="badge badge-pill badge-soft-success bstooltip" ><i style="font-size: 1.5em;" class="mdi mdi-file-check" ></i></span></p>
                <p style="font-size: 0.9em;" >
                    <b>Email: </b> ' . $item['secret'] . '<br/>
                    <b>Phone: </b> ' . $item['user_phone'] . '
                
                </p>
                ';
                $actionbtns .= '
                <button type="button" data-placement="top" title="View Application"  class="btn btn-primary bstooltip viewapplication" appid="' . $item['apply_jobs_id'] . '" style="padding: 0rem .5em;" ><i style="font-size: 1.5em;"  class="mdi mdi-file-account" ></i></button>
                ';
                if ($savedcandidate->isSaved($item['id'])) {
                    $actionbtns .= '
                    <button type="button" data-placement="top" title="View/Edit Saved candidate notes"  class="btn btn-warning bstooltip savecandidate-btn" uid="' . $item['id'] . '" style="padding: 0rem .5em;" ><i style="font-size: 1.5em;"  class="mdi mdi-account-details" ></i></button>
                    ';
                } else {
                    $actionbtns .= '
                    <button type="button" data-placement="top" title="Save candidate for later"  class="btn btn-warning bstooltip savecandidate-btn" uid="' . $item['id'] . '" style="padding: 0rem .5em;" ><i style="font-size: 1.5em;"  class="mdi mdi-account-star" ></i></button>
                    ';
                }
            } else {
                $candidate = '
                <p style="font-size:1em;margin:0;" >' . $item['fname'] . ' ' . $item['lname'] . ' <span data-placement="top" title="Express Applicant (CV less)" style="padding: 4px;" class="badge badge-pill badge-soft-info bstooltip" ><i style="font-size: 1.5em;" class="mdi mdi-file-question" ></i></span></p>
                <p style="font-size: 0.9em;">
                    <b>Email: </b> ' . $item['email'] . '<br/>
                    <b>Phone: </b> ' . $item['phone'] . '
                
                </p>
                
                ';
            }


            // actions
            if ($item['status'] != 'rejected') {
                $actionbtns .= '
                <button type="button" data-placement="top" title="Reject Application"  class="btn btn-danger bstooltip reject-btn" appid="' . $item['apply_jobs_id'] . '" style="padding: 0rem .5em;" ><i style="font-size: 1.5em;"  class="mdi mdi-account-off" ></i></button>
                ';
            }
            $actionbtns .= '
                
              </div>
              
              ';

            $tableData[] = [$candidate, $jtitle, date('d/m/Y', strtotime($item['created_at'])), $status, $actionbtns];
        }

        $drawtable = [
            "draw" => $tablevar['draw'],
            "recordsTotal" => $appliedjobs->getAllApllicantsDatatblrows($tablevar, $otherdata),
            "recordsFiltered" => $appliedjobs->getAllApllicantsDatatblrows($tablevar, $otherdata),
            "data" => $tableData,
        ];

        return $this->response->setJSON($drawtable);
    }

    // reject applicant
    public function rejectApplicant()
    {
        $appid = $this->request->getVar('appid');
        $applyjobs = new AppliyJobsModel();
        $applyjobs->save(['apply_jobs_id' => $appid, 'status' => 4]);
    }

    // shortlist applicant
    public function shortlistApplicant()
    {
        $appid = $this->request->getVar('appid');
        $applyjobs = new AppliyJobsModel();
        $applyjobs->save(['apply_jobs_id' => $appid, 'status' => 2]);
    }

    /**
     * -------------------------------------------------------------------------
     *  Saved Candidates page
     * -------------------------------------------------------------------------
     */
    public function savedCandidates()
    {
        $data = [
            'page' => "saved candidates",
        ];
        return view('common_includes/admin_includes/head', $data)
            . view('common_includes/admin_includes/top-menu')
            . view('employers/includes/left-menu')
            . view('employers/company-saved-candidates')
            . view('common_includes/admin_includes/footer')
            . view('common_includes/admin_includes/jsplugin');
    }

    // ------------ Followers page - Data Tables-----------------
    public function getSavedCandidatestbl()
    {

        $tablevar = $this->request->getVar();
        $savedCandidates = new SavedCandidatesModel();
        $dbtable = $savedCandidates->getCandidatesDataTable($tablevar, $this->company->companyInfo()["company_id"]);
        $tableData = [];
        $cvs = new CandidateCvsModel();
        foreach ($dbtable as $item) {
            // locations
            $location = $item['country_name'];
            if ($item['is_local'] == '1') {
                $location = $item['city'] . ', ' . $item['district'];
            }

            // Contact Details
            $contactdetails = '
            <p class="p-0 m-0" ><b>Phone: </b> ' . $item['user_phone'] . '</p>
            <p class="p-0 m-0"><b>Email: </b> ' . $item['secret'] . '</p>
            
            ';

            $disabled = '';
            $url = '#';
            $cv = $cvs->getDetaultCvById($item['id']);
            if ($cv) {
                $disabled = 'disabled';
                $url = $cv['cv_file'];
            }

            // actions
            $actionbtns = '
            <div class="btn-group" role="group">
                <button type="button" data-placement="top" title="View/Edit Saved candidate notes"  class="btn btn-warning bstooltip savecandidate-btn" uid="' . $item['id'] . '" style="padding: 0rem .5em;" ><i style="font-size: 1.5em;"  class="mdi mdi-account-details" ></i></button>
                <a href="' . $url . '" ' . $disabled . ' data-placement="top" title="View CV"  class="btn btn-info bstooltip" style="padding: 0rem .5em;" ><i style="font-size: 1.5em;"  class="mdi mdi-file-document" ></i></a>
                <button type="button" data-placement="top" title="Remove Candidate"  class="btn btn-danger bstooltip remove-btn" savid="' . $item['savedcan_id'] . '" style="padding: 0rem .5em;" ><i style="font-size: 1.5em;"  class="mdi mdi-account-remove" ></i></button>
            </div>
            ';

            $tableData[] = [$item['user_fname'] . ' ' . $item['user_lname'], $item['jobtitle'], $contactdetails, $item['sector_name'], $location, $actionbtns];
        }

        $drawtable = [
            "draw" => $tablevar['draw'],
            "recordsTotal" => $savedCandidates->getCandidatesDatatblrows($tablevar, $this->company->companyInfo()["company_id"]),
            "recordsFiltered" => $savedCandidates->getCandidatesDatatblrows($tablevar, $this->company->companyInfo()["company_id"]),
            "data" => $tableData,
        ];

        return $this->response->setJSON($drawtable);
    }

    public function removedSavedApplicant()
    {
        $data = $this->request->getRawInput();
        $savedcandidates = new SavedCandidatesModel();
        $savedcandidates->delete($data['savid']);
    }

    /**
     * -------------------------------------------------------------------------
     *  Followers page
     * -------------------------------------------------------------------------
     */
    public function followers()
    {
        $data = [
            'page' => "followers",
        ];
        return view('common_includes/admin_includes/head', $data)
            . view('common_includes/admin_includes/top-menu')
            . view('employers/includes/left-menu')
            . view('employers/company-followers')
            . view('common_includes/admin_includes/footer')
            . view('common_includes/admin_includes/jsplugin');
    }

    // ------------ Followers page - Data Tables-----------------
    public function getFollowerstbl()
    {

        $tablevar = $this->request->getVar();
        $followedCandidates = new FollowedCompaniesModel();
        $dbtable = $followedCandidates->getCandidatesDataTable($tablevar, $this->company->companyInfo()["company_id"]);
        $tableData = [];
        $cvs = new CandidateCvsModel();
        foreach ($dbtable as $item) {
            // locations
            $location = $item['country_name'];
            if ($item['is_local'] == '1') {
                $location = $item['city'] . ', ' . $item['district'];
            }

            // Contact Details
            $contactdetails = '
            <p class="p-0 m-0" ><b>Phone: </b> ' . $item['user_phone'] . '</p>
            <p class="p-0 m-0"><b>Email: </b> ' . $item['secret'] . '</p>
            
            ';

            $disabled = '';
            $url = '#';
            $cv = $cvs->getDetaultCvById($item['id']);
            if ($cv) {
                $disabled = 'disabled';
                $url = $cv['cv_file'];
            }

            // actions
            $actionbtns = '
            <div class="btn-group" role="group">
                <a href="' . $url . '" ' . $disabled . ' data-placement="top" title="View CV"  class="btn btn-info bstooltip" style="padding: 0rem .5em;" ><i style="font-size: 1.5em;"  class="mdi mdi-file-document" ></i></a>
            </div>
            ';

            $tableData[] = [$item['user_fname'] . ' ' . $item['user_lname'], $item['jobtitle'], $contactdetails, $item['sector_name'], $location, $actionbtns];
        }

        $drawtable = [
            "draw" => $tablevar['draw'],
            "recordsTotal" => $followedCandidates->getCandidatesDatatblrows($tablevar, $this->company->companyInfo()["company_id"]),
            "recordsFiltered" => $followedCandidates->getCandidatesDatatblrows($tablevar, $this->company->companyInfo()["company_id"]),
            "data" => $tableData,
        ];

        return $this->response->setJSON($drawtable);
    }

    /**
     * -------------------------------------------------------------------------
     *  Job credits page
     * -------------------------------------------------------------------------
     */

    public function jobCredits()
    {
        $bjc = new JobCreditsModel();
        $fbjc = new FeaturedJobsCreditsModel();
        $data = [
            'page' => "job credits",
            'bjcavaible' => $bjc->availableCredits(),
            'currentbjc' => $bjc->current(),
            'bjchistory' => $bjc->history(),
            'fbjcavaible' => $fbjc->availableCredits(),
            'currentfbjc' => $fbjc->current(),
            'fbjchistory' => $fbjc->history()
        ];
        return view('common_includes/admin_includes/head', $data)
            . view('common_includes/admin_includes/top-menu')
            . view('employers/includes/left-menu')
            . view('employers/company-job-credits')
            . view('common_includes/admin_includes/footer')
            . view('common_includes/admin_includes/jsplugin');
    }

    /**
     * -------------------------------------------------------------------------
     *  Transactions page
     * -------------------------------------------------------------------------
     */

    public function transactions()
    {
        $bjc = new JobCreditsModel();
        $fbjc = new FeaturedJobsCreditsModel();
        $data = [
            'page' => "credit transactions",
        ];
        return view('common_includes/admin_includes/head', $data)
            . view('common_includes/admin_includes/top-menu')
            . view('employers/includes/left-menu')
            . view('employers/transactions')
            . view('common_includes/admin_includes/footer')
            . view('common_includes/admin_includes/jsplugin');
    }

    // ------------ Transactions page - Data Tables-----------------
    public function getTransactionstbl()
    {

        $tablevar = $this->request->getVar();
        $transactions = new TransactionsModel();
        $dbtable = $transactions->getComapnyTransationsDataTable($tablevar, $this->company->companyInfo()["company_id"]);
        $tableData = [];
        foreach ($dbtable as $item) {


            // Transation type
            $trtype = '
            <span><img src="' . base_url('assets/images/bjc-1.svg') . '" style="width:10%;" /> ' . counted($item['credit_total'], ' Beyond Job Credit') . '</span>
            ';
            if ($item['credit_type'] == 'feature') {
                $trtype = '
                <span><img src="' . base_url('assets/images/bjc-2.svg') . '" style="width:10%;" /> ' . counted($item['credit_total'], ' Featured Credit') . '</span>
                
                ';
            }

            // Payment date
            $date = '
            <p style="margin: 0;" >Payment made on:  <b>' . date('d/m/Y H:i:s', strtotime($item['created_at'])) . '</b></p>
            <p style="margin: 0;" >Payment made by: <b>' . $item['user_fname'] . ' ' . $item['user_lname'] . '</b></p>
            ';

            // paymewnt method
            $paymethod = '
            <span class="badge badge-soft-primary" style="padding: 0;
            font-size: 3em;" ><i class="mdi mdi-credit-card-outline bstooltip" data-placement="top" title="Card Payment" ></i></span>
            ';
            if ($item['tr_type'] == 'bank') {
                $paymethod = '
                <span class="badge badge-soft-info" style="padding: 0;
                font-size: 3em;" ><i class="mdi mdi-bank-transfer bstooltip" data-placement="top" title="Bank Transfer" ></i></span>
                ';
            }

            // Status
            $status = '';
            switch ($item['tr_status']) {
                case 'approved':
                    $status = '
                    <span style="padding: 0;
                    font-size: 3em;" class="badge badge-soft-success" ><i class="mdi mdi-check-circle bstooltip" data-placement="top" title="Approved" ></i></span>
                    ';
                    break;
                case 'pending':
                    $status = '
                    <span style="padding: 0;
                    font-size: 3em;" class="badge badge-soft-warning" ><i class="mdi mdi-timer-sand bstooltip" data-placement="top" title="Pending" ></i></span>
                    ';
                    break;
                case 'declined':
                    $status = '
                    <span style="padding: 0;
                    font-size: 3em;" class="badge badge-soft-danger" ><i class="mdi mdi-close-octagon bstooltip" data-placement="top" title="Declined" ></i></span>
                    ';
                    break;
            }


            // actions
            $actionbtns = '
            ';

            $tableData[] = [$item['order_number'], $date, $trtype, number_format($item['tr_total'], 2) . " " . $item['tr_currency'], $paymethod, $status];
        }

        $drawtable = [
            "draw" => $tablevar['draw'],
            "recordsTotal" => $transactions->getComapnyTransationsDatatblrows($tablevar, $this->company->companyInfo()["company_id"]),
            "recordsFiltered" => $transactions->getComapnyTransationsDatatblrows($tablevar, $this->company->companyInfo()["company_id"]),
            "data" => $tableData,
        ];

        return $this->response->setJSON($drawtable);
    }


    /**
     * -------------------------------------------------------------------------
     *  Physical Interviews page
     * -------------------------------------------------------------------------
     */
    public function physicalinterview()
    {
        $data = [
            'page' => "physical interviews",
        ];
        return view('common_includes/admin_includes/head', $data)
            . view('common_includes/admin_includes/top-menu')
            . view('employers/includes/left-menu')
            . view('employers/company-physical-interviews')
            . view('common_includes/admin_includes/footer')
            . view('common_includes/admin_includes/jsplugin');
    }


    // ------------ Transactions page - Data Tables-----------------
    public function getPhysicalInterviewtbl()
    {
        $appliedjobs = new AppliyJobsModel();
        $savedcandidate = new SavedCandidatesModel();
        $interview = new InterviewModel();
        $tablevar = $this->request->getVar();
        $otherdata = [
            'canid' => "",
            'cid' => $this->company->companyInfo()["company_id"],
            'intype' => 'physical'
        ];
        $dbtable = $interview->getAllInterviewsDataTable($tablevar, $otherdata);
        $tableData = [];
        foreach ($dbtable as $item) {
            $actionbtns = '<div class="btn-group" role="group">';
            // Job Title
            $jtitle = '';
            if ($item['is_featured']) {
                $jtitle = '<span>' . $item['job_title'] . ' <img src="http://localhost:8080/assets/images/bjc-2.svg" style="width:4%;" alt="bjc-2" class="img-fluid"></span>';
            } else {
                $jtitle = '<span>' . $item['job_title'] . '</span>
                ';
            }



            // Hire btn

            // Status

            $status = '';
            switch ($item['interview_status']) {
                case 0:
                    $status = '
                        <span class="badge badge-soft-warning bstooltip" data-placement="top" title="Interview pending" style="font-size:1.8em;" ><i class="mdi mdi-timer-sand" ></i></span>
                    ';
                    if (time() > strtotime($item['interview_status'])) {
                        $status = '
                        <span class="badge badge-soft-danger bstooltip" data-placement="top" title="Expired" style="font-size:1.8em;" ><i class="mdi mdi-clock-alert" ></i></span>
                    ';
                    }
                    break;
                case 1:
                    $status = '
                            <span class="badge badge-soft-success bstooltip" data-placement="top" title="Interview Complete" style="font-size:1.8em;" ><i class="mdi mdi-check-circle" ></i></span>
                        ';
                    break;
            }

            // Candidate details
            $candidate = '
                <p style="font-size:1em;margin:0;" >' . $item['user_fname'] . ' ' . $item['user_lname'] . '</p>
                <p style="font-size: 0.9em;" >
                    <b>Email: </b> ' . $item['secret'] . '<br/>
                    <b>Phone: </b> ' . $item['user_phone'] . '
                
                </p>
                ';
            $actionbtns .= '
                <button type="button" data-placement="top" title="Interview Completed"  class="btn btn-success bstooltip completebtn" intid="' . $item['interview_id'] . '" style="padding: 0rem .5em;" ><i style="font-size: 1.5em;"  class="mdi mdi-check-circle" ></i></button>
                <button type="button" data-placement="top" title="View Application"  class="btn btn-primary bstooltip viewapplication" appid="' . $item['interview_applied_id'] . '" style="padding: 0rem .5em;" ><i style="font-size: 1.5em;"  class="mdi mdi-file-account" ></i></button>
                ';
            if ($savedcandidate->isSaved($item['interview_candidate_id'])) {
                $actionbtns .= '
                    <button type="button" data-placement="top" title="View/Edit Saved candidate notes"  class="btn btn-warning bstooltip savecandidate-btn" uid="' . $item['interview_candidate_id'] . '" style="padding: 0rem .5em;" ><i style="font-size: 1.5em;"  class="mdi mdi-account-details" ></i></button>
                    ';
            } else {
                $actionbtns .= '
                    <button type="button" data-placement="top" title="Save candidate for later"  class="btn btn-warning bstooltip savecandidate-btn" uid="' . $item['interview_candidate_id'] . '" style="padding: 0rem .5em;" ><i style="font-size: 1.5em;"  class="mdi mdi-account-star" ></i></button>
                    ';
            }

            $otherinfo = '
                <p style="margin: 0;" ><b>Date & Time:</b> ' . date('d/m/Y h:i:00 A', strtotime($item['interview_datetime'])) . '</p>
                <p><b>Contact Person:</b> ' . $item['contact_person_name'] . ', ' . $item['contact_person_email'] . ', ' . $item['contact_person_phone'] . '</p>
                ';

            $actionbtns .= '
            <button type="button" data-placement="top" title="Delete Interview"  class="btn btn-danger bstooltip deletebtn" intid="' . $item['interview_id'] . '" style="padding: 0rem .5em;" ><i style="font-size: 1.5em;"  class="mdi mdi-delete" ></i></button>
              </div>
              ';

            $tableData[] = [$candidate, $jtitle, $otherinfo, $status, $actionbtns];
        }

        $drawtable = [
            "draw" => $tablevar['draw'],
            "recordsTotal" => $interview->getAllInterviewsDatatblrows($tablevar, $otherdata),
            "recordsFiltered" => $interview->getAllInterviewsDatatblrows($tablevar, $otherdata),
            "data" => $tableData,
        ];

        return $this->response->setJSON($drawtable);
    }

    // interview update
    public function updateInterview()
    {
        $data = $this->request->getRawInput();
        $interview = new InterviewModel();
        switch ($this->request->getMethod()) {
            case 'put':
                $data['interview_status'] = 1;
                $interview->save($data);
                break;
            case 'delete':
                $interview->delete($data);
                break;
        }
    }

    /**
     * -------------------------------------------------------------------------
     *  Online Interviews page
     * -------------------------------------------------------------------------
     */
    public function onlineinterview()
    {
        $data = [
            'page' => "online interviews",
        ];
        return view('common_includes/admin_includes/head', $data)
            . view('common_includes/admin_includes/top-menu')
            . view('employers/includes/left-menu')
            . view('employers/company-online-interviews')
            . view('common_includes/admin_includes/footer')
            . view('common_includes/admin_includes/jsplugin');
    }


    // ------------ Transactions page - Data Tables-----------------
    public function getOnlineInterviewtbl()
    {
        $appliedjobs = new AppliyJobsModel();
        $savedcandidate = new SavedCandidatesModel();
        $interview = new InterviewModel();
        $tablevar = $this->request->getVar();
        $otherdata = [
            'canid' => "",
            'cid' => $this->company->companyInfo()["company_id"],
            'intype' => 'online'
        ];
        $dbtable = $interview->getAllInterviewsDataTable($tablevar, $otherdata);
        $tableData = [];
        foreach ($dbtable as $item) {
            // Job Title
            $jtitle = '';
            if ($item['is_featured']) {
                $jtitle = '<span>' . $item['job_title'] . ' <img src="http://localhost:8080/assets/images/bjc-2.svg" style="width:4%;" alt="bjc-2" class="img-fluid"></span>';
            } else {
                $jtitle = '<span>' . $item['job_title'] . '</span>
                ';
            }



            // Hire btn

            // Status

            $status = '';
            $interviewcompltedbtn='<button type="button" data-placement="top" title="Interview Completed"  class="btn btn-success bstooltip completebtn" intid="' . $item['interview_id'] . '" style="padding: 0rem .5em;" ><i style="font-size: 1.5em;"  class="mdi mdi-check-circle" ></i></button>';
            switch ($item['interview_status']) {
                case 0:
                    $status = '
                        <span class="badge badge-soft-warning bstooltip" data-placement="top" title="Interview pending" style="font-size:1.8em;" ><i class="mdi mdi-timer-sand" ></i></span>
                    ';
                    if (time() > strtotime($item['interview_datetime'])) {
                        $status = '
                        <span class="badge badge-soft-danger bstooltip" data-placement="top" title="Expired" style="font-size:1.8em;" ><i class="mdi mdi-clock-alert" ></i></span>
                    ';
                    }
                    break;
                case 1:
                    $interviewcompltedbtn='';
                    $status = '
                            <span class="badge badge-soft-success bstooltip" data-placement="top" title="Interview Complete" style="font-size:1.8em;" ><i class="mdi mdi-check-circle" ></i></span>
                        ';
                    break;
            }

            // Candidate details
            $candidate = '
                <p style="font-size:1em;margin:0;" >' . $item['user_fname'] . ' ' . $item['user_lname'] . '</p>
                <p style="font-size: 0.9em;" >
                    <b>Email: </b> ' . $item['secret'] . '<br/>
                    <b>Phone: </b> ' . $item['user_phone'] . '
                
                </p>
                ';

            // Join interview
            $actionbtns = '';
            $joinorviewbtn='';
            if (time() < strtotime($item['interview_datetime'] . ' +1 day')) {
                $joinorviewbtn= '<a href="' . base_url(route_to('employer.join')) . '?interview=' . urlencode(base64_encode($item['interview_id'])) . '" data-placement="top" title="Start Interview"  class="btn btn-info bstooltip startbtn" style="padding: 0rem .5em;width:100%;" ><i style="font-size: 1.5em;"  class="mdi mdi-video" ></i></a>
    <hr style="margin:6px;" />';
            }

            if($item['interview_status']==1){
                $joinorviewbtn= '<button  data-placement="top" title="Watch the Video" intid="'.$item['interview_id'].'"  class="btn btn-success bstooltip videobtn" style="padding: 0rem .5em;width:100%;" ><i style="font-size: 1.5em;"  class="mdi mdi-video" ></i></button>
                <hr style="margin:6px;"/>';
            }

            $actionbtns .= '
            '.$joinorviewbtn.'
            <div class="btn-group" role="group">
                '.$interviewcompltedbtn.'
                <button type="button" data-placement="top" title="View Application"  class="btn btn-primary bstooltip viewapplication" appid="' . $item['interview_applied_id'] . '" style="padding: 0rem .5em;" ><i style="font-size: 1.5em;"  class="mdi mdi-file-account" ></i></button>
                ';
            if ($savedcandidate->isSaved($item['interview_candidate_id'])) {
                $actionbtns .= '
                    <button type="button" data-placement="top" title="View/Edit Saved candidate notes"  class="btn btn-warning bstooltip savecandidate-btn" uid="' . $item['interview_candidate_id'] . '" style="padding: 0rem .5em;" ><i style="font-size: 1.5em;"  class="mdi mdi-account-details" ></i></button>
                    ';
            } else {
                $actionbtns .= '
                    <button type="button" data-placement="top" title="Save candidate for later"  class="btn btn-warning bstooltip savecandidate-btn" uid="' . $item['interview_candidate_id'] . '" style="padding: 0rem .5em;" ><i style="font-size: 1.5em;"  class="mdi mdi-account-star" ></i></button>
                    ';
            }

            $otherinfo = '
                <p style="margin: 0;" ><b>Date & Time:</b> ' . date('d/m/Y h:i:00 A', strtotime($item['interview_datetime'])) . '</p>
                <p><b>Attendee:</b> ' . $item['contact_person_name'] . ', ' . $item['contact_person_email'] . ', ' . $item['contact_person_phone'] . '</p>
                ';

            $actionbtns .= '
            <button type="button" data-placement="top" title="Delete Interview"  class="btn btn-danger bstooltip deletebtn" intid="' . $item['interview_id'] . '" style="padding: 0rem .5em;" ><i style="font-size: 1.5em;"  class="mdi mdi-delete" ></i></button>
              </div>
              ';

            $tableData[] = [$candidate, $jtitle, $otherinfo, $status, $actionbtns];
        }

        $drawtable = [
            "draw" => $tablevar['draw'],
            "recordsTotal" => $interview->getAllInterviewsDatatblrows($tablevar, $otherdata),
            "recordsFiltered" => $interview->getAllInterviewsDatatblrows($tablevar, $otherdata),
            "data" => $tableData,
        ];

        return $this->response->setJSON($drawtable);
    }

    // Join Interview
    public function interviewJoin()
    {
        $interview = new InterviewModel();
        $agoraToken = new AgoraToken();
        $applied = new AppliyJobsModel();
        $agoraRecording = new AgoraRecording();
        $intid = $this->request->getVar("interview");
        $intid = urldecode(base64_decode($intid));

        $curinterview = $interview->find($intid);
        if ($curinterview == null || time() > strtotime($curinterview['interview_datetime'] . ' +1 day')) {
            $this->Web_error_msg($this::INTERVIEW_NOT_FOUND);
            return redirect()->route('employer.onlineinterviews');
        }

        $token = $agoraToken->generate($curinterview['online_channel']);
        $resource = $agoraRecording->acquire($curinterview['online_channel'], $token['token']);


        $details = $applied->appliedJobById($curinterview['interview_applied_id']);

        $interview->save([
            'interview_id' => $curinterview['interview_id'],
            'online_resource_id' => json_decode($resource)->resourceId
        ]);

        $data = [
            'page' => "join interview",
            'access' => $token,
            'details' => $details,
            'interview' => $curinterview,
            'interid' => $this->request->getVar("interview")
        ];
        return view('common_includes/admin_includes/head', $data)
            . view('common_includes/admin_includes/top-menu')
            . view('employers/includes/left-menu')
            . view('employers/join-interview')
            . view('common_includes/admin_includes/footer')
            . view('common_includes/admin_includes/jsplugin');
    }

    public function recordinterview()
    {
        $agoraToken = new AgoraToken();
        $agoraRecording = new AgoraRecording();
        $interview = new InterviewModel();

        $intid = $this->request->getVar("interview");
        $intid = urldecode(base64_decode($intid));

        $curinterview = $interview->find($intid);

        $token = $agoraToken->generate($curinterview['online_channel']);

        $recordstart = $agoraRecording->start($curinterview['online_resource_id'], $curinterview['online_channel'], $token['token']);

        $interview->save([
            'interview_id' => $curinterview['interview_id'],
            'online_sid' => json_decode($recordstart)->sid
        ]);
    }

    public function stoprecord()
    {
        $agoraToken = new AgoraToken();
        $agoraRecording = new AgoraRecording();
        $interview = new InterviewModel();

        $intid = $this->request->getVar("interview");
        $intid = urldecode(base64_decode($intid));

        $curinterview = $interview->find($intid);

        $token = $agoraToken->generate($curinterview['online_channel']);

        $stoprec = $agoraRecording->stop($curinterview['online_resource_id'], $curinterview['online_sid'], $curinterview['online_channel'], $token['token']);
        $respon = json_decode($stoprec);
        $interview->save([
            'interview_id' => $curinterview['interview_id'],
            'interview_status' => 1,
            'online_video' => getenv('aws.s3.url').$respon->serverResponse->fileList[0]->fileName
        ]);
    }
}
