<?php 
namespace App\Http\Controllers;

/**
 * :: Currency Controller ::
 * To manage currency.
 *
 **/

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use App\Currency;
use App\CurrencyRates;

class CurrencyController extends Controller {

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{
		return view('currency.index');
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function create()
	{
		return view('currency.create');
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function store()
	{
		$inputs = Input::all();
		$validator = (new Currency)->validateCurrency($inputs);
		if ($validator->fails()) {
            return validationResponse(false, 206, "", "", $validator->messages());
		}
		try {
			\DB::beginTransaction();
			$inputs = $inputs + [
				'created_by' => authUserId(),
				'company_id' => loggedInCompanyId()
			];

			$inputs['wef'] = convertToUtc();
			(new Currency)->store($inputs);
			\DB::commit();
            $langMessage = lang('messages.created', lang('currency.currency'));
            $route = route('currency.index');
            return validationResponse(true, 201, $langMessage, $route);
		} catch (\Exception $exception) {
			\DB::rollBack();
            return validationResponse(false, 207, lang('messages.server_error'));
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
		$result = Currency::find($id);
		if (!$result) {
			abort(404);
		}

		$rate = 0;
		$rates = (new CurrencyRates)->getEffectedCurrency(true, ['currency' => $result->id]);
		if($rates) {
			$rate = $rates->rate;
		}
		return view('currency.edit', compact('result', 'rate'));
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param int $id
	 * @return Response
	 */
	public function update($id = null)
	{
		$result = Currency::find($id);
		if (!$result) {
			return redirect()->route('currency.index')
				->with('error', lang('messages.invalid_id', string_manip(lang('currency.currency'))));
		}

		$inputs = Input::all();
		$validator = (new Currency)->validateCurrency($inputs, $id);
		if ($validator->fails()) {
            return validationResponse(false, 206, "", "", $validator->messages());
		}

		try {
			\DB::beginTransaction();
			$inputs = $inputs + [
				'updated_by' => authUserId()
			];
			$inputs['wef'] = convertToUtc();
			(new Currency)->store($inputs, $id);
			\DB::commit();
            $langMessage = lang('messages.updated', lang('currency.currency'));
            $route = route('currency.index');
            return validationResponse(true, 201, $langMessage, $route);
		} catch (\Exception $exception) {
			\DB::rollBack();
            return validationResponse(false, 207, lang('messages.server_error'));
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
	 * Used to update currency active status.
	 *
	 * @param int $id
	 * @return Response
	 */
	public function currencyToggle($id = null)
	{
		if (!\Request::ajax()) {
			return lang('messages.server_error');
		}

		try {
            // get the currency w.r.t id
            $result = Currency::find($id);

            $result->update(['status' => !$result->status]);
            $response = ['status' => 1, 'data' => (int)$result->status . '.gif'];
            // return json response
            return json_encode($response);
        } catch (\Exception $exception) {
            return lang('messages.invalid_id', string_manip(lang('currency.currency')));
        }
	}

	/**
	 * Used to load more records and render to view.
	 *
	 * @param int $pageNumber
	 *
	 * @return Response
	 */
	public function currencyPaginate($pageNumber = null)
	{
		if (!\Request::isMethod('post') && !\Request::ajax()) { //
			return lang('messages.server_error');
		}

		$inputs = Input::all();
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

			$data = (new Currency)->getCurrencies($inputs, $start, $perPage);
			$totalCurrency = (new Currency)->totalCurrencies($inputs);
			$total = $totalCurrency->total;
		} else {
			
			$data = (new Currency)->getCurrencies($inputs, $start, $perPage);
			$totalCurrency = (new Currency)->totalCurrencies();
			$total = $totalCurrency->total;
		}

		return view('currency.load_data', compact('data', 'total', 'page', 'perPage'));
	}
}