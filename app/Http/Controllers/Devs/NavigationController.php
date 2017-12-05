<?php

namespace App\Http\Controllers\Devs;

use Illuminate\Http\Request;

use App\Navigation;
use App\Http\Requests;
use App\Http\Controllers\Controller;

class NavigationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('navigation.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $parentMenu = (new Navigation)->getParentNavigationService();
        return view('navigation.create', compact('parentMenu'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store()
    {
        $inputs = \Input::all();
        $validator = (new Navigation)->validateNavigation($inputs);
        if ($validator->fails()) {
            return redirect()->route('navigation.create')
                ->withInput()
                ->withErrors($validator);
        }

        try {
            $parent = $inputs['parent'];
            unset($inputs['parent']);
            \DB::beginTransaction();
            $inputs = $inputs + [
                'created_by'    => authUserId(),
                'parent_id'    => ($parent != null) ? $parent : null,
            ];
            (new Navigation)->store($inputs);
            \DB::commit();
            return redirect()->route('navigation.index')
                ->with('success', lang('messages.created', lang('navigation.navigation')));
        } catch (\Exception $exception) {
            \DB::rollBack();
            return redirect()->route('navigation.create')
                ->withInput()
                ->with('error', lang('messages.server_error'));
        }
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $navigation = Navigation::find($id);
        if (!$navigation) {
            abort(404);
        }

        $parentMenu = (new Navigation)->getNavigationService();
        return view('navigation.edit', compact('navigation', 'parentMenu'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update($id)
    {
        $navigation = Navigation::find($id);
        if (!$navigation) {
            return redirect()->route('navigation.index')
                ->with('error', lang('messages.invalid_id', string_manip(lang('navigation.navigation'))));
        }
        
        $inputs = \Input::all();
        $validator = (new Navigation)->validateNavigation($inputs, $id);
        if ($validator->fails()) {
            return redirect()->route('navigation.edit', ['id' => $id])
                ->withInput()
                ->withErrors($validator);
        }

        try {
            \DB::beginTransaction();
            $inputs = $inputs + [
                'updated_by'    => authUserId(),
            ];
            (new Navigation)->store($inputs, $id);
            \DB::commit();
            return redirect()->route('navigation.index')
                ->with('success', lang('messages.updated', lang('navigation.navigation')));
        } catch (\Exception $exception) {
            \DB::rollBack();
            return redirect()->route('navigation.edit', ['id' => $id])
                ->with('error', lang('messages.server_error'));
        }
    }

    /**
     * Used to update navigation active status.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function navigationToggle($id)
    {
        if (!\Request::isMethod('post') && !\Request::ajax()) {
            return lang('messages.server_error');
        }

        try {
            $navigation = Navigation::find($id);
        } catch (\Exception $exception) {
            return lang('messages.invalid_id', string_manip(lang('navigation.navigation')));
        }

        $navigation->update(['status' => !$navigation->status]);
        $response = ['status' => 1, 'data' => (int)$navigation->status . '.gif'];
        // return json response
        return json_encode($response);
    }

    /**
     * Used to load more records and render to view.
     *
     * @param int $pageNumber
     * @return \Illuminate\Http\Response
     */
    public function navigationPaginate($pageNumber = null)
    {
        if (!\Request::isMethod('post') && !\Request::ajax()) {
            return lang('messages.server_error');
        }

        $inputs = \Input::all();
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

            $data = (new Navigation)->getNavigation($inputs, $start, $perPage);
            $total = (new Navigation)->totalNavigation($inputs);
            $total = $total->total;
        } else {
            $data = (new Navigation)->getNavigation($inputs, $start, $perPage);
            $total = (new Navigation)->totalNavigation($inputs);
            $total = $total->total;
        }

        return view('navigation.load_data', compact('data', 'total', 'page', 'perPage'));
    }

    /**
     * Method is used to update status of group enable/disable
     *
     * @return \Illuminate\Http\Response
     */
    public function navigationAction()
    {
        $inputs = \Input::all();
        if (!isset($inputs['tick']) || count($inputs['tick']) < 1) {
            return redirect()->route('navigation.index')
                ->with('error', lang('messages.atleast_one', string_manip(lang('navigation.navigation'))));
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

        Navigation::whereRaw('id IN (' . $ids . ')')->update(['status' => $status]);
        return redirect()->route('navigation.index')
            ->with('success', lang('messages.updated', lang('navigation.navigation_status')));
    }

    /**
     * @return String
     */
    public function getNavigationsSearch($id)
    {
        if (!\Request::isMethod('post') && !\Request::ajax()) {
            return lang('messages.server_error');
        }
        $result = (new Navigation)->navigationSearch($id);
        $options = '';
        foreach($result as $key => $value) {
            $options .='<option value="'. $key .'">' . $value . '</option>';
        }
        echo $options;
    }
}
