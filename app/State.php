<?php

namespace App;

/**
 * :: State Model ::
 * To manage State CRUD operations
 *
 **/

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class State extends Model
{
    use SoftDeletes;
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'state_master';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'state_name',
        'state_digit_code',
        'state_char_code',
        'country_id',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
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
     * Method is used to validate roles
     *
     * @param $inputs
     * @param int $id
     * @return Response
     */
    public function validateState($inputs, $id = null)
    {
        // validation rule
        if ($id) {
            $rules['state_name']        = 'required|unique:state_master,state_name,' . $id .',id,deleted_at,NULL';
            $rules['state_digit_code']  = 'required|numeric|unique:state_master,state_digit_code,' . $id .',id,deleted_at,NULL';
            $rules['state_char_code']   = 'required|alpha|unique:state_master,state_char_code,' . $id .',id,deleted_at,NULL';
            $rules['country']           = 'required';
        } else {
            $rules['state_name']        = 'required|unique:state_master,state_name,NULL,id,deleted_at,NULL';
            $rules['state_digit_code']  = 'required|numeric|unique:state_master,state_digit_code,NULL,id,deleted_at,NULL';
            $rules['state_char_code']   = 'required|alpha|unique:state_master,state_char_code,NULL,id,deleted_at,NULL';
            $rules['country']           = 'required';
            $rules['status']            = 'required';
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
        }
        return $this->create($input)->id;
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
    public function getState($search = null, $skip, $perPage)
    {
        $take = ((int)$perPage > 0) ? $perPage : 20;
        $filter = 1; // default filter if no search

        $fields = [
            'id',
            'country_id',
            'state_name',
            'state_digit_code',
            'state_char_code',
            'status',
        ];

        if (is_array($search) && count($search) > 0) {
            $partyName = (array_key_exists('keyword', $search)) ? " AND state_name LIKE '%" .
                addslashes(trim($search['keyword'])) . "%' " : "";
            $filter .= $partyName;
        }
        return $this->whereRaw($filter)
            ->orderBy('state_name', 'ASC')
            ->skip($skip)
            ->take($take)
            ->get($fields);
    }

    /**
     * Method is used to get total results.
     *
     * @param array $search
     *
     * @return mixed
     */
    public function totalState($search = null)
    {
        $filter = 1; // if no search add where

        // when search
        if (is_array($search) && count($search) > 0) {
            $partyName = (array_key_exists('keyword', $search)) ? " AND state_name LIKE '%" .
                addslashes(trim($search['keyword'])) . "%' " : "";
            $filter .= $partyName;
        }
        return $this->select(\DB::raw('count(*) as total'))
            ->whereRaw($filter)->first();
    }

    /**
     * @return mixed
     */
    public function getStateService()
    {
        $data = $this->active()
            ->orderBy('state_name', 'ASC')
            ->get([\DB::raw("concat(state_name, ' (', state_digit_code, ')') as name"), 'id']);
        $result = [];
        foreach($data as $detail) {
            $result[$detail->id] = $detail->name;
        }
        return ['' => '-Select State-'] + $result;
    }

    /**
     * @param null $search
     * @return mixed
     */
    public function findStates($search = null)
    {
        $filter = 1;

        if (is_array($search) && count($search) > 0) {
            $filter .= (array_key_exists('country_id', $search)) ? " AND country_id=".addslashes(trim($search['country_id'])) : "";
        }

        return $this->active()->whereRaw($filter)->orderBy('state_name', 'asc')->get();
    }

    /**
     * @return mixed
     */
    public function getStateServiceAjax($countryId)
    {
        $data = $this->active()
            ->orderBy('state_name', 'ASC')
            ->where("country_id",$countryId)
            ->get([\DB::raw("concat(state_name, ' (', state_digit_code, ')') as name"), 'id']);
        $result = [];
        foreach($data as $detail) {
            $result[$detail->id] = $detail->name;
        }

        return $result  + ['0' => '-Select State-'] ;
    }
}
