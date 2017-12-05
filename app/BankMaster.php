<?php
namespace App;
/**
 * :: Bank Master Model ::
 * To manage Bank Master CRUD operations
 *
 **/

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankMaster extends Model
{
    use SoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'bank_master';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_id',
        'name',
        'account_number',
        'is_default',
        'manager_name',
        'mobile',
        'mobile2',
        'phone',
        'ifsc_code',
        'micr_code',
        'branch',
        'address',
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
    public function validateBank($inputs, $id = null)
    {
        // validation rule
        if($id) {
            $rules['name'] = 'required|unique:bank_master,name,' . $id . ',id,deleted_at,NULL,company_id,'.loggedInCompanyId();
        } else {
            $rules['name'] = 'required|unique:bank_master,name,NULL,id,deleted_at,NULL,company_id,'.loggedInCompanyId();
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
            $this->find($id)->update($input);
            return $id;
        } else {
            $id = $this->create($input)->id;
        }
    }

    /**
     * @param null $search
     * @return mixed
     */
    public function getBanks($search = null)
    {
        $filter = 1; // default filter if no search

        $fields = [
            'id',
            'name',
            'account_number',
            'ifsc_code',
            'micr_code',
            'manager_name',
            'mobile',
            'branch',
            'is_default',
            'status',
        ];

        if (is_array($search) && count($search) > 0) {
            $partyName = (array_key_exists('company', $search)) ? " AND company_id = '" .
                addslashes(trim($search['company'])) . "' " : "";
            $filter .= $partyName;
        }
        return $this->whereRaw($filter)
            ->orderBy('id', 'ASC')
            ->get($fields);
    }

    /**
     * @return mixed
     */
    public function getBanksService($id = null)
    {
        $result = $this->active()
        ->where('company_id', $id)
        ->lists('name', 'id')->toArray();
        return ['' => '-Select Banks-'] + $result;
    }
}
