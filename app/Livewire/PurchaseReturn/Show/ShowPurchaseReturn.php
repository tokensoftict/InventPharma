<?php

namespace App\Livewire\PurchaseReturn\Show;

use App\Models\PurchaseReturn;
use App\Repositories\PurchaseReturnRepository;
use App\Traits\LivewireAlert;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class ShowPurchaseReturn extends Component
{
    use LivewireAlert;

    public PurchaseReturn $purchaseReturn;

    public string $title = '';

    public string $subtitle = '';

    public function render()
    {
        return view('livewire.purchase-return.show.show-purchase-return');
    }

    public function complete()
    {
        $this->authorize('complete', $this->purchaseReturn);

        try {
            $this->purchaseReturn = (new PurchaseReturnRepository())->complete($this->purchaseReturn);
        } catch (ValidationException $e) {
            $this->alert('error', 'Purchase Return', [
                'position' => 'center', 'timer' => 6000, 'toast' => false,
                'text' => collect($e->errors())->flatten()->first(),
            ]);
            return;
        }

        $this->alert('success', 'Purchase Return', [
            'position' => 'center', 'timer' => 4000, 'toast' => false,
            'text' => 'Purchase return has been completed successfully!.',
        ]);

        return redirect()->route('purchasereturn.show', $this->purchaseReturn->id);
    }

    public function delete()
    {
        $this->authorize('delete', $this->purchaseReturn);

        try {
            (new PurchaseReturnRepository())->delete($this->purchaseReturn);
        } catch (ValidationException $e) {
            $this->alert('error', 'Purchase Return', [
                'position' => 'center', 'timer' => 6000, 'toast' => false,
                'text' => collect($e->errors())->flatten()->first(),
            ]);
            return;
        }

        return redirect()->route('purchasereturn.index');
    }
}
