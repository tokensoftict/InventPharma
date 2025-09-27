<?php

namespace App\Http\Controllers\Settings;

use App\Classes\Settings;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class StoreSettings extends Controller
{
    protected Settings $settings;

    public function __construct(Settings $_settings){
        $this->settings = $_settings;
    }


    public function show(){

        return setPageContent('settings.storesettings.settings');
    }


    public function update(){

    }




}
