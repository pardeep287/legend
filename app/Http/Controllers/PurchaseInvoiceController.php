<?php

namespace App\Http\Controllers;

/**
 * :: Purchase Invoice Controller ::
 * To manage purchase invoices.
 *
 **/

use App\Bank;
use App\CustomerPriceList;
use App\PriceList;
use App\Product;
use App\Customer;
use App\Stock;
use App\StockMaster;
use App\Company;
use App\Account;
use App\Carriage;
use App\CustomerOrder;
use App\CustomerReceivedOrder;
use App\PurchaseOrder;
use App\PurchaseInvoice;
use App\PurchaseInvoiceItems;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\CustomerOrderReceiveItems;
use App\Size;
use App\Unit;
use App\TransactionMaster;
use App\Voucher;
/*use App\Voucher;
use App\VoucherTransactions;*/
use Illuminate\Support\Facades\Mail;

class PurchaseInvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('purchase-invoice.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param null $id
     * @return \Illuminate\Http\Response
     */
    public function create($id = null)
    {
        if(!getActiveFinancialYear()) {
            return redirect()->route('purchase-invoice.index')
                ->with('error', lang('common.add_financial_year'));
        }
        $bank = [];
        $products = [];
        $purchaseOrder = [];
        /*$bank = (new Bank)->getBankService();*/
        $purchaseNumber = (new PurchaseInvoice)->getNewPONumber();
        $products = (new Product)->getProductsService();
        $productBySize = (new Product)->getProductBySize();
        $purchaseOrder = (new PurchaseOrder)->getPurchaseOrderService();
        //dd('gdsfgdsg');
        return view('purchase-invoice.create', compact('productBySize', 'bank', 'products', 'purchaseNumber','purchaseOrder'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */

    public function store(Request $request)
    {
        $inputs = $request->all();
        $validator = (new PurchaseInvoice)->validatePurchase($inputs);
        if ($validator->fails()) {
            return validationResponse(false, 206, "", "", $validator->messages());
        }

        try
        {
            \DB::beginTransaction();
            $purchaseOrder = $inputs['purchase_order'];
            unset($inputs['purchase_order']);
            $purchaseDate = $inputs['purchase_date'] .  ' ' . date('H:i:s');
            $purchaseDate = dateFormat('Y-m-d H:i:s', $purchaseDate);
            unset($inputs['purchase_date']);

            $orderDate = dateFormat('Y-m-d', $purchaseDate);
            $account = $inputs['account'];
            unset($inputs['account']);

            $product = (new Product)->getProductEffectedTax($inputs['product'], convertToUtc($purchaseDate));

            if(!$product){
                $errors = collect ( [ '' => [lang('products.product_tax_not_valid')] ] );
                return validationResponse(false, 206, "", "", $errors);
            }

            $cgst = ($product->cgst_rate != "") ? $product->cgst_rate : 0;
            $sgst = ($product->sgst_rate != "") ? $product->sgst_rate : 0;
            $igst = ($product->igst_rate != "") ? $product->igst_rate : 0;

            $price = ($inputs['manual_price'] != "") ? $inputs['manual_price'] : $inputs['price'];
            $quantity = $inputs['quantity'];
            $total = round($price * $quantity, 2);

            $cgstRate = round(($total * $cgst)/100, 2);
            $sgstRate = round(($total * $sgst)/100, 2);
            $igstRate = round(($total * $igst)/100, 2);

            $inputs = $inputs + [
                'purchase_date'     => convertToUtc($purchaseDate),
                'order_date'        => ($orderDate != "") ? $orderDate : null,
                'account_id'        => $account,
                'purchase_order_id' => $purchaseOrder,

                'cgst'              => $cgst,
                'sgst'              => $sgst,
                'igst'              => $igst,
                'total_price'       => $total,

                'cgst_total'        => $cgstRate,
                'sgst_total'        => $sgstRate,
                'igst_total'        => $igstRate,

                'created_by'        => authUserId(),
                'company_id'        => loggedInCompanyId(),
                'financial_year_id' => financialYearId(),
                'status'            => 1,
            ];


            $id = (new PurchaseInvoice)->store($inputs);

            if ($inputs['purchase_type'] == 1) {
                $totalTax = $cgstRate + $sgstRate;
            } elseif($inputs['purchase_type'] == 2) {
                $totalTax = $igstRate;
            }
            $grossTotal = $total + $totalTax;
            $netTotal = ($grossTotal + $inputs['freight'] + $inputs['other_charges']) + $inputs['round_off'];
            /*$netRound = round($netTotal);
            $round = ($netRound - $netTotal);*/
            $update = [
                'gross_amount'  => $grossTotal,
                /*'net_amount'    => $netTotal + $round,*/
                'net_amount'    => $netTotal,
            ];
           (new PurchaseInvoice)->purchaseInvoiceUpdate($update, $id);
            /*$this->storeToTransaction($inputs, $account, $id, $netTotal + $round);*/
            /*$purchaseNumber = '';
            $purchaseNumber = (new Purchase)->getPurchaseNumber($id);
            $inputs['ref_id'] = $id;
            $this->storeToVoucher($inputs, $account, $netTotal, $purchaseNumber['purchase_number']);*/
            \DB::commit();
            $route = route('purchase-invoice.index');
            $lang = lang('messages.created', lang('purchase_invoice.purchase_invoice'));
            if (isset($inputs['addmore'])) {
                $route = route('purchase-order.edit', ['id' => $id, 't' => 'edit', 'a' => '1']);
                $lang = lang('messages.created', lang('purchase_invoice.purchase_invoice'));
            }
            return validationResponse(true, 201, $lang, $route);
        }   catch (\Exception $exception) {
            \DB::rollBack();
            return validationResponse(false, 207, lang('messages.server_error').$exception->getMessage());
        }
    }
    /**
     * @param $inputs
     * @param $accountId
     * @param $amount
     * @param $purchaseNumber
     * @param $id
     */
    /*public function storeToVoucher($inputs, $account, $amount, $purchaseNumber, $id = null)
    {
        if($inputs['cash_credit'] == 1) {

            if($id > 0) {
                (new Voucher)->dropVoucher(2, $id);
            }

            $items = [];
            $voucherDate = dateFormat('Y-m-d', $inputs['purchase_date']);
            $inputs['account_id'] = (int) $account;
            $inputs['net_amount'] = $amount;
            //dd($inputs);
            $inputs = $inputs + [
                    'type'          => 2,
                    'voucher_date'  => $voucherDate,
                    'company_id'    => loggedInCompanyId(),
                    'created_by'    => authUserId(),
                    'financial_year_id' => financialYearId()
                ];

            $items = array_only($inputs, [ 'type', 'financial_year_id', 'company_id',
                'voucher_date', 'created_by', 'net_amount', 'account_id', 'ref_id'
            ]);

            $items = $items + [
                    'd_c'       => [ 1, 2 ],
                    'account'   => [ getTransactionTypes('c'), $items['account_id'] ],
                    'amount'    => [ $items['net_amount'] , $items['net_amount'] ],
                    'narration' => [ '', lang('transaction.purchase_against') . paddingLeft($purchaseNumber) ]

                ];

            (new Voucher)->store($items);
        }
        elseif($inputs['cash_credit'] == 2) {
            if($id > 0) {
                (new Voucher)->dropVoucher(2, $id);
            }
        }


    }*/

    /**
     * @param $inputs
     * @param $accountId
     * @param $typeId
     * @param $amount
     * @param $id
     */
    public function storeToTransaction($inputs, $accountId, $typeId, $amount, $id = null)
    {
        $inputs['type_id'] = $typeId;
        $inputs['account_id'] = $accountId;
        $inputs['amount'] = $amount;
        /*$inputs = array_only($inputs, [
                        'type_id',
                        'account_id',
                        'amount',
                        'cash_credit',
                        'purchase_date',
                        'purchase_number'
                        ]);
        if($inputs['cash_credit'] == 1) {
            if($id > 0) {
                (new TransactionMaster)->dropTransaction('P', $id);
            }
            // cash entry
            $inputs['is_cash'] = 1;
        } elseif($inputs['cash_credit'] == 2) {
            if($id > 0) {
                (new TransactionMaster)->dropTransaction('P', $id, true);
            }
            // sale entry
        }*/
        if($id > 0) {
            (new TransactionMaster)->dropTransaction('P', $id, true);
        }
        if($inputs['cash_credit'] == 1) {
            // sale entry
            (new TransactionMaster)->storePurchaseTransaction($inputs);

            $inputs['is_cash'] = 1;
            $this->storeToVoucher($inputs, $accountId, $amount, $id);

            // cash entry
            //(new TransactionMaster)->storeInvoiceTransaction($inputs);
        } else {

            if($id > 0) {
                $where = [
                    'type' => (int) 1,
                    'ref_id' => (int) $inputs['type_id']
                ];
                $result = (new Voucher)->getFilteredVoucher($where);
                if ($result) {
                    $voucherId = $result->id;
                    (new TransactionMaster)->dropTransaction('PV', $voucherId, true);
                    (new Voucher)->dropVoucher(1, $id);
                }
            }
            // purchase entry
            (new TransactionMaster)->storePurchaseTransaction($inputs);
        }
    }
    /**
     * @param $inputs
     * @param $account
     * @param $amount
     * @param null $id
     */
    public function storeToVoucher($inputs, $account, $amount, $id = null)
    {
        if($id > 0) {
            $where = [
                'type' => (int) 1,
                'ref_id' => (int) $inputs['type_id']
            ];
            $result = (new Voucher)->getFilteredVoucher($where);
            if ($result) {
                $voucherId = $result->id;
                (new TransactionMaster)->dropTransaction('PV', $voucherId, true);
            }
            (new Voucher)->dropVoucher(1, $id);
        }

        if($inputs['cash_credit'] == 1)
        {
            /*if($id > 0) {
                $where = [
                    'type' => (int) 1,
                    'ref_id' => (int) $id
                ];
                $result = (new Voucher)->getFilteredVoucher($where);
                if ($result) {
                    $voucherId = $result->id;
                    (new TransactionMaster)->dropTransaction('RV', $voucherId, true);
                }
                (new Voucher)->dropVoucher(1, $id);
            }*/

            $items = [];
            $voucherDate = convertToLocal($inputs['purchase_date'], 'Y-m-d');
            $inputs['account_id'] = (int) $account;
            $inputs['net_amount'] = $amount;

            $inputs = $inputs + [
                    'type'          => 1,
                    'ref_id'        => $inputs['type_id'],
                    'voucher_date'  => $voucherDate,
                    'company_id'    => loggedInCompanyId(),
                    'created_by'    => authUserId(),
                    'financial_year_id' => financialYearId()
                ];

            $items = array_only($inputs, ['type', 'financial_year_id', 'company_id',
                'voucher_date', 'created_by', 'net_amount', 'account_id', 'ref_id'
            ]);

            $items = $items + [
                    'd_c'       => [ 1, 2 ],
                    'account'   => [ getTransactionTypes('c'), $items['account_id'] ],
                    'amount'    => [ $items['net_amount'] , $items['net_amount'] ],
                    'narration' => [
                        lang('transaction.purchase_against') . paddingLeft($inputs['invoice_number']),
                        lang('transaction.purchase_against') . paddingLeft($inputs['invoice_number'])
                    ]
                ];
            $id = (new Voucher)->store($items);

            $itemM = (isset($items['d_c']) && is_array($items['d_c'])) ? $items['d_c'] : null;
            //dd($itemM);
            $data = [];
            foreach ($itemM as $key => $values) {
                $drCrKey = ($items['d_c'][$key] == 2) ? 'account_cr_id' : 'account_dr_id';
                $narration = ($key == 0) ? 'narration' : 'narration1';
                $data = $data + [
                        $drCrKey => $items['account'][$key],
                        'voucher_type' => $items['d_c'][$key],
                        'amount' => $items['amount'][$key],
                        $narration => $items['narration'][$key],
                    ];
            }

            $data = $data + [
                    'type_id' => $id,
                    'type' => transactionByKey('RV'),
                    'transaction_date' => $voucherDate,
                    'financial_year_id' => financialYearId(),
                    'created_by' => authUserId(),
                    'company_id' => loggedInCompanyId(),
                ];
            (new TransactionMaster)->storeVoucherTransaction($data);
        }/*
        elseif($inputs['cash_credit'] == 2) {
            if($id > 0) {
                (new Voucher)->dropVoucher(2, $id);
            }
        }*/
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id, $itemId = null)
    {
        $result = (new PurchaseInvoice)->getOrderDetail($id);
        $t = $request->get('t');
        $a = $request->get('a');
        if (!$result) {
            abort(404);
        }
        $bank = [];
        $items = [];
        $products = [];
        $unit = [];
        $bank = (new Bank)->getBankService();
        $items = (new PurchaseInvoiceItems)->getPurchasesItems(['purchase_order_id' => $id]);
        $products = (new Product)->getProductsService();
        $unit = (new Unit)->getUnitService();
        return view('purchase-invoice.edit', compact('result', 'items', 'products', 'bank', 'unit', 't', 'a'));
    }

    /**
     * @param $id
     * @return array|\Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        return $this->addMoreUpdateCommon($request, $id);
    }

    /**\
     * Update the specified resource in storage.
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function addMoreUpdateCommon(Request $request, $id) {
        $result = (new PurchaseInvoice)->company()->find($id);
        if (!$result) {
            return redirect()->route('purchase-invoice.index')
                ->with('error', lang('messages.invalid_id', string_manip(lang('purchase_invoice.purchase'))));
        }
        $inputs = $request->all();

        $a = ($inputs['a'] == 1) ? 1 : 0;
        $validator = (new PurchaseInvoice)->validatePurchase($inputs, $id);


        if ($validator->fails()) {
            return validationResponse(false, 206, "", "", $validator->messages());
        }

        if (isset($inputs['update_item']) && $inputs['update_item'] != null) {
            return $this->updateItem($id, $inputs);
        }
        try {
             //\DB::beginTransaction();
            $purchaseDate = $inputs['purchase_date'];
            $purchaseTime = dateFormat('H:i:s', $inputs['purchase_time']);
            $purchaseDate = dateFormat('Y-m-d H:i:s', $purchaseDate . ' ' . $purchaseTime);
            unset($inputs['purchase_date']);

            /*$orderDate = null;
            if($inputs['order_date'] != "") {
                $orderDate = $inputs['order_date'];
                $orderDate = dateFormat('Y-m-d', $orderDate);
            }*/

            $orderDate = dateFormat('Y-m-d', $purchaseDate);
            $account = $inputs['account'];
            unset($inputs['account']);

            $cgst = $sgst = $igst = $cgstRate = $sgstRate = $igstRate = $total = 0;

            if (isset($inputs['addmore'])) {
                /*$product = (new Product)->getProductEffectedTax($inputs['product'], convertToUtc($purchaseDate));
                if (!$product) {
                    $errors = collect(['' => [lang('messages.product_tax_not_defined')]]);
                    return validationResponse(false, 206, "", "", $errors);
                }

                $cgst = ($product->cgst_rate != "") ? $product->cgst_rate : 0;
                $sgst = ($product->sgst_rate != "") ? $product->sgst_rate : 0;
                $igst = ($product->igst_rate != "") ? $product->igst_rate : 0;*/
                //Demo Tax
                $cgst = 2.5;
                $sgst = 3.5;
                $igst = 6.0;

                $price = ($inputs['manual_price'] != "") ? $inputs['manual_price'] : $inputs['price'];
                $quantity = $inputs['quantity'];
                $total = round($price * $quantity, 2);

                $cgstRate = round(($total * $cgst) / 100, 2);
                $sgstRate = round(($total * $sgst) / 100, 2);
                $igstRate = round(($total * $igst) / 100, 2);
            }

            $inputs = $inputs + [
                    'purchase_date'     => convertToUtc($purchaseDate),
                    'order_date'        => ($orderDate != "") ? $orderDate : null,
                    'account_id'        => $account,

                    'cgst'              => $cgst,
                    'sgst'              => $sgst,
                    'igst'              => $igst,
                    'total_price'       => $total,

                    'cgst_total'       => $cgstRate,
                    'sgst_total'       => $sgstRate,
                    'igst_total'       => $igstRate,

                    'updated_by'        => authUserId(),
                    'financial_year_id' => financialYearId()
                ];
           /* dd($inputs);*/
            (new PurchaseInvoice)->store($inputs, $id, true);

            if ($inputs['purchase_type'] == 1) {
                $totalTax = $cgstRate + $sgstRate;
            } elseif($inputs['purchase_type'] == 2) {
                $totalTax = $igstRate;
            }
            $grossTotal = $inputs['gross_total'] + $total + $totalTax;
            $netTotal = ($grossTotal + $inputs['freight'] + $inputs['other_charges']) + $inputs['round_off'];
            /*$netRound = round($netTotal);
            $round = ($netRound - $netTotal);*/
            $update = [
                'gross_amount'  => $grossTotal,
                'net_amount'    => $netTotal,
                /*'net_amount'    => $netTotal + $round,*/
            ];
            (new PurchaseInvoice)->purchaseInvoiceUpdate($update, $id);
            /*$this->storeToTransaction($inputs, $account, $id, $netTotal + $round, $id);*/
            /*$purchaseNumber = '';
            $purchaseNumber = (new Purchase)->getPurchaseNumber($id);
            $inputs['ref_id'] = $id;
            $this->storeToVoucher($inputs, $account, $netTotal, $purchaseNumber['purchase_number'], $id);*/

            //\DB::commit();
            $route = route('purchase-invoice.index');
            $lang = lang('messages.updated', lang('purchase_invoice.purchase_invoice'));
            if(isset($inputs['save_adjust']) && !isset($inputs['addmore'])) {
                $route = route('purchase-invoice.order-adjustment', $id);
                $lang = lang('messages.updated',
                    lang('sale_invoice.sale_invoice'));
            }
            elseif (isset($inputs['addmore'])) {
                $param = ($a == 1) ? ['id' => $id, 't' => 'edit', 'a' => $a] : ['id' => $id, 't' => 'edit'];
                $route = route('purchase-invoice.edit', $param);
                $lang = lang('messages.itemadded', lang('purchase_invoice.purchase_invoice'));
            }
            return validationResponse(true, 201, $lang, $route);
        } catch (\Exception $exception) {
            //\DB::rollBack();
            return validationResponse(false, 207, lang('messages.server_error').$exception->getMessage());
        }
    }



    /**
     * @param $id
     * @param null $itemId
     * @return $this|\Illuminate\Http\RedirectResponse
     */
    public function editItem($id, $itemId = null)
    {
      $result = (new PurchaseInvoice)->company()->find($id);
      if (!$result) {
        abort(401);
      }
      // dd($result);
      $grossTotal = $result['gross_amount'];

      $items = (new PurchaseInvoiceItems)->getPurchaseItem(['id' => $itemId]);
      if (!$items) {
            return redirect()->route('purchase-invoice.edit', $id)
            ->with('error', lang('messages.invalid_id', string_manip(lang('purchase_invoice.purchase'))));
      }

      $totalPrice = $items['total_price'];
      $inputs = [
        'itemId'        => $itemId,
        'product'       => $items['product_id'],
        'hsn_code'      => $items['hsn_code'],
        'unit'          => $items['unit'],
        'tax_group'     => $items['tax_group'],
        'quantity'      => $items['quantity'],
        'price'         => $items['price'],
        'manual_price'  => $items["manual_price"],
        'tax_id'        => $items["tax_id"],
        'prevQty'       => $items['quantity'],
        'gross_total'   => $grossTotal,
      ];

      return redirect()->route('purchase-invoice.edit', ['id' => $id, 't' => 'edit'])
        ->with(array('totalPrice' => $totalPrice, 'productId' => $items['product_id'], 'update' => 1))
        ->withInput($inputs);
    }



    /**
     * @param $id
     * @param $inputs
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function updateItem($id, $inputs)
    {
        try {
            //\DB::beginTransaction();
            // default values
            $itemId = $inputs['itemId'];
            //$prevTotalPrice = $inputs['prevTotalPrice'];
            // purchase invoice updation

            $purchaseDate = $inputs['purchase_date'];
            $purchaseTime = dateFormat('H:i:s', $inputs['purchase_time']);
            $purchaseDate = dateFormat('Y-m-d H:i:s', $purchaseDate . ' ' . $purchaseTime);
            unset($inputs['purchase_date']);

            /*$orderDate = null;
            if($inputs['order_date'] != "") {
                $orderDate = $inputs['order_date'];
                $orderDate = dateFormat('Y-m-d', $orderDate);
            }
            unset($inputs['order_date']);*/
            $orderDate = dateFormat('Y-m-d', $purchaseDate);
            $productId = $inputs['product'];
            unset($inputs['product']);
            $account = $inputs['account'];
            unset($inputs['account']);

            //Old product assigned effected tax rates
            $oldPurchase = (new PurchaseInvoice)->company()->find($id);
            $oldPurchaseItems = (new PurchaseInvoiceItems)->where('id', $itemId)
                ->where('purchase_order_id', $id)
                ->first(
                    [
                        'cgst_amount',
                        'sgst_amount',
                        'igst_amount',
                        'total_price'
                    ]
                );

            $oldTotalTax = 0;
            if ($oldPurchase->purchase_type == 1) {
                $oldTotalTax = $oldPurchaseItems->cgst_amount + $oldPurchaseItems->sgst_amount;
            } elseif($oldPurchase->purchase_type == 2) {
                $oldTotalTax = $oldPurchaseItems->igst_amount;
            }

            $oldTotal = $oldPurchaseItems->total_price;
            $oldGrossAmount = $oldPurchase->gross_amount;
            $deductedOldGrossAmount = ($oldGrossAmount - $oldTotal - $oldTotalTax);

            //New product effected tax rates
            /*$product = (new Product)->getProductEffectedTax($productId, convertToUtc($purchaseDate));
            if (!$product) {
                $errors = collect(['' => [lang('messages.product_tax_not_defined')]]);
                return validationResponse(false, 206, "", "", $errors);
            }

            $cgst = ($product->cgst_rate != "") ? $product->cgst_rate : 0;
            $sgst = ($product->sgst_rate != "") ? $product->sgst_rate : 0;
            $igst = ($product->igst_rate != "") ? $product->igst_rate : 0;*/
            //Demo Tax
            $cgst = 2.5;
            $sgst = 3.5;
            $igst = 6.0;
            $newTotalPrice = 0;
            $price = ($inputs['manual_price'] != "") ? $inputs['manual_price'] : $inputs['price'];
            $quantity = $inputs['quantity'];
            $newTotalPrice = round($price * $quantity, 2);

            $cgstRate = round(($newTotalPrice * $cgst) / 100, 2);
            $sgstRate = round(($newTotalPrice * $sgst) / 100, 2);
            $igstRate = round(($newTotalPrice * $igst) / 100, 2);

            $itemArray = [
                'product_id'        => $productId,
                'cgst'              => $cgst,
                'sgst'              => $sgst,
                'igst'              => $igst,
                'cgst_amount'       => $cgstRate,
                'sgst_amount'       => $sgstRate,
                'igst_amount'       => $igstRate,
                'price'             => $inputs['price'],
                'manual_price'      => $inputs['manual_price'],
                'quantity'          => $inputs['quantity'],
                'total_price'       => $newTotalPrice
            ];
            //dd($itemArray);
            (new PurchaseInvoiceItems)->store($itemArray, $itemId);

            $newTotalTax = 0;
            if ($inputs['purchase_type'] == 1) {
                $newTotalTax = $cgstRate + $sgstRate;
            } elseif($inputs['purchase_type'] == 2) {
                $newTotalTax = $igstRate;
            }
            
            $newGrossAmount = $deductedOldGrossAmount + $newTotalPrice;
            $grossTotal = $newGrossAmount + $newTotalTax;

            $netTotal = ($grossTotal + $inputs['freight'] + $inputs['other_charges']) + $inputs['round_off'];

            $purchaseArray = $inputs + [
                'purchase_date' => convertToUtc($purchaseDate),
                'order_date'        => ($orderDate != "") ? $orderDate : null,
                'account_id'        => $account,
                'gross_amount' => $grossTotal,
                'net_amount' => $netTotal,
            ];
            // dd($purchaseArray);
            (new PurchaseInvoice)->purchaseInvoiceUpdate($purchaseArray, $id);
            $inputs['purchase_date'] = convertToUtc($purchaseDate);
            /*$this->storeToTransaction($inputs, $account, $id, $netTotal, $id);*/
            /*$purchaseNumber = '';
            $purchaseNumber = (new Purchase)->getPurchaseNumber($id);
            $inputs['ref_id'] = $id;
            $this->storeToVoucher($inputs, $account, $netTotal, $purchaseNumber['purchase_number'], $id);*/

            // stock master code
            /*(new StockMaster)->deleteStock($id, 2);
            $items = (new InvoiceItems)->getInvoicesItems(['invoice_id' => $id]);
            (new StockMaster)->saveStock($items, $id, 2);*/

            (new Stock)->delete(['type_id' => $id, 'type_item_id' => $itemId, 'type' => 1]);
            $stock = [
                'type' => 1,
                'type_id' => $id,
                'type_item_id' => $itemId,
                'product_id' => $productId,
                'stock_in' => $quantity,
                'stock_date' => convertToUtc()
            ];
            (new Stock)->store($stock);
            //\DB::commit();
            $route = route('purchase-invoice.edit', ['id' => $id, 't' => 'edit']);
            $lang = lang('messages.updated', lang('purchase_invoice.purchase_invoice_item'));
            return validationResponse(true, 201, $lang, $route);
        }
        catch (\Exception $exception) {
            //\DB::rollBack();
            return array('type' => 'error', 'message' => $exception->getMessage() . $exception->getFile() . $exception->getLine() . lang('messages.server_error'));
        }
    }

    /**
     * Used to load more records and render to view.
     *
     * @param int $pageNumber
     * @return \Illuminate\Http\Response
     */
    public function purchasePaginate(Request $request, $pageNumber = null)
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
            // $inputs = array_filter($inputs);
            unset($inputs['_token']);
            $data = (new PurchaseInvoice)->getPurchases($inputs, $start, $perPage);
            $total = (new PurchaseInvoice)->totalPurchases($inputs);
            $total = $total->total;
        } else {
            $data = (new PurchaseInvoice)->getPurchases($inputs, $start, $perPage);
            $total = (new PurchaseInvoice)->totalPurchases($inputs);
            $total = $total->total;
        }
        return view('purchase-invoice.load_data', compact('inputs', 'data', 'total', 'page', 'perPage'));
    }

    /**
     * @param $id
     * @param $itemId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function dropItem($id, $itemId)
    {

        try {
            //\DB::beginTransaction();
            $result = (new PurchaseInvoice)->company()->where('id', $id)->first();
            if (!$result) {
                return json_encode(array(
                    'status' => 0,
                    'message' => lang('messages.invalid_id', string_manip(lang('purchase_invoice.purchase')))
                ));
            }
            $resultItem = (new PurchaseInvoiceItems)->where('id', $itemId)
                                            ->where('purchase_order_id', $id)
                                            ->first([
                                                'cgst_amount',
                                                'sgst_amount',
                                                'igst_amount',
                                                'total_price'
                                            ]);

            if (count($resultItem) > 0)
            {
                if ($result->purchase_type == 1) {
                    $totalTax = $resultItem->cgst_amount + $resultItem->sgst_amount;
                } elseif($result->purchase_type == 2) {
                    $totalTax = $resultItem->igst_amount;
                }

                $total = $resultItem->total_price;
                $grossAmount = $result->gross_amount;

                $deductedGrossAmount = ($grossAmount - $total - $totalTax);
                $grossTotal = $deductedGrossAmount;
                $netTotal = ($result->net_amount - $total - $totalTax);

                $update = [
                    'gross_amount' => $grossTotal,
                    'net_amount' => $netTotal,
                ];
                //dd($result);
                (new PurchaseInvoice)->purchaseInvoiceUpdate($update, $id);
                (new PurchaseInvoiceItems)->dropItem($itemId);
                /*$this->storeToTransaction($result->toArray(), $result['account_id'], $id, $netTotal, $id);*/
                /*$result['ref_id'] = $id;
                $this->storeToVoucher($result->toArray(), $result['account_id'], $netTotal, $result['purchase_number'], $id);*/
                (new Stock)->delete(['type_id' => $id, 'type_item_id' => $itemId, 'type' => 1]);
                //\DB::commit();
                return json_encode(array(
                    'status' => 1,
                    'message' => lang('messages.itemDeleted', lang('purchase_invoice.purchase_invoice'))
                ));
            }

        }
        catch (\Exception $exception) {
            //\DB::rollBack();
            return json_encode(array( 'status' => 0, 'message' => lang('messages.invalid_id', string_manip(lang('purchase_invoice.purchase_invoice'))) ));
        }
    }

    /**
     * @param $id
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
   /* public function invoice($id)
    {
        $result = (new Invoice)->company()->find($id);
        if (!$result) {
            abort(401);
        }

        $customer = (new Customer)->getCustomerInfo($result->customer_id);
        $orderItems = (new InvoiceItems)->getInvoicesItems(['invoice_id' => $id]);
        return view('purchase.invoice', compact('id', 'customer', 'result', 'orderItems'));
    }*/

    /**
     * @param $id
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    /*public function purchasePrint($id)
    {
        $result = (new Purchase)->company()->find($id);
        if (!$result) {
            abort(401);
        }
        $companyId = loggedInCompanyId();
        $company = Company::find($companyId);
        $bank = Bank::find($result->bank_id);
        $account = (new Account)->getAccountInfo($result->account_id);
        $orderItems = (new PurchaseItems)->getPurchasesItems(['purchase_id' => $id]);

        $taxes = ['cgst' => [], 'sgst' => []];
        foreach ($orderItems as $key => $values) {
            if(!in_array("'" . $values->cgst . "'", array_keys($taxes['cgst']))) {
                $taxes['cgst'] =  $taxes['cgst'] + ["'" . $values->cgst . "'" => $values->cgst_amount];
            } else {
                $taxes['cgst']["'" . $values->cgst . "'"] = $taxes['cgst']["'" . $values->cgst . "'"] + $values->cgst_amount;
            }

            if(!in_array("'" . $values->sgst . "'", array_keys($taxes['sgst']))) {
                $taxes['sgst'] =  $taxes['sgst'] + ["'" . $values->sgst . "'" => $values->sgst_amount];
            } else {
                $taxes['sgst']["'" . $values->sgst . "'"] = $taxes['sgst']["'" . $values->sgst . "'"] + $values->sgst_amount;
            }
        }

        return view('purchase.purchase_print', compact('id', 'account', 'result', 'orderItems',
            'company' , 'bank', 'taxes'));
    }*/

    /**
     * @param $id
     *
     * @return string
     */
    public function drop($id)
    {
        if (!\Request::ajax()) {
            return lang('messages.server_error');
        }

        $result = (new Purchase)->company()->find($id);
        if (!$result) {
            abort(401);
        }

        try {
            \DB::beginTransaction();
            //(new Purchase)->drop($id);
            //(new PurchaseItems)->drop($id);
            // (new Stock)->delete(['type_id' => $id, 'type' => 1]);
            \DB::commit();
            /*(new StockMaster)->deleteStock($id, 2, null);*/
            $response = ['status' => 1, 'message' => lang('messages.deleted', lang('purchase.purchase'))];

        } catch (\Exception $exception) {
            \DB::rollBack();
            $response = ['status' => 0, 'message' => lang('messages.server_error')];
        }
        // return json response
        return json_encode($response);
    }

    /**
     * @param $id
     * @return mixed
     */
    /*public function generatePdfInvoice($id)
    {
        $result = (new Invoice)->company()->find($id);
        if (!$result) {
            abort(401);
        }

        $companyId = loggedInCompanyId();
        $company = Company::find($companyId);

        $bank = Bank::find($result->bank_id);
        $customer = (new Customer)->getCustomerInfo($result->customer_id);
        $orderItems = (new InvoiceItems)->getInvoicesItems(['invoice_id' => $id]);
        $pdf = \PDF::loadView('purchase.invoice_print', ['id' => $id, 'customer' => $customer, 'result' => $result, 'orderItems' => $orderItems, 'company' => $company, 'bank' => $bank, 'pdf' => 1]);
        return $pdf->stream();
        //return view('invoice.invoice_print', compact('id', 'customer', 'result', 'orderItems'));
    }*/

    /**
     * @param $id
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    /*public function sendEmail($id = null)
    {
        $inputs = \Input::all();
        if (count($inputs) > 0) {
            $result = (new Invoice)->company()->find($id);
            if (!$result) {
                abort(401);
            }

            $customer = (new Customer)->getCustomerInfo($result->customer_id);
            if ($customer->email1 == "") {
                return redirect()->route('purchase.index')
                    ->with('error', lang('sale_invoice.customer_email_not_found'));
            }
            $customer['message'] = $inputs['message'];
            return (new Invoice)->sendEmailToCustomer($id, $result, $customer);
        } else {
            return view('purchase.send_email_modal', compact('id'));
        }
    }*/

    /**
     * method is used to view popup item detail
     * @param null $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    /*public function invoiceItemDetail($id = null)
    {
        $items = [];
        $invoice = [];
        if($id) {
            $invoice = (new Invoice)->company()->find($id);
            $items = (new InvoiceItems)->getInvoicesItems(['invoice_id' => $id]);
            if (!$invoice) {
                abort(401);
            }

        }
        return view('purchase.invoice_item_detail_modal', compact('invoice', 'items'));
    }*/
    /**
     * method is used to set Purchase Type
     */
    public function setPurchaseType($accountId){
        $accountStateId  = (new Account)->getStateId($accountId);
        $companyStateId  = (new Company)->getStateId(loggedInCompanyId());
        if($accountStateId['state_id'] == $companyStateId['state_id']){
            return json_encode(['purchaseType' => 1 ]);
        }
        return json_encode(['purchaseType' => 2]);
    }
}
