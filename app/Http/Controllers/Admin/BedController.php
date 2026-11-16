<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BedRequest;
use App\Models\Bed;
use App\Models\Room;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;

class BedController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function store(BedRequest $request, Room $room): RedirectResponse
    {
        $bed = $room->beds()->create($request->payload());

        $this->audit->created('bed', 'Bed', $bed->id, "Menambahkan tempat tidur {$bed->code} pada kamar {$room->name}.");

        return back()->with('success', "Tempat tidur {$bed->code} berhasil ditambahkan.");
    }

    public function update(BedRequest $request, Room $room, Bed $bed): RedirectResponse
    {
        abort_unless($bed->room_id === $room->id, 404);

        $payload = $request->payload();

        if ($bed->activeAdmission()->exists() && $payload['status'] !== $bed->status?->value) {
            return back()->with('error', 'Status tempat tidur yang sedang terisi tidak dapat diubah. Pindahkan atau pulangkan pasien terlebih dahulu.');
        }

        $bed->update($payload);

        $this->audit->updated('bed', 'Bed', $bed->id, "Memperbarui tempat tidur {$bed->code} pada kamar {$room->name}.");

        return back()->with('success', "Tempat tidur {$bed->code} berhasil diperbarui.");
    }

    public function destroy(Room $room, Bed $bed): RedirectResponse
    {
        abort_unless($bed->room_id === $room->id, 404);

        if ($bed->activeAdmission()->exists()) {
            return back()->with('error', 'Tempat tidur ini sedang dipakai pasien rawat inap aktif.');
        }

        if ($bed->admissions()->exists()) {
            return back()->with('error', 'Tempat tidur ini sudah memiliki riwayat rawat inap sehingga tidak dapat dihapus. Nonaktifkan saja.');
        }

        $code = $bed->code;
        $bed->delete();

        $this->audit->deleted('bed', 'Bed', $bed->id, "Menghapus tempat tidur {$code} pada kamar {$room->name}.");

        return back()->with('success', "Tempat tidur {$code} telah dihapus.");
    }
}
