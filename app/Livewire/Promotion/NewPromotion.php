<?php

namespace App\Livewire\Promotion;

use App\Imports\PromotionStocksImport;
use App\Models\Promotion;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use App\Traits\LivewireAlert;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

class NewPromotion extends Component
{
    use LivewireAlert, WithFileUploads;

    public $name;
    public $from;
    public $to;
    public $template;

    public Promotion $promotion;

    protected $rules = [
        'name' => 'required|string',
        'from' => 'required|date',
        'to' => 'required|date|after:from',
        'template' => 'required|file|mimes:xls,xlsx',
    ];


    public function boot()
    {

    }

    public function mount()
    {
        $this->from = Carbon::today()->toDateString();
        $this->to = Carbon::today()->toDateString();

        if(isset($this->promotion->id))
        {
            $this->name = $this->promotion->name;
            $this->from = $this->promotion->from_date->toDateString();
            $this->to = $this->promotion->end_date->toDateString();
        }
    }

    public function render()
    {
        return view('livewire.promotion.new-promotion');
    }


    public function store()
    {
        $validatedData = $this->validate();

        DB::transaction(function() use($validatedData, ){
            if(isset($this->promotion->id)){

                $this->promotion->fill([
                    'name' => $validatedData['name'],
                    'from_date' => $validatedData['from'],
                    'end_date' => $validatedData['to'],
                    'status_id' => status('Pending')
                ])->save();

                $this->promotion->promotion_items()->delete();

            }else {
                $this->promotion = Promotion::create([
                    'name' => $validatedData['name'],
                    'created' => Carbon::today()->toDateString(),
                    'user_id' => auth()->id(),
                    'from_date' => $validatedData['from'],
                    'end_date' => $validatedData['to'],
                    'status_id' => status('Pending')
                ]);
            }

            Excel::import(new PromotionStocksImport($this->promotion), $this->template);

        });


        // Clear the form fields after successful submission
        $this->resetExcept(['promotion']);

        $this->alert(
            "success",
            "Product",
            [
                'position' => 'center',
                'timer' => 12000,
                'toast' => false,
                'text' => (isset($this->promotion->id) ? "Promotion has been updated successfully" :  "Promotion has been created successfully"),
            ]
        );

        return redirect()->route('promo.index');
    }


}
