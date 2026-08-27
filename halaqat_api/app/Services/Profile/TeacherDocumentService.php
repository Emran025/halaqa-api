<?php

namespace App\Services\Profile;

use App\Models\TeacherDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TeacherDocumentService
{
    public function create(User $teacher, array $data, ?UploadedFile $file = null): TeacherDocument
    {
        return DB::transaction(function () use ($teacher, $data, $file): TeacherDocument {
            $storageDisk = null;
            $storagePath = null;
            $mimeType = null;
            $fileSize = null;

            if ($file !== null) {
                $storageDisk = 'local';
                $storagePath = $file->storeAs(
                    'teacher-documents/'.$teacher->id,
                    Str::uuid()->toString().'.'.$file->extension(),
                    $storageDisk,
                );
                $mimeType = $file->getClientMimeType();
                $fileSize = $file->getSize();
            }

            return TeacherDocument::create([
                'teacher_id' => $teacher->id,
                'name' => $data['name'],
                'certificate_type' => $data['certificate_type'],
                'certificate_type_other' => $data['certificate_type_other'] ?? null,
                'riwayah' => $data['riwayah'] ?? null,
                'issuing_place' => $data['issuing_place'] ?? null,
                'issuing_date' => $data['issuing_date'] ?? null,
                'storage_disk' => $storageDisk,
                'storage_path' => $storagePath,
                'mime_type' => $mimeType,
                'file_size_bytes' => $fileSize,
            ]);
        });
    }

    public function delete(User $teacher, TeacherDocument $document): void
    {
        abort_unless((string) $document->teacher_id === (string) $teacher->id, 403);

        DB::transaction(function () use ($document): void {
            if ($document->storage_disk !== null && $document->storage_path !== null) {
                Storage::disk($document->storage_disk)->delete($document->storage_path);
            }
            $document->delete();
        });
    }
}
