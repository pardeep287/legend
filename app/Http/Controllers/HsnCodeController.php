<?php

namespace App\Http\Controllers;
/**
 * :: HSN Code Controller ::
 * To manage HSN Code.
 *
 **/
use App\HsnCode;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
//use Maatwebsite\Excel\Facades\Excel;


class HsnCodeController extends Controller
{
    public $data = [];
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('hsn-code.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('hsn-code.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $inputs = $request->all();
        $validator = (new HsnCode)->validateHsnCodes($inputs);
        if ($validator->fails()) {
            return validationResponse(false, 206, "", "", $validator->messages());
        }
        try {
            \DB::beginTransaction();
            if( !array_key_exists('status', $inputs) ) {
                $inputs = $inputs + [ 'status' =>  0];
            }
            $inputs = $inputs + [
                    'company_id'    => loggedInCompanyId(),
                    'created_by'    => authUserId(),
                ];

            $id = (new HsnCode)->store($inputs);
            $submitData = [];
            if(!empty($inputs['isAjax'])) {
                $submitData = ['id' => $id, 'name' => $inputs['hsn_code']];
            }
            $route = route('hsn-code.index');
            $lang = lang('messages.created', lang('hsn_code.hsn_code'));
            \DB::commit();
            return validationResponse(true, 201, $lang, $route, [], $submitData);
        }
        catch (\Exception $exception) {
            \DB::rollBack();
            return validationResponse(false, 207, lang('messages.server_error'));
        }
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id = null)
    {
        try{
            $data = (new HsnCode)->getHsnCodes([],'','',$id);
            if(!$data){
                return validationResponse(false, 207, lang('messages.no_data_found'));
            }
            return view('hsn-code.edit', compact('data'));
        }
        catch (\Exception $exception){
            return validationResponse(false, 207, lang('messages.server_error'));
        }

    }

    /**
     * Update the specified resource in storage.
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $data = (new HsnCode)->getHsnCodes([],'','',$id);
        if(!$data){
            abort(401);
        }
        $inputs = $request->all();
        $validator = (new HsnCode)->validateHsnCodes($inputs, $id);
        if ($validator->fails()) {
            return validationResponse(false, 206, "", "", $validator->messages());
        }
        try {
            \DB::beginTransaction();
            if( !array_key_exists('status', $inputs) ) {
                $inputs = $inputs + [ 'status' =>  0];
            }
            $inputs = $inputs + [
                    'updated_by'    => authUserId(),
                ];
            (new HsnCode)->store($inputs, $id);
            \DB::commit();
            $route = route('hsn-code.index');
            $lang = lang('messages.updated', lang('hsn_code.hsn_code'));
            return validationResponse(true, 201, $lang, $route);
        }
        catch (\Exception $exception) {
            \DB::rollBack();
            return validationResponse(false, 207, $exception->getMessage() . lang('messages.server_error'));
        }

    }

    /**
     * Used to update product category active status.
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function HsnCodeToggle($id)
    {
        if (!\Request::isMethod('post') && !\Request::ajax()) {
            return lang('messages.server_error');
        }
        try {
            $hsnCode = (new HsnCode)->company()->find($id);
        } catch (\Exception $exception) {
            return lang('messages.invalid_id', string_manip(lang('hsn_code.hsn_code')));
        }
        $hsnCode->update(['status' => !$hsnCode->status]);
        $response = ['status' => 1, 'data' => (int)$hsnCode->status . '.gif'];
        return json_encode($response);
    }

    /**
     * Used to load more records and render to view.
     * @param int $pageNumber
     * @return \Illuminate\Http\Response
     */
    public function hsnCodePaginate(Request $request, $pageNumber = null)
    {
        if (!\Request::isMethod('post') && !\Request::ajax()) {
            return lang('messages.server_error');
        }
        $inputs = $request->all();
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
            unset($inputs['_token']);
            $data = (new HsnCode)->getHsnCodes($inputs, $start, $perPage);
            $total = (new HsnCode)->totalHsnCodes($inputs);
            $total = $total->total;
        } else {

            $data = (new HsnCode)->getHsnCodes($inputs, $start, $perPage);
            $total = (new HsnCode)->totalHsnCodes($inputs);
            $total = $total->total;
        }
        return view('hsn-code.load_data', compact('data', 'total', 'page', 'perPage', 'inputs'));
    }

    /**
     * Method is used to update status of HSN Code enable/disable
     * @return \Illuminate\Http\Response
     */
    public function HsnCodeAction(Request $request)
    {
        $inputs = $request->all();
        if (!isset($inputs['tick']) || count($inputs['tick']) < 1) {
            return redirect()->route('hsn-code.index')
                ->with('error', lang('messages.atleast_one', string_manip(lang('hsn_code.hsn_code'))));
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
        //Product::whereRaw('id IN (' . $ids . ')')->update(['status' => $status]);
        return redirect()->route('hsn-code.index')
            ->with('success', lang('messages.updated', lang('hsn_code.hsn_code_status')));
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
            $data = (new HsnCode)->getHsnCodes([],'','',$id);
            if(!$data){
                $response = ['status' => 0, 'message' => lang('messages.no_data_found', lang('hsn_code.hsn_code'))];
            }
            \DB::beginTransaction();
            (new HsnCode)->drop($id);
            \DB::commit();
            $response = ['status' => 1, 'message' => lang('messages.deleted', lang('hsn_code.hsn_code'))];

        } catch (Exception $exception) {
            \DB::rollBack();
            $response = ['status' => 0, 'message' => lang('messages.server_error')];
        }
        return json_encode($response);
    }

    /**
     * Submit Modal Form
     * @return Success Or Failure
     */
    public function hsnCodeModal()
    {
        $isAjax = 1;
        return view('hsn-code.hsn_code_modal', compact('isAjax'));
    }

    /**
     * Get 10 Hsn Code Lists
     * @return Success Or Failure
     */
    public function hsnCodeLists()
    {
        $hsnCode = (new HsnCode)->getHsnCodeService();
        return response()->json($hsnCode);
    }
    /**
     * @return Generate Excel file
     */
    /*public function generateExcel() {
        ini_set('memory_limit', '-1');
        $data = (new HsnCode)->getAllHsnCode();
        $main = view('hsn-code.generate_excel_load_data', compact('data'));
        return generateExcel('hsn-code.hsn_code_common', ['main' => $main], 'Hsn-Code');
    }*/

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    /*public function uploadExcel()
    {
        $data = [];
        $inputs = \Input::all();
        if (count($inputs) > 0) {
            $validator = (new HsnCode)->validateHsnCodeExcel($inputs);
            if ($validator->fails()) {
                return validationResponse(false, 206, "", "", $validator->messages());
            }
            ini_set('memory_limit', '-1');
            Excel::load($inputs['file'], function ($reader) {
                $i = 1;
                try {
                    $reader->each(function($sheet) {
                        \DB::beginTransaction();
                        $hsn = (int)$sheet->hsn_code;
                        $hsnCodes = (new HsnCode)->findHsnCode(trim($hsn));
                        $message = '';
                        if(count($hsnCodes) > 0){
                            $message = 'The HSN Code has already been taken. ';
                        }

                        //echo $message . ' ---- ' . $hsn;

                        if($message == '') {
                            $data = [
                                'hsn_code'   => $hsn,
                                'status'     => 1,
                                'created_by' => authUserId(),
                                'company_id' => loggedInCompanyId(),
                            ];
                            (new HsnCode)->store($data);
                        }
                        \DB::commit();
                    });
                }
                catch (\Exception $e) {
                    \DB::rollBack();
                    return redirect()->back()->with('error', lang('messages.server_error'));

                }
            });
            //$route = route('products.index');
            $lang = lang('hsn_code.hsn_code_imported');
            return validationResponse(true, 201, $lang);
        }
        return view('hsn-code.upload_excel', compact('banks'));
    }*/

    /**
     * @param filename
     * @return Sample File
     */
    /*public function downloadSampleExcel($filename = 'HSN-CODE-Sample')
    {
        Excel::create($filename, function($excel){
            $excel->sheet('HSN CODE', function($sheet1){

                $sheet1->cells('A1:B1', function($cells) {
                    $cells->setFont(array(
                        'family'     => 'Calibri',
                        'size'       => '9',
                        'bold'       =>  true
                    ));
                    $cells->setBackground('#FFFF00');
                    $cells->setValignment('center');
                    $cells->setBorder('solid', 'solid', 'solid', 'solid');
                });
                $sheet1->setBorder('A1:B1', 'thin', "#000000");

                $sheet1->cell('A1', function($cell) {
                    $cell->setValue('SR NO.');
                });
                $sheet1->cell('B1', function($cell) {
                    $cell->setValue('HSN CODE');
                });

                // Set width for multiple cells
                $sheet1->setWidth(array(
                    'A'     =>  7,
                    'B'     =>  15
                ));
                $sheet1->setHeight(1, 25);
            });

        })->export('xls');
    }*/
}