<?php

namespace App\Http\Controllers;

use App\Models\Info;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class InfoController extends Controller
{
    /**
     * Hanya admin & ketua yang boleh upload/hapus.
     */
    private function guardUpload(): void
    {
        if (!in_array(auth()->user()?->role, ['admin', 'ketua'])) {
            abort(403, 'Tidak punya izin untuk mengelola Papan Informasi.');
        }
    }

    public function index()
    {
        $items = Info::with('uploadedBy:id,name,role')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($i) => [
                'id' => $i->id,
                'title' => $i->title,
                'description' => $i->description,
                'file_type' => $i->file_type,
                'file_path' => $i->file_path,
                'file_url' => Storage::url($i->file_path),
                'uploaded_by_name' => $i->uploadedBy?->name,
                'uploaded_by_role' => $i->uploadedBy?->role,
                'created_at' => $i->created_at?->translatedFormat('d M Y H:i'),
            ]);

        $canManage = in_array(auth()->user()?->role, ['admin', 'ketua']);

        return Inertia::render('Info/Index', [
            'items' => $items,
            'canManage' => $canManage,
        ]);
    }

    public function store(Request $request)
    {
        $this->guardUpload();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240', // 10 MB
        ]);

        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension());
        $fileType = in_array($ext, ['jpg', 'jpeg', 'png']) ? 'image' : 'pdf';

        $path = $file->store('info', 'public');

        Info::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'file_path' => $path,
            'file_type' => $fileType,
            'uploaded_by' => auth()->id(),
        ]);

        return back()->with('success', 'Informasi berhasil diunggah.');
    }

    public function destroy(Info $info)
    {
        $this->guardUpload();

        if ($info->file_path && Storage::disk('public')->exists($info->file_path)) {
            Storage::disk('public')->delete($info->file_path);
        }

        $info->delete();

        return back()->with('success', 'Informasi dihapus.');
    }
}
