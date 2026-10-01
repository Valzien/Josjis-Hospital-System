<?php

namespace App\Models;

use App\Enums\BloodType;
use App\Enums\Gender;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'user_id', 'medical_record_number', 'nik', 'name', 'gender', 'birth_date',
    'phone', 'address', 'blood_type', 'emergency_contact_name', 'emergency_contact_phone',
])]
class Patient extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
            'blood_type' => BloodType::class,
            'birth_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function queues(): HasMany
    {
        return $this->hasMany(Queue::class);
    }

    public function medicalRecords(): HasMany
    {
        return $this->hasMany(MedicalRecord::class);
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    /** @param  Builder<Patient>  $query */
    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        if (blank($term)) {
            return;
        }

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';

        $query->where(function (Builder $q) use ($like) {
            $q->where('name', 'like', $like)
                ->orWhere('medical_record_number', 'like', $like)
                ->orWhere('nik', 'like', $like)
                ->orWhere('phone', 'like', $like);
        });
    }

    /** @param  Builder<Patient>  $query */
    #[Scope]
    protected function completedProfile(Builder $query): void
    {
        $query->whereNotNull('gender')
            ->whereNotNull('birth_date')
            ->whereNotNull('phone')
            ->whereNotNull('address');
    }

    public function age(): ?int
    {
        return $this->birth_date?->age;
    }

    public function initials(): string
    {
        return collect(preg_split('/\s+/', trim($this->name)))
            ->filter()
            ->take(2)
            ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
    }

    public function genderLabel(): string
    {
        return $this->gender?->label() ?? '-';
    }

    public function isProfileComplete(): bool
    {
        return filled($this->gender)
            && filled($this->birth_date)
            && filled($this->phone)
            && filled($this->address);
    }

    public function latestQueue(): ?Queue
    {
        return $this->queues()->latest('queue_date')->latest('id')->first();
    }

    public function openQueue(): ?Queue
    {
        return $this->queues()->open()->latest('id')->first();
    }

    public function latestMedicalRecord(): ?MedicalRecord
    {
        return $this->medicalRecords()->latest('examined_at')->latest('id')->first();
    }
}
