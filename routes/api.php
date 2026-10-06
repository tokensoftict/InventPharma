<?php

use App\Http\Controllers\Api\InvoiceValidationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('test', function (Request $request) {
    return "hello well come";
});
// Invoice validation routes (accessible via Cloudflare Tunnel / external clients)
Route::post('/invoice/validate', [InvoiceValidationController::class, 'validateInvoice'])->name('api.invoice.validate');
Route::post('/invoice/validate-purchase-limits', [InvoiceValidationController::class, 'validatePurchaseLimits'])->name('api.invoice.validate-purchase-limits');

Route::prefix('v1')->group(function () {
    Route::post('/invoice/validate', [InvoiceValidationController::class, 'validateInvoice'])->name('api.v1.invoice.validate');
    Route::post('/invoice/validate-purchase-limits', [InvoiceValidationController::class, 'validatePurchaseLimits'])->name('api.v1.invoice.validate-purchase-limits');
});
