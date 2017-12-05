<?php 
namespace App\Http\Controllers;
/**
 * :: Product Type Controller ::
 * To manage product types.
 *
 **/

use App\Http\Controllers\Controller;
use App\ProductType;
use Illuminate\Http\Request;

class ProductTypeController extends Controller {

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{
		return view('product-type.index');
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function create()
	{
		return view('product-type.create');
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function store(Request $request)
	{
		$inputs  = $request->all();
		$validator = (new ProductType)->validateProductType($inputs);
		if ($validator->fails()) {
			return redirect()->route('product-type.create')
				->withInput()
				->withErrors($validator);
		}
		try {
			\DB::beginTransaction();
			$inputs = $inputs + [
				'created_by' => authUserId(),
				'company_id' => loggedInCompanyId()
			];
			(new ProductType)->store($inputs);
			\DB::commit();
			return redirect()->route('product-type.index')
				->with('success', lang('messages.created', lang('product_type.product_type')));
		} catch (\Exception $exception) {
			\DB::rollBack();
			return redirect()->route('product-type.create')
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
		$result = ProductType::find($id);
		if (!$result) {
			abort(404);
		}

		return view('product-type.edit', compact('result'));
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param int $id
	 * @return Response
	 */
	public function update(Request $request,$id = null)
	{
		$result = ProductType::find($id);
		if (!$result) {
			return redirect()->route('product-type.index')
				->with('error', lang('messages.invalid_id', string_manip(lang('product_type.product_type'))));
		}

		$inputs  = $request->all();
		$validator = (new ProductType)->validateProductType($inputs, $id);
		if ($validator->fails()) {
			return redirect()->route('product-type.edit', ['id' => $id])
				->withInput()
				->withErrors($validator);
		}

		try {
			\DB::beginTransaction();
			$inputs = $inputs + [
				'updated_by' => authUserId()
			];
			(new ProductType)->store($inputs, $id);
			\DB::commit();
			return redirect()->route('product-type.index')
				->with('success', lang('messages.updated', lang('product_type.product_type')));
		} catch (\Exception $exception) {
			\DB::rollBack();
			return redirect()->route('product-type.create')
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
	 * Used to update product-type active status.
	 *
	 * @param int $id
	 * @return Response
	 */
	public function productTypeToggle($id = null)
	{
		if (!\Request::ajax()) {
			return lang('messages.server_error');
		}

		try {
            // get the product-type w.r.t id
            $result = ProductType::find($id);
        } catch (Exception $exception) {
            return lang('messages.invalid_id', string_manip(lang('product_type.product_type')));
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
	public function productTypePaginate(Request $request,$pageNumber = null)
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

			$data = (new ProductType)->getProductTypes($inputs, $start, $perPage);
			$totalProductType = (new ProductType)->totalProductTypes($inputs);
			$total = $totalProductType->total;
		} else {
			
			$data = (new ProductType)->getProductTypes($inputs, $start, $perPage);
			$totalProductType = (new ProductType)->totalProductTypes();
			$total = $totalProductType->total;
		}

		return view('product-type.load_data', compact('data', 'total', 'page', 'perPage'));
	}
}