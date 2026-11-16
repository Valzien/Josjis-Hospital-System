<?php

namespace Database\Seeders;

use App\Models\Icd10Code;
use Illuminate\Database\Seeder;

/**
 * Data referensi kode ICD-10 yang sering dipakai layanan rawat jalan & inap.
 *
 * Seeder bersifat idempotent: kode yang sudah ada hanya diperbarui namanya.
 */
class Icd10Seeder extends Seeder
{
    public function run(): void
    {
        $codes = [
            // Infeksi & parasit
            ['A09', 'Diare dan gastroenteritis dengan dugaan infeksi', 'Infeksi & Parasit'],
            ['A15.0', 'Tuberkulosis paru, terkonfirmasi bakteriologis', 'Infeksi & Parasit'],
            ['A16.2', 'Tuberkulosis paru tanpa konfirmasi bakteriologis', 'Infeksi & Parasit'],
            ['A91', 'Demam berdarah dengue', 'Infeksi & Parasit'],
            ['B01.9', 'Cacar air tanpa komplikasi', 'Infeksi & Parasit'],
            ['B34.9', 'Infeksi virus, tidak spesifik', 'Infeksi & Parasit'],

            // Endokrin & metabolik
            ['E11.9', 'Diabetes melitus tipe 2 tanpa komplikasi', 'Endokrin & Metabolik'],
            ['E11.65', 'Diabetes melitus tipe 2 dengan hiperglikemia', 'Endokrin & Metabolik'],
            ['E78.5', 'Hiperlipidemia, tidak spesifik', 'Endokrin & Metabolik'],
            ['E86', 'Dehidrasi', 'Endokrin & Metabolik'],

            // Psikiatri
            ['F41.1', 'Gangguan cemas menyeluruh', 'Psikiatri'],
            ['F32.9', 'Episode depresi, tidak spesifik', 'Psikiatri'],

            // Saraf
            ['G40.9', 'Epilepsi, tidak spesifik', 'Saraf'],
            ['G43.9', 'Migrain, tidak spesifik', 'Saraf'],
            ['I63.9', 'Infark serebral, tidak spesifik', 'Saraf'],

            // Mata & telinga
            ['H10.9', 'Konjungtivitis, tidak spesifik', 'Mata & Telinga'],
            ['H66.9', 'Otitis media, tidak spesifik', 'Mata & Telinga'],

            // Sirkulasi
            ['I10', 'Hipertensi esensial (primer)', 'Sirkulasi'],
            ['I11.9', 'Penyakit jantung hipertensi tanpa gagal jantung', 'Sirkulasi'],
            ['I20.9', 'Angina pektoris, tidak spesifik', 'Sirkulasi'],
            ['I21.9', 'Infark miokard akut, tidak spesifik', 'Sirkulasi'],
            ['I25.10', 'Penyakit arteri koroner aterosklerotik', 'Sirkulasi'],
            ['I48', 'Fibrilasi dan flutter atrium', 'Sirkulasi'],
            ['I50.9', 'Gagal jantung, tidak spesifik', 'Sirkulasi'],

            // Pernapasan
            ['J00', 'Nasofaringitis akut (common cold)', 'Pernapasan'],
            ['J02.9', 'Faringitis akut, tidak spesifik', 'Pernapasan'],
            ['J03.9', 'Tonsilitis akut, tidak spesifik', 'Pernapasan'],
            ['J06.9', 'Infeksi saluran napas atas akut, tidak spesifik', 'Pernapasan'],
            ['J18.9', 'Pneumonia, tidak spesifik', 'Pernapasan'],
            ['J20.9', 'Bronkitis akut, tidak spesifik', 'Pernapasan'],
            ['J44.9', 'Penyakit paru obstruktif kronik, tidak spesifik', 'Pernapasan'],
            ['J45.9', 'Asma, tidak spesifik', 'Pernapasan'],

            // Pencernaan
            ['K21.0', 'Penyakit refluks gastroesofageal dengan esofagitis', 'Pencernaan'],
            ['K29.7', 'Gastritis, tidak spesifik', 'Pencernaan'],
            ['K30', 'Dispepsia', 'Pencernaan'],
            ['K35.8', 'Apendisitis akut, tidak spesifik', 'Pencernaan'],
            ['K80.2', 'Batu kandung empedu tanpa kolesistitis', 'Pencernaan'],

            // Kulit
            ['L50.9', 'Urtikaria, tidak spesifik', 'Kulit'],
            ['L30.9', 'Dermatitis, tidak spesifik', 'Kulit'],

            // Muskuloskeletal
            ['M54.5', 'Nyeri punggung bawah', 'Muskuloskeletal'],
            ['M79.1', 'Mialgia', 'Muskuloskeletal'],
            ['M17.9', 'Gonartrosis, tidak spesifik', 'Muskuloskeletal'],

            // Genitourinaria
            ['N39.0', 'Infeksi saluran kemih, lokasi tidak spesifik', 'Genitourinaria'],
            ['N18.9', 'Penyakit ginjal kronik, tidak spesifik', 'Genitourinaria'],
            ['N20.0', 'Batu ginjal', 'Genitourinaria'],

            // Kehamilan & persalinan
            ['O80', 'Persalinan tunggal spontan', 'Kehamilan & Persalinan'],
            ['O82', 'Persalinan sesar tunggal', 'Kehamilan & Persalinan'],
            ['Z34.9', 'Pemantauan kehamilan normal, tidak spesifik', 'Kehamilan & Persalinan'],

            // Gejala & tanda
            ['R05', 'Batuk', 'Gejala & Tanda'],
            ['R07.4', 'Nyeri dada, tidak spesifik', 'Gejala & Tanda'],
            ['R10.4', 'Nyeri perut, tidak spesifik', 'Gejala & Tanda'],
            ['R50.9', 'Demam, tidak spesifik', 'Gejala & Tanda'],
            ['R51', 'Sakit kepala', 'Gejala & Tanda'],
            ['R53', 'Malaise dan kelelahan', 'Gejala & Tanda'],

            // Cedera & keracunan
            ['S06.0', 'Gegar otak', 'Cedera & Keracunan'],
            ['S52.5', 'Fraktur ujung distal radius', 'Cedera & Keracunan'],
            ['T14.9', 'Cedera, tidak spesifik', 'Cedera & Keracunan'],

            // Faktor kesehatan
            ['Z00.0', 'Pemeriksaan kesehatan umum', 'Faktor Kesehatan'],
            ['Z76.0', 'Konsultasi ulang resep', 'Faktor Kesehatan'],
        ];

        foreach ($codes as [$code, $name, $category]) {
            Icd10Code::updateOrCreate(
                ['code' => $code],
                ['name' => $name, 'category' => $category],
            );
        }
    }
}
