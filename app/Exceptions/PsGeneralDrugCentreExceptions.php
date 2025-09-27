<?php

namespace App\Exceptions;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class PsGeneralDrugCentreExceptions
{
    public static function handleExceptions($e, Request $request) : Response
    {
        report($e);
        return response($e->getMessage(), ResponseAlias::HTTP_BAD_REQUEST);
    }
}
