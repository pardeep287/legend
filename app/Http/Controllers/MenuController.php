<?php

namespace App\Http\Controllers;

use App\Menu;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('menu.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $parentdata = (new Menu)->parentData();
        return view('menu.create',compact('parentdata'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $inputs = $request->all();
        $validator = (new Menu)->validateMenu($inputs);
        if ($validator->fails()) {
            return validationResponse(false, 206, "", "", $validator->messages());
        }

        try {
            \DB::beginTransaction();

            unset($inputs['_token']);

            $displayName = $inputs['display_name'];
            unset($inputs['display_name']);
            $routeName = $inputs['route_name'];
            unset($inputs['route_name']);
            $parentId = $inputs['parent_menu'];
            unset($inputs['parent_menu']);
            $order = $inputs['order'];
            unset($inputs['order']);

            $inputs = $inputs + [
                    'name'        => $displayName,
                    'route'       => $routeName,
                    'parent_id'   => ($parentId != "") ? $parentId : null,
                    '_order'      => $order,
                    'created_by'  => authUserId()
                ];
            (new menu)->store($inputs);
            \DB::commit();
            $route = route('menu.index');
            return validationResponse(true, 201, lang('messages.created', lang('menu.menu')), $route);
        }
        catch (\Exception $exception) {
            \DB::rollBack();
            return validationResponse(false, 207, $exception->getMessage().' '.lang('messages.server_error'));
        }
    }
    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Menu  $menu
     * @return \Illuminate\Http\Response
     */
    public function edit(Menu $menu)
    {
        $result = $menu;
        if (!$result) {
            abort(404);
        }
        if(!isSuperAdmin()) {
            abort(401);
        }
        // $items = (new Menu)->getMenuItems(['id' => $id]);
        $parentdata = (new Menu)->parentData();
        return view('menu.edit', compact('result', 'parentdata'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Menu  $menu
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Menu $menu)
    {
        $result = $menu;
        if (!$result) {
            return validationResponse(false, 206, lang('messages.invalid_id', string_manip(lang('menu.menu'))));
        }

        $inputs = $request->all();
        $validator = (new Menu)->validateMenu($inputs, $menu->id);
        if ($validator->fails()) {
            return validationResponse(false, 206, "", "", $validator->messages());
        }

        try {
            \DB::beginTransaction();

            $displayName = $inputs['display_name'];
            unset($inputs['display_name']);
            $routeName = $inputs['route_name'];
            unset($inputs['route_name']);
            $parentId = $inputs['parent_menu'];
            unset($inputs['parent_menu']);
            $order = $inputs['order'];
            unset($inputs['order']);

            $inputs = $inputs + [
                'name'          => $displayName,
                'route'         => $routeName,
                'parent_id'     => ($parentId != "") ? $parentId : null,
                '_order'        => $order,
                'is_in_menu'    => (isset($inputs['is_in_menu']) ? $inputs['is_in_menu'] : 0),
                'quick_menu'    => (isset($inputs['quick_menu']) ? $inputs['quick_menu'] : 0),
                'is_common'     => (isset($inputs['is_common']) ? $inputs['is_common'] : 0),
                'for_devs'      => (isset($inputs['for_devs']) ? $inputs['for_devs'] : 0),
                'has_child'     => (isset($inputs['has_child']) ? $inputs['has_child'] : 0),
                'updated_by' => authUserId()
            ];
            (new Menu)->store($inputs, $menu->id);
            \DB::commit();
            $route = route('menu.index');
            return validationResponse(true, 201, lang('messages.updated', lang('menu.menu')), $route);
        } catch (\Exception $exception) {
            \DB::rollBack();
            return validationResponse(false, 207, $exception->getMessage().' '.lang('messages.server_error'));
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Menu  $menu
     * @return \Illuminate\Http\Response
     */
    public function destroy(Menu $menu)
    {
        $result = $menu->id; //Menu::find($id);
        if(!$result)
        {
            abort(404);
        }
        if(!isSuperAdmin()) {
            abort(401);
        }
        return redirect()->back()->with('error', lang('messages.in_progress', lang('messages.delete')));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function menuAction(Request $request)
    {

        $inputs = $request->all();
        if (!isset($inputs['tick']) || count($inputs['tick']) < 1) {
            return redirect()->route('menu.index')
                ->with('error', lang('messages.atleast_one', string_manip(lang('menu.menu'))));
        }

        $ids = '';
        foreach ($inputs['tick'] as $key => $value) {
            $ids .= $value . ',';
        }

        $ids = rtrim($ids, ',');
        $status = 0;
        if (isset($inputs['active'])) {
            $status = 1;
        }

        Company::whereRaw('id IN (' . $ids . ')')->update(['status' => $status]);
        return redirect()->route('menu.index')
            ->with('success', lang('messages.updated', lang('menu.menu_status')));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function menuPaginate(Request $request, $pageNumber = null)
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
        if (isset($inputs['form-search']) && $inputs['form-search'] != '') {
            $inputs = array_filter($inputs);
            unset($inputs['_token']);

            $data = (new Menu)->getMenu($inputs, $start, $perPage);
            $total = (new Menu)->totalMenu($inputs);
            $total = $total->total;
        } else {
            $data = (new Menu)->getMenu($inputs, $start, $perPage);
            $total = (new Menu)->totalMenu($inputs);
            $total = $total->total;
        }
        return view('menu.load_data', compact('data', 'total', 'page', 'perPage'));
    }


    public function menuToggle($id = null)
    {
        if (!\Request::ajax()) {
            return lang('messages.server_error');
        }
        $result = Menu::find($id);
        try {
            // get the brand w.r.t id

            $result->update(['status' => !$result->status]);
            $response = ['status' => 1, 'data' => (int)$result->status . '.gif'];
            // return json response
            return json_encode($response);
        } catch (\Exception $exception) {
            return lang('messages.invalid_id', string_manip(lang('menu.menu')));
        }
    }
    /** * Method is used to sort news.
     *
     * @return Response
     */
    public function sortingMenu(Request $request)
    {
        $inputAll = $request->all();
        $menuOrder = $inputAll['order'];
        // dd($menuOrder);
        try {
            if( count($menuOrder) > 0 ) {
                $index = count($menuOrder);
                foreach ($menuOrder as $key => $value) {
                    Menu::where('id', $value)
                        ->update(['_order' => $index--]);
                }
            }
            // return 1 for successfully sorted news.
            echo '1';
        } catch (\Exception $e) {
            // else return 0
            echo '0';
        }
    }
}
