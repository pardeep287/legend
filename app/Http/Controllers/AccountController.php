<?php

namespace App\Http\Controllers;

/**
 * :: Account Controller ::
 * To manage party.
 *
 * @package Pay Track
 * @author  Ankush Abbi (ankush.abbi@gmail.com)
 **/

use App\City;
use App\Country;
use Illuminate\Http\Request;
//use App\Http\Controllers\Controller;
use App\Account;
use App\AccountGroup;
use App\State;
//use App\VoucherEntry;
//use Maatwebsite\Excel\Facades\Excel;

class AccountController extends Controller
{
   /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $currentRoute = explode('.', \Request::route()->getName());
        $routeMethod = reset($currentRoute);
        //$routeAction = end($currentRoute);
        if($routeMethod == 'supplier') {
            return view('supplier.index');
        }
        if($routeMethod == 'account') {
            return view('account.index');
        }
        if($routeMethod == 'customer') {
            return view('customer.index');
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $currentRoute = explode('.', \Request::route()->getName());
        $routeMethod = reset($currentRoute);
        //$routeAction = end($currentRoute);
        if($routeMethod == 'supplier') {
            $accountGroup = (new AccountGroup)->getAccountGroupService(['acc_type' => getCreditorId()]);
        }
        if($routeMethod == 'customer') {
            $accountGroup = (new AccountGroup)->getAccountGroupService(['acc_type' => getDebtorId()]);
        }
        if($routeMethod == 'account') {
            $accountGroup = (new AccountGroup)->getAccountGroupService(['acc_not_c' => getCreditorId(), 'acc_not_d' => getDebtorId()]);
        }

        $states = (new State)->getStateService();
        $country = (new Country)->getCountryService();
        $cities = (new City)->getCityService();

        //dd($states);
        if($routeMethod == 'supplier') {
            return view('supplier.create', compact('accountGroup','states','cities','country'));
        }
        if($routeMethod == 'customer') {
            return view('customer.create', compact('accountGroup','states','cities','country'));
        }
        if($routeMethod == 'account') {
            return view('account.create', compact('accountGroup','states','cities','country'));
        }

    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $inputs  = $request->all();
        $currentRoute = explode('.', \Request::route()->getName());
        $routeMethod = reset($currentRoute);
        //$routeAction = end($currentRoute);
        if($routeMethod == 'account') {
            $validator = (new Account)->validateAccountOnly($inputs);
        }
        else{
            $validator = (new Account)->validateAccount($inputs);
        }
        if ($validator->fails()) {
            return validationResponse(false, 206, "", "", $validator->messages());
        }
        try {
            \DB::beginTransaction();
                //dd($inputs);
            $accountGroup = $inputs['account_group'];
            unset($inputs['account_group']);
            $city = $inputs['city'];
            unset($inputs['city']);
            $state = $inputs['state'];
            unset($inputs['state']);
            $country = $inputs['country'];
            unset($inputs['country']);
            $drCrId = $inputs['d_c'];
            unset($inputs['d_c']);

            $inputs = $inputs + [
                'dr_cr_id'          =>  $drCrId,
                'city_id'           =>  $city,
                'state_id'          =>  $state,
                'country_id'        =>  $country,
                'account_group_id'  =>  $accountGroup,
                'created_by'        =>  authUserId(),
                'company_id'        =>  loggedInCompanyId(),
            ];
            //dd($inputs);
            (new Account)->store($inputs);
            $currentRoute = explode('.', \Request::route()->getName());
            $routeMethod = reset($currentRoute);
            //$routeAction = end($currentRoute);
            if($routeMethod == 'account') {
                $route = route('account.index');
                $lang = lang('messages.created', lang('account.account'));
            }if($routeMethod == 'supplier') {
                $route = route('supplier.index');
                $lang = lang('messages.created', lang('supplier.supplier'));
            }if($routeMethod == 'customer') {
                $route = route('customer.index');
                $lang = lang('messages.created', lang('customer.customer'));
            }

            \DB::commit();
            return validationResponse(true, 201, $lang, $route, [], []);
        } catch (\Exception $exception) {
            \DB::rollBack();
            return validationResponse(false, 207, $exception->getMessage() . $exception->getFile() .$exception->getLine() . lang('messages.server_error'));
        }
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $account = Account::find($id);
        if (!$account) {
            abort(404);
        }
       // dd($account->toArray());
        $currentRoute = explode('.', \Request::route()->getName());
        $routeMethod = reset($currentRoute);

        //$routeAction = end($currentRoute);
        if ($account->is_default == 1 ) {
            return redirect()->route('account.index')
                ->with('error', lang('messages.isdefault', string_manip(lang('account.account'))));
        }

        if($routeMethod == 'supplier') {
            $accountGroup = (new AccountGroup)->getAccountGroupService(['acc_type' => getCreditorId()]);
        }
        if($routeMethod == 'customer') {
            $accountGroup = (new AccountGroup)->getAccountGroupService(['acc_type' => getDebtorId()]);
        }
        if($routeMethod == 'account') {
            if ( $account->account_group_id == getCreditorId() || $account->account_group_id == getDebtorId() ) {
                return redirect()->route('account.index')
                    ->with('error', lang('messages.isdefault', string_manip(lang('account.account'))));
            }
            $accountGroup = (new AccountGroup)->getAccountGroupService(['acc_not_c' => getCreditorId(), 'acc_not_d' => getDebtorId()]);
        }
        $states = (new State)->getStateService();
        $country = (new Country)->getCountryService();
        $cities = (new City)->getCityService();



        if($routeMethod == 'account') {
            return view('account.edit', compact('account', 'accountGroup', 'states', 'country', 'cities'));
        }
        if($routeMethod == 'supplier') {

            return view('supplier.edit', compact('account', 'accountGroup', 'states', 'country', 'cities'));
        }
        if($routeMethod == 'customer') {
            return view('customer.edit', compact('account', 'accountGroup', 'states', 'country', 'cities'));
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request,$id)
    {
        $account = Account::find($id);
        if (!$account) {
            abort(404);
        }
        $inputs  = $request->all();
        $currentRoute = explode('.', \Request::route()->getName());
        $routeMethod = reset($currentRoute);
        //$routeAction = end($currentRoute);
        if($routeMethod == 'account') {
            $validator = (new Account)->validateAccountOnly($inputs,$id);
        }
        else{
            $validator = (new Account)->validateAccount($inputs,$id);
        }
        if ($validator->fails()) {
            return validationResponse(false, 206, "", "", $validator->messages());
        }

        try {
            \DB::beginTransaction();
            $accountGroup = $inputs['account_group'];
            unset($inputs['account_group']);
            $city = $inputs['city'];
            unset($inputs['city']);
            $state = $inputs['state'];
            unset($inputs['state']);
            $country = $inputs['country'];
            unset($inputs['country']);
            $drCrId = $inputs['d_c'];
            unset($inputs['d_c']);
            $default = 0;
            if(array_key_exists('is_default', $inputs)){
                $default = $inputs['is_default'];
                unset($inputs['is_default']);
            }
            $inputs = $inputs + [
                'dr_cr_id'          =>  $drCrId,
                'is_default'        =>  $default,
                'city_id'           =>  $city,
                'state_id'          =>  $state,
                'country_id'        =>  $country,
                'account_group_id'  =>  $accountGroup,
                'updated_by'        =>  \Auth::user()->id
            ];
            //dd($inputs);
            (new Account)->store($inputs, $id);
            $submitData = [];

            if($routeMethod == 'account') {
                $route = route('account.index');
                $lang = lang('messages.updated', lang('account.account'));
            }
            if($routeMethod == 'supplier') {
                $route = route('supplier.index');
                $lang = lang('messages.updated', lang('supplier.supplier'));
            }
            if($routeMethod == 'customer') {
                $route = route('customer.index');
                $lang = lang('messages.updated', lang('customer.customer'));
            }

            \DB::commit();
            return validationResponse(true, 201, $lang, $route, [], $submitData);
        } catch (\Exception $exception) {
            \DB::rollBack();
            return validationResponse(false, 207, $exception->getMessage() . $exception-> getFile() . $exception->getLine() . lang('messages.server_error'));
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function drop($id)
    {
        if (!\Request::ajax()) {
            return lang('messages.server_error');
        }

        try {
            $account = Account::find($id);
            if ($account->is_default == 1) {
                return redirect()->route('account.index')
                    ->with('error', lang('messages.isdefault', string_manip(lang('account.account'))));
            }
            if(!$account){
                $response = ['status' => 1, 'message' => lang('messages.not_found')];
            }
            else
            {
                (new Account)->drop($id);
                $currentRoute = explode('.', \Request::route()->getName());
                $routeMethod = reset($currentRoute);
                //$routeAction = end($currentRoute);
                if($routeMethod == 'account') {
                    $response = ['status' => 1, 'message' => lang('messages.deleted', lang('account.account'))];
                }
                if($routeMethod == 'supplier') {
                    $response = ['status' => 1, 'message' => lang('messages.deleted', lang('supplier.supplier'))];
                }
                if($routeMethod == 'customer') {
                    $response = ['status' => 1, 'message' => lang('messages.deleted', lang('customer.customer'))];
                }
            }
        } catch (Exception $exception) {
            $response = ['status' => 0, 'message' =>$exception. ' -'.lang('messages.server_error')];
        }
        return json_encode($response);
    }

    /**
     * Used to update party active status.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function accountToggle($id)
    {
        if (!\Request::isMethod('post') && !\Request::ajax()) {
            return lang('messages.server_error');
        }

        try {
            $account = Account::find($id);
        } catch (\Exception $exception) {
            return lang('messages.invalid_id', string_manip(lang('account.account')));
        }

        $account->update(['status' => !$account->status]);
        $response = ['status' => 1, 'data' => (int)$account->status . '.gif'];
        // return json response
        return json_encode($response);
    }

    /**
     * Used to load more records and render to view.
     *
     * @param int $pageNumber
     * @return \Illuminate\Http\Response
     */
    public function accountPaginate(Request $request,$pageNumber = null)
    {
        if (!\Request::isMethod('post') && !\Request::ajax()) {
            return lang('messages.server_error');
        }

        $inputs = $request->all();
        $currentRoute = explode('.', \Request::route()->getName());
        $routeMethod = reset($currentRoute);
        $routeAction = end($currentRoute);

        //print_r($inputs);
        $page = 1;
        if (isset($inputs['page']) && (int)$inputs['page'] > 0) {
            $page = $inputs['page'];
        }

        $perPage = 20;
        if (isset($inputs['perpage']) && (int)$inputs['perpage'] > 0) {
            $perPage = $inputs['perpage'];
        }

        $start = ($page - 1) * $perPage;
        if($routeMethod == 'supplier') {
            $inputs = $inputs +['acc_type' => getCreditorId()];
        }
        if($routeMethod == 'customer') {
            $inputs = $inputs +['acc_type' => getDebtorId()];
        }

        if (isset($inputs['form-search']) && $inputs['form-search'] != '') {
            $inputs = array_filter($inputs);
            unset($inputs['_token']);

            $data = (new Account)->getAccounts($inputs, $start, $perPage);
            $totalParty = (new Account)->totalAccounts($inputs);
            $total = $totalParty->total;
        } else {
            $data = (new Account)->getAccounts($inputs, $start, $perPage);
            $totalParty = (new Account)->totalAccounts($inputs);
            $total = $totalParty->total;
        }
        if($routeMethod == 'supplier') {
            return view('supplier.load_data', compact('data', 'total', 'page', 'perPage', 'inputs'));
        }
        if($routeMethod == 'customer') {
            return view('customer.load_data', compact('data', 'total', 'page', 'perPage', 'inputs'));
        }
        if($routeMethod == 'account') {
            return view('account.load_data', compact('data', 'total', 'page', 'perPage', 'inputs'));
        }

    }

    /**
     * Method is used to update status of group enable/disable
     *
     * @return \Illuminate\Http\Response
     */
    public function accountAction()
    {
        $inputs = \Input::all();
        if (!isset($inputs['tick']) || count($inputs['tick']) < 1) {
            return redirect()->route('account.index')
                ->with('error', lang('messages.atleast_one', string_manip(lang('party.party'))));
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

        Account::whereRaw('id IN (' . $ids . ')')->update(['status' => $status]);
        return redirect()->route('account.index')
            ->with('success', lang('messages.updated', lang('account.account_status')));
    }

    public function searchAccount(Request $request)
    {
        if (!\Request::ajax()) {
            return lang('messages.server_error');
        }

        $name = $request->get('name', '');
        //$name = \Input::get('name', '');
        if ($name != "") {
            $data = (new Account)->filterAccountService(['name' => $name], 30);
            echo json_encode($data);
        }
    }

    /**
     * @return Generate Excel file
     */
    public function generateExcel()
    {
        ini_set('memory_limit', '-1');
        $data = (new Account)->getAccountGroupWise();
        $main = view('account.generate_excel_load_data', compact('data'));
        return generateExcel('account.account_common', ['main' => $main], 'Accounts');
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    /*public function uploadExcel()
    {
        $inputs = \Input::all();


        if (count($inputs) > 0) {
            $validator = (new Account)->validateAccountExcel($inputs);
            if ($validator->fails()) {
                return validationResponse(false, 206, "", "", $validator->messages());
            }
            ini_set('memory_limit', '-1');
            Excel::load($inputs['file'], function ($reader) {

                $i = 1;
                try {

                    $data = [];
                    $account = [];
                    $accountGroup = [];

                    $reader->each(function($sheet) {

                        \DB::beginTransaction();
                        $account = (new Account)->findAccount(['name' => $sheet->account_name]);
                        $message = '';
                        if(count($account) > 0){
                            $message = 'The account name has already been taken. ';
                        }
                        if($message == '') {
                            //dd($sheet->account_name);
                            $data['salutation']     = $sheet->salutation;
                            $data['account_name']   = $sheet->account_name;
                            $data['contact_person'] = $sheet->contact_person;
                            $data['email1']         = $sheet->email_1;
                            $data['mobile1']        = $sheet->mobile_1;
                            $data['permanent_address'] = $sheet->permanent_address;
                            $data['status']         = 1;
                            $accountGroup = (new AccountGroup)->getAccountGroupID(['name' => $sheet->account_group], false);
                            if (count($accountGroup) > 0) {
                                $data['account_group_id'] = $accountGroup->id;
                            }

                            $data = $data + [
                                    'created_by' => authUserId(),
                                    'company_id' => loggedInCompanyId(),
                                ];

                            //dd($data);
                            (new Account)->store($data);
                           \DB::commit();
                        }
                    });
                }
                catch (\Exception $e) {
                    \DB::rollBack();
                    return redirect()->back()->with('error', $e->getMessage() . $e->getLine() . $e->getFile() . lang('messages.server_error'));

                }
            });
            //$route = route('products.index');
            $lang = lang('account.account_imported');
            return validationResponse(true, 201, $lang);
        }
        return view('account.upload_excel', compact('banks'));
    }*/

    /**
     * @param string $filename
     * @return Sample File
     */
    public function downloadSampleExcel($filename = 'account-sample')
    {
        ini_set('memory_limit', '-1');
        $isSample = 1;
        $data = [];
        $main = view('account.generate_excel_load_data', compact('data', 'isSample'));
        return generateExcel('account.account_common', ['main' => $main], 'Accounts');
        
        /*$accountGroups = '';
        $accountGroups = (new AccountGroup)->getAccountGroupService();
        $accountGroups = implode(',',$accountGroups);
        $accountGroups = "sdfsdf-sdfsdf, fsadfasf (dfgsdfg),asdf asfdd";

        $configs = "DUS800, DUG900+3xRRUS, DUW2100, 2xMU, SIU, DUS800+3xRRUS, DUG900+3xRRUS, DUW2100";
        //dd($accountGroups);
        Excel::create($filename, function($excel) use($accountGroups){
            $excel->sheet('Accounts', function($sheet1) use($accountGroups){

                $sheet1->cells('A1:G1', function($cells) {
                    $cells->setFont(array(
                        'family'     => 'Calibri',
                        'size'       => '9',
                        'bold'       =>  true
                    ));
                    $cells->setBackground('#FFFF00');
                    $cells->setValignment('center');
                    $cells->setBorder('solid', 'solid', 'solid', 'solid');
                });
                $sheet1->setBorder('A1:G1', 'thin', "#000000");

                $sheet1->cell('A1', function($cell) {
                    $cell->setValue('SALUTATION');
                });
                $sheet1->cell('B1', function($cell) {
                    $cell->setValue('ACCOUNT NAME');
                });
                $sheet1->cell('C1', function($cell) {
                    $cell->setValue('ACCOUNT GROUP');
                });
                $sheet1->cell('D1', function($cell) {
                    $cell->setValue('CONTACT PERSON');
                });
                $sheet1->cell('E1', function($cell) {
                    $cell->setValue('EMAIL 1');
                });
                $sheet1->cell('F1', function($cell) {
                    $cell->setValue('MOBILE 1');
                });
                $sheet1->cell('G1', function($cell) {
                    $cell->setValue('PERMANENT ADDRESS');
                });


                // Set width for multiple cells
                $sheet1->setWidth(array(
                    'A'     =>  7,
                    'B'     =>  30,
                    'C'     =>  30,
                    'D'     =>  20,
                    'E'     =>  20,
                    'F'     =>  20,
                    'G'     =>  20
                ));
                $sheet1->setHeight(1, 25);

                for ($i = 2; $i <= 500; $i++) {
                    $objValidation = $sheet1->getCell('C'.$i)->getDataValidation();
                    $objValidation->setType(\PHPExcel_Cell_DataValidation::TYPE_LIST);
                    $objValidation->setErrorStyle(\PHPExcel_Cell_DataValidation::STYLE_INFORMATION);
                    $objValidation->setAllowBlank(false);
                    $objValidation->setShowInputMessage(true);
                    $objValidation->setShowErrorMessage(true);
                    $objValidation->setShowDropDown(true);
                    $objValidation->setErrorTitle('Input error');
                    $objValidation->setError('Value is not in list.');
                    $objValidation->setPromptTitle('Pick from list');
                    $objValidation->setPrompt('Please pick a value from the drop-down list.');
                    $objValidation->setFormula1($accountGroups); //note this!
                }
            });
            //setUseBom(true);
            //$excel->writer->setUseBOM();

        })->export('xls');*/
    }

    public function uploadExcel()
    {
        $inputs = \Input::all();
        if (count($inputs) > 0) {
            $validator = (new Account)->validateAccountExcel($inputs);
            if ($validator->fails()) {
                return validationResponse(false, 206, "", "", $validator->messages());
            }
            ini_set('memory_limit', '-1');
            Excel::load($inputs['file'], function ($reader) use($inputs) {
                try {
                    $data = [];
                    $reader->each(function($sheet) use ($inputs) {
                        $detail = $sheet;
                        //dd($detail);
                        //foreach($result as $detail) {
                            if (trim($detail->account_name) != "") {

                                $accountName = (new Account)->getAccountByName(trim($detail->account_name));
                                if (!$accountName) {

                                    $stateID = 0;
                                    $state = (new State)->getStateByName(strtolower($detail->state));
                                    if ($state) {
                                        $stateID = $state->id;
                                    }

                                    $gst = (trim($detail->gst_number) != "") ? $detail->gst_number : null;
                                    $userType = (trim($detail->gst_number) != "") ? 1 : 2;

                                    \DB::beginTransaction();
                                    $data['account_name'] = trim($detail->account_name);
                                    $data['account_group_id'] = $inputs['account_group'];
                                    $data['address1'] = $detail->address;
                                    $data['contact_person'] = $detail->contact_person;
                                    $data['user_type'] = $userType;
                                    $data['gst_number'] = $gst;
                                    $data['city'] = $detail->city;
                                    $data['state_id'] = $stateID;
                                    $data['country'] = $detail->country;
                                    $data['mobile1'] = $detail->mobile;
                                    $data['email1'] = $detail->email;
                                    $data['phone'] = $detail->phone;
                                    $data['pincode'] = $detail->pincode;
                                    $data['status'] = 1;
                                    $data = $data + [
                                            'created_by' => authUserId(),
                                            'company_id' => loggedInCompanyId(),
                                        ];
                                    (new Account)->storeAccountFromExcel($data);
                                    \DB::commit();
                                }
                            }
                        //}
                    });
                }
                catch (\Exception $e) {
                    \DB::rollBack();
                    return validationResponse(false, 207, lang('messages.server_error'));
                }
            });

            //$route = route('account.upload-excel');
            $lang = lang('messages.uploaded', lang('account.account'));
            return validationResponse(true, 201, $lang);
        }

        return view('account.upload_excel');
    }

    /**
     * @param int $dc
     * @param int $type
     */
    public function getDebitCreditAccounts($dc = 1, $type = 1)
    {
        if($type == 1) // Receipt
        {
            if ($dc == 1) { // CR
                $result = getOtherExceptCashBankAccounts();
                getDropDown($result);
            } else { // DR
                $result = getCashBankAccounts();
                getDropDown($result);
            }
        } elseif($type == 2) // Payment
        {
            if ($dc == 1) { // DR
                $result = getCashBankAccounts();
                getDropDown($result);
            } else { //CR
                $result = getOtherExceptCashBankAccounts();
                getDropDown($result);
            }
        } elseif($type == 3) // Contra
        {
            if ($dc == 1) { // DR
                $result = getCashBankAccounts();
                getDropDown($result);
            } else { //CR
                $result = getCashBankAccounts();
                getDropDown($result);
            }
        } elseif($type == 4) // Journal
        {
            if ($dc == 1) { // DR
                $result = getOtherExceptCashAccounts();
                getDropDown($result);
            } else { //CR
                $result = getOtherExceptCashAccounts();
                getDropDown($result);
            }
        }
    }
}