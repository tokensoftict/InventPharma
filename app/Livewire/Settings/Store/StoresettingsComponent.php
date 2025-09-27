<?php

namespace App\Livewire\Settings\Store;

use App\Classes\Settings;
use Illuminate\Support\Collection;
use Livewire\Component;
use App\Traits\LivewireAlert;
use Livewire\WithFileUploads;


class StoresettingsComponent extends Component
{
    use LivewireAlert, WithFileUploads;

    public array $store;

    private Settings $settings;

    public bool $logoSelected = false;

    public function boot(Settings $settings)
    {
        $this->settings = $settings;
    }

    public function mount()
    {
        //$this->settings->all()
        $this->store = $this->settings->all();
       /*
        $this->store =  [
            'name' => NULL,
            'tax' => NULL,
            'threshold_days'=> NULL,
            'supply_days' => NULL,
            'qty_to_buy_threshold' => NULL,
            'product_near_expiry_days' => NULL,
            'material_near_expiry_days' => NULL,
            'first_address' => NULL,
            'second_address' => NULL,
            'contact_number' => NULL,
            'logo' => NULL,
            'footer_notes' => NULL
        ];
        */
    }

    public function render()
    {
        return view('livewire.settings.store.storesettings-component');
    }

    public function update(){

        $validation = Settings::$validation;

        if($this->store['logo'] !== NULL && !is_string($this->store['logo']))
        {
            $validation['store.logo'] = 'mimes:jpeg,jpg|required|max:10000';
        }

        $this->validate($validation);

        if($this->store['logo'] !== NULL && !is_string($this->store['logo'])) {
            $this->store['logo'] = $this->store['logo']->store('logo', 'real_public');
        }

        $this->settings->put($this->store);

        $this->alert(
            "success",
            "System Settings",
            [
                'position' => 'center',
                'timer' => 2000,
                'toast' => false,
                'text' =>  "Settings has been saved successfully!",
            ]
        );

    }
}
