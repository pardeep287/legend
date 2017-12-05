<?php

namespace App;
/**
 * :: User Model ::
 * To manage users CRUD operations
 *
 **/

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

class User extends Model implements AuthenticatableContract, CanResetPasswordContract
{
    use Authenticatable, CanResetPassword;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'fullname',
        'company_id',
        'is_reset_password',
        'user_role_id',
        'status',
        'last_login',
        'login_attempts',
        'is_super_admin',
        'theme_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['password', 'remember_token'];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at', 'last_login'];

    /**
     * Scope a query to only include active users.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeCompany($query)
    {
        if(!isSuperAdmin()) {
            return $query->where('users.company_id', loggedInCompanyId());
        }
    }

    /**
     * @param $inputs
     * @return \Illuminate\Validation\Validator
     */
    public function validatePassword($inputs)
    {
        $rules['password']          = 'required';
        $rules['new_password']      = 'required|same:confirm_password';
        $rules['confirm_password']  = 'required';
        return \Validator::make($inputs, $rules);
    }

    /**
     * @param $inputs
     * @return mixed
     */
    public function updatePassword($inputs)
    {
        return $this->where('id', authUserId())
            ->update(
                [
                    'password' => $inputs['new_password'],
                    'is_reset_password' => $inputs['is_reset_password']
                ]);
    }

    /**
     * @return mixed
     */
    public function updateLastLogin()
    {
        $loginTiming = [
            'last_login'        => new \DateTime,
            'login_attempts'    => 0,
        ];
        return $this->find(authUserId())->update($loginTiming);
    }

    /**
     * @param string $username
     * @return mixed
     */
    public function updateFailedAttempts($username)
    {
        $user = $this->where('id', '!=', 1)
            ->where(function($query) use ($username) {
                  $query->where('username', $username)
                      ->orWhere('email', $username);
            })
            ->first();

        if ($user) {
            $user->increment('login_attempts', 1);
        }
    }

    /**
     * @param array $inputs
     * @return \Illuminate\Validation\Validator
     */
    public function validateUser($inputs, $id = null, $isAdmin = null)
    {
        $inputs = array_filter($inputs);
        // validation rule
        $message = [];
        $rules = [
            'name' => 'required'  
        ]; 
        
        if ($id) {
            $rules['username'] = 'required|unique:users,username,' . $id . ',id,deleted_at,NULL';
            $rules['email'] = 'required|unique:users,email,' . $id . ',id,deleted_at,NULL';
            $rules['password'] = 'min:5';
        } else {
            $rules['username'] = 'required|unique:users,username,NULL,id,deleted_at,NULL';
            $rules['email'] = 'required|unique:users,email,NULL,id,deleted_at,NULL';
            $rules['password'] = 'required|min:5';
            $rules['status'] =  'required|in:0,1';
        }
        
        $rules['company_id'] = 'required|numeric';
        $message = $message + [ 'company_id.required' => lang('company.company_required'), 'company_id.numeric' => lang('company.company_numeric')];
        $rules['role'] = 'required';

        if(!$isAdmin) {
            $rules['section'] = 'required';
            $message = $message + [
                'section.required'  => 'Please select the permision section.'
            ];
        }
        return \Validator::make($inputs, $rules, $message);
    }

    /**
     * @param array $inputs
     * @param int $id
     * @return mixed
     */
    public function store($inputs, $id = null)
    {
        if ($id) {
            $this->find($id)->update($inputs);
            return $id;
        } else {
            return $this->create($inputs)->id;
        }
    }

    /**
     * Method is used to search results.
     * @param array $search
     * @param int $skip
     * @param int $perPage
     * @return mixed
     */
    public function getUsers($search = null, $skip, $perPage)
    {
        $take = ((int)$perPage > 0) ? $perPage : 20;
        $filter = 1; // default filter if no search

        $fields = [
            'users.id',
            'username',
            'users.name',
            'user_roles.name as role',
            'users.status',
            'company.company_name'
        ];
        /* Sorting operation */
        $sortBy = [
            'username' => 'users.username',
            'name'     => 'users.name',
            'role'     => 'user_roles.id'
        ];
        
        $orderEntity = 'users.id';
        $orderAction = 'desc';
        if (isset($search['sort_action']) && $search['sort_action'] != "") {
            $orderAction = ($search['sort_action'] == 1) ? 'desc' : 'asc';
        }

        if (isset($search['sort_entity']) && $search['sort_entity'] != "") {
            $orderEntity = (array_key_exists($search['sort_entity'], $sortBy)) ? $sortBy[$search['sort_entity']] : $orderEntity;
        }

        /*End of sorting operation*/

        if (is_array($search) && count($search) > 0) {
            $name = (array_key_exists('keyword', $search)) ? " AND username LIKE '%" .
                addslashes(trim($search['keyword'])) . "%' " : "";
            $filter .= $name;
        }

        return $this->leftJoin('user_roles', 'user_roles.id', '=', 'users.user_role_id')
                    ->leftJoin('company', 'company.id', '=', 'users.company_id')
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
    public function totalUser($search = null)
    {
        $filter = 1; // default filter if no search

        // when search
        if (is_array($search) && count($search) > 0) {
            $partyName = (array_key_exists('keyword', $search)) ? " AND username LIKE '%" .
                addslashes(trim($search['keyword'])) . "%' " : "";
            $filter .= $partyName;
        }
        return $this->company()->select(\DB::raw('count(*) as total'))->whereRaw($filter)->get()->first();
    }

    /**
     * @return mixed
     */
    public function getLastLoginLog()
    {
        return $this->where('user.id', '>', 1)->take(20)->get(['username', 'last_login', 'name']);
    }

    /**
     * @return mixed
     */
    public function getUsersService()
    {
        $result = $this->active()->company()->where('id', '>', 1)->lists('name', 'id')->toArray();
        return ['' => '-Select Users-'] + $result;
    }
}
