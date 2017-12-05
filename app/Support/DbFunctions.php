<?php

use App\Account;
use App\Bank;
use App\FinancialYear;
use App\InvoiceItems;
use App\InvoiceSetting;
use App\TaxRates;
use App\Menu;
use App\Tax;
use Illuminate\Support\Facades\DB;

/**
 * :: Db Functions File ::
 * USed for manage all kind database related helper functions.
 *
 **/

/**
 * @return null
 */
function loggedInCompanyId()
{
    $companyId = null;
    if(authUser()) {
        $companyId = authUser()->company_id;
    }
    return $companyId;
}

/**
 * @return null
 */
function financialYearId()
{
	$result = (new FinancialYear)->getActiveFinancialYear();
	if ($result) {
		return $result->id;
	}
	return null;
}

function financialYearStartEnd()
{
    $result = (new FinancialYear)->getActiveFinancialYear();
    if ($result) {
        return $result;
    }
    return null;
}

/**
 * Get Countires
 * @return array
 */
function getCountries() {
    $countries = DB::table('country_master')->get([\DB::raw("concat(country_name, ' (', country_digit_code, ')') as name"), 'id']);
    foreach($countries as $country) {
        $result[$country->id] = $country->name;
    }
    return ['' =>'-Select Country-'] + $result;
}
/**
 * Get States
 * @return mixed
 */
function getStates(){
    $states = DB::table('state_master')->get([\DB::raw("concat(state_name, ' (', state_digit_code, ')') as name"), 'id']);
    if(count($states)>0) {
    foreach($states as $state) {
        $result[$state->id] = $state->name;
    }
    return ['' =>'-Select State-'] + $result;
    }
    else{
        return ['' => '-Select State-'];
    }
}

/**
 * Get Cities
 * @return array
 */
function getCities() {
    $cities = DB::table('city_master')->get([\DB::raw("concat(city_name, ' (', city_digit_code, ')') as name"), 'id']);
    if(count($cities)>0) {

        foreach ($cities as $city) {
            $result[$city->id] = $city->name;
        }
        return ['' => '-Select City-'] + $result;
    }
    else{

        return ['' => '-Select City-'];
    }
}
/**
 * Get Cities
 * @return array
 */
function getCountry() {
    $countries = DB::table('country_master')->get([\DB::raw("concat(country_name, ' (', country_digit_code, ')') as name"), 'id']);
    if(count($countries)>0) {
    foreach($countries as $country) {
        $result[$country->id] = $country->name;
    }
    return ['' =>'-Select Country-'] + $result;
    }
    else{
        return ['' => '-Select Country-'];
    }
}
/**
 * @return int
 */
function getDebtorId()
{
    return 27;
}

/**
 * @return int
 */
function getCreditorId()
{
    return 26;
}

/**
 * @param null $id
 * @return bool
 */
function isDebtorORCreditor($id = null)
{
    if($id > 0) {
        return (in_array($id, [26, 27])) ? true : false;
    }
    false;
}

/**
 * Method is used on each and every view to display current financial year
 * @return null
 */
function getActiveFinancialYear()
{
    $result = (new FinancialYear)->getActiveFinancialYear();
    if ($result) {
        return $result->name;
    }
    return null;
}

/**
 * Method is used on each and every view to display current financial year
 * @return null
 */
function getActiveBank()
{
    $result = (new Bank)->totalBanks();
    if ($result) {
        return $result->total;
    }
    return null;
}

/**
 * @param $id
 * @param $date
 * @return mixed
 */
function getEffectedTaxRate($id, $date)
{
	$result = (new TaxRates)->getEffectedTaxRate($id, $date);
	return $result;
}

/**
 * @return array
 * @Author Inderjit Singh
 */
function renderMenus() {
   $menus = (new Menu)->getMenuNavigation(true, true);
   return $menus;
}

/*
 * @return Array
 */
function getQuickMenu() {
    $quickMenuArr = [];
    $tree = (new Menu)->getMenuNavigation(true, false);
    if(count($tree) > 0) {
        foreach ($tree as $firstLevel) {
            if(array_key_exists('child', $firstLevel)) {

                foreach($firstLevel['child'] as $key => $value) {
                    if(array_key_exists('quick_menu', $value)) {
                        array_push($quickMenuArr, $value);
                    }
                    else
                        continue;
                }
            }
        }
    }
    return $quickMenuArr;
}

/**
 * @return mixed
 */
function getCashAccount()
{
    return (new Account)->filterAccountService(['account_group_id' => 14]);
}

/**
 * @return mixed
 */
function getBankAccount()
{
    return (new Account)->filterAccountService(['account_group_id' => 12]);
}

/**
 * @return mixed
 */
function getAccountDebtors()
{
    return (new Account)->filterAccountService(['account_group_id' => 33]);
}

/**
 * @return mixed
 */
function getAccountCreditors()
{
    return (new Account)->filterAccountService(['account_group_id' => 32]);
}

/**
 * @return mixed
 */
function getCashBankAccounts()
{
    return (new Account)->filterAccountService(['account_in' => '12,14']);
}

/**
 * @return mixed
 */
function getDebtorCreditorAccounts()
{
    return (new Account)->filterAccountService(['account_in' => '32,33']);
}

/**
 * @return mixed
 */
function getOtherExceptCashBankAccounts()
{
    return (new Account)->filterAccountService(['account_not_in' => '12,14']);
}

/**
 * @return mixed
 */
function getOtherExceptCashAccounts()
{
    return (new Account)->filterAccountService(['account_not_in' => '14']);
}

/**
 * @return mixed
 */
function getAccountNotIn()
{
    return (new Account)->filterAccountService(['account_not_in' => '12,14,32,33,26,28']);
}

/**
 * @return mixed
 */
function getAccountIn()
{
    $result =  (new Account)->filterAccountService(['account_in' => '26']);
    //dd($result);
    return $result;
}

/**
 * @return mixed
 */
function getAccountByAccountGroup($id)
{
    return (new Account)->filterAccountService(['account_group_id' => $id]);
}

/**
 * @return mixed
 */
function getAllAccounts()
{
    return (new Account)->getAccountService();
}

/**
 * @return mixed
 */
function getCompanySetting()
{
    $setting = (new InvoiceSetting)->getInvoiceSetting(loggedInCompanyId());
    return $setting;
}

function getInvoiceItemsFromInvoiceNumber($invoiceNumber = null)
{
    return (new InvoiceItems)->getInvoiceItems(['invoice_id' => $invoiceNumber]);
}
/**
 * @return mixed
 */
function getAllTaxCategory()
{
    return (new Tax)->getTaxService();
}