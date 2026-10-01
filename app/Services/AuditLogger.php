<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

/**
 * Mencatat aktivitas penting ke tabel audit_logs (brief §13 & §22).
 * Sengaja memakai try/catch agar kegagalan audit tidak membatalkan transaksi bisnis.
 */
class AuditLogger
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public function log(string $module, string $action, string $description, array $properties = []): ?AuditLog
    {
        try {
            return AuditLog::create([
                'user_id' => Auth::id(),
                'action' => $action,
                'module' => $module,
                'description' => $description,
                'ip_address' => Request::ip(),
                'user_agent' => substr((string) Request::userAgent(), 0, 255) ?: null,
                'properties' => $properties ?: null,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Gagal menulis audit log: '.$e->getMessage(), [
                'module' => $module,
                'action' => $action,
            ]);

            return null;
        }
    }

    /** @param  array<string, mixed>  $properties */
    public function created(string $module, string $entity, string|int $id, string $description, array $properties = []): ?AuditLog
    {
        return $this->log($module, 'create', $description, $properties + ['entity' => $entity, 'entity_id' => $id]);
    }

    /** @param  array<string, mixed>  $properties */
    public function updated(string $module, string $entity, string|int $id, string $description, array $properties = []): ?AuditLog
    {
        return $this->log($module, 'update', $description, $properties + ['entity' => $entity, 'entity_id' => $id]);
    }

    /** @param  array<string, mixed>  $properties */
    public function deleted(string $module, string $entity, string|int $id, string $description, array $properties = []): ?AuditLog
    {
        return $this->log($module, 'delete', $description, $properties + ['entity' => $entity, 'entity_id' => $id]);
    }

    /** @param  array<string, mixed>  $properties */
    public function login(string $description, array $properties = []): ?AuditLog
    {
        return $this->log('auth', 'login', $description, $properties);
    }

    /** @param  array<string, mixed>  $properties */
    public function logout(string $description, array $properties = []): ?AuditLog
    {
        return $this->log('auth', 'logout', $description, $properties);
    }
}
