<?php

namespace App\Http\Controllers;

use App\Classes\Settings;
use App\Jobs\RunNearOsManually;
use App\Jobs\RunRetailNearOsManually;
use App\Models\Module;
use App\Models\Nearoutofstock;
use App\Models\Retailnearoutofstock;
use Illuminate\Http\Request;

class ReportsController extends Controller
{


    public Settings $settings;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    public function index()
    {
        $data = [];
        $data['modules'] = Module::with(['tasks'])->whereIn('id' ,Settings::$reports)->get();
        return setPageContent('reports', $data);
    }


    public function run_nearos()
    {
        $this->settings->put('m_run_nears', 'run');
        Nearoutofstock::truncate();
        //$this->dispatch(new RunNearOsManually());
        return redirect()->route('reports.productReport.nearoutofstock');
    }

    public function run_retail_nearos()
    {
        $this->settings->put('m_retail_run_nears', 'run');

        \DB::table('retailnearoutofstock')->truncate();
        //$this->dispatch(new RunRetailNearOsManually());
        return redirect()->route('reports.productReport.retailnearoutofstock');
    }

    public function run_moving_stock()
    {
        $this->settings->put('m_run_moving_stock', 'run');

        \DB::table('movingstocks')->truncate();

        return redirect()->route('reports.productReport.movingstocksreport');
    }



}
