<?php

namespace App\Http\Requests\Nurse;

use App\Http\Requests\Reception\AdmissionRequest as BaseAdmissionRequest;

class AdmissionRequest extends BaseAdmissionRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isNurse() || $this->user()?->isAdmin();
    }
}
