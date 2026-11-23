<?php

namespace App\Livewire\PurchaseReturn;

use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Repositories\PurchaseReturnRepository;
use App\Traits\LivewireAlert;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class PurchaseReturnComponent extends Component
{
    use LivewireAlert;

    public PurchaseReturn $purchaseReturn;

    public array $initialData = [];

    public array $suppliersList = [];

    public array $departmentsList = [];

    public array $initialSupplierPos = [];

    public bool $canSave = false;

    public bool $canComplete = false;

    public function mount(PurchaseReturn $purchaseReturn, $suppliers = null, $departments = null)
    {
        $this->purchaseReturn = $purchaseReturn;

        $suppliersCol = $suppliers ?? suppliers(true);
        $this->suppliersList = collect($suppliersCol)->map(fn ($s) => [
            'id'   => (string) $s->id,
            'name' => (string) $s->name,
        ])->values()->all();

        $departmentsCol = $departments ?? departments(true);
        $this->departmentsList = collect($departmentsCol)->map(fn ($d) => [
            'id'              => (string) $d->id,
            'quantity_column' => (string) $d->quantity_column,
            'label'           => (string) $d->label,
        ])->values()->all();

        $repoData = PurchaseReturnRepository::purchaseReturn($this->purchaseReturn);
        $rawItems = is_string($repoData['items'] ?? null)
            ? (json_decode($repoData['items'], true) ?: [])
            : ($repoData['items'] ?? []);

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

        // Ensure saved department is always selectable when editing an existing draft
        if ($defaultDepartment !== '' && !collect($this->departmentsList)->contains('quantity_column', $defaultDepartment)) {
            $deptModel = department_by_quantity_column($defaultDepartment, false);
            $this->departmentsList[] = [
                'id'              => (string) ($deptModel->id ?? ''),
                'quantity_column' => $defaultDepartment,
                'label'           => (string) ($deptModel->label ?? (\App\Classes\Settings::$department[$defaultDepartment] ?? $defaultDepartment)),
            ];
        }

        $dateCreated = trim((string) ($repoData['date_created'] ?: todaysDate()));
        if (strlen($dateCreated) >= 10) {
            $dateCreated = substr($dateCreated, 0, 10);
        }

        $supplierId = (string) ($repoData['supplier_id'] ?? '');

        $this->initialData = [
            'return_id'    => $this->purchaseReturn->id ?? null,
            'supplier_id'  => $supplierId,
            'department'   => $defaultDepartment,
            'purchase_id'  => $repoData['purchase_id'] ? (string) $repoData['purchase_id'] : '',
            'date_created' => $dateCreated,
            'remark'       => (string) ($repoData['remark'] ?? $repoData['notes'] ?? ''),
            'items'        => $items,
        ];

        // Preload supplier's completed POs when editing an existing draft
        if ($supplierId !== '') {
            $this->initialSupplierPos = Purchase::withCount('purchaseitems')
                ->with(['purchaseitems:id,purchase_id,qty,cost_price'])
                ->where('supplier_id', $supplierId)
                ->where('status_id', status('Complete'))
                ->orderByDesc('id')
                ->limit(100)
                ->get()
                ->map(function (Purchase $po) {
                    $total = $po->purchaseitems->sum(fn ($i) => (float) $i->qty * (float) $i->cost_price);

                    return [
                        'id'           => $po->id,
                        'date_created' => $po->date_created ? mysql_str_date($po->date_created) : '',
                        'department'   => $po->department,
                        'items_count'  => $po->purchaseitems_count,
                        'total'        => round($total, 2),
                    ];
                })
                ->values()
                ->all();
        }

        $this->canSave = (bool) (userCanView('purchasereturn.create') || userCanView('purchasereturn.edit'));
        $this->canComplete = (bool) userCanView('purchasereturn.complete');
    }

    public function render()
    {
        return view('livewire.purchase-return.purchase-return-component');
    }

    private function buildRepositoryPayload(array $payload): array
    {
        $rawItems = $payload['items'] ?? [];
        $items = [];

        if (is_array($rawItems)) {
            foreach ($rawItems as $item) {
                $exp = trim((string) ($item['expiry_date'] ?? ''));
                $items[] = [
                    'stock_id'    => (int) ($item['stock_id'] ?? 0),
                    'qty'         => (int) ($item['qty'] ?? 0),
                    'cost_price'  => ($item['cost_price'] === '' || $item['cost_price'] === null) ? 0.0 : (float) $item['cost_price'],
                    'batch_no'    => trim((string) ($item['batch_no'] ?? '')),
                    'expiry_date' => $exp !== '' ? $exp : null,
                    'reason'      => trim((string) ($item['reason'] ?? '')) ?: null,
                ];
            }
        }

        return [
            'supplier_id'  => trim((string) ($payload['supplier_id'] ?? '')),
            'department'   => trim((string) ($payload['department'] ?? '')),
            'purchase_id'  => ($payload['purchase_id'] ?? '') !== '' ? (int) $payload['purchase_id'] : null,
            'date_created' => trim((string) ($payload['date_created'] ?? '')) ?: todaysDate(),
            'remark'       => trim((string) ($payload['remark'] ?? '')) ?: null,
            'user_id'      => auth()->id(),
            'items'        => $items,
        ];
    }

    public function draftReturn(array $payload)
    {
        if (!$this->canSave) {
            return ['status' => false, 'message' => 'You do not have permission to save purchase returns.'];
        }

        try {
            $data = $this->buildRepositoryPayload($payload);
            $return = (new PurchaseReturnRepository())->save($this->purchaseReturn, $data);
            $this->purchaseReturn = $return;

            $this->alert('success', 'Purchase Return', [
                'position' => 'center',
                'timer'    => 4000,
                'toast'    => false,
                'text'     => 'Purchase return has been drafted successfully!',
            ]);

            return [
                'status'    => true,
                'return_id' => $return->id,
                'redirect'  => route('purchasereturn.index'),
            ];
        } catch (ValidationException $e) {
            $msg = collect($e->errors())->flatten()->first() ?? 'Validation failed.';
            return ['status' => false, 'message' => $msg];
        } catch (\Throwable $e) {
            report($e);
            return ['status' => false, 'message' => 'Unable to save draft return: ' . $e->getMessage()];
        }
    }

    public function completeReturn(array $payload)
    {
        if (!$this->canComplete) {
            return ['status' => false, 'message' => 'You do not have permission to complete purchase returns.'];
        }

        try {
            $repo = new PurchaseReturnRepository();
            $data = $this->buildRepositoryPayload($payload);
            $return = $repo->save($this->purchaseReturn, $data);
            $this->purchaseReturn = $return;

            $repo->complete($return);

            $this->alert('success', 'Purchase Return', [
                'position' => 'center',
                'timer'    => 4000,
                'toast'    => false,
                'text'     => 'Purchase return has been completed successfully!',
            ]);

            return [
                'status'    => true,
                'return_id' => $return->id,
                'redirect'  => route('purchasereturn.approved'),
            ];
        } catch (ValidationException $e) {
            $msg = collect($e->errors())->flatten()->first() ?? 'Validation failed.';
            return ['status' => false, 'message' => $msg];
        } catch (\Throwable $e) {
            report($e);
            return ['status' => false, 'message' => 'Unable to complete purchase return: ' . $e->getMessage()];
        }
    }
}
