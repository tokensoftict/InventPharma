<?php

namespace App\Livewire\Expenses;

use App\Models\Expense;
use App\Models\ExpensesType;
use App\Repositories\ExpenseRepository;
use Illuminate\Support\Arr;
use Livewire\Component;
use App\Traits\LivewireAlert;

class ExpenseComponent extends Component
{
    use LivewireAlert;

    public Expense $expense;

    public $departments, $expenses_types;
    public array $expense_data;

    public function boot()
    {

    }

    public function booted()
    {
        $this->departments = departments(true);
        $this->expenses_types = ExpensesType::where('status', 1)->get(['id', 'name']);
    }

    public function mount()
    {
        if(isset($this->expense->id))
        {
            $this->expense_data = Arr::only( $this->expense->toArray(), array_keys(ExpenseRepository::$fields));
        }
        else {
            $this->expense_data = ExpenseRepository::$fields;
        }
    }

    public function render()
    {
        return view('livewire.expenses.expense-component');
    }


    public function saveExpense()
    {

        $data = [
            "expense_data.expense_date"=>"bail|required",
            "expense_data.supplier_id"=>"bail|required",
            "expense_data.department_id"=>"bail|required",
            "expense_data.expenses_type_id"=>"bail|required",
        ];

        $this->validate($data);

        if(!isset($this->expense->id)) {
            $message = "created";
            $this->expense_data['user_id'] = auth()->id();
            ExpenseRepository::create($this->expense_data);
        }else{
            $message = "updated";
            ExpenseRepository::update($this->expense->id, $this->expense_data);
        }

        $this->alert(
            "success",
            "Product",
            [
                'position' => 'center',
                'timer' => 12000,
                'toast' => false,
                'text' =>  "Expense has been ".$message." successfully!.",
            ]
        );

        return redirect()->route('expenses.index');
    }
}
