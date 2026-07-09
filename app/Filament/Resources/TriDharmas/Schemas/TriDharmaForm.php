<?php

namespace App\Filament\Resources\TriDharmas\Schemas;

use App\Models\StudyProgram;
use App\Models\User;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TriDharmaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            Tabs::make('Tri Dharma')
                ->columnSpanFull()
                ->tabs([

                    /* =========================
                     * TAB 1 — INFORMASI
                     * ========================= */
                    Tab::make('Informasi Dokumen')
                        ->icon('heroicon-o-document-text')
                        ->schema([
                            TextInput::make('title')
                                ->label('Judul')
                                ->required()
                                ->maxLength(255),

                            RichEditor::make('abstract')
                                ->label('Abstrak')
                                ->columnSpanFull(),
                        ]),

                    /* =========================
                     * TAB 2 — KLASIFIKASI
                     * ========================= */
                    Tab::make('Klasifikasi')
                        ->icon('heroicon-o-tag')
                        ->schema([
                            Select::make('category_id')
                                ->label('Kategori Tri Dharma')
                                ->relationship(
                                    'category',
                                    'name',
                                    fn($query) => $query->whereNull('deleted_at')
                                )
                                ->searchable()
                                ->preload()
                                ->required(),

                            Select::make('document_type_id')
                                ->label('Jenis Dokumen')
                                ->relationship('documentType', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),
                        ]),

                    /* =========================
                     * TAB 3 — STRUKTUR & AUTHOR
                     * ========================= */
                    Tab::make('Struktur & Author')
                        ->icon('heroicon-o-users')
                        ->schema([
                            Select::make('faculty_id')
                                ->label('Fakultas')
                                ->relationship(
                                    'faculty',
                                    'name',
                                    fn(Builder $query): Builder => self::scopeFacultyQuery($query)
                                )
                                ->default(fn(): ?int => self::currentStudyProgram()?->faculty_id)
                                ->required()
                                ->reactive()
                                ->disabled(fn(): bool => self::isStudyProgramLocked())
                                ->dehydrated()
                                ->afterStateUpdated(fn(callable $set) => $set('study_program_id', null)),

                            Select::make('study_program_id')
                                ->label('Program Studi')
                                ->options(
                                    fn(callable $get) => self::studyProgramOptions($get('faculty_id'))
                                )
                                ->default(fn(): ?int => self::currentUser()?->study_program_id)
                                ->required()
                                ->disabled(fn(callable $get): bool => self::isStudyProgramLocked() || ! $get('faculty_id'))
                                ->dehydrated(),

                            Select::make('authors')
                                ->label('Author')
                                ->multiple()
                                ->relationship(
                                    'authors',
                                    'name',
                                    fn($query) => $query->whereNull('deleted_at')
                                )
                                ->searchable()
                                ->preload()
                                ->required(),
                        ]),

                    /* =========================
                     * TAB 4 — PUBLIKASI & FILE
                     * ========================= */
                    Tab::make('Publikasi & File')
                        ->icon('heroicon-o-cloud-arrow-up')
                        ->schema([
                            TextInput::make('publish_year')
                                ->label('Tahun Publikasi')
                                ->numeric()
                                ->minValue(1900)
                                ->maxValue(now()->year)
                                ->required(),

                            Select::make('status')
                                ->label('Status Publikasi')
                                ->options([
                                    'draft' => 'Draft',
                                    'published' => 'Published',
                                ])
                                ->default('draft')
                                ->required(),

                            FileUpload::make('file_path')
                                ->label('File Dokumen (PDF)')
                                ->disk('local')
                                ->directory('tri_dharmas')
                                ->visibility('private')
                                ->acceptedFileTypes(['application/pdf'])
                                ->maxSize(25600)
                                ->required()
                                ->columnSpanFull(),
                        ]),
                ]),
        ]);
    }

    private static function currentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }

    private static function currentStudyProgram(): ?StudyProgram
    {
        $user = self::currentUser();

        if (! $user instanceof User || $user->study_program_id === null) {
            return null;
        }

        $user->loadMissing('studyProgram:id,faculty_id');

        return $user->studyProgram;
    }

    private static function isStudyProgramLocked(): bool
    {
        $user = self::currentUser();

        return $user instanceof User && ! $user->canManageAllStudyPrograms();
    }

    private static function scopeFacultyQuery(Builder $query): Builder
    {
        $query->whereNull('deleted_at');
        $studyProgram = self::currentStudyProgram();

        if (! self::isStudyProgramLocked()) {
            return $query;
        }

        if (! $studyProgram instanceof StudyProgram) {
            return $query->whereKey([]);
        }

        return $query->whereKey($studyProgram->faculty_id);
    }

    /**
     * @return array<int, string>
     */
    private static function studyProgramOptions(mixed $facultyId): array
    {
        $query = StudyProgram::query()
            ->whereNull('deleted_at')
            ->orderBy('name');

        $user = self::currentUser();

        if ($user instanceof User && ! $user->canManageAllStudyPrograms()) {
            if ($user->study_program_id === null) {
                return [];
            }

            $query->whereKey($user->study_program_id);
        } elseif ($facultyId) {
            $query->where('faculty_id', $facultyId);
        } else {
            return [];
        }

        return $query->pluck('name', 'id')->all();
    }
}
