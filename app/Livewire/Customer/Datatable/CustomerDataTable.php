<?php

namespace App\Livewire\Customer\Datatable;

use App\Classes\ExportDataTableComponent;
use App\Traits\SimpleDatatableComponentTrait;
use App\Classes\Column;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;

class CustomerDataTable extends ExportDataTableComponent
{
    use SimpleDatatableComponentTrait;

    protected $model = Customer::class;

    public array $filters = [];

    protected $listeners = [
        'refreshData' => '$refresh',
        'updateFilter' => 'applyFilter',
    ];

    public function applyFilter($filter)
    {
        $this->filters = array_merge($this->filters, $filter);
        $this->resetPage();
    }

    public function builder(): Builder
    {
        $eligibleStatuses = array_values(array_filter([
            status("Paid"),
            status("Complete"),
            status("Dispatched"),
        ]));
        if (empty($eligibleStatuses)) {
            $eligibleStatuses = [1, 2, 3];
        }

        $query = Customer::query()
            ->select('customers.*')
            ->where('customers.id', '>', 1)
            ->with(['city', 'creditpaymentlog', 'invoice', 'payment', 'memberGroup', 'retailMemberGroup'])
            ->withSum(['invoices as total_spent' => function ($q) use ($eligibleStatuses) {
                $q->whereIn('status_id', $eligibleStatuses);
            }], 'sub_total')
            ->withSum(['invoices as retail_spent' => function ($q) use ($eligibleStatuses) {
                $q->where('in_department', 'retail')
                  ->whereIn('status_id', $eligibleStatuses);
            }], 'sub_total')
            ->withSum(['invoices as wholesale_spent' => function ($q) use ($eligibleStatuses) {
                $q->whereIn('in_department', ['bulksales', 'wholesales'])
                  ->whereIn('status_id', $eligibleStatuses);
            }], 'sub_total')
            ->withCount(['invoices as total_invoices' => function ($q) use ($eligibleStatuses) {
                $q->whereIn('status_id', $eligibleStatuses);
            }]);

        // Filter by Department Type (Wholesale, Retail, Both)
        if (!empty($this->filters['department_type']) && $this->filters['department_type'] !== 'all') {
            $type = $this->filters['department_type'];
            if ($type === 'both') {
                $query->where(function ($q) {
                    $q->where('customer_type', 'both')
                      ->orWhere(function ($sub) {
                          $sub->whereNotNull('member_group_id')->whereNotNull('retail_member_group_id');
                      });
                });
            } elseif ($type === 'retail') {
                $query->where(function ($q) {
                    $q->where('customer_type', 'retail')
                      ->orWhere(function ($sub) {
                          $sub->whereNull('customer_type')->where('retail_customer', 1);
                      });
                });
            } elseif ($type === 'wholesale') {
                $query->where(function ($q) {
                    $q->where('customer_type', 'wholesale')
                      ->orWhere(function ($sub) {
                          $sub->whereNull('customer_type')->where('retail_customer', 0);
                      });
                });
            }
        } elseif (isset($this->filters['retail_customer']) && $this->filters['retail_customer'] !== '') {
            // Backward compatibility
            $query->where('retail_customer', $this->filters['retail_customer']);
        }

        // Filter by Financial Status (owing, deposit, cleared)
        if (!empty($this->filters['financial'])) {
            $financial = $this->filters['financial'];
            if ($financial === 'owing') {
                $query->where('credit_balance', '<', 0);
            } elseif ($financial === 'deposit') {
                $query->where(function ($q) {
                    $q->where('credit_balance', '>', 0)
                      ->orWhere('deposit_balance', '>', 0);
                });
            } elseif ($financial === 'cleared') {
                $query->where('credit_balance', 0);
            }
        }

        // Filter by Active Status
        if (isset($this->filters['status']) && $this->filters['status'] !== '') {
            $query->where('status', (int) $this->filters['status']);
        }

        return $query;
    }

    public static function mountColumn(): array
    {
        return [
            Column::make("Customer", 'firstname')
                ->format(function ($value, $row, Column $column) {
                    $initials = strtoupper(substr($row->firstname ?? '', 0, 1) . substr($row->lastname ?? '', 0, 1));
                    $html = '<div class="d-flex align-items-center">';
                    $html .= '<div class="avatar-xs me-2 d-none d-md-block"><span class="avatar-title rounded-circle bg-light text-primary fw-bold" style="font-size:11px; padding: 6px;">' . ($initials ?: 'CU') . '</span></div>';
                    $html .= '<div>';
                    $html .= '<a class="text-dark fw-bold text-decoration-none" wire:click="view(' . $row->id . ')" href="javascript:void(0);" title="Click to view details">' . e($row->firstname . ' ' . $row->lastname) . '</a>';
                    if (!empty($row->email)) {
                        $html .= '<div class="text-muted" style="font-size:11px;">' . e($row->email) . '</div>';
                    }
                    $html .= '</div></div>';
                    return $html;
                })->html()
                ->sortable()->searchable(),

            Column::make("Phone Number", "phone_number")
                ->format(function ($value, $row, Column $column) {
                    if (empty($value)) {
                        return '<span class="text-muted fst-italic small">No phone</span>';
                    }
                    return '<span class="fw-medium text-nowrap"><i class="bx bx-phone text-muted me-1"></i>' . e($value) . '</span>';
                })->html()
                ->sortable()->searchable(),

            Column::make("Category / Type", "customer_type")
                ->format(function ($value, $row, Column $column) {
                    return $row->type_badge;
                })->html()
                ->sortable(),

            Column::make("Credit / Debt Status", "credit_balance")
                ->format(function ($value, $row, Column $column) {
                    return $row->owing_badge;
                })->html()
                ->sortable(),

            Column::make("Total Spent", "id")
                ->format(function ($value, $row, Column $column) {
                    $total = (float) ($row->total_spent ?? 0);
                    $invoices = (int) ($row->total_invoices ?? 0);
                    $html = '<div class="fw-bold text-dark">' . ($total > 0 ? money($total) : '₦0.00') . '</div>';
                    $html .= '<div class="text-muted" style="font-size:10px;">' . $invoices . ' invoice(s)</div>';
                    return $html;
                })->html(),

            Column::make("City", "city.name")
                ->format(function ($value, $row, Column $column) {
                    $cityName = $row->city?->name ?? 'N/A';
                    $addr = $row->address ? \Illuminate\Support\Str::limit($row->address, 20) : '';
                    return '<div><span class="fw-semibold">' . e($cityName) . '</span>' . ($addr ? '<div class="text-muted" style="font-size:10px;" title="' . e($row->address) . '">' . e($addr) . '</div>' : '') . '</div>';
                })->html()
                ->sortable(),

            Column::make("Member Groups", "member_group_id")
                ->format(function ($value, $row, Column $column) {
                    $badges = [];
                    if ($row->memberGroup) {
                        $badges[] = '<span class="badge bg-primary-subtle text-primary border border-primary-subtle" title="Wholesale Member Group">' . e($row->memberGroup->label) . '</span>';
                    }
                    if ($row->retailMemberGroup) {
                        $badges[] = '<span class="badge bg-info-subtle text-info border border-info-subtle" title="Retail Member Group">' . e($row->retailMemberGroup->label) . '</span>';
                    }
                    return !empty($badges) ? implode(' ', $badges) : '<span class="text-muted small">None</span>';
                })->html(),

            Column::make("Status", "status")
                ->format(function ($value, $row, Column $column) {
                    return '<div class="form-check form-switch mb-0" dir="ltr">
                                <input wire:change="toggle(' . $row->id . ')" id="user' . $row->id . '" type="checkbox" class="form-check-input" ' . ($value ? 'checked' : '') . '>
                                <label class="form-check-label small" for="user' . $row->id . '">' . ($value ? '<span class="text-success fw-semibold">Active</span>' : '<span class="text-danger">Inactive</span>') . '</label>
                            </div>';
                })->html()
                ->sortable(),

            Column::make("Last Activity", "id")
                ->format(function ($value, $row, Column $column) {
                    $invDate = $row->invoice?->invoice_date ? convert_date($row->invoice->invoice_date) : null;
                    $payDate = $row->payment?->payment_date ? convert_date($row->payment->payment_date) : null;

                    if (!$invDate && !$payDate) {
                        return '<span class="text-muted small">No activity</span>';
                    }

                    $html = '<div style="font-size: 11px;">';
                    if ($invDate) {
                        $html .= '<div title="Last Invoice"><i class="bx bx-receipt text-primary me-1"></i>' . $invDate . '</div>';
                    }
                    if ($payDate) {
                        $html .= '<div class="text-muted" title="Last Payment"><i class="bx bx-check-circle text-success me-1"></i>' . $payDate . '</div>';
                    }
                    $html .= '</div>';
                    return $html;
                })->html(),

            Column::make("Action", "id")
                ->format(function ($value, $row, Column $column) {
                    $html = '<div class="btn-group btn-group-sm" role="group">';
                    
                    // View Customer Glance (360)
                    $html .= '<button class="btn btn-outline-info btn-sm" wire:click="view(' . $value . ')" title="View Customer Overview at a glance">
                                <i class="bx bx-show"></i> View
                              </button>';

                    if (userCanView('customer.update')) {
                        $html .= '<button class="btn btn-outline-primary btn-sm ms-1" wire:click="edit(' . $value . ')" title="Edit Customer">
                                    <i class="bx bx-edit-alt"></i>
                                  </button>';
                    }
                    $html .= '</div>';
                    return $html;
                })->html()
        ];
    }

    public function view($id)
    {
        $this->dispatch('viewCustomer', id: (int) $id);
    }

    public function edit($id)
    {
        $this->dispatch('editCustomer', id: (int) $id);
    }

    public function toggle($id)
    {
        $customer = Customer::find($id);
        if ($customer) {
            $customer->status = !$customer->status;
            $customer->save();
            $this->alert('success', 'Customer', [
                'position' => 'center',
                'timer' => 2000,
                'toast' => false,
                'text' => 'Customer status updated successfully.'
            ]);
        }
    }
}
