<?php
namespace App;
/**
 * :: Role Model :: 
 * To manage roles CRUD operations
 *
 **/

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    use SoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'role';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'code',
        'company_id',
        'status',
        'isdefault',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * Scope a query to only include active users.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopeCompany($query)
    {
        if(!isSuperAdmin()) {
            return $query->where('role.company_id', loggedInCompanyId());
        }
    }

    /**
     * Method is used to validate roles
     *
     * @param int $id
     * @return Response
     **/
    public function validateRole($inputs, $id = null)
    {
        // validation rule
        if ($id) {
            $rules['name'] = 'required|unique:role,name,' . $id .',id,deleted_at,NULL,company_id,'.loggedInCompanyId();
            $rules['code'] = 'required|unique:role,code,' . $id .',id,deleted_at,NULL,company_id,'.loggedInCompanyId();
        } else {
            $rules['name'] = 'required|unique:role,name,NULL,id,deleted_at,NULL,company_id,'.loggedInCompanyId();
            $rules['code'] = 'required|unique:role,code,NULL,id,deleted_at,NULL,company_id,'.loggedInCompanyId();
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
    public function getRoles($search = null, $skip, $perPage)
    {
        $take = ((int)$perPage > 0) ? $perPage : 20;
        // default filter if no search
        $filter = 1;

        $fields = [
            'role.id',
            'role.isdefault',
            'role.name',
            'role.code',
            'role.status',
            'company.company_name'
        ];

        if (is_array($search) && count($search) > 0) {
            $partyName = (array_key_exists('keyword', $search)) ? " AND name LIKE '%" .
                addslashes(trim($search['keyword'])) . "%' " : "";
            $filter .= $partyName;
        }
        $result =  $this->leftJoin('company', 'company.id', '=', 'role.company_id')
                ->whereRaw($filter)->company()
                ->orderBy('role.id', 'ASC')->skip($skip)->take($take)->get($fields);

         return $result;
    }

    /**
     * Method is used to get total category search wise.
     *
     * @param array $search
     *
     * @return mixed
     */
    public function totalRoles($search = null)
    {
        // if no search add where
        $filter = 1;

        // when search news
        if (is_array($search) && count($search) > 0) {
            $partyName = (array_key_exists('name', $search)) ? " AND name LIKE '%" .
                addslashes(trim($search['keyword'])) . "%' " : "";
            $filter .= $partyName;
        }
        return $this->company()->select(\DB::raw('count(*) as total'))
                ->whereRaw($filter)->first();
    }

    /**
     * @return mixed
     */
    public function getRoleService()
    {
        $data = $this->active()->company()->orWhere('role.isdefault', 1)->get([\DB::raw("concat(name, ' (', code) as name"), 'id']);
        $result = [];
        foreach($data as $detail) {
            $result[$detail->id] = $detail->name .')';
        }
        return ['' => '-Select Role-'] + $result;
    }
}
