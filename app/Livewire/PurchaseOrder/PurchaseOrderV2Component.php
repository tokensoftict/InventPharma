<?php

namespace App\Livewire\PurchaseOrder;

use App\Models\Purchase;
use App\Repositories\PurchaseOrderRepository;
use App\Traits\LivewireAlert;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class PurchaseOrderV2Component extends Component
{
    use LivewireAlert;

    public Purchase $purchase;

    public array $initialData = [];

    public array $suppliersList = [];

    public array $departmentsList = [];

    public string $pageStatus = '';

    public bool $canChangeSellingPrice = false;

    public bool $canCreate = false;

    public bool $canComplete = false;

    public bool $canPreDraft = false;

    protected PurchaseOrderRepository $purchaseOrderRepository;

    public function boot(PurchaseOrderRepository $purchaseOrderRepository)
    {
        $this->purchaseOrderRepository = $purchaseOrderRepository;
    }

    public function mount(Purchase $purchase, $suppliers, $departments, $pageStatus)
    {
        $this->purchase = $purchase;
        $this->pageStatus = (string) $pageStatus;

        $this->suppliersList = collect($suppliers)->map(fn ($s) => [
            'id' => (string) $s->id,
            'name' => (string) $s->name,
        ])->values()->all();

        $this->departmentsList = collect($departments)->map(fn ($d) => [
            'id' => (string) $d->id,
            'quantity_column' => (string) $d->quantity_column,
            'label' => (string) $d->label,
        ])->values()->all();

        $repoData = PurchaseOrderRepository::purchaseOrder($this->purchase);
        $rawItems = is_string($repoData['purchaseitems'] ?? null)
            ? (json_decode($repoData['purchaseitems'], true) ?: [])
            : ($repoData['purchaseitems'] ?? []);

        $items = collect($rawItems)->map(function ($item) {
            $exp = trim((string) ($item['expiry_date'] ?? ''));
            if ($exp !== '' && strlen($exp) >= 10) {
                $exp = substr($exp, 0, 10);
            }
            $item['expiry_date'] = $exp;

            return $item;
        })->values()->all();

        $defaultDepartment = (string) ($repoData['department'] ?? '');
        if ($defaultDepartment === '' && count($this->departmentsList) === 1) {
            $defaultDepartment = $this->departmentsList[0]['quantity_column'];
        }

        // When editing an existing PO, make sure its saved department always exists in the dropdown options
        if ($defaultDepartment !== '' && !collect($this->departmentsList)->contains('quantity_column', $defaultDepartment)) {
            $deptModel = department_by_quantity_column($defaultDepartment, false);
            $this->departmentsList[] = [
                'id' => (string) ($deptModel->id ?? ''),
                'quantity_column' => $defaultDepartment,
                'label' => (string) ($deptModel->label ?? (\App\Classes\Settings::$department[$defaultDepartment] ?? $defaultDepartment)),
            ];
        }

        $dateCreated = trim((string) ($repoData['date_created'] ?: todaysDate()));
        if (strlen($dateCreated) >= 10) {
            $dateCreated = substr($dateCreated, 0, 10);
        }

        $this->initialData = [
            'purchase_id' => $this->purchase->id ?? null,
            'supplier_id' => (string) ($repoData['supplier_id'] ?? ''),
            'department' => $defaultDepartment,
            'date_created' => $dateCreated,
            'status_id' => (string) ($repoData['status_id'] ?? $this->pageStatus),
            'purchaseitems' => $items,
        ];

        $this->canChangeSellingPrice = (bool) userCanView('product.changeSellingPrice');
        $this->canCreate = (bool) (userCanView('purchase.create') || userCanView('purchase.edit'));
        $this->canComplete = (bool) userCanView('purchase.complete');
        $this->canPreDraft = (bool) (userCanView('purchase.pre_draft') || userCanView('purchase.create') || userCanView('purchase.edit'));
    }

    public function render()
    {
        return view('livewire.purchase-order.purchase-order-v2-component');
    }

    private function sanitizeAndValidate(array $payload, bool $requireBatchAndExpiry): array
    {
        $supplierId = trim((string) ($payload['supplier_id'] ?? ''));
        $department = trim((string) ($payload['department'] ?? ''));
        $dateCreated = trim((string) ($payload['date_created'] ?? '')) ?: todaysDate();
        $rawItems = $payload['purchaseitems'] ?? [];

        if ($supplierId === '') {
            throw ValidationException::withMessages(['supplier_id' => 'Please select a supplier.']);
        }

        if ($department === '') {
            throw ValidationException::withMessages(['department' => 'Please select a department.']);
        }

        if (!is_array($rawItems) || count($rawItems) === 0) {
            throw ValidationException::withMessages(['purchaseitems' => 'Please add at least one product to the purchase order.']);
        }

        $items = [];
        foreach ($rawItems as $idx => $item) {
            $line = $idx + 1;
            $name = $item['name'] ?? "Item #{$line}";
            $stockId = (int) ($item['stock_id'] ?? 0);
            $qty = (int) ($item['qty'] ?? 0);
            $costPrice = ($item['cost_price'] === '' || $item['cost_price'] === null) ? 0.0 : (float) $item['cost_price'];
            $expiryDate = trim((string) ($item['expiry_date'] ?? ''));
            $batchNo = trim((string) ($item['batch_no'] ?? ''));

            if ($stockId <= 0) {
                throw ValidationException::withMessages(['purchaseitems' => "Invalid product on row {$line}."]);
            }

            if ($qty <= 0) {
                throw ValidationException::withMessages(['purchaseitems' => "Quantity must be greater than 0 for {$name}."]);
            }

            if ($costPrice < 0) {
                throw ValidationException::withMessages(['purchaseitems' => "Cost price cannot be negative for {$name}."]);
            }

            if ($requireBatchAndExpiry) {
                if ($expiryDate === '') {
                    throw ValidationException::withMessages(['purchaseitems' => "Please enter an expiry date for {$name}."]);
                }
                if ($batchNo === '') {
                    throw ValidationException::withMessages(['purchaseitems' => "Please enter a batch number for {$name}."]);
                }
            }

            $qty1m = ($item['qty_to_buy_1m'] === '' || $item['qty_to_buy_1m'] === null) ? 0.0 : (float) $item['qty_to_buy_1m'];
            $highestSold = ($item['highest_qty_sold'] === '' || $item['highest_qty_sold'] === null) ? 0.0 : (float) $item['highest_qty_sold'];
            $qty1mResult = ($item['qty_1m_result'] === '' || $item['qty_1m_result'] === null) ? 0.0 : (float) $item['qty_1m_result'];

            $items[] = [
                'stock_id' => $stockId,
                'expiry_date' => $expiryDate !== '' ? $expiryDate : null,
                'batch_no' => $batchNo !== '' ? $batchNo : null,
                'qty' => $qty,
                'cost_price' => $costPrice,
                'bulk_price' => ($item['bulk_price'] === '' || $item['bulk_price'] === null) ? null : (float) $item['bulk_price'],
                'whole_price' => ($item['whole_price'] === '' || $item['whole_price'] === null) ? null : (float) $item['whole_price'],
                'retail_price' => ($item['retail_price'] === '' || $item['retail_price'] === null) ? null : (float) $item['retail_price'],
                'highest_qty_sold' => $highestSold,
                'qty_to_buy_1m' => $qty1m,
                'qty_1m_result' => $qty1mResult,
                'user_id' => auth()->id(),
            ];
        }

        return [
            'supplier_id' => $supplierId,
            'department' => $department,
            'date_created' => $dateCreated,
            'user_id' => auth()->id(),
            'purchaseitems' => $items,
        ];
    }

    public function draftPurchase(array $payload)
    {
        if (!$this->canCreate) {
            return ['status' => false, 'message' => 'You do not have permission to create or draft purchase orders.'];
        }

        try {
            $data = $this->sanitizeAndValidate($payload, true);
            $data['status_id'] = status('Draft');
            $data['completed_by'] = null;
            $data['date_completed'] = null;

            DB::transaction(function () use ($data) {
                $this->purchase = $this->purchaseOrderRepository->savePurchaseOrder($this->purchase, $data);
            });

            $this->alert('success', 'Purchase Order', [
                'position' => 'center',
                'timer' => 4000,
                'toast' => false,
                'text' => 'Purchase Order has been drafted successfully!',
            ]);

            return [
                'status' => true,
                'purchase_id' => $this->purchase->id,
                'redirect' => route('purchase.index'),
            ];
        } catch (ValidationException $e) {
            $msg = collect($e->errors())->flatten()->first() ?? 'Validation failed.';
            return ['status' => false, 'message' => $msg];
        } catch (\Throwable $e) {
            report($e);
            return ['status' => false, 'message' => 'Unable to save draft: ' . $e->getMessage()];
        }
    }

    public function preDraftPurchase(array $payload)
    {
        if (!$this->canPreDraft) {
            return ['status' => false, 'message' => 'You do not have permission to create pre-draft purchase orders.'];
        }

        try {
            $data = $this->sanitizeAndValidate($payload, false);
            $data['status_id'] = status('Pre-Draft');
            $data['completed_by'] = null;
            $data['date_completed'] = null;

            DB::transaction(function () use ($data) {
                $this->purchase = $this->purchaseOrderRepository->savePurchaseOrder($this->purchase, $data);
            });

            $this->alert('success', 'Purchase Order', [
                'position' => 'center',
                'timer' => 4000,
                'toast' => false,
                'text' => 'Pre-Draft Purchase Order has been saved successfully!',
            ]);

            return [
                'status' => true,
                'purchase_id' => $this->purchase->id,
                'redirect' => route('purchase.pre_draft_list'),
            ];
        } catch (ValidationException $e) {
            $msg = collect($e->errors())->flatten()->first() ?? 'Validation failed.';
            return ['status' => false, 'message' => $msg];
        } catch (\Throwable $e) {
            report($e);
            return ['status' => false, 'message' => 'Unable to save pre-draft: ' . $e->getMessage()];
        }
    }

    public function completePurchase(array $payload)
    {
        if (!$this->canComplete) {
            return ['status' => false, 'message' => 'You do not have permission to complete purchase orders.'];
        }

        try {
            $data = $this->sanitizeAndValidate($payload, true);
            $data['status_id'] = status('Draft');
            $data['completed_by'] = auth()->id();
            $data['date_completed'] = todaysDate();

            DB::transaction(function () use ($data) {
                $this->purchase = $this->purchaseOrderRepository->savePurchaseOrder($this->purchase, $data);
                $this->purchaseOrderRepository->complete($this->purchase->fresh());
            });

            $this->alert('success', 'Purchase Order', [
                'position' => 'center',
                'timer' => 4000,
                'toast' => false,
                'text' => 'Purchase Order has been completed successfully!',
            ]);

            return [
                'status' => true,
                'purchase_id' => $this->purchase->id,
                'redirect' => route('purchase.approved'),
            ];
        } catch (ValidationException $e) {
            $msg = collect($e->errors())->flatten()->first() ?? 'Validation failed.';
            return ['status' => false, 'message' => $msg];
        } catch (\Throwable $e) {
            report($e);
            return ['status' => false, 'message' => 'Unable to complete purchase order: ' . $e->getMessage()];
        }
    }
}
