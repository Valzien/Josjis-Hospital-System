<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SettingRequest;
use App\Models\Setting;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingController extends Controller
{
    /** Definisi field pengaturan umum sistem. */
    private const FIELDS = [
        'hospital_name' => ['label' => 'Nama Rumah Sakit', 'group' => 'umum', 'type' => 'string'],
        'hospital_tagline' => ['label' => 'Tagline', 'group' => 'umum', 'type' => 'string'],
        'hospital_address' => ['label' => 'Alamat', 'group' => 'umum', 'type' => 'text'],
        'hospital_phone' => ['label' => 'Telepon', 'group' => 'umum', 'type' => 'string'],
        'hospital_email' => ['label' => 'Email', 'group' => 'umum', 'type' => 'string'],
        'queue_prefix' => ['label' => 'Prefix Nomor Antrean', 'group' => 'antrean', 'type' => 'string'],
        'estimated_minutes' => ['label' => 'Estimasi Durata per Pasien (menit)', 'group' => 'antrean', 'type' => 'number'],
        'auto_call_refresh' => ['label' => 'Auto Refresh Papan Antrean (detik)', 'group' => 'antrean', 'type' => 'number'],
        'demo_mode' => ['label' => 'Mode Demo', 'group' => 'sistem', 'type' => 'boolean'],
        'allow_patient_registration' => ['label' => 'Izinkan Pendaftaran Pasien Online', 'group' => 'sistem', 'type' => 'boolean'],
        'allow_online_queue' => ['label' => 'Izinkan Ambil Antrean Online', 'group' => 'sistem', 'type' => 'boolean'],
        'low_stock_alert' => ['label' => 'Tampilkan Peringatan Stok Rendah', 'group' => 'farmasi', 'type' => 'boolean'],
    ];

    public function __construct(private readonly AuditLogger $audit) {}

    public function index(): View
    {
        $grouped = [];

        foreach (self::FIELDS as $key => $definition) {
            $grouped[$definition['group']][] = [
                'key' => $key,
                'label' => $definition['label'],
                'type' => $definition['type'],
                'value' => Setting::get($key),
            ];
        }

        return view('admin.settings.index', [
            'groups' => $grouped,
            'groupLabels' => [
                'umum' => ['title' => 'Identitas Rumah Sakit', 'icon' => 'bi-hospital', 'desc' => 'Informasi yang ditampilkan pada halaman publik dan dokumen cetak.'],
                'antrean' => ['title' => 'Pengaturan Antrean', 'icon' => 'bi-list-ol', 'desc' => 'Aturan pembentukan nomor antrean dan tampilan papan antrean.'],
                'sistem' => ['title' => 'Pengaturan Sistem', 'icon' => 'bi-toggles', 'desc' => 'Fitur yang dapat diakses pasien secara mandiri.'],
                'farmasi' => ['title' => 'Pengaturan Farmasi', 'icon' => 'bi-capsule', 'desc' => 'Notifikasi dan monitoring stok obat.'],
            ],
            'rawCount' => Setting::query()->count(),
        ]);
    }

    public function update(SettingRequest $request): RedirectResponse
    {
        foreach ($request->input('settings', []) as $key => $value) {
            if (! array_key_exists($key, self::FIELDS)) {
                continue;
            }

            $type = self::FIELDS[$key]['type'];
            $stored = $type === 'boolean' ? ($value ? '1' : '0') : (string) $value;

            Setting::put($key, $stored, self::FIELDS[$key]['group'], $type, self::FIELDS[$key]['label']);
        }

        Setting::put('queue_prefix', strtoupper($request->input('queue_prefix')), 'antrean', 'string', 'Prefix Nomor Antrean');
        Setting::put('estimated_minutes', (string) $request->integer('estimated_minutes'), 'antrean', 'number');

        cache()->forget('jhs.nav-badges.'.$request->user()->id);

        $this->audit->updated('setting', 'Setting', null, 'Memperbarui pengaturan sistem.');

        return redirect()->route('admin.settings.index')->with('success', 'Pengaturan sistem berhasil disimpan.');
    }
}
