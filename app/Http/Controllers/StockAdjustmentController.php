<?php

namespace App\Http\Controllers;

use App\StockMaster;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Product;
use App\Http\Controllers\Controller;
use App\StockAdjustment;

class StockAdjustmentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $products = (new Product)->getProductsService();
        return view('stock-adjustment.index', compact('products'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $products = (new Product)->getProductsService();
        return view('stock-adjustment.create', compact('products'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function store()
    {
        $inputs = \Input::all();

        $validator = (new StockAdjustment)->validateStockAdjustment($inputs);
        if ($validator->fails()) {
            return redirect()->route('stock-adjustment.create')
                ->withInput()
                ->withErrors($validator);
        }

        try {
            \DB::beginTransaction();

            // type check before save in stock_master (whether stock_in or stock_out)
            if($inputs['type'] == '1'){
                $inputs['stock_in'] = $inputs['quantity'];
            }
            else if($inputs['type'] == '2'){
                $inputs['stock_out'] = $inputs['quantity'];
            }

            // stock_date field for stock_master table
            $inputs['stock_date'] = date('Y-m-d H:i:s');
            $inputs['product_id'] = $inputs['product'];

            $stockId = (new StockMaster)->store($inputs);

            $inputs['date'] = dateFormat('Y-m-d', $inputs['date']);
            $inputs = $inputs + [
                    'created_by' => authUserId(),
                    'stock_id' => $stockId
                ];

            (new StockAdjustment)->store($inputs);
            \DB::commit();
            return redirect()->route('stock-adjustment.index')
                ->with('success', lang('messages.created', lang('stock_adjustment.stock_adjustment')));
        } catch (\Exception $exception) {
            \DB::rollBack();
            return redirect()->route('stock-adjustment.create')
                ->withInput()
                ->with('error', lang('messages.server_error'));
        }
    }

    /**
     * Paginate Stock Adjustment
     *
     * @param  int  $pageNumber
     * @return \Illuminate\Http\Response
     */
    public function stockAdjustmentPaginate($pageNumber = null)
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

            $data = (new StockAdjustment)->getStockAdjustment($inputs, $start, $perPage);
            $totalStockAdjustment = (new StockAdjustment)->totalStockAdjustment($inputs);
            $total = $totalStockAdjustment->total;
        } else {

            $data = (new StockAdjustment)->getStockAdjustment($inputs, $start, $perPage);
            $totalStockAdjustment = (new StockAdjustment)->totalStockAdjustment();
            $total = $totalStockAdjustment->total;
        }

		return view('stock-adjustment.load_data', compact('inputs', 'data', 'total', 'page', 'perPage'));
    }

    /**
     * Remove the specified resource from storage.
     * @param $id
     * @return string
     */
    public function drop($id)
    {
        if (!\Request::ajax()) {
            return lang('messages.server_error');
        }

        $stockAdjustment = StockAdjustment::find($id);
        if (!$stockAdjustment) {
            return json_encode(array(
                'status' => 0,
                'message' => lang('messages.invalid_id', string_manip(lang('stock_adjustment.stock_adjustment')))
            ));
        }

        try
        {
            \DB::beginTransaction();

            // soft delete from stock_adjustment table based on id
            (new StockAdjustment)->drop($id);

            // force delete from stock_master table based on stock_id exist in stock_adjustment
            (new StockMaster)->deletePermanently($stockAdjustment->stock_id);

            \DB::commit();

            return json_encode(array(
                'status' => 1,
                'message' => lang('messages.itemDeleted', lang('stock_adjustment.stock_adjustment'))
            ));
        }
        catch (\Exception $exception)
        {
            \DB::rollBack();

            return json_encode(array(
                'status' => 0,
                'message' => lang('messages.server_error', string_manip(lang('stock_adjustment.stock_adjustment')))
            ));
        }
    }
}
