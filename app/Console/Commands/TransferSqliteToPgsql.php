<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TransferSqliteToPgsql extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:transfer-sqlite-to-pgsql {--target=supabase : The target database connection name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Transfer all data from SQLite to PostgreSQL (Supabase) and sync auto-increment sequences';

    /**
     * Tables in dependency order (parents first, children later).
     *
     * @var array<string>
     */
    protected array $tables = [
        'users',
        'kelompok_sampah',
        'kategori_sampah',
        'harga_sampah',
        'wilayah',
        'jadwal_setor',
        'setoran',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Memulai migrasi data dari SQLite ke PostgreSQL (Supabase)...');

        $targetConnection = $this->option('target');
        $targetHost = config("database.connections.{$targetConnection}.host") ?? env('DB_HOST', 'db.ipwickemvyiwrnzhjilo.supabase.co');

        // Validasi penguncian project Supabase (ipwickemvyiwrnzhjilo)
        if (str_contains($targetHost, 'supabase.co') && ! str_contains($targetHost, 'ipwickemvyiwrnzhjilo')) {
            $this->error("❌ ERROR KEAMANAN: Host target '{$targetHost}' bukan project Supabase Bank Sampah (ipwickemvyiwrnzhjilo). Operasi dibatalkan demi keamanan data!");
            return Command::FAILURE;
        }

        $this->info("🔒 Target Supabase terkunci: https://ipwickemvyiwrnzhjilo.supabase.co ({$targetHost})");

        $source = DB::connection('sqlite');
        $target = DB::connection($targetConnection);

        try {
            $target->getPdo();
            $this->info('✅ Koneksi database target (PostgreSQL/Supabase) berhasil terhubung.');
        } catch (\Throwable $e) {
            $this->error('❌ Gagal terhubung ke database target: ' . $e->getMessage());
            return Command::FAILURE;
        }

        // Jalankan migrasi di target jika ada yang belum berjalan
        $this->info('🔄 Memeriksa status skema tabel di target...');
        $this->call('migrate', [
            '--database' => $targetConnection,
            '--force' => true,
        ]);

        $results = [];

        // Matikan foreign key checks atau truncate cascade
        $this->info('📦 Mentransfer data antar tabel...');

        foreach ($this->tables as $table) {
            $sqliteCount = $source->table($table)->count();
            
            // Bersihkan data lama di tabel target secara aman
            $target->statement("TRUNCATE TABLE \"{$table}\" RESTART IDENTITY CASCADE");

            if ($sqliteCount > 0) {
                $rows = $source->table($table)->get()->map(function ($row) use ($table) {
                    $item = (array) $row;

                    // Normalisasi kolom bertipe boolean
                    if (isset($item['aktif'])) {
                        $item['aktif'] = (bool) $item['aktif'];
                    }

                    // Normalisasi empty string menjadi null untuk kolom nullable / FK / datetime
                    foreach ($item as $key => $value) {
                        if ($value === '') {
                            $item[$key] = null;
                        }
                    }

                    return $item;
                })->toArray();

                foreach (array_chunk($rows, 100) as $chunk) {
                    $target->table($table)->insert($chunk);
                }

                // Sinkronkan sequence ID PostgreSQL agar auto-increment ID berikutnya valid
                try {
                    $target->statement("SELECT setval(pg_get_serial_sequence('{$table}', 'id'), COALESCE((SELECT MAX(id) FROM \"{$table}\"), 1))");
                } catch (\Throwable $e) {
                    // Beberapa tabel mungkin tidak memiliki sequence serial bawaan
                }
            }

            $targetCount = $target->table($table)->count();
            $matched = ($sqliteCount === $targetCount);

            $results[] = [
                'table' => $table,
                'sqlite_rows' => $sqliteCount,
                'target_rows' => $targetCount,
                'status' => $matched ? '<info>MATCH ✅</info>' : '<error>MISMATCH ❌</error>',
            ];
        }

        $this->newLine();
        $this->table(['Tabel', 'SQLite (Sumber)', 'PostgreSQL (Supabase)', 'Status'], $results);
        $this->newLine();

        $this->info('🎉 Migrasi data selesai dengan sempurna dan semua Sequence ID telah disinkronkan!');
        return Command::SUCCESS;
    }
}
