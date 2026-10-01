<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ActiveStatus;
use App\Enums\Day;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ScheduleRequest;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(Request $request): View
    {
        $schedules = DoctorSchedule::query()
            ->with('doctor')
            ->when($request->filled('q'), fn ($q) => $q->whereHas('doctor', fn ($d) => $d->search($request->string('q')->trim()->value)))
            ->when($request->filled('doctor_id'), fn ($q) => $q->where('doctor_id', $request->integer('doctor_id')))
            ->when($request->filled('day'), fn ($q) => $q->where('day', $request->integer('day')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->value))
            ->orderBy('day')
            ->orderBy('start_time')
            ->paginate(20)
            ->withQueryString();

        return view('admin.schedules.index', [
            'schedules' => $schedules,
            'doctors' => Doctor::query()->active()->orderBy('name')->get(),
            'days' => Day::options(),
            'statuses' => ActiveStatus::options(),
            'filters' => $request->only('q', 'doctor_id', 'day', 'status'),
            'weeklyGrid' => $this->weeklyGrid(),
        ]);
    }

    public function create(): View
    {
        return view('admin.schedules.form', [
            'schedule' => new DoctorSchedule(['day' => now()->dayOfWeek, 'quota' => 30, 'status' => ActiveStatus::Active]),
            'doctors' => Doctor::query()->active()->orderBy('name')->get(),
            'days' => Day::options(),
            'statuses' => ActiveStatus::options(),
        ]);
    }

    public function show(DoctorSchedule $schedule): RedirectResponse
    {
        return redirect()->route('admin.schedules.edit', $schedule);
    }

    public function store(ScheduleRequest $request): RedirectResponse
    {
        $schedule = DoctorSchedule::create($request->payload());

        $this->audit->created('doctor', 'DoctorSchedule', $schedule->id, "Menambahkan jadwal {$schedule->doctor->name} pada {$schedule->dayLabel()}.");

        return redirect()->route('admin.schedules.index')->with('success', 'Jadwal dokter berhasil ditambahkan.');
    }

    public function edit(DoctorSchedule $schedule): View
    {
        $schedule->load('doctor');

        return view('admin.schedules.form', [
            'schedule' => $schedule,
            'doctors' => Doctor::query()->active()->orderBy('name')->get(),
            'days' => Day::options(),
            'statuses' => ActiveStatus::options(),
        ]);
    }

    public function update(ScheduleRequest $request, DoctorSchedule $schedule): RedirectResponse
    {
        $schedule->update($request->payload());

        return redirect()->route('admin.schedules.index')->with('success', 'Jadwal dokter berhasil diperbarui.');
    }

    public function destroy(DoctorSchedule $schedule): RedirectResponse
    {
        $doctor = $schedule->doctor->name;
        $day = $schedule->dayLabel();
        $schedule->delete();

        return redirect()->route('admin.schedules.index')->with('success', "Jadwal {$doctor} hari {$day} telah dihapus.");
    }

    /**
     * Grid mingguan untuk tampilan overview.
     *
     * @return array<int, array<int, Collection<int, DoctorSchedule>>>
     */
    private function weeklyGrid(): array
    {
        $schedules = DoctorSchedule::query()
            ->with('doctor')
            ->active()
            ->orderBy('start_time')
            ->get()
            ->groupBy(fn (DoctorSchedule $s) => $s->day->value);

        $grid = [];

        for ($day = 0; $day <= 6; $day++) {
            $grid[$day] = $schedules->get($day, collect());
        }

        return $grid;
    }
}
