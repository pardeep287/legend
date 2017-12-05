<?php

namespace App\Http\Controllers;

/*use App\DiagnosticCategory;
use App\Files;
use App\Size;
use App\Unit;
use App\PriceListGroup;
use App\PriceListSizes;*/

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\PriceList;
use App\PriceListRates;
use App\Product;

class PriceListController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('price-list.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('price-list.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $inputs = $request->all();
        $validator = (new PriceList)->validatePriceList($inputs);
        if ($validator->fails()) {
            return validationResponse(false, 206, "", "", $validator->messages());
        }

        try {
            $route = 'price-list.index';
            if (isset($inputs['save_more'])) {
                $route = 'price-list.create';
            } elseif (isset($inputs['save_add_price'])) {
                $route = 'price-list.edit';
            }

            \DB::beginTransaction();
            $inputs = $inputs + [
                'company_id' => loggedInCompanyId(),
                'created_by'    => authUserId(),
            ];
            $id = (new PriceList)->store($inputs);
            \DB::commit();
            $langMessage = lang('messages.created', lang('price_list.price_list'));
            if ($route == 'price-list.edit') {
                return validationResponse(true, 201, $langMessage, route($route, $id));
            } else {
                return validationResponse(true, 201, $langMessage, route($route));
            }
        } catch (\Exception $e) {
            \DB::rollBack();
            return validationResponse(false, 207, $e->getMessage().' - '.$e->getFile().' - '.$e->getLine().' - '.lang('messages.server_error'));
        }
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $result = PriceList::find($id);
        if (!$result) {
            abort(404);
        }
        return view('price-list.edit', compact('result'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $priceList = PriceList::find($id);
        if (!$priceList) {
            return redirect()->route('price-list.index')
                ->with('error', lang('messages.invalid_id', string_manip(lang('price_list.price_list'))));
        }

        $inputs = $request->all();
        $validator = (new PriceList)->validatePriceList($inputs, $id);
        if ($validator->fails()) {
            return validationResponse(false, 206, "", "", $validator->messages());
        }

        try {
            \DB::beginTransaction();

            $discountApplicable = isset($inputs['discount_applicable'])? $inputs['discount_applicable'] : 0;
            unset($inputs['discount_applicable']);

            $inputs = $inputs + [
                'discount_applicable' => $discountApplicable,
//                'status' => isset($inputs['status']) ? 1 : 0,
            ];
            (new PriceList)->store($inputs, $id);

            if (isset($inputs['price'])) {
                $prices = array_filter($inputs['price']);
                $rates = (new PriceListRates)->getPriceListRates($id);

                if(count($prices) > 0) {
                    $save = $update = [];
                    foreach($inputs['price'] as $key => $value) {
                        $price = $inputs['price'][$key];
                        $productId = $inputs['product_id'][$key];
                        $sizeId = $inputs['size_id'][$key];

                        if ($price > 0) {
                            if (isset($rates[$inputs['product_id'][$key]][$inputs['size_id'][$key]]) &&
                                $rates[$inputs['product_id'][$key]][$inputs['size_id'][$key]] == $price) {

                            } elseif (isset($rates[$inputs['product_id'][$key]][$inputs['size_id'][$key]]) &&
                                $rates[$inputs['product_id'][$key]][$inputs['size_id'][$key]] != $price) {
                                (new PriceListRates)->updatePriceListIds(['id' => $id, 'product_id' => $productId, 'size_id' => $sizeId]);
                                $save[] = [
                                    'price_list_id' => $id,
                                    'product_id' => $productId,
                                    'size_id' => $sizeId,
                                    'rate' => $price,
                                    'wef' => convertToUtc()
                                ];
                            } else {
                                $save[] = [
                                    'price_list_id' => $id,
                                    'product_id' => $productId,
                                    'size_id' => $sizeId,
                                    'rate' => $price,
                                    'wef' => convertToUtc(),
                                ];
                            }
                        }
                    }

                    if (count($save) > 0) {
                        (new PriceListRates)->store($save);
                    }
                }
            }
            \DB::commit();
            $langMessage = lang('messages.updated', lang('price_list.price_list'));
            $route = route('price-list.edit', ['id' => $id]); //route('price-list.index');
            return validationResponse(true, 201, $langMessage, $route);
        } catch (\Exception $e) {
            \DB::rollBack();
            return validationResponse(false, 207, $e->getMessage().' - '.$e->getFile().' - '.$e->getLine().' - '.lang('messages.server_error'));
        }
    }

    /**
     * Used to update priceList category active status.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function priceListToggle($id)
    {
        if (!\Request::isMethod('post') && !\Request::ajax()) {
            return lang('messages.server_error');
        }

        try {
            $priceList = PriceList::find($id);
            $priceList->update(['status' => !$priceList->status]);
            $response = ['status' => 1, 'data' => (int)$priceList->status . '.gif'];
            // return json response
            return json_encode($response);

        } catch (\Exception $exception) {
            return lang('messages.invalid_id', string_manip(lang('price_list.price_list')));
        }
    }

    /**
     * Used to load more records and render to view.
     *
     * @param Request $request
     * @param int $pageNumber
     * @return \Illuminate\Http\Response
     */
    public function priceListPaginate(Request $request, $pageNumber = null)
    {
        if (!\Request::isMethod('post') && !\Request::ajax()) {
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

            $data = (new PriceList)->getPriceLists($inputs, $start, $perPage);
            $total = (new PriceList)->totalPriceLists($inputs);
            $total = $total->total;
        } else {
            $data = (new PriceList)->getPriceLists($inputs, $start, $perPage);
            $total = (new PriceList)->totalPriceLists($inputs);
            $total = $total->total;
        }

        return view('price-list.load_data', compact('data', 'total', 'page', 'perPage'));
    }

    /**
     * Used to load more records and render to view.
     *
     * @param Request $request
     * @param int $id
     * @param null $brand
     * @return \Illuminate\Http\Response
     */
    public function getListProducts(Request $request, $id = null, $brand = null)
    {
        if (!\Request::isMethod('post') && !\Request::ajax()) {
            return lang('messages.server_error');
        }

        $inputs = $request->all();
        $rates = (new PriceListRates)->getPriceListRates($id);

        $page = 1;
        if (isset($inputs['page']) && (int)$inputs['page'] > 0) {
            $page = $inputs['page'];
        }

        $perPage = 20;
        if (isset($inputs['perpage']) && (int)$inputs['perpage'] > 0) {
            $perPage = $inputs['perpage'];
        }

        //$inputs['brand'] = $brand;
        $start = ($page - 1) * $perPage;
        if (isset($inputs['form-search']) && $inputs['form-search'] != '') {
            $inputs = array_filter($inputs);
            unset($inputs['_token']);
            $data = (new Product)->getPriceListProducts($inputs, $start, $perPage);
            $total = (new Product)->totalPriceListProducts($inputs);
            $total = $total->total;
        } else {
            $data = (new Product)->getPriceListProducts($inputs, $start, $perPage);
            $total = (new Product)->totalPriceListProducts($inputs);
            $total = $total->total;
        }
        return view('price-list.load_price_list_data', compact('data', 'total', 'page', 'perPage', 'rates'));
    }

    /**
     * Method is used to update status of priceList enable/disable
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function priceListAction(Request $request)
    {
        $inputs = $request->all();
        if (!isset($inputs['tick']) || count($inputs['tick']) < 1) {
            return redirect()->route('price-list.index')
                ->with('error', lang('messages.atleast_one', string_manip(lang('price_list.priceList'))));
        }

        $ids = '';
        foreach ($inputs['tick'] as $key => $value) {
            $ids .= $value . ',';
        }

        $ids = rtrim($ids, ',');
        $status = 0;
        if (isset($inputs['active'])) {
            $status = 1;
        }

        PriceList::whereRaw('id IN (' . $ids . ')')->update(['status' => $status]);
        return redirect()->route('price-list.index')
            ->with('success', lang('messages.updated', lang('price_list.price_list_status')));
    }

    /**
     * @param null $id
     * @return string
     */
    public function getPriceListProducts($id = null)
    {
        if (!\Request::ajax()) {
            return lang('messages.server_error');
        }
        $result = (new PriceList)->getPriceListProductService($id);
        $options = ''; //<option value="">-Select Product-</option>
        foreach($result as $key => $value) {
            $options .='<option value="'. $key .'">'. $value .'</option>';
        }
        echo $options;
    }
}
