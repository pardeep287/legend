<?php

namespace App;

/**
 * :: Bank Model ::
 * To manage bank CRUD operations
 * @package Pay Track
 * @author  Ankush Abbi (ankush.abbi@gmail.com)
 **/

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bank extends Model
{
    use SoftDeletes;

    /**
     * The database table used by the model.
     * @var string
     */
    protected $table = 'bank_master';

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = [
        'name',
        'ifsc_code',
        'account_number',
        'account_holder',
        'manager_name',
        'contact_number',
        'phone_number',
        'company_id',
        'status',
        'created_by',
        'updated_by',
        'deleted_at',
        'deleted_by'
    ];

    /**
     * The attributes that should be mutated to dates.
     * @var array
     */
    protected $dates = ['deleted_at'];

    /**
     * Scope a query to only include active users.
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
    
    /**
     * 
     * @param type $query
     * @return type
     */
    public function scopeCompany($query)
    {  
        return $query->where('bank_master.company_id', loggedInCompanyId());  
    }

    /**
     * Method is used to validate bank
     *
     * @param array $inputs
     * @param int $id
     *
     * @return \Illuminate\Validation\Validator
     **/
    public function validateBank($inputs, $id = null)
    {
        // validation rule
        if ($id) {
            $rules['name'] = 'required|unique:bank_master,account_number,' . $id . ',id,deleted_at,NULL,company_id,'.loggedInCompanyId();
        }
        else {
            $rules['name'] = 'required|unique:bank_master,account_number,NULL,id,deleted_at,NULL,company_id,'.loggedInCompanyId();
        }

        $rules['account_holder'] = 'required';
        $rules['contact_number'] = 'nullable|numeric|digits:10';
        $rules['account_number'] = 'nullable|numeric';
        $rules['phone_number'] = 'nullable|numeric|digits:10';

        $messages = [
            'name.required' => 'The bank name is required',
            'account_holder.required' => 'Account holder is required',
        ];
        return \Validator::make($inputs, $rules, $messages);
    }

    /**
     * Method is used to save/update resource.
     *
     * @param array $inputs
     * @param int $id
     *
     * @return mixed
     */
    public function store($inputs, $id = null)
    {
        if ($id) {
            return $this->find($id)->update($inputs);
        } else {
            return $this->create($inputs)->id;
        }
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
    public function getBanks($search = null, $skip, $perPage)
    {
        $sortBy = [
            'name' => 'name',
            'account_number' => 'account_number',
            'manager_name' => 'manager_name',
            'account_holder' => 'account_holder',
        ];
        $take = ((int)$perPage > 0) ? $perPage : '20';
        // default filter if no search
        $filter = 1;

        $orderEntity = 'id';
        $orderAction = 'desc';
        if (isset($search['sort_action']) && $search['sort_action'] != "" && $search['sort_action'] != 0) {
            $orderAction = ($search['sort_action'] == 1) ? 'desc' : 'asc';
        }

        if (isset($search['sort_entity']) && $search['sort_entity'] != "") {
            $orderEntity = (array_key_exists($search['sort_entity'], $sortBy)) ? $sortBy[$search['sort_entity']] : $orderEntity;
        }

        if (is_array($search) && count($search) > 0) {
            $bankName = (array_key_exists('keyword', $search)) ? " AND name LIKE '%" .
                addslashes($search['keyword']) . "%' " : "";
            $filter .= $bankName;
        }

        return $this->whereRaw($filter)
            ->company()
            ->orderBy($orderEntity, $orderAction)
            ->skip($skip)->take($take)->get();
    }

    /**
     * Method is used to get total bank search wise.
     *
     * @param array $search
     *
     * @return mixed
     */
    public function totalBanks($search = null)
    {
        // if no search add where
        $filter = 1;

        // when search news
        if (is_array($search) && count($search) > 0) {
            $bankName = (array_key_exists('name', $search)) ? " AND name LIKE '%" .
                addslashes($search['name']) . "%' " : "";
            $filter .= $bankName;
        }

        $result = $this->select(\DB::raw('count(*) as total'))->whereRaw($filter)->company();
        return $result->get()->first();
    }

    /**
     * @param int $id
     * @return int
     */
    public function drop($id)
    {
        $this->find($id)->update([ 'deleted_at' => convertToUtc(), 'deleted_by' => authUserId()]);
    }

    /**
     * @return mixed
     */
    public function getBankService()
    {
        $data = $this->active()->company()->get(['id','name', 'account_number']);
        $result = [];
        foreach($data as $detail) {
            $result[$detail->id]  = $detail->name . ' - ' . $detail->account_number;
        }
        return $result;
    }

    /**
     * @param $inputs
     * @return \Illuminate\Validation\Validator
     */
    public function validateUploadStatement($inputs)
    {
        $rules = [
            'file' => 'required',
            'bank' => 'required'
        ];

        return \Validator::make($inputs, $rules);
    }

    /**
     * @param null $id
     * @return mixed
     */
    public function getBankDetail($id = null)
    {
        return $this->company()->find($id);
    }
}