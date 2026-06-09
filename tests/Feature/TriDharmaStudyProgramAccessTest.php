<?php

use App\Filament\Resources\TriDharmas\TriDharmaResource;
use App\Models\Categories;
use App\Models\DocumentType;
use App\Models\Faculty;
use App\Models\StudyProgram;
use App\Models\TriDharma;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    rebuildTriDharmaAccessTestSchema();

    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

test('admin only sees tri dharma records from their study program in filament resources', function (): void {
    $ownStudyProgram = createStudyProgramForTriDharmaAccess('informatika');
    $otherStudyProgram = createStudyProgramForTriDharmaAccess('manajemen');
    $admin = createUserWithTriDharmaAccess('admin', $ownStudyProgram);
    $uploader = User::factory()->create();

    $ownTriDharma = createTriDharmaForStudyProgram($ownStudyProgram, $uploader, 'Dokumen Prodi Sendiri');
    $otherTriDharma = createTriDharmaForStudyProgram($otherStudyProgram, $uploader, 'Dokumen Prodi Lain');

    $this->actingAs($admin);

    $visibleRecordIds = TriDharmaResource::getEloquentQuery()
        ->pluck('id')
        ->all();

    expect($visibleRecordIds)
        ->toContain($ownTriDharma->getKey())
        ->not->toContain($otherTriDharma->getKey());
});

test('admin and editor can only update tri dharma records from their study program', function (string $roleName): void {
    $ownStudyProgram = createStudyProgramForTriDharmaAccess($roleName.'-own');
    $otherStudyProgram = createStudyProgramForTriDharmaAccess($roleName.'-other');
    $user = createUserWithTriDharmaAccess($roleName, $ownStudyProgram);
    $uploader = User::factory()->create();

    $ownTriDharma = createTriDharmaForStudyProgram($ownStudyProgram, $uploader, 'Dokumen Milik '.$roleName);
    $otherTriDharma = createTriDharmaForStudyProgram($otherStudyProgram, $uploader, 'Dokumen Lain '.$roleName);

    expect($user->can('update', $ownTriDharma))->toBeTrue()
        ->and($user->can('update', $otherTriDharma))->toBeFalse();
})->with(['admin', 'editor']);

test('admin cannot open the edit page for another study program record directly', function (): void {
    $ownStudyProgram = createStudyProgramForTriDharmaAccess('admin-direct-own');
    $otherStudyProgram = createStudyProgramForTriDharmaAccess('admin-direct-other');
    $admin = createUserWithTriDharmaAccess('admin', $ownStudyProgram);
    $uploader = User::factory()->create();
    $otherTriDharma = createTriDharmaForStudyProgram($otherStudyProgram, $uploader, 'Dokumen URL Langsung');

    $this->actingAs($admin);

    $response = $this->get(TriDharmaResource::getUrl('edit', ['record' => $otherTriDharma]));

    expect(in_array($response->getStatusCode(), [403, 404], true))->toBeTrue();
});

test('super admin sees and can update all tri dharma records', function (): void {
    $firstStudyProgram = createStudyProgramForTriDharmaAccess('super-first');
    $secondStudyProgram = createStudyProgramForTriDharmaAccess('super-second');
    $superAdmin = createUserWithTriDharmaAccess('super_admin');
    $uploader = User::factory()->create();

    $firstTriDharma = createTriDharmaForStudyProgram($firstStudyProgram, $uploader, 'Dokumen Super Satu');
    $secondTriDharma = createTriDharmaForStudyProgram($secondStudyProgram, $uploader, 'Dokumen Super Dua');

    $this->actingAs($superAdmin);

    $visibleRecordIds = TriDharmaResource::getEloquentQuery()
        ->pluck('id')
        ->all();

    expect($visibleRecordIds)
        ->toContain($firstTriDharma->getKey())
        ->toContain($secondTriDharma->getKey())
        ->and($superAdmin->can('update', $firstTriDharma))->toBeTrue()
        ->and($superAdmin->can('update', $secondTriDharma))->toBeTrue();
});

function createUserWithTriDharmaAccess(string $roleName, ?StudyProgram $studyProgram = null): User
{
    $role = Role::query()->firstOrCreate([
        'name' => $roleName,
        'guard_name' => 'web',
    ]);

    if ($roleName !== 'super_admin') {
        $permissions = collect([
            'ViewAny:TriDharma',
            'View:TriDharma',
            'Create:TriDharma',
            'Update:TriDharma',
            'Delete:TriDharma',
            'Restore:TriDharma',
            'ForceDelete:TriDharma',
            'ForceDeleteAny:TriDharma',
            'RestoreAny:TriDharma',
            'Replicate:TriDharma',
            'Reorder:TriDharma',
        ])->map(
            fn (string $permission): Permission => Permission::query()->firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ])
        );

        $role->syncPermissions($permissions);
    }

    $factory = User::factory();

    if ($studyProgram instanceof StudyProgram) {
        $factory = $factory->forStudyProgram($studyProgram);
    }

    $user = $factory->create();

    $user->assignRole($role);

    return $user;
}

function rebuildTriDharmaAccessTestSchema(): void
{
    Schema::disableForeignKeyConstraints();

    foreach ([
        'activity_log',
        'author_tri_dharma',
        'tri_dharmas',
        'authors',
        'categories',
        'document_types',
        'model_has_permissions',
        'model_has_roles',
        'role_has_permissions',
        'permissions',
        'roles',
        'users',
        'study_programs',
        'faculties',
    ] as $table) {
        Schema::dropIfExists($table);
    }

    Schema::enableForeignKeyConstraints();

    Schema::create('faculties', function (Blueprint $table): void {
        $table->id();
        $table->string('name', 100)->nullable()->unique();
        $table->string('slug')->unique();
        $table->string('kode', 10)->nullable()->unique();
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('study_programs', function (Blueprint $table): void {
        $table->id();
        $table->string('name', 100)->nullable();
        $table->string('slug')->unique();
        $table->foreignId('faculty_id');
        $table->string('kode', 10)->nullable()->unique();
        $table->foreignId('degree_id')->nullable();
        $table->foreignId('program_type_id')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('users', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->timestamp('email_verified_at')->nullable();
        $table->foreignId('study_program_id')->nullable();
        $table->string('password');
        $table->rememberToken();
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('permissions', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('guard_name');
        $table->timestamps();
        $table->unique(['name', 'guard_name']);
    });

    Schema::create('roles', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('guard_name');
        $table->timestamps();
        $table->unique(['name', 'guard_name']);
    });

    Schema::create('model_has_permissions', function (Blueprint $table): void {
        $table->foreignId('permission_id');
        $table->string('model_type');
        $table->unsignedBigInteger('model_id');
        $table->index(['model_id', 'model_type']);
    });

    Schema::create('model_has_roles', function (Blueprint $table): void {
        $table->foreignId('role_id');
        $table->string('model_type');
        $table->unsignedBigInteger('model_id');
        $table->index(['model_id', 'model_type']);
    });

    Schema::create('role_has_permissions', function (Blueprint $table): void {
        $table->foreignId('permission_id');
        $table->foreignId('role_id');
        $table->primary(['permission_id', 'role_id']);
    });

    Schema::create('categories', function (Blueprint $table): void {
        $table->id();
        $table->string('name')->unique();
        $table->string('slug')->unique();
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('document_types', function (Blueprint $table): void {
        $table->id();
        $table->string('name')->unique();
        $table->string('slug')->unique();
        $table->timestamps();
    });

    Schema::create('authors', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('slug')->nullable()->unique();
        $table->string('email')->nullable();
        $table->text('bio')->nullable();
        $table->string('image_url', 500)->nullable();
        $table->string('identifier')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('tri_dharmas', function (Blueprint $table): void {
        $table->id();
        $table->string('title');
        $table->string('slug')->nullable()->unique();
        $table->text('abstract')->nullable();
        $table->foreignId('category_id');
        $table->foreignId('document_type_id');
        $table->foreignId('faculty_id');
        $table->foreignId('study_program_id');
        $table->year('publish_year');
        $table->enum('status', ['draft', 'published'])->default('draft');
        $table->string('file_path');
        $table->unsignedInteger('file_size')->nullable();
        $table->unsignedInteger('download_count')->default(0);
        $table->foreignId('created_by');
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('author_tri_dharma', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('author_id');
        $table->foreignId('tri_dharma_id');
        $table->timestamps();
    });

    Schema::create('activity_log', function (Blueprint $table): void {
        $table->bigIncrements('id');
        $table->string('log_name')->nullable();
        $table->text('description');
        $table->string('subject_type')->nullable();
        $table->unsignedBigInteger('subject_id')->nullable();
        $table->string('event')->nullable();
        $table->string('causer_type')->nullable();
        $table->unsignedBigInteger('causer_id')->nullable();
        $table->json('properties')->nullable();
        $table->uuid('batch_uuid')->nullable();
        $table->timestamps();
        $table->index('log_name');
    });
}

function createStudyProgramForTriDharmaAccess(string $suffix): StudyProgram
{
    $slug = Str::slug($suffix);

    $faculty = Faculty::query()->create([
        'name' => 'Fakultas '.$suffix,
        'slug' => 'fakultas-'.$slug,
        'kode' => Str::upper(Str::limit(md5('faculty-'.$suffix), 8, '')),
    ]);

    return StudyProgram::query()->create([
        'name' => 'Program Studi '.$suffix,
        'slug' => 'program-studi-'.$slug,
        'faculty_id' => $faculty->getKey(),
        'kode' => Str::upper(Str::limit(md5('study-program-'.$suffix), 10, '')),
    ]);
}

function createTriDharmaForStudyProgram(StudyProgram $studyProgram, User $createdBy, string $title): TriDharma
{
    $slug = Str::slug($title);

    $category = Categories::query()->firstOrCreate(
        ['slug' => 'kategori-'.$slug],
        ['name' => 'Kategori '.$title],
    );

    $documentType = DocumentType::query()->firstOrCreate(
        ['slug' => 'jenis-'.$slug],
        ['name' => 'Jenis '.$title],
    );

    return TriDharma::query()->create([
        'title' => $title,
        'abstract' => 'Abstrak '.$title,
        'category_id' => $category->getKey(),
        'document_type_id' => $documentType->getKey(),
        'faculty_id' => $studyProgram->faculty_id,
        'study_program_id' => $studyProgram->getKey(),
        'publish_year' => 2026,
        'status' => 'draft',
        'file_path' => 'tri_dharmas/'.Str::slug($title).'.pdf',
        'created_by' => $createdBy->getKey(),
    ]);
}
