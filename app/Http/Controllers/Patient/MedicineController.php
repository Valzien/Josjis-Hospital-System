<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Medicine;
use App\Models\PrescriptionDetail;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MedicineController extends Controller
{
    public function index(Request $request): View
    {
        $patient = auth()->user()->patient;
        $tab = $request->string('tab', 'my_medicines')->value();

        $myMedicines = null;
        $medicines = null;

        if ($tab === 'my_medicines') {
            $myMedicines = PrescriptionDetail::query()
                ->whereHas('prescription', fn ($p) => $p->where('patient_id', $patient?->id))
                ->with(['prescription.doctor', 'medicine'])
                ->when($request->filled('q'), fn ($q) => $q->whereHas('medicine', fn ($m) => $m->where('name', 'like', '%'.$request->string('q')->trim().'%')))
                ->latest('id')
                ->paginate(12)
                ->withQueryString();
        } else {
            $medicines = Medicine::query()
                ->active()
                ->when($request->filled('q'), fn ($q) => $q->search($request->string('q')->trim()->value()))
                ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')->value))
                ->orderBy('name')
                ->paginate(12)
                ->withQueryString();
        }

        return view('patient.medicines.index', [
            'tab' => $tab,
            'myMedicines' => $myMedicines,
            'medicines' => $medicines,
            'categories' => Medicine::categoryOptions(),
            'filters' => $request->only('q', 'category', 'tab'),
        ]);
    }
}
