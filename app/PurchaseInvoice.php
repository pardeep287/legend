<?php

namespace App;
/**
 * :: Purchase Invoice Model ::
 * To manage Purchase Invoice CRUD operations
 *
 **/

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Mail;

class PurchaseInvoice extends Model
{
    use SoftDeletes;

    /**
     * The database table used by the model.
     * @var string
     */
    protected $table = 'purchase_invoice';

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = [
        'company_id',
        'financial_year_id',
        'purchase_order_id',
        'purchase_number',
        'purchase_date',
        'cash_credit',
        'account_id',
        'purchase_type',
        'po_type',
        'order_date',
        'invoice_number',
        'carriage',
        'through',
        'vehicle_no',
        'cgst_total',
        'sgst_total',
        'igst_total',
        'freight',
        'round_off',
        'other_charges',
        'gross_amount',
        'net_amount',
        'status',
        'is_email_sent',
        'remarks',
        'created_by',
        'updated_by',
        'deleted_by',
        'deleted_at'
    ];

    /**
     * Scope a query to only include active users.
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Scope a query to only include active users.
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFinancialYear($query)
    {
        return $query->where('purchase_invoice.financial_year_id', financialYearId());
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopeCompany($query)
    {
        return $query->where('purchase_invoice.company_id', loggedInCompanyId());
    }
    
    /**
     * @return string
     */
    public function getNewPONumber()
    {
        $id = financialYearId();
        $data =  $this->where('financial_year_id', $id)->company()->first([\DB::raw('max(purchase_number) as purchase_number')]);
        if (count($data) == 0) {
            $number = '01';
        } else {
            $number = paddingLeft(++$data->purchase_number);
            // new purchase invoice number increment by 1
        }
        return $number;
    }

    /**
     * @param array $inputs
     * @param int $id
     *
     * @return \Illuminate\Validation\Validator
     */
    public function validatePurchase($inputs, $id = null, $isApi = false , $isEdited = false)
    {
        $rules = [
            'account' => 'required|numeric',
            //'purchase_date' => 'required|date|after:'. date('Y-m-d', strtotime("-1 days")),
            'purchase_date' => 'required|date|date_format:d-m-Y|before:'. date('d-m-Y', strtotime("+1 days")),
            'po_type'     => 'required'
            /*'order_date' => 'date|date_format:d-m-Y|before:purchase_date',*/

        ];

        if($id) {
            $rules['purchase_number'] = 'unique:purchase_invoice,purchase_number,' . $id . ',id,deleted_at,NULL,financial_year_id,' . financialYearId();
        } else {
            $rules['purchase_number'] = 'required|unique:purchase_invoice,purchase_number,NULL,id,deleted_at,NULL,financial_year_id,' . financialYearId();
        }

        if($isApi)
        {
            if(isset($inputs['product']) && isset($inputs['quantity']) && isset($inputs['price']) && isset($inputs['manual_price']) )
            {
                foreach($inputs['product'] as $key => $value)
                {
                    $message =
                        [
                            'product.'.$key.'.required'      => lang('messages.product_required'),
                            'quantity.'.$key.'.required'     => lang('messages.quantity_required'),
                            'quantity.'.$key.'.numeric'      => lang('messages.quantity_num'),
                            'quantity.'.$key.'.min:1'        => lang('messages.quantity_min'),
                            'price.'.$key.'.required'        => lang('messages.price_required'),
                            'price.'.$key.'.numeric'         => lang('messages.price_num'),
                            'manual_price.'.$key.'.numeric'  => lang('messages.manual_price_num')
                        ];

                    if($inputs['price'][$key] == "") {
                        $rules['manual_price.'.$key] =  'required|numeric';
                        $message = [
                            'manual_price.'.$key.'.required'  => lang('messages.one_price_required'),
                            'manual_price.'.$key.'.numeric' => lang('messages.manual_price_num')
                        ];
                    }

                    if ($isEdited) {
                        $rules['invoice_master_item_id.'. $key]        = 'numeric';
                    }

                    $rules['product.'. $key]        = 'required|numeric';
                    $rules['quantity.'. $key]       = 'required|numeric';
                    $rules['price.'.   $key]        = 'numeric';
                }
            }
            else {
                $rules['product.0']        = 'required';
                $rules['hsn_code.0']        = 'required';
                $rules['quantity.0']       = 'required|numeric';
                $rules['unit.0']            = 'required';
                $rules['price.0']          = 'required|numeric';
                $rules['cgst.0']            = 'required|numeric';
                $rules['sgst.0']          = 'required|numeric';
                $rules['manual_price.0']   = 'numeric';
            }
        } else {
            if (isset($inputs['addmore']) || isset($inputs['update_item'])) {
                $rules['product'] = 'required';
                $rules['quantity'] = 'required|numeric|greater_zero';
                $rules['tax_id'] = 'required';
                $rules['price'] = 'required|numeric';
                $rules['manual_price'] = 'required|numeric|greater_zero';
            } else {
                if(!$id) {
                    $rules['product'] = 'required';
                    $rules['tax_id'] = 'required';
                    $rules['quantity'] = 'required|numeric|greater_zero';
                    $rules['price'] = 'required|numeric';
                    $rules['manual_price'] = 'required|numeric|greater_zero';
                }
            }
        }

        $messages = [
            'account.required' => 'The party head field is required.',
            'account.numeric' => 'The party head field must be numeric.',

            'tax_id.required' => 'Product tax not defined, please define tax for product.',

            'manual_price.required' => 'The price field is required.',
            'manual_price.numeric' => 'The price must be a numeric.',
            'manual_price.min' => 'The price must be at least 1.',
        ];

        return \Validator::make($inputs, $rules, $messages);
    }
    
    /**
     * @param array $inputs
     * @param int $id
     *
     * @return mixed
     */
    public function store($inputs, $id = null)
    {
        $items = $inputs + [
            'product_id'        => $inputs['product'],
            'cgst'              => $inputs['cgst'],
            'sgst'              => $inputs['sgst'],
            'igst'              => $inputs['igst'],
            'cgst_amount'       => $inputs['cgst_total'],
            'sgst_amount'       => $inputs['sgst_total'],
            'igst_amount'       => $inputs['igst_total'],
            'price'             => $inputs['price'],
            'manual_price'      => $inputs['manual_price'],
            'quantity'          => $inputs['quantity'],
            'total_price'       => $inputs['total_price'],
        ];

        if ($id) {
            $this->find($id)->update($inputs);
            if (isset($inputs['update_item'])) {
                (new PurchaseInvoiceItems)->where('id',  $inputs['item_id'])->update($items);
                (new Stock)->delete(['type_id' => $id, 'type_item_id' => $inputs['item_id'], 'type' => 1]);
                $stock = [
                    'type' => 1,
                    'type_id' => $id,
                    'type_item_id' => $inputs['item_id'],
                    'product_id' => $inputs['product'],
                    'stock_in' => $inputs['quantity'],
                    'stock_date' => convertToUtc()
                ];
                (new Stock)->store($stock);
            } else {
                if ($inputs['product'] != "" && $inputs['quantity'] != "") {
                    $items['purchase_order_id'] = $id;
                    $itemId = (new PurchaseOrderItems)->store($items);
                    $stock = [
                        'type' => 1,
                        'type_id' => $id,
                        'type_item_id' => $itemId,
                        'product_id' => $inputs['product'],
                        'stock_in' => $inputs['quantity'],
                        'stock_date' => convertToUtc()
                    ];
                    (new Stock)->store($stock);
                }
            }
            return $id;
        }
        else {

            $id = $this->create($inputs)->id;
            $items['purchase_invoice_id'] = $id;
            $itemId = (new PurchaseInvoiceItems)->store($items);
            $stock = [
                'type' => 1,
                'type_id' => $id,
                'type_item_id' => $itemId,
                'product_id' => $inputs['product'],
                'stock_in' => $inputs['quantity'],
                'stock_date' => convertToUtc()
            ];
            (new Stock)->store($stock);
            return $id;
        }
    }

    /**
     * @param array $inputs
     * @param null $id
     * @return mixed
     */
    public function purchaseInvoiceUpdate($inputs = [], $id = null)
    {
        return $this->find($id)->update($inputs);
    }

    /**
     * Method is used to search total results.
     * @param array $search
     * @param int $skip
     * @param int $perPage
     * @return mixed
     */
    public function getPurchases($search = null, $skip, $perPage)
    {
      trimInputs();
      $take = ((int)$perPage > 0) ? $perPage : 20;      
      $fields = [
          'purchase_invoice.id',
          'account_master.account_name',
          'account_master.account_code',
          'purchase_number',
          'invoice_number',
          'purchase_date',
          'gross_amount',
          'net_amount',
          'purchase_invoice.status',
          'purchase_invoice.is_email_sent'
      ];

      $sortBy = [
          'invoice_number' => 'invoice_number',
          'purchase_invoice_number' => 'purchase_invoice_number',
          'account_name' => 'account_name',
          'purchase_date' => 'purchase_date',
      ];

      $orderEntity = 'purchase_invoice.id';
      $orderAction = 'desc';

      if (isset($search['sort_action']) && $search['sort_action'] != "") {
          $orderAction = ($search['sort_action'] == 1) ? 'desc' : 'asc';
      }

      if (isset($search['sort_entity']) && $search['sort_entity'] != "") {
          $orderEntity = (array_key_exists($search['sort_entity'], $sortBy)) ? $sortBy[$search['sort_entity']] : $orderEntity;
      }

      $filter = $this->getFilters($search);
      return $this->leftJoin('account_master', 'account_master.id', '=', 'purchase_invoice.account_id')
            ->financialyear()
            ->company()
            ->whereRaw($filter)
            ->orderBy($orderEntity, $orderAction)
            ->skip($skip)->take($take)
          ->get($fields);
    }

    /**
     * Method is used to get total results.
     * @param array $search
     * @return mixed
     */
    public function totalPurchases($search = null)
    {
        trimInputs();
        $filter = $this->getFilters($search);
        return $this->leftJoin('account_master', 'account_master.id', '=', 'purchase_invoice.account_id')
            ->financialyear()
            ->company()
            ->select(\DB::raw('count(*) as total'))
            ->whereRaw($filter)
            ->get()->first();
    }

    /**
     * Method is used to get sale invoice filters.
     * @param array $search
     * @return mixed
     */
    public function getFilters($search = [])
    {
        $filter = 1;
        if (is_array($search) && count($search) > 0)
        {
            $keyword = (array_key_exists('keyword', $search) && $search['keyword'] != "") ?
                " AND (purchase_number LIKE '%" .addslashes(trim($search['keyword'])) . "%'" .
                " OR invoice_number LIKE '%" .addslashes(trim($search['keyword'])) . "%'" .
                " OR account_name LIKE '%" .addslashes(trim($search['keyword'])) . "%')"
                : "";

            $f1 = (array_key_exists('financial_year', $search) && $search['financial_year'] != "") ? " and financial_year_id = " .
                addslashes(trim($search['financial_year'])) : "";

            /*$f2 = (array_key_exists('customer', $search) && $search['customer'] != "") ? " AND invoice_master.customer_id = '" .
                  addslashes(trim($search['customer'])) . "' " : "";*/

            if (array_key_exists('from_date', $search) && $search['from_date'] != ""  && $search['to_date'] == "") {
                $date = $search['from_date'] . ' 00:00:00';
                $filter .= " and " . \DB::raw('DATE_FORMAT(purchase_date, "%Y-%m-%d")') . " = '" . convertToLocal($date, 'Y-m-d') . "' ";
            }

            if (array_key_exists('from_date', $search) && $search['from_date'] != "" &&
                array_key_exists('to_date', $search) && $search['to_date'] != ""
            )
            {
                $fromDate = $search['from_date'] . ' 00:00:00';
                $toDate = $search['to_date'] . ' 00:00:00';
                $filter .= " and " . \DB::raw('DATE_FORMAT(purchase_date, "%Y-%m-%d")')
                    . " between '" . convertToLocal($fromDate, 'Y-m-d')
                    . "' and '" . convertToLocal($toDate, 'Y-m-d') . "'";
            }

            if (array_key_exists('purchase_date', $search) && $search['purchase_date'] != "") {
                $date = $search['purchase_date'] . ' 00:00:00';
                $filter .= " and " . \DB::raw('DATE_FORMAT(purchase_date, "%Y-%m-%d")') . " = '" . convertToLocal($date, 'Y-m-d') . "' ";
            }

            if (array_key_exists('month', $search) && $search['month'] != "" && $search['report_type'] == '2') {
                $m = paddingLeft($search['month']);
                $start = convertToUtc(date('Y-' . $m . '-01 00:00:00'));
                $end = convertToUtc(date('Y-' . $m . '-t 23:59:59'));
                $filter .= " and purchase_date between ' " . $start . "' and  '" . $end . "'";
            }

            if (array_key_exists('year', $search) && $search['year'] != "" && $search['report_type'] == '3') {
                $filter .= " and " . \DB::raw('DATE_FORMAT(purchase_date, "%Y")') . " = '" . $search['year'] . "' ";
            }

            $filter.= $keyword . $f1;
        }
        return $filter;
    }

    /**
     * @param array $options
     */
    protected function finishSave(array $options)
    {
        parent::finishSave($options);
    }

    /**
     * @param $id
     * @return int
     */
    public function drop($id)
    {
        $this->find($id)->update( [ 'deleted_by' => authUserId(), 'deleted_at' => convertToUtc() ] );
    }

    /**
     * @param null $id
     * @param $inputs
     * @return null
     */
    public function updateEmailStatus($id = null, $inputs)
    {
        if ($id) {
            $this->find($id)->update($inputs);
            return $id;
        }
    }

    /**
     * @param $id
     * @return mixed
     */
    public function getOrderDetail($id)
    {
        $fields = [
            'purchase_invoice.*',
            'account_master.account_name'
        ];

        return $this->leftJoin('account_master','account_master.id' , '=', 'purchase_invoice.account_id')
                    ->where('purchase_order.id', $id)
                    ->company()
                    ->first($fields);
    }

    /**
     * @param $invoiceId
     * @param $amount
     * @return mixed
     */
    public function updateAmount($invoiceId, $amount) 
    {        
        $input = [
            'gross_amount' => $amount ,
            'net_amount' => $amount
        ];
        return $this->find($invoiceId)->update($input);
    }

    /**
     * @param $search
     * @return null
     */
    public function getPurchaseSummary($search)
    {
        $fields = [
            'account_name',
            'invoice_number',
            'purchase_date',
            'purchase_type',
            'freight',
            'other_charges',
            'round_off',
            'gross_amount',
            'net_amount',
            \DB::raw('sum(cgst_amount) as cgst_amount'),
            \DB::raw('sum(sgst_amount) as sgst_amount'),
            \DB::raw('sum(igst_amount) as igst_amount'),
            \DB::raw('sum(total_price) as total_amount'),
        ];
        $filter = $this->getFilters($search);
        if(count($search) > 0 && isset($search['month']) && $search['month'] != "") {
            return $this->company()
                ->leftJoin('account_master', 'account_master.id', '=', 'purchase_invoice.account_id')
                ->leftJoin('purchase_invoice_items as pi', 'pi.purchase_order_id', '=', 'purchase_invoice.id')
                ->whereRaw($filter)
                ->groupBy('purchase_invoice.id')
                ->orderBy('purchase_date', 'ASC')
                ->get($fields);
        }
        return null;
    }

    /**
     * @param $id
     * @return purchase number
     */
    public function getPurchaseNumber($id)
    {
        return $this->where('purchase_invoice.id', $id)
                ->company()
                ->first(['purchase_invoice.purchase_invoice_number']);
    }
    /**
     *
     * @return array
     */
    public function getLocalPurchaseByTax($search = null)
    {
        if ($search){
            $fields = [
                'purchase_invoice_items.cgst',
                'purchase_invoice_items.sgst',
                \DB::raw(" SUM(purchase_invoice_items.cgst_amount) as cgst_total"),
                \DB::raw(" SUM(purchase_invoice_items.sgst_amount) as sgst_total"),
                \DB::raw(" SUM(purchase_invoice_items.total_price) as total_purchase")
            ];

            $filter = $this->getFilters($search);
            return $this->join('purchase_invoice_items', function($join){
                $join->on('purchase_invoice.id','=','purchase_invoice_items.purchase_id');
                $join->on('purchase_invoice.purchase_type','=', \DB::raw("1"));
            })
                ->where("financial_year_id", financialYearId())
                ->whereRaw($filter)
                ->company()
                ->groupBy('purchase_invoice_items.cgst','purchase_invoice.sgst')
                ->get($fields);
            /*$query = \DB::getQueryLog();
            dd(end($query));*/
        }
        return [];

    }
    
    /**
     *
     * @return array
     */
    public function getCenterPurchaseByTax($search = null)
    {
        if ($search){
            $fields = [
                'purchase_invoice_items.igst',
                \DB::raw(" SUM(purchase_invoice_items.igst_amount) as igst_total"),
                \DB::raw(" SUM(purchase_invoice_items.total_price) as total_purchase")
            ];

            $filter = $this->getFilters($search);
            return $this->join('purchase_invoice_items', function($join){
                $join->on('purchase_invoice.id','=','purchase_invoice_items.purchase_id');
                $join->on('purchase_invoice.purchase_type','=', \DB::raw("2"));
            })
                ->where("financial_year_id", financialYearId())
                ->whereRaw($filter)
                ->company()
                ->groupBy('purchase_invoice_items.igst')
                ->get($fields);
            /*$query = \DB::getQueryLog();
            dd(end($query));*/
        }
        return [];

    }
}
