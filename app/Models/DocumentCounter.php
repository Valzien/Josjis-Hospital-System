<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['scope', 'period', 'last_value'])]
class DocumentCounter extends Model
{
    /**
     * Penghitung nomor dokumen per jenis dan per periode (YYYYMM).
     *
     * Baris untuk satu periode dibuat sebelum nomor pertama periode itu
     * diberikan, sehingga lockForUpdate() selalu mengunci baris konkret.
     * Membangkitkan nomor dari "SELECT ... ORDER BY ... DESC LIMIT 1 FOR
     * UPDATE" terhadap tabel dokumen tidak aman: saat tabel masih kosong
     * tidak ada baris yang terkunci dan InnoDB justru saling-blocking
     * sampai terjadi deadlock.
     */
    protected function casts(): array
    {
        return [
            'last_value' => 'integer',
        ];
    }

    /** @param  Builder<DocumentCounter>  $query */
    #[Scope]
    protected function forPeriod(Builder $query, string $scope, string $period): void
    {
        $query->where('scope', $scope)->where('period', $period);
    }
}
