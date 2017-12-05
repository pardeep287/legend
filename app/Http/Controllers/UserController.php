<?php
namespace App\Http\Controllers;

/**
 * :: User Controller ::
 * To manage users.
 *
 **/

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\City;
use App\Menu;
use App\Theme;
use App\Laboratory;
use App\UserRoles;
use App\User;
use App\Company;
use File;
use App\UserPermission;

class UserController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        //return view('test');
        return view('user.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $role    = (new UserRoles)->getRoleService();
        $tree    = (new Menu)->getMenuNavigation(true, false);
        $companies = (new Company)->getCompanyService(); 
        return view('user.create', compact('role', 'tree', 'companies'));
    }


    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return $this|\Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $isAdmin = false;
        $inputs = $request->except('section');
        $data = $request->all();
        

        if(!empty($data['role']) && $data['role'] == 1) {
            $isAdmin = true;
        }

        $validator = (new User)->validateUser($data, null, $isAdmin);
        if ($validator->fails()) {
            return validationResponse(false, 206, "", "", $validator->messages());
        }

        try {
            $password = $inputs['password'];
            $role = $inputs['role'];
            unset($inputs['password']);
            unset($inputs['role']);

            \DB::beginTransaction();
            $inputs = $inputs + [
                'user_role_id'       => $role,
                'password'      => \Hash::make($password),
                'created_by'    => authUserId()
            ];

            $userId = (new User)->store($inputs);
            if(!$isAdmin) {
                if (isset($data['section']) && is_array($data['section']) && count($data['section']) > 0) {
                    $sections = $data['section'];
                    $sectionsData = implode(',',$sections);
                    $sectionExplode = explode(',',$sectionsData);
                    $uniqueValues = array_unique($sectionExplode);
                    $menuId = implode(',', $uniqueValues);
                }

                $section = [
                    'user_id' => $userId,
                    'menu_id' => (isset($uniqueValues) && count($uniqueValues) > 0) ? $menuId : null,
                    'created_by' => authUserId(),
                    'created_at' => convertToUtc()
                ];
                (new UserPermission)->store($section);
            }
            \DB::commit();
            $langMessage = lang('messages.created', lang('user.user'));
            $route = route('user.index');
            return validationResponse(true, 201, $langMessage, $route);
        } catch (\Exception $exception) {
            \DB::rollBack();
            return validationResponse(false, 207, $exception->getMessage().' - '.lang('messages.server_error'));
        }
    }

    /**
     * Show the form for editing the specified resource.
     * @param  int $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $isAdmin = false;
        $user = (new User)->company()->find($id);
        if (!$user) {
            return redirect()->route('user.index')
                ->with('error', lang('messages.invalid_id', string_manip(lang('user.user'))));
        }

        if ($user->id == 1) {   
            if ($user->id != authUserId()) {
                return redirect()->route('user.index')
                    ->with('error', lang('messages.permission_denied'));
            }
        }
        $role = (new UserRoles)->getRoleService();

        $tree = (new Menu)->getMenuNavigation(true, false);
        $userPermissions = (new UserPermission)->getUserPermissions(['user_id'=> $id], true);
        $companies = (new Company)->getCompanyService(); 
        $detail = [];
        if (count($userPermissions) > 0) {
            if ($userPermissions->menu_id != "") {
                $detail = explode(',', $userPermissions->menu_id);
            }
        }
        if(!empty($user->user_role_id) && $user->user_role_id == 1) {
            $isAdmin = true;
        }
        return view('user.edit', compact('user', 'role','tree', 'detail', 'userPermissions', 'isAdmin', 'comp   anies', 'companies'));
    }


    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param  int $id
     * @return $this|\Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $user = (new User)->company()->find($id);
        $isAdmin = false;
        if (!$user) {
            $langMessage = lang('messages.invalid_id', string_manip(lang('user.user')));
            $route = route('user.index');
            return validationResponse(false, 207, $langMessage, $route);
        }

        if ($user->id == 1) {
            if ($user->id != authUserId()) {
                $langMessage = lang('messages.permission_denied');
                $route = route('user.index');
                return validationResponse(false, 207, $langMessage, $route);
            }
        } 

        $inputs = $request->except('company');
        $data = $request->all();
        
        if( !array_key_exists('is_super_admin', $inputs) ) {
            $inputs = $inputs + [ 'is_super_admin' => 0 ];
        }
        
        if(!empty($data['role']) && $data['role'] == 1) {
            $isAdmin = true;
        }
        
        $validator = (new User)->validateUser($data, $id, $isAdmin);
        if ($validator->fails()) {
            return validationResponse(false, 206, "", "", $validator->messages());
        }

        try {
            \DB::beginTransaction();
            $password = $inputs['password'];
            unset($inputs['password']);
            $role = $inputs['role'];
            unset($inputs['role']);
            $inputs = $inputs + [
                'user_role_id'       => $role,
                'updated_by'    => authUserId()
            ];
            if (trim($password) != "") {
                $inputs['password'] = \Hash::make($password);
            }

            (new User)->store($inputs, $id);
            if(!$isAdmin) {
                if (isset($data['section']) && is_array($data['section']) && count($data['section']) > 0) {
                    $sections = $data['section'];
                    $sectionsData = implode(',', $sections);
                    $sectionExplode = explode(',', $sectionsData);
                    $uniqueValues = array_unique($sectionExplode);
                    $menuId = implode(',', $uniqueValues);
                }
                $section = [
                    'user_id' => $id,
                    'menu_id' => (isset($uniqueValues) && count($uniqueValues) > 0) ? $menuId : null,
                    'created_by' => authUserId(),
                    'created_at' => convertToUtc()
                ];

                (new UserPermission)->store($section, $inputs['pemission_id'], 1);
            }

            \DB::commit();
            $langMessage = lang('messages.updated', lang('user.user'));
            $route = route('user.index');
            return validationResponse(true, 201,$langMessage, $route);
        } catch (\Exception $exception) {
            \DB::rollBack();
            return validationResponse(false, 207, $exception->getMessage().' - '.lang('messages.server_error'));
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return Response
     */
    public function drop($id)
    {
        return "In Progress";
    }

    /**
     * Used to update User active status.
     * @param  int $id
     * @return string
     */
    public function userToggle($id = null)
    {
        if (!\Request::isMethod('post') && !\Request::ajax()) {
            return lang('messages.server_error');
        }
        try {

            $user = (new User)->company()->find($id);

            $user->update(['status' => !$user->status]);
            $response = ['status' => 1, 'data' => (int)$user->status . '.gif'];
            // return json response
            return json_encode($response);

        }catch (\Exception $exception) {
            return lang('messages.invalid_id', string_manip(lang('user.user')));
        }
    }

    /**
     * Used to load more records and render to view.
     * @param Request $request
     * @param int $pageNumber
     * @return mixed
     */
    public function userPaginate(Request $request, $pageNumber = null)
    {

        if (!\Request::isMethod('post') && !\Request::ajax()) {
            return lang('messages.server_error');
        }

        $inputs = $request->all();
        $page = 1;
        if (isset($inputs['page']) && (int)$inputs['page'] > 0) {
            $page = $inputs['page'];
        }

        $perPage = 20;
        if (isset($inputs['perpage']) && (int)$inputs['perpage'] > 0) {
            $perPage = $inputs['perpage'];
        }

        $start = ($page - 1) * $perPage;
        $filter = '';

        if (isset($inputs['form-search']) && $inputs['form-search'] != '') {
            $inputs = array_filter($inputs);
            unset($inputs['_token']);

            $data = (new User)->getUsers($inputs, $start, $perPage);
            $totalUser = (new User)->totalUser($inputs);
            $total = $totalUser->total;

        } else {
            $data = (new User)->getUsers($filter, $start, $perPage);

            $totalUser = (new User)->totalUser();
            $total = $totalUser->total;
        }

        return view('user.load_data', compact('data', 'total', 'page', 'perPage', 'inputs'));
    }

    /**
     * Method is used to update status of user enable/disable
     * @param Request $request
     * @return Response
     */
    public function userAction(Request $request)
    {
        $inputs = $request->all();
        if (!isset($inputs['tick']) || count($inputs['tick']) < 1) {
            return redirect()->route('user.index')
                ->with('error', lang('messages.atleast_one', string_manip(lang('user.user'))));
        }

        $ids = '';
        foreach ($inputs['tick'] as $key => $value) {
            $ids .= $value . ',';
        }
        $ids = rtrim($ids, ',');
        $status = 0;
        if (isset($inputs['active'])) {
            $status = '1';
        }

        User::whereRaw('id IN (' . $ids . ')')->update(['status' => $status]);
        return redirect()->route('user.index')
            ->with('success', lang('messages.updated', lang('user.user_status')));
    }
}