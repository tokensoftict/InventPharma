<?php
namespace App\Traits;


use App\Classes\Settings;
use PowerComponents\LivewirePowerGrid\{Exportable, Footer, Header, PowerGrid, PowerGridFields};

use PowerComponents\LivewirePowerGrid\Traits\{ WithExport};

trait PowerGridComponentTrait
{
    use WithExport;

    public function setUp(): array
    {
        $this->deferLoading = true;
        $this->primaryKey = $this->key;
        $this->showCheckBox($this->key);


        return [
            Exportable::make('export')
                ->type(
                    Exportable::TYPE_XLS,
                    Exportable::TYPE_CSV),
            Header::make()->showSearchInput(),
            Footer::make()
                ->showPerPage(100, Settings::$perPageAccepted)
                ->showRecordCount(),
        ];
    }



}
