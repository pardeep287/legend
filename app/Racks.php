<?php

namespace App;

/**
 * :: Racks Model ::
 * To manage Racks CRUD operations
 *
 **/

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Racks extends Model
{
    use SoftDeletes;
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'racks';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_id',
        'store_id',
        'rack_code',
        'rack_name',
        'total_shelves',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
        'created_at',
        'updated_at',
        'deleted_at',
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
    public function scopeCompanyRacks($query)
    {
        return (!isSuperAdmin())?
            $query->where('racks.company_id', loggedInCompanyId()) : null;
    }

    /**
     * @param null $code
     * @return mixed|string
     */
    public function getRacksCode($code = null)
    {
        $result =  $this->companyRacks()->where('rack_code', $code)->first();
        if ($result) {
            $data =  $this->companyRacks()->orderBy('id', 'desc')->take(1)->first(['rack_code']);
        } else {
            $data =  $this->companyRacks()->orderBy('id', 'desc')->take(1)->first(['rack_code']);
        }

        if (count($data) == 0) {
            $number = 'RCK-01';
        } else {
            $number = number_inc($data->rack_code); // new rack_code increment by 1
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
    public function validateRacks($inputs, $id = null, $isMultiple = false, $idArr = [])
    {
        $inputs = array_filter($inputs);
        // validation rule
        if($isMultiple) {
               if(count($idArr) > 0 && is_array($idArr) && !empty($idArr)) {
                   if(isset($inputs['rack_name']) && isset($inputs['total_shelves']))
                   {
                       foreach($inputs['rack_code'] as $key => $value)
                       {
                           $decKey = ($key - 1);

                           $rules['rack_code.'. $key]           = 'required|unique:racks,rack_code,' .$inputs['id'][$key] .',id,deleted_at,NULL,company_id,'.loggedInCompanyId();
                           $rules['rack_name.'. $key]           = 'required|unique:racks,rack_name,' . $inputs['id'][$key] .',id,deleted_at,NULL,company_id,'.loggedInCompanyId();
                           if($key && $decKey != -1 && $value != '') {
                               $rules['rack_name.'.$key]  .= '|different:rack_name.'.$decKey;
                           }
                           $rules['total_shelves.' .$key]       = 'required|numeric';
                       }
                       $rules['store']                          = 'required|numeric';
                   }

               }else {
                   $rules['rack_code.*']     = 'required|unique:racks,rack_code,NULL,id,deleted_at,NULL,company_id,'.loggedInCompanyId();
                   $rules['rack_name.*']     = 'required|unique:racks,rack_name,NULL,id,deleted_at,NULL,company_id,'.loggedInCompanyId();

                   if(isset($inputs['rack_name']))
                   {
                       foreach($inputs['rack_name'] as $key => $value) {
                           $decKey = ($key - 1);
                           if($key && $decKey != -1 && $value != '') {
                               //if(isset($rules['rack_name.'.$key])) {
                               $rules['rack_name.'.$key]  = 'different:rack_name.'.$decKey;
                               //}
                           }
                       }
                   }

                   $rules['store']           = 'required';
                   $rules['total_shelves.*'] = 'required|numeric';
                   $rules['status']          = 'required';
               }
        }else {
            if ($id) {
                $rules['rack_code']     = 'required|unique:racks,rack_code,' . $id .',id,deleted_at,NULL,company_id,'.loggedInCompanyId();
                $rules['rack_name']     = 'required|unique:racks,rack_name,' . $id .',id,deleted_at,NULL,company_id,'.loggedInCompanyId();
                $rules['store']         = 'required';
                $rules['total_shelves'] = 'required|numeric';
            } else {
                $rules['rack_code']     = 'required|unique:racks,rack_code,NULL,id,deleted_at,NULL,company_id,'.loggedInCompanyId();
                $rules['rack_name']     = 'required|unique:racks,rack_name,NULL,id,deleted_at,NULL,company_id,'.loggedInCompanyId();
                $rules['store']         = 'required';
                $rules['total_shelves'] = 'required|numeric';
                $rules['status']        = 'required';
            }
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
    public function store($input, $id = null, $isMultiple = false)
    {
        if ($id) {
            return $this->find($id)->update($input);
        }else{
            if($isMultiple) {
                return $this->insert($input);
            }else {
                return $this->create($input)->id;
            }
        }
    }

    /**
     * @param $id
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
    public function getRacks($search = null, $skip, $perPage, $id = null)
    {
        $take = ((int)$perPage > 0) ? $perPage : 20;
        $filter = 1; // default filter if no search

        $fields = [
            'id',
            'company_id',
            'store_id',
            'rack_code',
            'rack_name',
            'total_shelves',
            'status',
        ];
        $sortBy = [ 'rack_name' => 'rack_name' ];

        $sortEntity = 'racks.id'; //$orderEntity
        $sortAction = 'desc';     //$orderAction

        if (isset($search['sort_action']) && $search['sort_action'] != "") {
            $sortAction = ($search['sort_action'] == 1) ? 'desc' : 'asc';
        }

        if (isset($search['sort_entity']) && $search['sort_entity'] != "") {
            $sortEntity = (array_key_exists($search['sort_entity'], $sortBy)) ? $sortBy[$search['sort_entity']] : $sortEntity;
        }

        if (is_array($search) && count($search) > 0) {
            $partyName = (array_key_exists('keyword', $search)) ? " AND rack_name LIKE '%".addslashes(trim($search['keyword']))."%'" : "";
            $filter .= $partyName;

            $filter .= (array_key_exists('store_id', $search) && $search['store_id'] != '') ? " AND store_id=".addslashes(trim($search['store_id'])) : "";
        }

        if($id){
            return $this->where('racks.id', $id)
                ->companyRacks()
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
    public function totalRacks($search = null)
    {
        $filter = 1; // if no search add where

        // when search
        if (is_array($search) && count($search) > 0) {
            $partyName = (array_key_exists('keyword', $search)) ? " AND rack_name LIKE '%".addslashes(trim($search['keyword']))."%'" : "";
            $filter .= $partyName;

            $filter .= (array_key_exists('store_id', $search) && $search['store_id'] != '') ? " AND store_id=".addslashes(trim($search['store_id'])) : "";
        }
        return $this->select(\DB::raw('count(*) as total'))
            ->whereRaw($filter)->first();
    }

    /**
     * @return mixed
     */
    public function getRackService()
    {
        $data = $this->active()->get([\DB::raw("concat(rack_name, ' (', rack_code, ')') as name"), 'id']);
        $result = [];
        foreach($data as $detail) {
            $result[$detail->id] = $detail->name;
        }
        return ['' => '-Select Rack-'] + $result;
    }

    /**
     * @param null $search
     * @return mixed
     */
    public function findRacks($search = null)
    {
        $filter = 1;

        if (is_array($search) && count($search) > 0) {
           $filter .= (array_key_exists('store_id', $search)) ? " AND store_id=".addslashes(trim($search['store_id'])) : "";
        }

        return $this->active()->whereRaw($filter)->orderBy('racks.id', 'asc')->get();
    }
}
