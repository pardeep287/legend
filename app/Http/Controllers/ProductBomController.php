<?php 
namespace App\Http\Controllers;
/**
 * :: Product Bom Controller ::
 * To manage product bom.
 *
 **/

use App\Http\Controllers\Controller;
use App\ProductBom;
use App\Product;

class ProductBomController extends Controller {

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{
		return view('product-bom.index');
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function create()
	{
		$products = (new Product)->getProductsService(['type_id' => [4]]);
		$rawProduct = (new Product)->getProductsService(['type_id' => [1, 5]]);
		return view('product-bom.create', compact('products', 'rawProduct'));
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function store()
	{
		$inputs = \Input::except('raw_product', 'raw_size', 'bom_id', 'quantity');
        $data = \Input::all();
        //dd($data);
		$validator = (new ProductBom)->validateProductBom($inputs);
		if ($validator->fails()) {
			return redirect()->route('product-bom.create')
				->withInput($inputs)
				->withErrors($validator);
		}

		try {
			\DB::beginTransaction();
			$save = [];
            if (isset($data['raw_product']) && is_array($data['raw_product'])) {
                $names = array_filter($data['raw_product']);
                if (count($names) == 0) {
                    return redirect()->route('products.edit', ['id' => $id, 'tab' => $tab])
                        ->with('error', lang('messages.attach_atleast_one_product_size'));
                }

                foreach($names as $key => $detail) {
                    if ($data['raw_product'][$key] != "" && $data['raw_size'][$key] != "" && $data['quantity'][$key] != "") {
                        $quantity = $data['quantity'][$key];
                        $product = $data['raw_product'][$key];
                        $size = $data['size'];
                        $id = $data['product'];
                        $rawSize = $data['raw_size'][$key];
                        $bomId = (isset($data['bom_id'][$key])) ? $data['bom_id'][$key] : null;
                        if ($bomId > 0) {
                            $update = [
                                'parent_product_id' => $id,
                                'parent_size_id' => $size,
                                'raw_product_id' => $product,
                                'raw_size_id' => $rawSize,
                                'quantity' => $quantity,
                                'created_at' => convertToUtc(),
                                'updated_at' => convertToUtc(),
                            ];
                            (new ProductBom)->store($update, $bomId);
                        } else {
                            $save[] = [
                                'parent_product_id' => $id,
                                'parent_size_id' => $size,
                                'raw_product_id' => $product,
                                'raw_size_id' => $rawSize,
                                'quantity' => $quantity,
                                'created_at' => convertToUtc(),
                                'updated_at' => convertToUtc(),
                            ];
                        }
                    }
                }
                if(count($save) > 0) {
                    (new ProductBom)->store($save);
                }
            }
			\DB::commit();
			if(isset($inputs['save'])) {
				return redirect()->route('product-bom.index')
					->with('success', lang('messages.created', lang('product_bom.product_bom')));
			}
			return redirect()->route('product-bom.edit', $id)
					->with('success', lang('messages.updated', lang('product_bom.product_bom')));
		} catch (\Exception $exception) {
			\DB::rollBack();
			return redirect()->route('product-bom.create')
				->withInput($inputs)
				->with('error', $exception->getMessage() . lang('messages.server_error'));
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
		$result = ProductBom::where('parent_product_id', $id)->get();
		if (!$result) {
			abort(404);
		}
		$result = $result->toArray();
		$products = (new Product)->getProductsService(['type_id' => [4]]);
		$rawProduct = (new Product)->getProductsService(['type_id' => [1, 5]]);

		//dd($result);
		return view('product-bom.edit', compact('result', 'products', 'rawProduct'));
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param int $id
	 * @return Response
	 */
	public function update($id = null)
	{
		$result = ProductBom::find($id);
		if (!$result) {
			return redirect()->route('product-bom.index')
				->with('error', lang('messages.invalid_id', string_manip(lang('product_bom.product_bom'))));
		}

		$inputs = \Input::all();
		$validator = (new ProductBom)->validateProductBom($inputs, $id);
		if ($validator->fails()) {
			return redirect()->route('product-bom.edit', ['id' => $id])
				->withInput()
				->withErrors($validator);
		}

		try {
			\DB::beginTransaction();
			$inputs = $inputs + [
				'updated_by' => authUserId()
			];
			(new ProductBom)->store($inputs, $id);
			\DB::commit();
			return redirect()->route('product-bom.index')
				->with('success', lang('messages.updated', lang('product_bom.product_bom')));
		} catch (\Exception $exception) {
			\DB::rollBack();
			return redirect()->route('product-bom.create')
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
	 * Used to update product-bom active status.
	 *
	 * @param int $id
	 * @return Response
	 */
	public function productBomToggle($id = null)
	{
		if (!\Request::ajax()) {
			return lang('messages.server_error');
		}

		try {
            // get the product-bom w.r.t id
            $result = ProductBom::find($id);
        } catch (Exception $exception) {
            return lang('messages.invalid_id', string_manip(lang('product_bom.product_bom')));
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
	public function productBomPaginate($pageNumber = null)
	{
		if (!\Request::isMethod('post') && !\Request::ajax()) { //
			return lang('messages.server_error');
		}

		$inputs = \Input::all();
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
			$data = (new ProductBom)->getProductBom($inputs, $start, $perPage);
			$total = (new ProductBom)->totalProductBom($inputs);
		} else {
			$data = (new ProductBom)->getProductBom($inputs, $start, $perPage);
			$total = (new ProductBom)->totalProductBom();
		}

		return view('product-bom.load_data', compact('data', 'total', 'page', 'perPage'));
	}
}