<?php

namespace App\Http\Controllers\Expenses;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\ExpensesType;
use App\Models\Stock;
use Illuminate\Http\Request;

class ExpensesController extends Controller
{
    public function index(){
        $data = [
            'filters' => ['expense_date'=>todaysDate()]
            ];
        return view('expenses.index', $data);
    }

    public function create(){
        $data = [
            'expense'=> new Expense(),
            'title'=>'New',
            'subtitle'=>'Create new'
        ];

        return view('expenses.form',$data);
    }


    public function edit(Expense $expense){
        $data = [
            'expense'=> $expense,
            'title'=>'Update',
            'subtitle'=>'Update'
        ];

        return view('expenses.form',$data);
    }

    public function update(Request $request, $id){}

    public function destroy(Expense $expense){

        if($expense) $expense->delete();

        return redirect()->route('expenses.index')->with('success', 'Expense has been deleted successfully');
    }
}
