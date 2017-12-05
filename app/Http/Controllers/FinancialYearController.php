<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\FinancialYear;

class FinancialYearController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $t = $request->get('t');
        return view('financial_year.index', compact('t'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('financial_year.create');
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

        $validator = (new FinancialYear)->validateFinanciaYear($inputs);
        if ($validator->fails()) {
            return validationResponse(false, 206, "", "", $validator->messages());
        }
        try {
            \DB::beginTransaction();
            $fromDate = dateFormat('Y-m-d', $inputs['from_date']);
            unset($inputs['from_date']);
            $toDate = dateFormat('Y-m-d', $inputs['to_date']);
            unset($inputs['to_date']);
            $inputs['from_date'] = $fromDate;
            $inputs['to_date'] = $toDate;

            if(array_key_exists('status', $inputs)) {
                (new FinancialYear)->updateStatusAll();
            }

            //(new FinancialYear)->updateStatusAll();
            $inputs = $inputs + [
                'company_id' => loggedInCompanyId(),
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'created_by' => authUserId()
            ];
            (new FinancialYear)->store($inputs);
            \DB::commit();
            $langMessage = lang('messages.created', lang('financial_year.financial_year'));
            $route = route('financial-year.index');
            return validationResponse(true, 201, $langMessage, $route);
        } catch (\Exception $e) {
            \DB::rollBack();
            return validationResponse(false, 207, $e->getMessage().' - '.$e->getFile().' - '.$e->getLine().' - '.lang('messages.server_error'));
        }
    }
    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\FinancialYear  $financialYear
     * @return \Illuminate\Http\Response
     */
    public function edit(FinancialYear $financialYear)
    {
        $result = $financialYear;
        if (!$result) {
            abort(404);
        }
//        if($result->hospital_id != loggedInHospitalId()) {
//            abort(401);
//        }
        return view('financial_year.edit', compact('result'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\FinancialYear  $financialYear
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, FinancialYear $financialYear)
    {
        $inputs = $request->all();

        $id = $financialYear->id;

        $validator = (new FinancialYear)->validateFinanciaYear($inputs, $id);
        if ($validator->fails()) {
            return validationResponse(false, 206, "", "", $validator->messages());
        }

        try {
            $fromDate = dateFormat('Y-m-d', $inputs['from_date']);
            unset($inputs['from_date']);

            $toDate = dateFormat('Y-m-d', $inputs['to_date']);
            unset($inputs['to_date']);

            $inputs['from_date'] = $fromDate;
            $inputs['to_date'] = $toDate;

            \DB::beginTransaction();
            $inputs = $inputs + [
                'status' => (isset($inputs['status']))? 1 : 0,
                'updated_by' => authUserId()
            ];
            if($inputs['status'] == 1)
            {
                (new FinancialYear)->updateStatusAll();
            }
            (new FinancialYear)->store($inputs, $id);
            \DB::commit();
            $langMessage = lang('messages.updated', lang('financial_year.financial_year'));
            $route = route('financial-year.index');

            return validationResponse(true, 201,$langMessage, $route);
        } catch (\Exception $e) {
            \DB::rollBack();
            return validationResponse(false, 207, lang('messages.server_error'));
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\FinancialYear  $financialYear
     * @return \Illuminate\Http\Response
     */
    public function destroy(FinancialYear $financialYear)
    {
        return 'in progress';
    }

    /**
     * @param Request $request
     * @param null $pageNumber
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View|String
     */
    public function financialYearPaginate(Request $request, $pageNumber = null)
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

            $data = (new FinancialYear)->getFinancialYears($inputs, $start, $perPage);
            $totalFinancialYear = (new FinancialYear())->totalFinancialYears($inputs);
            $total = $totalFinancialYear->total;
        } else {

            $data = (new FinancialYear)->getFinancialYears($inputs, $start, $perPage);
            $totalFinancialYear = (new FinancialYear)->totalFinancialYears();
            $total = $totalFinancialYear->total;
        }
        return view('financial_year.load_data', compact('data', 'total', 'page', 'perPage', 'inputs'));
    }

    /**
     * @param null $id
     * @return string
     */
    public function financialYearToggle($id = null)
    {
        if (!\Request::ajax()) {
            return lang('messages.server_error');
        }
        try {
            /* Changing the status of the all the financial year */
            (new FinancialYear)->updateStatusAll();
            $result = FinancialYear::find($id);

            $result->update(['status' => !$result->status]);
            $response = ['status' => 1, 'data' => (int)$result->status . '.gif'];
            return json_encode($response);

        } catch (Exception $exception) {
            return lang('messages.invalid_id', string_manip(lang('financial_year.financial_year')));
        }
    }

    /**
     * @param $id
     * @return string
     */
    public function drop($id)
    {
        if (!\Request::ajax()) {
            return lang('messages.server_error');
        }
        $result = FinancialYear::find($id);

        if(!$result) {
            return validationResponse(false, 207, lang('messages.invalid_id', lang('financial_year.financial_year')));
        }
        if($result->company_id != loggedInCompanyId()) {
            return validationResponse(false, 207, lang('messages.permission_denied'));
        }
        try {
            // get the unit w.r.t id
            //$result = (new FinancialYear)->company()->find($id);
            if($result->status == 1) {
                $response = ['status' => 0, 'message' => lang('financial_year.financial_year_in_use')];
            }
            else {
                (new FinancialYear)->drop($id);
                $response = ['status' => 1, 'message' => lang('messages.deleted', lang('financial_year.financial_year'))];
            }
        } catch (Exception $exception) {
            $response = ['status' => 0, 'message' => lang('messages.server_error')];
        }
        // return json response
        return json_encode($response);
    }
}
