<?php

namespace App\Models;

use App\Enums\ActiveStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'code', 'name', 'category', 'unit', 'price', 'status', 'description',
])]
class Service extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => ActiveStatus::class,
            'price' => 'decimal:2',
        ];
    }

    public function medicalRecordServices(): HasMany
    {
        return $this->hasMany(MedicalRecordService::class);
    }

    /** @param  Builder<Service>  $query */
    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        if (blank($term)) {
            return;
        }

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';

        $query->where(function (Builder $q) use ($like) {
            $q->where('name', 'like', $like)
                ->orWhere('code', 'like', $like)
                ->orWhere('category', 'like', $like);
        });
    }

    /** @param  Builder<Service>  $query */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', ActiveStatus::Active->value);
    }

    /** @return array<string, string> */
    public static function categoryOptions(): array
    {
        $fixed = [
            'Konsultasi' => 'Konsultasi',
            'Tindakan Medis' => 'Tindakan Medis',
            'Laboratorium' => 'Laboratorium',
            'Radiologi' => 'Radiologi',
            'Keperawatan' => 'Keperawatan',
            'Rawat Inap' => 'Rawat Inap',
        ];

        $existing = static::query()
            ->distinct()
            ->orderBy('category')
            ->pluck('category', 'category')
            ->all();

        return $existing + $fixed;
    }

    /** @return array<string, string> */
    public static function unitOptions(): array
    {
        return [
            'Tindakan' => 'Tindakan',
            'Kali' => 'Kali',
            'Kunjungan' => 'Kunjungan',
            'Pemeriksaan' => 'Pemeriksaan',
            'Hari' => 'Hari',
        ];
    }
}
