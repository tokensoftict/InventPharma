<?php

namespace App\Http\Controllers\ExpensesReport;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class ExpensesReportController extends Controller
{

    public function by_date(Request $request)
    {
        $data = [
            'title' => 'Expenses Report By Date',
            'subtitle' => 'View Expenses Report By Date Range',
            'filters' => [
                'from' =>todaysDate(),
                'to'=>todaysDate(),
                'filters' => [
                    'between.expense_date' => [todaysDate(),todaysDate()],
                ]
            ]
        ];
        if($request->get('filter'))
        {
            $data['filters'] = $request->get('filter');
            $data['filters']['filters']['between.expense_date'] = Arr::only(array_values( $request->get('filter')), [0,1]);
        }

        return view('reports.expenses.by_date', $data);
    }


    public function by_type(Request $request)
    {
        $data = [
            'title' => 'Expenses Report By Expense Type',
            'subtitle' => 'View Expenses Report By Expense Type',
            'filters' => [
                'from' =>todaysDate(),
                'to'=>todaysDate(),
                'expenses_type_id' => 1,
                'filters' => [
                    'between.expense_date' => [todaysDate(),todaysDate()],
                    'expenses_type_id' => 1,
                ]
            ]
        ];
        if($request->get('filter'))
        {
            $data['filters'] = $request->get('filter');
            $data['filters']['filters']['between.expense_date'] = Arr::only(array_values( $request->get('filter')), [0,1]);
            $data['filters']['filters']['expenses_type_id'] = $data['filters']['expenses_type_id'];
        }

        return view('reports.expenses.by_type', $data);
    }

    public function by_department(Request $request)
    {
        $data = [
            'title' => 'Expenses Report By Expense Department',
            'subtitle' => 'View Expenses Report By Expense Department',
            'filters' => [
                'from' =>todaysDate(),
                'to'=>todaysDate(),
                'department_id' => 1,
                'filters' => [
                    'between.expense_date' => [todaysDate(),todaysDate()],
                    'department_id' => 1,
                ]
            ]
        ];
        if($request->get('filter'))
        {
            $data['filters'] = $request->get('filter');
            $data['filters']['filters']['between.expense_date'] = Arr::only(array_values( $request->get('filter')), [0,1]);
            $data['filters']['filters']['department_id'] = $data['filters']['department_id'];
        }

        return view('reports.expenses.by_department', $data);
    }

    public function by_type_and_department(Request $request)
    {
        $data = [
            'title' => 'Expenses Report By Expense Department',
            'subtitle' => 'View Expenses Report By Expense Department',
            'filters' => [
                'from' =>todaysDate(),
                'to'=>todaysDate(),
                'department_id' => 1,
                'expenses_type_id' => 1,
                'filters' => [
                    'between.expense_date' => [todaysDate(),todaysDate()],
                    'expenses_type_id' => 1,
                    'department_id' => 1,
                ]
            ]
        ];
        if($request->get('filter'))
        {
            $data['filters'] = $request->get('filter');
            $data['filters']['filters']['between.expense_date'] = Arr::only(array_values( $request->get('filter')), [0,1]);
            $data['filters']['filters']['department_id'] = $data['filters']['department_id'];
            $data['filters']['filters']['expenses_type_id'] = $data['filters']['expenses_type_id'];
        }

        return view('reports.expenses.by_type_and_department', $data);
    }

    public function by_user(Request $request)
    {
        $data = [
            'title' => 'Expenses Report By System User',
            'subtitle' => 'View Expenses Report By System User',
            'user_id' => 1,
            'filters' => [
                'from' =>todaysDate(),
                'to'=>todaysDate(),
                'user_id' => 1,
                'filters' => [
                    'between.expense_date' => [todaysDate(),todaysDate()],
                    'user_id' => 1,
                ]
            ]
        ];
        if($request->get('filter'))
        {
            $data['filters'] = $request->get('filter');
            $data['filters']['filters']['between.expense_date'] = Arr::only(array_values( $request->get('filter')), [0,1]);
            $data['filters']['filters']['user_id'] = $data['filters']['user_id'];
        }

        return view('reports.expenses.by_user', $data);
    }

}
