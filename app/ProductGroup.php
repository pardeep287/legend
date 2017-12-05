<?php
namespace App;
/**
 * :: Product Group Model ::
 * To manage Product Group CRUD operations
 *
 **/

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductGroup extends Model
{
    use SoftDeletes;

    /**
     * The database table used by the model.
     * @var string
     */
    protected $table = 'product_group';

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = [
        'name',
        'code',
        'description',
        'company_id',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
        'deleted_at'
    ];

    /**
     * Scope a query to only include active users.
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
     * Method is used to validate
     * @param int $id
     * @return Response
     **/
    public function validateProductGroup($inputs, $id = null)
    {
        if ($id) {
            $rules['name'] = 'required|unique:product_group,name,' . $id .',id,deleted_at,NULL,company_id,'.loggedInCompanyId();
            //$rules['code'] = 'required|unique:product_group,code,' . $id .',id,deleted_at,NULL,company_id,'.loggedInCompanyId();
        } else {
            $rules['name'] = 'required|unique:product_group,name,NULL,id,deleted_at,NULL,company_id,'.loggedInCompanyId();
            //$rules['code'] = 'required|unique:product_group,code,NULL,id,deleted_at,NULL,company_id,'.loggedInCompanyId();
        }
        return \Validator::make($inputs, $rules);
    }

    /**
     * @param $inputs
     * @return \Illuminate\Validation\Validator
     */
    public function validateProductGroupCodeExcel($inputs)
    {
        $rules = [
            'file' => 'required',
        ];
        return \Validator::make($inputs, $rules);
    }

    /**
     * Method is used to save/update resource.
     * @param   array $input
     * @param   int $id
     * @return  Response
     */
    public function store($input, $id = null)
    {
        if ($id) {
            // save role
            return $this->find($id)->update($input);
        } else {
            return $this->create($input)->id;
        }
    }

    /**
     * Method is used to search detail.
     *
     * @param array $search
     * @param int $skip
     * @param int $perPage
     * @return mixed
     */
    public function getProductGroups($search = null, $skip, $perPage)
    {
        $take = ((int)$perPage > 0) ? $perPage : 20;
        // default filter if no search
        $filter = 1;

        $sortBy = [
            'name' => 'name',
            'code' => 'code',
        ];

        $fields = [
            'id',
            'name',
            'code',
            'description',
            'status',
        ];

        $orderEntity = 'name';
        $orderAction = 'asc';
        if (isset($search['sort_action']) && $search['sort_action'] != "") {
            $orderAction = ($search['sort_action'] == 1) ? 'desc' : 'asc';
        }

        if (isset($search['sort_entity']) && $search['sort_entity'] != "") {
            $orderEntity = (array_key_exists($search['sort_entity'], $sortBy)) ? $sortBy[$search['sort_entity']] : $orderEntity;
        }

        if (is_array($search) && count($search) > 0) {
            $f1 = (array_key_exists('keyword', $search))?
                " AND (name LIKE '%".addslashes(trim($search['keyword'])).
                "%' OR code LIKE '%".addslashes(trim($search['keyword']))."%')"
                : "";
            $filter .= $f1;
        }
        return $this->whereRaw($filter)
            ->company()
            ->orderBy($orderEntity, $orderAction)
            ->skip($skip)->take($take)->get($fields);
    }

    /**
     * Method is used to get total results.
     * @param array $search
     *
     * @return mixed
     */
    public function totalProductGroups($search = null)
    {
        $filter = 1; // if no search add where
        // when search
        if (is_array($search) && count($search) > 0) {
            $name = (array_key_exists('keyword', $search)) ? " AND name LIKE '%" .
                addslashes(trim($search['keyword'])) . "%' " : "";
            $filter .= $name;
        }
        return $this->select(\DB::raw('count(*) as total'))
            ->whereRaw($filter)
            ->company()
            ->first();
    }

    /**
     * @return mixed
     */
    public function getProductGroupService()
    {
        $data = $this->active()
            ->company()
            ->orderBy('id','DESC')
            ->get(['name', 'id']);
        $result = [];
        foreach($data as $detail) {
            $result[$detail->id] = $detail->name;

        }
        return ['' =>'-Select Product Group-'] + $result;
    }
    /*public function getProductGroupService($search = [])
    {
        $filter = 1; // if no search add where
        // when search
        if (is_array($search) && count($search) > 0) {
            $f1 = (array_key_exists('t', $search) && $search['t'] != "") ? " AND (name LIKE '%" .
                addslashes(trim($search['t'])) . "%' OR code LIKE '%" .
                addslashes(trim($search['t'])) . "%')" : "";
            $filter .= $f1;
        }

        $data = $this->active()->company()->whereRaw($filter)
        ->get([\DB::raw("concat(name, ' (', code) as name"), 'id']);
        $result = [];
        foreach($data as $detail) {
            $result[$detail->id] = $detail->name .')';
        }
        return ['' => '-Select Product Group-'] + $result;
    }*/

    /**
     * @param $id
     */
    public function drop($id)
    {
        $this->find($id)->update( [ 'deleted_at' => convertToUtc(), 'deleted_by' => authUserId() ] );
    }

    /**
     * @return mixed
     */
    public function getProductGroupForApi($inputs = [])
    {
        $result = $this->getProductGroupService();
        $productGroupResult = [];
        if (count($result) > 0)
        {
            foreach ($result as $key => $value)
            {
                if(!empty($key)) {
                    $productGroupResult[] = [
                        'id' => $key,
                        'name' => $value
                    ];
                }
            }
        }
        return $productGroupResult;
    }

    /**
     * @param array $search
     * @param bool $all
     * @return mixed
     */
    public function getProductGroup($search = [], $all = true)
    {
        $filter = 1;
        if (is_array($search) && count($search) > 0) {
            $name = (array_key_exists('name', $search)) ? " AND name = '" .
                addslashes(trim($search['name'])) . "' " : "";
            $filter .= $name;
        }
        $result = $this->whereRaw($filter);

        if($all) {
            return $result->get();
        } else {
            return $result->first();
        }
    }
}