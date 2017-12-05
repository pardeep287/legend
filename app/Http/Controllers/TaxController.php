<?php 
namespace App\Http\Controllers;
/**
 * :: Tax Controller ::
 * To manage tax.
 *
 **/

use App\TaxRates;
use App\Http\Controllers\Controller;
use App\Tax;
use App\Product;
use Illuminate\Http\Request;

class TaxController extends Controller {

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{
		return view('tax.index');
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function create()
	{
		return view('tax.create');
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function store(Request $request)
	{
		$inputs = $request->all();
		$validator = (new Tax)->validateTax($inputs);
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
			(new Tax)->store($inputs);
			\DB::commit();
			$route = route('tax.index');
			$lang = lang('messages.created', lang('tax.tax'));
			return validationResponse(true, 201, $lang, $route);
		} catch (\Exception $exception) {
			\DB::rollBack();
			return validationResponse(false, 207, $exception->getMessage() . $exception->getFile() . $exception->getLine() . lang('messages.server_error'));
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
		$result = (new Tax)->company()->find($id);
		if (!$result) {
			abort(401);
		}
		$cgst = $sgst = $igst = 0;
		$cgstId = $sgstId = $igstId = null;
		$rates = (new TaxRates)->getEffectedTax(true, ['tax' => $result->id]);

		if($rates) {
			$cgst = $rates->cgst_rate;
			$sgst = $rates->sgst_rate;
			$igst = $rates->igst_rate;

			$cgstId = $rates->cgst_account_id;
			$sgstId = $rates->sgst_account_id;
			$igstId = $rates->igst_account_id;
		}
		return view('tax.edit', compact('result', 'cgst', 'sgst', 'igst', 'cgstId', 'sgstId', 'igstId'));
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param int $id
	 * @return Response
	 */
	public function update(Request $request, $id = null)
	{
		$result = (new Tax)->company()->find($id);
		if (!$result) {
			$route = route('tax.index');
			$lang = lang('messages.invalid_id', string_manip(lang('tax.tax')));
			return validationResponse(false, 206, $lang, $route);
		}

		$isInUse = (new Product)->getFilteredProduct(['tax' => $id]);
		if($isInUse) {
			$lang = lang('tax.tax_is_in_use');
			return validationResponse(false, 207, $lang);
		}

		$inputs = $request->all();
		$validator = (new Tax)->validateTax($inputs, $id);
		if ($validator->fails()) {
			return validationResponse(false, 206, "", "", $validator->messages());
		}

		try {
			\DB::beginTransaction();
			$inputs = $inputs + [
				'company_id' => loggedInCompanyId(),
				'updated_by' => authUserId()
			];
			//$inputs['wef'] = convertToUtc();
			//dd($inputs);
			(new Tax)->store($inputs, $id);
			\DB::commit();
			$route = route('tax.index');
			$lang = lang('messages.updated', lang('tax.tax'));
			return validationResponse(true, 201, $lang, $route);
		} catch (\Exception $exception) {
			\DB::rollBack();
			return validationResponse(false, 207, $exception->getMessage().lang('messages.server_error'));
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
	 * Used to update tax active status.
	 *
	 * @param int $id
	 * @return Response
	 */
	public function taxToggle($id = null)
	{
		if (!\Request::ajax()) {
			return lang('messages.server_error');
		}

		try {
            // get the tax w.r.t id
			$result = (new Tax)->company()->find($id);
        } catch (\Exception $exception) {
            return lang('messages.invalid_id', string_manip(lang('tax.tax')));
        }

		$result->update(['status' => !$result->status]);
        $response = ['status' => 1, 'data' => (int)$result->status . '.gif'];
        // return json response
        return json_encode($response);
	}

	/**
	 * Used to load more records and render to view.
	 *
	 * @param int $pageNumber
	 *
	 * @return Response
	 */
	public function taxPaginate(Request $request, $pageNumber = null)
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

			$data = (new Tax)->getTaxes($inputs, $start, $perPage);
			$totalTax = (new Tax)->totalTaxes($inputs);
			$total = $totalTax->total;
		} else {

			$data = (new Tax)->getTaxes($inputs, $start, $perPage);
			$totalTax = (new Tax)->totalTaxes($inputs);
			$total = $totalTax->total;
		}

		return view('tax.load_data', compact('data', 'total', 'page', 'perPage'));
	}
}