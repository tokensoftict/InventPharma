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
        $name = trim((string) $name);
        if(\Str::length($name) < 2) return collect([])->toJson();

        // Escape LIKE wildcards typed by the user
        $escaped = addcslashes($name, '%_\\');
        $like = "%{$escaped}%";
        $prefix = "{$escaped}%";

        // Capped result set: returning every match (with SELECT *) made the browser
        // freeze while rendering thousands of rows for short queries.
        return \DB::table('customers')
            ->select("*", \DB::raw("CONCAT(firstname,' ',lastname) as text"))
            ->where(function($query) use ($like){
                $query->where('firstname', 'LIKE', $like)
                    ->orWhere('lastname', 'LIKE', $like)
                    ->orWhereRaw("CONCAT(firstname, ' ', lastname) LIKE ?", [$like])
                    ->orWhere('phone_number', 'LIKE', $like)
                    ->orWhere('email', 'LIKE', $like);
            })
            ->where('status', 1)
            ->where('id', '<>', 1)
            // best matches (starting with the term) first
            ->orderByRaw("CASE WHEN firstname LIKE ? OR lastname LIKE ? OR phone_number LIKE ? THEN 0 ELSE 1 END", [$prefix, $prefix, $prefix])
            ->orderBy('firstname')
            ->limit(15)
            ->get()
            ->toJson();
    }
}
