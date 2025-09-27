<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Production;
use App\Models\Purchase;
use App\Models\Stocktransfer;
use App\Models\SupplierCreditPaymentHistory;
use App\Policies\ExpensePolicy;
use App\Policies\InvoicePolicy;
use App\Policies\PaymentPolicy;
use App\Policies\PurchaseOrderPolicy;
use App\Policies\StockTransferPolicy;
use App\Policies\SupplierPaymentPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Invoice::class => InvoicePolicy::class,
        Purchase:: class=> PurchaseOrderPolicy::class,
        Payment::class => PaymentPolicy::class,
        Stocktransfer::class => StockTransferPolicy::class,
        Expense::class => ExpensePolicy::class,
        SupplierCreditPaymentHistory::class => SupplierPaymentPolicy::class
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        //
    }
}
