<?php

namespace App\Repositories;

use App\Models\Customer;

class CustomerRepository
{
    public function __construct()
    {
        //
    }

    public function findCustomer($name)
    {
        if(\Str::length($name) < 3) return collect([])->toJson();
        return  \DB::table('customers')->select("*",\DB::raw("CONCAT(firstname,' ',lastname) as text"))->where(function($query) use ($name){
            $query->orwhere('firstname', 'LIKE', "%{$name}%")
                ->orwhere('lastname', 'LIKE', "%{$name}%")
                ->orWhere('phone_number', "LIKE", "%{$name}%")
                ->orWhere('email', "LIKE", "%{$name}%");
        })->where('status',1)->where('id','<>', 1)->get()->toJson();
    }
}
