<?php

namespace App\Repositories;

use App\Livewire\InvoiceAndSales\InvoiceFormComponent;
use App\Jobs\AddLogToCustomerLedger;
use App\Jobs\AddLogToProductBinCard;
use App\Jobs\PushStockUpdateToServer;
use App\Models\Creditpaymentlog;
use App\Models\CustomerLedger;
use App\Models\Invoice;
use App\Models\Invoiceitem;
use App\Models\Invoiceitembatch;
use App\Models\InvoiceReturn;
use App\Models\MultipleInvoiceScanReport;
use App\Models\Onlineordertotal;
use App\Models\Payment;
use App\Models\Paymentmethoditem;
use App\Models\Stock;
use App\Models\WaitingCustomer;
use App\Services\PurchaseLimitService;
use App\Services\InvoiceValidationService;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InvoiceRepository
{
    public function __construct() {
        //
    }
    public static function invoice(Invoice $invoice, InvoiceFormComponent $component)
    {
        if(isset($invoice->id))
        {

            return [
                'invoice_number' => $invoice->invoice_number,
                'customer_id' => $invoice->customer ? $invoice->customer->toArray() : ['firstname' => ''],
                'is_stock_exchange' => (bool) ($invoice->is_stock_exchange ?? false),
                'invoice_type' => $invoice->invoice_type ?? 'normal',
                'supplier_id' => $invoice->supplier_id ?? null,
                'payment_id' => $invoice->payment_id,
                'department' => $invoice->department,
                'in_department' => $invoice->in_department,
                'discount_amount' => $invoice->discount_amount,
                'discount_type' => $invoice->discount_type,
                // discount_value was previously missing, so the return screen recalculated the invoice
                // with no discount and the return difference was wrong/zero when quantities were reduced
                'discount_value' => (float) $invoice->discount_value > 0
                    ? $invoice->discount_value
                    : (($invoice->discount_type === 'Fixed') ? $invoice->discount_amount : 0),
                'membership_discount' => $invoice->membership_discount,
                'membership_discount_value' => $invoice->membership_discount_value,
                'status_id' => $invoice->status_id,
                'sub_total' => $invoice->sub_total,
                'total_amount_paid' => $invoice->total_amount_paid,
                'exchange_amount_utilised' => $invoice->exchange_amount_utilised ?? 0,
                'total_profit' => $invoice->total_profit,
                'total_cost' => $invoice->total_cost,
                'vat' => $invoice->vat,
                'vat_amount' => $invoice->vat_amount,
                'created_by' => $invoice->created_by,
                'last_updated_by' => $invoice->last_updated_by,
                'voided_by' => $invoice->voided_by,
                'invoice_date' => mysql_str_date($invoice->invoice_date),
                'sales_time' => $invoice->sales_time,
                'void_reason' => $invoice->void_reason,
                'date_voided' =>  $invoice->date_voided,
                'void_time' =>  $invoice->void_time,
                'picked_by' =>  $invoice->picked_by,
                'packed_by' =>   $invoice->packed_by,
                'checked_by' =>  $invoice->checked_by,
                'carton_no' =>  $invoice->carton_no,
                'online_order_status' =>  $invoice->online_order_status,
                'online_order_debit' =>  $invoice->online_order_debit,
                'onliner_order_id'=>  $invoice->onliner_order_id,
//                'prescriber_id' => $invoice->prescriber_id,
                //'invoiceitems' => $invoice->invoiceitems->map->only(array_keys(self::invoiceitems(new Invoiceitem())))->toJson(),
                'invoiceitems' => $invoice->invoiceitems->map(function($item){
                    return self::invoiceitems($item);
                })->toJson()
            ];

        }else {

            return [
                'invoice_number' => $component->invoice_number,
                'customer_id' => ['firstname'=>""],
                'is_stock_exchange' => false,
                'invoice_type' => 'normal',
                'supplier_id' => null,
                'department' => NULL,
                'in_department' => department_by_id(auth()->user()->department_id)->quantity_column,
                'discount_amount' => 0,
                'discount_type' => 'Fixed',
                'discount_value' => 0,
                'membership_discount' => 0,
                'membership_discount_value' => 0,
                'status_id' => status('Draft'),
                'sub_total' => 0,
                'total_amount_paid' => 0,
                'exchange_amount_utilised' => 0,
                'total_profit' => 0,
                'total_cost' => 0,
                'vat' => 0,
                'vat_amount' => 0,
                'created_by' => auth()->id(),
                'last_updated_by' => auth()->id(),
                'invoice_date' => todaysDate(),
                'sales_time' => Carbon::now()->toDateTimeLocalString(),
                'invoiceitems' =>  collect([])->toJson(),
//                'prescriber_id' => NULL,
            ];
        }

    }

    public static function invoiceitems(Invoiceitem $invoiceitem)
    {
        if(isset($invoiceitem->id)) {
            return [
                'invoice_id' => $invoiceitem->invoice_id,
                'stock_id' => $invoiceitem->stock_id,
                'quantity' => $invoiceitem->quantity,
                'original_quantity' => $invoiceitem->quantity,
                'max_return_qty' => $invoiceitem->quantity,
                'customer_id' => $invoiceitem->customer_id,
                'added_by' => $invoiceitem->added_by,
                'discount_added_by' => $invoiceitem->discount_added_by,
                'cost_price' => $invoiceitem->cost_price,
                'selling_price' => $invoiceitem->selling_price,
                'profit' => $invoiceitem->profit,
                'discount_value' => $invoiceitem->discount_value,
                'discount_amount' => $invoiceitem->discount_amount,
                'discount_type' =>$invoiceitem->discount_type,
                'box' => $invoiceitem->box,
                'carton' => $invoiceitem->carton,
                'name' => $invoiceitem->name,
                'av_qty' => $invoiceitem->av_qty,
                'custom_price' => $invoiceitem->stock->stockquantityprices()->where("department", ($invoiceitem->invoice->department === 'retail' ? "retail" : "wholesales"))->get()->transform(function($item) use($invoiceitem){
                    $item->carton= $invoiceitem->carton;
                    return $item;
                })->toArray(),
                'default_selling_price' => $invoiceitem->invoice->department === 'retail' ? $invoiceitem->stock->retail_price : $invoiceitem->stock->whole_price,
                // this might cause an issue if the price was updated and the customer later return the product
                // the system might or will use the new price not the old price the customer
                // we will fix later
                'selectedOptions' => $invoiceitem->selectedOptions,
                'isDependent' => $invoiceitem->isDependent,
                'parent_stock_id' => $invoiceitem->parent_stock_id,
                'dependent_products' => $invoiceitem->dependent_products
            ];
        } //stock_option_json
        else {
            return [
                'invoice_id' => NULL,
                'stock_id' => NULL,
                'quantity' => NULL,
                'customer_id' => NULL,
                'added_by' => auth()->id(),
                'discount_added_by' =>NULL,
                'cost_price' => NULL,
                'selling_price' => NULL,
                'profit' => NULL,
                'discount_value' => NULL,
                'discount_amount' => NULL,
                'discount_type' => 'Fixed',
                'box' => NULL,
                'carton' =>NULL,
                'name' => NULL,
                'av_qty' =>NULL,
                'custom_price' => [],
                'default_selling_price' => NULL,
                'selectedOptions' => NULL,
                'dependent_products' => []
            ];

        }
    }

    public function resolvePriceByQuantity(int $quantity, float $defaultSellingPrice, array $customPrices, Stock $stock, string $department): float
    {
        return (new InvoiceValidationService())->resolvePriceByQuantity($quantity, $defaultSellingPrice, $customPrices, $stock, $department);
    }

    public function getOptionsTotalAmount(array $selectedOptions): float
    {
        return (new InvoiceValidationService())->getOptionsTotalAmount($selectedOptions);
    }

    public function validateInvoiceItems(array $items, string $from)
    {
        return (new InvoiceValidationService())->validateItems($items, $from);
    }

    public function calculateInvoiceTotal(array &$invoiceData, array $items)
    {
        $items = collect($items);

        $invoiceData['sub_total'] = $items->sum(function($item){
            return $item['quantity'] * $item['selling_price'];
        });

        $invoiceData['discount_amount'] = 0;
        if(isset($invoiceData['discount_type']) && $invoiceData['discount_type'] != "None") {
            if($invoiceData['discount_type'] == "Percentage") {
                $invoiceData['discount_amount'] = round(abs((($invoiceData['discount_value'] / 100) * $invoiceData['sub_total'])));
            } else {
                $invoiceData['discount_amount'] = $invoiceData['discount_value'];
            }
        }

        $invoiceData['membership_discount'] = 0;
        if(isset($invoiceData['in_department']) && $invoiceData['in_department'] == 'retail' && isset($invoiceData['membership_discount_value']) && $invoiceData['membership_discount_value'] > 0) {
            $invoiceData['membership_discount'] = abs((($invoiceData['membership_discount_value'] / 100) * $invoiceData['sub_total']));
        }

        $invoiceData['total_cost'] =  $items->sum(function($item){
            return $item['quantity'] * $item['cost_price'] ?? 0;
        });

        $invoiceData['total_profit'] =  ($invoiceData['sub_total'] -  $invoiceData['total_cost']) - ($invoiceData['discount_amount'] + $invoiceData['membership_discount']);

    }

    public function createInvoice(array $invoiceData, $validate = true) : Invoice|array
    {

        $invoice = Invoice::where('invoice_number', $invoiceData['invoice_number'])->first();

        if($invoice) {
            logActivity($invoice->id, $invoice->invoice_number, "Duplicate Invoice found, [Duplicate prevented]");
            return $invoice;
        }

        $items = json_decode($invoiceData['invoiceitems'],true);

        //set the cost price of items that does not have cost price to zero
        foreach($items as $key => $item){
            if(!isset($items[$key]['cost_price']) || $items[$key]['cost_price'] == "" || is_null($items[$key]['cost_price'])) {
                $items[$key]['cost_price'] = 0;
            }
        }

        Arr::forget($invoiceData, ['invoiceitems']);

        $isStockExchange = !empty($invoiceData['is_stock_exchange']) || ($invoiceData['invoice_type'] ?? '') === 'stock_exchange';
        if ($isStockExchange) {
            if (in_array($invoiceData['department'] ?? '', ['retail', 'retail_store'], true)
                || in_array($invoiceData['in_department'] ?? '', ['retail', 'retail_store'], true)) {
                return ['department' => 'Stock Exchange Invoices are only permitted for wholesales, not retail.'];
            }
            if (empty($invoiceData['supplier_id'])) {
                return ['supplier_id' => 'Please select a supplier for a Stock Exchange Invoice.'];
            }
            $invoiceData['is_stock_exchange'] = true;
            $invoiceData['invoice_type'] = 'stock_exchange';
            if (empty($invoiceData['customer_id']) || is_array($invoiceData['customer_id'])) {
                $invoiceData['customer_id'] = null;
            }
            if (empty($invoiceData['status_id']) || (int) $invoiceData['status_id'] === (int) status('Draft')) {
                $invoiceData['status_id'] = status('Unpaid') ?? status('Draft');
            }
        }

        $validationService = new InvoiceValidationService();
        $validation = $validationService->validateInvoice(
            $items,
            $invoiceData['department'],
            $invoiceData['customer_id'] ?? null,
            !$isStockExchange
        );

        if ($validation['status'] === false) {
            return $validation['errors'];
        }

        $results = ['status' => true, 'results' => $validation['results']];

        $this->calculateInvoiceTotal($invoiceData, array_column($results['results'], 'item'));

        $invoice = Invoice::create($invoiceData);

        $invoiceItems = array_column($results['results'], 'item');

        $invoiceItems = collect($invoiceItems);
        $removeQuantity = [];

        $columns = [];

        $invoiceItems->each(function($item, $key) use(&$invoice, $results, &$removeQuantity, &$columns){
            Arr::forget($item, ['name','box','carton','av_qty','total_incentives']);
            Arr::set($item,'customer_id', $invoice->customer_id);
            Arr::set($item,'department', $invoice->department);
            $invoiceItemBatches = [];
            collect($results['results'][$item['stock_id']]['batches'])->each(function($batch, $index) use($item, &$invoiceItemBatches, &$invoice, &$removeQuantity,  &$columns) {
                $columns[] = $batch['department'];
                $invoiceItemBatches[] = new Invoiceitembatch([
                    'invoice_id' => $invoice->id,
                    'stock_id' => $item['stock_id'],
                    'stockbatch_id' => $batch['id'],
                    'cost_price' => $batch['cost_price'] ?? 0,
                    'selling_price' => $item['selling_price'],
                    'department' => $batch['department'],
                    'quantity' => $batch['qty'],
                    'batch_no' => $batch['batch_no']
                ]);
                $removeQuantity[] =  $batch;
            });

            $invoice->invoiceitems()->save(
                new Invoiceitem($item)
            )->invoiceitembatches()->saveMany($invoiceItemBatches);

        });

        Stock::removeSaleableBatches($invoice, $removeQuantity, array_unique($columns));


        logActivity($invoice->id, $invoice->invoice_number,'Invoice was generated status :'.status_name($invoice->status_id));

        if($invoice->customer_id && $invoice->customer_id !== 1 && !$invoice->isStockExchange()) { // customer ledger for walking customer

            dispatch(new AddLogToCustomerLedger([
                'payment_id' => NULL,
                'invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'amount' => -($invoice->sub_total - ($invoice->discount_amount + $invoice->membership_discount)),
                'transaction_date' => $invoice->invoice_date,
                'user_id' => auth()->id(),
            ]));
        }

        logActivity($invoice->id, $invoice->invoice_number, "Invoice was created".status_name($invoice->status_id));

        if(config('app.sync_with_online')== 1) {
            dispatch(new PushStockUpdateToServer(array_column($invoice->invoiceitems->toArray(), 'stock_id')));
        }

        return $invoice;
    }

    public static function returnStock(Invoice $invoice)
    {
        $invoiceItemsBatches = [];
        $reverseItemsBatches = [];
        $columns = [];

        $invoice->invoiceitembatches->map->only(['stockbatch_id', 'department', 'av_qty', 'quantity'])->each(function($item, $key) use (&$invoiceItemsBatches, &$reverseItemsBatches, &$columns){
            $columns[] = $item['department'];
            $invoiceItemsBatches[] = [
                'id' => $item['stockbatch_id'],
                $item['department'] =>  $item['av_qty'] +  $item['quantity'],
                'department' =>  $item['department']
            ];

        });

        Stock::returnStocks($invoice, $invoiceItemsBatches, array_unique($columns));
    }

    public function updateInvoice(Invoice $invoice ,array $invoiceData) : Invoice|array
    {
        return DB::transaction(function () use ($invoice, $invoiceData) {
            if ($invoice->hasActiveExchangeAllocations()) {
                return ['invoice' => 'Cannot modify an invoice with active stock exchange allocations. Reverse the allocations first.'];
            }

            $items = json_decode($invoiceData['invoiceitems'], true) ?: [];

            // Enforce Return Invoice rules: cannot add new stock and cannot increase quantity above original invoice quantity
            $returnableStatuses = array_values(array_filter([
                status('Dispatched'),
                status('Paid'),
                status('Complete'),
            ]));
            $isReturnOperation = $invoice->payment_id !== null
                || in_array((int) $invoice->status_id, $returnableStatuses, true);

            $originalStatusId = (int) $invoice->status_id;
            $originalInvoiceAmount = round($invoice->calculateOriginalTotal(), 5);
            $originalItemsByStock = collect();
            $originalPayment = null;
            $originalMethodItems = collect();
            $returnInfo = is_array($invoiceData['return_info'] ?? null) ? $invoiceData['return_info'] : [];
            $returnReason = '';
            $otherExplanation = '';
            $isCustomerRefunded = false;
            $refundPaymentMethodModel = null;
            $bankAccountId = null;

            if ($isReturnOperation) {
                // Snapshot original payment & payment method items before any modifications
                if ($invoice->payment_id !== null) {
                    $originalPayment = Payment::find($invoice->payment_id);
                }
                if (!$originalPayment) {
                    $originalPayment = Payment::where('invoice_id', $invoice->id)
                        ->where(function ($q) {
                            $q->where('invoice_type', Invoice::class)
                              ->orWhereNull('invoice_type')
                              ->orWhere('invoice_type', '');
                        })
                        ->latest('id')
                        ->first();
                }

                if ($originalPayment && \Illuminate\Support\Facades\Schema::hasTable('paymentmethoditems')) {
                    $originalMethodItems = Paymentmethoditem::where('payment_id', $originalPayment->id)
                        ->orderBy('id')
                        ->get();
                }
                if ($originalMethodItems->isEmpty() && \Illuminate\Support\Facades\Schema::hasTable('paymentmethoditems')) {
                    $originalMethodItems = Paymentmethoditem::where('invoice_id', $invoice->id)
                        ->where(function ($q) {
                            $q->where('invoice_type', Invoice::class)
                              ->orWhereNull('invoice_type')
                              ->orWhere('invoice_type', '');
                        })
                        ->orderBy('id')
                        ->get();
                }
                // Idempotency protection: prevent duplicate return/refund records on double submission
                $idempotencyKey = trim((string) ($returnInfo['idempotency_key'] ?? ''));
                if ($idempotencyKey !== '' && \Illuminate\Support\Facades\Schema::hasTable('invoice_returns')) {
                    $existingReturn = InvoiceReturn::where('idempotency_key', $idempotencyKey)->first();
                    if ($existingReturn) {
                        return $invoice->fresh();
                    }
                }

                $originalItemsByStock = $invoice->invoiceitems()->get()->keyBy('stock_id');
                $returnErrors = [];

                foreach ($items as $item) {
                    $stockId = (int) ($item['stock_id'] ?? 0);
                    $itemName = $item['name'] ?? "Stock #{$stockId}";

                    if (!$originalItemsByStock->has($stockId)) {
                        $returnErrors[$stockId] = "Cannot add new stock ({$itemName}) when returning an invoice.";
                        continue;
                    }

                    $originalQty = (int) $originalItemsByStock->get($stockId)->quantity;
                    $submittedQty = (int) ($item['quantity'] ?? 0);

                    if ($submittedQty > $originalQty) {
                        $returnErrors[$stockId] = "Cannot increase quantity of {$itemName} above original invoice quantity ({$originalQty}).";
                    }
                }

                if (!empty($returnErrors)) {
                    return $returnErrors;
                }

                // Validate return reason & customer refund confirmation
                $returnReason = trim((string) ($returnInfo['return_reason'] ?? ''));
                if (!in_array($returnReason, InvoiceReturn::ALLOWED_REASONS, true)) {
                    return ['return_reason' => 'Please select a valid reason for this invoice return.'];
                }

                $otherExplanation = trim((string) ($returnInfo['other_reason_explanation'] ?? ''));
                if ($returnReason === InvoiceReturn::REASON_OTHER && $otherExplanation === '') {
                    return ['other_reason_explanation' => 'Please provide an explanation when selecting Other reason.'];
                }

                $willRefundRaw = $returnInfo['will_refund'] ?? null;
                if ($willRefundRaw === null || $willRefundRaw === '' || !in_array($willRefundRaw, ['yes', 'no', 'YES', 'NO', true, false, 1, 0, '1', '0'], true)) {
                    return ['will_refund' => 'Please confirm whether you will be refunding the customer the invoice return difference.'];
                }

                $isCustomerRefunded = in_array($willRefundRaw, ['yes', 'YES', true, 1, '1'], true);

                if ($isCustomerRefunded) {
                    $submittedMethod = $returnInfo['paymentmethod_id'] ?? ($returnInfo['refund_payment_method'] ?? null);
                    $refundPaymentMethodModel = InvoiceReturn::resolveRefundPaymentMethod($submittedMethod);

                    if (!$refundPaymentMethodModel) {
                        return ['refund_payment_method' => 'Please select a valid refund payment method (Cash or Transfer).'];
                    }

                    if (InvoiceReturn::isTransferPaymentMethod($refundPaymentMethodModel)) {
                        $bankAccountId = (int) ($returnInfo['bank_account_id'] ?? 0);
                        $bankAccountValid = $bankAccountId > 0 && (
                            !\Illuminate\Support\Facades\Schema::hasTable('bank_accounts')
                            || \App\Models\BankAccount::where('id', $bankAccountId)->where('status', 1)->exists()
                        );
                        if (!$bankAccountValid) {
                            return ['bank_account_id' => 'Please select a valid bank account for the Transfer refund.'];
                        }
                    }
                }
            }

            //set the cost price of items that does not have cost price to zero
            foreach($items as $key => $item){
                if(!isset($items[$key]['cost_price']) || $items[$key]['cost_price'] == "" || is_null($items[$key]['cost_price'])) {
                    $items[$key]['cost_price'] = 0;
                }
            }

            Arr::forget($invoiceData, ['invoiceitems', 'return_info']);

            $invoiceItemsBatches = [];
            $reverseItemsBatches = [];
            $columns = [];
            //prepare to return stocks  here
            $invoice->invoiceitembatches->map->only(['stockbatch_id', 'department', 'av_qty', 'quantity'])->each(function($item, $key) use (&$invoiceItemsBatches, &$reverseItemsBatches, &$columns){
                $columns[] = $item['department'];
                $invoiceItemsBatches[] = [
                    'id' => $item['stockbatch_id'],
                    $item['department'] =>  $item['av_qty'] +  $item['quantity'],
                    'department' => $item['department']
                ];
            });

            Stock::returnStocks($invoice, $invoiceItemsBatches, array_unique($columns));

            $validationService = new InvoiceValidationService();
            $validation = $validationService->validateInvoice(
                $items,
                $invoiceData['department'],
                $invoice->customer_id
            );
            $results = ['status' => $validation['status'], 'results' => $validation['results'], 'errors' => $validation['errors']];


            if($results['status'] === false) {

                $invoice->invoiceitembatches()->lockForUpdate()->get()->map->only(['stockbatch_id', 'department', 'av_qty', 'quantity'])->each(function($item, $key)use (&$invoiceItemsBatches, &$reverseItemsBatches, &$columns) {

                    $reverseItemsBatches[] = [
                        'id' => $item['stockbatch_id'],
                        $item['department'] =>  $item['av_qty'] - $item['quantity'],
                        'department' => $item['department']
                    ];

                });

                Stock::removeSaleableBatches($invoice, $reverseItemsBatches, array_unique($columns));

                return $results['errors'];
            }

            // Purchase limit validation already handled by InvoiceValidationService above

            if ($isReturnOperation) {
                // On an invoice return, ignore custom_price / tier repricing and lock each item's selling_price
                // to the exact selling_price originally recorded on the invoice.
                foreach ($results['results'] as $stockId => &$resEntry) {
                    if ($originalItemsByStock->has((int) $stockId)) {
                        $origSellingPrice = (float) $originalItemsByStock->get((int) $stockId)->selling_price;
                        $resEntry['item']['selling_price'] = $origSellingPrice;
                        $resEntry['item']['profit'] = $origSellingPrice - (float) ($resEntry['item']['cost_price'] ?? 0);
                    }
                }
                unset($resEntry);

                // A return must keep the discount terms the invoice was originally issued with. Do not trust the
                // client payload (the logged-in user's department, a blank discount value, etc.), otherwise the
                // "amount after return" loses the discount and the return difference becomes 0.
                $invoiceData['in_department'] = $invoice->in_department;
                $storedDiscountAmount = (float) ($invoice->discount_amount ?? 0);
                $storedDiscountValue = (float) ($invoice->discount_value ?? 0);
                if ($invoice->discount_type === 'Percentage' && $storedDiscountValue > 0) {
                    $invoiceData['discount_type'] = 'Percentage';
                    $invoiceData['discount_value'] = $storedDiscountValue;
                } elseif ($storedDiscountAmount > 0) {
                    $invoiceData['discount_type'] = 'Fixed';
                    $invoiceData['discount_value'] = $storedDiscountAmount;
                } else {
                    $invoiceData['discount_type'] = 'None';
                    $invoiceData['discount_value'] = 0;
                }
                $invoiceData['membership_discount_value'] = (float) ($invoice->membership_discount_value ?? 0);
            }

            $this->calculateInvoiceTotal($invoiceData, array_column($results['results'], 'item'));

            $currentInvoiceAmount = max(0.0, round(
                (float) ($invoiceData['sub_total'] ?? 0)
                - ((float) ($invoiceData['discount_amount'] ?? 0) + (float) ($invoiceData['membership_discount'] ?? 0)),
                5
            ));
            $returnDifference = max(0.0, round($originalInvoiceAmount - $currentInvoiceAmount, 5));

            if ($isReturnOperation && $isCustomerRefunded && $returnDifference <= 0) {
                $invoice->invoiceitembatches()->lockForUpdate()->get()->map->only(['stockbatch_id', 'department', 'av_qty', 'quantity'])->each(function($item) use (&$reverseItemsBatches, &$columns) {
                    $reverseItemsBatches[] = [
                        'id' => $item['stockbatch_id'],
                        $item['department'] => $item['av_qty'] - $item['quantity'],
                        'department' => $item['department'],
                    ];
                });
                Stock::removeSaleableBatches($invoice, $reverseItemsBatches, array_unique($columns));

                return ['refund_amount' => 'Cannot issue a customer refund when the invoice return difference is 0.00. Reduce or remove the returned item(s) or choose No for customer refund.'];
            }

            Arr::forget($invoiceData, ['created_by']);

            $invoiceData['last_updated_by'] = auth()->id();

            if ($isReturnOperation) {
                // Keep the original invoice status before the invoice was returned
                $invoiceData['status_id'] = $originalStatusId;
                $invoiceData['total_amount_paid'] = $currentInvoiceAmount;
            }

            $invoice->update($invoiceData);

            $invoiceItems = array_column($results['results'], 'item');

            $invoiceItems = collect($invoiceItems);

            $removeQuantity = [];

            $invoice->invoiceitems()->delete();

            $invoice->invoiceitembatches()->delete();

            $invoice->invoiceprinthistories()->delete();

            if ($isReturnOperation) {
                $oldPaymentIds = collect([$invoice->payment_id, $originalPayment?->id])
                    ->merge(
                        Payment::where('invoice_id', $invoice->id)
                            ->where(function ($q) {
                                $q->where('invoice_type', Invoice::class)
                                  ->orWhereNull('invoice_type')
                                  ->orWhere('invoice_type', '');
                            })
                            ->pluck('id')
                    )
                    ->filter()
                    ->unique()
                    ->values();

                $existingInvoiceCreditLogs = collect();
                if ($oldPaymentIds->isNotEmpty()) {
                    if (\Illuminate\Support\Facades\Schema::hasTable('creditpaymentlogs')) {
                        $existingInvoiceCreditLogs = Creditpaymentlog::whereIn('payment_id', $oldPaymentIds)
                            ->where(function ($q) {
                                if (\Illuminate\Support\Facades\Schema::hasColumn('creditpaymentlogs', 'invoicelog_type')) {
                                    $q->whereNull('invoicelog_type')
                                      ->orWhere('invoicelog_type', Invoice::class);
                                }
                            })
                            ->get();
                    }
                    if (\Illuminate\Support\Facades\Schema::hasTable('paymentmethoditems')) {
                        Paymentmethoditem::whereIn('payment_id', $oldPaymentIds)->delete();
                    }
                    if (\Illuminate\Support\Facades\Schema::hasTable('customer_ledgers')) {
                        CustomerLedger::whereIn('payment_id', $oldPaymentIds)->delete();
                    }
                    Payment::whereIn('id', $oldPaymentIds)->delete();
                }

                if (\Illuminate\Support\Facades\Schema::hasTable('paymentmethoditems')) {
                    Paymentmethoditem::where('invoice_id', $invoice->id)
                        ->where(function ($q) {
                            $q->where('invoice_type', Invoice::class)
                              ->orWhereNull('invoice_type')
                              ->orWhere('invoice_type', '');
                        })
                        ->delete();
                }

                // Automatically re-add payment with the original payment details and the new invoice amount after return
                if ($originalPayment || $originalMethodItems->isNotEmpty()) {
                    $rawPaymentDate = $originalPayment
                        ? ($originalPayment->getRawOriginal('payment_date') ?: $originalPayment->payment_date)
                        : ($invoice->getRawOriginal('invoice_date') ?: todaysDate());
                    $rawPaymentTime = $originalPayment
                        ? ($originalPayment->getRawOriginal('payment_time') ?: $originalPayment->payment_time)
                        : Carbon::now()->toTimeString();

                    $newPayment = Payment::create([
                        'user_id' => $originalPayment?->user_id ?? (auth()->id() ?: $invoice->created_by),
                        'customer_id' => $invoice->customer_id ?? $originalPayment?->customer_id,
                        'invoice_number' => $invoice->invoice_number,
                        'invoice_type' => ($originalPayment && !empty($originalPayment->invoice_type))
                            ? $originalPayment->invoice_type
                            : Invoice::class,
                        'invoice_id' => $invoice->id,
                        'department' => $originalPayment?->department ?? $invoice->department,
                        'subtotal' => $currentInvoiceAmount,
                        'total_paid' => $currentInvoiceAmount,
                        'payment_time' => $rawPaymentTime,
                        'payment_date' => $rawPaymentDate,
                    ]);

                    if ($originalMethodItems->isNotEmpty() && \Illuminate\Support\Facades\Schema::hasTable('paymentmethoditems')) {
                        $oldItemsTotal = (float) $originalMethodItems->sum('amount');
                        $itemsCount = $originalMethodItems->count();
                        $allocatedSum = 0.0;
                        $hasAmountTenderedCol = \Illuminate\Support\Facades\Schema::hasColumn('paymentmethoditems', 'amount_tendered');

                        foreach ($originalMethodItems->values() as $idx => $origMethodItem) {
                            $isLast = ($idx === $itemsCount - 1);
                            if ($itemsCount === 1) {
                                $newItemAmount = $currentInvoiceAmount;
                            } elseif ($isLast) {
                                $newItemAmount = max(0.0, round($currentInvoiceAmount - $allocatedSum, 5));
                            } elseif ($oldItemsTotal > 0) {
                                $newItemAmount = round((((float) $origMethodItem->amount) / $oldItemsTotal) * $currentInvoiceAmount, 2);
                                $allocatedSum = round($allocatedSum + $newItemAmount, 5);
                            } else {
                                $newItemAmount = $idx === 0 ? $currentInvoiceAmount : 0.0;
                                $allocatedSum = round($allocatedSum + $newItemAmount, 5);
                            }

                            $itemPayload = [
                                'payment_id' => $newPayment->id,
                                'user_id' => $origMethodItem->user_id ?? $newPayment->user_id,
                                'customer_id' => $invoice->customer_id ?? $origMethodItem->customer_id,
                                'paymentmethod_id' => $origMethodItem->paymentmethod_id,
                                'invoice_type' => $origMethodItem->invoice_type ?: $newPayment->invoice_type,
                                'invoice_id' => $invoice->id,
                                'department' => $origMethodItem->department ?? $newPayment->department,
                                'payment_date' => $origMethodItem->getRawOriginal('payment_date') ?: $rawPaymentDate,
                                'amount' => $newItemAmount,
                                'payment_info' => $origMethodItem->payment_info,
                                'bank_account_id' => $origMethodItem->bank_account_id,
                            ];

                            if ($hasAmountTenderedCol) {
                                $itemPayload['amount_tendered'] = $origMethodItem->amount_tendered;
                            }

                            $createdMethodItem = Paymentmethoditem::create($itemPayload);

                            if ((int) $createdMethodItem->paymentmethod_id === 4 && \Illuminate\Support\Facades\Schema::hasTable('creditpaymentlogs')) {
                                // If Not Refunded, credit debt reduces to the new amount (-$createdMethodItem->amount);
                                // If Refunded in Cash/Transfer, credit balance does not change (0 effect on credit balance).
                                if ($existingInvoiceCreditLogs->isNotEmpty()) {
                                    $existingCreditLog = $existingInvoiceCreditLogs->first();
                                    $newCreditAmount = $isCustomerRefunded
                                        ? (float) $existingCreditLog->amount
                                        : (float) $existingCreditLog->amount + $returnDifference;

                                    $updateCreditFields = [
                                        'payment_id' => $newPayment->id,
                                        'amount' => min(0.0, round($newCreditAmount, 5)),
                                    ];
                                    if (\Illuminate\Support\Facades\Schema::hasColumn('creditpaymentlogs', 'paymentmethoditem_id')) {
                                        $updateCreditFields['paymentmethoditem_id'] = $createdMethodItem->id;
                                    }
                                    if (\Illuminate\Support\Facades\Schema::hasColumn('creditpaymentlogs', 'invoicelog_type')) {
                                        $updateCreditFields['invoicelog_type'] = Invoice::class;
                                        $updateCreditFields['invoicelog_id'] = $invoice->id;
                                    }
                                    $existingCreditLog->update($updateCreditFields);
                                } else {
                                    $newCreditAmount = $isCustomerRefunded
                                        ? -((float) $origMethodItem->amount)
                                        : -((float) $createdMethodItem->amount);

                                    $creditPayload = [
                                        'payment_id' => $newPayment->id,
                                        'user_id' => $createdMethodItem->user_id,
                                        'paymentmethod_id' => 4,
                                        'customer_id' => $createdMethodItem->customer_id,
                                        'amount' => $newCreditAmount,
                                        'payment_date' => $rawPaymentDate,
                                    ];
                                    if (\Illuminate\Support\Facades\Schema::hasColumn('creditpaymentlogs', 'credit_number') && function_exists('creditPaymentReference')) {
                                        $creditPayload['credit_number'] = creditPaymentReference();
                                    }
                                    if (\Illuminate\Support\Facades\Schema::hasColumn('creditpaymentlogs', 'paymentmethoditem_id')) {
                                        $creditPayload['paymentmethoditem_id'] = $createdMethodItem->id;
                                    }
                                    if (\Illuminate\Support\Facades\Schema::hasColumn('creditpaymentlogs', 'invoicelog_type')) {
                                        $creditPayload['invoicelog_type'] = Invoice::class;
                                        $creditPayload['invoicelog_id'] = $invoice->id;
                                    }
                                    Creditpaymentlog::create($creditPayload);
                                }
                            }
                        }
                    } elseif ($existingInvoiceCreditLogs->isNotEmpty()) {
                        foreach ($existingInvoiceCreditLogs as $existingCreditLog) {
                            $existingCreditLog->update(['payment_id' => $newPayment->id]);
                        }
                    }

                    if ($invoice->customer) {
                        $invoice->customer->updateCreditBalance();
                    }

                    $totalPaidForLedger = ($originalMethodItems->isNotEmpty() && \Illuminate\Support\Facades\Schema::hasTable('paymentmethoditems'))
                        ? (float) $newPayment->paymentmethoditems()->whereIn('paymentmethod_id', [1, 2, 3, 9])->sum('amount')
                        : (float) $newPayment->total_paid;

                    if ($totalPaidForLedger > 0 && $newPayment->customer_id && (int) $newPayment->customer_id !== 1 && !$invoice->isStockExchange()) {
                        dispatch(new AddLogToCustomerLedger([
                            'payment_id' => $newPayment->id,
                            'invoice_id' => null,
                            'customer_id' => $newPayment->customer_id,
                            'amount' => $totalPaidForLedger,
                            'transaction_date' => $rawPaymentDate,
                            'user_id' => auth()->id() ?: $newPayment->user_id,
                        ]));
                    }

                    $invoice->payment_id = $newPayment->id;
                    $invoice->total_amount_paid = $currentInvoiceAmount;
                } else {
                    if ($invoice->customer) {
                        $invoice->customer->updateCreditBalance();
                    }
                    $invoice->total_amount_paid = $currentInvoiceAmount;
                }

                $invoice->status_id = $originalStatusId;
                $invoice->retail_printed = "0";
                $invoice->date_voided = todaysDate();
                $invoice->void_time = Carbon::now();
                $invoice->voided_by = auth()->id();
                $invoice->void_reason = $invoice->void_reason === NULL ? 1 : ($invoice->void_reason + 1);
                $invoice->update();

                logActivity($invoice->id, $invoice->invoice_number, 'invoice was returned RETURNED');
            } elseif ($invoice->payment_id !== NULL) {
                Creditpaymentlog::where('payment_id',  $invoice->payment_id)->delete(); // delete credit payment logs

                if ($invoice->customer) {
                    $invoice->customer->updateCreditBalance();
                }

                $invoice->payment()->delete();

                $invoice->payment_id = NULL;
            }

            $columns = [];

            $invoiceItems->each(function($item, $key) use(&$invoice, $results, &$removeQuantity, &$columns){
                Arr::forget($item, ['name','box','carton','av_qty','total_incentives','original_quantity','max_return_qty']);
                Arr::set($item,'customer_id', $invoice->customer_id);
                Arr::set($item,'department', $invoice->department);
                $invoiceItemBatches = [];
                collect($results['results'][$item['stock_id']]['batches'])->each(function($batch, $index) use($item, &$invoiceItemBatches, &$invoice, &$removeQuantity, &$columns) {
                    $columns[] = $batch['department'];
                    $invoiceItemBatches[] = new Invoiceitembatch([
                        'invoice_id' => $invoice->id,
                        'stock_id' => $item['stock_id'],
                        'stockbatch_id' => $batch['id'],
                        'cost_price' => $batch['cost_price'] ?? 0,
                        'selling_price' => $item['selling_price'],
                        'department' => $batch['department'],
                        'quantity' => $batch['qty']
                    ]);

                    $removeQuantity[] =  $batch;
                });

                $invoice->invoiceitems()->save(
                    new Invoiceitem($item)
                )->invoiceitembatches()->saveMany($invoiceItemBatches);

            });


            Stock::removeSaleableBatches($invoice, $removeQuantity, array_unique( $columns));

            if ($isReturnOperation && \Illuminate\Support\Facades\Schema::hasTable('invoice_returns')) {
                $submittedByStock = $invoiceItems->keyBy('stock_id');
                $snapshot = $originalItemsByStock->map(function ($origItem) use ($submittedByStock) {
                    $newItem = $submittedByStock->get($origItem->stock_id);
                    $origQty = (int) $origItem->quantity;
                    $newQty = $newItem ? (int) ($newItem['quantity'] ?? 0) : 0;

                    return [
                        'stock_id' => (int) $origItem->stock_id,
                        'name' => $origItem->name ?? ($origItem->stock->name ?? ''),
                        'original_quantity' => $origQty,
                        'remaining_quantity' => $newQty,
                        'returned_quantity' => max(0, $origQty - $newQty),
                        'selling_price' => (float) $origItem->selling_price,
                    ];
                })->values()->all();

                $idempotencyKey = trim((string) ($returnInfo['idempotency_key'] ?? ''));

                InvoiceReturn::create([
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'customer_id' => $invoice->customer_id,
                    'department' => $invoice->in_department ?: $invoice->department,
                    'return_sequence' => (int) ($invoice->void_reason ?: 1),
                    'original_invoice_amount' => $originalInvoiceAmount,
                    'current_invoice_amount' => $currentInvoiceAmount,
                    'return_difference' => $returnDifference,
                    'return_reason' => $returnReason,
                    'other_reason_explanation' => $returnReason === InvoiceReturn::REASON_OTHER ? $otherExplanation : null,
                    'is_customer_refunded' => $isCustomerRefunded,
                    'refund_status' => $isCustomerRefunded
                        ? InvoiceReturn::REFUND_STATUS_REFUNDED
                        : InvoiceReturn::REFUND_STATUS_NOT_REFUNDED,
                    'refund_amount' => $isCustomerRefunded ? $returnDifference : 0.0,
                    'paymentmethod_id' => ($isCustomerRefunded && $refundPaymentMethodModel) ? (int) $refundPaymentMethodModel->id : null,
                    'refund_payment_method' => ($isCustomerRefunded && $refundPaymentMethodModel) ? (string) $refundPaymentMethodModel->name : null,
                    'bank_account_id' => ($isCustomerRefunded && InvoiceReturn::isTransferPaymentMethod($refundPaymentMethodModel))
                        ? $bankAccountId
                        : null,
                    'processed_by' => auth()->id() ?: ($invoice->last_updated_by ?: 1),
                    'return_date' => todaysDate(),
                    'processed_at' => Carbon::now(),
                    'idempotency_key' => $idempotencyKey !== '' ? $idempotencyKey : null,
                    'returned_items_snapshot' => $snapshot,
                ]);
            }

            logActivity($invoice->id, $invoice->invoice_number, "Invoice was updated".status_name($invoice->status_id));

            if($invoice->customer_id && $invoice->customer_id !== 1 && !$invoice->isStockExchange()) { // customer ledger for walking customer

                dispatch(new AddLogToCustomerLedger([
                    'payment_id' => NULL,
                    'invoice_id' => $invoice->id,
                    'customer_id' => $invoice->customer_id,
                    'amount' => -($invoice->sub_total - ($invoice->discount_amount + $invoice->membership_discount)),
                    'transaction_date' => $invoice->invoice_date,
                    'user_id' => auth()->id(),
                ]));
            }
            if(config('app.sync_with_online')== 1) {
                dispatch(new PushStockUpdateToServer(array_column($invoice->invoiceitems()->get()->toArray(), 'stock_id')));
            }

            //$this->initiateBinCard($invoice);

            return $invoice;
        });
    }

    public function createOnlineInvoice(array $invoiceData, Collection $orderTotal) : Invoice
    {
        return DB::transaction(function() use (&$invoiceData, &$orderTotal){

            $invoiceitems = collect($invoiceData['invoiceitems']);

            Arr::forget($invoiceData, ['invoiceitems']);

            $invoice = Invoice::create($invoiceData);

            $removeQuantity = [];
            $columns = [];
            $invoiceitems->each(function ($item) use(&$invoice, &$columns,&$removeQuantity) {
                $batches = $item['batches'];

                Arr::forget($item, ['name','box','carton','av_qty','total_incentives','batches']);
                Arr::set($item,'customer_id', $invoice->customer_id);
                Arr::set($item,'department', $invoice->department);
                $invoiceItemBatches = [];

                collect($batches)->each(function($batch) use(&$invoice, &$removeQuantity, &$item, &$columns, &$invoiceItemBatches){

                    $columns[] = $batch['department'];

                    $invoiceItemBatches[] = new Invoiceitembatch([
                        'invoice_id' => $invoice->id,
                        'stock_id' => $item['stock_id'],
                        'stockbatch_id' => $batch['id'],
                        'cost_price' => $batch['cost_price'],
                        'selling_price' => $item['selling_price'],
                        'department' => $batch['department'],
                        'quantity' => $batch['qty']
                    ]);

                    $removeQuantity[] =  $batch;

                    //Arr::only(, ['id','bulksales','quantity','wholesales','retail']);

                });

                $invoice->invoiceitems()->save(
                    new Invoiceitem($item)
                )->invoiceitembatches()->saveMany($invoiceItemBatches);

            });

            Stock::removeSaleableBatches($invoice, $removeQuantity, array_unique($columns)); // remove stock quantity

            $totals = [];
            $orderTotal->each(function($total) use(&$totals) {
                $totals[] = new Onlineordertotal([
                    'name'=>$total['name'],
                    'value'=>$total['value']
                ]);
            });

            $invoice->onlineordertotals()->saveMany($totals);

            //$this->initiateBinCard($invoice);


            if($invoice->customer_id !== 1) { // customer ledger for walking customer

                dispatch(new AddLogToCustomerLedger([
                    'payment_id' => NULL,
                    'invoice_id' => $invoice->id,
                    'customer_id' => $invoice->customer_id,
                    'amount' => -($invoice->sub_total - ($invoice->discount_amount + $invoice->membership_discount)),
                    'transaction_date' => $invoice->invoice_date,
                    'user_id' => auth()->id(),
                ]));
            }


            dispatch(new PushStockUpdateToServer(array_column($invoice->invoiceitems->toArray(), 'stock_id')));

            return $invoice;
        });
    }

    private function initiateBinCard(Invoice $invoice)
    {
        $returnBatches = [];

        $cards = $invoice->invoiceitembatches->map(function($item) use(&$returnBatches, &$invoice){
            return [
                'bin_card_type'=>"APP//SOLD",
                'bin_card_date'=>todaysDate(),
                'user_id'=>auth()->id(),
                'stock_id' => $item->stock_id,
                'sold_qty'=>$item->quantity,
                'stockbatch_id'=> $item->stockbatch_id,
                'to_department'=>$item->department,
                'comment'=>"Stock Sold By : by ".$invoice->create_by->name,
                'balance'=>$item->stock->totalBalance(),
                'department_balance'=>$item->stock->{$item->department}
            ];
        })->toArray();

        dispatch(new AddLogToProductBinCard($cards));
    }

    public function findByInvoiceNumber($invoice_number){

        $invoice = Invoice::where('invoice_number', $invoice_number)->first();

        if(!$invoice) {$invoice = Invoice::find($invoice_number);}

        if(!$invoice) return false;

        return $invoice;
    }

    private function logMultipleScan(Invoice $invoice)
    {
        $check = MultipleInvoiceScanReport::query()->where('invoice_id', $invoice->id)->first();
        if(!$check) {
            MultipleInvoiceScanReport::query()->create([
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'scan_date' => todaysDate(),
                'scan_time' => now(),
                'user_id' => auth()->id(),
                'no_of_items' => 2
            ]);
        } else {
            $noOfTimes = $check->no_of_items + 1;
            $check->no_of_items = $noOfTimes;
            $check->user_id = auth()->id();
            $check->save();
        }
    }

    public function checkOut(Invoice $invoice) : array
    {
        if($invoice->scan_user_id !== NULL)
        {
            logActivity($invoice->id, $invoice->invoice_number, "Trying to scan / checkout invoice has been checkout already");
            $this->logMultipleScan($invoice);
            return ['status'=>false, 'message'=>'Invoice has already been checkout by '.$invoice->scan_by->name];
        }else{

            $invoice->scan_user_id = \auth()->id();
            $invoice->scan_time = Carbon::now();
            $invoice->scan_date = todaysDate();
            $invoice->update();

            if(isset($invoice->waitingCustomer->status)) {
                $invoice->waitingCustomer->status = WaitingCustomer::$waitingInvoiceStatus['complete'];
                $invoice->waitingCustomer->save();
                addCustomerWaitingListStatusHistory($invoice->waitingCustomer, 'complete');
            }

            logActivity($invoice->id, $invoice->invoice_number, "Invoice number has been Scan / Checkout");

            return ['status'=>true];
        }
    }
}
