<?php

namespace App\Http\Controllers;

/**
 * :: State Controller ::
 * To manage State.
 *
 **/

use Illuminate\Http\Request;
use App\Country;
use App\State;

class StateController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        return view('state.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        $country = (new Country)->getCountryService();
        return view('state.create', compact('country'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $inputs = $request->all();
        $validator = (new State)->validateState($inputs);
        if ($validator->fails()) {
            return validationResponse(false, 206, "", "", $validator->messages());
        }
        try {
            \DB::beginTransaction();
            $country = $inputs['country'];
            unset($inputs['country']);

            $inputs = $inputs + [
                    /*'state_name'        => $inputs['state_name'],
                    'state_digit_code'  => $inputs['state_digit_code'],
                    'state_char_code'   => $inputs['state_char_code'],*/
                    'country_id'   => $country,
                    'status'        => (isset($inputs['status']) && $inputs['status'] == '1')? 1 : 0,
                    'created_by'    => authUserId()
                ];

            (new State)->store($inputs);
            \DB::commit();
            $langMessage = lang('messages.created', lang('location.state'));
            $route = route('state.index');
            return validationResponse(true, 201, $langMessage, $route);
        } catch (\Exception $e) {
            \DB::rollBack();
            return validationResponse(false, 207, $e->getMessage().' - '.$e->getFile().' - '.$e->getLine().' - '.lang('messages.server_error'));
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return Response
     */
    public function edit($id = null)
    {
        $result = State::find($id);
        if (!$result) {
            abort(404);
        }
        $country = (new Country)->getCountryService();
        return view('state.edit', compact('result', 'country'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id = null)
    {
        $result = State::find($id);
        if (!$result) {
            return redirect()->route('state.index')
                ->with('error', lang('messages.invalid_id', string_manip(lang('location.state'))));
        }

        $inputs = $request->all();
        $validator = (new State)->validateState($inputs, $id);
        if ($validator->fails()) {
            return validationResponse(false, 206, "", "", $validator->messages());
        }

        try {
            \DB::beginTransaction();
            $country = $inputs['country'];
            unset($inputs['country']);

            $inputs = $inputs + [
                    'country_id'   => $country,
                    'status'        => (isset($inputs['status']) && $inputs['status'] == '1')? 1 : 0,
                    'updated_by' => authUserId()
                ];

            (new State)->store($inputs, $id);
            \DB::commit();
            $langMessage = lang('messages.updated', lang('location.state'));
            $route = route('state.index');
            return validationResponse(true, 201, $langMessage, $route);
        } catch (\Exception $e) {
            \DB::rollBack();
            return validationResponse(false, 207, $e->getMessage().' - '.$e->getFile().' - '.$e->getLine().' - '.lang('messages.server_error'));

        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return Response
     */
    public function drop($id)
    {
        return "In Progress";
    }

    /**
     * Used to update state active status.
     *
     * @param int $id
     * @return Response
     */
    public function stateToggle($id = null)
    {
        if (!\Request::ajax()) {
            return lang('messages.server_error');
        }

        try {
            // get the state w.r.t id
            $result = State::find($id);

            $result->update(['status' => !$result->status]);
            $response = ['status' => 1, 'data' => (int)$result->status . '.gif'];
            // return json response
            return json_encode($response);

        } catch (\Exception $exception) {
            return lang('messages.invalid_id', string_manip(lang('location.state')));
        }
    }

    /**
     * Used to load more records and render to view.
     *
     * @param Request $request
     * @param int $pageNumber
     * @return Response
     */
    public function statePaginate(Request $request, $pageNumber = null)
    {
        if (!\Request::isMethod('post') && !\Request::ajax()) { //
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

            $data = (new State)->getState($inputs, $start, $perPage);
            $total = (new State)->totalState($inputs);
            $total = $total->total;
        } else {

            $data = (new State)->getState($inputs, $start, $perPage);
            $total = (new State)->totalState($inputs);
            $total = $total->total;
        }
        return view('state.load_data', compact('data', 'total', 'page', 'perPage', 'inputs'));
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStateLister(Request $request)
    {
        $states= (new State)->getStateServiceAjax($request->country_id);
        return response()->json($states);
    }

}
