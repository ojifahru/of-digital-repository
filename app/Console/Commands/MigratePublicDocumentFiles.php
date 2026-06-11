<?php

namespace App\Console\Commands;

use App\Models\TriDharma;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigratePublicDocumentFiles extends Command
{
    protected $signature = 'documents:migrate-public-files
        {--dry-run : Tampilkan dokumen yang akan dicopy tanpa mengubah storage atau database}
        {--delete-public-after-copy : Hapus file public setelah file private berhasil tersedia}
        {--limit= : Batasi jumlah record yang discan}
        {--chunk=100 : Jumlah record per batch}';

    protected $description = 'Copy legacy Tri Dharma document files from the public disk to the private local disk.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $deletePublicAfterCopy = (bool) $this->option('delete-public-after-copy');
        $chunkSize = $this->positiveIntegerOption('chunk');
        $limit = $this->nullablePositiveIntegerOption('limit');

        if ($chunkSize === null || $limit === false) {
            return self::FAILURE;
        }

        $stats = [
            'scanned' => 0,
            'copied' => 0,
            'would_copy' => 0,
            'skipped' => 0,
            'missing' => 0,
            'failed' => 0,
            'deleted' => 0,
            'would_delete' => 0,
        ];

        $query = TriDharma::query()
            ->select(['id', 'title', 'file_path', 'file_size'])
            ->whereNotNull('file_path')
            ->where('file_path', '<>', '')
            ->orderBy('id');

        foreach ($query->lazyById($chunkSize) as $document) {
            if (is_int($limit) && $stats['scanned'] >= $limit) {
                break;
            }

            $stats['scanned']++;
            $this->migrateDocument($document, $dryRun, $deletePublicAfterCopy, $stats);
        }

        $this->newLine();
        $this->info('Migrasi file dokumen selesai.');
        $this->line('Scanned: '.$stats['scanned']);
        $this->line('Copied: '.$stats['copied']);
        $this->line('Would copy: '.$stats['would_copy']);
        $this->line('Skipped: '.$stats['skipped']);
        $this->line('Missing: '.$stats['missing']);
        $this->line('Deleted public: '.$stats['deleted']);
        $this->line('Would delete public: '.$stats['would_delete']);
        $this->line('Failed: '.$stats['failed']);

        return $stats['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @param  array{scanned: int, copied: int, would_copy: int, skipped: int, missing: int, failed: int, deleted: int, would_delete: int}  $stats
     */
    private function migrateDocument(TriDharma $document, bool $dryRun, bool $deletePublicAfterCopy, array &$stats): void
    {
        $path = trim((string) $document->file_path);

        if ($this->isUnsafePath($path)) {
            $stats['failed']++;
            $this->error("Path tidak aman untuk dokumen #{$document->getKey()}: {$path}");

            return;
        }

        $privateExists = Storage::disk('local')->exists($path);
        $publicExists = Storage::disk('public')->exists($path);

        if ($privateExists) {
            $stats['skipped']++;
            $this->updateFileSize($document, $path, $dryRun);
            $this->deletePublicFileIfRequested($path, $dryRun, $deletePublicAfterCopy, $publicExists, $stats);

            return;
        }

        if (! $publicExists) {
            $stats['missing']++;
            $this->warn("File public tidak ditemukan untuk dokumen #{$document->getKey()}: {$path}");

            return;
        }

        if ($dryRun) {
            $stats['would_copy']++;
            $this->line("Would copy dokumen #{$document->getKey()}: {$path}");

            return;
        }

        if (! $this->copyPublicFileToPrivateDisk($path)) {
            $stats['failed']++;
            $this->error("Gagal copy dokumen #{$document->getKey()}: {$path}");

            return;
        }

        if (! $this->fileSizesMatch($path)) {
            $stats['failed']++;
            $this->error("Ukuran file tidak cocok setelah copy dokumen #{$document->getKey()}: {$path}");

            return;
        }

        $stats['copied']++;
        $this->updateFileSize($document, $path, dryRun: false);
        $this->deletePublicFileIfRequested($path, $dryRun, $deletePublicAfterCopy, $publicExists, $stats);
    }

    private function copyPublicFileToPrivateDisk(string $path): bool
    {
        $stream = Storage::disk('public')->readStream($path);

        if ($stream === false) {
            return false;
        }

        try {
            return Storage::disk('local')->put($path, $stream);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    private function fileSizesMatch(string $path): bool
    {
        return Storage::disk('public')->size($path) === Storage::disk('local')->size($path);
    }

    private function updateFileSize(TriDharma $document, string $path, bool $dryRun): void
    {
        if ($dryRun) {
            return;
        }

        $document->updateQuietly([
            'file_size' => Storage::disk('local')->size($path),
        ]);
    }

    /**
     * @param  array{scanned: int, copied: int, would_copy: int, skipped: int, missing: int, failed: int, deleted: int, would_delete: int}  $stats
     */
    private function deletePublicFileIfRequested(
        string $path,
        bool $dryRun,
        bool $deletePublicAfterCopy,
        bool $publicExists,
        array &$stats
    ): void {
        if (! $deletePublicAfterCopy || ! $publicExists) {
            return;
        }

        if ($dryRun) {
            $stats['would_delete']++;

            return;
        }

        if (Storage::disk('public')->delete($path)) {
            $stats['deleted']++;

            return;
        }

        $stats['failed']++;
        $this->error("Gagal menghapus file public: {$path}");
    }

    private function isUnsafePath(string $path): bool
    {
        return $path === ''
            || str_starts_with($path, '/')
            || str_contains($path, '..')
            || str_contains($path, '\\');
    }

    private function positiveIntegerOption(string $option): ?int
    {
        $value = $this->option($option);

        if (! is_numeric($value) || (int) $value < 1) {
            $this->error("Option --{$option} harus berupa integer positif.");

            return null;
        }

        return (int) $value;
    }

    private function nullablePositiveIntegerOption(string $option): int|false|null
    {
        $value = $this->option($option);

        if ($value === null) {
            return null;
        }

        if (! is_numeric($value) || (int) $value < 1) {
            $this->error("Option --{$option} harus berupa integer positif.");

            return false;
        }

        return (int) $value;
    }
}
