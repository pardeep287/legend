<?php

namespace App;

/**
 * :: Store Master Model ::
 * To manage Store Master CRUD operations
 *
 **/

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StoreMaster extends Model
{
    use SoftDeletes;
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'store_master';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_id',
        'store_code',
        'store_name',
        'store_type',
        'godown_lvl_sto_mnt',
        'store_address',
        'total_racks',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
        'created_at',
        'updated_at',
        'deleted_at'
    ];
    /**
     * Scope a query to only include active users.
     *
     * @param $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
    /**
     * @param $query
     * @return null
     */
    public function scopeCompanyStore($query)
    {
        return (!isSuperAdmin())?
            $query->where('store_master.company_id', loggedInCompanyId()) : null;
    }

    /**
     * @param null $code
     * @return mixed|string
     */
    public function getStoreCode($code = null)
    {
        $result =  $this->companyStore()->where('store_code', $code)->first();
        if ($result) {
            $data =  $this->companyStore()->orderBy('id', 'desc')->take(1)->first(['store_code']);
        } else {
            $data =  $this->companyStore()->orderBy('id', 'desc')->take(1)->first(['store_code']);
        }

        if (count($data) == 0) {
            $number = 'STR-01';
        } else {
            $number = number_inc($data->store_code); // new store_code increment by 1
        }
        return $number;
    }

    /**
     * Method is used to validate roles
     *
     * @param $inputs
     * @param int $id
     * @return Response
     */
    public function validateStore($inputs, $id = null)
    {
        // validation rule
        if ($id) {
            $rules['store_code']     = 'required|unique:store_master,store_code,' . $id .',id,deleted_at,NULL,company_id,'.loggedInCompanyId();
            $rules['store_name']     = 'required|unique:store_master,store_name,' . $id .',id,deleted_at,NULL,company_id,'.loggedInCompanyId();
            $rules['store_type']     = 'required';
            $rules['store_address']  = 'required';
            $rules['total_racks']    = 'required|numeric';
        } else {
            $rules['store_code']     = 'required|unique:store_master,store_code,NULL,id,deleted_at,NULL,company_id,'.loggedInCompanyId();
            $rules['store_name']     = 'required|unique:store_master,store_name,NULL,id,deleted_at,NULL,company_id,'.loggedInCompanyId();
            $rules['store_type']     = 'required';
            $rules['store_address']  = 'required';
            $rules['total_racks']    = 'required|numeric';
            $rules['status']         = 'required';
        }
        return \Validator::make($inputs, $rules);
    }

    /**
     * Method is used to save/update resource.
     *
     * @param   array $input
     * @param   int $id
     * @return  Response
     */
    public function store($input, $id = null)
    {
        if ($id) {
            return $this->find($id)->update($input);
        }else{
            return $this->create($input)->id;
        }
    }

    /**
     * @param $id
     */
    public function drop($id)
    {
        $this->find($id)->update(['deleted_by' => authUserId(), 'deleted_at' => convertToUtc()]);
//        $this->find($id)->delete();
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
    public function getStore($search = null, $skip, $perPage, $id = null)
    {
        $take = ((int)$perPage > 0) ? $perPage : 20;
        $filter = 1; // default filter if no search

        $fields = [
            'id',
            'company_id',
            'store_code',
            'store_name',
            'store_type',
            'store_address',
            'godown_lvl_sto_mnt',
            'total_racks',
            'status',
        ];

        $sortBy = [ 'store_name' => 'store_name' ];

        $sortEntity = 'store_master.id'; //$orderEntity
        $sortAction = 'asc';     //$orderAction

        if (isset($search['sort_action']) && $search['sort_action'] != "") {
            $sortAction = ($search['sort_action'] == 1) ? 'desc' : 'asc';
        }

        if (isset($search['sort_entity']) && $search['sort_entity'] != "") {
            $sortEntity = (array_key_exists($search['sort_entity'], $sortBy)) ? $sortBy[$search['sort_entity']] : $sortEntity;
        }

        if (is_array($search) && count($search) > 0) {
            $partyName = (array_key_exists('keyword', $search)) ? " AND store_name LIKE '%" .
                addslashes(trim($search['keyword'])) . "%' " : "";
            $filter .= $partyName;
        }
        if($id){
            return $this->where('store_master.id', $id)
                ->companyStore()
                ->first($fields);
        }else {
            return $this->whereRaw($filter)
                ->orderBy($sortEntity, $sortAction)
                ->skip($skip)->take($take)->get($fields);
        }
    }

    /**
     * Method is used to get total results.
     *
     * @param array $search
     *
     * @return mixed
     */
    public function totalStore($search = null)
    {
        $filter = 1; // if no search add where

        // when search
        if (is_array($search) && count($search) > 0) {
            $partyName = (array_key_exists('keyword', $search)) ? " AND store_name LIKE '%" .
                addslashes(trim($search['keyword'])) . "%' " : "";
            $filter .= $partyName;
        }
        return $this->select(\DB::raw('count(*) as total'))
            ->whereRaw($filter)->first();
    }

    /**
     * @return mixed
     */
    public function getStoreService()
    {
        $data = $this->active()->get([\DB::raw("concat(store_name, ' (', store_code, ')') as name"), 'id']);
        $result = [];
        foreach($data as $detail) {
            $result[$detail->id] = $detail->name;
        }
        return ['' => '-Select Store-'] + $result;
    }
}
