<?php

namespace App\Http\Controllers;

use App\Shelves;
use Illuminate\Http\Request;
use App\Racks;
use App\StoreMaster;

class RacksController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($storeId = null)
    {
        return view('racks.index', compact('storeId'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($id = null, $isMultiple = null)
    {
        $racksCode = (new Racks)->getRacksCode();
        $getStore  = (new StoreMaster)->getStoreService();
        /*if(!$isMultiple) {
            return view('racks.create', compact('racksCode', 'getStore'));
        }else*/
        if($isMultiple == 1) {
            $result = StoreMaster::find($id);
            if(!$result) {
                return redirect()->back()->with('error', lang('messages.invalid_id', string_manip(lang('store.store'))));
            }

            $findRacks = (new Racks)->findRacks(['store_id' => $id]);
            /*if(count($findRacks) > 0 && count($findRacks) == $result->total_racks) {
                return redirect()->back()->with('error', lang('racks.already_added', string_manip(lang('racks.racks'))));
            }*/

            return view('store_master.racks_create', compact('result', 'racksCode', 'getStore', 'findRacks'));
        }else {
            return redirect()->back()->with('error', lang('messages.invalid_qry_strng', string_manip(lang('racks.racks'))));
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $inputs = $request->all();
        $data = $request->except('rack_code', 'rack_name', 'total_shelves', 'rack');
        $storeId = $inputs['store'];
        $inputRacks = ( isset($inputs['rack']) )?$inputs['rack']:[];

        if(isset($inputRacks) && count($inputRacks) > 0) {
            $inputRacks['store'] = $storeId;
            $validation = (new Racks)->validateRacks($inputRacks, null, true, $inputRacks['id']);
            if ($validation->fails()) {
                return validationResponse(false, 206, "", "", $validation->messages());
            }
        }
        $isMultiple = (isset($data['is_multiple']))? true : false;

        $validator = (new Racks)->validateRacks($inputs, null, $isMultiple);
        if ($validator->fails()) {
            return validationResponse(false, 206, "", "", $validator->messages());
        }
        try {
            \DB::beginTransaction();
            unset($inputs['_token']);
            unset($inputs['store']);

            if($isMultiple) {

                if (isset($inputs['rack_name']) && is_array($inputs['rack_name'])) {

                    foreach ($inputs['rack_name'] as $key => $value) {
                        $rackCode      = $inputs['rack_code'][$key];
                        $rackName      = $inputs['rack_name'][$key];
                        $totalShelves  = $inputs['total_shelves'][$key];

                        $rackInputs = [
                            'store_id'      => $storeId,
                            'rack_code'     => $rackCode,
                            'rack_name'     => $rackName,
                            'total_shelves' => $totalShelves,
                            'status'        => $data['status'],
                            'company_id'    => loggedInCompanyId(),
                            'created_by'    => authUserId()
                        ];
                        (new Racks)->store($rackInputs);
                    }
                }
                if(isset($inputRacks) && count($inputRacks) > 0) {

                    $storeId = $inputRacks['store'];
                    foreach($inputRacks['rack_code'] as $key => $value)
                    {
                        $rackCode     = $inputRacks['rack_code'][$key];
                        $rackName     = $inputRacks['rack_name'][$key];
                        $totalShelves = $inputRacks['total_shelves'][$key];
                        $status       = $inputRacks['status'][$key];
                        $rackId       = $inputRacks['id'][$key];

                        $updateInputs = [
                            'store_id'      => $storeId,
                            'rack_code'     => $rackCode,
                            'rack_name'     => $rackName,
                            'total_shelves' => $totalShelves,
                            'status'        => $status,
                            'updated_by'    => authUserId()
                        ];
                        (new Racks)->store($updateInputs, $rackId);
                    }
                }
            }else {
                $inputs = $inputs + [
                    'store_id'      => $storeId,
                    'status'        => (isset($inputs['status']) && $inputs['status'] == '1')? 1 : 0,
                    'company_id'    => loggedInCompanyId(),
                    'created_by'    => authUserId()
                ];
                (new Racks)->store($inputs);
            }
            \DB::commit();
            $langMessage = lang('messages.created', lang('racks.racks'));
            $route = route('racks.index', $storeId);
            return validationResponse(true, 201, $langMessage, $route);
        } catch (\Exception $e) {
            \DB::rollBack();
            return validationResponse(false, 207, $e->getMessage().' - '.$e->getFile().' - '.$e->getLine().' - '.lang('messages.server_error'));
        }
    }
    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Racks  $racks
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $result = Racks ::find($id);
        if (!$result) {
            abort(404);
        }
        $getStore  = (new StoreMaster)->getStoreService();
        return view('racks.edit', compact('result', 'getStore'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Racks  $racks
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $result = Racks::find($id);
        if (!$result) {
            return redirect()->route('racks.index')
                ->with('error', lang('messages.invalid_id', string_manip(lang('racks.racks'))));
        }

        $inputs = $request->all();
        $inputs['store'] = $inputs['store_id'];
        $validator = (new Racks)->validateRacks($inputs, $id);
        if ($validator->fails()) {
            return validationResponse(false, 206, "", "", $validator->messages());
        }

        try {
            \DB::beginTransaction();
            unset($inputs['store']);

            $inputs = $inputs + [
                'status'     => (isset($inputs['status']) && $inputs['status'] == '1')? 1 : 0,
                'updated_by' => authUserId()
            ];

            (new Racks)->store($inputs, $id);
            \DB::commit();
            $langMessage = lang('messages.updated', lang('racks.racks'));
            $route = route('racks.index');
            return validationResponse(true, 201, $langMessage, $route);
        } catch (\Exception $e) {
            \DB::rollBack();
            return validationResponse(false, 207, $e->getMessage().' - '.$e->getFile().' - '.$e->getLine().' - '.lang('messages.server_error'));

        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function drop($id)
    {
        if (!\Request::ajax()) {
            return lang('messages.server_error');
        }
        try {
            $data = (new Racks)->getRacks([], '', '', $id);
            if(!$data){
                return json_encode(['status' => 0, 'message' => lang('messages.no_data_found', lang('racks.racks'))]);
            }

            $findShelves = (new Shelves)->findShelves(['rack_id' => $id]);
            if(count($findShelves) > 0) {
                return json_encode(['status' => 0, 'message' => lang('racks.rack_isin_use')]);
            }

            \DB::beginTransaction();

            (new Racks)->drop($id);

            \DB::commit();
            return json_encode(['status' => 1, 'message' => lang('messages.deleted', lang('racks.racks'))]);
        } catch (\Exception $e) {
            \DB::rollBack();
            return json_encode(['status' => 0, 'message' => $e->getMessage().'--'.$e->getFile().'--'.$e->getLine().'--'. lang('messages.server_error')]);
        }
    }
    /**
     * Used to update racks active status.
     *
     * @param int $id
     * @return Response
     */
    public function rackToggle($id = null)
    {
        if (!\Request::ajax()) {
            return lang('messages.server_error');
        }

        try {
            // get the racks w.r.t id
            $result = Racks::find($id);

            $result->update(['status' => !$result->status]);
            $response = ['status' => 1, 'data' => (int)$result->status . '.gif'];
            // return json response
            return json_encode($response);

        } catch (\Exception $exception) {
            return lang('messages.invalid_id', string_manip(lang('racks.racks')));
        }
    }

    /**
     * Used to load more records and render to view.
     *
     * @param Request $request
     * @param int $pageNumber
     * @return Response
     */
    public function rackPaginate(Request $request, $storeId = null)
    {
        if (!\Request::isMethod('post') && !\Request::ajax()) { //
            return lang('messages.server_error');
        }

        $inputs = $request->all();

        $inputs['store_id'] = ($storeId != '')? $storeId : null;

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

            $data = (new Racks)->getRacks($inputs, $start, $perPage);
            $total = (new Racks)->totalRacks($inputs);
            $total = $total->total;
        } else {

            $data = (new Racks)->getRacks($inputs, $start, $perPage);
            $total = (new Racks)->totalRacks($inputs);
            $total = $total->total;
        }
        return view('racks.load_data', compact('data', 'total', 'page', 'perPage', 'inputs'));
    }
}
