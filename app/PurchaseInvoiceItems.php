<?php

namespace App;
/**
 * :: Purchase Invoice Items Model ::
 * To manage Purchase Invoice Items CRUD operations
 *
 **/

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseInvoiceItems extends Model
{
    use SoftDeletes;
    /**
     * The database table used by the model.
     * @var string
     */
    protected $table = 'purchase_invoice_items';

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'cgst',
        'cgst_amount',
        'sgst',
        'sgst_amount',
        'igst',
        'igst_amount',
        'quantity',
        'price',
        'tax_id',
        'price_list_id',
        'size_id',
        'unit_id',
        'manual_price',
        'total_price',
        'deleted_at'
    ];

    public $timestamps = false;

    /**
     * @param $input
     * @param int $id
     * @return mixed
     * @internal param array $inputs
     * @internal param bool $isMultiple
     *
     */
    public function store($input, $id = null)
    {
        if ($id) {
            $this->find($id)->update($input);
            return $id;
        } else {
            return $this->create($input)->id;
        }

    }

    /**
     * Method is used to search total results.
     *
     * @param array $search
     *
     * @return mixed
     */
    public function getPurchasesItems($search = null)
    {
        $fields = [
            'purchase_invoice_items.*',
            'unit.name',
            'unit.code',
            'product.product_name',
            'product.product_code',
            'hsn_master.hsn_code',
            'unit.code as unit',
            'tax.name as tax_group',
            'tax.id as tax_id'
        ];
        $filter = 1; // default filter if no search
        if (is_array($search) && count($search) > 0) {
            $f1 = (array_key_exists('purchase_order_id', $search) && $search['purchase_order_id'] != "") ? " AND (purchase_invoice_items.purchase_order_id = " .
                addslashes(trim($search['purchase_order_id'])) . ")" : "";

            $filter .= $f1;
            return $this->leftJoin('purchase_invoice', 'purchase_invoice.id', '=', 'purchase_invoice_items.purchase_order_id')
                ->leftJoin('product', 'product.id', '=', 'purchase_invoice_items.product_id')
                ->leftJoin('tax', 'tax.id', '=', 'product.tax_id')
                ->leftJoin('hsn_master', 'hsn_master.id', '=', 'product.hsn_id')
                ->leftJoin('unit', 'unit.id', '=', 'product.unit_id')
                ->whereRaw($filter)
                ->get($fields);
        }
        return null;
    }

    /**
     * Method is used to search total results.
     *
     * @param array $search
     *
     * @return mixed
     */
    public function getPurchaseItem($search = null)
    {
        $fields = [
            'purchase_invoice_items.*',
            'unit.name',
            'unit.code',
            'product.product_name',
            'product.product_code',
            'hsn_master.hsn_code',
            'unit.code as unit',
            'tax.name as tax_group',
            'tax.id as tax_id'
        ];
        $filter = 1; // default filter if no search
        if (is_array($search) && count($search) > 0) {
            $f1 = (array_key_exists('id', $search) && $search['id'] != "") ? " AND (purchase_invoice_items.id = " .
                addslashes(trim($search['id'])) . ")" : "";

            $filter .= $f1;
            return $this->leftJoin('purchase_invoice', 'purchase_invoice.id', '=', 'purchase_invoice_items.purchase_order_id')
                ->leftJoin('product', 'product.id', '=', 'purchase_invoice_items.product_id')
                ->leftJoin('tax', 'tax.id', '=', 'product.tax_id')
                ->leftJoin('hsn_master', 'hsn_master.id', '=', 'product.hsn_id')
                ->leftJoin('unit', 'unit.id', '=', 'product.unit_id')
                ->whereRaw($filter)
                ->first($fields);
        }
        return null;
    }

    /**
     * @param $id
     *
     * @return mixed
     */
    public function dropItem($id)
    {
        $this->find($id)->update( [ 'deleted_at' => convertToUtc()] );
    }

    /**
     * @param id
     * @return mixed
     */
    public function drop($id = [])
    {
        $this->where('purchase_id', $id)->update( [ 'deleted_at' => convertToUtc()] );
    }
    /**
     * @param $search
     * @return null
     */
    public function getPurchaseReportTaxWise($search)
    {
        $fields = [
            'purchase_invoice.id',
            'account_name',
            'purchase_number',
            'purchase_date',
            'purchase_type',
            \DB::raw('sum(cgst) as cgst'),
            \DB::raw('sum(sgst) as sgst'),
            \DB::raw('sum(igst) as igst'),
            \DB::raw('sum(total_price) as total_price'),
        ];

        $filter = $this->getFilters($search);

        if(count($search) > 0) {
             return $this->leftJoin('purchase_invoice','purchase_invoice.id','=','purchase_invoice_items.purchase_order_id')
                ->leftJoin('tax','tax.id','=','purchase_invoice_items.tax_id')
                ->leftJoin('account_master','account_master.id','=','purchase_order.account_id')
                ->whereRaw($filter)
                ->groupBy('purchase_invoice.id')
                ->orderBy('purchase_number', 'ASC')
                ->orderBy('purchase_date', 'ASC')
                ->get($fields);
            /*$query = \DB::getQueryLog();
            dd(end($query));*/
        }
        return null;

    }
    /**
     * Method is used to get purchase order filters.
     * @param array $search
     * @return mixed
     */
    public function getFilters($search = [])
    {
        $filter = 1;
        if (is_array($search) && count($search) > 0)
        {
            $f1 = (array_key_exists('purchase_type', $search) && $search['purchase_type'] != "") ? " and purchase_invoice.purchase_type = " .
                addslashes(trim($search['purchase_type'])) : "";

            $f2 = (array_key_exists('tax_category', $search) && $search['tax_category'] != "") ? " AND purchase_invoice_items.tax_id = '" .
                addslashes(trim($search['tax_category'])) . "' " : "";

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
                $filter .= " and " . \DB::raw('DATE_FORMAT(purchase_date, "%Y-%m-%d")') . " between '" . convertToLocal($fromDate, 'Y-m-d') . "' and '" . convertToLocal($toDate, 'Y-m-d') . "'";
            }

            if (array_key_exists('purchase_date', $search) && $search['purchase_date'] != "") {
                $date = $search['purchase_date'] . ' 00:00:00';
                $filter .= " and " . \DB::raw('DATE_FORMAT(purchase_date, "%Y-%m-%d")') . " = '" . convertToLocal($date, 'Y-m-d') . "' ";

            }

            if (array_key_exists('month', $search) && $search['month'] != "" && $search['report_type'] == '2') {
                $filter .= " and " . \DB::raw('DATE_FORMAT(purchase_date, "%m")') . " = '" . paddingLeft($search['month']) . "' and purchase_invoice.financial_year_id = '" . financialYearId() . "'";
            }

            if (array_key_exists('year', $search) && $search['year'] != "" && $search['report_type'] == '3') {
                $filter .= " and " . \DB::raw('DATE_FORMAT(purchase_date, "%Y")') . " = '" . $search['year'] . "' ";
            }

            $filter.= $f1 . $f2;
        }
        return $filter;
    }
}
