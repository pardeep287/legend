<?php

/*
|--------------------------------------------------------------------------
| Application Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
| Now create something great!
|
*/

/*Route::get('/', function () {
    return view('welcome');
});*/

// Authentication routes...
Route::get('/', 'Auth\AuthController@getLogin')->name('home');

Route::post('login', 'Auth\AuthController@postLogin');
Route::get('logout', ['as' => 'logout', 'uses' => 'Auth\AuthController@getLogout']);

Route::any('reset-password', ['as' => 'reset-password.index',
    'uses' => 'ResetPasswordController@index']);
Route::post('reset-password/reset', ['as' => 'reset-password.reset',
    'uses' => 'ResetPasswordController@resetPassword']);


// 'middleware' => 'auth'

Route::group(['middleware' => 'auth', 'after' => 'no-cache'], function () {

    Route::any('myaccount', ['as' => 'setting.manage-account', 'uses' => 'SettingController@myAccount']);
    Route::any('company-profile', ['as' => 'setting.company-profile', 'uses' => 'SettingController@index']);


    Route::get('dashboard', array('as' => 'dashboard', 'uses' => 'DashboardController@index'));


    // Company Routes
    //Route::resource('company','CompanyController');
    Route::resource('company', 'CompanyController',
        ['names' => [
            'index'     => 'company.index',
            'create'    => 'company.create',
            'store'     => 'company.store',
            'edit'      => 'company.edit',
            'update'    => 'company.update'
        ],
            'except' => ['show', 'destroy']
        ]);


    Route::any('company/action',            ['as' => 'company.action',      'uses' => 'CompanyController@companyAction']);
    Route::any('company/paginate/{page?}',  ['as' => 'company.paginate',    'uses' => 'CompanyController@companyPaginate']);
    Route::any('company/toggle/{id?}',      ['as' => 'company.toggle',      'uses' => 'CompanyController@companyToggle']);
    Route::any('company/add-bank/{id?}',    ['as' => 'company.add-bank',    'uses' => 'CompanyController@companyAddBank']);
    Route::any('company/edit-bank/{id?}',   ['as' => 'company.edit-bank',   'uses' => 'CompanyController@companyEditBank']);
    Route::any('company/update-bank/{id?}', ['as' => 'company.update-bank', 'uses' => 'CompanyController@companyUpdateBank']);
    Route::get('company/drop/{id?}',        ['as' => 'company.drop',        'uses' => 'CompanyController@drop']);



    // User Routes
    Route::resource('user','UserController');

    Route::any('user/action',       ['as' => 'user.action',   'uses' => 'UserController@userAction']);
    Route::any('user/paginate',     ['as' => 'user.paginate', 'uses' => 'UserController@userPaginate']);
    Route::any('user/toggle/{id?}', ['as' => 'user.toggle',   'uses' => 'UserController@userToggle']);
    Route::get('user/drop/{id?}',   ['as' => 'user.drop',     'uses' => 'UserController@drop']);

    // UserRole Routes
    Route::resource('user-roles','UserRoleController');

    Route::any('user-roles/action',       ['as' => 'user-roles.action',   'uses' => 'UserRoleController@roleAction']);
    Route::any('user-roles/paginate',     ['as' => 'user-roles.paginate', 'uses' => 'UserRoleController@rolePaginate']);
    Route::any('user-roles/toggle/{id?}', ['as' => 'user-roles.toggle',   'uses' => 'UserRoleController@roleToggle']);
    Route::get('user-roles/drop/{id?}',   ['as' => 'user-roles.drop',     'uses' => 'UserRoleController@drop']);

    // Menu Routes
    Route::resource('menu','MenuController');

    Route::any('menu/action',         ['as' => 'menu.action',   'uses' => 'MenuController@menuAction']);
    Route::any('menu/paginate',       ['as' => 'menu.paginate', 'uses' => 'MenuController@menuPaginate']);
    Route::any('menu/toggle/{id?}',   ['as' => 'menu.toggle',   'uses' => 'MenuController@menuToggle']);
    Route::any('menu/sorter/{page?}', ['as' => 'menu.sorter',   'uses' => 'MenuController@sortingMenu']);
    Route::get('menu/drop/{id?}',     ['as' => 'menu.drop',     'uses' => 'MenuController@drop']);

    // Financial Year Routes
    Route::resource('financial-year','FinancialYearController');

    Route::any('financial-year/action',         ['as' => 'financial-year.action',   'uses' => 'FinancialYearController@financialYearAction']);
    Route::any('financial-year/paginate',       ['as' => 'financial-year.paginate', 'uses' => 'FinancialYearController@financialYearPaginate']);
    Route::any('financial-year/toggle/{id?}',   ['as' => 'financial-year.toggle',   'uses' => 'FinancialYearController@financialYearToggle']);
    Route::get('financial-year/drop/{id?}',     ['as' => 'financial-year.drop',     'uses' => 'FinancialYearController@drop']);



    // Account Group Routes
    Route::resource('account-group', 'AccountGroupController',
        ['names' => [
            'index'     => 'account-group.index',
            'create'    => 'account-group.create',
            'store'     => 'account-group.store',
            'edit'      => 'account-group.edit',
            'update'    => 'account-group.update'
        ],
            'except' => ['show', 'destroy']
        ]);

    Route::any('account-group/paginate/{page?}', ['as' => 'account-group.paginate',
        'uses' => 'AccountGroupController@accountGroupPaginate']);
    Route::any('account-group/action', ['as' => 'account-group.action',
        'uses' => 'AccountGroupController@accountGroupAction']);
    Route::any('account-group/toggle/{id?}', ['as' => 'account-group.toggle',
        'uses' => 'AccountGroupController@accountGroupToggle']);
    Route::get('account-group/drop/{id?}', ['as' => 'account-group.drop',
        'uses' => 'AccountGroupController@drop']);

    // Party Routes
    Route::resource('account', 'AccountController',
        ['names' => [
            'index'     => 'account.index',
            'create'    => 'account.create',
            'store'     => 'account.store',
            'edit'      => 'account.edit',
            'update'    => 'account.update'
        ],
            'except' => ['show', 'destroy']
        ]);

    Route::any('account/paginate/{page?}', ['as' => 'account.paginate',
        'uses' => 'AccountController@accountPaginate']);
    Route::any('account/action', ['as' => 'account.action',
        'uses' => 'AccountController@accountAction']);
    Route::any('account/toggle/{id?}', ['as' => 'account.toggle',
        'uses' => 'AccountController@accountToggle']);
    Route::get('account/drop/{id?}', ['as' => 'account.drop',
        'uses' => 'AccountController@drop']);

    Route::get('account/generate-excel', ['as' => 'account.generate-excel',
        'uses' => 'AccountController@generateExcel']);

    Route::any('account/upload-excel', ['as' => 'account.upload-excel',
        'uses' => 'AccountController@uploadExcel']);
    Route::any('account/download-sample-excel', ['as' => 'account.download-sample-excel',
        'uses' => 'AccountController@downloadSampleExcel']);

    Route::any('account/for-voucher/{d?}/{type?}', ['as' => 'account.for-voucher',
        'uses' => 'AccountController@getDebitCreditAccounts']);

    Route::get('account/search', ['as' => 'account.search',
        'uses' => 'AccountController@searchAccount']);

    //supplier routes
    Route::resource('supplier', 'AccountController',
        ['names' => [
            'index'     => 'supplier.index',
            'create'    => 'supplier.create',
            'store'     => 'supplier.store',
            'edit'      => 'supplier.edit',
            'update'    => 'supplier.update'
        ],
            'except' => ['show', 'destroy']
        ]);
    Route::any('supplier/paginate/{page?}', ['as' => 'supplier.paginate',
        'uses' => 'AccountController@accountPaginate']);
    Route::any('supplier/action', ['as' => 'supplier.action',
        'uses' => 'AccountController@accountAction']);
    Route::any('supplier/toggle/{id?}', ['as' => 'supplier.toggle',
        'uses' => 'AccountController@accountToggle']);
    Route::get('supplier/drop/{id?}', ['as' => 'supplier.drop',
        'uses' => 'AccountController@drop']);


    //Customer Routes
    Route::resource('customer', 'AccountController',
        ['names' => [
            'index'     => 'customer.index',
            'create'    => 'customer.create',
            'store'     => 'customer.store',
            'edit'      => 'customer.edit',
            'update'    => 'customer.update'
        ],
            'except' => ['show', 'destroy']
        ]);
    Route::any('customer/paginate/{page?}', ['as' => 'customer.paginate',
        'uses' => 'AccountController@accountPaginate']);
    Route::any('customer/action', ['as' => 'customer.action',
        'uses' => 'AccountController@accountAction']);
    Route::any('customer/toggle/{id?}', ['as' => 'customer.toggle',
        'uses' => 'AccountController@accountToggle']);
    Route::get('customer/drop/{id?}', ['as' => 'customer.drop',
        'uses' => 'AccountController@drop']);

    // Currency Routes
    Route::resource('currency','CurrencyController');

    Route::any('currency/action',         ['as' => 'currency.action',   'uses' => 'CurrencyController@currencyAction']);
    Route::any('currency/paginate',       ['as' => 'currency.paginate', 'uses' => 'CurrencyController@currencyPaginate']);
    Route::any('currency/toggle/{id?}',   ['as' => 'currency.toggle',   'uses' => 'CurrencyController@currencyToggle']);
    Route::get('currency/drop/{id?}',     ['as' => 'currency.drop',     'uses' => 'CurrencyController@drop']);


    // Country Routes
    Route::resource('country','CountryController');

    Route::any('country/action',         ['as' => 'country.action',   'uses' => 'CountryController@countryAction']);
    Route::any('country/paginate',       ['as' => 'country.paginate', 'uses' => 'CountryController@countryPaginate']);
    Route::any('country/toggle/{id?}',   ['as' => 'country.toggle',   'uses' => 'CountryController@countryToggle']);
    Route::get('country/drop/{id?}',     ['as' => 'country.drop',     'uses' => 'CountryController@drop']);

    // State Routes
    //Route::resource('state','StateController');
    Route::resource('state', 'StateController',
        ['names' => [
            'index'     => 'state.index',
            'create'    => 'state.create',
            'store'     => 'state.store',
            'edit'      => 'state.edit',
            'update'    => 'state.update'
        ],
            'except' => ['show', 'destroy']
        ]);


    Route::get('state/get-state-list', ['as' => 'state.ajx',   'uses' => 'StateController@getStateLister']);

    Route::any('state/action',         ['as' => 'state.action',   'uses' => 'StateController@stateAction']);
    Route::any('state/paginate',       ['as' => 'state.paginate', 'uses' => 'StateController@statePaginate']);
    Route::any('state/toggle/{id?}',   ['as' => 'state.toggle',   'uses' => 'StateController@stateToggle']);
    Route::get('state/drop/{id?}',     ['as' => 'state.drop',     'uses' => 'StateController@drop']);

    // City Routes
    //Route::resource('city','CityController');
    Route::resource('city', 'CityController',
        ['names' => [
            'index'     => 'city.index',
            'create'    => 'city.create',
            'store'     => 'city.store',
            'edit'      => 'city.edit',
            'update'    => 'city.update'
        ],
            'except' => ['show', 'destroy']
        ]);


    Route::get('city/get-city-list', ['as' => 'city.ajx',   'uses' => 'CityController@getCityList']);


    Route::any('city/action',         ['as' => 'city.action',   'uses' => 'CityController@cityAction']);
    Route::any('city/paginate',       ['as' => 'city.paginate', 'uses' => 'CityController@cityPaginate']);
    Route::any('city/toggle/{id?}',   ['as' => 'city.toggle',   'uses' => 'CityController@cityToggle']);
    Route::get('city/drop/{id?}',     ['as' => 'city.drop',     'uses' => 'CityController@drop']);

    // Unit Routes
    Route::resource('unit', 'UnitController',
        ['names' => [
            'index'     => 'unit.index',
            'create'    => 'unit.create',
            'store'     => 'unit.store',
            'edit'      => 'unit.edit',
            'update'    => 'unit.update'
        ],
            'except' => ['show', 'destroy']
        ]);

    Route::any('unit/paginate/{page?}', ['as' => 'unit.paginate',
        'uses' => 'UnitController@unitPaginate']);
    Route::any('unit/action', ['as' => 'unit.action',
        'uses' => 'UnitController@unitAction']);
    Route::any('unit/toggle/{id?}', ['as' => 'unit.toggle',
        'uses' => 'UnitController@unitToggle']);
    Route::any('unit/drop/{id?}', ['as' => 'unit.drop',
        'uses' => 'UnitController@drop']);
    Route::any('unit/unit-modal/', ['as' => 'unit.unit-modal',
        'uses' => 'UnitController@unitModal']);

    // HSN Code Routes
    Route::resource('hsn-code', 'HsnCodeController',
        ['names' => [
            'index'     => 'hsn-code.index',
            'create'    => 'hsn-code.create',
            'store'     => 'hsn-code.store',
            'edit'      => 'hsn-code.edit',
            'update'    => 'hsn-code.update',
            'drop'      => 'hsn-code.drop'
        ],
            'except' => ['show', 'destroy']
        ]);

    Route::any('hsn-code/paginate/{page?}', ['as' => 'hsn_code.paginate',
        'uses' => 'HsnCodeController@hsnCodePaginate']);
    Route::any('hsn-code/action', ['as' => 'hsn_code.action',
        'uses' => 'HsnCodeController@hsnCodeAction']);
    Route::any('hsn-code/toggle/{id?}', ['as' => 'hsn_code.toggle',
        'uses' => 'HsnCodeController@hsnCodeToggle']);
    Route::any('hsn-code/drop/{id?}', ['as' => 'hsn-code.drop',
        'uses' => 'HsnCodeController@drop']);
    Route::any('hsn-code/hsn-code-modal/', ['as' => 'hsn-code.hsn-modal',
        'uses' => 'HsnCodeController@hsnCodeModal']);

    /*Route::get('hsn-code/generate-excel', ['as' => 'hsn-code.generate-excel',
        'uses' => 'HsnCodeController@generateExcel']);
    Route::any('hsn-code/upload-excel', ['as' => 'hsn-code.upload-excel',
        'uses' => 'HsnCodeController@uploadExcel']);
    Route::any('hsn-code/download-sample-excel', ['as' => 'hsn-code.download-sample-excel',
        'uses' => 'HsnCodeController@downloadSampleExcel']);*/

    // Tax Routes
    Route::resource('tax-group', 'TaxController',
        ['names' => [
            'index'     => 'tax.index',
            'create'    => 'tax.create',
            'store'     => 'tax.store',
            'edit'      => 'tax.edit',
            'update'    => 'tax.update'
        ],
            'except' => ['show', 'destroy']
        ]);

    Route::any('tax-group/paginate/{page?}', ['as' => 'tax.paginate',
        'uses' => 'TaxController@taxPaginate']);
    Route::any('tax-group/action', ['as' => 'tax.action',
        'uses' => 'TaxController@taxAction']);
    Route::any('tax-group/toggle/{id?}', ['as' => 'tax.toggle',
        'uses' => 'TaxController@taxToggle']);
    Route::any('tax-group/drop/{id?}', ['as' => 'tax-group.drop',
        'uses' => 'TaxController@drop']);
    Route::any('tax-group/tax-modal', ['as' => 'tax.tax-modal',
        'uses' => 'TaxController@create']);


    // Account Master Routes
    Route::resource('account', 'AccountController',
        ['names' => [
            'index'     => 'account.index',
            'create'    => 'account.create',
            'store'     => 'account.store',
            'edit'      => 'account.edit',
            'update'    => 'account.update'
        ],
            'except' => ['show', 'destroy']
        ]);

    Route::any('account/paginate/{page?}', ['as' => 'account.paginate',
        'uses' => 'AccountController@accountPaginate']);
    Route::any('account/action', ['as' => 'account.action',
        'uses' => 'AccountController@accountAction']);
    Route::any('account/toggle/{id?}', ['as' => 'account.toggle',
        'uses' => 'AccountController@accountToggle']);
    Route::get('account/drop/{id?}', ['as' => 'account.drop',
        'uses' => 'AccountController@drop']);

    /*Route::get('account/generate-excel', ['as' => 'account.generate-excel',
        'uses' => 'AccountController@generateExcel']);

    Route::any('account/upload-excel', ['as' => 'account.upload-excel',
        'uses' => 'AccountController@uploadExcel']);
    Route::any('account/download-sample-excel', ['as' => 'account.download-sample-excel',
        'uses' => 'AccountController@downloadSampleExcel']);

    Route::any('account/for-voucher/{d?}/{type?}', ['as' => 'account.for-voucher',
        'uses' => 'AccountController@getDebitCreditAccounts']);

    Route::get('account/search', ['as' => 'account.search',
        'uses' => 'AccountController@searchAccount']);*/

    // Purchase Order Routes
    Route::resource('purchase-order', 'PurchaseOrderController',
        ['names' => [
            'index'     => 'purchase-order.index',
            'create'    => 'purchase-order.create',
            'store'     => 'purchase-order.store',
            'edit'      => 'purchase-order.edit',
            'update'    => 'purchase-order.update'
        ],
            'except' => ['show', 'destroy']
        ]);

    Route::any('purchase-order/add-more/{id?}', ['as' => 'purchase-order.add-more',
        'uses' => 'PurchaseOrderController@addMoreUpdateCommon']);
    Route::any('purchase-order/paginate/{page?}', ['as' => 'purchase-order.paginate',
        'uses' => 'PurchaseOrderController@purchasePaginate']);

    // Purchase Order Items
    Route::get('purchase-order/item/drop/{id?}/{item_d?}', ['as' => 'purchase-order-item.drop', 'uses' => 'PurchaseOrderController@dropItem']);
    Route::get('purchase-order/edit/{id?}/{item_id?}', ['as' => 'purchase-order-item.edit', 'uses' => 'PurchaseOrderController@editItem']);
    Route::get('purchase-order/drop/{id?}/{id2?}', ['as' => 'purchase-order.drop',
        'uses' => 'PurchaseOrderController@drop']);
    Route::get('purchase-order/set-purchase-type/{id?}', ['as' => 'purchase-order.set-purchase-type',
        'uses' => 'PurchaseOrderController@setPurchaseType']);

    Route::any('purchase-order/item-detail/{id?}', ['as' => 'purchase-order.item-detail',
        'uses' => 'PurchaseOrderController@invoiceItemDetail']);
    Route::any('purchase-order/purchase-print/{id?}', ['as' => 'purchase-order.purchase-print',
        'uses' => 'PurchaseOrderController@purchasePrint']);
    Route::any('purchase-order/purchase-pdf/{id?}', ['as' => 'purchase-order.purchase-pdf',
        'uses' => 'PurchaseOrderController@generatePdfInvoice']);
    Route::any('purchase-order/send-email/{id}', ['as' => 'purchase-order.send-email',
        'uses' => 'PurchaseOrderController@sendEmail']);

    // Purchase Invoice Routes
    Route::resource('purchase-invoice', 'PurchaseInvoiceController',
        ['names' => [
            'index'     => 'purchase-invoice.index',
            'create'    => 'purchase-invoice.create',
            'store'     => 'purchase-invoice.store',
            'edit'      => 'purchase-invoice.edit',
            'update'    => 'purchase-invoice.update'
        ],
            'except' => ['show', 'destroy']
        ]);

    Route::any('purchase-invoice/add-more/{id?}', ['as' => 'purchase-invoice.add-more',
        'uses' => 'PurchaseInvoiceController@addMoreUpdateCommon']);
    Route::any('purchase-invoice/paginate/{page?}', ['as' => 'purchase-invoice.paginate',
        'uses' => 'PurchaseInvoiceController@purchasePaginate']);

    // Purchase Invoice Items
    Route::get('purchase-invoice/item/drop/{id?}/{item_d?}', ['as' => 'purchase-invoice-item.drop', 'uses' => 'PurchaseInvoiceController@dropItem']);
    Route::get('purchase-invoice/edit/{id?}/{item_id?}', ['as' => 'purchase-invoice-item.edit', 'uses' => 'PurchaseInvoiceController@editItem']);
    Route::get('purchase-invoice/drop/{id?}/{id2?}', ['as' => 'purchase-invoice.drop',
        'uses' => 'PurchaseInvoiceController@drop']);
    Route::get('purchase-invoice/set-purchase-type/{id?}', ['as' => 'purchase-invoice.set-purchase-type',
        'uses' => 'PurchaseInvoiceController@setPurchaseType']);

    Route::any('purchase-invoice/item-detail/{id?}', ['as' => 'purchase-invoice.item-detail',
        'uses' => 'PurchaseInvoiceController@invoiceItemDetail']);
    Route::any('purchase-invoice/purchase-print/{id?}', ['as' => 'purchase-invoice.purchase-print',
        'uses' => 'PurchaseInvoiceController@purchasePrint']);
    Route::any('purchase-invoice/purchase-pdf/{id?}', ['as' => 'purchase-invoice.purchase-pdf',
        'uses' => 'PurchaseInvoiceController@generatePdfInvoice']);
    Route::any('purchase-invoice/send-email/{id}', ['as' => 'purchase-invoice.send-email',
        'uses' => 'PurchaseInvoiceController@sendEmail']);

    // Size Routes
    Route::resource('size', 'SizeController',
        ['names' => [
            'index'     => 'size.index',
            'create'    => 'size.create',
            'store'     => 'size.store',
            'edit'      => 'size.edit',
            'update'    => 'size.update'
        ],
            'except' => ['show', 'destroy']
        ]);


    Route::any('size/paginate/{page?}', ['as' => 'size.paginate', 'uses' => 'SizeController@sizePaginate']);
    Route::any('size/action', ['as' => 'size.action', 'uses' => 'SizeController@sizeAction']);
    Route::any('size/toggle/{id?}', ['as' => 'size.toggle', 'uses' => 'SizeController@sizeToggle']);
    Route::get('size/drop/{id?}', ['as' => 'size.drop']);


    // Product Type Routes
    Route::resource('product-type', 'ProductTypeController',
        ['names' => [
            'index'     => 'product-type.index',
            'create'    => 'product-type.create',
            'store'     => 'product-type.store',
            'edit'      => 'product-type.edit',
            'update'    => 'product-type.update'
        ],
            'except' => ['show', 'destroy']
        ]);

    Route::any('product-type/paginate/{page?}', ['as' => 'product-type.paginate',
        'uses' => 'ProductTypeController@productTypePaginate']);
    Route::any('product-type/action', ['as' => 'product-type.action',
        'uses' => 'ProductTypeController@productTypeAction']);
    Route::any('product-type/toggle/{id?}', ['as' => 'product-type.toggle',
        'uses' => 'ProductTypeController@productTypeToggle']);
    Route::get('product-type/drop/{id?}', ['as' => 'product-type.drop',
        'uses' => 'ProductTypeController@drop']);

    // Product Group Routes
    Route::resource('product-group', 'ProductGroupController',
        ['names' => [
            'index'     => 'product-group.index',
            'create'    => 'product-group.create',
            'store'     => 'product-group.store',
            'edit'      => 'product-group.edit',
            'update'    => 'product-group.update'
        ],
            'except' => ['show', 'destroy']
        ]);

    Route::any('product-group/paginate/{page?}', ['as' => 'product-group.paginate',
        'uses' => 'ProductGroupController@productGroupPaginate']);
    Route::any('product-group/action', ['as' => 'product-group.action',
        'uses' => 'ProductGroupController@productGroupAction']);
    Route::any('product-group/toggle/{id?}', ['as' => 'product-group.toggle',
        'uses' => 'ProductGroupController@productGroupToggle']);
    Route::get('product-group/drop/{id?}', ['as' => 'product-group.drop',
        'uses' => 'ProductGroupController@drop']);
    Route::any('product-group/product-group-modal/', ['as' => 'product-group.product-group-modal',
        'uses' => 'ProductGroupController@productGroupModal']);

    Route::any('product-group/upload-excel', ['as' => 'product-group.upload-excel',
        'uses' => 'ProductGroupController@uploadExcel']);

    // Product Routes
    Route::resource('products', 'ProductsController',
        ['names' => [
            'index'     => 'products.index',
            'create'    => 'products.create',
            'store'     => 'products.store',
            'edit'      => 'products.edit',
            'update'    => 'products.update'
        ],
            'except' => ['show', 'destroy']
        ]);
    Route::any('products/paginate/{page?}', ['as' => 'products.paginate',
        'uses' => 'ProductsController@productPaginate']);
    Route::any('products/action', ['as' => 'products.action',
        'uses' => 'ProductsController@productAction']);
    Route::any('products/toggle/{id?}', ['as' => 'products.toggle',
        'uses' => 'ProductsController@productToggle']);
    Route::get('products/drop/{id?}', ['as' => 'product.drop',
        'uses' => 'ProductsController@drop']);
    Route::get('products/get-price/{id?}/{list_id?}/{size_id?}', ['as' => 'products.get-price', 'uses' => 'ProductsController@getPrice']);
    Route::get('products/get-sizes/{id?}', ['as' => 'products.get-sizes',
        'uses' => 'ProductsController@getProductSizes']);
    Route::get('products/get-cost/{id?}', ['as' => 'products.get-cost',
        'uses' => 'ProductsController@getProductCost']);
    Route::get('products/get-information/{id?}', ['as' => 'products.get-info',
        'uses' => 'ProductsController@getProductDetailForInvoice']);
    Route::get('products/get-product-size/{id?}', ['as' => 'products.get-product-size',
        'uses' => 'ProductsController@getProductSizeList']);
    Route::get('products/get-product-info/{id?}', ['as' => 'products.get-product-info',
        'uses' => 'ProductsController@getProductInfo']);
    Route::get('products/generate-excel', ['as' => 'products.generate-excel',
        'uses' => 'ProductsController@generateExcel']);
    Route::any('products/upload-excel', ['as' => 'products.upload-excel',
        'uses' => 'ProductsController@uploadExcel']);
    Route::any('products/download-sample-excel', ['as' => 'products.download-sample-excel',
        'uses' => 'ProductsController@downloadSampleExcel']);
    Route::any('products/ajax-product', ['as' => 'products.ajax-product',
        'uses' => 'ProductsController@ajaxProduct']);
    Route::get('products/get-product-information/{id?}/{size_id?}/{cid_id?}', ['as' => 'products.get-product-info',
        'uses' => 'ProductsController@getProductInfo']);
    Route::any('products/ajax-product-not-finished', ['as' => 'products.ajax-not-finished-product',
        'uses' => 'ProductsController@ajaxNotFinishedProduct']);

    // Bank Routes
    Route::resource('bank', 'BankController',
        ['names' => [
            'index'     => 'bank.index',
            'create'    => 'bank.create',
            'store'     => 'bank.store',
            'edit'      => 'bank.edit',
            'update'    => 'bank.update'
        ],
            'except' => ['show', 'destroy']
        ]);

    Route::any('bank/paginate/{page?}', ['as' => 'bank.paginate', 'uses' => 'BankController@bankPaginate']);
    Route::any('bank/action', ['as' => 'bank.action', 'uses' => 'BankController@bankAction']);
    Route::any('bank/toggle/{id?}', ['as' => 'bank.toggle', 'uses' => 'BankController@bankToggle']);
    Route::get('bank/drop/{id?}', ['as' => 'bank.drop', 'uses' => 'BankController@drop']);
    /*Route::any('bank/upload-statement/{id?}', ['as' => 'bank.upload_statement',
        'uses' => 'BankController@uploadStatement']);
    Route::any('bank/list-upload-statement/{id?}', ['as' => 'bank.list_upload_statement',
        'uses' => 'BankController@viewUploadStatement']);
    Route::any('bank/save-statement/{id?}', ['as' => 'bank.save_statement',
        'uses' => 'BankController@saveStatement']);
    Route::any('bank/bank-statement', ['as' => 'bank.bank_statement',
        'uses' => 'BankController@bankStatement']);
    Route::any('bank/bank-statement-paginate/{page?}', ['as' => 'bank.bank_statement_paginate',
        'uses' => 'BankController@bankStatementPaginate']);*/

    // Store Master Routes
    Route::resource('store-master','StoreMasterController');

    Route::any('store-master/action',       ['as' => 'store-master.action',   'uses' => 'StoreMasterController@storeMasterAction']);
    Route::any('store-master/paginate',     ['as' => 'store-master.paginate', 'uses' => 'StoreMasterController@storeMasterPaginate']);
    Route::any('store-master/toggle/{id?}', ['as' => 'store-master.toggle',   'uses' => 'StoreMasterController@storeMasterToggle']);
    Route::get('store-master/drop/{id?}',   ['as' => 'store-master.drop',     'uses' => 'StoreMasterController@drop']);

// Racks Routes
    Route::resource('racks', 'RacksController',
        ['names' => [
            //'index'     => 'racks.index',
            //'create'    => 'racks.create',
            'store'     => 'racks.store',
            'edit'      => 'racks.edit',
            'update'    => 'racks.update'
        ],
            'except' => ['show', 'destroy', 'create', 'index']
        ]);


    Route::any('racks/create/{id?}/{isMultiple?}', ['as' => 'racks.create',   'uses' => 'RacksController@create']);
    Route::any('racks/index/{storeId?}',           ['as' => 'racks.index',    'uses' => 'RacksController@index']);
    Route::any('racks/action',                     ['as' => 'racks.action',   'uses' => 'RacksController@rackAction']);
    Route::any('racks/paginate/{storeId?}',        ['as' => 'racks.paginate', 'uses' => 'RacksController@rackPaginate']);
    Route::any('racks/toggle/{id?}',               ['as' => 'racks.toggle',   'uses' => 'RacksController@rackToggle']);
    Route::get('racks/drop/{id?}',                 ['as' => 'racks.drop',     'uses' => 'RacksController@drop']);


    // Shelves Routes
    Route::resource('shelves', 'ShelvesController',
        ['names' => [
            //'index'     => 'shelves.index',
            //'create'    => 'shelves.create',
            'store'     => 'shelves.store',
            'edit'      => 'shelves.edit',
            'update'    => 'shelves.update'
        ],
            'except' => ['show', 'destroy', 'create']
        ]);


    Route::any('shelves/index/{rackId?}',            ['as' => 'shelves.index',    'uses' => 'ShelvesController@index']);
    Route::any('shelves/create/{id?}/{isMultiple?}', ['as' => 'shelves.create',   'uses' => 'ShelvesController@create']);
    Route::any('shelves/action',                     ['as' => 'shelves.action',   'uses' => 'ShelvesController@shelvesAction']);
    Route::any('shelves/paginate/{rackId?}',         ['as' => 'shelves.paginate', 'uses' => 'ShelvesController@shelvesPaginate']);
    Route::any('shelves/toggle/{id?}',               ['as' => 'shelves.toggle',   'uses' => 'ShelvesController@shelvesToggle']);
    Route::get('shelves/drop/{id?}',                 ['as' => 'shelves.drop',     'uses' => 'ShelvesController@drop']);


// Price List Routes
    Route::resource('price-list','PriceListController');

    Route::any('price-list/action',                  ['as' => 'price-list.action',   'uses' => 'PriceListController@priceListAction']);
    Route::any('price-list/paginate/{page?}',        ['as' => 'price-list.paginate', 'uses' => 'PriceListController@priceListPaginate']);
    Route::any('price-list/products/{id?}/{brand?}', ['as' => 'price-list.products', 'uses' => 'PriceListController@getListProducts']);
    Route::any('price-list/toggle/{id?}',            ['as' => 'price-list.toggle',   'uses' => 'PriceListController@priceListToggle']);
    Route::get('price-list/drop/{id?}',              ['as' => 'price-list.drop',     'uses' => 'PriceListController@drop']);



    //productBom
    /*Route::get('products/get-product-bom-information/{id?}', ['as' => 'products.get-infoEdit',
        'uses' => 'ProductsController@getProductDetailForBom']);*/

    //department Routes
    Route::resource('department', 'DepartmentController');

    Route::any('department/paginate/{page?}', ['as' => 'department.paginate',
        'uses' => 'DepartmentController@departmentPaginate']);
    Route::any('department/action', ['as' => 'department.action',
        'uses' => 'DepartmentController@departmentAction']);
    Route::any('department/toggle/{id?}', ['as' => 'department.toggle',
        'uses' => 'DepartmentController@departmentToggle']);
    Route::get('department/drop/{id?}', ['as' => 'department.drop',
        'uses' => 'DepartmentController@drop']);

    //Employee Routes
    Route::resource('employee', 'EmployeeController');
      
    Route::any('employee/paginate/{page?}', ['as' => 'employee.paginate',
        'uses' => 'EmployeeController@employeePaginate']);
    Route::any('employee/action', ['as' => 'employee.action',
        'uses' => 'EmployeeController@employeeAction']);
    Route::any('employee/toggle/{id?}', ['as' => 'employee.toggle',
        'uses' => 'EmployeeController@employeeToggle']);
    Route::get('employee/drop/{id?}', ['as' => 'employee.drop',
        'uses' => 'EmployeeController@drop']);
});



