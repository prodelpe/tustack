<?php

namespace App\Enums;

enum CompanyAliasStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
