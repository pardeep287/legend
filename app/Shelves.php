<?php

namespace App;

/**
 * :: Shelves Model ::
 * To manage Shelves CRUD operations
 *
 **/


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shelves extends Model
{
    use SoftDeletes;
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'shelves';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_id',
        'rack_id',
        'shelve_code',
        'shelve_name',
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
    public function scopeCompanyShelves($query)
    {
        return (!isSuperAdmin())?
            $query->where('shelves.company_id', loggedInCompanyId()) : null;
    }

    /**
     * @param null $code
     * @return mixed|string
     */
    public function getShelveCode($code = null)
    {
        $result =  $this->companyShelves()->where('shelve_code', $code)->first();
        if ($result) {
            $data =  $this->companyShelves()->orderBy('id', 'desc')->take(1)->first(['shelve_code']);
        } else {
            $data =  $this->companyShelves()->orderBy('id', 'desc')->take(1)->first(['shelve_code']);
        }

        if (count($data) == 0) {
            $number = 'SHL-01';
        } else {
            $number = number_inc($data->shelve_code); // new shelve_code increment by 1
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
    public function validateShelves($inputs, $id = null, $isMultiple = false, $idArr = [])
    {
        $inputs = array_filter($inputs);
        // validation rule
        if($isMultiple) {
            if(count($idArr) > 0 && is_array($idArr)) {
                if(isset($inputs['shelve_code']) && isset($inputs['shelve_name']))
                {
                    foreach($inputs['shelve_code'] as $key => $value)
                    {
                        $decKey = ($key - 1);
                        $rules['shelve_code.'. $key]           = 'required|unique:shelves,shelve_code,' .$inputs['id'][$key] .',id,deleted_at,NULL,company_id,'.loggedInCompanyId();
                        $rules['shelve_name.'. $key]           = 'required|unique:shelves,shelve_name,' . $inputs['id'][$key] .',id,deleted_at,NULL,company_id,'.loggedInCompanyId();

                        if($key && $decKey != -1 && $value != '') {
                          $rules['shelve_name.'.$key]  .= '|different:shelve_name.'.$decKey;
                        }
                    }
                    $rules['rack']                          = 'required|numeric';
                }

            }else {
                $rules['shelve_code.*'] = 'required|unique:shelves,shelve_code,NULL,id,deleted_at,NULL,company_id,' . loggedInCompanyId();
                $rules['shelve_name.*'] = 'required|unique:shelves,shelve_name,NULL,id,deleted_at,NULL,company_id,' . loggedInCompanyId();

                if(isset($inputs['shelve_name']))
                {
                    foreach($inputs['shelve_name'] as $key => $value) {
                        $decKey = ($key - 1);
                        if($key && $decKey != -1 && $value != '') {
                            //if(isset($rules['rack_name.'.$key])) {
                            $rules['shelve_name.'.$key]  = 'different:shelve_name.'.$decKey;
                            //}
                        }
                    }
                }

                $rules['rack'] = 'required';
                $rules['status'] = 'required';
            }
        }else {
            if ($id) {
                $rules['shelve_code']   = 'required|unique:shelves,shelve_code,' . $id .',id,deleted_at,NULL,company_id,'.loggedInCompanyId();
                $rules['shelve_name']   = 'required|unique:shelves,shelve_name,' . $id .',id,deleted_at,NULL,company_id,'.loggedInCompanyId();
                $rules['rack']         = 'required';
            } else {
                $rules['shelve_code']   = 'required|unique:shelves,shelve_code,NULL,id,deleted_at,NULL,company_id,'.loggedInCompanyId();
                $rules['shelve_name']   = 'required|unique:shelves,shelve_name,NULL,id,deleted_at,NULL,company_id,'.loggedInCompanyId();
                $rules['rack']         = 'required';
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
    public function getShelves($search = null, $skip, $perPage, $id = null)
    {
        $take = ((int)$perPage > 0) ? $perPage : 20;
        $filter = 1; // default filter if no search

        $fields = [
            'id',
            'company_id',
            'rack_id',
            'shelve_code',
            'shelve_name',
            'status',
        ];
        $sortBy = [ 'shelve_name' => 'shelve_name' ];

        $sortEntity = 'shelves.id'; //$orderEntity
        $sortAction = 'desc';     //$orderAction

        if (isset($search['sort_action']) && $search['sort_action'] != "") {
            $sortAction = ($search['sort_action'] == 1) ? 'desc' : 'asc';
        }

        if (isset($search['sort_entity']) && $search['sort_entity'] != "") {
            $sortEntity = (array_key_exists($search['sort_entity'], $sortBy)) ? $sortBy[$search['sort_entity']] : $sortEntity;
        }

        if (is_array($search) && count($search) > 0) {
            $partyName = (array_key_exists('keyword', $search)) ? " AND shelve_name LIKE '%".addslashes(trim($search['keyword']))."%'" : "";
            $filter .= $partyName;

            $filter .= (array_key_exists('rack_id', $search) && $search['rack_id'] != '') ? " AND rack_id=".addslashes(trim($search['rack_id'])) : "";
        }

        if($id){
            return $this->where('shelves.id', $id)
                ->companyShelves()
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
    public function totalShelves($search = null)
    {
        $filter = 1; // if no search add where

        // when search
        if (is_array($search) && count($search) > 0) {
            $partyName = (array_key_exists('keyword', $search)) ? " AND shelve_name LIKE '%".addslashes(trim($search['keyword']))."%'" : "";
            $filter .= $partyName;

            $filter .= (array_key_exists('rack_id', $search) && $search['rack_id'] != '') ? " AND rack_id=".addslashes(trim($search['rack_id'])) : "";
        }
        return $this->select(\DB::raw('count(*) as total'))
            ->whereRaw($filter)->first();
    }

    /**
     * @return mixed
     */
    public function getShelveService()
    {
        $data = $this->active()->get([\DB::raw("concat(shelve_name, ' (', shelve_code, ')') as name"), 'id']);
        $result = [];
        foreach($data as $detail) {
            $result[$detail->id] = $detail->name;
        }
        return ['' => '-Select Shelve-'] + $result;
    }

    /**
     * @param null $search
     * @return mixed
     */
    public function findShelves($search = null)
    {
        $filter = 1;

        if (is_array($search) && count($search) > 0) {
            $filter .= (array_key_exists('rack_id', $search)) ? " AND rack_id=".addslashes(trim($search['rack_id'])) : "";
        }

        return $this->active()->whereRaw($filter)->orderBy('shelves.id', 'asc')->get();
    }
}
