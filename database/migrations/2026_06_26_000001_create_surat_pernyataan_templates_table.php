<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surat_pernyataan_templates', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->string('file_name'); // nama file di public/surat-pernyataan/
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Migrasikan blangko yang sudah ada (sebelumnya hard-coded di controller)
        // supaya tidak hilang. Cari file aktual (.docx / .doc) di folder.
        $existing = [
            'belum-pernah-menikah' => [
                'Surat Pernyataan Belum Pernah Menikah',
                'Pernyataan masih berstatus perjaka/perawan/janda/duda & belum terikat perkawinan. Disertai 2 saksi.',
            ],
            'persetujuan-menikah' => [
                'Surat Pernyataan Persetujuan Menikah',
                'Pernyataan belum menikah beserta data calon pasangan dan persetujuan ayah & ibu kandung.',
            ],
            'ditinggalkan-istri' => [
                'Surat Pernyataan Ditinggalkan Istri',
                'Pernyataan suami bahwa telah ditinggalkan istri sejak tanggal tertentu dan tidak dinafkahi.',
            ],
            'ditinggalkan-suami' => [
                'Surat Pernyataan Ditinggalkan Suami',
                'Pernyataan istri bahwa telah ditinggalkan suami sejak tanggal tertentu dan tidak dinafkahi.',
            ],
            'perbedaan-data' => [
                'Surat Pernyataan Perbedaan Data',
                'Pernyataan perbedaan nama/tanggal lahir di beberapa dokumen yang merujuk pada orang yang sama.',
            ],
            'penghasilan' => [
                'Surat Pernyataan Penghasilan',
                'Pernyataan pekerjaan dan besaran penghasilan per bulan.',
            ],
            'domisili-sementara' => [
                'Surat Pernyataan Domisili',
                'Pernyataan domisili tempat tinggal di wilayah Kel. Sepinggan Baru beserta status rumah (sendiri/kontrak/sewa).',
            ],
        ];

        $dir = public_path('surat-pernyataan');
        $order = 0;
        foreach ($existing as $slug => [$judul, $deskripsi]) {
            $fileName = null;
            foreach (['docx', 'doc'] as $ext) {
                if (is_file($dir . DIRECTORY_SEPARATOR . $slug . '.' . $ext)) {
                    $fileName = $slug . '.' . $ext;
                    break;
                }
            }
            if (!$fileName) {
                continue; // file tidak ada, lewati
            }

            DB::table('surat_pernyataan_templates')->insert([
                'judul'      => $judul,
                'deskripsi'  => $deskripsi,
                'file_name'  => $fileName,
                'sort_order' => $order++,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('surat_pernyataan_templates');
    }
};
