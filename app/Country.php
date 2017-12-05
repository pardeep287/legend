<?php

namespace App;

/**
 * :: Country Model ::
 * To manage Country CRUD operations
 *
 **/

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Country extends Model
{
    use SoftDeletes;
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'country_master';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'currency_id',
        'country_name',
        'country_digit_code',
        'country_char_code',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
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
     * Method is used to validate roles
     *
     * @param $inputs
     * @param int $id
     * @return Response
     */
    public function validateCountry($inputs, $id = null)
    {
        // validation rule
        if ($id) {
            $rules['country_name']        = 'required|unique:country_master,country_name,' . $id .',id,deleted_at,NULL';
            $rules['country_digit_code']  = 'required|numeric|unique:country_master,country_digit_code,' . $id .',id,deleted_at,NULL';
            $rules['country_char_code']   = 'required|alpha|unique:country_master,country_char_code,' . $id .',id,deleted_at,NULL';
            $rules['currency']            = 'required';
        } else {
            $rules['country_name']        = 'required|unique:country_master,country_name,NULL,id,deleted_at,NULL';
            $rules['country_digit_code']  = 'required|numeric|unique:country_master,country_digit_code,NULL,id,deleted_at,NULL';
            $rules['country_char_code']   = 'required|alpha|unique:country_master,country_char_code,NULL,id,deleted_at,NULL';
            $rules['currency']            = 'required';
            $rules['status']              = 'required';
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

        $this->find($id)->update(['status' => 0 , 'deleted_by' => authUserId(), 'deleted_at' => convertToUtc()]);
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
    public function getCountries($search = null, $skip, $perPage, $id = null)
    {
        $take = ((int)$perPage > 0) ? $perPage : 20;
        $filter = 1; // default filter if no search

        $fields = [
            'id',
            'currency_id',
            'country_name',
            'country_digit_code',
            'country_char_code',
            'status',
        ];

        if (is_array($search) && count($search) > 0) {
            $partyName = (array_key_exists('keyword', $search)) ? " AND country_name LIKE '%" .
                addslashes(trim($search['keyword'])) . "%' " : "";
            $filter .= $partyName;
        }
        if($id){
            return $this->where('id', $id)
                ->active()
                ->first($fields);
        }else {
            return $this->whereRaw($filter)
                ->orderBy('country_name', 'ASC')
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
    public function totalCountries($search = null)
    {
        $filter = 1; // if no search add where

        // when search
        if (is_array($search) && count($search) > 0) {
            $partyName = (array_key_exists('keyword', $search)) ? " AND country_name LIKE '%" .
                addslashes(trim($search['keyword'])) . "%' " : "";
            $filter .= $partyName;
        }
        return $this->select(\DB::raw('count(*) as total'))
            ->whereRaw($filter)->first();
    }

    /**
     * @return mixed
     */
    public function getCountryService()
    {
        $data = $this->active()
            ->orderBy('country_name', 'ASC')
            ->get([\DB::raw("concat(country_name, ' (', country_digit_code, ')') as name"), 'id']);
        $result = [];
        foreach($data as $detail) {
            $result[$detail->id] = $detail->name;
        }
        return ['' => '-Select Country-'] + $result;
    }
}
