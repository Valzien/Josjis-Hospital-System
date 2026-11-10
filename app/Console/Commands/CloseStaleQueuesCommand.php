<?php

namespace App\Console\Commands;

use App\Services\AuditLogger;
use App\Services\QueueService;
use Illuminate\Console\Command;

class CloseStaleQueuesCommand extends Command
{
    protected $signature = 'queue:close-stale';

    protected $description = 'Menutup antrean lampau yang masih menggantung (Daily Closing)';

    public function handle(QueueService $queues, AuditLogger $audit): int
    {
        $this->info('Memeriksa antrean lampau yang belum terlayani...');

        $count = $queues->closeStaleQueues();

        if ($count > 0) {
            $audit->updated('queue', 'Queue', 0, "CLI: Menutup {$count} antrean lampau (Daily Closing).");
            $this->info("Berhasil menutup {$count} antrean lampau.");
        } else {
            $this->info('Tidak ada antrean lampau yang menggantung.');
        }

        return self::SUCCESS;
    }
}

