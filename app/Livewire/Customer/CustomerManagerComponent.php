<?php

namespace App\Livewire\Customer;

use App\Models\City;
use App\Models\Creditpaymentlog;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\MemberGroup;
use App\Models\Payment;
use App\Services\MemberGroupService;
use App\Traits\LivewireAlert;
use Livewire\Attributes\On;
use Livewire\Component;

class CustomerManagerComponent extends Component
{
    use LivewireAlert;

    protected $listeners = [
        'editCustomer' => 'edit',
        'viewCustomer' => 'view',
    ];

    public string $modalTitle = "New Customer";
    public string $saveButton = "Save Customer";
    public string $modalName = "Customer";

    public ?int $modelId = null;

    // Filters
    public array $filters = [];
    public string $currentDepartmentFilter = 'all';
    public string $currentFinancialFilter = '';

    // Form fields
    public string $firstname = "";
    public string $lastname = "";
    public string $address = "";
    public string $phone_number = "";
    public string $email = "";
    public string $city_id = "";
    public string $customer_type = "wholesale"; // 'wholesale', 'retail', 'both'
    public string $member_group_id = "";
    public string $retail_member_group_id = "";
    public ?string $duplicatePhoneWarning = null;

    // Lookups
    public $cities;
    public $memberGroups;

    // Customer 360 Glance state
    public $selectedCustomer = null;
    public $customerInvoices = [];
    public $customerPayments = [];
    public $customerCreditLogs = [];
    public array $selectedCustomerStats = [];

    public function mount(array $filters = [])
    {
        $this->filters = $filters;

        if (!empty($filters['department_type'])) {
            $this->currentDepartmentFilter = $filters['department_type'];
        } elseif (isset($filters['retail_customer'])) {
            $this->currentDepartmentFilter = $filters['retail_customer'] ? 'retail' : 'wholesale';
        } else {
            $this->currentDepartmentFilter = 'all';
        }

        $this->cities = City::orderBy('name', 'asc')->get();
        $this->memberGroups = MemberGroup::where('status', 1)->orderBy('label', 'asc')->get();
    }

    public function render()
    {
        return view('livewire.customer.customer-manager-component', [
            'stats' => $this->getStats(),
        ]);
    }

    public function filterByDepartment(string $type)
    {
        $this->currentDepartmentFilter = $type;
        $this->filters['department_type'] = $type;
        unset($this->filters['retail_customer']); // Merged mode replaces raw boolean
        $this->dispatch('updateFilter', ['department_type' => $type, 'retail_customer' => '']);
    }

    public function filterByFinancial(string $type)
    {
        $this->currentFinancialFilter = $type;
        $this->filters['financial'] = $type;
        $this->dispatch('updateFilter', ['financial' => $type]);
    }

    public function resetFilters()
    {
        $this->currentDepartmentFilter = 'all';
        $this->currentFinancialFilter = '';
        $this->filters = [];
        $this->dispatch('updateFilter', ['department_type' => 'all', 'financial' => '', 'retail_customer' => '']);
    }

    protected function getStats(): array
    {
        $eligibleStatuses = array_values(array_filter([
            status("Paid"),
            status("Complete"),
            status("Dispatched"),
        ]));
        if (empty($eligibleStatuses)) {
            $eligibleStatuses = [1, 2, 3];
        }

        $totalCustomers = Customer::where('id', '>', 1)->count();

        $wholesaleCount = Customer::where('id', '>', 1)->where(function ($q) {
            $q->where('customer_type', 'wholesale')
              ->orWhere(function ($sub) {
                  $sub->whereNull('customer_type')->where('retail_customer', 0);
              });
        })->count();

        $retailCount = Customer::where('id', '>', 1)->where(function ($q) {
            $q->where('customer_type', 'retail')
              ->orWhere(function ($sub) {
                  $sub->whereNull('customer_type')->where('retail_customer', 1);
              });
        })->count();

        $bothCount = Customer::where('id', '>', 1)->where('customer_type', 'both')->count();

        // Debtors and total owing
        $totalOwing = abs((float) Customer::where('id', '>', 1)->where('credit_balance', '<', 0)->sum('credit_balance'));
        $debtorsCount = Customer::where('id', '>', 1)->where('credit_balance', '<', 0)->count();

        // Total positive deposit/credit balances
        $totalDeposit = (float) Customer::where('id', '>', 1)->where('credit_balance', '>', 0)->sum('credit_balance')
            + (float) Customer::where('id', '>', 1)->sum('deposit_balance');

        // Total spent across completed invoices
        $totalSpent = (float) Invoice::whereIn('status_id', $eligibleStatuses)->sum('sub_total');

        return [
            'total_customers' => $totalCustomers,
            'wholesale_count' => $wholesaleCount,
            'retail_count' => $retailCount,
            'both_count' => $bothCount,
            'total_owing' => $totalOwing,
            'debtors_count' => $debtorsCount,
            'total_deposit' => $totalDeposit,
            'total_spent' => $totalSpent,
        ];
    }

    /* -------------------------------------------------------------------------
     * Customer 360 Glance Modal
     * ------------------------------------------------------------------------- */

    #[On('viewCustomer')]
    public function view($id = null)
    {
        if (is_array($id)) {
            $id = $id['id'] ?? $id[0] ?? null;
        }
        if (!$id) {
            return;
        }

        $customer = Customer::with(['city', 'memberGroup', 'retailMemberGroup'])->find($id);
        if (!$customer) {
            return;
        }

        $this->selectedCustomer = $customer;

        $eligibleStatuses = array_values(array_filter([
            status("Paid"),
            status("Complete"),
            status("Dispatched"),
        ]));
        if (empty($eligibleStatuses)) {
            $eligibleStatuses = [1, 2, 3];
        }

        $agg = Invoice::where('customer_id', $id)
            ->whereIn('status_id', $eligibleStatuses)
            ->selectRaw("COUNT(*) as total_invoices, COALESCE(SUM(sub_total),0) as total_spent, "
                . "COALESCE(SUM(CASE WHEN in_department = 'retail' THEN sub_total ELSE 0 END),0) as retail_spent, "
                . "COALESCE(SUM(CASE WHEN in_department IN ('bulksales','wholesales') THEN sub_total ELSE 0 END),0) as wholesale_spent")
            ->toBase()
            ->first();

        $this->selectedCustomerStats = [
            'total_spent' => (float) $agg->total_spent,
            'retail_spent' => (float) $agg->retail_spent,
            'wholesale_spent' => (float) $agg->wholesale_spent,
            'total_invoices' => (int) $agg->total_invoices,
            'owing_amount' => $customer->owing_amount,
            'is_owing' => $customer->is_owing,
        ];

        $this->customerInvoices = Invoice::where('customer_id', $id)
            ->with('status')
            ->latest('invoice_date')
            ->limit(10)
            ->get();

        $this->customerPayments = Payment::where('customer_id', $id)
            ->latest('payment_date')
            ->limit(10)
            ->get();

        $this->customerCreditLogs = Creditpaymentlog::where('customer_id', $id)
            ->latest('payment_date')
            ->limit(10)
            ->get();

        $this->dispatch("openGlanceModal", []);
    }

    public function closeGlance()
    {
        $this->dispatch("closeGlanceModal", []);
    }

    public function editFromGlance()
    {
        if ($this->selectedCustomer) {
            $id = $this->selectedCustomer->id;
            $this->closeGlance();
            $this->edit($id);
        }
    }

    /* -------------------------------------------------------------------------
     * Customer Form: New, Edit, Save
     * ------------------------------------------------------------------------- */

    public function new()
    {
        $this->modalTitle = "New Customer";
        $this->modelId = null;
        $this->firstname = "";
        $this->lastname = "";
        $this->email = "";
        $this->phone_number = "";
        $this->address = "";
        $this->city_id = "";
        $this->customer_type = ($this->currentDepartmentFilter === 'retail') ? 'retail' : 'wholesale';
        $this->member_group_id = "";
        $this->retail_member_group_id = "";
        $this->duplicatePhoneWarning = null;
        $this->saveButton = "Save Customer";

        $this->dispatch("openModal", []);
    }

    #[On('editCustomer')]
    public function edit($id = null)
    {
        if (is_array($id)) {
            $id = $id['id'] ?? $id[0] ?? null;
        }
        if (!$id) {
            return;
        }

        $customer = Customer::find($id);
        if (!$customer) {
            return;
        }

        $this->modalTitle = "Update Customer";
        $this->modelId = (int) $id;

        $this->firstname = $customer->firstname ?? "";
        $this->lastname = $customer->lastname ?? "";
        $this->email = $customer->email ?? "";
        $this->phone_number = $customer->phone_number ?? "";
        $this->address = $customer->address ?? "";
        $this->city_id = $customer->city_id ? (string) $customer->city_id : "";
        $this->customer_type = $customer->customer_type ?: ($customer->retail_customer ? 'retail' : 'wholesale');

        if (userCanView("customer.changeorassigncustumertomembergroup")) {
            $this->member_group_id = $customer->member_group_id ? (string) $customer->member_group_id : "";
            $this->retail_member_group_id = $customer->retail_member_group_id ? (string) $customer->retail_member_group_id : "";
        } else {
            $this->member_group_id = "";
            $this->retail_member_group_id = "";
        }

        $this->duplicatePhoneWarning = null;
        $this->saveButton = "Update Customer";

        $this->dispatch("openModal", []);
    }

    public function updatedPhoneNumber()
    {
        $this->duplicatePhoneWarning = null;
        $cleaned = trim(preg_replace('/[\s\-\(\)]+/', '', $this->phone_number));

        if (!empty($cleaned)) {
            $existing = $this->findPhoneOwner($cleaned);
            if ($existing) {
                $this->duplicatePhoneWarning = $this->duplicateMessage($cleaned, $existing);
            }
        }
    }

    private function findPhoneOwner(string $phone): ?Customer
    {
        $query = Customer::where('phone_number', $phone);
        if ($this->modelId) {
            $query->where('id', '<>', $this->modelId);
        }
        return $query->first();
    }

    private function duplicateMessage(string $phone, Customer $existing): string
    {
        return "Phone number '{$phone}' already belongs to customer '{$existing->firstname} {$existing->lastname}' (ID #{$existing->id})"
            . ($existing->status ? '' : ' (inactive)') . ". A phone number identifies one customer only.";
    }

    public function saveCustomers()
    {
        $this->validate([
            'firstname'     => 'required|string|max:100',
            'lastname'      => 'required|string|max:100',
            'email'         => 'nullable|email|max:150',
            'phone_number'  => 'required|string|max:25',
            'customer_type' => 'required|in:wholesale,retail,both',
        ]);

        $cleanedPhone = trim(preg_replace('/[\s\-\(\)]+/', '', $this->phone_number));

        if ($cleanedPhone === '') {
            $this->addError('phone_number', 'Phone number is required.');
            return;
        }

        // Phone number uniquely identifies a customer: never allow duplicates
        $duplicate = $this->findPhoneOwner($cleanedPhone);
        if ($duplicate) {
            $this->duplicatePhoneWarning = $this->duplicateMessage($cleanedPhone, $duplicate);
            $this->addError('phone_number', 'This phone number is already registered.');
            return;
        }

        if ($this->modelId) {
            $customer = Customer::findOrFail($this->modelId);
            $message = "Customer updated successfully.";
        } else {
            $customer = new Customer();
            $message = "Customer created successfully.";
        }

        // Standard Member Group downgrade guard
        if (userCanView("customer.changeorassigncustumertomembergroup")) {
            $memberGroupService = app(MemberGroupService::class);

            $newGroupId = $this->member_group_id ?: null;
            if ($this->modelId && $customer->member_group_id != $newGroupId) {
                $currentThreshold = $customer->memberGroup ? $customer->memberGroup->min_sales_amount : -1;
                $newGroup = $newGroupId ? MemberGroup::find($newGroupId) : null;
                $newThreshold = $newGroup ? $newGroup->min_sales_amount : 0;

                if ($newThreshold < $currentThreshold) {
                    $this->alert("error", "Member Group", [
                        'position' => 'center',
                        'timer' => 5000,
                        'text' => "Manual downgrade of Standard Member Group is not allowed."
                    ]);
                    return;
                }

                $memberGroupService->logHistory($customer, $customer->member_group_id, $newGroupId, 'other', 0, true);
                $customer->is_manual_member_group = true;
            }
            $customer->member_group_id = $newGroupId;

            // Retail Member Group downgrade guard
            $newRetailId = $this->retail_member_group_id ?: null;
            if ($this->modelId && $customer->retail_member_group_id != $newRetailId) {
                $currentRetailThreshold = $customer->retailMemberGroup ? $customer->retailMemberGroup->retail_min_sales_amount : -1;
                $newRetailGroup = $newRetailId ? MemberGroup::find($newRetailId) : null;
                $newRetailThreshold = $newRetailGroup ? $newRetailGroup->retail_min_sales_amount : 0;

                if ($newRetailThreshold < $currentRetailThreshold) {
                    $this->alert("error", "Retail Member Group", [
                        'position' => 'center',
                        'timer' => 5000,
                        'text' => "Manual downgrade of Retail Member Group is not allowed."
                    ]);
                    return;
                }

                $memberGroupService->logHistory($customer, $customer->retail_member_group_id, $newRetailId, 'retail', 0, true);
                $customer->is_manual_retail_member_group = true;
            }
            $customer->retail_member_group_id = $newRetailId;
        } elseif (!$this->modelId) {
            // Default groups for new customer if user lacks changeorassign permission
            $customer->member_group_id = 4;
            $customer->retail_member_group_id = 4;
        }

        $customer->firstname = $this->firstname;
        $customer->lastname = $this->lastname;
        $customer->email = $this->email ?: null;
        $customer->phone_number = $cleanedPhone;
        $customer->address = $this->address ?: null;
        $customer->city_id = $this->city_id ?: null;
        $customer->customer_type = $this->customer_type;
        $customer->retail_customer = ($this->customer_type === 'retail') ? 1 : 0;

        $customer->save();

        $this->dispatch("closeModal", []);
        $this->dispatch("refreshData");

        $this->alert("success", "Customer", [
            'position' => 'center',
            'timer' => 3000,
            'toast' => false,
            'text' => $message
        ]);
    }
}
