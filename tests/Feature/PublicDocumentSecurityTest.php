<?php

use App\Models\Author;
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

test('published document pdf is served from the private disk through the controller', function (): void {
    $path = 'tri_dharmas/private-document.pdf';

    Storage::disk('local')->put($path, '%PDF-1.4 private');

    $document = createPublicDocumentSecurityRecord([
        'file_path' => $path,
        'title' => 'Dokumen Private',
    ]);

    $response = $this->get(route('public.repository.pdf', $document));

    $response->assertSuccessful();

    expect($response->headers->get('content-disposition'))
        ->toContain('inline')
        ->toContain('dokumen_private.pdf');
});

test('draft document pdf stays hidden even when the file exists', function (): void {
    $path = 'tri_dharmas/draft-document.pdf';

    Storage::disk('local')->put($path, '%PDF-1.4 draft');

    $document = createPublicDocumentSecurityRecord([
        'file_path' => $path,
        'status' => 'draft',
        'title' => 'Dokumen Draft',
    ]);

    $this->get(route('public.repository.pdf', $document))
        ->assertNotFound();
});

test('legacy public disk documents are still served through the controller', function (): void {
    $path = 'tri_dharmas/legacy-document.pdf';

    Storage::disk('public')->put($path, '%PDF-1.4 legacy');

    $document = createPublicDocumentSecurityRecord([
        'file_path' => $path,
        'title' => 'Dokumen Legacy',
    ]);

    $this->get(route('public.repository.pdf', $document))
        ->assertSuccessful();
});

test('download route increments the download count after resolving the stored file', function (): void {
    $path = 'tri_dharmas/download-document.pdf';

    Storage::disk('local')->put($path, '%PDF-1.4 download');

    $document = createPublicDocumentSecurityRecord([
        'download_count' => 7,
        'file_path' => $path,
        'title' => 'Dokumen Download',
    ]);

    $this->get(route('public.repository.download', $document))
        ->assertSuccessful();

    expect($document->refresh()->download_count)->toBe(8);
});

test('document abstract rich html is sanitized on the public page', function (): void {
    $path = 'tri_dharmas/sanitized-document.pdf';

    Storage::disk('local')->put($path, '%PDF-1.4 sanitized');

    $document = createPublicDocumentSecurityRecord([
        'abstract' => '<p>Abstrak terlihat.</p><script>alert("owned")</script>',
        'file_path' => $path,
        'title' => 'Dokumen Sanitized',
    ]);

    $this->get(route('public.repository.show', $document))
        ->assertSuccessful()
        ->assertSee('Abstrak terlihat.', false)
        ->assertDontSee('<script>alert', false);
});

test('author bio rich html is sanitized on the public page', function (): void {
    $author = Author::query()->create([
        'name' => 'Author Sanitized',
        'slug' => 'author-sanitized',
        'email' => 'author-sanitized@example.test',
        'bio' => '<p>Bio terlihat.</p><script>alert("owned")</script>',
    ]);

    $this->get(route('public.authors.show', $author))
        ->assertSuccessful()
        ->assertSee('Bio terlihat.', false)
        ->assertDontSee('<script>alert', false);
});

/**
 * @param  array<string, mixed>  $attributes
 */
function createPublicDocumentSecurityRecord(array $attributes = []): TriDharma
{
    $suffix = Str::lower(Str::random(8));

    $faculty = Faculty::query()->create([
        'name' => 'Fakultas Keamanan '.$suffix,
        'kode' => 'FK'.$suffix,
        'slug' => 'fakultas-keamanan-'.$suffix,
    ]);

    $degree = Degree::query()->firstOrCreate(
        ['code' => 'TestDegree'],
        [
            'name' => 'Test Degree',
            'slug_suffix' => 'td',
        ],
    );

    $programType = ProgramType::query()->firstOrCreate(
        ['code' => 'TestProgram'],
        ['name' => 'Test Program'],
    );

    $studyProgram = StudyProgram::query()->create([
        'name' => 'Program Keamanan '.$suffix,
        'faculty_id' => $faculty->getKey(),
        'kode' => 'PK'.$suffix,
        'degree_id' => $degree->getKey(),
        'program_type_id' => $programType->getKey(),
        'slug' => 'program-keamanan-'.$suffix,
    ]);

    $category = Categories::query()->create([
        'name' => 'Kategori Keamanan '.$suffix,
        'slug' => 'kategori-keamanan-'.$suffix,
    ]);

    $documentType = DocumentType::query()->create([
        'name' => 'Jenis Keamanan '.$suffix,
        'slug' => 'jenis-keamanan-'.$suffix,
    ]);

    $user = User::factory()->create([
        'study_program_id' => $studyProgram->getKey(),
    ]);

    return TriDharma::query()->create(array_merge([
        'title' => 'Dokumen Keamanan '.$suffix,
        'abstract' => '<p>Abstrak aman.</p>',
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
