<?php

use App\Models\Categories;
use App\Models\Degree;
use App\Models\DocumentType;
use App\Models\Faculty;
use App\Models\ProgramType;
use App\Models\StudyProgram;
use App\Models\TriDharma;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('local');
    Storage::fake('public');
});

test('dry run reports public documents without copying files or updating database rows', function (): void {
    $path = 'tri_dharmas/dry-run.pdf';

    $document = createDocumentForPublicFileMigration([
        'file_path' => $path,
        'file_size' => null,
    ]);

    Storage::disk('public')->put($path, '%PDF-1.4 dry run');

    $this->artisan('documents:migrate-public-files --dry-run')
        ->assertSuccessful();

    Storage::disk('local')->assertMissing($path);

    expect($document->refresh()->file_size)->toBeNull();
});

test('it copies legacy public document files to the private local disk', function (): void {
    $path = 'tri_dharmas/legacy.pdf';
    $contents = '%PDF-1.4 copied';

    $document = createDocumentForPublicFileMigration([
        'file_path' => $path,
        'file_size' => null,
    ]);

    Storage::disk('public')->put($path, $contents);

    $this->artisan('documents:migrate-public-files')
        ->assertSuccessful();

    Storage::disk('local')->assertExists($path);
    Storage::disk('public')->assertExists($path);

    expect(Storage::disk('local')->get($path))->toBe($contents)
        ->and($document->refresh()->file_size)->toBe(strlen($contents));
});

test('it may delete the public file after a successful copy', function (): void {
    $path = 'tri_dharmas/delete-after-copy.pdf';

    createDocumentForPublicFileMigration([
        'file_path' => $path,
    ]);

    Storage::disk('public')->put($path, '%PDF-1.4 delete after copy');

    $this->artisan('documents:migrate-public-files --delete-public-after-copy')
        ->assertSuccessful();

    Storage::disk('local')->assertExists($path);
    Storage::disk('public')->assertMissing($path);
});

test('it reports missing public files without failing the whole migration', function (): void {
    $path = 'tri_dharmas/missing.pdf';

    $document = createDocumentForPublicFileMigration([
        'file_path' => $path,
        'file_size' => null,
    ]);

    $this->artisan('documents:migrate-public-files')
        ->assertSuccessful();

    Storage::disk('local')->assertMissing($path);

    expect($document->refresh()->file_size)->toBeNull();
});

test('it fails for unsafe stored paths', function (): void {
    $document = createDocumentForPublicFileMigration();
    $document->updateQuietly([
        'file_path' => '../secret.pdf',
    ]);

    $this->artisan('documents:migrate-public-files')
        ->assertFailed();
});

/**
 * @param  array<string, mixed>  $attributes
 */
function createDocumentForPublicFileMigration(array $attributes = []): TriDharma
{
    $suffix = Str::lower(Str::random(8));

    $faculty = Faculty::query()->create([
        'name' => 'Fakultas Migrasi '.$suffix,
        'kode' => 'FM'.$suffix,
        'slug' => 'fakultas-migrasi-'.$suffix,
    ]);

    $degree = Degree::query()->firstOrCreate(
        ['code' => 'MigrationDegree'],
        [
            'name' => 'Migration Degree',
            'slug_suffix' => 'md',
        ],
    );

    $programType = ProgramType::query()->firstOrCreate(
        ['code' => 'MigrationProgram'],
        ['name' => 'Migration Program'],
    );

    $studyProgram = StudyProgram::query()->create([
        'name' => 'Program Migrasi '.$suffix,
        'faculty_id' => $faculty->getKey(),
        'kode' => 'PM'.$suffix,
        'degree_id' => $degree->getKey(),
        'program_type_id' => $programType->getKey(),
        'slug' => 'program-migrasi-'.$suffix,
    ]);

    $category = Categories::query()->create([
        'name' => 'Kategori Migrasi '.$suffix,
        'slug' => 'kategori-migrasi-'.$suffix,
    ]);

    $documentType = DocumentType::query()->create([
        'name' => 'Jenis Migrasi '.$suffix,
        'slug' => 'jenis-migrasi-'.$suffix,
    ]);

    $user = User::factory()->create([
        'study_program_id' => $studyProgram->getKey(),
    ]);

    return TriDharma::query()->create(array_merge([
        'title' => 'Dokumen Migrasi '.$suffix,
        'abstract' => '<p>Abstrak migrasi.</p>',
        'category_id' => $category->getKey(),
        'document_type_id' => $documentType->getKey(),
        'faculty_id' => $faculty->getKey(),
        'study_program_id' => $studyProgram->getKey(),
        'publish_year' => 2025,
        'status' => 'published',
        'file_path' => 'tri_dharmas/document-'.$suffix.'.pdf',
        'file_size' => null,
        'download_count' => 0,
        'created_by' => $user->getKey(),
    ], $attributes));
}
