<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Models\JenisDokumen;
use App\Models\DokumenPendaftar;
use App\Models\Pendaftar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DokumenController extends Controller
{
    public function index()
    {
        $pendaftar = $this->getPendaftar();
        if (!$pendaftar) {
            return redirect()->route('peserta.biodata')
                             ->with('warning', 'Silakan lengkapi biodata terlebih dahulu.');
        }

        $jenisDokumens = JenisDokumen::all();
        
        // Map uploaded files
        $uploadedDocs = DokumenPendaftar::where('applicant_id', $pendaftar->id)
            ->get()
            ->keyBy('document_type_id');

        return view('peserta.dokumen.index', compact('pendaftar', 'jenisDokumens', 'uploadedDocs'));
    }

    public function store(Request $request)
    {
        $jenisDokumens = JenisDokumen::all();
        $pendaftar = $this->getPendaftar();

        // Dynamically build validation rules based on required documents
        $rules = [];
        $messages = [];
        foreach ($jenisDokumens as $jenis) {
            $inputName = 'dokumen_' . $jenis->id;
            $label = $jenis->name;
            
            // Check if already uploaded
            $exists = DokumenPendaftar::where('applicant_id', $pendaftar->id)
                ->where('document_type_id', $jenis->id)
                ->exists();

            // If it is mandatory and not uploaded yet, require it on submit
            if ($jenis->is_required && !$exists) {
                $rules[$inputName] = 'required|file|mimes:jpg,jpeg,png,pdf|mimetypes:image/jpeg,image/png,application/pdf|max:2048';
            } else {
                $rules[$inputName] = 'nullable|file|mimes:jpg,jpeg,png,pdf|mimetypes:image/jpeg,image/png,application/pdf|max:2048';
            }

            $messages["{$inputName}.required"] = "{$label} wajib diunggah.";
            $messages["{$inputName}.file"] = "{$label} harus berupa file.";
            $messages["{$inputName}.mimes"] = "{$label} harus berformat JPG, PNG, atau PDF.";
            $messages["{$inputName}.mimetypes"] = "{$label} harus benar-benar berupa file JPG, PNG, atau PDF.";
            $messages["{$inputName}.max"] = "{$label} terlalu besar. Ukuran maksimal 2 MB. Kompres file atau gunakan foto/scan yang lebih kecil.";
        }

        $request->validate($rules, $messages);

        // Save uploaded files
        foreach ($jenisDokumens as $jenis) {
            $inputName = 'dokumen_' . $jenis->id;
            if ($request->hasFile($inputName)) {
                $file = $request->file($inputName);
                
                // Construct beautiful file name: registration_number_document_name.ext
                $cleanDocName = str_replace([' ', '/', '\\', '(', ')'], '_', strtolower($jenis->name));
                $fileName = ($pendaftar->registration_number ?? 'draft_' . $pendaftar->id) . '_' . $cleanDocName . '.' . $file->getClientOriginalExtension();
                
                // Store in public upload folder
                $filePath = $file->storeAs('uploads/dokumen', $fileName, 'public');

                // Delete old file if exists to keep storage clean
                $oldDoc = DokumenPendaftar::where('applicant_id', $pendaftar->id)
                    ->where('document_type_id', $jenis->id)
                    ->first();
                if ($oldDoc && $oldDoc->file_path) {
                    Storage::disk('public')->delete($oldDoc->file_path);
                }

                DokumenPendaftar::updateOrCreate(
                    [
                        'applicant_id' => $pendaftar->id,
                        'document_type_id' => $jenis->id,
                    ],
                    [
                        'file_path' => $filePath,
                        'status' => 'pending',
                    ]
                );
            }
        }

        return $this->redirectAfterParticipantSave($request, 'peserta.review', 'Dokumen berhasil diunggah.', 'peserta.review');
    }

    private function getPendaftar(): ?Pendaftar
    {
        return Auth::user()->pendaftar;
    }
}
