<?php

namespace App;

/**
 * :: Menu Model ::
 * To manage Menu CRUD operations
 *
 **/

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Menu extends Model
{
    use SoftDeletes;

    /**
     * The database table used by the model.
     * @var string
     */
    protected $table = 'menu';

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = [
        'name',
        'route',
        'icon',
        'parent_id',
        'is_in_menu',
        'quick_menu',
        'dependent_routes',
        'is_common',
        '_order',
        'for_devs',
        'has_child',
        'status',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    /**
     * Scope a query to only include active users.
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function store($inputs, $id = null){
        if ($id) {
            $this->find($id)->update($inputs);
            return $id;
        } else {
            return $this->create($inputs)->id;
        }
    }

    /**
     * @param array $inputs
     * @param int $id
     *
     * @return \Illuminate\Validation\Validator
     */
    public function validateMenu($inputs, $id = null)
    {
        $rules = [
            'display_name' => 'required',
            'status' => 'required|in:0,1'
        ];
        if($id) {
            $rules['display_name'] = 'required|unique:menu,name,' . $id . ',id,deleted_at,NULL';
        } else {
            $rules['display_name'] = 'required|unique:menu,name,NULL,id,deleted_at,NULL';
        }
        return \Validator::make($inputs, $rules);
    }

    /**
     * @return array
     */
    public function parentData() {

        $result = $this->where('has_child', 1)->pluck('name','id')->toArray();
        return ['' => '-Select Parent Menu-'] + $result;
    }

    /**
     * Method is used to search total results.
     * @param array $search
     * @param int $skip
     * @param int $perPage
     * @return mixed
     */
    public function getMenu($search = null, $skip, $perPage)
    {
        trimInputs();
        $take = ((int)$perPage > 0) ? $perPage : 20;
        $filter = 1; // default filter if no search

        $fields = [
            'menu.id',
            'menu.name',
            'menu.route',
            'parent.name as parent',
            'menu.is_in_menu',
            'menu.quick_menu',
            'menu.status'
        ];

        if (is_array($search) && count($search) > 0) {
            $keyword = (array_key_exists('keyword', $search)) ? " AND (menu.name LIKE '%" .
                addslashes(trim($search['keyword'])) . "%')" : "";
            $filter .= $keyword;
        }

        if (is_array($search) && count($search) > 0) {
            $keyword = (array_key_exists('quick_menu', $search)) ? " AND (menu.quick_menu == 1)" : "";
            $filter .= $keyword;
        }

        return $this->leftJoin('menu as parent','menu.parent_id','=','parent.id')
            ->whereRaw($filter)->orderBy('menu.id', 'ASC')
            ->get($fields);
    }

    /**
     * Method is used to get total results.
     * @param array $search
     * @return mixed
     */
    public function totalMenu($search = null)
    {
        trimInputs();
        $filter = 1; // default filter if no search

        if (is_array($search) && count($search) > 0) {
            $keyword = (array_key_exists('keyword', $search)) ? " AND (name LIKE '%" .
                addslashes(trim($search['keyword'])) . "%')" : "";
            $filter .= $keyword;
        }
        return $this->select(\DB::raw('count(*) as total'))->whereRaw($filter)->get()->first();
    }

    /**
     * @param null $search
     * @return mixed
     */
    public function getMenuItems($search = null)
    {
        trimInputs();
        $filter = 1; // default filter if no search
        if (is_array($search) && count($search) > 0) {
            $keyword = (array_key_exists('keyword', $search)) ? " AND (name LIKE '%" .
                addslashes(trim($search['keyword'])) . "%')" : "";
            $filter .= $keyword;
        }
        return $this->select(\DB::raw('count(*) as total'))->whereRaw($filter)->get()->first();
    }

    /**
     * @param bool $isMenu
     * @param bool $isTopMenu
     * @return array
     */
    public function getMenuNavigation($isMenu = false, $isTopMenu = false)
    {
        $userAssignedMenus = (new UserPermission)->where('user_id', authUserId())->first();

        $query = $this->where('menu.status', 1);
        if($isTopMenu) {
            $query = $query->where('menu.is_in_menu', 1);
        }
        $data = $query->leftJoin('menu as parent', 'menu.parent_id', '=', 'parent.id');

        if(count($userAssignedMenus) > 0) {
            $menuArr = (!empty($userAssignedMenus->menu_id))?explode(',', $userAssignedMenus->menu_id):[];
            $data->whereIn('menu.id', $menuArr);
        }

        if ($isMenu) {
            $data->orderBy('menu._order', 'asc');
        }
        $res = $data->get(
            [
                'menu.id',
                'menu.name',
                'parent.name as parent',
                'menu.route',
                'menu.parent_id',
                'menu.dependent_routes',
                'menu.icon',
                'menu.is_in_menu',
                'menu.quick_menu'
            ]
        );
        $result =  $res->toArray();
        return $this->prepareNavigation($result);
    }
    /**
     * @param $data
     * @param null $parent
     *
     * @return array
     */
    public function prepareNavigation($data, $parent = null)
    {
        $nav = array();
        $x = 0;
        foreach ($data as $d) {
            if ($d['parent_id'] == $parent) {
                $child = $this->prepareNavigation($data, $d['id']);
                // set a trivial key
                if (!empty($child)) {
                    $d['child'] = $child;
                }
                $nav[$x]['id']                  = $d['id'];
                $nav[$x]['name']                = $d['name'];
                $nav[$x]['route']               = $d['route'];
                $nav[$x]['icon']               = $d['icon'];
                if(isset($d['is_in_menu']))
                {
                    $nav[$x]['is_in_menu']      = $d['is_in_menu'];
                }

                if(isset($d['quick_menu']) && $d['quick_menu'] == 1)
                {
                    $nav[$x]['quick_menu']      = $d['quick_menu'];
                }

                $nav[$x]['dependent_routes']    = $d['dependent_routes'];

                if(array_key_exists('child', $d))
                    $nav[$x]['child'] =  $d['child'];
                $x++;
            }
        }
        return $nav;
    }
}
