<?php

namespace App\Http\Controllers;

use App\Support\PublicSearch;

class TendenciesController extends Controller
{
    public function __invoke()
    {
        return view('tendencies', PublicSearch::viewData());
    }
}
