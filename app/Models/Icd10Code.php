<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Referensi kode diagnosis ICD-10.
 *
 * Data referensi diisi oleh Icd10Seeder dan dipakai bersama oleh rekam medis
 * rawat jalan maupun rawat inap. Kode disimpan sebagai snapshot pada
 * admission_diagnoses agar riwayat tidak berubah bila referensi diperbarui.
 */
#[Fillable(['code', 'name', 'category'])]
class Icd10Code extends Model
{
    use HasFactory;

    public function diagnoses(): HasMany
    {
        return $this->hasMany(AdmissionDiagnosis::class);
    }

    /** @param  Builder<Icd10Code>  $query */
    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        if (blank($term)) {
            return;
        }

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';

        $query->where(function (Builder $q) use ($like) {
            $q->where('code', 'like', $like)
                ->orWhere('name', 'like', $like)
                ->orWhere('category', 'like', $like);
        });
    }

    public function label(): string
    {
        return $this->code.' — '.$this->name;
    }
}
