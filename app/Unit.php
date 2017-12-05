<?php
namespace App;
/**
 * :: Unit Model ::
 * To manage Unit CRUD operations
 *
 **/

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unit extends Model
{
    use SoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'unit';

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = [
        'name',
        'code',
        'quantity',
        'alternate_quantity',
        'description',
        'company_id',
        'status',
        'deleted_at',
        'deleted_by',
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
     * @param type $query
     * @return type
     */
    public function scopeCompany($query)
    {
        return $query->where('company_id', loggedInCompanyId());
    }

    /**
     * Method is used to validate roles
     * @param $inputs
     * @param int $id
     * @return Response
     */
    public function validateUnit($inputs, $id = null)
    {
        // validation rule
        if ($id) {
            $rules['name'] = 'required|unique:unit,name,' . $id .',id,deleted_at,NULL,company_id,'.loggedInCompanyId();
            $rules['code'] = 'required|unique:unit,code,' . $id .',id,deleted_at,NULL,company_id,'.loggedInCompanyId();
        } else {
            $rules['name'] = 'required|unique:unit,name,NULL,id,deleted_at,NULL,company_id,'.loggedInCompanyId();
            $rules['code'] = 'required|unique:unit,code,NULL,id,deleted_at,NULL,company_id,'.loggedInCompanyId();
        }
        $rules['quantity'] = 'required|numeric';
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
     * @return mixed
     */
    public function getUnits($search = null, $skip, $perPage)
    {
        $sortBy = [
            'name' => 'name',
            'code' => 'code',
        ];
        $take = ((int)$perPage > 0) ? $perPage : 20;
        $filter = 1; // default filter if no search

        $fields = [
            'id',
            'name',
            'code',
            'quantity',
            'description',
            'status',
        ];

        echo $search['sort_action'];
        echo " -- ";
        $orderEntity = 'id';
        $orderAction = 'desc';
        if (isset($search['sort_action']) && $search['sort_action'] != "") {
            $orderAction = ($search['sort_action'] == 1) ? 'desc' : 'asc';
        }

        if (isset($search['sort_entity']) && $search['sort_entity'] != "") {
            $orderEntity = (array_key_exists($search['sort_entity'], $sortBy)) ? $sortBy[$search['sort_entity']] : $orderEntity;
        }

        echo $orderAction;
        echo " -- ";
        echo $orderEntity;

        if (is_array($search) && count($search) > 0) {
            $f1 = (array_key_exists('keyword', $search))?
                " AND (name LIKE '%".addslashes(trim($search['keyword'])).
                "%' OR code LIKE '%".addslashes(trim($search['keyword']))."%')"
                : "";
            $filter .= $f1;
        }

        return $this
            ->whereRaw($filter)
            ->company()
            ->orderBy($orderEntity, $orderAction)
            ->skip($skip)->take($take)->get($fields);
    }

    /**
     * Method is used to get total results.
     * @param array $search
     * @return mixed
     */
    public function totalUnits($search = null)
    {
        $filter = 1; // if no search add where

        // when search
        if (is_array($search) && count($search) > 0) {
            $partyName = (array_key_exists('keyword', $search)) ? " AND name LIKE '%" .
                addslashes(trim($search['keyword'])) . "%' " : "";
            $filter .= $partyName;
        }
        return $this->select(\DB::raw('count(*) as total'))
            ->whereRaw($filter)
            ->company()
            ->first();
    }

    /**
     * @return mixed
     */
    public function getUnitService()
    {
        $data = $this->active()
            ->company()
            ->orderBy('id','DESC')
            ->get([\DB::raw("concat(name, ' (', code) as name"), 'id']);
        $result = [];
        foreach($data as $detail) {
            $result[$detail->id] = $detail->name.')';

        }
        return ['' =>'-Select Unit-'] + $result;
    }

    /**
     * @param $id
     */
    public function drop($id)
    {
        $this->find($id)->update([ 'deleted_by' => authUserId(), 'deleted_at' =>convertToUtc() ]);
    }

    /**
     * @param $id
     * @return bool
     */
    public function unitExists($id)
    {
        $unitExistsInProduct = (new Product)->company()->where('unit_id',$id)->first();
        if(count($unitExistsInProduct) > 0) {
            return true;
        }
    }

    /**
     * Method is used to find Unit ID.
     * @param string $search
     * @return id
     */
    public function findUnitID($search = '')
    {
        if ($search != '') {
            $filter = "code = '" . $search . "' ";
            return $this->select('unit.id as unit_id')
                ->whereRaw($filter)
                ->company()
                ->first();
        }
        return null;
    }
}