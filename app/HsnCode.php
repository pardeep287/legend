<?php

namespace App;
/**
 * :: HSN Code Model ::
 * To manage HSN Code CRUD operations
 *
 **/

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HsnCode extends Model
{
    use SoftDeletes;

    /**
     * The database table used by the model.
     * @var string
     */
    protected $table = 'hsn_master';

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = [
        'id',
        'hsn_code',
        'company_id',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    /**
     * @param array $inputs
     * @param int $id
     * @return \Illuminate\Validation\Validator
     */
    public function validateHsnCodes($inputs, $id = '')
    {
        if ($id) {
            $rules['hsn_code'] = 'required|numeric|unique:hsn_master,hsn_code,' . $id .',id,deleted_at,NULL,company_id,'.loggedInCompanyId();
        } else {
            $rules['hsn_code'] = 'required|numeric|unique:hsn_master,hsn_code,NULL,id,deleted_at,NULL,company_id,'.loggedInCompanyId();
        }

        return \Validator::make($inputs, $rules);
    }

    /**
     * @param $inputs
     * @return \Illuminate\Validation\Validator
     */
    public function validateHsnCodeExcel($inputs)
    {
        $rules = [
            'file' => 'required',
        ];
        return \Validator::make($inputs, $rules);
    }

    /**
     * Scope a query to only include active users.
     * @param $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('hsn_master.status', 1);
    }

    public function scopeCompany($query)
    {
        return $query->where('hsn_master.company_id', loggedInCompanyId());
    }

    /**
     * @param array $inputs
     * @param int $id
     * @return mixed
     */
    public function store($inputs, $id = null)
    {

        if ($id) {
            $this->find($id)->update($inputs);
        }
        else{
            $id = $this->create($inputs)->id;
            return $id;
        }
    }

    /**
     * @param null $search
     * @param $skip
     * @param $perPage
     * @return mixed
     */
    public function getHsnCodes($search = null, $skip, $perPage, $id = null)
    {
        $sortBy = [
            'hsn_code' => 'hsn_code'
        ];
        $take = ((int)$perPage > 0) ? $perPage : 20;
        $filter = 1; // default filter if no search
        $fields = [
            'hsn_master.id',
            'hsn_code',
            'hsn_master.status',
        ];
        $orderEntity = 'hsn_master.id';
        $orderAction = 'asc';
        if (isset($search['sort_action']) && $search['sort_action'] != "") {
            $orderAction = ($search['sort_action'] == 1) ? 'desc' : 'asc';
            //session(['sort_action' => $orderAction]);
        }
        if (isset($search['sort_entity']) && $search['sort_entity'] != "") {
            $orderEntity = (array_key_exists($search['sort_entity'], $sortBy)) ? $sortBy[$search['sort_entity']] : $orderEntity;
            //session(['sort_entity' => $orderEntity]);
        }
        if (is_array($search) && count($search) > 0) {
            $f1 = (array_key_exists('keyword', $search) && $search['keyword'] != "") ? " AND (hsn_code LIKE '%" .
                addslashes($search['keyword']) . "%')" : "";
            $filter .= $f1;
        }
        $action = $orderAction; //(session('sort_action') != "") ? session('sort_action') : $orderAction;
        $entity = $orderEntity; //(session('sort_entity') != "") ? session('sort_entity') : $orderEntity;
        if($id){
            return $this->where('hsn_master.id',$id)
                ->company()
                ->first($fields);
        }
        else{
            return $this->whereRaw($filter)
                ->company()
                ->orderBy($entity, $action)
                ->skip($skip)->take($take)->get($fields);
        }

    }
    /**
     * Method is used to get total HSN Codes.
     * @param array $search
     * @return mixed
     */
    public function totalHsnCodes($search = null)
    {
        $filter = 1; // if no search add where

        if (is_array($search) && count($search) > 0) {
            $f1 = (array_key_exists('keyword', $search) && $search['keyword'] != "") ? " AND (hsn_code LIKE '%" .
                addslashes($search['keyword']) . "%')" : "";
            $filter .= $f1;
        }
        return $this->select(\DB::raw('count(*) as total'))
            ->whereRaw($filter)
            ->company()
            ->get()
            ->first();
    }
    /**
     * @param $id
     */
    public function drop($id)
    {
        $this->find($id)->update( [ 'deleted_by' => authUserId(), 'deleted_at' =>convertToUtc()] );
    }
    /**
     * @return mixed
     */
    public function getHsnCodeService()
    {
        $fields = [
            'hsn_master.id',
            'hsn_code',
        ];
        $data = $this->active()->company()->orderBy('id','DESC')->get($fields);
        $result = [];
        foreach($data as $detail) {
            $result[$detail->id] = $detail->hsn_code;

        }
        return ['' =>'-Select HSN Code-'] + $result;
    }
    /**
     * Method is used to find HSN Code.
     * @param string $search
     * @return id
     */
    public function findHsnCode($search = '')
    {
        if ($search != '') {
            $filter = "hsn_code = '" . $search . "' ";
            return $this->select('hsn_master.id as hsn_id')
                ->whereRaw($filter)
                ->company()
                ->first();
        }
        return null;
    }

    /**
     * Method is used to get all hsn codes
     * @return id
     */
    public function getAllHsnCode()
    {
        $fields = ['hsn_master.hsn_code'];
        return $this->active()
                    ->company()
                    ->get($fields);
    }

    /**
     * @param array $inputs
     * @return mixed
     */
    public function createHsnCode($inputs = [])
    {
        $result = $this->where('hsn_code', $inputs['hsn_code'])->first();
        if($result) {
            return $result->id;
        }
        $inputs['company_id'] = loggedInCompanyId();
        $inputs['created_by'] = authUserId();
        return $this->store($inputs);
    }
}