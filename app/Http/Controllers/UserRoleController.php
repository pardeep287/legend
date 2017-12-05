<?php 
namespace App\Http\Controllers;
/**
 * :: UserRole Controller ::
 * To manage UserRoles.
 *
 **/

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\UserRoles;

class UserRoleController extends Controller {

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{
		return view('role.index');
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function create()
	{
		return view('role.create');
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
		$validator = (new UserRoles)->validateRole($inputs);
		if ($validator->fails()) {
            return validationResponse(false, 206, "", "", $validator->messages());
		}
		try {
			\DB::beginTransaction();
			$inputs = $inputs + [
				'created_by' => authUserId(),
				'company_id' => loggedInCompanyId()
			];
			//dd($inputs);
			(new UserRoles)->store($inputs);
			\DB::commit();
            $langMessage = lang('messages.created', lang('role.role'));
            $route = route('user-roles.index');
            return validationResponse(true, 201, $langMessage, $route);
		} catch (\Exception $exception) {
			\DB::rollBack();
            return validationResponse(false, 207, $exception->getMessage().' - '.lang('messages.server_error'));
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
		$role = (new UserRoles)->company()->find($id);
		if (!$role) {
			abort(401);
		}

		if ($role->isdefault == 1) {
            $langMessage = lang('messages.isdefault', string_manip(lang('role.role')));
			return redirect()->route('user-roles.index')
				->with('error', $langMessage);
		}

		return view('role.edit', compact('role'));
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
		$role = (new UserRoles)->company()->find($id);
		if (!$role) {
            $langMessage = lang('messages.invalid_id', string_manip(lang('role.role')));
            $route = route('user-roles.index');
            return validationResponse(false, 207, $langMessage, $route);
		}

		$inputs = $request->all();
		$validator = (new UserRoles)->validateRole($inputs, $id);
		if ($validator->fails()) {
            return validationResponse(false, 206, "", "", $validator->messages());
		}

		try {
			\DB::beginTransaction();
			$inputs = $inputs + [
				'updated_by' => authUserId()
			];
			(new UserRoles)->store($inputs, $id);
			\DB::commit();
            $langMessage = lang('messages.updated', lang('role.role'));
            $route = route('user-roles.index');
            return validationResponse(true, 201,$langMessage, $route);
		} catch (\Exception $exception) {
			\DB::rollBack();
            return validationResponse(false, 207, $exception->getMessage().' - '.lang('messages.server_error'));
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
	 * Used to update role active status.
	 *
	 * @param int $id
	 * @return Response
	 */
	public function roleToggle($id = null)
	{
		if (!\Request::ajax()) {
			return lang('messages.server_error');
		}
		try {
			$role = (new UserRoles)->company()->find($id);

            $role->update(['status' => !$role->status]);
            $response = ['status' => 1, 'data' => (int)$role->status . '.gif'];
            return json_encode($response);

        } catch (\Exception $exception) {
            return lang('messages.invalid_id', string_manip(lang('role.role')));
        }
	}

    /**
     * Used to load more records and render to view.
     *
     * @param Request $request
     * @param int $pageNumber
     * @return Response
     */
	public function rolePaginate(Request $request, $pageNumber = null)
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

			$data = (new UserRoles)->getRoles($inputs, $start, $perPage);
			$totalRole = (new UserRoles)->totalRoles($inputs);
			$total = $totalRole->total;
		} else {
			
			$data = (new UserRoles)->getRoles($inputs, $start, $perPage);
			$totalRole = (new UserRoles)->totalRoles();
			$total = $totalRole->total;
		}

		return view('role.load_data', compact('data', 'total', 'page', 'perPage'));
	}
}