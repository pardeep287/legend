<?php

namespace App\Http\Controllers;

/**
 * :: City Controller ::
 * To manage City.
 *
 **/

use Illuminate\Http\Request;
use App\State;
use App\City;

class CityController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        return view('city.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        $state = (new State)->getStateService();
        return view('city.create', compact('state'));
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
        $validator = (new City)->validateCity($inputs);
        if ($validator->fails()) {
            return validationResponse(false, 206, "", "", $validator->messages());
        }
        try {
            \DB::beginTransaction();
            $state = $inputs['state'];
            unset($inputs['state']);

            $inputs = $inputs + [
                    /*'city_name'        => $inputs['city_name'],
                    'city_digit_code'  => $inputs['city_digit_code'],
                    'city_char_code'   => $inputs['city_char_code'],*/
                    'state_id'   => $state,
                    'status'        => (isset($inputs['status']) && $inputs['status'] == '1')? 1 : 0,
                    'created_by'    => authUserId()
                ];

            (new City)->store($inputs);
            \DB::commit();
            $langMessage = lang('messages.created', lang('location.city'));
            $route = route('city.index');
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
        $result = City::find($id);
        if (!$result) {
            abort(404);
        }
        $state = (new State)->getStateService();
        return view('city.edit', compact('result', 'state'));
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
        $result = City::find($id);
        if (!$result) {
            return redirect()->route('city.index')
                ->with('error', lang('messages.invalid_id', string_manip(lang('location.city'))));
        }

        $inputs = $request->all();
        $validator = (new City)->validateCity($inputs, $id);
        if ($validator->fails()) {
            return validationResponse(false, 206, "", "", $validator->messages());
        }

        try {
            \DB::beginTransaction();
            $state = $inputs['state'];
            unset($inputs['state']);

            $inputs = $inputs + [
                    'state_id'      => $state,
                    'status'        => (isset($inputs['status']) && $inputs['status'] == '1')? 1 : 0,
                    'updated_by' => authUserId()
                ];

            (new City)->store($inputs, $id);
            \DB::commit();
            $langMessage = lang('messages.updated', lang('location.city'));
            $route = route('city.index');
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
     * Used to update city active status.
     *
     * @param int $id
     * @return Response
     */
    public function cityToggle($id = null)
    {
        if (!\Request::ajax()) {
            return lang('messages.server_error');
        }

        try {
            // get the city w.r.t id
            $result = City::find($id);

            $result->update(['status' => !$result->status]);
            $response = ['status' => 1, 'data' => (int)$result->status . '.gif'];
            // return json response
            return json_encode($response);

        } catch (\Exception $exception) {
            return lang('messages.invalid_id', string_manip(lang('location.city')));
        }
    }

    /**
     * Used to load more records and render to view.
     *
     * @param Request $request
     * @param int $pageNumber
     * @return Response
     */
    public function cityPaginate(Request $request, $pageNumber = null)
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

            $data = (new City)->getCity($inputs, $start, $perPage);
            $total = (new City)->totalCity($inputs);
            $total = $total->total;
        } else {

            $data = (new City)->getCity($inputs, $start, $perPage);
            $total = (new City)->totalCity($inputs);
            $total = $total->total;
        }
        return view('city.load_data', compact('data', 'total', 'page', 'perPage', 'inputs'));
    }

    public function getCityList(Request $request)
    {
        $cities= (new City)->getCityServiceAjax($request->state_id);
        return response()->json($cities);
    }
}
