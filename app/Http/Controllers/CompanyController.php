<?php

namespace App\Http\Controllers;

/**
 * :: Company Controller ::
 * To manage companies.
 *
 **/

use App\Http\Controllers\Controller;
use App\Company;
use App\BankMaster;
use App\Currency;
use App\DateTimeFormat;
use Illuminate\Http\Request;
use App\InvoiceSetting;
use App\Setting;
use App\Theme;
use App\Timestamp;
use App\Timezone;

class CompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('company.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $countries      = getCountries();
        $states         = getStates();
        $cities         = getCities();
        $timezone = (new Timestamp)->getTimeStampsService();
        return view('company.create', compact('timezone','states', 'countries', 'cities'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $inputs = $request->all();
        $tab  = 1;
        $validator = (new Company)->validateCompany($inputs, $tab);
        if ($validator->fails()) {
            return validationResponse(false, 206, "", "", $validator->messages());
        }

        try {
            \DB::beginTransaction();
            $state = $inputs['state'];
            unset($inputs['state']);

            $inputs = $inputs + [
                'registration_date' => convertToUtc(),
                'state_id'          => $state,
                'created_by'        => authUserId()
            ];
            $id = (new Company)->store($inputs);
            $save = [
                'company_id'    => $id,
                'terms' => lang('company.terms'),
                'created_by'    => authUserId()
            ];
            (new InvoiceSetting)->store($save);
            \DB::commit();
            $langMessage = lang('messages.created', lang('company.company'));
            $route = route('company.index');
            return validationResponse(true, 201, $langMessage, $route);
        } catch (\Exception $exception) {
            \DB::rollBack();
            return validationResponse(false, 207, $exception->getMessage().' - '.lang('messages.server_error'));
        }
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param Request $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id = null)
    {
        $company = Company::find($id);
        if (!$company) {
            abort(404);
        }
        $tab            = $request->get('tab', '1');
        $company        = (new Company)->getCompanyInfo($id);
        $setting        = (new Setting)->getSettingByCompanyId($id);
        $currency       = (new Currency)->getCurrencyService();
        $timezone       = (new Timezone)->getTimezoneService();
        $theme          = (new Theme)->getThemeService();
        $dateTimeFormat = (new DateTimeFormat)->getDateTimeFormatService();
        $countries      = getCountries();
        $states         = getStates();
        $cities         = getCities();
        return view('company.edit', compact('company', 'countries', 'cities', 'setting', 'currency', 'timezone', 'theme', 'dateTimeFormat', 'tab', 'id', 'states'));
    }


    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $company = Company::find($id);
        $inputs = $request->all();
        $tab = $inputs['tab'];
        unset($inputs['tab']);

        if (!$company) {
            $langMessage = lang('messages.invalid_id', string_manip(lang('company.company')));
            $route = route('company.index'); //, ['id' => $id, 'tab' => $tab]
            return validationResponse(false, 207, $langMessage, $route);
        }

        $validator = (new Company)->validateCompany($inputs, $tab);
        if ($validator->fails()) {
            return validationResponse(false, 206, "", "", $validator->messages());
        }

        try {
            \DB::beginTransaction();

            // tab == 1 for company profile detail
            if($tab == 1) {
                $state = $inputs['state'];
                unset($inputs['state']);
                $inputs = $inputs + ['state_id' => $state ];
                if(!array_key_exists('is_full_version', $inputs)) {
                    $inputs = $inputs + ['is_full_version' => 0 ];
                }
                (new Company)->store($inputs, $id);
            }
            // tab == 2 for company logo
            else if($tab == 2) {
                $companyLogo = $request->file('company_logo');
                $oldCompanyLogo = $company->company_logo;
                $fileName = str_random(6) . '_' . str_replace(' ', '_', $companyLogo->getClientOriginalName());
                $folder = ROOT . \Config::get('constants.UPLOADS');
                if ($companyLogo->move($folder, $fileName)) {
                    if (!empty($oldCompanyLogo) && file_exists($folder . $oldCompanyLogo)) {
                        unlink($folder . $oldCompanyLogo);
                    }
                }
                $data = [
                    'company_logo' => $fileName,
                    'updated_by' => authUserId()
                ];
                (new Company)->store($data, $id);
            }
            // tab == 2 for company logo
            else if($tab == 3) {
                $data = [
                    'company_id' => $id,
                    'currency_id' => $inputs['currency'],
                    'timezone_id' => $inputs['timezone'],
                    'datetime_format_id' => $inputs['datetime_format'],
                    'theme_id' => $inputs['theme'],
                    'is_email_enable' => (isset($inputs['is_email_enable']) && $inputs['is_email_enable'] == '1')?1:0,
                    'is_sms_enable' => (isset($inputs['is_sms_enable']) && $inputs['is_sms_enable'] == '1')?1:0,
                    'status' => (isset($inputs['status']) && $inputs['status'] == '1')?1:0
                ];

                $setting = (new Setting)->getSettingByCompanyId($id);
                if(!$setting){
                    $data['created_by'] = authUserId();
                    (new Setting)->store($data);
                }else{
                    $data['updated_by'] = authUserId();
                    (new Setting)->store($data, $id);
                }
            }

            \DB::commit();
            $langMessage = lang('messages.updated', lang('company.company'));
            $route = route('company.edit', ['id' => $id, 'tab' => $tab]);
            return validationResponse(true, 201,$langMessage, $route);

        } catch (Exception $e) {
            \DB::rollback();
            return validationResponse(false, 207, $e->getMessage().' - '.lang('messages.server_error'));
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function companyAddBank(Request $request, $id)
    {
        $company = Company::find($id);
        if (!$company) {
            $langMessage = lang('messages.invalid_id', string_manip(lang('company.company')));
            $route = route('company.index');
            return validationResponse(false, 207, $langMessage, $route);
        }

        $inputs = $request->all();
        $validator = (new BankMaster)->validateBank($inputs);
        if ($validator->fails()) {
            //redirect()->route('company.edit', ['id' => $id, 'tab' => 2, 'show' => 1])
            return validationResponse(false, 206, "", "", $validator->messages());
        }

        try {
            \DB::beginTransaction();
            $inputs = $inputs + [
                'company_id'    => $id,
                'updated_by'    => authUserId(),
            ];
            (new BankMaster)->store($inputs, $id);
            \DB::commit();

            $langMessage = lang('messages.added', lang('company.bank_detail'));
            $route = route('company.edit', ['id' => $id, 'tab' => 2]);
            return validationResponse(true, 201,$langMessage, $route);
        } catch (\Exception $exception) {
            \DB::rollBack();
            return validationResponse(false, 207, $exception->getMessage().' - '.lang('messages.server_error'));
        }
    }

    /**
     * Show the form for editing the specified resource.
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function companyEditBank($id)
    {
        $result = BankMaster::find($id);
        if (!$result) {
            abort(404);
        }
        return view('company.edit-bank', compact('result'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function companyUpdateBank(Request $request, $id)
    {
        $bank = BankMaster::find($id);
        if (!$bank) {
            $langMessage = lang('messages.invalid_id', string_manip(lang('company.bank_detail')));
            $route = route('company.index');
            return validationResponse(false, 207, $langMessage, $route);
        }

        $inputs = $request->all();
        $validator = (new BankMaster)->validateBank($inputs, $id);
        if ($validator->fails()) {
            //redirect()->route('company.edit', ['id' => $inputs['company_id'], 'tab' => 2])
            return validationResponse(false, 206, "", "", $validator->messages());
        }

        try {
            \DB::beginTransaction();
            $inputs = $inputs + [
                'company_id'    => $id,
                'updated_by'    => authUserId(),
            ];
            (new BankMaster)->store($inputs, $id);
            \DB::commit();
            $langMessage = lang('messages.updated', lang('company.bank_detail'));
            $route       = route('company.edit', ['id' => $inputs['company_id'], 'tab' => 2]);
            return validationResponse(true, 201, $langMessage, $route);
        } catch (\Exception $exception) {
            \DB::rollBack();
            //->route('company.edit', ['id' => $inputs['company_id'], 'tab' => 2])
            return validationResponse(false, 207, $exception->getMessage().' - '.lang('messages.server_error'));
        }
    }



    /**
     * Used to update company active status.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function companyToggle($id)
    {
        if (!\Request::isMethod('post') && !\Request::ajax()) {
            return lang('messages.server_error');
        }

        try {
            $company = Company::find($id);

            $company->update(['status' => !$company->status]);
            $response = ['status' => 1, 'data' => (int)$company->status . '.gif'];
            // return json response
            return json_encode($response);

        } catch (\Exception $exception) {
            return lang('messages.invalid_id', string_manip(lang('company.company')));
        }
    }

    /**
     * Used to load more records and render to view.
     *
     * @param Request $request
     * @param int $pageNumber
     * @return \Illuminate\Http\Response
     */
    public function companyPaginate(Request $request, $pageNumber = null)
    {
        if (!\Request::isMethod('post') && !\Request::ajax()) {
            return lang('messages.server_error');
        }

        $inputs = $request->all();
        $page = 1;
        if (isset($inputs['page']) && (int)$inputs['page'] > 0) {
            $page = $inputs['page'];
        }

        $perPage = 20;
        if (isset($inputs['perpage']) && (int)$inputs['perpage'] > 0) {
            $perPage = $inputs['perpage'];
        }

        $start = ($page - 1) * $perPage;
        if (isset($inputs['form-search']) && $inputs['form-search'] != '') {
            $inputs = array_filter($inputs);
            unset($inputs['_token']);

            $data = (new Company)->getCompany($inputs, $start, $perPage);
            $total = (new Company)->totalCompany($inputs);
            $total = $total->total;
        } else {
            $data = (new Company)->getCompany($inputs, $start, $perPage);
            $total = (new Company)->totalCompany($inputs);
            $total = $total->total;
        }

        return view('company.load_data', compact('data', 'total', 'page', 'perPage'));
    }

    /**
     * Method is used to update status of group enable/disable
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function companyAction(Request $request)
    {
        $inputs = $request->all();
        if (!isset($inputs['tick']) || count($inputs['tick']) < 1) {
            return redirect()->route('company.index')
                ->with('error', lang('messages.atleast_one', string_manip(lang('company.company'))));
        }

        $ids = '';
        foreach ($inputs['tick'] as $key => $value) {
            $ids .= $value . ',';
        }

        $ids = rtrim($ids, ',');
        $status = 0;
        if (isset($inputs['active'])) {
            $status = 1;
        }

        Company::whereRaw('id IN (' . $ids . ')')->update(['status' => $status]);
        return redirect()->route('company.index')
            ->with('success', lang('messages.updated', lang('company.company_status')));
    }

    /**
     * @param Request $request
     * @return String
     */
    public function mrUserSearch(Request $request)
    {
        if (!\Request::isMethod('post') && !\Request::ajax()) {
            return lang('messages.server_error');
        }
        $name = $request->get('name', '');
        $result = "";
        if ($name != "") {
            $data = (new Company)->companySearch($name);
            foreach($data as $detail) {
                $result[] = $detail->id . "|" . $detail->first_name . " " . $detail->last_name . " (" . $detail->phone . ")";
            }
            echo json_encode($result);
        }
    }

    /**
     * @return String
     */
    public function getCompanysSearch($id)
    {
        if (!\Request::isMethod('post') && !\Request::ajax()) {
            return lang('messages.server_error');
        }
        $result = (new Company)->companySearch($id);
        $options = '';
        foreach($result as $key => $value) {
            $options .='<option value="'. $key .'">' . $value . '</option>';
        }
        echo $options;
    }
}
