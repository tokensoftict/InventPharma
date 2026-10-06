<?php

namespace App\Livewire\PurchaseLimit;

use App\Classes\Settings;
use App\Models\PurchaseLimit;
use App\Models\Stock;
use App\Services\PurchaseLimitService;
use App\Traits\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class PurchaseLimitComponent extends Component
{
    use LivewireAlert, WithPagination;

    protected $listeners = [
        'editLimit' => 'edit',
        'toggleLimit' => 'toggle',
        'destroyLimit' => 'destroy',
        'refreshData' => '$refresh',
    ];

    public string $modalTitle = "New";
    public string $saveButton = "Save";
    public string $modalName = "Purchase Limit";

    public ?int $modelId = null;

    // Form fields
    public string $name = "";
    public string $department = "wholesales";
    public string $max_quantity = "";
    public string $period_value = "";
    public string $period_unit = "days";
    public ?string $start_date = null;
    public ?string $end_date = null;
    public bool $is_active = true;

    // Product selection
    public array $selectedProducts = [];
    public string $productSearch = "";
    public array $productSearchResults = [];

    public string $search = '';

    public function mount()
    {
    }

    public function render()
    {
        $limits = PurchaseLimit::with('stocks')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhereHas('stocks', function ($sq) {
                            $sq->where('name', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->orderByDesc('id')
            ->paginate(Settings::$pagination);

        return view('livewire.purchase-limit.purchase-limit-component', [
            'limits' => $limits,
        ]);
    }

    /**
     * Search products for the multi-select input.
     */
    public function updatedProductSearch()
    {
        if (strlen($this->productSearch) < 2) {
            $this->productSearchResults = [];
            return;
        }

        $selectedIds = array_column($this->selectedProducts, 'id');

        $this->productSearchResults = Stock::where('name', 'like', '%' . $this->productSearch . '%')
            ->where('status', 1)
            ->whereNotIn('id', $selectedIds)
            ->limit(10)
            ->get(['id', 'name'])
            ->toArray();
    }

    /**
     * Add a product to the selected list.
     */
    public function addProduct(int $productId, string $productName)
    {
        // Prevent duplicates
        foreach ($this->selectedProducts as $p) {
            if ($p['id'] === $productId) {
                return;
            }
        }

        $this->selectedProducts[] = ['id' => $productId, 'name' => $productName];
        $this->productSearch = "";
        $this->productSearchResults = [];
    }

    /**
     * Remove a product from the selected list.
     */
    public function removeProduct(int $productId)
    {
        $this->selectedProducts = array_values(
            array_filter($this->selectedProducts, fn($p) => $p['id'] !== $productId)
        );
    }

    /**
     * Open the "New" modal.
     */
    public function new()
    {
        $this->resetForm();
        $this->modalTitle = "New";
        $this->saveButton = "Save";
        $this->dispatch("openModal", []);
    }

    /**
     * Save a new purchase limit.
     */
    public function save()
    {
        $this->validate([
            'department' => 'required|in:wholesales,retail',
            'max_quantity' => 'required|integer|min:1',
            'period_value' => 'required|integer|min:1',
            'period_unit' => 'required|in:days,weeks,months',
            'selectedProducts' => 'required|array|min:1',
        ], [
            'department.required' => 'Please select the department for this purchase limit.',
            'selectedProducts.required' => 'Please select at least one product.',
            'selectedProducts.min' => 'Please select at least one product.',
        ]);

        $limit = new PurchaseLimit();
        $limit->name = $this->name ?: null;
        $limit->department = $this->department;
        $limit->max_quantity = (int)$this->max_quantity;
        $limit->period_value = (int)$this->period_value;
        $limit->period_unit = $this->period_unit;
        $limit->start_date = $this->start_date ?: null;
        $limit->end_date = $this->end_date ?: null;
        $limit->is_active = $this->is_active;
        $limit->created_by = auth()->id();
        $limit->updated_by = auth()->id();

        $stockIds = array_column($this->selectedProducts, 'id');

        // Check for conflicts
        $service = new PurchaseLimitService();
        $conflicts = $service->validateNoConflict($limit, $stockIds);

        if (!empty($conflicts)) {
            $this->alert("error", "Conflict Detected", [
                'position' => 'center',
                'timer' => 5000,
                'toast' => false,
                'text' => implode("\n", $conflicts),
            ]);
            return;
        }

        $limit->save();
        $limit->stocks()->sync($stockIds);

        $this->dispatch("closeModal", []);
        $this->dispatch('refreshData', []);
        $this->resetForm();

        $this->alert("success", "Purchase Limit", [
            'position' => 'center',
            'timer' => 2000,
            'toast' => false,
            'text' => "Purchase limit has been created successfully!",
        ]);
    }

    /**
     * Load a limit for editing.
     */
    public function edit($id)
    {
        $limit = PurchaseLimit::with('stocks')->find($id);
        if (!$limit) {
            return;
        }

        $this->modelId = $limit->id;
        $this->name = $limit->name ?? "";
        $this->department = $limit->department ?? "wholesales";
        $this->max_quantity = (string)$limit->max_quantity;
        $this->period_value = (string)$limit->period_value;
        $this->period_unit = $limit->period_unit;
        $this->start_date = $limit->start_date ? $limit->start_date->format('Y-m-d') : null;
        $this->end_date = $limit->end_date ? $limit->end_date->format('Y-m-d') : null;
        $this->is_active = $limit->is_active;

        $this->selectedProducts = $limit->stocks->map(fn($s) => ['id' => $s->id, 'name' => $s->name])->toArray();

        $this->modalTitle = "Update";
        $this->saveButton = "Update";
        $this->dispatch("openModal", []);
    }

    /**
     * Update an existing purchase limit.
     */
    public function update($id)
    {
        $this->validate([
            'department' => 'required|in:wholesales,retail',
            'max_quantity' => 'required|integer|min:1',
            'period_value' => 'required|integer|min:1',
            'period_unit' => 'required|in:days,weeks,months',
            'selectedProducts' => 'required|array|min:1',
        ], [
            'department.required' => 'Please select the department for this purchase limit.',
            'selectedProducts.required' => 'Please select at least one product.',
            'selectedProducts.min' => 'Please select at least one product.',
        ]);

        $limit = PurchaseLimit::find($id);
        if (!$limit) {
            return;
        }

        $limit->name = $this->name ?: null;
        $limit->department = $this->department;
        $limit->max_quantity = (int)$this->max_quantity;
        $limit->period_value = (int)$this->period_value;
        $limit->period_unit = $this->period_unit;
        $limit->start_date = $this->start_date ?: null;
        $limit->end_date = $this->end_date ?: null;
        $limit->is_active = $this->is_active;
        $limit->updated_by = auth()->id();

        $stockIds = array_column($this->selectedProducts, 'id');

        // Check for conflicts
        $service = new PurchaseLimitService();
        $conflicts = $service->validateNoConflict($limit, $stockIds);

        if (!empty($conflicts)) {
            $this->alert("error", "Conflict Detected", [
                'position' => 'center',
                'timer' => 5000,
                'toast' => false,
                'text' => implode("\n", $conflicts),
            ]);
            return;
        }

        $limit->save();
        $limit->stocks()->sync($stockIds);

        $this->dispatch("closeModal", []);
        $this->dispatch('refreshData', []);
        $this->resetForm();

        $this->alert("success", "Purchase Limit", [
            'position' => 'center',
            'timer' => 2000,
            'toast' => false,
            'text' => "Purchase limit has been updated successfully!",
        ]);
    }

    /**
     * Toggle active/inactive.
     */
    public function toggle($id)
    {
        $limit = PurchaseLimit::find($id);
        if ($limit) {
            $limit->is_active = !$limit->is_active;
            $limit->updated_by = auth()->id();
            $limit->save();

            $this->dispatch('refreshData', []);

            $this->alert("success", "Purchase Limit", [
                'position' => 'center',
                'timer' => 2000,
                'toast' => false,
                'text' => "Purchase limit has been " . ($limit->is_active ? 'activated' : 'deactivated') . " successfully!",
            ]);
        }
    }

    /**
     * Delete a purchase limit.
     */
    public function destroy($id)
    {
        $limit = PurchaseLimit::find($id);
        if ($limit) {
            $limit->delete();
            $this->dispatch('refreshData', []);

            $this->alert("success", "Purchase Limit", [
                'position' => 'center',
                'timer' => 2000,
                'toast' => false,
                'text' => "Purchase limit has been deleted successfully!",
            ]);
        }
    }

    /**
     * Reset form fields.
     */
    private function resetForm()
    {
        $this->modelId = null;
        $this->name = "";
        $this->department = "wholesales";
        $this->max_quantity = "";
        $this->period_value = "";
        $this->period_unit = "days";
        $this->start_date = null;
        $this->end_date = null;
        $this->is_active = true;
        $this->selectedProducts = [];
        $this->productSearch = "";
        $this->productSearchResults = [];
    }
}
