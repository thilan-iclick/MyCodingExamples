<?php

/**
 * This Model will handle all the Jobs functionalities of the BeyondJobs as requires in 
 * the scope
 *
 * Created on: 15/09/2022
 * 
 * Last Modified on:
 * Last Modified by: 
 * 
 * @author Thilan Pathirage
 * @copyright (c) 2022, ICLICK Online Technologies
 */

namespace App\Models;

use CodeIgniter\I18n\Time;
use CodeIgniter\Model;

class JobsModel extends Model
{
    // Model Configurations
    protected $table      = 'jobs';
    protected $primaryKey = 'job_id';
    protected $useAutoIncrement = true;
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;

    protected $allowedFields = [
        'job_title',
        'description',
        'expire_on',
        'is_featured',
        'views_cnt',
        'job_status',
        'address',
        'city_id',
        'district_id',
        'country_id',
        'loc_type',
        'company_id',
        'sector_id',
        'job_type_id',
        'job_modality_id',
        'min_q_id',
        'min_ex_id',
        'posted_by',
        'approved_by',
        'is_filled',
        'edit_job_title',
        'edit_job_description',
        'edit_min_q',
        'edit_min_ex'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $beforeInsert = ['addUserData'];
    protected $afterInsert = ['deductjobcredits'];

    public function addUserData(array $data)
    {
        $company = new CompanyModel();
        $countries = new CountriesModel();
        if (!isset($data['data']['company_id'])) {
            $data['data']['company_id'] = $company->companyInfo()["company_id"];
        }
        if (isset($data['data']['company_id'])) {
            $data['data']['job_status'] = 2;
        }
        $data['data']['posted_by'] = auth()->id();
        if (!$countries->islocal($data['data']['country_id'])) {
            $data['data']['loc_type'] = 2;
            $data['data']['city_id'] = null;
            $data['data']['district_id'] = null;
        }
        return $data;
    }

    public function deductjobcredits(array $data)
    {
        $companyid = 0;
        if (array_key_exists('company_id', $data['data'])) {
            $companyid = $data['data']['company_id'];
        }
        if (array_key_exists('is_featured', $data['data'])) {
            if ($data['data']['is_featured'] == 1) {
                $fcredit = new FeaturedJobsCreditsModel();
                $fcredit->deduct($companyid);
            }
        }
        $credits = new JobCreditsModel();
        $credits->deduct($companyid);
    }

    public function jobPagesPerSector($sector, $offset, $job, $company = null)
    {
        $mainqr = $this->where('job_status', 2)->where('is_featured', $job)->where('expire_on>', date('Y-m-d H:i:s'));
        if ($sector != 'all') {
            $mainqr->where('sector_id', $sector);
        }
        if ($company != null) {
            $mainqr->where('company_id', $company);
        }
        return ceil(($mainqr->countAllResults() / $offset));
    }

    public function jobsMainSearch($data, $offset, $job, $countonly = false)
    {
        $mainqr = $this
            ->select('jobs.job_id, jobs.company_id, jobs.description, companies.companytitle, companies.pro_pic, jobs.job_title, jobs.approved_on, districts.name_en as "district", countries.country_name, exlevel_name, qlevel_name, sector_name, jobtypes_name, loc_type')
            ->join('companies', 'companies.company_id=jobs.company_id')
            ->join('cities', 'cities.cities_id=jobs.city_id')
            ->join('districts', 'districts.districts_id=jobs.district_id')
            ->join('countries', 'countries.countries_id=jobs.country_id')
            ->join('job_sectors', 'job_sectors.sectors_id=jobs.sector_id')
            ->join('qualification_levels', 'qualification_levels.qlevel_id=jobs.min_q_id')
            ->join('experiance_levels', 'experiance_levels.exlevel_id=jobs.min_ex_id')
            ->join('jobtypes', 'jobtypes.jobtypes_id=jobs.job_type_id')
            ->where('job_status', 2)
            ->where('is_featured', $job)
            ->where('expire_on>', date('Y-m-d H:i:s'));

        // Search by Job Title
        if ($data->title != "") {
            $mainqr->like('job_title', $data->title);
        }

        // Serach by sector
        if ($data->sector != 'all') {
            $mainqr->where('jobs.sector_id', $data->sector);
        }
        // Search by company
        if ($data->company != "") {
            $mainqr->where('jobs.company_id', $data->company);
        }
        // Search by district
        if ($data->district != "") {
            $mainqr->where('jobs.district_id', $data->district);
        }

        // Search by city
        if ($data->city != "") {
            $mainqr->where('jobs.city_id', $data->city);
        }

        // Search by city
        if ($data->country != "") {
            $mainqr->where('jobs.country_id', $data->country);
        }

        // Search by Modality
        if (!empty($data->jobModality)) {
            $mainqr->whereIn('job_modality_id', $data->jobModality);
        }

        // Search by Type
        if (!empty($data->jobType)) {
            $mainqr->whereIn('job_type_id', $data->jobType);
        }

        // search by posted dates
        if ($data->datePosted != '') {
            switch ($data->datePosted) {
                case "0":
                    $mainqr->where('approved_on >= NOW()- INTERVAL 1 HOUR');
                    break;
                case "1":
                    $mainqr->where('approved_on >= NOW()- INTERVAL 24 HOUR');
                    break;
                case "2":
                    $mainqr->where('approved_on >= NOW()- INTERVAL 7 DAY');
                    break;
            }
        }


        // Order by approved date
        $mainqr->orderBy('approved_on', $data->sorter);

        if ($countonly) {
            return $mainqr->countAllResults();
        } else {
            // Limit result
            $pg = ((intval($data->pageNumber) - 1) * intval($offset));
            if ($pg <= 0) {
                return  $mainqr->findAll($offset);
            } else {
                return  $mainqr->findAll($pg, $offset);
            }
        }
    }

    public function getAllfilterCounts($keyword, $sector)
    {
        $dataset = [];
        $qry = [
            'job_status' => 2,
            'expire_on >' => date('Y-m-d H:i:s')
        ];
        $likeqry = [];
        // Search by Job Title
        if ($keyword != "") {
            $likeqry['job_title'] = $keyword;
        }

        // Serach by sector
        if ($sector != 'all') {
            $qry['jobs.sector_id'] = $sector;
        }

        $dataset['alljobs'] = $this->where($qry)->like($likeqry)->countAllResults();
        $dataset['local'] = $this->where($qry)->like($likeqry)->where('loc_type', 'local')->countAllResults();
        $dataset['inter'] = $this->where($qry)->like($likeqry)->where('loc_type', 'international')->countAllResults();
        $jobtypes = new JobsTypesModel();
        $dataset['jobtypes'] = [];
        foreach ($jobtypes->findAll() as $jtype) {
            $dataset['jobtypes'][$jtype['jobtypes_id']] = $this->where($qry)->like($likeqry)->where('job_type_id', $jtype['jobtypes_id'])->countAllResults();
        }
        $dataset['jobmodalitys'] = [];
        $jobmodality = new JobsModalitiesModel();
        foreach ($jobmodality->findAll() as $jmodal) {
            $dataset['jobmodalitys'][$jmodal['jobsmodality_id']] = $this->where($qry)->like($likeqry)->where('job_modality_id', $jmodal['jobsmodality_id'])->countAllResults();
        }

        $dataset['jobdateposted']['0'] = $this->where('approved_on >= NOW()- INTERVAL 1 HOUR')->where($qry)->like($likeqry)->countAllResults();
        $dataset['jobdateposted']['1'] = $this->where('approved_on >= NOW()- INTERVAL 24 HOUR')->where($qry)->like($likeqry)->countAllResults();
        $dataset['jobdateposted']['2'] = $this->where('approved_on >= NOW()- INTERVAL 7 DAY')->where($qry)->like($likeqry)->countAllResults();

        return $dataset;
    }

    public function isFavorite($jid)
    {
        $favjobs = new FavoriteJobsModel();
        return $favjobs->isFavorite($jid);
    }

    public function isApplied($jid)
    {
        $applied = new AppliyJobsModel();
        return $applied->isApplied($jid);
    }

    public function markOrUnmarkAsFavorite($jid, $status)
    {
        $favjobs = new FavoriteJobsModel();
        $data = ['job' => $jid, 'candidate' => auth()->id()];
        if ($status == 'true') {
            $favjobs->save($data);
        } else {
            $favjobs->where($data)->delete();
        }
    }

    public function getJob($jid)
    {

        $job = $this
            ->select('jobs.*, companies.companytitle, companies.pro_pic, companies.cover_pic, companies.phone, companies.address, districts.name_en as "district", countries.country_name, exlevel_name, qlevel_name, sector_name, jobs.sector_id, jobtypes_name, jobsmodality_name, approved_on')
            ->join('companies', 'companies.company_id=jobs.company_id')
            ->join('cities', 'cities.cities_id=jobs.city_id')
            ->join('districts', 'districts.districts_id=jobs.district_id')
            ->join('countries', 'countries.countries_id=jobs.country_id')
            ->join('job_sectors', 'job_sectors.sectors_id=jobs.sector_id')
            ->join('qualification_levels', 'qualification_levels.qlevel_id=jobs.min_q_id')
            ->join('experiance_levels', 'experiance_levels.exlevel_id=jobs.min_ex_id')
            ->join('jobtypes', 'jobtypes.jobtypes_id=jobs.job_type_id')
            ->join('job_modalities', 'job_modalities.jobsmodality_id=jobs.job_modality_id')
            ->where('job_id', $jid)
            ->whereIn('job_status', [2, 3])
            ->where('expire_on>', date('Y-m-d H:i:s'))
            ->first();
        if ($job) {
            $applied = new AppliyJobsModel();
            $favjobs = new FavoriteJobsModel();
            $time = new Time($job['approved_on']);
            $job['appliedcnt'] = $applied->where('job', $jid)->countAllResults();
            $job['approvehuman'] = $time->humanize();
            $job['isapplied'] = $applied->isApplied($jid);
            $job['isfav'] = $favjobs->isFavorite($jid);
        }

        return $job;
    }

    public function getJobOnly($jid)
    {
        return $this->where('job_id', $jid)->first();
    }


    public function jobsByCompany($cid)
    {
        return $this
            ->where('company_id', $cid)
            ->where('job_status', 2)
            ->where('expire_on>', date('Y-m-d H:i:s'));
    }

    public function similarJobs($job)
    {
        return $this
            ->join('companies', 'companies.company_id=jobs.company_id')
            ->join('jobtypes', 'jobtypes.jobtypes_id=jobs.job_type_id')
            ->where('job_id!=', $job['job_id'])
            ->where('sector_id', $job['sector_id'])
            ->where('job_status', 2)
            ->where('expire_on>', date('Y-m-d H:i:s'))
            ->orderBy('job_title', 'RANDOM');
    }

    public function expressApply($data)
    {
        $data['applytype'] = 2;
        $apply = new AppliyJobsModel();
        $apply->save($data);
    }

    public function normalApply($data)
    {
        $data['applytype'] = 1;
        $data['candidate'] = auth()->id();
        $apply = new AppliyJobsModel();
        $apply->save($data);
    }

    public function appliedCount($jid)
    {
        $apply = new AppliyJobsModel();
        $apply->where('job', $jid)->countAllResults();
    }
    // get jobs list
    public function jobs_list_lazyloader($job, $page, $offset, $company)
    {
        $qry = $this->select('job_id,job_title');
        if ($job != null) {
            $qry->like('job_title', $job);
        }
        if ($company != null) {
            $qry->where('company_id', $company);
        }
        $qry->orderBy('job_title');
        $pg = ((intval($page) - 1) * intval($offset));
        if ($pg <= 0) {
            return  $qry->findAll($offset);
        } else {
            return  $qry->findAll($offset, $pg);
        }
    }


    public function getManagejobsrows($data, $company)
    {
        $mainquery = $this->_manageJobsdataTblMainSearch($data, $company);
        return $mainquery;
    }


    // Company Manage Jobs Data table datas
    public function getManageJobsDataTable($data, $company)
    {
        $mainquery = $this->_manageJobsdataTblMainSearch($data, $company);
        if ($data['length'] != -1) {
            return $mainquery->findAll($data['length'], $data['start']);
        } else {
            return $mainquery->findAll();
        }
    }

    // Company Manage Jobs Data table datas main query 
    private function _manageJobsdataTblMainSearch($data, $company)
    {
        $columns_order = [
            'job_title',
            'approved_on',
            'job_sectors.sector_name',
            'districts.name_en',
            '',
            'job_status'
        ];
        $default_order = ['job_title' => 'asc'];
        $columns_search = [
            'job_title',
            'approved_on',
            'expire_on',
            'job_sectors.sector_name',
            'districts.name_en',
            'cities.name_en',
            'countries.country_name'
        ];

        $mainquery = $this->select('job_id, is_local, is_featured, job_status, edit_job_title,company_id, 
        job_title,
        approved_on, 
        expire_on, 
        jobs.created_at,
        job_sectors.sector_name, 
        districts.name_en as "district",
        cities.name_en as "city",
        countries.country_name')
            ->join('job_sectors', 'job_sectors.sectors_id=jobs.sector_id', 'right')
            ->join('cities', 'cities.cities_id=jobs.city_id', 'right')
            ->join('districts', 'districts.districts_id=jobs.district_id', 'right')
            ->join('countries', 'countries.countries_id=jobs.country_id');

        $i = 0;
        foreach ($columns_search as $item) {
            if ($data['search']['value']) {
                if ($i === 0) {
                    $mainquery->groupStart()
                        ->like($item, $data['search']['value']);
                } else {
                    $mainquery->orLike($item, $data['search']['value']);
                }

                if (count($columns_search) - 1 == $i) {
                    $mainquery->groupEnd();
                }
            }
            $i++;
        }

        if($company!=0){
            $mainquery->where('company_id', $company);
        }
        

        if (isset($data['order'])) {
            $mainquery->orderBy($columns_order[$data['order']['0']['column']], $data['order']['0']['dir']);
        } else {
            $mainquery->orderBy(key($default_order), $default_order[key($default_order)]);
        }

        return $mainquery;
    }

    public function viewed($jid)
    {
        $jobcnt = $this->select('views_cnt')->find($jid);
        $jobcnt = intval($jobcnt['views_cnt']);
        $jobcnt++;
        $this->save([
            'job_id' => $jid,
            'views_cnt' => $jobcnt
        ]);
    }
}
