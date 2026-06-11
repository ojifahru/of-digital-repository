<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\TriDharma;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentDownloadController extends Controller
{
    public function __invoke(TriDharma $document): StreamedResponse
    {
        if ($document->status !== 'published') {
            abort(404);
        }

        $path = $document->documentPath();
        if ($path === '') {
            abort(404);
        }

        $disk = $document->documentDisk();
        if ($disk === null) {
            abort(404);
        }

        $document->increment('download_count');

        return Storage::disk($disk)->download($path, $document->documentDownloadName());
    }
}
