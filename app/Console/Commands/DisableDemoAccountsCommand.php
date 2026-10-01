<?php

namespace App\Console\Commands;

use App\Enums\ActiveStatus;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Menonaktifkan akun demo JOSJIS pada database yang sudah terlanjur ter-seed.
 *
 * Perintah ini tidak menghapus data, hanya menonaktifkan lima akun demo dan
 * mengunci kembali dengan sandi acak. Girasi data tetap dapat dilakukan lewat
 * admin/pengguna.
 */
class DisableDemoAccountsCommand extends Command
{
    protected $signature = 'jhs:demo-off
                            {--list : Tampilkan daftar akun demo tanpa mengubah apa pun}
                            {--purge : Hapus permanen akun demo (hanya bila tidak ada data klinis terkait)}';

    protected $description = 'Matikan akun demo (admin/resepsionis/dokter/apoteker/pasien @josjis.test)';

    public function handle(): int
    {
        $emails = array_values(array_map(
            static fn (array $account): string => $account['email'],
            config('jhs.demo_accounts'),
        ));

        $demoUsers = User::query()->withTrashed()->whereIn('email', $emails)->get();

        if ($demoUsers->isEmpty()) {
            $this->info('Tidak ada akun demo di database ini.');

            return self::SUCCESS;
        }

        $this->table(
            ['Email', 'Peran', 'Status'],
            $demoUsers->map(fn (User $user): array => [
                $user->email,
                $user->roleLabel(),
                $user->trashed() ? 'dihapus' : $user->status->label(),
            ])->all(),
        );

        if ($this->option('list')) {
            return self::SUCCESS;
        }

        if (! $this->confirm('Nonaktifkan akun-akun di atas?', true)) {
            $this->info('Dibatalkan.');

            return self::SUCCESS;
        }

        if ($this->option('purge')) {
            return $this->purge($demoUsers);
        }

        foreach ($demoUsers as $user) {
            $user->forceFill([
                'status' => ActiveStatus::Inactive->value,
                'password' => bcrypt(Str::password(32)),
            ])->save();
        }

        $this->info(sprintf('%d akun demo dinonaktifkan dan sandinya diacak ulang.', $demoUsers->count()));
        $this->line('  Gunakan <info>jhs:demo-off --list</info> sewaktu-waktu untuk melihat daftar akun demo.');

        return self::SUCCESS;
    }

    /** @param  Collection<int, User>  $demoUsers */
    private function purge(Collection $demoUsers): int
    {
        $demoUsers->each(fn (User $user) => $user->forceDelete());

        $this->info(sprintf('%d akun demo dihapus permanen.', $demoUsers->count()));

        return self::SUCCESS;
    }
}
