<?php
namespace App\Http\Controllers;
/**
 * :: Unit Controller ::
 * To manage unit.
 *
 **/

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Unit;

class UnitController extends Controller {
	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{
		return view('unit.index');
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function create()
	{
		return view('unit.create');
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function store(Request $request)
	{
		$inputs = $request->all();
		$validator = (new Unit)->validateUnit($inputs);
		if ($validator->fails()) {
			return validationResponse(false, 206, "", "", $validator->messages());

		}
		try {
			\DB::beginTransaction();

			if( !array_key_exists('status', $inputs) ) {
				$inputs = $inputs + [ 'status' =>  0];
			}

			$inputs = $inputs + [
					'created_by' => authUserId(),
					'company_id' => loggedInCompanyId()
				];
			$id = (new Unit)->store($inputs);
			$submitData = [];
			if(!empty($inputs['isAjax'])) {
				$submitData = ['id' => $id, 'name' => $inputs['name']];
			}
			$route = route('unit.index');
			$lang = lang('messages.created', lang('unit.unit'));
			\DB::commit();
			return validationResponse(true, 201, $lang, $route, [], $submitData);

		} catch (\Exception $exception) {
			\DB::rollBack();
			return validationResponse(false, 207, lang('messages.server_error'));
		}
	}

	/**
	 * Show the form for editing the specified resource.
	 * @param int $id
	 * @return Response
	 */
	public function edit($id = null)
	{
		$result = (new Unit)->company()->find($id);
		if (!$result) {
			abort(401);
		}

		return view('unit.edit', compact('result'));
	}

	/**
	 * Update the specified resource in storage.
	 * @param int $id
	 * @return Response
	 */
	public function update(Request $request, $id = null)
	{
		$result = (new Unit)->company()->find($id);
		if (!$result) {
			abort(401);
		}

		$result = (new Unit)->company()->find($id);
		if (!$result) {
			return redirect()->route('unit.index')
				->with('error', lang('messages.invalid_id', string_manip(lang('unit.unit'))));
		}

		$inputs = $request->all();
		$validator = (new Unit)->validateUnit($inputs, $id);
		if ($validator->fails()) {

			return validationResponse(false, 206, "", "", $validator->messages());
		}

		try {
			\DB::beginTransaction();
			$inputs = $inputs + [
					'status' => (isset($inputs['status'])) ? 1 : 0,
					'updated_by' => authUserId()
				];
			(new Unit)->store($inputs, $id);
			\DB::commit();
			$route = route('unit.index');
			$lang = lang('messages.updated', lang('unit.unit'));
			return validationResponse(true, 201, $lang, $route);

		} catch (\Exception $exception) {
			\DB::rollBack();

			return validationResponse(false, 207, lang('messages.server_error'));
		}
	}

	/**
	 * Remove the specified resource from storage.
	 * @param int $id
	 * @return Response
	 */
	public function drop($id)
	{
		if (!\Request::ajax()) {
			return lang('messages.server_error');
		}

		try {
			$check = (new Unit)->unitExists($id);
			if($check) {
				$response = ['status' => 1, 'message' => lang('unit.unit_in_use')];
			}
			else {
				(new Unit)->drop($id);
				$response = ['status' => 1, 'message' => lang('messages.deleted', lang('unit.unit'))];
			}

		} catch (Exception $exception) {
			$response = ['status' => 0, 'message' => lang('messages.server_error')];
		}

		return json_encode($response);
	}

	/**
	 * Used to update unit active status.
	 * @param int $id
	 * @return Response
	 */
	public function unitToggle($id = null)
	{
		if (!\Request::ajax()) {
			return lang('messages.server_error');
		}

		try {

			$result = Unit::find($id);
		} catch (Exception $exception) {
			return lang('messages.invalid_id', string_manip(lang('unit.unit')));
		}

		$result->update(['status' => !$result->status]);
		$response = ['status' => 1, 'data' => (int)$result->status . '.gif'];

		return json_encode($response);
	}

	/**
	 * Used to load more records and render to view.
	 * @param int $pageNumber
	 * @return Response
	 */
	public function unitPaginate(Request $request, $pageNumber = null)
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

			$data = (new Unit)->getUnits($inputs, $start, $perPage);
			$totalUnit = (new Unit)->totalUnits($inputs);
			$total = $totalUnit->total;
		}

		else {

			$data = (new Unit)->getUnits($inputs, $start, $perPage);
			$totalUnit = (new Unit)->totalUnits();
			$total = $totalUnit->total;
		}

		return view('unit.load_data', compact('data', 'total', 'page', 'perPage', 'inputs'));
	}
	/**
	 * Submit Modal Form
	 * @return Success Or Failure
	 */
	public function unitModal()
	{
		$isAjax = 1;
		return view('unit.unit_modal', compact('isAjax'));
	}

	/**
	 * Get 10 Hsn Code Lists
	 * @return Success Or Failure
	 */
	public function unitLists()
	{
		$unit = (new Unit)->getUnitService();
		return response()->json($unit);
	}

}