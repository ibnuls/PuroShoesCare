<?php

namespace App\Services;

use App\Models\MediaAsset;
use App\Models\StaffUser;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class MediaStorage
{
    private array $written = [];

    public function atomic(callable $callback): mixed
    {
        $this->written = [];
        try {
            return DB::transaction($callback);
        } catch (\Throwable $e) {
            foreach ($this->written as [$disk,$path]) {
                Storage::disk($disk)->delete($path);
            }
            throw $e;
        } finally {
            $this->written = [];
        }
    }

    public function store(UploadedFile $file, ?StaffUser $actor = null, bool $public = false): MediaAsset
    {
        Validator::make(['image' => $file], ['image' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120'])->validate();
        $disk = $public ? 'public' : 'local';
        $path = $file->store('media/'.now()->format('Y/m'), $disk);
        if (! $path) {
            throw ValidationException::withMessages(['image' => 'Berkas belum tersimpan. Coba lagi.']);
        }
        $this->written[] = [$disk, $path];

        return MediaAsset::create(['storage_key' => $path, 'mime_type' => $file->getMimeType(), 'byte_size' => $file->getSize(), 'visibility' => $public ? 'PUBLIC' : 'PRIVATE', 'uploaded_by' => $actor?->id, 'original_name' => mb_substr(basename($file->getClientOriginalName()), 0, 255)]);
    }
}
