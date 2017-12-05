<?php

namespace App\Http\Controllers;

/**
 * :: Bank Controller ::
 * To manage bank.
 *
 **/
use Illuminate\Http\Request;
use App\Bank;
use App\GroupHead;
use App\Http\Controllers\Controller;
use App\Invoice;
use App\SaleInvoice;
use App\SaleOrder;
use Maatwebsite\Excel\Facades\Excel;
use App\TempBankStatement;
use App\BankStatement;

class BankController extends Controller
{
    private $thisData = [];

    /**
     * Display a listing of the resource.
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('bank.index');
    }

    /**
     * Show the form for creating a new resource.
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('bank.create');
    }

    /**
     * Store a newly created resource in storage.
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $inputs = $request->all();
        $validator = (new Bank)->validateBank($inputs);

        if ($validator->fails()) {
            return validationResponse(false, 206, "", "", $validator->messages());
        }

        try {

            if (!array_key_exists('status', $inputs)) {
                $inputs = $inputs + ['status' => 0];
            }

            $inputs = $inputs + [
                'company_id' => loggedInCompanyId(),
                'created_by' => \Auth::user()->id
            ];
            (new Bank)->store($inputs);

            $route = route('bank.index');
            $lang = lang('messages.created', lang('bank.bank'));
            return validationResponse(true, 201, $lang, $route);


        } catch (\Exception $exception) {
            return validationResponse(false, 207, lang('messages.server_error'));

        }
    }

    /**
     * Show the form for editing the specified resource.
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $bank = (new Bank)->company()->find($id);
        if (!$bank) {
            abort(401);
        }
        return view('bank.edit', compact('bank'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $bank = (new Bank)->company()->find($id);
        if (!$bank) {
            $route = route('bank.index');
            $lang = lang('messages.invalid_id', string_manip(lang('bank.bank')));
            return validationResponse(false, 206, $lang, $route);
        }

        $inputs = $request->all();
        $validator = (new Bank)->validateBank($inputs, $id);
        if ($validator->fails()) 
        {
            return validationResponse(false, 206, "", "", $validator->messages());
        }

        try {

            if (!array_key_exists('status', $inputs)) {
                $inputs = $inputs + ['status' => 0];
            }

            $inputs = $inputs + [
                'company_id' => \Auth::user()->company_id,
                'created_by' => \Auth::user()->id
            ];
            (new Bank)->store($inputs, $id);
            $route = route('bank.index');
            $lang = lang('messages.updated', lang('bank.bank'));
            return validationResponse(true, 201, $lang, $route);
        } catch (\Exception $exception) {
            return validationResponse(false, 207, lang('messages.server_error'));
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function drop($id)
    {
        if (!\Request::ajax()) {
            return lang('messages.server_error');
        }

        try {
            $existsInInvoice = (new Invoice)->where('bank_id', $id)->first();
            if ($existsInInvoice) {
                $response = ['status' => 0, 'message' => lang('bank.bank_in_use')];
            } else {
                (new Bank)->drop($id);
                $response = ['status' => 1, 'message' => lang('messages.deleted', lang('bank.bank'))];
            }
        } catch (Exception $exception) {
            $response = ['status' => 0, 'message' => lang('messages.server_error')];
        }
        return json_encode($response);
    }

    /**
     * Used to update bank active status.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function bankToggle($id)
    {
        if (!\Request::isMethod('post') && !\Request::ajax()) {
            return lang('messages.server_error');
        }

        try {
            $bank = (new Bank)->company()->find($id);
        } catch (\Exception $exception) {
            return lang('messages.invalid_id', string_manip(lang('bank.bank')));
        }

        $bank->update(['status' => !$bank->status]);
        $response = ['status' => 1, 'data' => (int)$bank->status . '.gif'];
        return json_encode($response);
    }

    /**
     * Used to load more records and render to view.
     * @param int $pageNumber
     * @return \Illuminate\Http\Response
     */
    public function bankPaginate(Request $request, $pageNumber = null)
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

            $data = (new Bank)->getBanks($inputs, $start, $perPage);
            $totalCurrency = (new Bank)->totalBanks($inputs);
            $total = $totalCurrency->total;
        } else {
            $data = (new Bank)->getBanks($inputs, $start, $perPage);
            $totalCurrency = (new Bank)->totalBanks($inputs);
            $total = $totalCurrency->total;
        }
        return view('bank.load_data',
            compact('data', 'total', 'page', 'perPage', 'inputs'));
    }

    /**
     * Method is used to update status of bank enable/disable
     * @return \Illuminate\Http\Response
     */
    public function bankAction(Request $request)
    {
        $inputs = $request->all();
        if (!isset($inputs['tick']) || count($inputs['tick']) < 1) {
            return redirect()->route('bank.index')
                ->with('error', lang('messages.atleast_one', string_manip(lang('bank.bank'))));
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

        Bank::whereRaw('id IN (' . $ids . ')')->update(['status' => $status]);
        return redirect()->route('bank.index')
            ->with('success', lang('messages.updated', lang('bank.bank_status')));
    }


    /**
     * Display a bank statement listing.
     * @return \Illuminate\Http\Response
     */
    public function bankStatement()
    {
        return view('bank.bank_statement');
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    public function uploadStatement(Request $request)
    {
        $inputs = $request->all();

        if (count($inputs) > 0) {
            $validator = (new Bank)->validateUploadStatement($inputs);

            if ($validator->fails()) {
                return validationResponse(false, 206, "", "", $validator->messages());
            }

            $bankID = $inputs['bank'];
            $tempNo = (new TempBankStatement)->getNewTempNumber();
            ini_set('memory_limit', '-1');
            Excel::load($inputs['file'], function ($reader) use ($tempNo, $bankID) {
                $i = 1;
                try {
                    $reader->each(function ($sheet) use ($i, $tempNo, $bankID) {

                        $date = str_replace('/', '-', $sheet->date);
                        $this->thisData[] = [
                            'bank_id' => $bankID,
                            'bank_account' => $sheet->bank_account,
                            'temp_no' => $tempNo,
                            'date' => dateFormat('Y-m-d', $date),
                            'narrative' => $sheet->narrative,
                            'debit_amount' => ($sheet->debit_amount > 0) ? $sheet->debit_amount : 0.00,
                            'credit_amount' => ($sheet->credit_amount > 0) ? $sheet->credit_amount : 0.00,
                            'category' => $sheet->categories,
                            'serial' => $sheet->serial
                        ];

                    });
                } catch (\Exception $e) {
                    \DB::rollBack();
                    return redirect()->back()
                        ->with('error', lang('messages.server_error'));
                }
            });
            (new TempBankStatement)->uploadStatement($this->thisData);
            $route = route('bank.list_upload_statement', $tempNo);
            $lang = lang('common.view_heading', lang('bank.bank_statement_list'));
            return validationResponse(true, 201, $lang, $route);
        }

        $banks = (new Bank)->getBankService();
        return view('bank.upload_statement', compact('banks'));
    }

    /**
     * used to view uploaded bank statement
     * @param $tempNo
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function viewUploadStatement($tempNo)
    {
        $tempBankStatement = (new TempBankStatement)->getTempBankStatement($tempNo);
        $bankAccounts = (new GroupHead)->getGroupHeadService();
        return view('bank.list_upload_statement', compact('tempBankStatement', 'bankAccounts', 'banks'));
    }

    /**
     * Used to save bank statement from temp_bank_statement into bank_statement
     * @return \Illuminate\Http\RedirectResponse
     */
    public function saveStatement(Request $request)
    {
        $inputs = $request->all();
        $statements = array_filter($inputs['statement']);
        $saveArray = [];
        if (count($statements) < 1) {
            return redirect()->back()
            ->withInput($inputs)
            ->with('error', lang('bank.please_select_atleast_single_record'));
        }

        try {
            \DB::beginTransaction();
            foreach ($statements as $key => $value) {
                if (isset($statements[$key]) && $value != '') {
                    $tempBankStatement = TempBankStatement::find($key);
                    $updateArray = [
                        'id' => $tempBankStatement->id,
                        'is_processed' => '1'
                    ];

                    (new TempBankStatement)->updateProcessedStatus($updateArray);

                    $saveArray[] = array(
                        'bank_account' => $tempBankStatement->bank_account,
                        'date' => $tempBankStatement->date,
                        'narrative' => $tempBankStatement->narrative,
                        'debit_amount' => $tempBankStatement->debit_amount,
                        'credit_amount' => $tempBankStatement->credit_amount,
                        'category' => $tempBankStatement->category,
                        'serial' => $tempBankStatement->serial,
                        'bank_id' => $tempBankStatement->bank_id,
                        'bank_account_id' => $value
                    );
                }
            }
            (new BankStatement)->saveStatement($saveArray);
            \DB::commit();
            return redirect()->route('bank.bank_statement')
                ->with('success', lang('messages.updated', lang('bank.bank_statement')));
        } catch (Exception $e) {
            \DB::rollback();
            return redirect()->back()
                ->with('error', lang('messages.server_error'));
        }
    }

    /**
     * used for bank statement pagination
     * @param array $inputs
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View|String
     */
    public function bankStatementPaginate(Request $request, $inputs = [])
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
            $data = (new TempBankStatement)->getTempBankDetail($inputs, $start, $perPage);
            $total = (new TempBankStatement)->totalTempBankDetail($inputs);
        } else {
            $data = (new TempBankStatement)->getTempBankDetail($inputs, $start, $perPage);
            $total = (new TempBankStatement)->totalTempBankDetail($inputs);
        }
        return view('bank.load_bank_statement_data', compact('inputs', 'data', 'total', 'page', 'perPage'));
    }
}