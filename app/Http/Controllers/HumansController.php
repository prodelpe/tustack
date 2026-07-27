<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class HumansController extends Controller
{
    public function __invoke(): Response
    {
        // Readable by anyone who asks for it, invisible to search engines:
        // a plain text file cannot carry a meta robots tag, so it travels
        // as a header instead.
        return response(view('humans')->render(), 200, [
            'Content-Type'  => 'text/plain; charset=utf-8',
            'X-Robots-Tag'  => 'noindex, nofollow',
        ]);
    }
}
