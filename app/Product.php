<?php

namespace App;
/**
 * :: Products Model ::
 * To manage products CRUD operations
 *
 **/

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    /**
     * The database table used by the model.
     * @var string
     */
    protected $table = 'product';

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = [
        'product_name',
        'product_code',
        'product_group_id',
        'product_type_id',
        'company_id',
        'hsn_id',
        'unit_id',
        'size_id',
        'tax_id',
        'item_qty',
        'cost',
        //'item_packing',
        //'item_level',
        'minimum_level',
        'reorder_level',
        'maximum_level',
        //'alternate_quantity',
        'sub_unit_id',
        'sub_unit_conversion_factor',
        //'is_stock_maintain',
        //'default_vendor',
        //'account_id',
        'description',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
        'deleted_at'
    ];

    /**
     * @param array $inputs
     * @param int $id
     *
     * @return \Illuminate\Validation\Validator
     */
    public function validateProducts($inputs, $id = null)
    {
        //dd($inputs);
        if ($id) {
            $rules['product_name'] = 'required|unique:product,product_name,' . $id . ',id,deleted_at,NULL,company_id,' . loggedInCompanyId();
            $rules['product_code'] = 'nullable|unique:product,product_code,' . $id . ',id,deleted_at,NULL,company_id,' . loggedInCompanyId();
        } else {
            $rules['product_name'] = 'required|unique:product,product_name,NULL,id,deleted_at,NULL,company_id,' . loggedInCompanyId();
            $rules['product_code'] = 'nullable|unique:product,product_code,NULL,id,deleted_at,NULL,company_id,' . loggedInCompanyId();
        }
        //$rules['product_name'] = 'required';
        $rules['product_group'] = 'required';
        $rules['hsn_code'] = 'required|numeric';
        $rules['unit'] = 'required|numeric';
        $rules['product_type_id'] = 'required|numeric';
        $rules['cost'] = 'nullable|numeric';
       // $rules['discount'] = 'numeric';
        $rules['tax_group'] = 'required|numeric';
        $rules['minimum_level'] = 'nullable|numeric';
        $rules['reorder_level'] = 'nullable|numeric';
        $rules['maximum_level'] = 'nullable|numeric';

        if($inputs['product_type_id'] == 3) {
            
            $rules['bomQuantity.*'] = 'required|numeric';
        }
//        if((isset($inputs['product']) && $inputs['product'][0] != null)  && isset($inputs['bomQuantity'])) {
//            foreach ($inputs['bomQuantity'] as $key => $val) {
//                $rules['bomQuantity.' . $key] = 'required|numeric';
//            }
//        }

        //$rules['opening_balance'] = 'numeric';
        //$rules['alternate_quantity'] = 'numeric';

        return \Validator::make($inputs, $rules);
    }

    /**
     * @param $inputs
     * @return \Illuminate\Validation\Validator
     */
    public function validateProductExcel($inputs) 
    {
        $rules = [
            'file' => 'required',
        ];
        return \Validator::make($inputs, $rules);
    }

    /**
     * Scope a query to only include active users.
     * @param $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('product.status', 1);
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopeCompany($query)
    {
        return $query->where('product.company_id', loggedInCompanyId());
    }

    /**
     * @param array $inputs
     * @param int $id
     * @return mixed
     */
    public function store($input, $id = null)
    {
        if ($id) {
            return $this->find($id)->update($input);
        } else {
            return $this->create($input)->id;
        }
    }

    /**
     * @param type $id
     * @return type
     */
    public function findByID($id)
    {
        return $this->where('id', $id)->company()->first();
    }

    /**
     * @param null $search
     * @param $skip
     * @param $perPage
     * @param null $id
     * @return mixed
     */
    public function getProducts($search = null, $skip, $perPage, $id = null)
    {
        $sortBy = [
            'name' => 'product_name',
            'product_code' => 'product_code',
        ];
        $take = ((int)$perPage > 0) ? $perPage : 20;
        $filter = 1; // default filter if no search

        $fields = [
            'product.id',
            'product_name',
            'product_code',
            'sub_unit_id',
            'sub_unit_conversion_factor',
            'product_group_id',
            'product_type_id',
            'product_type.name as product_type_name',
            'hsn_master.id as hsn_id',
            'hsn_master.hsn_code',
            'unit.id as unit_id',
            'unit.name as unit_name',
            'unit.code as unit',
            'tax.name as tax_group',
            'tax.id as tax_id',
            'product.status'
        ];

        $orderEntity = 'product.product_name';
        $orderAction = 'asc';
        if (isset($search['sort_action']) && $search['sort_action'] != "") {
            $orderAction = ($search['sort_action'] == 1) ? 'desc' : 'asc';
            session(['sort_action' => $orderAction]);
        }
        if (isset($search['sort_entity']) && $search['sort_entity'] != "") {
            $orderEntity = (array_key_exists($search['sort_entity'], $sortBy)) ? $sortBy[$search['sort_entity']] : $orderEntity;
            session(['sort_entity' => $orderEntity]);
        }
        if (is_array($search) && count($search) > 0) {
            $f1 = (array_key_exists('keyword', $search) && $search['keyword'] != "") ? " AND (product_name LIKE '%" .
                addslashes($search['keyword']) . "%' OR product_code LIKE '%" .
                //addslashes(trim($search['keyword'])) . "%' OR item_code LIKE '%" .
                addslashes(trim($search['keyword'])) . "%')" : "";

            $f3 = (array_key_exists('product_group', $search) && $search['product_group'] != "") ? " AND (product_group_id = '" .
                addslashes($search['product_group']) . "')" : "";

            $filter .= $f1 . $f3;


        }
        $action = (session('sort_action') != "") ? session('sort_action') : $orderAction;
        $entity = (session('sort_entity') != "") ? session('sort_entity') : $orderEntity;
        if($id){

            $fields = [
                'product.*',
                'hsn_master.id as hsn_id',
                'hsn_master.hsn_code',
                'product_type_id',
                'product_type.name as product_type_name',
                'unit.id as unit_id',
                'unit.name as unit_name',
                'unit.code as unit',
                'tax.name as tax_group',
                'tax.id as tax_id',
                //'product_cost.cost',
                //'product_discount.discount',
                'product.status',
            ];

            return $this->leftJoin('hsn_master', 'hsn_master.id', '=', 'product.hsn_id')
                ->leftJoin('product_type', 'product_type.id', '=', 'product.product_type_id')
                ->leftJoin('unit', 'unit.id', '=', 'product.unit_id')
                ->leftJoin('tax', 'tax.id', '=', 'product.tax_id')
                ->leftJoin('tax_rates', 'tax.id', '=', \DB::raw('tax_rates.tax_id and tax_rates.is_active = 1'))
                /*->leftJoin('product_cost', function($join){
                    $join->on('product_cost.product_id', '=', 'product.id');
                    $join->on('product_cost.status', '=', \DB::raw("1"));
                })
                ->leftJoin('product_discount', function($join){
                    $join->on('product_discount.product_id', '=', 'product.id');
                    $join->on('product_discount.status', '=', \DB::raw("1"));
                })*/

                ->company()
                ->active()
                ->where('product.id', $id)
                ->first($fields);
        } else {
            
             return $this->leftJoin('hsn_master', 'hsn_master.id', '=', 'product.hsn_id')
                 ->leftJoin('product_type', 'product_type.id', '=', 'product.product_type_id')
                ->leftJoin('unit', 'unit.id', '=', 'product.unit_id')
                ->leftJoin('tax', 'tax.id', '=', 'product.tax_id')
                ->whereRaw($filter)
                ->company()
                ->active()
                ->orderBy($entity, $action)
                ->skip($skip)->take($take)->get($fields);
        }

    }
    /**
     * Method is used to get total products.
     * @param array $search
     * @return mixed
     */
    public function totalProducts($search = null)
    {
        $filter = 1; // if no search add where

        if (is_array($search) && count($search) > 0) {
            $f1 = (array_key_exists('keyword', $search) && $search['keyword'] != "") ? " AND (product_name LIKE '%" .
                addslashes($search['keyword']) . "%' OR product_code LIKE '%" .
                addslashes(trim($search['keyword'])) . "%' OR hsn_code LIKE '%" .
                addslashes(trim($search['keyword'])) . "%')" : "";

            $f3 = (array_key_exists('product_group', $search) && $search['product_group'] != "") ? " AND (product_group_id = '" .
                addslashes($search['product_group']) . "')" : "";

            $filter .= $f1 . $f3;
        }
        return $this->select(\DB::raw('count(*) as total'))
            ->leftJoin('hsn_master', 'hsn_master.id', '=', 'product.hsn_id')
            ->whereRaw($filter)
            ->company()
            ->active()
            ->first();
    }
    /**
     * @param null $search
     * @param $skip
     * @param $perPage
     * @param null $id
     * @return mixed
     */
    public function getProductDetails($id, $sid)
    {
        if($id && $sid){
            $fields = [
                'product.cost',
                'hsn_master.hsn_code',
                'unit.code as unit',
                'tax.name as tax_group',
                'tax.id as tax_id',
            ];

            return $this->leftJoin('hsn_master', 'hsn_master.id', '=', 'product.hsn_id')
                ->leftJoin('unit', 'unit.id', '=', 'product.unit_id')
                ->leftJoin('tax', 'tax.id', '=', 'product.tax_id')
                ->leftJoin('product_sizes', 'product_sizes.product_id', '=', 'product.id')
                ->leftJoin('sizes', 'product_sizes.size_id', '=', 'sizes.id')
                ->where('product.id',$id)
                ->where('sizes.id',$sid)
                ->company()
                ->active()
                ->get($fields);
        }
        return [];

    }

    /**
     * @param array $search
     * @return mixed
     */
    public function getProductsService($search = [])
    {
        $filter = 1;
        if (is_array($search) && count($search) > 0) {
            $f1 = (array_key_exists('type_id', $search)) ? " AND (product_type_id IN (" .
                implode(',', $search['type_id']) . "))" : "";

            $f2 = (array_key_exists('t', $search) && $search['t'] != "") ? " AND (product_name LIKE '%" .
                addslashes(trim($search['t'])) . "%' OR product_code LIKE '%" .
                addslashes(trim($search['t'])) . "%')" : "";
            $filter .= $f1 . $f2;
        }

        $data = $this->active()->company()->whereRaw($filter)
                ->orderBy('product_name', 'ASC')
                ->get(['id','product_name', 'product_code']);
        $result = [];
        foreach($data as $detail) {
            $code = ($detail->product_code != "") ? ' (' . $detail->product_code . ')' : '';
            $result[$detail->id]  = $detail->product_name . $code;
        }
        return ['' => '-Select Product-'] + $result;
    }

    /**
     * @param null $search
     * @param $skip
     * @param $perPage
     * @return mixed
     */
    public function getPriceListProducts($search = null, $skip, $perPage)
    {
       //$take = ((int)$perPage > 0) ? $perPage : 5;
        $take = ((int)$perPage > 0) ? $perPage : 20;
        $filter = 1; // default filter if no search

        $fields = [
            'product.id as product_id',
            'product_name',
            'product_code',
            //'item_code',
            'product_group.name as product_group',
            'sizes.name as size',
            'sizes.id as size_id'
        ];

        //$filter = 1;
        if (is_array($search) && count($search) > 0) {
            $f1 = (array_key_exists('keyword', $search) && $search['keyword'] != "") ? " AND (product_name LIKE '%" .
                addslashes($search['keyword']) . "%')" : "";
            $filter .= $f1;
        }

        return $this->active()
            ->leftJoin('product_group', 'product_group.id', '=', 'product.product_group_id')
            ->leftJoin('product_sizes', 'product_sizes.product_id', '=', 'product.id')
            ->leftJoin('sizes', 'product_sizes.size_id', '=', 'sizes.id')
            ->whereNull('product_sizes.deleted_at')
            ->whereIn('product_type_id', [3])
            ->where('sizes.id', '!=', "")
            ->whereRaw($filter)
            ->orderBy('product_id', 'ASC')
            ->orderBy('_order', 'ASC')
            ->skip($skip)->take($take)->get($fields);

    }

    /**
     * @param array $search
     * @return mixed
     */
    public function totalPriceListProducts($search = [])
    {
        $filter = 1;
        if (is_array($search) && count($search) > 0) {
            $f1 = (array_key_exists('keyword', $search) && $search['keyword'] != "") ? " AND (product_name LIKE '%" . addslashes($search['keyword']) . "%')" : "";
            $filter .= $f1;
        }

        return $this->active()
            ->leftJoin('product_group', 'product_group.id', '=', 'product.product_group_id')
            ->leftJoin('product_sizes', 'product_sizes.product_id', '=', 'product.id')
            ->leftJoin('sizes', 'product_sizes.size_id', '=', 'sizes.id')
            ->where('sizes.id', '!=', "")
            ->select(\DB::raw('count(*) as total'))
            ->whereRaw($filter)
            ->get()->first();

    }

    /**
     * @param array $inputs
     * @return array
     */
    public function getStockReport($inputs = [])
    {
        $fields = [
            'product.id',
            'product.product_name',
            'product.product_code',
            'product.item_code',
            'product_cost.cost',
            'supplier_order_items.quantity',
            'supplier_order.id as so_id',
            'supplier_order.expected_arrival_date',
            'supplier_order_items.id as soi_id',
            \DB::raw('sum(stock_master.stock_in) as stock_in'),
            \DB::raw('sum(stock_master.stock_out) as stock_out'),
        ];

        $filter = 1; // default filter if no search
        if (is_array($inputs) && count($inputs) > 0) {
            $f1 = (array_key_exists('product', $inputs) && $inputs['product'] != "") ? " AND (product.id = " .
                addslashes(trim($inputs['product'])) . ")" : "";

            $f2 = (array_key_exists('group', $inputs) && $inputs['group'] != "") ? " AND (product.product_group_id = " .
                addslashes(trim($inputs['group'])) . ")" : "";
            $filter .= $f1 . $f2;
        }

        $result =  $this->leftJoin('product_cost', 'product.id', '=', 'product_cost.product_id')
            ->leftJoin('stock_master', 'product.id', '=', 'stock_master.product_id')
            ->leftJoin('supplier_order_items', 'product.id', '=', 'supplier_order_items.product_id')
            ->leftJoin('supplier_order', 'supplier_order_items.supplier_order_id', '=', \DB::raw('supplier_order.id and supplier_order.is_received=0'))
            ->where('product_cost.status', 1)
            ->whereNull('supplier_order_items.deleted_at')
            ->whereNull('supplier_order.deleted_at')
            ->whereRaw($filter)
            ->Company()
            ->groupBy('product.id')
            ->groupBy('supplier_order_items.id')
            ->get($fields);

        $render = [];
        if(count($result) > 0) {
            $stockIn = 0;
            $oldProductId = '';
            foreach ($result as $key => $value) {
                if ($oldProductId != $value->id) {
                    $stockIn = 0;
                    $x = [];
                }
                if ($value->so_id == "" && $value->expected_arrival_date == "") {
                    $stockIn = $value->stock_in;
                }

                $render[$value->id] = [
                    'id' => $value->id,
                    'product_name' => $value->product_name,
                    'product_code' => $value->product_code,
                    'item_code' => $value->item_code,
                    'cost' => $value->cost,
                    'stock_in' => $stockIn,
                    'stock_out' => $value->stock_out,
                    'intransit' => []
                ];

                if ($value->so_id > 0 && $value->soi_id > 0 && $value->expected_arrival_date != "") {
                    $x[] = [
                        'quantity' => $value->quantity,
                        'date' => convertToLocal($value->expected_arrival_date, 'd.m.Y')
                    ];
                }
                if (isset($x) && count($x) > 0) {
                    $render[$value->id]['intransit'] = $x;
                }
                $oldProductId = $value->id;
            }
        }
        return $render;
    }

    /**
     * @param $id
     * @return bool
     */
    public function productGroupExists($id)
    {
        $result  = $this->where('product_group_id', $id)->company()->first();
        if(count($result) > 0) {
            return true;
        }
    }

    /**
     * @param $id
     * @return bool
     */
    public function productInUse($id)
    {
        $productExistsInSaleOrderItem = (new SaleOrderItem)
            ->leftJoin('product', 'product.id', '=','sale_order_items.product_id')
            ->leftJoin('sale_order', 'sale_order.id', '=','sale_order_items.sale_order_id')
            ->where('sale_order_items.product_id',$id)
            ->where('product.company_id', loggedInCompanyId())
            ->whereNull('sale_order.deleted_at')
            ->first();
        $productExistsInPurchaseOrderItem =(new SupplierOrderItems)
            ->leftJoin('product', 'product.id', '=', 'supplier_order_items.product_id')
            ->leftJoin('supplier_order', 'supplier_order.id', '=', 'supplier_order_items.supplier_order_id')
            ->where('supplier_order_items.product_id',$id)
            ->where('product.company_id', loggedInCompanyId())
            ->whereNull('supplier_order.deleted_at')
            ->first();
        if(count($productExistsInSaleOrderItem) > 0 || count($productExistsInPurchaseOrderItem) > 0)
        {
            return true;
        }
    }

    /**
     * @param $id
     */
    public function drop($id)
    {
        $this->find($id)->update( [ 'deleted_by' => authUserId(), 'deleted_at' => convertToUtc()] );
    }

    /**
     * @return null
     */
    public function getProductDetail()
    {
        $fields = [
            'product.id',
            'product.product_name',
            'product.product_code',
            'hsn_master.hsn_code',
            'unit.name as unit_name',
            'tax.name as tax',
            'product_cost.cost',
            'product_discount.discount',
            'product.minimum_level',
            'product.reorder_level',
            'product.maximum_level',
            'product.description',
            'tax_rates.cgst_rate',
            'tax_rates.sgst_rate',
            'tax_rates.igst_rate'
        ];
        $filter = 1;
        return $this->leftJoin('hsn_master', 'hsn_master.id', '=', 'product.hsn_id')
            ->leftJoin('unit', 'unit.id', '=', 'product.unit_id')
            ->leftJoin('product_cost', 'product_cost.product_id', '=', 'product.id')
            ->leftJoin('product_discount', 'product_discount.product_id', '=', 'product.id')
            ->leftJoin('tax', 'tax.id', '=', 'product.tax_id')
            ->leftJoin('tax_rates', 'tax_rates.tax_id', '=', \DB::raw('product.tax_id and tax_rates.is_active = 1'))
            ->whereRaw($filter)
            ->company()
            ->active()
            ->get($fields);
    }
    /**
     * @return null
     */
    public function getProductInfo()
    {
        $fields = [
            'product.id',
            'product.product_name',
            'product.product_code',
            'hsn_master.hsn_code',
            'unit.name as unit_name',
            'tax.name as tax',
            'product_cost.cost',
            'product_discount.discount',
            'product.minimum_level',
            'product.reorder_level',
            'product.maximum_level',
            'product.description',
            'tax_rates.cgst_rate',
            'tax_rates.sgst_rate',
            'tax_rates.igst_rate'
        ];
        $filter = 1;
        return $this->leftJoin('hsn_master', 'hsn_master.id', '=', 'product.hsn_id')
            ->leftJoin('unit', 'unit.id', '=', 'product.unit_id')
            ->leftJoin('product_cost', 'product_cost.product_id', '=', 'product.id')
            ->leftJoin('product_discount', 'product_discount.product_id', '=', 'product.id')
            ->leftJoin('tax', 'tax.id', '=', 'product.tax_id')
            ->leftJoin('tax_rates', 'tax_rates.tax_id', '=', \DB::raw('product.tax_id and tax_rates.is_active = 1'))
            ->whereRaw($filter)
            ->company()
            ->active()
            ->get($fields);
    }
    /**
     * Method is used to find Product Code.
     * @param string $search
     * @return id
     */
    public function findProductCode($search = '')
    {
        $filter = '';
        if ($search != '') {
            $filter = "product_code LIKE '" . $search . "' ";
            return $this->whereRaw($filter)
                ->company()
                ->first();
        }
        return false;
    }

    /**
     * @param $id
     * @param $date
     * @return mixed
     */
    public function getProductEffectedTax($id, $date)
    {
        return $this->leftJoin('tax', 'tax.id', '=', 'product.tax_id')
            ->leftJoin('tax_rates', 'tax.id', '=', \DB::raw('tax_rates.tax_id'))
            ->where('product.id', $id)
            ->where(function($query) use ($date) {
                $query->where(function($inner) use ($date) {
                    $inner->where('wef', '<=', $date)
                        ->where('wet', '>=', $date);
                });
                $query->oRWhere(function($inner) use ($date) {
                    $inner->where('wef', '<=', $date)
                        ->whereNull('wet');
                });
            })->first(['tax_rates.*']);
    }

    /**
     * @param array $search
     * @return bool
     */
    public function getFilteredProduct($search = [])
    {
        $filter = '';
        if ($search != '') {
            $filter = 1;
            if (is_array($search) && count($search) > 0) {
                $filter .=  (array_key_exists('tax', $search) && $search['tax'] != "") ?
                    " AND tax_id = '" . $search['tax'] . "'" : "";
            }
            return $this->active()->company()->whereRaw($filter)
                ->first(['id','product_name', 'product_code']);
        }
        return false;
    }

    /**
     * @param array $inputs
     * @return mixed
     */
    public function productCreate($inputs = [])
    {
        $inputs['company_id'] = loggedInCompanyId();
        $inputs['created_by'] = authUserId();
        $inputs['status'] = 1;
        $hsnId = (new HsnCode)->createHsnCode($inputs);

        $result = $this->where('product_name', $inputs['product_name'])
            ->where('unit_id', $inputs['unit_id'])
            ->first();

        if($result) {
            $rs['hsn_id'] = $hsnId;
            $rs['product_id'] = $result->id;
            return $rs;
        }

        $inputs['hsn_id'] = $hsnId;
        $inputs['product_group_id'] = 178;

            $id = $this->create($inputs)->id;
            $rs['hsn_id'] = $hsnId;
            $rs['product_id'] = $id;
            return $rs;
    }

    /**
     * @param array $search
     * @return array
     */
    public function ajaxProduct($search = [])
    {
        $fields = [
            'product.id',
            'product.product_name',
            'product_group.name as product_group_name',
        ];
        $filter = 1;
        $json = [];

        if (is_array($search) && count($search) > 0 && isset($search['keyword']) && $search['keyword'] != '')
        {
            $filter .= " and (product.product_name like '%" . addslashes($search['keyword']) .
                "%' or product_group.name like '%" . addslashes($search['keyword']) . "%')";
            $filter .= " and (product_type.id = 1)";

            $result = $this->leftJoin('product_group', 'product_group.id', '=', 'product.product_group_id')
                           ->leftJoin('product_type', 'product_type.id', '=', 'product.product_type_id')
                ->whereRaw($filter)

                ->get($fields);
            if (isset($result) && count($result) > 0) {
                foreach ($result as $key => $value) {
                    $json[] = [
                        'id' => $value->id,
                        'text' => $value->product_name,
                        /*'text' => $value->product_group_name . ' --> ' . $value->product_name,*/
                    ];
                }
            }
        }
        return $json;
    }
    /**
     * @param array $search
     * @return array
     */
    public function getProductBySize($search = [])
    {
        $fields = [
            'product.id',
            'product.product_name',
            'sizes.id as size_id',
            'sizes.size as size',
        ];
        $json = [];

        $result = $this->leftJoin('product_sizes', 'product_sizes.product_id', '=', 'product.id')
                        ->leftJoin('sizes', 'sizes.id', '=', 'product_sizes.size_id')
                        ->get($fields)->toArray();

        if (isset($result) && count($result) > 0) {
            foreach ($result as $key => $value) {
                $json[$value['id']][] = [
                    'id'        => $value['id'],
                    'name'      => $value['product_name'] ,
                    'size_id'   => $value['size_id'],
                    'size'      => $value['size']
                ];
            }
        }
      // dd($json);
        return $json;
    }

    /**
     * @param array $search
     * @return array
     */
    public function getNotFinishedProduct($search = [])
    {
        /*$fields = [
            'product.id',
            'product.product_name',
            'product_group.name as product_group_name',
        ];
        $filter = 1;
        //$json = [];

        if (is_array($search) && count($search) > 0 && isset($search['keyword']) && $search['keyword'] != '')
        {
            $filter .= " and (product.product_name like '%" . addslashes($search['keyword']) .
                "%' or product_group.name like '%" . addslashes($search['keyword']) . "%')";
        }
        $data = $this->leftJoin('product_group', 'product_group.id', '=', 'product.product_group_id')
                ->whereRaw($filter)
                ->where('product_type_id' ,'!=','3')
                ->get($fields);
        $result = [];
        foreach($data as $detail) {
            $result[$detail->id] = $detail->product_name.'('.$detail->product_group_name.')';
        }
        return ['' => '-Select Product-'] + $result;*/
        $fields = [
            'product.id',
            'product.product_name',
            'product_group.name as product_group_name',
        ];
        $filter = 1;
        $json = [];

        if (is_array($search) && count($search) > 0 && isset($search['keyword']) && $search['keyword'] != '')
        {
            $filter .= " and (product.product_name like '%" . addslashes($search['keyword']) .
                "%' or product_group.name like '%" . addslashes($search['keyword']) . "%')";

            $result = $this->leftJoin('product_group', 'product_group.id', '=', 'product.product_group_id')
                ->whereRaw($filter)
                ->where('product_type_id' ,'!=','3')
                ->get($fields);
            if (isset($result) && count($result) > 0) {
                foreach ($result as $key => $value) {
                    $json[] = [
                        'id' => $value->id,
                        'text' => $value->product_group_name . ' --> ' . $value->product_name,
                    ];
                }
            }
        }
        return $json;

    }
}