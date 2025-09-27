<?php

namespace App\Livewire\Settings\Bank;

use App\Models\Bank;
use App\Models\BankAccount;
use App\Traits\SimpleComponentTrait;
use Livewire\Component;

class BankAccountComponent extends Component
{
    use SimpleComponentTrait;


    public function mount()
    {
        $this->model = BankAccount::class;
        $this->modalName = "Bank Account";
        $this->data = [
            'account_name' => ['label' => 'Account Name', 'type'=>'text'],
            'account_number' => ['label' => 'Account Number', 'type'=>'text'],
            'bank_id' => ['label' => 'Bank', 'type'=>'select',
                'options'=> banks()->toArray()
            ],
        ];


        $this->newValidateRules = [
            'account_name' => 'required|min:3',
            'account_number' => 'required|min:10',
            'bank_id' => 'required',
        ];


        $this->updateValidateRules = $this->newValidateRules;

        $this->initControls();

    }



    public function booted()
    {
        $this->cacheModel = "bank_accounts";
    }


    public function render()
    {
        return view('livewire.settings.bank.bank-account-component');
    }
}
