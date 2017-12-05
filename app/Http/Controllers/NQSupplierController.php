<?php

namespace App\Http\Controllers;

/**
 * :: Account Controller ::
 * To manage party.
 *
 * @package Pay Track
 * @author  Ankush Abbi (ankush.abbi@gmail.com)
 **/

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Account;
use App\AccountGroup;

class SupplierControllerold extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        //return view('supplier.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $accountGroup = (new AccountGroup)->getAccountGroupService(['acc_type' => getCreditorId()]);

        $country = getCountry();
        $cities = getCities();
        //dd($cities);
        $states = getStates();
        //dd($states);
        return view('supplier.create', compact('accountGroup','states','cities','country'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $inputs  = $request->all();

        $validator = (new Account)->validateAccount($inputs);
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

            (new Account)->store($inputs);
            $route = route('supplier.index');
            $lang = lang('messages.created', lang('supplier.supplier'));
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
        dd();
        $account = Account::find($id);
        if (!$account) {
            abort(404);
        }
        if ($account->is_default == 1) {
            return redirect()->route('account.index')
                ->with('error', lang('messages.isdefault', string_manip(lang('account.account'))));
        }

        $accountGroup = (new AccountGroup)->getAccountGroupService(['acc_type' => getCreditorId()]);
        $states = getStates();
        $country = getCountry();
        $cities = getCities();
        return view('supplier.edit', compact('account', 'accountGroup','states','country','cities'));
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
        $validator = (new Account)->validateAccount($inputs, $id);
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
            (new Account)->store($inputs, $id);
            $submitData = [];
            $route = route('supplier.index');
            $lang = lang('messages.updated', lang('account.account'));
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
                return redirect()->route('supplier.index')
                    ->with('error', lang('messages.isdefault', string_manip(lang('supplier.supplier'))));
            }
            if(!$account){
                $response = ['status' => 1, 'message' => lang('messages.not_found')];
            }
            else
            {
                (new Account)->drop($id);
                $response = ['status' => 1, 'message' => lang('messages.deleted', lang('supplier.supplier'))];
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
        /*if (!\Request::isMethod('post') && !\Request::ajax()) {
            return lang('messages.server_error');
        }

        $inputs = $request->all();
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
        $inputs = $inputs +['acc_type' => getCreditorId()];
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

        return view('supplier.load_data', compact('data', 'total', 'page', 'perPage', 'inputs'));*/
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
            return redirect()->route('supplier.index')
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


}