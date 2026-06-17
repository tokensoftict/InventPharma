<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ChequeScheduleExport implements FromView, ShouldAutoSize
{
    protected $cheques;
    protected $department_totals;

    public function __construct(array $cheques, array $department_totals)
    {
        $this->cheques = $cheques;
        $this->department_totals = $department_totals;
    }

    public function view(): View
    {
        return view('exports.cheque_schedule', [
            'cheques' => $this->cheques,
            'department_totals' => $this->department_totals
        ]);
    }
}
