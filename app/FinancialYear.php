<?php

namespace App;

/**
 * :: Financial Year Model ::
 * To manage Financial Year CRUD operations
 *
 **/

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancialYear extends Model
{
    use SoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'financial_year';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_id',
        'name',
        'from_date',
        'to_date',
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

    public function scopeCompany($query)
    {
        return $query->where('company_id', loggedInCompanyId());
    }
    /**
     * Method is used to validate roles
     *
     * @param $inputs
     * @param int $id
     * @return Response
     */
    public function validateFinanciaYear($inputs, $id = null)
    {
        $inputs = array_filter($inputs);
        // validation rule
        if ($id) {
            $rules['name']      = 'required|unique:financial_year,name,' . $id .',id,deleted_at,NULL';
            $rules['from_date'] = 'required|date|unique:financial_year,from_date,' . $id .',id,deleted_at,NULL';
            $rules['to_date']   = 'required|date|unique:financial_year,to_date,' . $id .',id,deleted_at,NULL';
        } else {
            $rules['name']      = 'required|unique:financial_year,name,NULL,id,deleted_at,NULL';
            $rules['from_date'] = 'required|date|unique:financial_year,from_date,NULL,id,deleted_at,NULL';
            $rules['to_date']   = 'required|date|unique:financial_year,to_date,NULL,id,deleted_at,NULL';
        }

        return \Validator::make($inputs, $rules);
    }
    /**
     * Method is used to save/update resource.
     *
     * @param   array $input
     * @param   int $id
     * @return  mixed
     */
    public function store($input, $id = null)
    {
        if ($id) {
            return $this->find($id)->update($input);
        } else {
            return $this->create($input)->id;
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
    public function getFinancialYears($search = null, $skip, $perPage)
    {
        $take = ((int)$perPage > 0) ? $perPage : 20;
        $filter = 1; // default filter if no search

        $fields = [
            'id',
            'name',
            'from_date',
            'to_date',
            'status',
        ];

        $sortBy = [
            'id'   => 'id',
            'name' => 'name',
            'from_date' => 'from_date',
            'to_date' => 'to_date',
        ];

        $sortEntity = 'id'; //$orderEntity
        $sortAction = 'desc'; //$orderAction
        if (isset($search['sort_action']) && $search['sort_action'] != "") {
            $sortAction = ($search['sort_action'] == 1) ? 'desc' : 'asc';
        }

        if (isset($search['sort_entity']) && $search['sort_entity'] != "") {
            $sortEntity = (array_key_exists($search['sort_entity'], $sortBy)) ? $sortBy[$search['sort_entity']] : $sortEntity;
        }

        if (is_array($search) && count($search) > 0) {
            $partyName = (array_key_exists('keyword', $search)) ?
                " AND name LIKE '%" .addslashes(trim($search['keyword'])) . "%' " : "";
            $filter .= $partyName;
        }
        return $this->whereRaw($filter)
            ->orderBy($sortEntity, $sortAction)
            ->skip($skip)->take($take)
            ->get($fields);
    }
    /**
     * Method is used to get total results.
     *
     * @param array $search
     *
     * @return mixed
     */
    public function totalFinancialYears($search = null)
    {
        $filter = 1; // if no search add where

        // when search
        if (is_array($search) && count($search) > 0) {
            $partyName = (array_key_exists('keyword', $search)) ? " AND name LIKE '%" .
                addslashes(trim($search['keyword'])) . "%' " : "";
            $filter .= $partyName;
        }
        return $this->select(\DB::raw('count(*) as total'))
            ->whereRaw($filter)->first();
    }

    /**
     * @return bool
     */
    public function updateStatusAll()
    {
        return $this->where('status', '=' ,1)->update([ 'status' => 0 ]);
    }


    /**
     * @return mixed
     */

    public function getActiveFinancialYear()
    {
        return $this->active()->first();
    }

    /**
     * @param $id
     * @return bool|null
     */
    public function drop($id)
    {
//        return $this->find($id)->update([ 'deleted_by' => authUserId(), 'deleted_at' => convertToUtc()]);
        return $this->find($id)->delete();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function getAllFinancialYear()
    {
        return $this->get();
    }
}
