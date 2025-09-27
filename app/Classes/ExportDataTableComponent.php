<?php

namespace App\Classes;

use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;

abstract class ExportDataTableComponent extends DataTableComponent
{
    public array $perPageAccepted = [25, 50, 100, 200, 500, 1000, 1500, 2000, 2500, 3000, 3500, 4000, 4500, 5000, 5000, 6000, 6500, 10000,15000,20000,25000, -1];

    public function getExportBuilder() : Builder
    {
        $this->setupColumnSelect();
        $this->setupPagination();
        $this->setupReordering();
        $this->baseQuery();
       return $this->getBuilder();
    }

}
