<?php

namespace App\Http\Controllers;

use App\Models\LegalitasDocument;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class LegalitasController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if ($user && $user->role === 'kemitraan') {
            $profile = \Illuminate\Support\Facades\DB::table('kemitraan_profiles')->where('user_id', $user->id)->first();
            if (!$profile || $profile->status_akun !== 'Aktif') {
                abort(403, 'Akses ditolak. Status kemitraan Anda belum aktif.');
            }
        }

        $documents = LegalitasDocument::latest()->get();
        return Inertia::render('Kemitraan/Legalitas/Index', [
            'documents' => $documents
        ]);
    }

    public function store(Request $request)
    {
        if (!$request->user()->hasRole('admin')) {
            abort(403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'file' => 'required|file|mimes:pdf,doc,docx,jpg,png,jpeg|max:10240', // 10MB
        ]);

        $filePath = $request->file('file')->store('legalitas', 'public');

        LegalitasDocument::create([
            'title' => $request->title,
            'description' => $request->description,
            'file_path' => '/storage/' . $filePath,
        ]);

        return back()->with('success', 'Dokumen legalitas berhasil diunggah.');
    }

    public function destroy(Request $request, $id)
    {
        if (!$request->user()->hasRole('admin')) {
            abort(403);
        }

        $document = LegalitasDocument::findOrFail($id);

        $path = str_replace('/storage/', '', $document->file_path);
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        $document->delete();

        return back()->with('success', 'Dokumen legalitas berhasil dihapus.');
    }
}
