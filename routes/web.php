<?php

use Illuminate\Support\Facades\Route;


Route::get('/', ['as' => 'index', 'uses' => 'Auth\LoginController@index']);
Route::get('/auth', ['as' => 'login', 'uses' => 'Auth\LoginController@index']);
Route::post('login', ['as' => 'login_process', 'uses' => 'Auth\LoginController@loginprocess']);
Route::match(['get', 'post'], 'logout', ['as' => 'logout', 'uses' => 'Auth\LoginController@logout']);
Route::get('/scan', ['as' => 'scan', 'uses' => 'ProductScannerController']);
Route::get('/waiting-list', ['as' => 'waiting-list', 'uses' => 'WaitingListController']);


Route::middleware(['auth'])->group(function () {
    Route::match(['post', 'get'], '/profile', 'Auth\LoginController@profile')->name('profile');
    Route::get('/dashboard', 'DashboardController@index')->name('dashboard');
    Route::get('/reports', 'ReportsController@index')->name('reports');
    Route::get('/run_nearos', 'ReportsController@run_nearos')->name('run_nearos');
    Route::get('/run_retail_nearos', 'ReportsController@run_retail_nearos')->name('run_retail_nearos');
    Route::get('/run_moving_stock', 'ReportsController@run_moving_stock')->name('run_moving_stock');

    Route::prefix('ajax')->namespace('Ajax')->group(function () {
        Route::get('/findstock', ['as' => 'findstock', 'uses' => 'AjaxController@findstock']);
        Route::get('/findStockByBarcode', ['as' => 'findStockByBarcode', 'uses' => 'AjaxController@findStockByBarcode']);
        Route::get('/findPurchaseProduct', ['as' => 'findpurchasestock', 'uses' => 'AjaxController@findpurchasestock']);
        Route::get('/findcustomer', ['as' => 'findcustomer', 'uses' => 'AjaxController@findcustomer']);
        Route::get('/profitandlossdatatable', ['as' => 'profitandlossdatatable', 'uses' => 'AjaxController@profitandlossdatatable']);
        Route::get('/profitandlossdatatablebydepartment', ['as' => 'profitandlossdatatablebydepartment', 'uses' => 'AjaxController@profitandlossdatatablebydepartment']);
        Route::get('/supplier_sales_analysis_table', ['as' => 'supplier_sales_analysis_table', 'uses' => 'AjaxController@supplier_sales_analysis_table']);
    });


    Route::middleware(['permit.task'])->group(function () {
        Route::prefix('accesscontrol')->namespace('AccessControl')->group(function () {
            Route::prefix('user-group')->as('user.group.')->group(function () {
                Route::get('', ['as' => 'index', 'uses' => 'GroupController@index', 'visible' => true]);
                Route::get('list', ['as' => 'list', 'uses' => 'GroupController@list_all']);
                Route::get('create', ['as' => 'create', 'uses' => 'GroupController@create']);
                Route::post('', ['as' => 'store', 'uses' => 'GroupController@store']);
                Route::match(['get', 'post'], '{group}/permission', ['as' => 'permission', 'uses' => 'GroupController@permission']);
                Route::get('{id}/fetch_task', ['as' => 'task', 'uses' => 'GroupController@fetch_task']);
                Route::get('{id}', ['as' => 'show', 'uses' => 'GroupController@show']);
                Route::get('{id}/edit', ['as' => 'edit', 'uses' => 'GroupController@edit']);
                Route::get('{id}/toggle', ['as' => 'toggle', 'uses' => 'GroupController@toggle']);
                Route::put('{id}', ['as' => 'update', 'uses' => 'GroupController@update']);
                Route::get('{id}', ['as' => 'destroy', 'uses' => 'GroupController@destroy']);
            });
            Route::prefix('user')->as('user.')->group(function () {
                Route::get('', ['as' => 'index', 'uses' => 'UserController@index', 'visible' => true]);
                Route::get('list', ['as' => 'list', 'uses' => 'UserController@listAll']);
                Route::get('create', ['as' => 'create', 'uses' => 'UserController@create']);
                Route::post('', ['as' => 'store', 'uses' => 'UserController@store']);
                Route::get('{id}', ['as' => 'show', 'uses' => 'UserController@show']);
                Route::get('{id}/edit', ['as' => 'edit', 'uses' => 'UserController@edit']);
                Route::get('{id}/toggle', ['as' => 'toggle', 'uses' => 'UserController@toggle']);
                Route::put('{id}', ['as' => 'update', 'uses' => 'UserController@update']);
                Route::get('{id}', ['as' => 'destroy', 'uses' => 'UserController@destroy']);
            });
        });
        Route::prefix('settings')->namespace('Settings')->group(function () {
            Route::prefix('bank')->as('bank.')->group(function () {
                Route::get('', ['as' => 'index', 'uses' => 'BankController@index', 'visible' => true, 'custom_label'=>'Accounts Manager']);
                Route::get('list', ['as' => 'list', 'uses' => 'BankController@listAll']);
                Route::get('create', ['as' => 'create', 'uses' => 'BankController@create']);
                Route::post('', ['as' => 'store', 'uses' => 'BankController@store']);
                Route::get('{id}', ['as' => 'show', 'uses' => 'BankController@show']);
                Route::get('{id}/edit', ['as' => 'edit', 'uses' => 'BankController@edit']);
                Route::get('{id}/toggle', ['as' => 'toggle', 'uses' => 'BankController@toggle']);
                Route::put('{id}', ['as' => 'update', 'uses' => 'BankController@update']);
                Route::delete('{id}', ['as' => 'destroy', 'uses' => 'BankController@destroy']);
            });
            Route::prefix('department')->as('department.')->group(function () {
                Route::get('', ['as' => 'index', 'uses' => 'DepartmentController@index', 'visible' => true, 'custom_label'=>'Departments']);
                Route::get('list', ['as' => 'list', 'uses' => 'DepartmentController@listAll']);
                Route::get('create', ['as' => 'create', 'uses' => 'DepartmentController@create']);
                Route::post('', ['as' => 'store', 'uses' => 'DepartmentController@store']);
                Route::get('{id}', ['as' => 'show', 'uses' => 'DepartmentController@show']);
                Route::get('{id}/edit', ['as' => 'edit', 'uses' => 'DepartmentController@edit']);
                Route::get('{id}/toggle', ['as' => 'toggle', 'uses' => 'DepartmentController@toggle']);
                Route::put('{id}', ['as' => 'update', 'uses' => 'DepartmentController@update']);
                Route::delete('{id}', ['as' => 'destroy', 'uses' => 'DepartmentController@destroy']);
            });
            Route::prefix('supplier')->as('supplier.')->group(function () {
                Route::get('', ['as' => 'index', 'uses' => 'SupplierController@index', 'visible' => true, 'custom_label'=>'Supplier Manager']);
                Route::get('list', ['as' => 'list', 'uses' => 'SupplierController@listAll']);
                Route::get('create', ['as' => 'create', 'uses' => 'SupplierController@create']);
                Route::post('', ['as' => 'store', 'uses' => 'SupplierController@store']);
                Route::get('{id}', ['as' => 'show', 'uses' => 'SupplierController@show']);
                Route::get('{id}/edit', ['as' => 'edit', 'uses' => 'SupplierController@edit']);
                Route::get('{id}/toggle', ['as' => 'toggle', 'uses' => 'SupplierController@toggle']);
                Route::put('{id}', ['as' => 'update', 'uses' => 'SupplierController@update']);
                Route::delete('{id}', ['as' => 'destroy', 'uses' => 'SupplierController@destroy']);
            });
            Route::prefix('manufacturer')->as('manufacturer.')->group(function () {
                Route::get('', ['as' => 'index', 'uses' => 'ManufacturerController@index', 'visible' => true, 'custom_label'=>'Manufacturers']);
                Route::get('list', ['as' => 'list', 'uses' => 'ManufacturerController@listAll']);
                Route::get('create', ['as' => 'create', 'uses' => 'ManufacturerController@create']);
                Route::post('', ['as' => 'store', 'uses' => 'ManufacturerController@store']);
                Route::get('{id}', ['as' => 'show', 'uses' => 'ManufacturerController@show']);
                Route::get('{id}/edit', ['as' => 'edit', 'uses' => 'ManufacturerController@edit']);
                Route::get('{id}/toggle', ['as' => 'toggle', 'uses' => 'ManufacturerController@toggle']);
                Route::put('{id}', ['as' => 'update', 'uses' => 'ManufacturerController@update']);
                Route::delete('{id}', ['as' => 'destroy', 'uses' => 'ManufacturerController@destroy']);
            });
            Route::prefix('brand')->as('brand.')->group(function () {
                Route::get('', ['as' => 'index', 'uses' => 'BrandController@index', 'visible' => true, 'custom_label'=>'Brands']);
                Route::get('list', ['as' => 'list', 'uses' => 'BrandController@listAll']);
                Route::get('create', ['as' => 'create', 'uses' => 'BrandController@create']);
                Route::post('', ['as' => 'store', 'uses' => 'BrandController@store']);
                Route::get('{id}', ['as' => 'show', 'uses' => 'BrandController@show']);
                Route::get('{id}/edit', ['as' => 'edit', 'uses' => 'BrandController@edit']);
                Route::get('{id}/toggle', ['as' => 'toggle', 'uses' => 'BrandController@toggle']);
                Route::put('{id}', ['as' => 'update', 'uses' => 'BrandController@update']);
                Route::delete('{id}', ['as' => 'destroy', 'uses' => 'BrandController@destroy']);
            });
            Route::prefix('category')->as('category.')->group(function () {
                Route::get('', ['as' => 'index', 'uses' => 'CategoryController@index', 'visible' => true, 'custom_label'=>'Product Category']);
                Route::get('list', ['as' => 'list', 'uses' => 'CategoryController@listAll']);
                Route::get('create', ['as' => 'create', 'uses' => 'CategoryController@create']);
                Route::post('', ['as' => 'store', 'uses' => 'CategoryController@store']);
                Route::get('{id}', ['as' => 'show', 'uses' => 'CategoryController@show']);
                Route::get('{id}/edit', ['as' => 'edit', 'uses' => 'CategoryController@edit']);
                Route::get('{id}/toggle', ['as' => 'toggle', 'uses' => 'CategoryController@toggle']);
                Route::put('{id}', ['as' => 'update', 'uses' => 'CategoryController@update']);
                Route::delete('{id}', ['as' => 'destroy', 'uses' => 'CategoryController@destroy']);
            });
            Route::prefix('classification')->as('classification.')->group(function () {
                Route::get('', ['as' => 'index', 'uses' => 'ClassificationController@index', 'visible' => true, 'custom_label'=>'Classification']);
                Route::get('list', ['as' => 'list', 'uses' => 'ClassificationController@listAll']);
                Route::get('create', ['as' => 'create', 'uses' => 'ClassificationController@create']);
                Route::post('', ['as' => 'store', 'uses' => 'ClassificationController@store']);
                Route::get('{id}', ['as' => 'show', 'uses' => 'ClassificationController@show']);
                Route::get('{id}/edit', ['as' => 'edit', 'uses' => 'ClassificationController@edit']);
                Route::get('{id}/toggle', ['as' => 'toggle', 'uses' => 'ClassificationController@toggle']);
                Route::put('{id}', ['as' => 'update', 'uses' => 'ClassificationController@update']);
                Route::delete('{id}', ['as' => 'destroy', 'uses' => 'ClassificationController@destroy']);
            });
            Route::prefix('stockgroup')->as('stockgroup.')->group(function () {
                Route::get('', ['as' => 'index', 'uses' => 'StockGroupController@index', 'visible' => true, 'custom_label'=>'Stock Group']);
                Route::get('list', ['as' => 'list', 'uses' => 'StockGroupController@listAll']);
                Route::get('create', ['as' => 'create', 'uses' => 'StockGroupController@create']);
                Route::post('', ['as' => 'store', 'uses' => 'StockGroupController@store']);
                Route::get('{id}', ['as' => 'show', 'uses' => 'StockGroupController@show']);
                Route::get('{id}/edit', ['as' => 'edit', 'uses' => 'StockGroupController@edit']);
                Route::get('{id}/toggle', ['as' => 'toggle', 'uses' => 'StockGroupController@toggle']);
                Route::put('{id}', ['as' => 'update', 'uses' => 'StockGroupController@update']);
                Route::delete('{id}', ['as' => 'destroy', 'uses' => 'StockGroupController@destroy']);
            });
            Route::prefix('payment_method')->as('payment_method.')->group(function () {
                Route::get('', ['as' => 'index', 'uses' => 'PaymentMethodController@index', 'visible' => true, 'custom_label'=>'Payment Methods']);
                Route::get('list', ['as' => 'list', 'uses' => 'PaymentMethodController@listAll']);
                Route::get('create', ['as' => 'create', 'uses' => 'PaymentMethodController@create']);
                Route::post('', ['as' => 'store', 'uses' => 'PaymentMethodController@store']);
                Route::get('{id}', ['as' => 'show', 'uses' => 'PaymentMethodController@show']);
                Route::get('{id}/edit', ['as' => 'edit', 'uses' => 'PaymentMethodController@edit']);
                Route::get('{id}/toggle', ['as' => 'toggle', 'uses' => 'PaymentMethodController@toggle']);
                Route::put('{id}', ['as' => 'update', 'uses' => 'PaymentMethodController@update']);
                Route::delete('{id}', ['as' => 'destroy', 'uses' => 'PaymentMethodController@destroy']);
            });
            Route::prefix('store_settings')->as('store_settings.')->group(function () {
                Route::get('', ['as' => 'view', 'uses' => 'StoreSettings@show', 'visible' => true, 'custom_label'=>"System Settings"]);
                Route::put('update', ['as' => 'update', 'uses' => 'StoreSettings@update']);
                // Route::get('backup', ['as' => 'backup', 'uses' => 'StoreSettings@backup', 'visible' => true,'custom_label'=>"Back Up Database"]);
            });

            Route::prefix('expenses_type')->as('expenses_type.')->group(function () {
                Route::get('', ['as' => 'index', 'uses' => 'ExpensesTypeController@index', 'visible' => true, 'custom_label'=>'Expenses Type']);
                Route::get('list', ['as' => 'list', 'uses' => 'ExpensesTypeController@listAll']);
                Route::get('create', ['as' => 'create', 'uses' => 'ExpensesTypeController@create']);
                Route::post('', ['as' => 'store', 'uses' => 'ExpensesTypeController@store']);
                Route::get('{id}', ['as' => 'show', 'uses' => 'ExpensesTypeController@show']);
                Route::get('{id}/edit', ['as' => 'edit', 'uses' => 'ExpensesTypeController@edit']);
                Route::get('{id}/toggle', ['as' => 'toggle', 'uses' => 'ExpensesTypeController@toggle']);
                Route::put('{id}', ['as' => 'update', 'uses' => 'ExpensesTypeController@update']);
                Route::delete('{id}', ['as' => 'destroy', 'uses' => 'ExpensesTypeController@destroy']);
            });

        });
        Route::prefix('CustomerManager')->namespace('CustomerManager')->group(function () {
            Route::prefix('customer')->as('customer.')->group(function () {
                Route::get('', ['as' => 'index', 'uses' => 'CustomerController@index', 'visible' => true, 'custom_label'=>'List Whole Customer']);
                Route::get('/retails', ['as' => 'retail', 'uses' => 'CustomerController@retails', 'visible' => true, 'custom_label'=>'List retail Customer']);
                Route::get('list', ['as' => 'list', 'uses' => 'CustomerController@list_all']);
                Route::get('create', ['as' => 'create', 'uses' => 'CustomerController@create']);
                Route::post('', ['as' => 'store', 'uses' => 'CustomerController@store']);
                Route::get('{id}/show', ['as' => 'show', 'uses' => 'CustomerController@show']);
                Route::get('{id}/edit', ['as' => 'edit', 'uses' => 'CustomerController@edit']);
                Route::put('{id}', ['as' => 'update', 'uses' => 'CustomerController@update']);
                Route::match(['get','post'],'/credit_report', ['as' => 'credit_report', 'uses' => 'CustomerController@credit_report', 'custom_label'=>"Customer Credit Report"]);
                Route::match(['get','post'],'/payment_report', ['as' => 'payment_report', 'uses' => 'CustomerController@payment_report', 'custom_label'=>"Customer Payment Report"]);
                Route::match(['get','post'],'/balance_sheet', ['as' => 'balance_sheet', 'uses' => 'CustomerController@balance_sheet', 'custom_label'=>"Customer Balance Sheet"]);
            });
        });
        Route::prefix('expenses')->namespace('Expenses')->group(function () {
            Route::prefix('expenses')->as('expenses.')->group(function () {
                Route::get('', ['as' => 'index', 'uses' => 'ExpensesController@index', 'visible' => true]);
                Route::get('create', ['as' => 'create', 'uses' => 'ExpensesController@create', 'visible' => true, "custom_label"=>"New Expense"]);
                Route::get('{expense}/edit', ['as' => 'edit', 'uses' => 'ExpensesController@edit', "custom_label"=>"Update Expense"]);
                Route::delete('{id}', ['as' => 'destroy', 'uses' => 'ExpensesController@destroy']);
            });
        });
        Route::prefix('stock')->namespace('ProductManager')->group(function () {
            Route::prefix('product')->as('product.')->group(function () {
                Route::get('', ['as' => 'index', 'uses' => 'ProductController@index', 'visible' => true,'custom_label'=>'List Stock']);
                Route::get('/available', ['as' => 'available', 'uses' => 'ProductController@available', 'visible' => true,'custom_label'=>'List Available']);
                Route::get('/otherinfo', ['as' => 'otherinfo', 'uses' => 'ProductController@otherinfo', 'visible' => true,'custom_label'=>'List Other info']);
                Route::get('create', ['as' => 'create', 'uses' => 'ProductController@create','visible' => true, 'custom_label'=>'Add Stock']);
                Route::get('expired', ['as' => 'expired', 'uses' => 'ProductController@expired','visible' => true]);
                Route::get('near_expired', ['as' => 'near_expired', 'uses' => 'ProductController@near_expired','visible' => true,
                    'custom_label'=>'Near Expiration Stock']);
                Route::get('disable', ['as' => 'disable', 'uses' => 'ProductController@disabled','visible' => true,'custom_label'=>'List Disabled Stock']);
                Route::get('non_reorder', ['as' => 'non_reorder', 'uses' => 'ProductController@non_reorder','visible' => true,'custom_label'=>'Non Re-order List']);
                Route::match(['post', 'get'],'stock_balance_by_supplier', ['as' => 'stock_balance_by_supplier', 'uses' => 'ProductController@stock_balance_by_supplier','visible' => true,'custom_label'=>'Stock Balance By Supplier']);

                Route::match(['post', 'get'],'stock_balance_by_supplier', ['as' => 'stock_balance_by_supplier', 'uses' => 'ProductController@stock_balance_by_supplier','visible' => true,'custom_label'=>'Stock Balance By Supplier']);

                Route::match(['post', 'get'],'batched_stock_list', ['as' => 'batched_stock_list', 'uses' => 'ProductController@batched_stock_list','visible' => true,'custom_label'=>'Batched Stock List']);

                Route::get('export', ['as' => 'export', 'uses' => 'ProductController@export']);
                Route::get('{stock}/edit', ['as' => 'edit', 'uses' => 'ProductController@edit','custom_label'=>'Edit Product']);
                Route::get('{stock}/toggle', ['as' => 'toggle', 'uses' => 'ProductController@toggle','custom_label'=>'Toggle Product']);
                //Route::get('/changeCostPrice', ['as' => 'changeCostPrice', 'uses' => 'ProductController@changeCostPrice',
                //   'custom_label'=>'Change Product Cost Price']);
                Route::get('/changeSellingPrice', ['as' => 'changeSellingPrice', 'uses' => 'ProductController@changeSellingPrice', 'custom_label'=>'Change Product Selling Price']);

                Route::match(['post','get'],'balance_stock', ['as' => 'balance_stock', 'uses' => 'ProductController@balance_stock','visible' => true, 'custom_label'=>'Quick Adjust Quantity']);

                Route::match(['post','get'], 'stock_export', ['as' => 'export_stock', 'uses' => 'ProductController@export_stock','custom_label'=>'Export / Import Stocks', 'visible' => true]);

            });
        });
        Route::prefix('transfer')->namespace('StockTransfer')->group(function () {
            Route::prefix('transfer')->as('transfer.')->group(function () {

                Route::get('', ['as' => 'index', 'uses' => 'StockTransferController@index', 'visible' => true,'custom_label'=>' Draft List']);

                Route::get('/approved', ['as' => 'approved', 'uses' => 'StockTransferController@approved', 'visible' => true,'custom_label'=>' Approved List']);

                Route::match(['post','get'], 'create', ['as' => 'create', 'uses' => 'StockTransferController@create','visible' => true, 'custom_label'=>'Create New Transfer']);

                Route::match(['post','get'],'stock-move-list', ['as' => 'stock_move_list', 'uses' => 'StockTransferController@stock_move_list','visible' => true, 'custom_label'=>'Stock to Move List Report']);

                Route::match(['get','post'],'{stocktransfer}/edit', ['as' => 'edit', 'uses' => 'StockTransferController@edit','custom_label'=>'Edit Transfer']);

                Route::get('{stocktransfer}/show', ['as' => 'show', 'uses' => 'StockTransferController@show','custom_label'=>'View Stock Transfer']);

                Route::get('{stocktransfer}/complete', ['as' => 'complete', 'uses' => 'StockTransferController@complete','custom_label'=>'Complete Transfer']);

                Route::get('{stocktransfer}/destroy', ['as' => 'destroy', 'uses' => 'StockTransferController@destroy','custom_label'=>'Delete Transfer']);

            });
        });
        Route::prefix('purchase')->namespace('PurchaseOrder')->group(function () {

            Route::prefix('purchase')->as('purchase.')->group(function () {

                Route::get('', ['as' => 'index', 'uses' => 'PurchaseOrderController@index', 'visible' => true,'custom_label'=>' Draft  List']);

                Route::get('/completed', ['as' => 'approved', 'uses' => 'PurchaseOrderController@completed', 'visible' => true,'custom_label'=>'Completed List']);

                Route::get( 'create', ['as' => 'create', 'uses' => 'PurchaseOrderController@create','visible' => true, 'custom_label'=>'Create New Purchase']);

                Route::get('{purchase}/edit', ['as' => 'edit', 'uses' => 'PurchaseOrderController@edit','custom_label'=>'Edit Purchase']);

                Route::get('{purchase}/show', ['as' => 'show', 'uses' => 'PurchaseOrderController@show','custom_label'=>'Show Purchase Information']);

                Route::get('{purchase}/destroy', ['as' => 'destroy', 'uses' => 'PurchaseOrderController@destroy','custom_label'=>'Delete Purchase Order']);

                Route::get('{purchase}/complete', ['as' => 'complete', 'uses' => 'PurchaseOrderController@complete','custom_label'=>'Complete Purchase Order']);

            });


            Route::prefix('supplier')->as('supplier.')->group(function () {
                Route::prefix('payment')->as('payment.')->group(function () {
                    Route::get('', ['as' => 'index', 'uses' => 'SupplierPaymentController@index', 'visible' => true, 'custom_label'=>"Supplier Payment"]);
                    Route::get('create', ['as' => 'create', 'uses' => 'SupplierPaymentController@create']);
                    Route::get('{supplierCreditPaymentHistory}/edit', ['as' => 'edit', 'uses' => 'SupplierPaymentController@edit']);
                    Route::delete('{supplierCreditPaymentHistory}', ['as' => 'destroy', 'uses' => 'SupplierPaymentController@destroy']);
                });
                Route::prefix('credit')->as('credit.')->group(function () {
                    Route::get('', ['as' => 'index', 'uses' => 'SupplierCreditController@index', 'visible' => true, 'custom_label'=>"Supplier Credit"]);
                });
            });
        });
        Route::prefix('invoiceandsales')->namespace('InvoiceAndSales')->group(function () {
            Route::prefix('invoice')->as('invoiceandsales.')->group(function () {
                Route::get('create', ['as' => 'create', 'uses' => 'InvoiceController@create', 'custom_label'=>'New Invoice', 'visible'=>true]);
                Route::match(['get','post'],'', ['as' => 'draft', 'uses' => 'InvoiceController@draft', 'visible' => true, 'custom_label'=>'Draft Invoice']);
                Route::match(['get','post'],'packing', ['as' => 'packing', 'uses' => 'InvoiceController@packing', 'visible' => true, 'custom_label'=>'Packing Invoice']);
                Route::match(['get','post'],'waiting', ['as' => 'waiting', 'uses' => 'InvoiceController@waiting', 'visible' => true, 'custom_label'=>'Waiting Invoice']);
                Route::match(['get','post'],'alredy_packed', ['as' => 'alredy_packed', 'uses' => 'InvoiceController@alredy_packed', 'visible' => true, 'custom_label'=>'Already Packed Invoice']);
                Route::match(['get','post'],'discount', ['as' => 'discount', 'uses' => 'InvoiceController@discount', 'visible' => true, 'custom_label'=>'Discount Invoice']);
                Route::match(['get','post'],'paid', ['as' => 'paid', 'uses' => 'InvoiceController@paid', 'visible' => true, 'custom_label'=>'Paid Invoice']);
                Route::match(['get','post'],'waiting-for-credit-approval', ['as' => 'waiting-for-credit-approval', 'uses' => 'InvoiceController@waiting_for_credit_approval', 'visible' => true, 'custom_label'=>'Waiting Credit Approval Invoice']);
                Route::match(['get','post'],'waiting-for-cheque-approval', ['as' => 'waiting-for-cheque-approval', 'uses' => 'InvoiceController@waiting_for_cheque_approval', 'visible' => true, 'custom_label'=>'Waiting Cheque Approval Invoice']);
                Route::match(['get','post'],'dispatched', ['as' => 'dispatched', 'uses' => 'InvoiceController@dispatched', 'visible' => true, 'custom_label'=>'Completed Invoice']);
                Route::get('editInvoiceDate', ['as' => 'editInvoiceDate', 'uses' => 'InvoiceController@editInvoiceDate', 'custom_label'=>'Edit Invoice Date']);
                Route::get('deleted', ['as' => 'deleted', 'uses' => 'InvoiceController@deleted', 'visible' => true, 'custom_label'=>'Deleted Invoice']);
                Route::get('{invoice}/pos_print', ['as' => 'pos_print', 'uses' => 'InvoiceController@print_pos','custom_label'=>'Print Thermal' ]);
                Route::get('{invoice}/print_afour', ['as' => 'print_afour', 'uses' => 'InvoiceController@print_afour', 'custom_label'=>'Print A4 Invoice']);
                Route::get('{invoice}/dispatchInvoice', ['as' => 'dispatchInvoice', 'uses' => 'InvoiceController@dispatchInvoice', 'custom_label'=>'Dispatch Invoice']);
                Route::get('{invoice}/print_way_bill', ['as' => 'print_way_bill', 'uses' => 'InvoiceController@print_way_bill', 'custom_label'=>'Print WayBill']);
                Route::get('{invoice}/view', ['as' => 'view', 'uses' => 'InvoiceController@view']);
                //Route::get('{invoice}/applyForCredit', ['as' => 'applyForCredit', 'uses' => 'InvoiceController@applyForCredit', "custom_label"=>"Apply For Credit Payment Approval"]);
                //Route::get('{invoice}/applyForCheque', ['as' => 'applyForCheque', 'uses' => 'InvoiceController@applyForCheque', "custom_label"=>"Apply For Cheque Payment Approval"]);

                Route::get('{invoice}/approve_or_decline_credit_payment', ['as' => 'approve_or_decline_credit_payment', 'uses' => 'InvoiceController@approve_or_decline_credit_payment', "custom_label"=>"Approve/Decline Credit Payment"]);
                Route::get('{invoice}/approve_or_decline_cheque_payment', ['as' => 'approve_or_decline_cheque_payment', 'uses' => 'InvoiceController@approve_or_decline_cheque_payment', "custom_label"=>"Approve/Decline Cheque Payment"]);

                Route::get('{invoice}/applyProductDiscount', ['as' => 'applyProductDiscount', 'uses' => 'InvoiceController@applyProductDiscount', "custom_label"=>"Apply Product Discount"]);
                Route::get('{invoice}/applyInvoiceDiscount', ['as' => 'applyInvoiceDiscount', 'uses' => 'InvoiceController@applyInvoiceDiscount', "custom_label"=>"Apply Invoice Discount"]);
                Route::match(['get', 'post'],'requestForDiscount', ['as' => 'requestForDiscount', 'uses' => 'InvoiceController@requestForDiscount', 'custom_label'=>'Request For Discount']);
                Route::get('{invoice}/edit', ['as' => 'edit', 'uses' => 'InvoiceController@edit']);
                Route::get('{invoice}/return', ['as' => 'return', 'uses' => 'InvoiceController@return']);
                Route::get('{invoice}/destroy', ['as' => 'destroy', 'uses' => 'InvoiceController@destroy']);
                Route::put('{invoice}/update', ['as' => 'update', 'uses' => 'InvoiceController@update']);
                Route::match(['get', 'post'], 'merge', ['as' => 'merge', 'uses' => 'InvoiceController@mergeInvoice', 'custom_label'=>'Merge Invoice', 'visible' => true]);
                Route::match(['get', 'post'], 'checkoutScan', ['as' => 'checkoutScan', 'uses' => 'InvoiceController@checkOutInvoice', 'custom_label'=>'Scan Invoice for Product Checkout', 'visible' => true]);
                Route::get( 'rePrintInvoice', ['as' => 'rePrintInvoice', 'uses' => 'InvoiceController@rePrintInvoice', 'custom_label'=>'Re-print Invoice Retail Receipt']);
                Route::get('{invoice}/processOnlineInvoice', ['as' => 'processOnlineInvoice', 'uses' => 'InvoiceController@processOnlineInvoice', 'custom_label'=>'Process/Pack Online Invoice']);
                Route::get('{invoice}/packOnlineInvoice', ['as' => 'packOnlineInvoice', 'uses' => 'InvoiceController@packOnlineInvoice', 'custom_label'=>'Mark Online Invoice has Packed']);
                Route::get('{invoice}/addToWaitingList', ['as' => 'addToWaitingList', 'uses' => 'InvoiceController@addToWaitingList', 'custom_label'=>'Add Invoice To Waiting List']);
                Route::get('{invoice}/removeFromWaitingList', ['as' => 'removeFromWaitingList', 'uses' => 'InvoiceController@removeFromWaitingList', 'custom_label'=>'Remove Invoice To Waiting List']);
                Route::get('{invoice}/packWaitingListInvoice', ['as' => 'packWaitingListInvoice', 'uses' => 'InvoiceController@packWaitingListInvoice', 'custom_label'=>'Set Waiting Customer invoice to Packing']);
                Route::get('{invoice}/pickWaitingListInvoice', ['as' => 'pickWaitingListInvoice', 'uses' => 'InvoiceController@pickWaitingListInvoice', 'custom_label'=>'Set Waiting Customer invoice to Picking']);
                Route::get('{invoice}/completePickingWaitingListInvoice', ['as' => 'completePickingWaitingListInvoice', 'uses' => 'InvoiceController@completePickingWaitingListInvoice', 'custom_label'=>'Set Waiting Customer invoice to Packed']);
                Route::get('{invoice}/packedWaitingListInvoice', ['as' => 'packedWaitingListInvoice', 'uses' => 'InvoiceController@packedWaitingListInvoice', 'custom_label'=>'Set Waiting Customer invoice to Packed']);

            });
        });
        Route::prefix('promotion')->namespace('PromotionManager')->group(function () {
            Route::prefix('promo')->as('promo.')->group(function () {
                Route::get('', ['as' => 'index', 'uses' => 'PromotionManagerController@index', 'visible' => true, 'custom_label'=>'List Promotion (s)']);
                Route::get('list', ['as' => 'list', 'uses' => 'PromotionManagerController@listAll']);
                Route::get('create', ['as' => 'create', 'uses' => 'PromotionManagerController@create', 'custom_label'=>'Create Promotion']);
                Route::post('', ['as' => 'store', 'uses' => 'PromotionManagerController@store']);
                Route::get('{promotion}', ['as' => 'show', 'uses' => 'PromotionManagerController@show', 'custom_label'=>'Show Promotion']);
                Route::get('{promotion}/edit', ['as' => 'edit', 'uses' => 'PromotionManagerController@edit']);
                Route::get('{promotion}/approve', ['as' => 'approve', 'uses' => 'PromotionManagerController@approve', 'custom_label'=>'Approve Promotion']);
                Route::get('{promotion}/toggle', ['as' => 'toggle', 'uses' => 'PromotionManagerController@toggle']);
                Route::get('{promotion}/update', ['as' => 'update', 'uses' => 'PromotionManagerController@update', 'custom_label'=>'Edit Promotion']);
                Route::delete('{id}', ['as' => 'destroy', 'uses' => 'PromotionManagerController@destroy', 'custom_label'=>'Delete Promotion']);
            });
        });
        Route::prefix('retailsales')->namespace('RetailSales')->group(function () {
            Route::prefix('retail')->as('retailsales.')->group(function () {

                Route::get('create', ['as' => 'create', 'uses' => 'RetailSalesController@create', 'custom_label'=>'New Retail Sales', 'visible'=>true]);

                Route::match(['get', 'post'],'', ['as' => 'sales', 'uses' => 'RetailSalesController@sales', 'visible' => true, 'custom_label'=>'Sales List',]);

                Route::match(['get', 'post'],'draft', ['as' => 'draft', 'uses' => 'RetailSalesController@draft', 'visible' => true, 'custom_label'=>'Draft Sales List']);

                Route::get('{invoice}/edit', ['as' => 'edit', 'uses' => 'RetailSalesController@edit']);

                Route::get('{invoice}/pos_print', ['as' => 'pos_print', 'uses' => 'RetailSalesController@print_pos','custom_label'=>'Print Thermal' ]);

                Route::get('{invoice}/view', ['as' => 'view', 'uses' => 'RetailSalesController@view']);


                Route::match(['get', 'post'],'requestForDiscount', ['as' => 'requestForDiscount', 'uses' => 'RetailSalesController@requestForDiscount', 'custom_label'=>'Request For Discount']);

            });
        });
        Route::prefix('paymentmanager')->namespace('PaymentManager')->group(function () {
            Route::prefix('payment')->as('payment.')->group(function () {
                Route::get('create', ['as' => 'create', 'uses' => 'PaymentController@create', 'custom_label'=>'Add Payment', 'visible'=>true]);
                Route::get('list', ['as' => 'list', 'uses' => 'PaymentController@list_payment', 'custom_label'=>'List Payment', 'visible'=>true]);
                Route::get('{payment}/show', ['as' => 'show', 'uses' => 'PaymentController@show', 'custom_label'=>'View Payment Details']);
                Route::get('{payment}/destroy', ['as' => 'destroy', 'uses' => 'PaymentController@destroy', 'custom_label'=>'Delete Payment', ]);
                Route::get('{payment}/print', ['as' => 'print', 'uses' => 'PaymentController@print_payment', 'custom_label'=>'Print Payment Receipt']);
                Route::match(['get', 'post'],'createInvoicePayment', ['as' => 'createInvoicePayment', 'uses' => 'PaymentController@createInvoicePayment', 'custom_label'=>'Add Invoice Payment']);

                Route::match(['get', 'post'],'createCreditPayment', ['as' => 'createCreditPayment', 'uses' => 'PaymentController@createCreditPayment', 'custom_label'=>'Add Credit Payment']);


                /*
                    Route::get('createDepositPayment', ['as' => 'createDepositPayment', 'uses' => 'PaymentController@createDepositPayment', 'custom_label'=>'Add Deposit Payment']);
                */
            });
        });
        Route::prefix('reports')->as('reports.')->group(function(){

            Route::prefix('purchasesReport')->as('purchase.')->namespace('PurchaseReport')->group(function(){
                Route::match(['get','post'],'by_date', ['as' => 'by_date', 'uses' => 'PurchaseReportsController@index', 'custom_label'=>'Purchase Report By Date']);

                Route::match(['get','post'],'by_supplier', ['as' => 'by_supplier', 'uses' => 'PurchaseReportsController@by_supplier', 'custom_label'=>'Purchase Report By Supplier']);

                Route::match(['get','post'],'by_system_user', ['as' => 'by_system_user', 'uses' => 'PurchaseReportsController@by_system_user', 'custom_label'=>'Purchase Report By User']);

                Route::match(['get','post'],'by_stock', ['as' => 'by_material', 'uses' => 'PurchaseReportsController@by_stock', 'custom_label'=>'Purchase Report By Stock']);

                Route::match(['get','post'],'by_status', ['as' => 'by_status', 'uses' => 'PurchaseReportsController@by_status', 'custom_label'=>'Purchase Report By Status']);

                Route::match(['get','post'],'by_department', ['as' => 'index', 'uses' => 'PurchaseReportsController@by_department', 'custom_label'=>'Purchase Report By Department']);

                Route::match(['get','post'],'supplier_payment', ['as' => 'supplier_payment', 'uses' => 'PurchaseReportsController@supplier_payment', 'custom_label'=>'Supplier Payment Report']);

                Route::match(['get','post'],'supplier_credit', ['as' => 'supplier_credit', 'uses' => 'PurchaseReportsController@supplier_credit', 'custom_label'=>'Supplier Credit Report']);

                Route::match(['get','post'],'balance_sheet', ['as' => 'balance_sheet', 'uses' => 'PurchaseReportsController@balance_sheet', 'custom_label'=>'Supplier Balance Sheet']);
                Route::match(['get','post'],'supplier_ranking', ['as' => 'supplier_ranking', 'uses' => 'PurchaseReportsController@supplier_ranking', 'custom_label'=>'Supplier Ranking Report']);
                Route::match(['get','post'],'supplier_sales_analysis', ['as' => 'supplier_sales_analysis', 'uses' => 'PurchaseReportsController@supplier_sales_analysis', 'custom_label'=>'Supplier Sales Analysis']);
            });

            Route::prefix('paymentReport')->as('payment.')->namespace('PaymentReport')->group(function(){
                Route::match(['get','post'],'by_date', ['as' => 'by_date', 'uses' => 'PaymentReportsController@index', 'custom_label'=>'Payment Report By Date']);

                Route::match(['get','post'],'by_customer', ['as' => 'by_customer', 'uses' => 'PaymentReportsController@by_customer', 'custom_label'=>'Payment Report By Customer']);

                Route::match(['get','post'],'by_system_user', ['as' => 'by_system_user', 'uses' => 'PaymentReportsController@by_system_user', 'custom_label'=>'Payment Report By User']);

                Route::match(['get','post'],'by_payment_method', ['as' => 'by_payment_method', 'uses' => 'PaymentReportsController@by_payment_method', 'custom_label'=>'Payment Report By Method']);

                Route::match(['get','post'],'profit_and_loss', ['as' => 'profit_and_loss', 'uses' => 'PaymentReportsController@profit_and_loss', 'custom_label'=>'Profit and Loss Analysis Report']);

                Route::match(['get','post'],'profit_and_loss_by_department', ['as' => 'profit_and_loss_by_department', 'uses' => 'PaymentReportsController@profit_and_loss_by_department', 'custom_label'=>'Profit and Loss Analysis Report By Deparment']);

                Route::match(['get','post'],'payment_method', ['as' => 'payment_method', 'uses' => 'PaymentReportsController@payment_method', 'custom_label'=>'Payment Method Report(s)']);

                Route::match(['get','post'],'payment_method_by_user', ['as' => 'payment_method_by_user', 'uses' => 'PaymentReportsController@payment_method_by_user', 'custom_label'=>'Payment Method Report By Users']);

                Route::match(['get','post'],'credit_report', ['as' => 'credit_report', 'uses' => 'PaymentReportsController@credit_report', 'custom_label'=>'Credit Report By Date']);

                Route::match(['get','post'],'credit_payment_report', ['as' => 'credit_payment_report', 'uses' => 'PaymentReportsController@credit_payment_report', 'custom_label'=>'Credit Payment Report By Date']);
            });

            Route::prefix('invoiceReport')->as('invoice.')->namespace('InvoiceReport')->group(function(){

                Route::match(['get','post'],'by_date', ['as' => 'by_date', 'uses' => 'InvoiceReportController@index', 'custom_label'=>'Invoice Report By Date']);

                Route::match(['get','post'],'by_system_user', ['as' => 'by_system_user', 'uses' => 'InvoiceReportController@by_system_user', 'custom_label'=>'Invoice Report By System User']);

                Route::match(['get','post'],'by_status', ['as' => 'by_status', 'uses' => 'InvoiceReportController@by_status', 'custom_label'=>'Invoice Report By Status']);

                Route::match(['get','post'],'by_product', ['as' => 'by_product', 'uses' => 'InvoiceReportController@by_product', 'custom_label'=>'Invoice Report By Product']);

                Route::match(['get','post'],'by_customer', ['as' => 'by_customer', 'uses' => 'InvoiceReportController@by_customer', 'custom_label'=>'Invoice Report By Customer']);

                Route::match(['get','post'],'return_invoice', ['as' => 'return_invoice', 'uses' => 'InvoiceReportController@return_invoice', 'custom_label'=>'Returned Invoice Report']);

                Route::match(['get','post'],'print_frequency', ['as' => 'print_frequency', 'uses' => 'InvoiceReportController@print_frequency', 'custom_label'=>'Retail Invoice POS Print Frequency']);

            });


            Route::prefix('stockTransferReport')->as('stockTransferReport.')->namespace('StockTransferReport')->group(function(){

                Route::match(['get','post'],'by_date', ['as' => 'by_date', 'uses' => 'StockTransferReportController@index', 'custom_label'=>'Stock Transfer Report By Date']);

                Route::match(['get','post'],'by_system_user', ['as' => 'by_system_user', 'uses' => 'StockTransferReportController@by_system_user', 'custom_label'=>'Stock Transfer By System User']);

                Route::match(['get','post'],'by_status', ['as' => 'by_status', 'uses' => 'StockTransferReportController@by_status', 'custom_label'=>'Stock Transfer By Status']);

                Route::match(['get','post'],'by_product', ['as' => 'by_product', 'uses' => 'StockTransferReportController@by_product', 'custom_label'=>'Stock Transfer By Product']);

                Route::match(['get','post'],'transfer_summary', ['as' => 'transfer_summary', 'uses' => 'StockTransferReportController@transfer_summary', 'custom_label'=>'Transfer Summary Report']);

            });




            Route::prefix('customerReport')->as('customerReport.')->namespace('CustomerReport')->group(function(){

                Route::match(['get','post'],'balance_sheet', ['as' => 'balance_sheet', 'uses' => 'CustomerReportController@balance_sheet', 'custom_label'=>'Customer Balance Sheet']);

                Route::match(['get','post'],'customer_ledger', ['as' => 'customer_ledger', 'uses' => 'CustomerReportController@customer_ledger', 'custom_label'=>'Customer Ledger']);

                Route::match(['get','post'],'customer_ranking', ['as' => 'customer_ranking', 'uses' => 'CustomerReportController@customer_ranking', 'custom_label'=>'Customer Ranking Report']);

                Route::match(['get','post'],'customer_sales_report', ['as' => 'customer_sales_report', 'uses' => 'CustomerReportController@customer_sales_report', 'custom_label'=>'Customer Sales Report']);

            });


            Route::prefix('productReport')->as('productReport.')->namespace('ProductReport')->group(function(){

                Route::match(['get','post'],'opening_stock', ['as' => 'opening_stock', 'uses' => 'ProductReportController@opening_stock', 'custom_label'=>'Stock Opening Report']);

                Route::match(['get','post'],'bin', ['as' => 'bincard_report', 'uses' => 'ProductReportController@bincard_report', 'custom_label'=>'Product Bincard Report']);

                Route::match(['get','post'],'nearoutofstock', ['as' => 'nearoutofstock', 'uses' => 'ProductReportController@nearoutofstock', 'custom_label'=>'Near Out Of Stock']);

                Route::match(['get','post'],'retailnearoutofstock', ['as' => 'retailnearoutofstock', 'uses' => 'ProductReportController@retailnearoutofstock', 'custom_label'=>'Retail Near Out Of Stock']);

                Route::match(['get','post'],'stockpriceanalysis', ['as' => 'stockpriceanalysis', 'uses' => 'ProductReportController@stockpriceanalysis', 'custom_label'=>'Stock Price Analysis']);

                Route::match(['get','post'],'movingstocksreport', ['as' => 'movingstocksreport', 'uses' => 'ProductReportController@movingstocksreport', 'custom_label'=>'Moving Stocks Report']);

                Route::match(['get','post'],'view_stock_batch_product', ['as' => 'view_stock_batch_product', 'uses' => 'ProductReportController@view_stock_batch_product', 'custom_label'=>'Stock Batch Update Report']);

                Route::match(['get','post'],'balance_stock_worth', ['as' => 'balance_stock_worth', 'uses' => 'ProductReportController@balance_stock_worth', 'custom_label'=>'Balance Stock Worth Report']);

                Route::match(['get','post'],'product_price_change_history', ['as' => 'product_price_change_history', 'uses' => 'ProductReportController@product_price_change_history', 'custom_label'=>'Stock Price Change History Report']);

                Route::match(['get','post'],'supplierDBOverviewReport', ['as' => 'supplierDBOverviewReport', 'uses' => 'ProductReportController@supplierDBOverviewReport', 'custom_label'=>'Supplier DB Overview Report']);

            });


            Route::prefix('expensesReport')->as('expensesReport.')->namespace('ExpensesReport')->group(function(){

                Route::match(['get','post'],'by_date', ['as' => 'by_date', 'uses' => 'ExpensesReportController@by_date', 'custom_label'=>'Expenses Report By Date']);

                Route::match(['get','post'],'by_type', ['as' => 'by_type', 'uses' => 'ExpensesReportController@by_type', 'custom_label'=>'Expenses Report By Type']);

                Route::match(['get','post'],'by_department', ['as' => 'by_department', 'uses' => 'ExpensesReportController@by_department', 'custom_label'=>'Expenses Report By Department']);

                Route::match(['get','post'],'by_type_and_department', ['as' => 'by_type_and_department', 'uses' => 'ExpensesReportController@by_type_and_department', 'custom_label'=>'Expenses Report By Department & Type']);

                Route::match(['get','post'],'by_user', ['as' => 'by_user', 'uses' => 'ExpensesReportController@by_user', 'custom_label'=>'Expenses Report By User']);

            });



            Route::prefix('staffPerformanceReport')->as('staffPerformanceReport.')->namespace('StaffPerformanceReport')->group(function(){

                Route::match(['get','post'],'sales_order_performancereport', ['as' => 'sales_order_performancereport', 'uses' => 'StaffPerformanceReportController@sales_order_performancereport', 'custom_label'=>'Sales Order Performance Report']);
                Route::match(['get','post'],'picker_and_packer', ['as' => 'picker_and_packer', 'uses' => 'StaffPerformanceReportController@picker_and_packer', 'custom_label'=>'Picker and Packer Performance Report']);
            });

        });
    });


});
