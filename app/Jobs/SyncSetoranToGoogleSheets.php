<?php

namespace App\Jobs;

use App\Models\Setoran;
use App\Services\GoogleSheetsSync;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncSetoranToGoogleSheets implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Jumlah percobaan maksimal.
     */
    public int $tries = 2;

    /**
     * Timeout job dalam detik.
     */
    public int $timeout = 15;

    /**
     * @param  array<int>  $setoranIds
     */
    public function __construct(
        public array $setoranIds
    ) {}

    public function handle(GoogleSheetsSync $sync): void
    {
        if (! $sync->isConfigured() || empty($this->setoranIds)) {
            return;
        }

        try {
            $setorans = Setoran::with(['user', 'kategori', 'dicatatOleh'])
                ->whereIn('id', $this->setoranIds)
                ->orderBy('id')
                ->get();

            if ($setorans->isNotEmpty()) {
                $sync->sync($setorans);
            }
        } catch (Throwable $e) {
            Log::error('SyncSetoranToGoogleSheets Job Error: ' . $e->getMessage());
        }
    }
}
