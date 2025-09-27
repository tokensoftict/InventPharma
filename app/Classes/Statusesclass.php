<?php
namespace App\Classes;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class Statusesclass
{
    public static array $allStatuses = [
        [
            'name'=>'Active',
            'label'=>'primary'
        ],
        [
            'name'=>'Paid',
            'label'=>'success'
        ],
        [
            'name'=>'Draft',
            'label'=>'primary'
        ],
        [
            'name'=>'Dispatched',
            'label'=>'success'
        ],
        [
            'name'=>'Pending',
            'label'=>'warning'
        ],
        [
            'name'=>'Complete',
            'label'=>'success'
        ],
        [
            'name'=>'Approved',
            'label'=>'primary'
        ],
        [
            'name'=>'Declined',
            'label'=>'danger'
        ],
        [
            'name'=>'Deleted',
            'label'=>'danger'
        ],
        [
            'name'=>'Discount',
            'label'=>'primary'
        ],
        [
            'name'=>'Waiting-Material',
            'label'=>'primary'
        ],
        [
            'name'=>'Cancelled',
            'label'=>'danger'
        ],

        [
            'name'=>'Ready',
            'label'=>'success'
        ],
        [
            'name'=>'Transferred',
            'label'=>'primary'
        ],
        [
            'name'=>'In-Progress',
            'label'=>'warning'
        ],
        [
            'name'=>'Material-Approval-In-Progress',
            'label'=>'warning'
        ],
        [
            'name'=>'Waiting-For-Credit-Approval',
            'label'=>'primary'
        ],
        [
            'name'=>'Waiting-For-Cheque-Approval',
            'label'=>'primary'
        ],
        [
            'name'=>'Packing',
            'label'=>'warning'
        ],
        [
            'name'=>'Packed-Waiting-For-Payment',
            'label'=>'primary'
        ],
    ];


    public static function loadSystemStatus()
    {
        foreach (self::$allStatuses as $status)
        {
            DB::table('statuses')->updateOrInsert(['name'=> $status['name']], $status);
        }
    }
}