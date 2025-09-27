<?php

namespace App\Livewire\Settings\Category;

use App\Models\Category;
use App\Traits\SimpleComponentTrait;
use Livewire\Component;

class ProductCategoryComponent extends Component
{

    use SimpleComponentTrait;


    public function mount()
    {
        $this->model = Category::class;
        $this->modalName = "Product Category";
        $this->data = [
            'name' => ['label' => 'Category Name', 'type'=>'text'],
        ];

        $this->newValidateRules = [
            'name' => 'required|min:1',
        ];

        $this->updateValidateRules = $this->newValidateRules;

        $this->initControls();


    }

    public function booted()
    {
        //$this->cacheModel = "categories";
    }


    public function render()
    {
        return view('livewire.settings.category.product-category-component');
    }
}
