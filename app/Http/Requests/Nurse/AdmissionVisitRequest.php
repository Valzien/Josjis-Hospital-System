<?php

namespace App\Http\Requests\Nurse;

use App\Http\Requests\Doctor\AdmissionVisitRequest as BaseAdmissionVisitRequest;

class AdmissionVisitRequest extends BaseAdmissionVisitRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isNurse() || $this->user()?->isAdmin();
    }
}
