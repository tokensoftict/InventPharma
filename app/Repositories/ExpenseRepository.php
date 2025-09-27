<?php

namespace App\Repositories;

use App\Models\Expense;

class ExpenseRepository
{
    public function __construct()
    {
        //
    }

    public static array $fields = [
        'amount' => null,
        'department_id' => null,
        'expenses_type_id' => null,
        'user_id' => null,
        'expense_date' => null,
        'purpose' => null,
    ];


    public static function create(array $data)
    {
        Expense::create($data);
    }


    public static function update(int $id, array $data)
    {
        Expense::where('id', $id)->update($data);
    }

    public static function destroy(int $id)
    {
        Expense::where('id', $id)->delete();
    }

}
