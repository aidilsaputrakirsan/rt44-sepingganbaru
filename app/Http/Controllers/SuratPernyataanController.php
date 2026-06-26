<?php

namespace App\Http\Controllers;

use App\Models\SuratPernyataanTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class SuratPernyataanController extends Controller
{
    private const UPLOAD_DIR = 'surat-pernyataan';

    /**
     * Daftar blangko untuk ditampilkan (Ketua & homepage publik).
     */
    public static function items(): array
    {
        return SuratPernyataanTemplate::orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (SuratPernyataanTemplate $t) => [
                'id'        => $t->id,
                'judul'     => $t->judul,
                'deskripsi' => $t->deskripsi,
                'ext'       => $t->extension(),
                'available' => $t->fileExists(),
            ])
            ->all();
    }

    private function authorizeKetua(): void
    {
        if (auth()->user()?->role !== 'ketua') {
            abort(403);
        }
    }

    /** Simpan file upload ke public/surat-pernyataan, kembalikan nama file unik. */
    private function storeUpload($file, string $judul): string
    {
        $ext = strtolower($file->getClientOriginalExtension());
        $fileName = Str::slug($judul) . '-' . Str::random(6) . '.' . $ext;
        $file->move(public_path(self::UPLOAD_DIR), $fileName);

        return $fileName;
    }

    private function deleteFile(?string $fileName): void
    {
        if (!$fileName) {
            return;
        }
        $path = public_path(self::UPLOAD_DIR . DIRECTORY_SEPARATOR . $fileName);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    // ── Halaman Ketua ────────────────────────────────────────────
    public function index()
    {
        $this->authorizeKetua();

        return Inertia::render('Ketua/SuratPernyataan', [
            'items' => self::items(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeKetua();

        $validated = $request->validate([
            'judul'     => 'required|string|max:150',
            'deskripsi' => 'nullable|string|max:500',
            'file'      => 'required|file|mimes:doc,docx,pdf|max:10240', // 10 MB
        ]);

        $fileName = $this->storeUpload($request->file('file'), $validated['judul']);

        SuratPernyataanTemplate::create([
            'judul'      => $validated['judul'],
            'deskripsi'  => $validated['deskripsi'] ?? null,
            'file_name'  => $fileName,
            'sort_order' => (int) SuratPernyataanTemplate::max('sort_order') + 1,
        ]);

        return back()->with('success', 'Surat pernyataan berhasil ditambahkan.');
    }

    public function update(Request $request, SuratPernyataanTemplate $suratPernyataan)
    {
        $this->authorizeKetua();

        $validated = $request->validate([
            'judul'     => 'required|string|max:150',
            'deskripsi' => 'nullable|string|max:500',
            'file'      => 'nullable|file|mimes:doc,docx,pdf|max:10240',
        ]);

        $data = [
            'judul'     => $validated['judul'],
            'deskripsi' => $validated['deskripsi'] ?? null,
        ];

        if ($request->hasFile('file')) {
            $this->deleteFile($suratPernyataan->file_name);
            $data['file_name'] = $this->storeUpload($request->file('file'), $validated['judul']);
        }

        $suratPernyataan->update($data);

        return back()->with('success', 'Surat pernyataan berhasil diperbarui.');
    }

    public function destroy(SuratPernyataanTemplate $suratPernyataan)
    {
        $this->authorizeKetua();

        $this->deleteFile($suratPernyataan->file_name);
        $suratPernyataan->delete();

        return back()->with('success', 'Surat pernyataan dihapus.');
    }

    // ── Download ─────────────────────────────────────────────────
    /** Download dari halaman Ketua (perlu login & role ketua). */
    public function download(SuratPernyataanTemplate $suratPernyataan)
    {
        $this->authorizeKetua();

        return $this->serveFile($suratPernyataan);
    }

    /** Download publik dari homepage (tanpa login). */
    public function publicDownload(SuratPernyataanTemplate $suratPernyataan)
    {
        return $this->serveFile($suratPernyataan);
    }

    private function serveFile(SuratPernyataanTemplate $template)
    {
        if (!$template->fileExists()) {
            abort(404, 'File blangko belum tersedia.');
        }

        return response()->download(
            $template->filePath(),
            $template->judul . '.' . $template->extension()
        );
    }
}
