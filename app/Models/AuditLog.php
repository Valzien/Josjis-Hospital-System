<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'action', 'module', 'description', 'ip_address', 'user_agent', 'properties'])]
class AuditLog extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    /** @param  Builder<AuditLog>  $query */
    #[Scope]
    protected function module(Builder $query, string $module): void
    {
        $query->where('module', $module);
    }

    /** @param  Builder<AuditLog>  $query */
    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        if (blank($term)) {
            return;
        }

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';

        $query->where(function (Builder $q) use ($like) {
            $q->where('description', 'like', $like)
                ->orWhere('action', 'like', $like)
                ->orWhereHas('user', fn (Builder $u) => $u->where('name', 'like', $like));
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function moduleBadge(): string
    {
        return match ($this->module) {
            'auth' => 'secondary',
            'user' => 'primary',
            'patient' => 'info',
            'doctor' => 'success',
            'queue' => 'warning',
            'examination' => 'info',
            'prescription' => 'primary',
            'pharmacy' => 'warning',
            'setting' => 'dark',
            default => 'secondary',
        };
    }

    public function moduleIcon(): string
    {
        return match ($this->module) {
            'auth' => 'bi-box-arrow-in-right',
            'user' => 'bi-people',
            'patient' => 'bi-person-vcard',
            'doctor' => 'bi-heart-pulse',
            'queue' => 'bi-list-ol',
            'examination' => 'bi-clipboard2-pulse',
            'prescription' => 'bi-file-earmark-medical',
            'pharmacy' => 'bi-capsule',
            'setting' => 'bi-gear',
            default => 'bi-dot',
        };
    }
}
