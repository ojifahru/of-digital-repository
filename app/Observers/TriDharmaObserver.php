<?php

namespace App\Observers;

use App\Models\TriDharma;
use Illuminate\Support\Facades\Storage;

class TriDharmaObserver
{
    public function created(TriDharma $triDharma): void
    {
        $this->updateFileSize($triDharma);
    }

    public function updated(TriDharma $triDharma): void
    {
        // kalau file diganti
        if ($triDharma->wasChanged('file_path')) {
            $this->updateFileSize($triDharma);
        }
    }

    protected function updateFileSize(TriDharma $triDharma): void
    {
        $disk = $triDharma->documentDisk();

        if ($disk === null) {
            $triDharma->updateQuietly([
                'file_size' => null,
            ]);

            return;
        }

        $triDharma->updateQuietly([
            'file_size' => Storage::disk($disk)->size($triDharma->documentPath()),
        ]);
    }
}
