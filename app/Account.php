<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use SoftDeletes;

    /**
     * The database table used by the model.
     * @var string
     */
    protected $table = 'account_master';

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = [
        'company_id',
        'dr_cr_id',
        'account_group_id',
        'salutation',
        'account_name',
        'account_code',
        'contact_person',
        'mobile1',
        'mobile2',
        'phone',
        'phone2',
        'email1',
        'email2',
        'user_type',
        'is_default',
        'gst_number',
        'tin_number',
        'address1',
        'address2',
        'country_id',
        'state_id',
        'city_id',
        'pincode',
        'opening_balance',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
        'deleted_at',


    ];


    /**
     * @param array $inputs
     * @param int $id
     *
     * @return \Illuminate\Validation\Validator
     */
    public function validateAccountOnly($inputs, $id = null)
    {
        if ($id) {
            $rules['account_name'] = 'required|unique:account_master,account_name,' . $id . ',id,deleted_at,NULL,company_id,'.\Auth::user()->company_id;
            $rules['account_group'] = 'required';
        } else {
            $rules['account_name'] = 'required|unique:account_master,account_name,NULL,id,deleted_at,NULL,company_id,'.\Auth::user()->company_id;
            $rules['account_group'] = 'required';
            //$rules['dr_cr'] = 'required|in:receipt,payment';
        }

        return \Validator::make($inputs, $rules);
    }

    /**
     * @param array $inputs
     * @param int $id
     *
     * @return \Illuminate\Validation\Validator
     */
    public function validateAccount($inputs, $id = null)
    {
        if ($id) {
            $rules['account_name'] = 'required|unique:account_master,account_name,' . $id . ',id,deleted_at,NULL,company_id,'.\Auth::user()->company_id;
        } else {
            $rules['account_name'] = 'required|unique:account_master,account_name,NULL,id,deleted_at,NULL,company_id,'.\Auth::user()->company_id;
            //$rules['dr_cr'] = 'required|in:receipt,payment';
        }

        $rules = $rules + [
            'account_group'     => 'required',
            'mobile1'           => 'numeric|digits_between:10,11',
            'mobile2'           => 'nullable|numeric|digits_between:10,11',
            'phone'             => 'nullable|numeric_hyphen|max:13',
            'phone2'            => 'nullable|numeric_hyphen|max:13',
            //'phone2'            => 'nullable|numeric_hyphen|digits_between:10,11',    
            'email1'            => 'email',
            'email2'            => 'nullable|email',
            'opening_balance'   => 'nullable|numeric',
            //'contact_person'    => 'numeric',
            'state'             => 'numeric',
            'country'           => 'numeric',
            //'country'           => 'alpha_spaces',
            'city'              => 'numeric',
        ];

        if($inputs['account_group'] == getDebtorId() || $inputs['account_group'] == getCreditorId()) {

            if($inputs['user_type'] == 1) {

                $rules['gst_number'] = 'required';
                $rules['tin_number'] = 'nullable|numeric';
            }

            $rules['country'] = 'required|numeric';
            $rules['state'] = 'required|numeric';
            $rules['city'] = 'required|numeric';
        }

        if(!empty($inputs['d_c']) && empty($inputs['opening_balance'])) {
            $rules['opening_balance'] = 'required';
        }
        if(!empty($inputs['opening_balance']) && empty($inputs['d_c'])) {
            $rules['d_c'] = 'required';
        }

        $messages = [
            'd_c.required'  =>  'The debit and credit must be selected before opening balance.'
        ];
        return \Validator::make($inputs, $rules, $messages);
    }

    /**
     * @param $inputs
     * @return \Illuminate\Validation\Validator
     */
    public function validateAccountExcel($inputs) {
        $rules = [
            'account_group' => 'required',
            'file' => 'required',
        ];
        return \Validator::make($inputs, $rules);
    }

    /**
     * Scope a query to only include active users.
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('account_master.status', 1);
    }

    /**
     * Scope a query to only include active users.
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCompany($query)
    {
        return $query->where(function($query) {
            $query->where('account_master.is_default', 1)
                ->orWhere('account_master.company_id', loggedInCompanyId());
        });
    }

    /**
     * @param array $inputs
     * @param int $id
     *
     * @return mixed
     */
    public function store($inputs, $id = null)
    {
        $inputs = $inputs + [
            'dr_cr' => (isset($inputs['dr_cr_id'])) ? (int)$inputs['dr_cr_id'] : null,
            'amount' => (isset($inputs['opening_balance'])) ? (int)$inputs['opening_balance'] : 0,
        ];

        if ($id) {
            /*$this->find($id)->update($inputs);
            $inputs['id'] = $id;
            (new TransactionMaster)->storeAccountTransaction($inputs, $id);*/
            return $this->find($id)->update($inputs);

        } else {
           /* $id = $this->create($inputs)->id;
            $inputs['id'] = $id;
            (new TransactionMaster)->storeAccountTransaction($inputs);*/
            return $this->create($inputs);
        }
    }

    /**
     * @param $inputs
     * @return mixed
     */
    public function storeAccountFromExcel($inputs)
    {
        return $this->create($inputs);
    }

    /**
     * @param $name
     * @return mixed
     */
    public function getAccountByName($name)
    {
        return $this->where('account_name', $name)->first();
    }

    /**
     * @param int $id
     *
     * @return int
     */
    public function drop($id)
    {
        $this->find($id)->update(['deleted_by' => authUserId(), 'deleted_at' => convertToUtc()]);
    }

    /**
     * Method is used to search news detail.
     *
     * @param array $search
     * @param int $skip
     * @param int $perPage
     *
     * @return mixed
     */
    public function getAccounts($search = null, $skip, $perPage)
    {
        $take = ((int)$perPage > 0) ? $perPage : 20;
        $fields = [
            'account_master.*',
            'account_group.name as account_group_name'
        ];
        // default filter if no search
        $filter = 1;

        $sortBy = [
            'name' => 'account_master.account_name'
        ];

        $orderEntity = 'account_master.account_name';
        $orderAction = 'asc';
        if (isset($search['sort_action']) && $search['sort_action'] != "" && $search['sort_action'] != 0) {
            $orderAction = ($search['sort_action'] == 1) ? 'desc' : 'asc';
        }

        if (isset($search['sort_entity']) && $search['sort_entity'] != "") {
            $orderEntity = (array_key_exists($search['sort_entity'], $sortBy)) ? $sortBy[$search['sort_entity']] : $orderEntity;
        }

        if (is_array($search) && count($search) > 0) {

            $filter .=  (array_key_exists('keyword', $search) && $search['keyword'] != "") ? " AND (account_master.account_name LIKE '%" .
                addslashes(trim($search['keyword'])) . "%' OR account_group.name LIKE '%" .
                addslashes(trim($search['keyword'])) . "%')" : "";

            $filter .= (array_key_exists('account_name', $search)) ? " AND account_master.account_name LIKE '%" .
                addslashes($search['account_name']) . "%' " : "";


            $filter .= (array_key_exists('acc_type', $search) && $search['acc_type'] != "") ? " AND (account_group.id = " .
                addslashes(trim($search['acc_type'])) . ")" : "";

        }
        

        return $result = $this->company()
            ->leftjoin('account_group','account_master.account_group_id','=','account_group.id')
            ->whereRaw($filter)
            ->orderBy($orderEntity, $orderAction)
            ->skip($skip)->take($take)
            ->get($fields);
    }

    /**
     * Method is used to get total category search wise.
     *
     * @param array $search
     *
     * @return mixed
     */
    public function totalAccounts($search = null)
    {
        // if no search add where
        $filter = 1;

        // when search news
        if (is_array($search) && count($search) > 0) {
            $filter .=  (array_key_exists('keyword', $search) && $search['keyword'] != "") ? " AND (account_master.account_name LIKE '%" .
                addslashes(trim($search['keyword'])) . "%' OR account_group.name LIKE '%" .
                addslashes(trim($search['keyword'])) . "%')" : "";

            $filter .= (array_key_exists('account_name', $search)) ? " AND account_master.account_name LIKE '%" .
                addslashes($search['account_name']) . "%' " : "";

            $filter .= (array_key_exists('acc_type', $search) && $search['acc_type'] != "") ? " AND (account_group.id = " .
                addslashes(trim($search['acc_type'])) . ")" : "";
        }
        return $this->company()->leftjoin('account_group','account_master.account_group_id','=','account_group.id')
            ->select(\DB::raw('count(*) as total'))->whereRaw($filter)
            ->get()->first();
    }

    /**
     * @return mixed
     */
    public function getAccountService()
    {
        // LIsts Function deprecated
        /*$result = $this->active()->company()->lists('account_name', 'id')->toArray();*/
        $result = $this->active()->company()->pluck('account_name', 'id')->toArray();
        return ['' => ''] + $result;
    }

    /**
     * @param array $search
     * @param int $take
     * @return mixed
     */
    public function filterAccountService($search = [], $take = 0)
    {
        $filter  = 1;
        if (is_array($search) && count($search) > 0)
        {
            $filter .= (array_key_exists('account_group_id', $search) && $search['account_group_id'] != "") ? " AND account_group_id = '" .
                addslashes($search['account_group_id']) . "' " : "";

            $filter .= (array_key_exists('account_not_in', $search) && $search['account_not_in'] != "") ? " AND account_group_id NOT IN (" .
                addslashes($search['account_not_in']) . ") " : "";


            $filter .= (array_key_exists('account_in', $search) && $search['account_in'] != "") ? " AND account_group_id IN (" .
                addslashes($search['account_in']) . ") " : "";

            $filter .= (array_key_exists('name', $search) && $search['name'] != "") ? " AND account_name LIKE '%" .
                addslashes(trim($search['name'])) . "%' " : "";

            $accounts = $this->active()->company()
                ->leftjoin('account_group','account_master.account_group_id','=','account_group.id')
                ->whereRaw($filter)
                ->orderBy('account_master.account_name', 'ASC');

            if($take > 0) {
                $accounts = $accounts->take($take);
            }
            $accounts = $accounts->get(['account_name as name', 'account_master.id', 'account_group.name as account_group']);
            $accounts = convertToDropDown($accounts);
            return ['' => ''] + $accounts;
        }
    }

    /**
     * @param $id
     * @return mixed
     */
    public function getAccountDetail($id)
    {
        return $this->leftJoin('state_master', 'state_master.id', '=', 'account_master.state_id')
            ->company()->find($id);
    }
    /**
     * @param $id
     * @return mixed
     */
    public function getAccountInfo($id)
    {
        return $this->company()->find($id);
    }
    /**
     * @param $id
     * @return mixed
     */
    public function getStateId($id)
    {
        return $this->company()->where('id', $id)->first(['state_id']);
    }
    /**
     * @return mixed
     */
    public function getAccountGroupWise()
    {
        $fields = [
            'account_master.account_name',
            'account_master.contact_person',
            'account_master.email1',
            'account_master.mobile1',
            'account_master.phone',
            'account_master.gst_number',
            'account_master.address1',
            'account_master.city',
            'state_master.state_name as state',
            'account_master.country',
            'account_group.name as account_group'
        ];
        return $result = $this->where('account_master.company_id', loggedInCompanyId())
            ->leftjoin('account_group','account_master.account_group_id','=','account_group.id')
            ->leftjoin('state_master','state_master.id','=','account_master.state_id')
            ->get($fields);
    }

    /**
     * Method is used to search account name.
     *
     * @param array $search
     * @return mixed
     */
    public function findAccount($search = null)
    {
        $fields = [
            'account_master.*'
        ];
        $filter = '';
        if (is_array($search) && count($search) > 0) {
            $groupName = (array_key_exists('name', $search)) ? " account_name LIKE '" .
                addslashes(trim($search['name'])) . "' " : "";
            $filter .= $groupName;
            return $this->whereRaw($filter)->first($fields);
        }
        return false;
    }

    /**
     * Method is used to search account name.
     *
     * @param array $search
     * @return mixed
     */
    public function getAccount($name = null)
    {
        $result = $this->where('account_name', trim($name))->first();
        if($result) {
            return $result->id;
        }
        return 56;
    }

    public function partyGST($search = null, $skip, $perPage)
    {
        $take = ((int)$perPage > 0) ? $perPage : 20;
        $fields = [
            'account_master.*',
            'account_group.name as account_group_name',
            'state_master.state_digit_code',
        ];

        $filter = 1;

        if(is_array($search) && count($search) > 0) {
            $f1 = (isset($search['account_group']) && $search['account_group'] != '') ?
                " and account_group.id = '" . addslashes($search['account_group']) ."'" : '';

            $filter .= $f1;
        }

        return $result = $this->
            company()
            ->leftjoin('account_group','account_master.account_group_id','=','account_group.id')
            ->leftjoin('state_master','state_master.id','=','account_master.state_id')
            ->whereRaw($filter)
            ->orderBy('account_master.account_name', 'asc')
            ->skip($skip)->take($take)
            ->get($fields);
    }

    public function totalPartyGST($search = null)
    {
        $filter = 1;
        if(is_array($search) && count($search) > 0) {
            $f1 = (isset($search['account_group']) && $search['account_group'] != '') ?
                " and account_group.id = '" . addslashes($search['account_group']) ."'" : '';

            $filter .= $f1;
        }
        return $this->company()->leftjoin('account_group','account_master.account_group_id','=','account_group.id')
            ->select(\DB::raw('count(*) as total'))->whereRaw($filter)
            ->get()->first();
    }

}