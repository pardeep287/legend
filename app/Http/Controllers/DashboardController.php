<?php
namespace App\Http\Controllers;

/**
 * :: Dashboard Controller ::
 * To manage dashboard settings.
 *
 **/

use App\Account;
use App\Http\Controllers\Controller;
use App\Invoice;
use App\InvoiceItems;
use App\Product;
use App\Purchase;
use App\PurchaseItems;
use App\SaleInvoice;
use App\TransactionMaster;
use App\Unit;
use App\User;
use App\Voucher;
use App\Transaction;
use App\VoucherEntry;
use App\SaleOrder;
class DashboardController extends Controller
{
    /**
     * Display a dashboard view.
     *
     * @return Response
     */
    public function index()
    {
        //dd('df');
        return view('dashboard');
        /*$saleOrderStatuses = []; //(new SaleOrder)->getSaleOrderStatusProgress();
        $latestInvoices = (new Invoice)->getInvoices([], null, 10);
        $pendingOrders = [];//(new SaleOrder)->getSaleOrder(['status' => 0], null, 10);
        $resultArray = ['total_amount' => 0, 'total_amount_yearly' => 0];
        $month = date('m');
        $year = date('Y');

        $start = convertToUtc(date('Y-m-01 00:00:00'));
        $end = date('Y-m-t 23:59:59');
        $result = (new Invoice)->getTotalAmount([] , $start, $end);
        if (count($result) > 0) {
            $resultArray['total_amount'] = $result->total;
        }

        $currentYear = financialYearStartEnd();
        if(is_object($currentYear)) {
            $start = convertToUtc(date($currentYear->from_date . '00:00:00'));
            $end = convertToUtc(date($currentYear->to_date . '23:59:59'));
            $result = (new Invoice)->getTotalAmount([], $start, $end);
            if (count($result) > 0) {
                $resultArray['total_amount_yearly'] = $result->total;
            }
        }

        $resultInvoiceArray = ['total_invoices' => 0];
        $resultInvoice = (new Invoice)->getAllInvoice([], $start, $end);
        if (count($resultInvoice) > 0) {
            $resultInvoiceArray = ['total_invoices' => $resultInvoice->total_invoices];
        }

        $monthlyInvoices = (new SaleInvoice)->getMonthWiseInvoice();
        $financialYearInvoices = (new SaleInvoice)->getFinancialYearWiseInvoice();

        $totalReceivableAmount = $totalPayableAmount = 0;
        $totalReceivable = (new TransactionMaster)->accountStatementGroupWise(['account_group' => 33]);
        if(count($totalReceivable) > 0) {
            $crTotal = array_sum(array_column($totalReceivable, 'amount_cr'));
            $drTotal = array_sum(array_column($totalReceivable, 'amount_dr'));
            $totalReceivableAmount = ($crTotal - $drTotal);
        }

        $totalPayable = (new TransactionMaster)->accountStatementGroupWise(['account_group' => 32]);
        if(count($totalPayable) > 0) {
            $crTotal = array_sum(array_column($totalPayable, 'amount_cr'));
            $drTotal = array_sum(array_column($totalPayable, 'amount_dr'));
            $totalPayableAmount = abs($crTotal - $drTotal);
        }
        return view('dashboard', compact('saleOrderStatuses', 'latestInvoices',
            'resultArray', 'resultInvoiceArray', 'monthlyInvoices', 'financialYearInvoices',
            'pendingOrders', 'totalPayableAmount', 'totalReceivableAmount')
        );*/
    }

    /**
     * used to display change password form
     * @return \Illuminate\View\View
     */
    public function changePasswordForm()
    {
        return view('changepassword');
    }

    /**
     * update password
     * @return $this|\Illuminate\Http\RedirectResponse
     */
    public function changePassword()
    {
        $inputs = \Input::all();
        $password = \Auth::user()->password;
        if(!(\Hash::check($inputs['old_password'], $password))){
            return redirect()->route('changepassword')
                ->with("error", "Incorrect Old Password.");
        }

        $validator = (new user)->validatePassword($inputs);
        if ($validator->fails()) {
            return redirect()->route('changepassword')
                ->withErrors($validator);
        }

        if ((new user)->updatePassword(\Hash::make($inputs['new_password']))) {
            return redirect()->route('changepassword')
                ->with("success", "Password Successfully Updated.");
        } else {
            return redirect()->route('changepassword')
                ->withErrors("Internal Server Error");
        }
    }

    /*public function testdump()
    {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 0);

        \DB::beginTransaction();
        $units = Unit::lists('id', 'code')->toArray();
        $taxes = [
            '2.5' => 1,
            '6' => 2,
            '9' => 3,
            '14' => 4,
            '0' => 5
        ];

        $i = \Input::get('i', 0);
         // if($i > 0) {
            // $r = \DB::select('select si.*, c.customer_name as cn from erp_girdhar.sale_invoice as si left join erp_girdhar.customer c on (c.id = si.customer_id) where invoice_number = '. $i .' and si.deleted_at IS NULL');
            $r = \DB::select('select si.*, c.customer_name as cn from erp_girdhar.sale_invoice as si left join erp_girdhar.customer c on (c.id = si.customer_id) where si.deleted_at IS NULL');

            foreach ($r as $detail) {
                $d = (array)$detail;
                unset($d['id']);

                $account = (new Account)->getAccount($d['cn']);

                $saveInvoice = [
                    'invoice_number' => $d['invoice_number'],
                    'company_id' => loggedInCompanyId(),
                    'financial_year_id' => financialYearId(),
                    'account_id' => $account,
                    'bank_id' => $d['bank_id'],
                    'cash_credit' => 2,
                    'invoice_date' => $d['invoice_date'],
                    'through' => 'Direct',
                    'sale' => 1,
                    'freight' => $d['freight'],
                    'other_charges' => $d['other_charges'],
                ];

                //echo "select inv.*, p.product_name from erp_gk2.invoice_items as inv left join erp_gk2.product as p on (inv.product_id = p.id) where invoice_id = '" . $detail->id . "'";
                //echo " --- ";
                // $iId = (new Invoice)->store($saveInvoice);

                $iId = Invoice::create($saveInvoice)->id;
                $items = \DB::select("select * from erp_girdhar.sale_invoice_items where sale_invoice_id = '" . $detail->id . "' and deleted_at IS NULL");

                $totalCgst = $totalSgst = $totalIgst = $grossTotal = $netTotal = $round = 0;
                foreach ($items as $iItem) {
                    $rI = (array)$iItem;
                    $cgst = $rI['cgst'];
                    $sgst = $rI['sgst'];
                    $igst = $cgst + $sgst;
                    $qty = $rI['quantity'];
                    $price = $rI['manual_price'];
                    $total = ($qty * $price);

                    $cgstAmount = ($total * $cgst) / 100;
                    $sgstAmount = ($total * $sgst) / 100;
                    $igstAmount = ($total * $igst) / 100;
                    $totalCgst += $cgstAmount;
                    $totalSgst += $sgstAmount;
                    $totalIgst += $igstAmount;
                    $grossTotal += $total;

                    $taxId = (array_key_exists((string)$cgst, $taxes)) ? $taxes[(string)$cgst] : 0;
                    $rItem = [
                        'product_name' => $rI['product'],
                        'unit_id' => $rI['unit_id'],
                        'hsn_code' => $rI['hsn_code'],
                        'invoice_id' => $iId,
                        'tax_id' => $taxId,
                        'cgst' => $cgst,
                        'sgst' => $sgst,
                        'igst' => $igst,
                        'cgst_amount' => $cgstAmount,
                        'sgst_amount' => $sgstAmount,
                        'igst_amount' => $igstAmount,
                        'quantity' => $qty,
                        'price' => 0,
                        'manual_price' => $price,
                        'total_price' => $total,
                        'company_id' => loggedInCompanyId(),
                        'financial_year_id' => financialYearId(),
                    ];

                    $productId = (new Product)->productCreate($rItem);
                    $rItem = $rItem + $productId;
                    // dd($rItem);
                    unset($rItem['unit_id']);
                    InvoiceItems::create($rItem);
                }


                $netTotal = $grossTotal + $totalIgst + $d['freight'] + $d['other_charges'];
                $netRound = round($netTotal);
                $round = ($netRound - $netTotal);

                $updateInvoice = [
                    'cgst_total' => $totalCgst,
                    'sgst_total' => $totalSgst,
                    'igst_total' => $totalIgst,
                    'gross_amount' => $grossTotal,
                    'round_off' => $round,
                    'net_amount' => $netTotal + $round,
                ];
                Invoice::find($iId)->update($updateInvoice);

                echo "Inserted: " . $d['invoice_number'] . '</br/></br/>';
            }
            \DB::commit();
           }
    }*/


    public function testdump()
    {
        try {
            ini_set('memory_limit', '-1');
            ini_set('max_execution_time', 0);
            \DB::beginTransaction();

            $taxes = [
                '2.5' => 1,
                '6' => 2,
                '9' => 3,
                '14' => 4,
                '0' => 5
            ];

            $query1 = \DB::select('select * from erp_girdhar_fetch.purchase_master where id < 2 and erp_girdhar_fetch.purchase_master.deleted_at is null');

             foreach($query1 as $row)
             {
                 $d = (array)$row;
                 unset($d['id']);
                 //dd($row);
                 $purchaseMaster = [
                     'company_id'   => $row->company_id,
                     'financial_year_id' => financialYearId(),
                     'purchase_number' => $row->purchase_number,
                     'cash_credit' => $row->cash_credit,
                     'account_id' => $row->account_id,
                     'purchase_type' => $row->purchase_type,
                     'purchase_date' => $row->purchase_date,
                     'invoice_number' => $row->invoice_number,
                     'order_number' => $row->order_number,
                     'order_date' => $row->order_date,
                     'carriage' => $row->carriage,
                     'through' => $row->through,
                     'vehicle_no' => $row->vehicle_no,
                     'other_charges' => $row->other_charges,
                     'status' => $row->status,
                 ];

                 $purchaseId = Purchase::create($purchaseMaster)->id;

                 $purchaseItems = \DB::select("
                                    select fitem.*, fp.product_name, fp.unit_id, fhsn.hsn_code, fp.tax_id  from erp_girdhar_fetch.purchase_items as fitem
                                    left join erp_girdhar_fetch.product as fp on
                                    fitem.product_id = fp.id
                                    left join  erp_girdhar_fetch.hsn_master as fhsn on
                                    fhsn.id = fp.hsn_id
                                    where fitem.purchase_id = '" . $row->id . "' and fitem.deleted_at IS NULL
                 ");

                 //$purchaseItems = \DB::select("select pi.*, p.product_name, p.id as pid from erp_girdhar_fetch.purchase_items as pi left join erp_girdhar_fetch.product as p on pi.product_id = p.id  where pi.purchase_id = ". $row->id ." and pi.deleted_at is null");

                 if(count($purchaseItems) > 0) {
                     $totalCgst = $totalSgst = $totalIgst = $grossTotal = $netTotal = $round = 0;
                     foreach($purchaseItems as $purchaseItem){

                         //dd($purchaseItem);
                         $rI = (array)$purchaseItem;
                         $cgst = $rI['cgst'];
                         $sgst = $rI['sgst'];
                         $igst = $cgst + $sgst;
                         $qty = $rI['quantity'];
                         $price = $rI['manual_price'];
                         $total = ($qty * $price);

                         $cgstAmount = ($total * $cgst) / 100;
                         $sgstAmount = ($total * $sgst) / 100;
                         $igstAmount = ($total * $igst) / 100;



                         $totalCgst += $cgstAmount;
                         $totalSgst += $sgstAmount;
                         $totalIgst += $igstAmount;
                         $grossTotal += $total;
                         /* fetching the products  */
                         $taxId = (array_key_exists((string)$cgst, $taxes)) ? $taxes[(string)$cgst] : 0;
                         $rItem = [
                             'product_name' => $purchaseItem->product_name,
                             'unit_id' => $purchaseItem->unit_id,
                             'hsn_code' => $purchaseItem->hsn_code,
                             'purchase_id' => $purchaseId,
                             'tax_id' => $taxId,
                             'cgst' => $cgst,
                             'sgst' => $sgst,
                             'igst' => $igst,
                             'cgst_amount' => $cgstAmount,
                             'sgst_amount' => $sgstAmount,
                             'igst_amount' => $igstAmount,
                             'quantity' => $qty,
                             'price' => 0,
                             'manual_price' => $price,
                             'total_price' => $total
                         ];

                         $productId = (new Product)->productCreate($rItem);
                         $rItem = $rItem + $productId;
                         unset($rItem['unit_id']);
                         PurchaseItems::create($rItem);
                     }

                     $netTotal = $grossTotal + $totalIgst + $d['freight'] + $d['other_charges'];
                     $netRound = round($netTotal);
                     $round = ($netRound - $netTotal);
                     $updateInvoice = [
                         'cgst_total' => $totalCgst,
                         'sgst_total' => $totalSgst,
                         'igst_total' => $totalIgst,
                         'gross_amount' => $grossTotal,
                         'round_off' => $round,
                         'net_amount' => $netTotal + $round,
                     ];
                     Purchase::find($purchaseId)->update($updateInvoice);
                     echo "Inserted: ";
                 }
             }

            \DB::commit();
        }
        catch(\Exception $exception)
        {
            \DB::rollBack();
            echo $exception->getMessage();
        }
    }
    /*public function reportFix()
    {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 0);
        $r = Invoice::all();//where('id', '<', 3)->get();
        $r = $r->toArray();
        //echo "<pre>";
        foreach($r as $detail) {
            //echo "<br/><br/><br/>";
            (new InvoiceController)->storeToTransaction($detail, $detail['account_id'], $detail['id'], $detail['net_amount']);
        }
        echo "done";
    }*/

    public function invoiceDump()
    {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 0);

        \DB::beginTransaction();
        $units = Unit::lists('id', 'code')->toArray();
        $taxes = [
            '2.5' => 1,
            '6' => 2,
            '9' => 3,
            '14' => 4,
            '0' => 5
        ];

        $i = \Input::get('i', 0);
        if($i > 0) {
            // $r = \DB::select('select si.*, c.customer_name as cn from erp_girdhar.sale_invoice as si left join erp_girdhar.customer c on (c.id = si.customer_id) where invoice_number = '. $i .' and si.deleted_at IS NULL');

            $r = \DB::select('select fim.* from erp_girdhar_fetch.invoice_master as fim where fim.id = 1 and fim.deleted_at IS NULL');

            foreach ($r as $detail) {
                $d = (array)$detail;
                // unset($d['id']);

                //$account = (new Account)->getAccount($d['cn']);

                $saveInvoice = [
                    'invoice_number' => $d['invoice_number'],
                    'company_id' => loggedInCompanyId(),
                    'financial_year_id' => financialYearId(),
                    'account_id' => $d['account_id'],
                    'bank_id' => $d['bank_id'],
                    'cash_credit' => 2,
                    'invoice_date' => $d['invoice_date'],
                    'through' => 'Direct',
                    'sale' => 1,
                    'freight' => $d['freight'],
                    'other_charges' => $d['other_charges'],
                ];

                $iId = Invoice::create($saveInvoice)->id;
                // $items = \DB::select("select * from erp_girdhar.sale_invoice_items where sale_invoice_id = '" . $detail->id . "' and deleted_at IS NULL");
                $items = \DB::select("select fitem.*, fp.product_name, fp.unit_id, fhsn.hsn_code, fp.tax_id  from erp_girdhar_fetch.invoice_items as fitem

                      left join erp_girdhar_fetch.product as fp on
                      fitem.product_id = fp.id

                      left join  erp_girdhar_fetch.hsn_master as fhsn on
                      fhsn.id = fp.hsn_id

                      where fitem.invoice_id = '" . $d['id'] . "' and fitem.deleted_at IS NULL");

                $totalCgst = $totalSgst = $totalIgst = $grossTotal = $netTotal = $round = 0;

                foreach ($items as $iItem) {
                    $rI = (array)$iItem;
                    $cgst = $rI['cgst'];
                    $sgst = $rI['sgst'];
                    $igst = $cgst + $sgst;
                    $qty = $rI['quantity'];
                    $price = $rI['manual_price'];
                    $total = ($qty * $price);

                    $cgstAmount = ($total * $cgst) / 100;
                    $sgstAmount = ($total * $sgst) / 100;
                    $igstAmount = ($total * $igst) / 100;

                    /*-----------Gross amount---------------*/

                    $totalCgst += $cgstAmount;
                    $totalSgst += $sgstAmount;
                    $totalIgst += $igstAmount;
                    $grossTotal += $total;

                    $taxId = (array_key_exists((string)$cgst, $taxes)) ? $taxes[(string)$cgst] : 0;
                    /*---------- Product Code End -----------------*/
                    $rItem = [
                        'product_name' => $rI['product_name'],
                        'unit_id' => $rI['unit_id'],
                        'hsn_code' => $rI['hsn_code'],
                        'invoice_id' => $iId,
                        'tax_id' => $taxId,
                        'cgst' => $cgst,
                        'sgst' => $sgst,
                        'igst' => $igst,
                        'cgst_amount' => $cgstAmount,
                        'sgst_amount' => $sgstAmount,
                        'igst_amount' => $igstAmount,
                        'quantity' => $qty,
                        'price' => 0,
                        'manual_price' => $price,
                        'total_price' => $total,
                        'company_id' => loggedInCompanyId(),
                        'financial_year_id' => financialYearId(),
                    ];

                    $productId = (new Product)->productCreate($rItem);
                    $rItem = $rItem + $productId;
                    // dd($rItem);
                    unset($rItem['unit_id']);
                    InvoiceItems::create($rItem);
                }
                /* update invoice*/

                $netTotal = $grossTotal + $totalIgst + $d['freight'] + $d['other_charges'];
                $netRound = round($netTotal);
                $round = ($netRound - $netTotal);

                $updateInvoice = [
                    'cgst_total' => $totalCgst,
                    'sgst_total' => $totalSgst,
                    'igst_total' => $totalIgst,
                    'gross_amount' => $grossTotal,
                    'round_off' => $round,
                    'net_amount' => $netTotal + $round,
                ];
                Invoice::find($iId)->update($updateInvoice);

                echo "Inserted: " . $d['invoice_number'] . '</br/></br/>';
            }
            \DB::commit();
        }
    }
}