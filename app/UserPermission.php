<?php

namespace App;

/**
 * :: UserPermissios Model ::
 * To manage UserPermissios CRUD operations
 *
 **/

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserPermission extends Model
{
    use SoftDeletes;
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'user_permissions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'menu_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * Scope a query to only include active Menu.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('menu.status', 1);
    }

    /**
     * @param $inputs
     * @param null $id
     * @return $this|Model|null
     */
    public function store($inputs, $id = null)
    {
        if ($id) {
            $this->find($id)->update($inputs);
            return $id;
        } else {
            return $this->create($inputs);
        }
    }

    /**
     * To get User Permissions.
     * @param $input
     * @param bool $isAll
     *@return Model|\Illuminate\Support\Collection|null|static
     * @internal param array $
     */

    /**
     * @param $input
     * @param bool $isAll
     * @return Model|\Illuminate\Support\Collection|null|static
     */
    public function getUserPermissions($input, $isAll = false)
    {
        $filter = 1;

        if (is_array($input) && count($input) > 0) {
            $keyword = (array_key_exists('user_id', $input)) ? " AND (user_id = '" . addslashes($input['user_id']) . "')" : "";
            $filter .= $keyword;
            $result = $this->leftJoin('menu', "menu.id", '=', \DB::raw("menu.id and FIND_IN_SET(menu.id, user_permissions.menu_id) "))
                ->whereRaw($filter)
                ->orderBy('_order', 'ASC')
                ->select('user_permissions.id as permission_id', 'user_permissions.menu_id','menu.id', 'menu.name', 'menu.route');

            if ($isAll == true) {
                return $result->first();
            } else {
                return $result->get();
            }
        }
    }

    /**
     * @param $menuId
     * @param $input
     * @return mixed
     */
    public function getPermissionsMenu($menuId, $input)
    {
        // $filter = 1;
        $keyword = (array_key_exists('user_id', $input)) ? " (user_id = '".addslashes($input['user_id']) . "')" : "";
        $filter = $keyword;

        return $result = $this->active()
            ->leftJoin('menu', "menu.id", '=', \DB::raw("menu.id and FIND_IN_SET(menu.id, user_permissions.menu_id) "))
            ->leftJoin('menu as parent', 'menu.parent_id', '=', 'parent.id')
            ->whereRaw($filter)
            ->select('menu.id', 'menu.name', 'menu.parent_id', 'parent.name as parent', 'menu.route', 'menu.dependent_routes', 'menu._order', 'user_permissions.id as permission_id', 'menu.is_in_menu')
            ->orderBy('menu.id', 'ASC')
            ->get()->toArray();
    }

    /**
     * @param $menuId
     * @param $input
     * @return mixed
     */
    public function userAllowedPermissions($menuId, $input)
    {
        // $filter = 1;
        $keyword = (array_key_exists('user_id', $input)) ? " (user_id = '".addslashes($input['user_id']) . "')" : "";
        $filter = $keyword;

        return $result = $this->active()
            ->leftJoin('menu', "menu.id", '=', \DB::raw("menu.id and FIND_IN_SET(menu.id, user_permissions.menu_id) "))
            ->leftJoin('menu as parent', 'menu.parent_id', '=', 'parent.id')
            ->whereRaw($filter)
            ->select('menu.id', 'menu.name', 'menu.parent_id', 'parent.name as parent', 'menu.route', 'menu.dependent_routes', 'menu._order', 'user_permissions.id as permission_id', 'menu.is_in_menu')
            ->orderBy('menu.id', 'ASC')
            ->get()->toArray();
    }
}
