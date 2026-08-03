<?php

namespace App\Http\Controllers;

use App\Support\LegalDocument;
use Illuminate\View\View;

class LegalController extends Controller
{
    public function show(string $document): View
    {
        abort_unless(LegalDocument::isKnown($document), 404);

        return view('legal', [
            'body'           => LegalDocument::html($document),
            'updatedAt'      => LegalDocument::updatedAt($document),
            'seoTitle'       => __('legal.' . $document . '_title'),
            'seoDescription' => __('legal.' . $document . '_description'),
            'noindex'        => true,
            'breadcrumbs'    => [
                ['label' => 'TuStack', 'url' => route('home')],
                ['label' => __('legal.' . $document . '_heading')],
            ],
        ]);
    }
}
