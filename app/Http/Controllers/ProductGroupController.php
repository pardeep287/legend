<?php
namespace App\Http\Controllers;
/**
 * :: Product Group Controller ::
 * To manage product group.
 *
 **/

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\ProductGroup;
use App\Product;
use Maatwebsite\Excel\Facades\Excel;

class ProductGroupController extends Controller {

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{
		return view('product-group.index');
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function create()
	{
		return view('product-group.create');
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function store(Request $request)
	{
		$inputs  = $request->all();
		$validator = (new ProductGroup)->validateProductGroup($inputs);
		if ($validator->fails()) {

			return validationResponse(false, 206, "", "", $validator->messages());
		}
		try {
			\DB::beginTransaction();
			$inputs = $inputs + [
					'created_by' => authUserId(),
					'company_id' => loggedInCompanyId()
				];
			$id = (new ProductGroup)->store($inputs);
			$submitData = [];
			if(!empty($inputs['isAjax'])) {
				$submitData = ['id' => $id, 'name' => $inputs['name']];
			}
			$route = route('product-group.index');
			$lang = lang('messages.created', lang('product_group.product_group'));
			\DB::commit();
			return validationResponse(true, 201, $lang, $route, [], $submitData);

		} catch (\Exception $exception) {
			\DB::rollBack();
			return validationResponse(false, 207, $exception->getMessage().lang('messages.server_error'));
		}
	}

	/**
	 * Show the form for editing the specified resource.
	 * @param int $id
	 * @return Response
	 */
	public function edit($id = null)
	{
		$result = (new ProductGroup)->company()->find($id);
		if (!$result) {
			abort(401);
		}

		return view('product-group.edit', compact('result'));
	}

	/**
	 * Update the specified resource in storage.
	 * @param int $id
	 * @return Response
	 */
	public function update(Request $request,$id = null)
	{
		$result = (new ProductGroup)->company()->find($id);
		if (!$result) {

			$route = route('product-group.index');
			$lang = lang('messages.invalid_id', string_manip(lang('product_group.product_group')));
			return validationResponse(false, 206, $lang, $route);
		}

		$inputs  = $request->all();

		if(!array_key_exists('status', $inputs)) {
			$inputs = $inputs + [ 'status' => 0 ];
		}

		$validator = (new ProductGroup)->validateProductGroup($inputs, $id);
		if ($validator->fails()) {
			return validationResponse(false, 206, "", "", $validator->messages());
		}

		try {
			\DB::beginTransaction();
			$inputs = $inputs + [
					'updated_by' => authUserId()
				];
			(new ProductGroup)->store($inputs, $id);
			\DB::commit();

			$route = route('product-group.index');
			$lang = lang('messages.updated', lang('product_group.product_group'));
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
			$check =  (new product)->productGroupExists($id);
			if(!$check) {
				(new ProductGroup)->drop($id);
				$response = ['status' => 1, 'message' => lang('messages.deleted', lang('product_group.product_group'))];
			}
			else
				$response = ['status' => 1, 'message' => lang('product_group.product_group_in_use')];

		} catch (Exception $exception) {
			$response = ['status' => 0, 'message' => lang('messages.server_error')];
		}

		return json_encode($response);
	}
	/**
	 * Used to update product-group active status.
	 * @param int $id
	 * @return Response
	 */
	public function productGroupToggle($id = null)
	{
		if (!\Request::ajax()) {
			return lang('messages.server_error');
		}

		try {
			$result  = (new ProductGroup)->company()->find($id);
		}
		catch (Exception $exception) {
			return lang('messages.invalid_id', string_manip(lang('product_group.product_group')));
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
	public function productGroupPaginate(Request $request,$pageNumber = null)
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

			$data = (new ProductGroup)->getProductGroups($inputs, $start, $perPage);
			$totalProductGroup = (new ProductGroup)->totalProductGroups($inputs);
			$total = $totalProductGroup->total;
		} else {

			$data = (new ProductGroup)->getProductGroups($inputs, $start, $perPage);
			$totalProductGroup = (new ProductGroup)->totalProductGroups();
			$total = $totalProductGroup->total;
		}

		return view('product-group.load_data', compact('data', 'total', 'page', 'perPage', 'inputs'));
	}
	/**
	 * Submit Modal Form
	 * @return Success Or Failure
	 */
	public function productGroupModal()
	{
		$isAjax = 1;
		return view('product-group.product_group_modal', compact('isAjax'));
	}

	/**
	 * Get 10 Hsn Code Lists
	 * @return Success Or Failure
	 */
	public function productGroupLists()
	{
		$groups = (new ProductGroup)->getProductGroupService();
		return response()->json($groups);
	}

	/**
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\JsonResponse|\Illuminate\View\View
	 */
	public function uploadExcel()
	{
		$data = [];
		$inputs = \Input::all();
		if (count($inputs) > 0) {
			$validator = (new ProductGroup)->validateProductGroupCodeExcel($inputs);
			if ($validator->fails()) {
				return validationResponse(false, 206, "", "", $validator->messages());
			}
			ini_set('memory_limit', '-1');
			Excel::load($inputs['file'], function ($reader) {
				$i = 1;
				try {
					$reader->each(function($sheet) {
						\DB::beginTransaction();
						$name  = trim($sheet->group_name);
						$hsnCodes = (new ProductGroup)->getProductGroup(['name' => trim($name)], false);
						$message = '';
						if(count($hsnCodes) > 0){
							$message = 'The HSN Code has already been taken. ';
						}

						echo $message . ' ---- ' . $name;

						if($message == '') {
							$data = [
								'name'   => $name,
								'code'   => $sheet->code,
								'status'     => 1,
								'created_by' => authUserId(),
								'company_id' => loggedInCompanyId(),
							];
							//(new ProductGroup)->store($data);
						}
						\DB::commit();
					});
				}
				catch (\Exception $e) {
					\DB::rollBack();
					return redirect()->back()->with('error', lang('messages.server_error'));

				}
			});
			//$route = route('products.index');
			$lang = lang('hsn_code.hsn_code_imported');
			return validationResponse(true, 201, $lang);
		}
		return view('hsn-code.upload_excel', compact('banks'));
	}
}