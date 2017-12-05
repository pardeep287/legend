<?php

namespace App\Http\Middleware;

use App\User;
use Closure;

class HttpBasicAuthenticate
{
    /**
     * Path for login with http basic authorization api.
     * @var string
     */
    protected $httpAuthLogin = 'api/v1/login';

    /**
     * Path for logout and clear authorization cache.
     * @var string
     */
    protected $httpAuthLogout = 'api/v1/logout';

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {

            if (trim($_SERVER['PHP_AUTH_USER']) != '' && trim($_SERVER['PHP_AUTH_PW']) != '') {

                // login authorization code
                if (\Request::path() == $this->httpAuthLogin) {
                    // validate user is authorized or not.
                    return $this->doLogin();

                } elseif (\Request::path() == $this->httpAuthLogout) {

                    // logout user & clear authorization cache.
                    return $this->doLogout();
                } else {
                    // if normal request validate user is authorized or not
                    if ($this->doLogin(true) === false) {
                        return apiResponse(false, 401, lang('auth.failed_login'));
                    }
                }
            } else {
                return apiResponse(false, 401, lang('auth.auth_required'));
            }
        } else {
            return apiResponse(false, 401, lang('auth.auth_required'));
        }

        return $next($request);
    }

    /**
     * Method is used for login authorization.
     *
     * @param bool $request
     *
     * @return Json|Response
     */
    protected function doLogin($request = false)
    {
        $username = $_SERVER['PHP_AUTH_USER'];
        $password = $_SERVER['PHP_AUTH_PW'];

        try {
            $credentials = [
                'username' => $username,
                'password' => $password,
                'status' => 1
            ];

            if (\Auth::once(['email' => $username, 'password' => $password, 'status' => 1]) ||
                \Auth::once($credentials)
                ) {
                $user = $this->updateLastLogin();
            } else {
                //$this->loginAttemptsFailed($username);
                if ($request == true) {
                    return false;
                } else {
                    return apiResponse(false, 401, lang('auth.failed_login'));
                }
            }

            if (\Request::path() == $this->httpAuthLogin) {
                return apiResponse(true, 200, '', ['user_data' => $user]);
            }

        } catch (\Exception $e) {
            return apiResponse(false, 500, lang('messages.server_error'));
        }
    }

    /**
     * Method is used for logout and clear authorization cache.
     *
     * @return  Response|Json
     */
    protected function doLogout()
    {
        if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
            try {
                // unset the http auth values.
                $_SERVER['PHP_AUTH_USER'] = $_SERVER['PHP_AUTH_PW'] = '';
                unset($_SERVER['PHP_AUTH_USER']);
                unset($_SERVER['PHP_AUTH_PW']);

                return apiResponse(true, 200, lang('auth.logout'));

            } catch (\Exception $e) {
                return apiResponse(false, 500, lang('messages.server_error'));
            }
        }
    }

    /**
     * Method is used for update last login time.
     *
     * @return  Response
     */
    protected function updateLastLogin()
    {
        //(new User)->updateLastLogin();
        $id = \Auth::user()->id;
        $username = \Auth::user()->username;
        $email = \Auth::user()->email;
        //$company = \Auth::user()->company_id;
        $dateFormat = getCompanySettings('datetime_format');
        $currency = getCompanySettings('currency');

        return [
            'id'        => $id,
            'username'  => $username,
            'email'     => $email,
            'is_full_version'     => false,
            'date_format'  => $dateFormat,
            'currency' => $currency
        ];
    }

    /**
     * Method is used for update last login time.
     *
     * @param string $username
     *
     * @return Response
     */
    protected function loginAttemptsFailed($username)
    {
        if($username != "") {
            (new User)->updateFailedAttempts($username);
        }
    }
}
