<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;
use App\Company;
use App\Currency;
use App\DateTimeFormat;
use App\Invoice;
use App\InvoiceSetting;
use App\Laboratory;
use App\LaboratoryProfile;
use App\Setting;
use App\Theme;
use App\Timezone;
use App\User;
use Mockery\CountValidator\Exception;
use Hamcrest\Core\Set;
//use App\Http\Requests;

class SettingController extends Controller
{

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @internal param null $id
     */
    public function index(Request $request)
    {
        $id = loggedInCompanyId();
        if($id == '') {
            abort(404);
        }

        $tab = $request->get('tab', '1');
        $company = (new Company)->getCompanyInfo($id);
        $setting = (new Setting)->getSettingByCompanyId($id);
        $currency = (new Currency)->getCurrencyService();
        $timezone = (new Timezone)->getTimezoneService();
        $theme = (new Theme)->getThemeService();
        $dateTimeFormat = (new DateTimeFormat)->getDateTimeFormatService();
        $states = getStates();
        return view('setting.company-profile', compact('company', 'setting', 'currency', 'timezone', 'theme', 'dateTimeFormat', 'tab', 'id', 'states'));
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function myAccount(Request $request)
    {
        $inputs = $request->all();
        if (count($inputs) > 0) {
            $validator = (new User)->validatePassword($inputs);
            if ($validator->fails()) {
                return redirect()->route('setting.manage-account')
                    ->withErrors($validator);
            }

            $password = \Auth::user()->password;
            if (!(\Hash::check($inputs['password'], $password))) {
                return redirect()->route('setting.manage-account')
                    ->with("error", lang('messages.invalid_password'));
            }

            $inputs['new_password'] = \Hash::make($inputs['new_password']);
            $inputs['is_reset_password'] = '0';

            (new User)->updatePassword($inputs);
            return redirect()->route('setting.manage-account')
                ->with('success', lang('messages.password_updated'));
        }
        return view('setting.account');
    }

    /**
     * @param Request $request
     * @return $this|\Illuminate\Http\RedirectResponse
     */
    public function updateCompany(Request $request)
    {
        $inputs = $request->all();
        $validator = (new Company)->validateCompany($inputs, 1);
        if (array_key_exists('id', $inputs)) {
            $id = $inputs['id'];
        }

        if ($validator->fails()) {
            return redirect()->route('setting.company-profile')->withErrors($validator)->withInput($inputs);
        }
        try {
            \DB::beginTransaction();
            if (array_key_exists('company_logo', $inputs)) {
                $companyLogo = $request->file('company_logo');
                $company = (new Company)->getCompanyInfo($id);
                $oldCompanyLogo = $company->company_logo;
                $fileName = str_random(6) . '_' . str_replace(' ', '_', $companyLogo->getClientOriginalName());
                $folder = ROOT . \Config::get('constants.UPLOADS');
                if ($companyLogo->move($folder, $fileName)) {
                    if (!empty($oldCompanyLogo) && file_exists($folder . $oldCompanyLogo)) {
                        unlink($folder . $oldCompanyLogo);
                    }
                }

                $inputs['company_logo'] = $fileName;
            }

            (new Company)->store($inputs, $id);
            \DB::commit();
            return redirect()->route('setting.company-profile')
                ->with('success', lang('messages.updated', lang('setting.company_settings')));
        } catch (Exception $e) {
            \DB::rollback();
            return redirect()->route('setting.company-profile' )
                ->withInput($inputs)
                ->with('error', lang('messages.server_error'));
        }
    }

    /**
     * @param Request $request
     * @param null $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    public function invoiceSetting(Request $request, $id = null)
    {
        $inputs = $request->all();
        if (count($inputs) > 0 && \Request::ajax()) {
            $validator = (new InvoiceSetting)->validateSetting($inputs);
            if ($validator->fails()) {
                return validationResponse(false, 206, "", "", $validator->messages());
            }
            try {
                \DB::beginTransaction();
                $printOtions = $inputs['print_options'];
                unset($inputs['print_options']);
                $printOtions = implode(',',$printOtions);
                $inputs = $inputs + [
                    'print_options' =>$printOtions
                ];
                (new InvoiceSetting)->store($inputs, $id);
                $lang = lang('messages.updated', lang('setting.invoice_setting'));
                \DB::commit();
                $route = route('setting.invoice');
                return validationResponse(true, 201, $lang, $route);
            } catch (\Exception $exception) {
                \DB::rollBack();
                return validationResponse(false, 207, $exception->getMessage() .  lang('messages.server_error'));
            }
        }
        $setting = (new InvoiceSetting)->getInvoiceSetting(loggedInCompanyId());
        $printOptions = explode(',', $setting->print_options);
        return view('setting.invoice-setting', compact('setting','printOptions'));
    }
}
