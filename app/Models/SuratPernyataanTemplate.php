<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuratPernyataanTemplate extends Model
{
    protected $fillable = [
        'judul',
        'deskripsi',
        'file_name',
        'sort_order',
    ];

    /** Path absolut file di public/surat-pernyataan/. */
    public function filePath(): string
    {
        return public_path('surat-pernyataan' . DIRECTORY_SEPARATOR . $this->file_name);
    }

    public function fileExists(): bool
    {
        return is_file($this->filePath());
    }

    /** Ekstensi file (docx/doc/pdf) lowercase. */
    public function extension(): string
    {
        return strtolower(pathinfo($this->file_name, PATHINFO_EXTENSION));
    }
}
