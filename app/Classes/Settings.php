<?php

namespace App\Classes;

use Spatie\Valuestore\Valuestore;

class Settings extends Valuestore
{


    public static array $department = [
        'quantity' => 'Main Store',
        'wholesales' => 'Whole Sales Department',
        'bulksales' => 'Bulk Sales Department',
        'retail' => 'Retail Department',
        'retail_store' => 'SuperMarket Store',
        '' => '',
        NULL => ''
    ];


    public static array $printType = [
        'waybill' => 'waybill',
        'thermal' => 'thermal',
        'a4' => 'a4'
    ];

    public static  $validation = [
        'store.name' => 'required|max:255',
        'store.first_address'=>'required',
        'store.contact_number'=>'required',
    ];

    public static $pagination = 10;

    public function store(){
        return json_decode(json_encode($this->all()));
    }

    public static array $reports = [12,13,14,15,16,17,18,19];

    public  static array $perPageAccepted = [50, 100, 200, 500, 1000, 1500, 2000, 2500, 3000, 3500, 4000, 4500, 5000, 5000, 6000, 6500, 0];

}
