<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\StoreMaster;
use App\Racks;

class StoreMasterController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('store_master.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $storeCode = (new StoreMaster)->getStoreCode();
        $storeType = lang('common.store_type');
        return view('store_master.create', compact('storeCode', 'storeType'));
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
        $validator = (new StoreMaster)->validateStore($inputs);
        if ($validator->fails()) {
            return validationResponse(false, 206, "", "", $validator->messages());
        }
        try {
            \DB::beginTransaction();
            unset($inputs['_token']);
            $inputs = $inputs + [
                'status'              => (isset($inputs['status']) && $inputs['status'] == '1')? 1 : 0,
                'godown_lvl_sto_mnt'  => (isset($inputs['godown_lvl_sto_mnt']) && $inputs['godown_lvl_sto_mnt'] == '1')? 1 : 0,
                'company_id'          => loggedInCompanyId(),
                'created_by'          => authUserId()
            ];
            (new StoreMaster)->store($inputs);
            \DB::commit();
            $langMessage = lang('messages.created', lang('store.store'));
            $route = route('store-master.index');
            return validationResponse(true, 201, $langMessage, $route);
        } catch (\Exception $e) {
            \DB::rollBack();
            return validationResponse(false, 207, $e->getMessage().' - '.$e->getFile().' - '.$e->getLine().' - '.lang('messages.server_error'));
        }
    }
    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\StoreMaster  $storeMaster
     * @return \Illuminate\Http\Response
     */
    public function edit(StoreMaster $storeMaster)
    {
        $result = $storeMaster;
        if (!$result) {
            abort(404);
        }
        $storeType = lang('common.store_type');
        return view('store_master.edit', compact('result', 'storeType'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\StoreMaster  $storeMaster
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, StoreMaster $storeMaster)
    {
        $result = $storeMaster;
        if (!$result) {
            return redirect()->route('store-master.index')
                ->with('error', lang('messages.invalid_id', string_manip(lang('store.store'))));
        }
        $id = $result->id;

        $inputs = $request->all();
        $validator = (new StoreMaster)->validateStore($inputs, $id);
        if ($validator->fails()) {
            return validationResponse(false, 206, "", "", $validator->messages());
        }

        try {
            \DB::beginTransaction();

            $inputs = $inputs + [
                'godown_lvl_sto_mnt'  => (isset($inputs['godown_lvl_sto_mnt']) && $inputs['godown_lvl_sto_mnt'] == '1')? 1 : 0,
                'status'     => (isset($inputs['status']) && $inputs['status'] == '1')? 1 : 0,
                'updated_by' => authUserId()
            ];

            (new StoreMaster)->store($inputs, $id);
            \DB::commit();
            $langMessage = lang('messages.updated', lang('store.store'));
            $route = route('store-master.index');
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
            $data = (new StoreMaster)->getStore([], '', '', $id);
            if(!$data){
                return json_encode(['status' => 0, 'message' => lang('messages.no_data_found', lang('store.store'))]);
            }
            $findRacks = (new Racks)->findRacks(['store_id' => $id]);
            if(count($findRacks) > 0) {
                return json_encode(['status' => 0, 'message' => lang('store.store_isin_use')]);
            }

            \DB::beginTransaction();

            (new StoreMaster)->drop($id);

            \DB::commit();
            return json_encode(['status' => 1, 'message' => lang('messages.deleted', lang('store.store'))]);
        } catch (\Exception $e) {
            \DB::rollBack();
            return json_encode(['status' => 0, 'message' => $e->getMessage().'--'.$e->getFile().'--'.$e->getLine().'--'. lang('messages.server_error')]);
        }
    }
    /**
     * Used to update store-master active status.
     *
     * @param int $id
     * @return Response
     */
    public function storeMasterToggle($id = null)
    {
        if (!\Request::ajax()) {
            return lang('messages.server_error');
        }

        try {
            // get the store-master w.r.t id
            $result = StoreMaster::find($id);

            $result->update(['status' => !$result->status]);
            $response = ['status' => 1, 'data' => (int)$result->status . '.gif'];
            // return json response
            return json_encode($response);

        } catch (\Exception $exception) {
            return lang('messages.invalid_id', string_manip(lang('store.store')));
        }
    }

    /**
     * Used to load more records and render to view.
     *
     * @param Request $request
     * @param int $pageNumber
     * @return Response
     */
    public function storeMasterPaginate(Request $request, $pageNumber = null)
    {
        if (!\Request::isMethod('post') && !\Request::ajax()) { //
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

            $data = (new StoreMaster)->getStore($inputs, $start, $perPage);
            $total = (new StoreMaster)->totalStore($inputs);
            $total = $total->total;
        } else {

            $data = (new StoreMaster)->getStore($inputs, $start, $perPage);
            $total = (new StoreMaster)->totalStore($inputs);
            $total = $total->total;
        }
        return view('store_master.load_data', compact('data', 'total', 'page', 'perPage', 'inputs'));
    }
}
