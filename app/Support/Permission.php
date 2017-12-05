<?php 
/**
 * :: Permission File :: 
 * USed for manage all kind of User Permission functions.
 *
 **/
use App\Menu;
use App\UserPermission;
/**
 * @return user permission from json file
 */
function getNavigation()
{
    $menu = [];
    if(authUser()) {
        $userId   = authUser()->id;
        $result   = (new UserPermission)->getUserPermissions(['user_id'=> $userId], true); //->toArray()
        $result   = (!empty($result) && is_array($result))? $result->toArray() : $result;
        $menuId   = (!empty($result)) ? $result['menu_id'] : '';
        $menuData = (new UserPermission)->userAllowedPermissions($menuId, ['user_id'=> $userId]);
        $menu     = (new Menu)->prepareNavigation($menuData);
    }
    return view('layouts.user-menu', compact('menu'));    
}

function getUserPermission()
{
    $permissionResult = [];
    if(authUser()) {
        $userId = authUser()->id;
        $result = (new UserPermission)->getUserPermissions(['user_id'=> $userId], true);
        $menuId = (!empty($result)) ? $result['menu_id'] : '';
        $menuData = (new UserPermission)->userAllowedPermissions($menuId, ['user_id'=> $userId]);
        $routes                  = array_column($menuData, 'dependent_routes', 'route');
        $permissionsKeys         = array_keys($routes);
        $permissionsValues       = array_values($routes);
        $permissions             = array_merge($permissionsKeys, $permissionsValues);
        $permissionsComaSeprate  = implode(',', $permissions);
        $permissionResult        = explode(',', $permissionsComaSeprate);
    }
    return $permissionResult;
}

/*
 *      Old Navigation Code
 *
    function getNavigation()
    {
    	$userId = authUser()->id;
        $fileName =  md5('user_permission').'_'.$userId;
        $storagePath = storage_path().'\\user_permission_json\\'.$fileName.'.json';
        $data = json_decode(File::get($storagePath), true);
    	$result = (new Menu)->prepareNavigation($data);
    	return view('layouts.user-menu', compact('result'));
    }

    function getUserPermissionFromJson()
    {
    	$userId = authUser()->id;
        $fileName =  md5('user_permission').'_'.$userId;
        $storagePath = storage_path().'\\user_permission_json\\'.$fileName.'.json';
        $currentRouteName 		= \Route::currentRouteName(); // current route name
        if(\File::exists($storagePath)) {
            $result 		  		= (array)json_decode(\File::get($storagePath), true);
            $routes 				= array_column($result, 'dependent_routes', 'route');
            $permissionsKeys 		= array_keys($routes);
            $permissionsValues      = array_values($routes);
            $permissions 			= array_merge($permissionsKeys, $permissionsValues);
            $permissionsComaSeprate = implode(',', $permissions);
            $permissionResult		= explode(',', $permissionsComaSeprate);
            $routeCheck 			= in_array($currentRouteName, $permissionResult);
            if($currentRouteName != 'dashboard'){
            	if($routeCheck != true)
    	        {
    	        	abort(401);
    	        }
            }
        } else {
        	abort(404);
        }
    }
*/