<?php
namespace App;
/**
 * :: Unit Model ::
 * To manage Unit CRUD operations
 *
 **/

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use SoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'employee_master';

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = [
        'name',
        'employee_number',
        'mobile',
        'phone',
        'email',
        'address',
        'country_id',
        'state_id',
        'city_id',
        'pincode',
        'department_id',
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
     * @param $inputs
     * @param int $id
     * @return Response
     */
    public function validateEmployee($inputs, $id = null)
    {
        // validation rule
        if ($id) {
            $rules['name'] = 'required|unique:employee_master,name,' . $id .',id,deleted_at,NULL';
            $rules['employee_number'] = 'required|unique:employee_master,employee_number,' . $id .',id,deleted_at,NULL';
        } else {
            $rules['name'] = 'required|unique:employee_master,name,NULL,id,deleted_at,NULL';
            $rules['employee_number'] = 'required|unique:employee_master,employee_number,NULL';
        }
        $rules = $rules + [
            'department_id' => 'required|numeric',
            'mobile'        => 'nullable|numeric|digits_between:10,11',
            'phone'         => 'nullable|numeric_hyphen|max:13',
            'email'         => 'nullable|email',
            'state_id'      => 'numeric',
            'country_id'    => 'numeric',
            'city_id'       => 'numeric',
            ];
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
    public function getDepartments($search = null, $skip, $perPage)
    {
        $sortBy = [
            'dept_name' => 'dept_name',
            'dept_code' => 'dept_code',
        ];
        $take = ((int)$perPage > 0) ? $perPage : 20;
        $filter = 1; // default filter if no search

        $fields = [
            'id',
            'dept_name',
            'dept_code',
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
                " AND (dept_name LIKE '%".addslashes(trim($search['keyword'])).
                "%' OR dept_code LIKE '%".addslashes(trim($search['keyword']))."%')"
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
    public function totalDepartments($search = null)
    {
        $filter = 1; // if no search add where

        // when search
        if (is_array($search) && count($search) > 0) {
            $partyName = (array_key_exists('keyword', $search)) ? " AND dept_name LIKE '%" .
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
    public function getDepartmentService()
    {
        $data = $this->active()
            ->company()
            ->orderBy('id','DESC')
            ->get([\DB::raw("concat(dept_name, ' (', dept_code) as name"), 'id']);
        $result = [];
        foreach($data as $detail) {
            $result[$detail->id] = $detail->name.')';

        }
        return ['' =>'-Select Department-'] + $result;
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
    public function DepartmentExists($id)
    {
        $DeptExistsInEmployee = (new Employee)->company()->where('employee_id',$id)->first();
        if(count($DeptExistsInEmployee) > 0) {
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

    public function getNewEmpnumber()
    {
        $employeeNumber = 0;
        $result = $this->orderBy('id', 'desc')->take(1)->first(['employee_number']);
        if(!empty($result->_order))
        {
            $employeeNumber = ($result->employee_number + 1);
        }else{
            $employeeNumber = 1;
        }
        return $employeeNumber;
    }
}