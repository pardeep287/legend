<?php 
namespace App\Http\Controllers;
/**
 * :: Account Group Controller ::
 * To manage account group.
 *
 **/

use App\Http\Controllers\Controller;
use App\AccountGroup;
use Illuminate\Support\Facades\Input;
use Illuminate\Http\Request;

class AccountGroupController extends Controller {

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{
		return view('account-group.index');
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function create()
	{
		$accountGroup = (new AccountGroup)->getAccountGroupService();

		return view('account-group.create',compact('accountGroup'));
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function store(Request $request)
	{
		$inputs = $request->all();

		$validator = (new AccountGroup)->validateAccountGroup($inputs);
		if ($validator->fails()) {
			return validationResponse(false, 206, "", "", $validator->messages());
		}

		try {
			\DB::beginTransaction();
			//dd($inputs);

			$parentGroup = $inputs['parent_group'];
			unset($inputs['parent_group']);
			$primary = 0;
			if(array_key_exists('primary', $inputs)){
				$primary = $inputs['primary'];
				$parentGroup = null;
				unset($inputs['primary']);
			}
			$default = 0;
			if(array_key_exists('is_default', $inputs)){
				$default = $inputs['is_default'];
				unset($inputs['is_default']);
			}

			$inputs = $inputs + [
				'is_default' => $default,
				//'is_primary' => $primary,
				'parent_group_id' => $parentGroup,
				'created_by' => authUserId(),
				'company_id' => loggedInCompanyId()
			];

			//dd($inputs);
			(new AccountGroup)->store($inputs);
			\DB::commit();
			$route = route('account-group.index');
			$lang = lang('messages.created', lang('account_group.account_group'));
			return validationResponse(true, 201, $lang, $route, [], []);
		} catch (\Exception $exception) {
			\DB::rollBack();
			return validationResponse(false, 207,$exception->getMessage(). lang('messages.server_error'));
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
		$result = AccountGroup::find($id);
		
		if (!$result) {
			abort(404);
		}
		if ($result->is_default == 1) {
			return redirect()->route('account-group.index')
				->with('error', lang('messages.isdefault', string_manip(lang('account_group.account_group'))));
		}
		$accountGroup = (new AccountGroup)->getAccountGroupService();
		return view('account-group.edit', compact('result', 'accountGroup'));
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param int $id
	 * @return Response
	 */
	public function update(Request $request, $id = null)
	{
		$result = AccountGroup::find($id);
		if (!$result) {
			return validationResponse(false, 206, "", "", lang('messages.invalid_id'));
		}

		$inputs = $request->all();
		$validator = (new AccountGroup)->validateAccountGroup($inputs, $id);
		if ($validator->fails()) {
			return validationResponse(false, 206, "", "", $validator->messages());
		}

		try {
			\DB::beginTransaction();
			$parentGroup = $inputs['parent_group'];
			unset($inputs['parent_group']);
			$primary = 0;
			if(array_key_exists('primary', $inputs)){
				$primary = $inputs['primary'];
				$parentGroup = null;
				unset($inputs['primary']);
			}
			$default = 0;
			if(array_key_exists('is_default', $inputs)){
				$default = $inputs['is_default'];
				unset($inputs['is_default']);
			}
			$inputs = $inputs + [
					'is_default' => $default,
					//'is_primary' => $primary,
					'parent_group_id' => $parentGroup,
					'updated_by' => authUserId()
				];
			//dd($inputs);
			(new AccountGroup)->store($inputs, $id);
			\DB::commit();
			$route = route('account-group.index');
			$lang = lang('messages.updated', lang('account_group.account_group'));
			return validationResponse(true, 201, $lang, $route, [], []);
		} catch (\Exception $exception) {
			\DB::rollBack();
			return validationResponse(false, 207, $exception->getMessage() .lang('messages.server_error'));
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
		if (!\Request::ajax()) {
			return lang('messages.server_error');
		}

		try {
			$account = AccountGroup::find($id);
			if ($account->is_default == 1) {
				return redirect()->route('account-group.index')
					->with('error', lang('messages.isdefault', string_manip(lang('account_group.account_group'))));
			}
			if(!$account){
				$response = ['status' => 1, 'message' => lang('messages.not_found')];
			}
			else
			{
				(new AccountGroup)->drop($id);
				$response = ['status' => 1, 'message' => lang('messages.deleted', lang('account_group.account_group'))];
			}
		} catch (Exception $exception) {
			$response = ['status' => 0, 'message' =>$exception. ' -'.lang('messages.server_error')];
		}
		return json_encode($response);
	}

	/**
	 * Used to update account group active status.
	 *
	 * @param int $id
	 * @return Response
	 */
	public function accountGroupToggle($id = null)
	{
		if (!\Request::ajax()) {
			return lang('messages.server_error');
		}

		try {
            // get the account-group w.r.t id
            $result = AccountGroup::find($id);
        } catch (\Exception $exception) {
            return lang('messages.invalid_id', string_manip(lang('account_group.account_group')));
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
	public function accountGroupPaginate(Request $request,$pageNumber = null)
	{
		if (!\Request::isMethod('post') && !\Request::ajax()) { //
			return lang('messages.server_error');
		}

		//$inputs =  Input::all();

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

			$data = (new AccountGroup)->getAccountGroups($inputs, $start, $perPage);
			$totalAccountGroup = (new AccountGroup)->totalAccountGroups($inputs);
			$total = $totalAccountGroup->total;
		} else {
			
			$data = (new AccountGroup)->getAccountGroups($inputs, $start, $perPage);
			//dd($data);
			$totalAccountGroup = (new AccountGroup)->totalAccountGroups();
			$total = $totalAccountGroup->total;
		}

		return view('account-group.load_data', compact('data', 'total', 'page', 'perPage'));
	}
}