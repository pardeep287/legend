<?php

namespace App\Http\Controllers;

/**
 * :: Country Controller ::
 * To manage Country.
 *
 **/

use App\State;
use Illuminate\Http\Request;
use App\Currency;
use App\Country;

class CountryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        return view('country.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        $currency = (new Currency)->getCurrencyService();
        return view('country.create', compact('currency'));
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
        $validator = (new Country)->validateCountry($inputs);
        if ($validator->fails()) {
            return validationResponse(false, 206, "", "", $validator->messages());
        }
        try {
            \DB::beginTransaction();
            $currency = $inputs['currency'];
            unset($inputs['currency']);

            $inputs = $inputs + [
                /*'country_name'        => $inputs['country_name'],
                'country_digit_code'  => $inputs['country_digit_code'],
                'country_char_code'   => $inputs['country_char_code'],*/
                'currency_id'   => $currency,
                'status'        => (isset($inputs['status']) && $inputs['status'] == '1')? 1 : 0,
                'created_by'    => authUserId()
            ];

            (new Country)->store($inputs);
            \DB::commit();
            $langMessage = lang('messages.created', lang('location.country'));
            $route = route('country.index');
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
        $result = Country::find($id);
        if (!$result) {
            abort(404);
        }
        $currency = (new Currency)->getCurrencyService();
        return view('country.edit', compact('result', 'currency'));
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
        $result = Country::find($id);
        if (!$result) {
            return redirect()->route('country.index')
                ->with('error', lang('messages.invalid_id', string_manip(lang('location.country'))));
        }

        $inputs = $request->all();
        $validator = (new Country)->validateCountry($inputs, $id);
        if ($validator->fails()) {
            return validationResponse(false, 206, "", "", $validator->messages());
        }

        try {
            \DB::beginTransaction();
            $currency = $inputs['currency'];
            unset($inputs['currency']);

            $inputs = $inputs + [
                'currency_id'   => $currency,
                'status'        => (isset($inputs['status']) && $inputs['status'] == '1')? 1 : 0,
                'updated_by' => authUserId()
            ];

            (new Country)->store($inputs, $id);
            \DB::commit();
            $langMessage = lang('messages.updated', lang('location.country'));
            $route = route('country.index');
            return validationResponse(true, 201, $langMessage, $route);
        } catch (\Exception $e) {
            \DB::rollBack();
            return validationResponse(false, 207, $e->getMessage().' - '.$e->getFile().' - '.$e->getLine().' - '.lang('messages.server_error'));

        }
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function drop($id)
    {
        if (!\Request::ajax()) {
            return lang('messages.server_error');
        }
        try {
            $data = (new Country)->getCountries([], '', '', $id);
            if(!$data){
                return json_encode(['status' => 0, 'message' => lang('messages.no_data_found', lang('location.country'))]);
            }
            $findStates = (new State)->findStates(['country_id' => $id]);
            
            if(count($findStates) > 0) {
                return json_encode(['status' => 0, 'message' => lang('common.in_use', lang('location.country'))]);
            }
            \DB::beginTransaction();

            (new Country)->drop($id);

            \DB::commit();
            return json_encode(['status' => 1, 'message' => lang('messages.deleted', lang('location.country'))]);
        } catch (\Exception $e) {
            \DB::rollBack();
            return json_encode(['status' => 0, 'message' => lang('messages.server_error')]);
        }
    }

    /**
     * Used to update country active status.
     *
     * @param int $id
     * @return Response
     */
    public function countryToggle($id = null)
    {
        if (!\Request::ajax()) {
            return lang('messages.server_error');
        }

        try {
            // get the country w.r.t id
            $result = Country::find($id);

            $result->update(['status' => !$result->status]);
            $response = ['status' => 1, 'data' => (int)$result->status . '.gif'];
            // return json response
            return json_encode($response);

        } catch (\Exception $exception) {
            return lang('messages.invalid_id', string_manip(lang('location.country')));
        }
    }

    /**
     * Used to load more records and render to view.
     *
     * @param Request $request
     * @param int $pageNumber
     * @return Response
     */
    public function countryPaginate(Request $request, $pageNumber = null)
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

            $data = (new Country)->getCountries($inputs, $start, $perPage);
            $total = (new Country)->totalCountries($inputs);
            $total = $total->total;
        } else {

            $data = (new Country)->getCountries($inputs, $start, $perPage);
            $total = (new Country)->totalCountries($inputs);
            $total = $total->total;
        }
        return view('country.load_data', compact('data', 'total', 'page', 'perPage', 'inputs'));
    }
}
