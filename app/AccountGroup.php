<?php
namespace App;
/**
 * :: Account Group Model ::
 * To manage Account Group CRUD operations
 *
 **/

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountGroup extends Model
{
    use SoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'account_group';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'parent_group_id',
        'company_id',
        'is_primary',
        'is_default',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
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
     * Method is used to validate roles
     *
     * @param $inputs
     * @param int $id
     * @return Response
     */
    public function validateAccountGroup($inputs, $id = null)
    {
        // validation rule
        if ($id) {
            $rules['name'] = 'required|unique:account_group,name,' . $id .',id,deleted_at,NULL,company_id,'.loggedInCompanyId();
        } else {
            $rules['name'] = 'required|unique:account_group,name,NULL,id,deleted_at,NULL,company_id,'.loggedInCompanyId();
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
    public function getAccountGroups($search = null, $skip, $perPage)
    {
        $take = ((int)$perPage > 0) ? $perPage : 20;
        $filter = 1; // default filter if no search

        $fields = [
            'a.id',
            'a.name',
            'a.status',
            'a.is_default',
            'b.name as parent_name'
        ];

        if (is_array($search) && count($search) > 0) {
            $partyName = (array_key_exists('keyword', $search)) ? " AND a.name LIKE '%" .
                addslashes(trim($search['keyword'])) . "%' " : "";
            $filter .= $partyName;
        }

        return \DB::table('account_group as a')
            ->leftjoin('account_group as b', 'a.parent_group_id' ,'=', 'b.id')
            ->whereRaw($filter)
            ->whereNull('a.deleted_at')
            ->orderBy('id', 'ASC')
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
    public function totalAccountGroups($search = null)
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
     * @return mixed
     */
    public function getAccountGroupService( $inputs = null)
    {
        $filter = 1; // if no search add where
        if (is_array($inputs) && count($inputs) > 0) {
        $f1 = (array_key_exists('acc_type', $inputs) && $inputs['acc_type'] != "") ? " AND (account_group.id = " .
            addslashes(trim($inputs['acc_type'])) . ")" : "";
        $f2 = (array_key_exists('acc_not_c', $inputs) && $inputs['acc_not_c'] != "") ? " AND (account_group.id != " .
                addslashes(trim($inputs['acc_not_c'])) . ")" : "";
        $f3 = (array_key_exists('acc_not_d', $inputs) && $inputs['acc_not_d'] != "") ? " AND (account_group.id != " .
                addslashes(trim($inputs['acc_not_d'])) . ")" : "";
        $filter .=  $f1 . $f2 .$f3;
        }
        //dd($filter);
        $result = $this->active()->whereRaw($filter)->pluck('name', 'id')->toArray();
        return ['' => '-Select Account Group-'] + $result;
    }
    /**
     * @param int $id
     *
     * @return int
     */
    public function drop($id)
    {
        $this->find($id)->update( [ 'deleted_by' => authUserId(), 'deleted_at' => date("Y-m-d h:i:s") ] );
        //$this->find($id)->update( [ 'deleted_by' => authUserId(), 'deleted_at' => convertToUtc() ] );
    }

    /**
     * Method is used to search group ID.
     *
     * @param array $search
     * @param int $skip
     * @param int $perPage
     *
     * @return mixed
     */
    public function getAccountGroupID($search = null)
    {
        $fields = [
            'account_group.id'
        ];
        $filter = '';
        if (is_array($search) && count($search) > 0) {
            $groupName = (array_key_exists('name', $search)) ? " account_group.name LIKE '" .
                addslashes(trim($search['name'])) . "' " : "";
            $filter .= $groupName;
            return $this->whereRaw($filter)->first($fields);
        }
        return false;

    }

}
