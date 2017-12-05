<?php

namespace App;

/**
 * :: City Model ::
 * To manage City CRUD operations
 *
 **/

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class City extends Model
{
    use SoftDeletes;
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'city_master';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'city_name',
        'city_digit_code',
        'city_char_code',
        'state_id',
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
    public function validateCity($inputs, $id = null)
    {
        // validation rule
        if ($id) {
            $rules['city_name']        = 'required|unique:city_master,city_name,' . $id .',id,deleted_at,NULL';
            $rules['city_digit_code']  = 'required|numeric|unique:city_master,city_digit_code,' . $id .',id,deleted_at,NULL';
            $rules['city_char_code']   = 'required|alpha|unique:city_master,city_char_code,' . $id .',id,deleted_at,NULL';
            $rules['state']            = 'required';
        } else {
            $rules['city_name']        = 'required|unique:city_master,city_name,NULL,id,deleted_at,NULL';
            $rules['city_digit_code']  = 'required|numeric|unique:city_master,city_digit_code,NULL,id,deleted_at,NULL';
            $rules['city_char_code']   = 'required|alpha|unique:city_master,city_char_code,NULL,id,deleted_at,NULL';
            $rules['state']            = 'required';
            $rules['status']           = 'required';
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
    public function getCity($search = null, $skip, $perPage)
    {
        $take = ((int)$perPage > 0) ? $perPage : 20;
        $filter = 1; // default filter if no search

        $fields = [
            'id',
            'state_id',
            'city_name',
            'city_digit_code',
            'city_char_code',
            'status',
        ];

        if (is_array($search) && count($search) > 0) {
            $partyName = (array_key_exists('keyword', $search)) ? " AND city_name LIKE '%" .
                addslashes(trim($search['keyword'])) . "%' " : "";
            $filter .= $partyName;
        }
        return $this->whereRaw($filter)
            ->orderBy('city_name', 'ASC')->skip($skip)->take($take)->get($fields);
    }

    /**
     * Method is used to get total results.
     *
     * @param array $search
     *
     * @return mixed
     */
    public function totalCity($search = null)
    {
        $filter = 1; // if no search add where

        // when search
        if (is_array($search) && count($search) > 0) {
            $partyName = (array_key_exists('keyword', $search)) ? " AND city_name LIKE '%" .
                addslashes(trim($search['keyword'])) . "%' " : "";
            $filter .= $partyName;
        }
        return $this->select(\DB::raw('count(*) as total'))
            ->whereRaw($filter)->first();
    }

    /**
     * @return mixed
     */
    public function getCityService()
    {
        $data = $this->active()
            ->orderBy('city_name', 'ASC')
            ->get([\DB::raw("concat(city_name, ' (', city_char_code, ')') as name"), 'id']);
        $result = [];
        foreach($data as $detail) {
            $result[$detail->id] = $detail->name;
        }
        return ['' => '-Select City-'] + $result;
    }


    /**
     * @return mixed
     */
    public function getCityServiceAjax($stateId)
    {
        $data = $this->active()
            ->orderBy('city_name', 'ASC')
            ->where("state_id",$stateId)
            ->get([\DB::raw("concat(city_name, ' (', city_char_code, ')') as name"), 'id']);
        $result = [];
        foreach($data as $detail) {
            $result[$detail->id] = $detail->name;
        }

        return $result  + ['0' => '-Select City-'] ;
    }
}
