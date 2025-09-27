<?php


namespace App\Classes;

class Column extends \Rappasoft\LaravelLivewireTables\Views\Column
{

    public string $hello = "Me";

    public function getColumnTitle() : string
    {
        return $this->title;
    }

    public function getColumnField() : string
    {
        return $this->field;
    }


    public static function make(string $title, string $from = null) : Column
    {
        return new static($title, $from);
    }

}
