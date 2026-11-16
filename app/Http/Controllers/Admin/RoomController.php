<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ActiveStatus;
use App\Enums\AdmissionStatus;
use App\Enums\BedStatus;
use App\Enums\RoomClass;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoomRequest;
use App\Models\Room;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoomController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(Request $request): View
    {
        $rooms = Room::query()
            ->withCount([
                'beds',
                'beds as occupied_beds_count' => fn ($q) => $q->where('status', BedStatus::Occupied->value),
                'admissions as active_admissions_count' => fn ($q) => $q->where('status', AdmissionStatus::Active->value),
            ])
            ->when($request->filled('q'), fn ($q) => $q->search($request->string('q')->trim()->value()))
            ->when($request->filled('class'), fn ($q) => $q->where('class', $request->string('class')->value()))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->value()))
            ->orderBy('floor')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.rooms.index', [
            'rooms' => $rooms,
            'classes' => RoomClass::options(),
            'statuses' => ActiveStatus::options(),
            'filters' => $request->only('q', 'class', 'status'),
        ]);
    }

    public function create(): View
    {
        return view('admin.rooms.form', [
            'room' => new Room([
                'class' => RoomClass::Class3,
                'status' => ActiveStatus::Active,
                'rate_per_day' => 0,
            ]),
            'classes' => RoomClass::options(),
            'statuses' => ActiveStatus::options(),
        ]);
    }

    public function store(RoomRequest $request): RedirectResponse
    {
        $room = Room::create($request->payload());

        $this->audit->created('room', 'Room', $room->id, "Menambahkan kamar rawat inap {$room->name}.");

        return redirect()->route('admin.rooms.show', $room)
            ->with('success', "Kamar {$room->name} berhasil ditambahkan. Tambahkan tempat tidur agar dapat dipakai.");
    }

    public function show(Room $room): View
    {
        $room->load([
            'beds.activeAdmission.patient',
            'admissions' => fn ($q) => $q->with(['patient', 'doctor', 'bed'])
                ->latest('admitted_at')
                ->limit(20),
        ]);

        return view('admin.rooms.show', [
            'room' => $room,
            'bedStatuses' => BedStatus::manualOptions(),
        ]);
    }

    public function edit(Room $room): View
    {
        return view('admin.rooms.form', [
            'room' => $room,
            'classes' => RoomClass::options(),
            'statuses' => ActiveStatus::options(),
        ]);
    }

    public function update(RoomRequest $request, Room $room): RedirectResponse
    {
        $room->update($request->payload());

        $this->audit->updated('room', 'Room', $room->id, "Memperbarui kamar rawat inap {$room->name}.");

        return redirect()->route('admin.rooms.show', $room)
            ->with('success', "Kamar {$room->name} berhasil diperbarui.");
    }

    public function toggleStatus(Room $room): RedirectResponse
    {
        $status = $room->status === ActiveStatus::Active ? ActiveStatus::Inactive : ActiveStatus::Active;
        $room->update(['status' => $status]);

        $this->audit->updated('room', 'Room', $room->id, "Mengubah status kamar {$room->name} menjadi {$status->label()}.");

        return back()->with('success', "Status kamar {$room->name} diubah menjadi {$status->label()}.");
    }

    public function destroy(Room $room): RedirectResponse
    {
        if ($room->hasActiveAdmission()) {
            return back()->with('error', 'Kamar ini masih dipakai pasien rawat inap aktif.');
        }

        if ($room->admissions()->exists()) {
            return back()->with('error', 'Kamar ini sudah memiliki riwayat rawat inap sehingga tidak dapat dihapus. Nonaktifkan saja.');
        }

        if ($room->beds()->exists()) {
            return back()->with('error', 'Hapus seluruh tempat tidur pada kamar ini terlebih dahulu.');
        }

        $name = $room->name;
        $room->delete();

        $this->audit->deleted('room', 'Room', $room->id, "Menghapus kamar rawat inap {$name}.");

        return redirect()->route('admin.rooms.index')->with('success', "Kamar {$name} telah dihapus.");
    }
}
