<?php 
namespace App\Http\Controllers;
/**
 * :: Size Controller ::
 * To manage size.
 *
 **/

use App\City;
use App\Country;
use App\Department;
use App\Employee;
use App\Http\Controllers\Controller;
use App\Size;
use App\State;
use Illuminate\Http\Request;

class EmployeeController extends Controller {

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{
		return view('employee.index');
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function create()
	{
		$newEmpNumber = (new Employee)->getNewEmpnumber();
		$states = (new State)->getStateService();
		$country = (new Country)->getCountryService();
		$cities = (new City)->getCityService();
		$department = (new Department)->getDepartmentService();
		return view('employee.create', compact('newEmpNumber','states','country','cities','department'));
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function store(Request $request)
	{
        $inputs  = $request->all();

		$validator = (new Employee)->validateEmployee($inputs);
		if ($validator->fails()) {
			return redirect()->route('employee.create')
				->withInput()
				->withErrors($validator);
		}
		try {
			\DB::beginTransaction();
			$inputs = $inputs + [
				'created_by' => authUserId(),
			];
				
			(new Employee)->store($inputs);
			\DB::commit();
			return redirect()->route('employee.index')
				->with('success', lang('messages.created', lang('employee.employee')));
		} catch (\Exception $exception) {
			\DB::rollBack();
			return redirect()->route('employee.create')
				->withInput()
				->with('error', lang('messages.server_error'));
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
		$result = Size::find($id);
		if (!$result) {
			abort(404);
		}

		return view('employee.edit', compact('result'));
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param int $id
	 * @return Response
	 */
	public function update(Request $request,$id = null)
	{
		$result = Size::find($id);
		if (!$result) {
			return redirect()->route('size.index')
				->with('error', lang('messages.invalid_id', string_manip(lang('size.size'))));
		}

        $inputs  = $request->all();
		$validator = (new Size)->validateSize($inputs, $id);
		if ($validator->fails()) {
			return redirect()->route('size.edit', ['id' => $id])
				->withInput()
				->withErrors($validator);
		}

		try {
			\DB::beginTransaction();
			$inputs = $inputs + [
				'status' => isset($inputs['status']) ? 1 : 0,
				'updated_by' => authUserId()
			];
			(new Size)->store($inputs, $id);
			\DB::commit();
			return redirect()->route('size.index')
				->with('success', lang('messages.updated', lang('size.size')));
		} catch (\Exception $exception) {
			\DB::rollBack();
			return redirect()->route('size.create')
				->with('error', lang('messages.server_error'));
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
	 * Used to update size active status.
	 *
	 * @param int $id
	 * @return Response
	 */
	public function sizeToggle($id = null)
	{
		if (!\Request::ajax()) {
			return lang('messages.server_error');
		}

		try {
            // get the size w.r.t id
            $result = Size::find($id);
        } catch (\Exception $exception) {
            return lang('messages.invalid_id', string_manip(lang('size.size')));
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
	public function sizePaginate(Request $request,$pageNumber = null)
	{
		if (!\Request::isMethod('post') && !\Request::ajax()) { //
			return lang('messages.server_error');
		}

        $inputs  = $request->all();
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

			$data = (new Size)->getSizes($inputs, $start, $perPage);
			$totalSize = (new Size)->totalSizes($inputs);
			$total = $totalSize->total;
		} else {
			
			$data = (new Size)->getSizes($inputs, $start, $perPage);
			$totalSize = (new Size)->totalSizes();
			$total = $totalSize->total;
		}

		return view('size.load_data', compact('data', 'total', 'page', 'perPage'));
	}
}