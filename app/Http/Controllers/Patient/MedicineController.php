<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Medicine;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MedicineController extends Controller
{
    public function index(Request $request): View
    {
        $medicines = Medicine::query()
            ->active()
            ->when($request->filled('q'), fn ($q) => $q->search($request->string('q')->trim()->value()))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')->value))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('patient.medicines.index', [
            'medicines' => $medicines,
            'categories' => Medicine::categoryOptions(),
            'filters' => $request->only('q', 'category'),
        ]);
    }
}
