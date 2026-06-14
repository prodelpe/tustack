<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\JobOffer;

class JobOfferController extends Controller
{
    public function show(Company $company, JobOffer $jobOffer)
    {
        abort_if($jobOffer->company_id !== $company->id, 404);

        $jobOffer->load('technologies');

        return view('job-offer', compact('company', 'jobOffer'));
    }
}
