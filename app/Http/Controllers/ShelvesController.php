<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Shelves;
use App\Racks;

class ShelvesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($rackId = null)
    {
        return view('shelves.index', compact('rackId'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($id = null, $isMultiple = null)
    {
        $shelveCode = (new Shelves)->getShelveCode();
        $getRacks  = (new Racks)->getRackService();
        /*if(!$isMultiple) {
            return view('shelves.create', compact('shelveCode', 'getStore'));
        }else*/
        if($isMultiple == 1) {
            $result = Racks::find($id);
            if(!$result) {
                return redirect()->back()->with('error', lang('messages.invalid_id', string_manip(lang('shelves.shelve'))));
            }

            $findShelves = (new Shelves)->findShelves(['rack_id' => $id]);
           /* if(count($findShelves) > 0 && count($findShelves) == $result->total_shelves) {
                return redirect()->back()->with('error', lang('shelves.already_added', string_manip(lang('shelves.shelves'))));
            }*/

            return view('racks.shelves_create', compact('result', 'shelveCode', 'getRacks', 'findShelves'));
        }else {
            return redirect()->back()->with('error', lang('messages.invalid_qry_strng', string_manip(lang('shelves.shelve'))));
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
        $data = $request->except('shelve_code', 'shelve_name', 'shelves');
        $rackId = $inputs['rack'];
        $inputShelves = (isset($inputs['shelves'])) ? $inputs['shelves']:[];

        if(isset($inputShelves) && count($inputShelves) > 0) {
            $inputShelves['rack'] = $rackId;
            $validation = (new Shelves)->validateShelves($inputShelves, null, true, $inputShelves['id']);
            if ($validation->fails()) {
                return validationResponse(false, 206, "", "", $validation->messages());
            }
        }
        $isMultiple = (isset($data['is_multiple']))? true : false;
        $validator = (new Shelves)->validateShelves($inputs, null, $isMultiple);
        if ($validator->fails()) {
            return validationResponse(false, 206, "", "", $validator->messages());
        }
        try {
            \DB::beginTransaction();
            unset($inputs['_token']);
            unset($inputs['rack']);
            if($isMultiple) {
                if (isset($inputs['shelve_name']) && is_array($inputs['shelve_name'])) {
                    foreach ($inputs['shelve_name'] as $key => $value) {
                        $shelveCode      = $inputs['shelve_code'][$key];
                        $shelveName      = $inputs['shelve_name'][$key];

                        $shelveInputs = [
                            'rack_id'       => $rackId,
                            'shelve_code'   => $shelveCode,
                            'shelve_name'   => $shelveName,
                            'status'        => $data['status'],
                            'company_id'    => loggedInCompanyId(),
                            'created_by'    => authUserId()
                        ];
                        (new Shelves)->store($shelveInputs);
                    }
                }
                if(isset($inputShelves) && count($inputShelves) > 0) {

                    $rackId = $inputShelves['rack'];
                    foreach($inputShelves['shelve_code'] as $key => $value)
                    {
                        $rackCode      = $inputShelves['shelve_code'][$key];
                        $rackName      = $inputShelves['shelve_name'][$key];
                        $status        = $inputShelves['status'][$key];
                        $shelveId        = $inputShelves['id'][$key];

                        $updateInputs = [
                            'rack_id'       => $rackId,
                            'shelve_code'     => $rackCode,
                            'shelve_name'     => $rackName,
                            'status'        => $status,
                            'updated_by'    => authUserId()
                        ];
                        (new Shelves)->store($updateInputs, $shelveId);
                    }
                }
            }else {
                $inputs = $inputs + [
                        'rack_id'      => $rackId,
                        'status'        => (isset($inputs['status']) && $inputs['status'] == '1')? 1 : 0,
                        'company_id'    => loggedInCompanyId(),
                        'created_by'    => authUserId()
                    ];
                (new Shelves)->store($inputs);
            }
            \DB::commit();
            $langMessage = lang('messages.created', lang('shelves.shelve'));
            $route = route('shelves.index', $rackId);
            return validationResponse(true, 201, $langMessage, $route);
        } catch (\Exception $e) {
            \DB::rollBack();
            return validationResponse(false, 207, $e->getMessage().' - '.$e->getFile().' - '.$e->getLine().' - '.lang('messages.server_error'));
        }
    }
    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Shelves  $shelves
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $result = Shelves ::find($id);
        if (!$result) {
            abort(404);
        }
        $getRacks  = (new Racks)->getRackService();
        return view('shelves.edit', compact('result', 'getRacks'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Shelves  $shelves
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $result = Shelves::find($id);
        if (!$result) {
            return redirect()->route('shelves.index')
                ->with('error', lang('messages.invalid_id', string_manip(lang('shelves.shelve'))));
        }

        $inputs = $request->all();
        $inputs['rack'] = $inputs['rack_id'];
        $validator = (new Shelves)->validateShelves($inputs, $id);
        if ($validator->fails()) {
            return validationResponse(false, 206, "", "", $validator->messages());
        }

        try {
            \DB::beginTransaction();
            unset($inputs['rack']);

            $inputs = $inputs + [
                'status'     => (isset($inputs['status']) && $inputs['status'] == '1')? 1 : 0,
                'updated_by' => authUserId()
            ];

            (new Shelves)->store($inputs, $id);
            \DB::commit();
            $langMessage = lang('messages.updated', lang('shelves.shelve'));
            $route = route('shelves.index');
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
            $data = (new Shelves)->getShelves([], '', '', $id);
            if(!$data){
                return json_encode(['status' => 0, 'message' => lang('messages.no_data_found', lang('shelves.shelve'))]);
            }
            \DB::beginTransaction();

            (new Shelves)->drop($id);

            \DB::commit();
            return json_encode(['status' => 1, 'message' => lang('messages.deleted', lang('shelves.shelve'))]);
        } catch (\Exception $e) {
            \DB::rollBack();
            return json_encode(['status' => 0, 'message' => $e->getMessage().'--'.$e->getFile().'--'.$e->getLine().'--'. lang('messages.server_error')]);
        }
    }
    /**
     * Used to update shelves active status.
     *
     * @param int $id
     * @return Response
     */
    public function shelvesToggle($id = null)
    {
        if (!\Request::ajax()) {
            return lang('messages.server_error');
        }

        try {
            // get the shelves w.r.t id
            $result = Shelves::find($id);

            $result->update(['status' => !$result->status]);
            $response = ['status' => 1, 'data' => (int)$result->status . '.gif'];
            // return json response
            return json_encode($response);

        } catch (\Exception $exception) {
            return lang('messages.invalid_id', string_manip(lang('shelves.shelve')));
        }
    }

    /**
     * Used to load more records and render to view.
     *
     * @param Request $request
     * @param int $pageNumber
     * @return Response
     */
    public function shelvesPaginate(Request $request, $rackId = null)
    {
        if (!\Request::isMethod('post') && !\Request::ajax()) { //
            return lang('messages.server_error');
        }

        $inputs = $request->all();

        $inputs['rack_id'] = ($rackId != '')? $rackId : null;

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

            $data = (new Shelves)->getShelves($inputs, $start, $perPage);
            $total = (new Shelves)->totalShelves($inputs);
            $total = $total->total;
        } else {

            $data = (new Shelves)->getShelves($inputs, $start, $perPage);
            $total = (new Shelves)->totalShelves($inputs);
            $total = $total->total;
        }
        return view('shelves.load_data', compact('data', 'total', 'page', 'perPage', 'inputs'));
    }
}
