<?php

namespace App\Classes;

class BooleanColumn extends \Rappasoft\LaravelLivewireTables\Views\Columns\BooleanColumn
{
    public function getColumnTitle() : string
    {
        return $this->title;
    }

    public function getColumnField() : string
    {
        return $this->field;
    }

    public static function make(string $title, string $from = null) : BooleanColumn
    {
        return new static($title, $from);
    }
}
