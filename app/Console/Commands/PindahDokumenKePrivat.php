<?php

namespace App\Console\Commands;

use App\Models\ResidentIdCard;
use App\Models\ResidentProfile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Sekali jalan setelah deploy: pindahkan file KK/KTP lama dari disk 'public'
 * (bisa dibuka siapa pun lewat /storage/...) ke disk privat 'local'.
 * Path di database tidak berubah (relatif terhadap root disk), jadi cukup pindah file.
 * Aman diulang: file yang sudah ada di disk privat dilewati.
 */
class PindahDokumenKePrivat extends Command
{
    protected $signature = 'dokumen:pindah-ke-privat {--dry-run : Hanya tampilkan rencana, tanpa memindahkan}';

    protected $description = 'Pindahkan file KK/KTP warga dari disk public ke disk privat';

    public function handle(): int
    {
        $paths = ResidentProfile::whereNotNull('kk_path')->pluck('kk_path')
            ->merge(ResidentIdCard::whereNotNull('file_path')->pluck('file_path'))
            ->filter()->unique();

        $public = Storage::disk('public');
        $privat = Storage::disk('local');
        $hitung = ['dipindah' => 0, 'sudah_privat' => 0, 'tidak_ada' => 0];

        foreach ($paths as $path) {
            if ($privat->exists($path)) {
                $hitung['sudah_privat']++;
                // Sisa salinan publik (mis. perintah terhenti di tengah) tetap dihapus.
                if (! $this->option('dry-run') && $public->exists($path)) {
                    $public->delete($path);
                }

                continue;
            }
            if (! $public->exists($path)) {
                $hitung['tidak_ada']++;
                $this->warn("File tidak ditemukan: {$path}");

                continue;
            }
            if (! $this->option('dry-run')) {
                $privat->writeStream($path, $public->readStream($path));
                if ($privat->size($path) === $public->size($path)) {
                    $public->delete($path);
                } else {
                    $this->error("Ukuran tidak cocok, file publik TIDAK dihapus: {$path}");

                    continue;
                }
            }
            $hitung['dipindah']++;
        }

        $this->table(['Dipindah', 'Sudah privat', 'Tidak ditemukan'], [array_values($hitung)]);
        if ($this->option('dry-run')) {
            $this->info('Dry run — belum ada file yang dipindah.');
        }

        return self::SUCCESS;
    }
}
