<?php

namespace App\Http\Controllers;

use App\Support\Robots;
use Illuminate\Http\Response;

/** Fallback: what servers normally serve is the file written by robots:build. */
class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        return response(Robots::content(), 200, ['Content-Type' => 'text/plain']);
    }
}
