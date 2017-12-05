<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Product;
//use App\ProductCost;
use App\ProductBom;
use App\ProductGroup;
use App\ProductSize;
use App\ProductType;
use App\Size;
//use App\Stock;
use App\Unit;
use App\Tax;
use Maatwebsite\Excel\Facades\Excel;
use App\HsnCode;
use Illuminate\Http\Request;

class ProductsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $groups = (new ProductGroup)->getProductGroupService();
        return view('products.index', compact('groups'));
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $groups = (new ProductGroup)->getProductGroupService();
        $productType = (new ProductType)->getProductTypeService();
        $size = (new Size)->getSizeService();
        $unit = (new Unit)->getUnitService();
        $tax = (new Tax)->getTaxService();
        $hsnCode = (new HsnCode)->getHsnCodeService();
        //$allRawProducts=(new Product)->getNotFinishedProduct([]);
        //dd($allRawProducts);
        $tab = 1;
        return view('products.create', compact('groups', 'unit', 'tax', 'hsnCode', 'productType','size','tab'));
    }
    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $inputs  = $request->all();
        //trimInputs();
       // dd($inputs);
        $validator = (new Product)->validateProducts($inputs);
        if ($validator->fails()) {
            return validationResponse(false, 206, "", "", $validator->messages());
        }
        try {
            $authUserId=authUserId();
            $loggedInComp=loggedInCompanyId();
            $productGroup = $inputs['product_group'];
            unset($inputs['product_group']);
            $hsnCode = $inputs['hsn_code'];
            unset($inputs['hsn_code']);
            $unit = $inputs['unit'];
            unset($inputs['unit']);
            $taxGroup = $inputs['tax_group'];
            unset($inputs['tax_group']);
            $sizeArray = $inputs['size'];
            unset($inputs['size']);


            \DB::beginTransaction();
            if( !array_key_exists('status', $inputs) ) {
                $inputs = $inputs + [ 'status' =>  0];
            }
            $inputs = $inputs + [
                    'product_group_id'  =>  $productGroup,
                    'hsn_id'    => $hsnCode,
                    'unit_id'   => $unit,
                    'tax_id'   => $taxGroup,
                    'created_by'    => $authUserId,
                    'company_id' => $loggedInComp,
                ];
            //dd($inputs);
            $productId =(new Product)->store($inputs);

            //dd($productId);

            if(count($sizeArray) > 0 && $sizeArray[0] != null ){
                $sizeData=[];
                foreach ($sizeArray as $key=>$sizeId){
                    $sizeData[]=[
                        'product_id' => $productId,
                        'size_id'    => $sizeId,
                        'created_by' => $authUserId,
                    ];
                }
                //dd($sizeData);
                (new ProductSize)->store($sizeData,null,true);
            }

            //dd($inputs['product'],$inputs['bomQuantity']);
            if(count($inputs['product']) > 0 && $inputs['product'][0] != null ){
                $bomData=[];
                foreach ($inputs['product'] as $key=>$details){
                   // dd($inputs['bomQuantity'][$key]);
                    $bomData[]=[
                        'company_id'        => $loggedInComp,
                        'bom_number'        => 0,
                        'parent_product_id' => $productId,
                        'parent_size_id'    => 0,
                        'raw_product_id'    => $details,
                        'raw_size_id'       => 0,
                        'quantity'          => $inputs['bomQuantity'][$key],
                        'status'            => 1,
                        //'created_by'        => $authUserId,
                    ];
                }
                //dd($bomData);
                (new ProductBom)->store($bomData,null,true);
            }

            /*$stock = [
                'type' => 3,
                'type_id' => $id,
                'type_item_id' => $id,
                'product_id' => $id,
                'stock_in' => $inputs['opening_balance'],
                'stock_date' => convertToUtc()
            ];
            (new Stock)->store($stock);*/
            \DB::commit();
            $route = route('products.index');
            $lang = lang('messages.created', lang('products.product'));
            return validationResponse(true, 201, $lang, $route);
        }
        catch (\Exception $exception) {
            \DB::rollBack();
            return validationResponse(false, 207, $exception->getFile().$exception->getMessage().$exception->getLine().lang('messages.server_error'));
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
        //$product = (new Product)->company()->find($id);

        try {
            $product = (new Product)->getProducts([], 0, 0, $id);
        } catch (\Exception $exception) {
            abort(401);
        }

        //$cost = ($product->cost != "") ? $product->cost : 0;
        //$discount = ($product->discount != "") ? $product->discount : 0;

        /*if ($cost > 0 && $discount > 0) {
            $cost = ($cost * $discount) / 100;
        }*/
        $productBom=(new ProductBom)->findByIdProductId($id);
        //dd($productBom->toArray());
        $productSizes=(new ProductSize)->findByIdProductId($id);
        $sizes = [];
        if ($productSizes) {
            $sizes = array_column($productSizes->toArray(), 'product_master_size_id');
        }
       // dd($productSizes->toArray(),$sizes  );
        $groups = (new ProductGroup)->getProductGroupService();
        $productType = (new ProductType)->getProductTypeService();
        $size = (new Size)->getSizeService();
        //dd($size, $sizes);
        $unit = (new Unit)->getUnitService();
        $tax = (new Tax)->getTaxService();
        $hsnCode = (new HsnCode)->getHsnCodeService();
        $tab = 1;

        return view('products.edit', compact('product','hsnCode', 'unit', 'tax', 'groups', 'cost', 'discount','productType','size','tab','productSizes','sizes','productBom'));
    }

    /**
     * Update the specified resource in storage.
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request,$id)
    {
        $product = (new Product)->company()->find($id);
        if (!$product) {
            $route = route('products.index');
            $lang = lang('messages.invalid_id', string_manip(lang('products.product')));
            return validationResponse(false, 206, $lang, $route);
        }
        $inputs  = $request->all();
        //dd($inputs);
        $bomDetails=[];
        if(isset($inputs['product_bom_id']) && is_array($inputs['product_bom_id'])) {
            $bomDetails = $request->only('product','product_bom_id','bomQuantity');
        }
        //dd($bomDetails);

        if(!array_key_exists('status', $inputs)) {
            $inputs = $inputs + ['status' => 0 ];
        }
        $validator = (new Product)->validateProducts($inputs, $id);
        if ($validator->fails()) {
            return validationResponse(false, 206, "", "", $validator->messages());
        }


        try {
            \DB::beginTransaction();
            $authUserId=authUserId();
            $loggedInComp=loggedInCompanyId();
            $productGroup = $inputs['product_group'];
            unset($inputs['product_group']);
            $hsnCode = $inputs['hsn_code'];
            unset($inputs['hsn_code']);
            $unit = $inputs['unit'];
            unset($inputs['unit']);
            $taxGroup = $inputs['tax_group'];
            unset($inputs['tax_group']);
            if(isset($inputs['size'])) {
                $sizeArray = $inputs['size'];
                unset($inputs['size']);
            }
            $inputs = $inputs + [
                    'product_group_id' => $productGroup,
                    'hsn_id'           => $hsnCode,
                    'unit_id'          => $unit,
                    'tax_id'           => $taxGroup,
                    'created_by'       => $authUserId,
                    'company_id'       => $loggedInComp,
                ];
            (new Product)->store($inputs, $id);



            if(isset($sizeArray)) {
                $oldSizes = [];
                $result = (new ProductSize)->findByIdProductId($id);
                //dd($result->toArray(),$sizeArray);
                //$result = (new ProductSize)->findByIdProductId(['product_id' => $id]);
                if ($result) {
                    $oldSizes = array_column($result->toArray(), 'product_master_size_id');
                }

                if (isset($sizeArray) && is_array($sizeArray)) {
                    $newSizes = array_values($sizeArray);
                    $deleted = array_diff($oldSizes, $newSizes);
                    $newAdded = array_diff($newSizes, $oldSizes);
                    //dd($newAdded);

                    if (count($newAdded) > 0) {
                        foreach ($newAdded as $key => $value) {
                            if ($value > 0) {
                                $update[] = [
                                    'product_id' => $id,
                                    'size_id' => $value,
                                    'created_by' => $authUserId,
                                    'created_at' => convertToUtc()
                                ];
                            }
                        }
                        //dd($update);
                        (new ProductSize())->store($update,null,true);
                    }

                    if (count($deleted) > 0) {
                        foreach ($deleted as $key => $value) {
                            if ($value > 0) {
                                $deletedSizes[] = $value;
                            }
                        }
                        (new ProductSize)->deletedSizes($id, $deletedSizes);
                    }
                }
            }

            if($inputs['product_type_id'] == 3) {
                if (isset($bomDetails) && count($bomDetails) > 0) {
                    //dd($bomDetails);
                    //delete entry
                    (new ProductBom)->deleteBom($id);
                    //insert all
                    foreach ($bomDetails['product'] as $key => $productData) {

                        $arrayBom[] = [
                            'company_id' => $loggedInComp,
                            'bom_number' => 0,
                            'parent_product_id' => $id,
                            'parent_size_id' => 0,
                            'raw_product_id' => $bomDetails['product'][$key],
                            'raw_size_id' => 0,
                            'quantity' => $bomDetails['bomQuantity'][$key],
                            'status' => 1,
                            'created_at' => convertToUtc()
                        ];

                    }
                    (new ProductBom)->store($arrayBom, null, true);
                }
            }


            /*$stock = [
                'type' => 3,
                'type_id' => $id,
                'type_item_id' => $id,
                'product_id' => $id,
                'stock_in' => $inputs['opening_balance'],
                'stock_date' => convertToUtc()
            ];
            (new Stock)->store($stock);*/
            \DB::commit();
            $route = route('products.index');
            $lang = lang('messages.updated', lang('products.product'));
            return validationResponse(true, 201, $lang, $route);


        } catch (\Exception $exception) {
            \DB::rollBack();
            return validationResponse(false, 207, $exception->getMessage() . lang('messages.server_error'));
        }
    }

    /**
     * Used to update product category active status.
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function productToggle($id)
    {
        if (!\Request::isMethod('post') && !\Request::ajax()) {
            return lang('messages.server_error');
        }

        try {
            $product = (new Product)->company()->find($id);
        } catch (\Exception $exception) {
            return lang('messages.invalid_id', string_manip(lang('products.product')));
        }

        $product->update(['status' => !$product->status]);
        $response = ['status' => 1, 'data' => (int)$product->status . '.gif'];

        return json_encode($response);
    }

    /**
     * Used to load more records and render to view.
     * @param int $pageNumber
     * @return \Illuminate\Http\Response
     */
    public function productPaginate(Request $request,$pageNumber = null)
    {
        if (!\Request::isMethod('post') && !\Request::ajax()) {
            return lang('messages.server_error');
        }

        $inputs  = $request->all();
        $page = 1;
        if (isset($inputs['page']) && (int)$inputs['page'] > 0) {
            $page = $inputs['page'];
            session(['p_page' => $page]);
        }

        $perPage = 20;
        if (isset($inputs['perpage']) && (int)$inputs['perpage'] > 0) {
            $perPage = $inputs['perpage'];
        }
        $start = ($page - 1) * $perPage;
        if (isset($inputs['form-search']) && $inputs['form-search'] != '') {
            // $inputs = array_filter($inputs);
            unset($inputs['_token']);
            $data = (new Product)->getProducts($inputs, $start, $perPage);
            $total = (new Product)->totalProducts($inputs);
            $total = $total->total;
        } else {
            $data = (new Product)->getProducts($inputs, $start, $perPage);
            $total = (new Product)->totalProducts($inputs);
            $total = $total->total;
        }
        
        return view('products.load_data', compact('data', 'total', 'page', 'perPage', 'inputs'));
    }

    /**
     * Method is used to update status of product enable/disable
     * @return \Illuminate\Http\Response
     */
    public function productAction(Request $request)
    {
        $inputs  = $request->all();
        if (!isset($inputs['tick']) || count($inputs['tick']) < 1) {
            return redirect()->route('products.index')
                ->with('error', lang('messages.atleast_one', string_manip(lang('products.product'))));
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

        Product::whereRaw('id IN (' . $ids . ')')->update(['status' => $status]);
        return redirect()->route('products.index')
            ->with('success', lang('messages.updated', lang('products.products_status')));
    }

    /**
     * @return String
     */
    public function productSearch(Request $request)
    {
        if (!\Request::isMethod('post') && !\Request::ajax()) {
            return lang('messages.server_error');
        }
        $name = $request->get('name', '');
        if ($name != "") {
            $data = (new Product)->getProductsService($name);
            echo json_encode($data);
        }
    }

    /**
     * @param null $id
     * @return string
     */
    public function getProductSizes($id)
    {
        if (!\Request::ajax()) {
            return lang('messages.server_error');
        }
        $result = (new ProductSizes)->getProductSizeService($id, false);
        $options = '';
        foreach($result as $key => $value) {
            $options .='<option value="'. $key .'">' . $value . '</option>';
        }
        echo $options;
    }

    /**
     * @param null $id
     * @return string
     */
    public function getProductSizeList($id)
    {
        if (!\Request::ajax()) {
            return lang('messages.server_error');
        }
        $result = (new ProductSize)->getProductSizeService($id);
        $options = '<option value=""></option>';
        foreach($result as $key => $value) {
            $options .='<option value="'. $value['p_id'] .','  .$value['s_id'].'">' . $value['size_name'] .' ('.$value['size'].')' . '</option>';
        }
        echo $options;
    }



    /**
     * @param $id
     * @return String
     */
    public function getProductCost($id){
        if(!\Request::ajax()){
            return lang('messages.server_error');
        }
        $result = (new ProductCost())->getEffectedCost(true, ['product' => $id]);
        if(count($result)){
            echo $result->cost;
        }
        echo 0;
    }

    /**
     * @param $id
     * @return String
     */
    public function getProductDetailForInvoice($id)
    {
        if(!\Request::ajax()){
            return lang('messages.server_error');
        }

        try {
            $product = (new Product)->getProducts([], 0, 0, $id);
        } catch (\Exception $exception) {
            return lang('messages.invalid_id', string_manip(lang('products.product')));
        }
        $cost = ($product->cost != "") ? $product->cost : 0;
        $discount = ($product->discount != "") ? $product->discount : 0;

        if ($cost > 0 && $discount > 0) {
            $discountAmount = ($cost * $discount) / 100;
            $cost = getRoundedAmount($cost - $discountAmount);
        }
        $response = [
            'price_rate' => $cost,
            'hsn_code' => ($product->hsn_code != "") ? $product->hsn_code : '--',
            'unit' => ($product->unit != "") ? $product->unit : '--',
            'gst' => ($product->tax_group != "") ? $product->tax_group : '--',
            'gst_id' => ($product->tax_id != "") ? $product->tax_id : '',
        ];
        echo json_encode($response);
    }

    /**
     * @param $id
     * @return String
     */
    public function getProductInfo($id, $sid)
    {
        if(!\Request::ajax()){
            return lang('messages.server_error');
        }

        try {
            $product = (new Product)->getProductDetails($id, $sid);

            //dd($product);
        } catch (\Exception $exception) {
            return lang('messages.invalid_id', string_manip(lang('products.product')));
        }
        $cost = ($product['cost'] != "") ? $product['cost'] : 0;
        //$discount = ($product->discount != "") ? $product->discount : 0;

        /*if ($cost > 0 && $discount > 0) {
            $discountAmount = ($cost * $discount) / 100;
            $cost = getRoundedAmount($cost - $discountAmount);
        }*/
        $response = [
            'price_rate' => $cost,
            'hsn_code' => ($product['hsn_code'] != "") ? $product['hsn_code'] : '--',
            'unit' => ($product['unit'] != "") ? $product['unit'] : '--',
            'gst' => ($product['tax_group'] != "") ? $product['tax_group'] : '--',
            'gst_id' => ($product['tax_id'] != "") ? $product['tax_id'] : '',
        ];
        echo json_encode($response);
    }





    /**
     * @param $id
     * @return String
     */
    public function getProductDetailForBom($id)
    {
        if(!\Request::ajax()){
            return lang('messages.server_error');
        }

        try {
            $product = (new Product)->getProducts([], 0, 0, $id);
        } catch (\Exception $exception) {
            return lang('messages.invalid_id', string_manip(lang('products.product')));
        }
        $cost = ($product->cost != "") ? $product->cost : 0;
        $discount = ($product->discount != "") ? $product->discount : 0;

        if ($cost > 0 && $discount > 0) {
            $discountAmount = ($cost * $discount) / 100;
            $cost = getRoundedAmount($cost - $discountAmount);
        }
        $response = [
            'price_rate' => $cost,
            'hsn_code' => ($product->hsn_code != "") ? $product->hsn_code : '--',
            'unit' => ($product->unit != "") ? $product->unit : '--',
            'gst' => ($product->tax_group != "") ? $product->tax_group : '--',
            'gst_id' => ($product->tax_id != "") ? $product->tax_id : '',
        ];
        echo json_encode($response);
    }

    /**
     * @param $id
     * @return string
     */
    public function drop($id)
    {

        if (!\Request::ajax()) {
            return lang('messages.server_error');
        }

        try {
            $check = (new Product)->productInUse($id);
            if($check){
                $response = ['status' => 1, 'message' => lang('products.product_in_use')];
            }
            else
            {
                (new Product)->drop($id);
                (new Stock)->delete(['product_id' => $id, 'type' => 3]);
                $response = ['status' => 1, 'message' => lang('messages.deleted', lang('products.product'))];
            }
        } catch (Exception $exception) {
            $response = ['status' => 0, 'message' =>$exception. ' -'.lang('messages.server_error')];
        }
        return json_encode($response);
    }

    /**
     * @return Generate Excel file
     */
    public function generateExcel() {
        ini_set('memory_limit', '-1');
        $data = (new Product)->getProductDetail();
        $main = view('products.generate_excel_load_data', compact('data'));
        return generateExcel('products.product_common', ['main' => $main], 'Products');
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    public function uploadExcel(Request $request)
    {
        $inputs  = $request->all();
        if (count($inputs) > 0) {
            $validator = (new Product)->validateProductExcel($inputs);
            if ($validator->fails()) {
                return validationResponse(false, 206, "", "", $validator->messages());
            }
            ini_set('memory_limit', '-1');
            Excel::load($inputs['file'], function ($reader) {
                $i = 1;
                try {
                    $data = [];
                    $reader->each(function($sheet) {
                        \DB::beginTransaction();
                        //$product = (new Product)->findProductCode($sheet->product_code);
                        $name = trim($sheet->name);
                        if($name != "") {
                            $data['product_name'] = $name;
                            $data['hsn_id'] = trim($sheet->hsn_id);
                            $data['unit_id'] = trim($sheet->unit_id);
                            $data['tax_id'] = trim($sheet->tax_id);
                            //$data['product_code'] = $sheet->product_code;
                            /*$hsnID = (new HsnCode)->findHsnCode((int)$sheet->hsn_code);
                            if (count($hsnID) > 0) {
                                $data['hsn_id'] = $hsnID->hsn_id;
                            } else {
                                $store = ['hsn_code' => (int)$sheet->hsn_code, 'company_id' => loggedInCompanyId(), 'status' => 1];
                                $hsnId = (new HsnCode)->store($store);
                                $data['hsn_id'] = $hsnId;
                            }
                            $unitID = (new Unit)->findUnitID($sheet->unit);
                            if (count($unitID) > 0) {
                                $data['unit_id'] = $unitID->unit_id;
                            }
                            $group = (new ProductGroup)->getProductGroup(['name' => $sheet->group_name], false);
                            if (count($group) > 0) {
                                $data['product_group_id'] = $group->id;
                            }
                            $data['cost'] = (isset($sheet->cost)) ? $sheet->cost : 0;
                            $data['discount'] = (isset($sheet->discount)) ? ltrim($sheet->discount, '0.') : 0;
                            if (!array_key_exists('status', $data)) {
                                $data = $data + ['status' => 1];
                            }
                            $data['cgst_rate'] = ltrim($sheet->cgst_rate, '0.');
                            $data['sgst_rate'] = ltrim($sheet->sgst_rate, '0.');
                            $data['igst_rate'] = ltrim($sheet->igst_rate, '0.');*/
                            $data = $data + [
                                'created_by' => authUserId(),
                                'company_id' => loggedInCompanyId(),
                            ];
                            //dd($data);
                            (new Product)->store($data);
                            \DB::commit();
                        }
                    });
                }
                catch (\Exception $e) {
                    \DB::rollBack();
                    return redirect()->back()->with('error', lang('messages.server_error'));

                }
            });
            //$route = route('products.index');
            $lang = lang('products.product_uploaded');
            return validationResponse(true, 201, $lang);
        }
        return view('products.upload_excel', compact('banks'));
    }
    /**
     * @param filename
     * @return Sample File
     */
    public function downloadSampleExcel($filename = 'product-sample')
    {
        $configs = "GST./[18%], GST28%";
        //dd($accountGroups);
        Excel::create($filename, function($excel) use($configs){

            $excel->sheet('Products', function($sheet1) use($configs){

                $sheet1->cells('A1:H1', function($cells) {
                    $cells->setFont(array(
                        'family'     => 'Calibri',
                        'size'       => '9',
                        'bold'       =>  true
                    ));
                    $cells->setBackground('#FFFF00');
                    $cells->setValignment('center');
                    $cells->setBorder('solid', 'solid', 'solid', 'solid');
                });
                $sheet1->setBorder('A1:I1', 'thin', "#000000");

                $sheet1->cell('A1', function($cell) {
                    $cell->setValue('SR NO.');
                });
                $sheet1->cell('B1', function($cell) {
                    $cell->setValue('PRODUCT NAME');
                });
                $sheet1->cell('C1', function($cell) {
                    $cell->setValue('GROUP NAME');
                });
                $sheet1->cell('D1', function($cell) {
                    $cell->setValue('HSN CODE');
                });
                $sheet1->cell('E1', function($cell) {
                    $cell->setValue('UNIT');
                });
                $sheet1->cell('F1', function($cell) {
                    $cell->setValue('GST');
                });
                $sheet1->cell('G1', function($cell) {
                    $cell->setValue('COST');
                });
                $sheet1->cell('H1', function($cell) {
                    $cell->setValue('DISCOUNT');
                });

                // Set width for multiple cells
                $sheet1->setWidth(array(
                    'A'     =>  5,
                    'B'     =>  25,
                    'C'     =>  25,
                    'D'     =>  10,
                    'E'     =>  10,
                    'F'     =>  10,
                    'G'     =>  10,
                    'H'     =>  10
                ));
                $sheet1->setHeight(1, 25);
                for ($i = 2; $i <= 500; $i++) {
                    $objValidation = $sheet1->getCell('F'.$i)->getDataValidation();
                    $objValidation->setType(\PHPExcel_Cell_DataValidation::TYPE_LIST);
                    $objValidation->setErrorStyle(\PHPExcel_Cell_DataValidation::STYLE_INFORMATION);
                    $objValidation->setAllowBlank(false);
                    $objValidation->setShowInputMessage(true);
                    $objValidation->setShowErrorMessage(true);
                    $objValidation->setShowDropDown(true);
                    $objValidation->setErrorTitle('Input error');
                    $objValidation->setError('Value is not in list.');
                    $objValidation->setPromptTitle('Pick from list');
                    $objValidation->setPrompt('Please pick a value from the drop-down list.');
                    $objValidation->setFormula1('"'.$configs .'"'); //note this!

                }
            });
        })->export('xls');
    }


    /**
     * @return array
     */
    public function ajaxProduct(Request $request)
    {
        $inputs  = $request->all();
        $json = (new Product)->ajaxProduct($inputs);
        //return json_encode(['result' => [['text' => 'ABC' , 'children' => $json]]]);
        return json_encode($json);
    }

    /**
     * @return array
     */
    public function ajaxNotFinishedProduct(Request $request)
    {
        $inputs  = $request->all();
        $json = (new Product)->getNotFinishedProduct($inputs);
        //return json_encode(['result' => [['text' => 'ABC' , 'children' => $json]]]);
        return json_encode($json);
    }
}