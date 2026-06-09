<?php

namespace App\Filament\Resources\TriDharmas\Tables;

use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TriDharmasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->limit(50) // tampilkan max 50 karakter
                    ->tooltip(fn ($record) => $record->title),

                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->badge()
                    ->sortable(),

                TextColumn::make('authors.name')
                    ->label('Author')
                    ->listWithLineBreaks()
                    ->limitList(3)
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('documentType.name')
                    ->label('Jenis Dokumen')
                    ->sortable(),

                TextColumn::make('faculty.name')
                    ->label('Fakultas')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('studyProgram.name')
                    ->label('Program Studi')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('publish_year')
                    ->label('Tahun')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'gray' => 'draft',
                        'success' => 'published',
                    ]),
            ])

            ->filters([
                SelectFilter::make('authors')
                    ->label('Author')
                    ->relationship('authors', 'name'),

                SelectFilter::make('category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name'),

                SelectFilter::make('document_type_id')
                    ->label('Jenis Dokumen')
                    ->relationship('documentType', 'name'),

                SelectFilter::make('faculty_id')
                    ->label('Fakultas')
                    ->relationship(
                        'faculty',
                        'name',
                        fn (Builder $query): Builder => self::scopeFacultyQuery($query),
                    )
                    ->visible(fn (): bool => Auth::user()?->hasRole('super_admin') ?? false),

                SelectFilter::make('study_program_id')
                    ->label('Program Studi')
                    ->relationship(
                        'studyProgram',
                        'name',
                        fn (Builder $query): Builder => self::scopeStudyProgramQuery($query),
                    )
                    ->visible(fn (): bool => Auth::user()?->hasRole('super_admin') ?? false),
            ])

            ->recordActions([
                EditAction::make()
                    ->label('Ubah')
                    ->visible(
                        fn ($record): bool => Auth::user()?->can('update', $record) ?? false
                    ),
                RestoreAction::make()
                    ->label('Pulihkan')
                    ->visible(
                        fn ($record): bool => ! is_null($record->deleted_at)
                            && (Auth::user()?->can('restore', $record) ?? false)
                    ),
                DeleteAction::make()
                    ->label('Hapus')
                    ->visible(
                        fn ($record): bool => is_null($record->deleted_at)
                            && (Auth::user()?->can('delete', $record) ?? false)
                    ),
                ForceDeleteAction::make()
                    ->label('Hapus Permanen')
                    ->visible(
                        fn ($record): bool => ! is_null($record->deleted_at)
                            && (Auth::user()?->can('forceDelete', $record) ?? false)
                    ),
            ])

            ->toolbarActions([])

            ->defaultSort('created_at', 'desc');
    }

    private static function scopeFacultyQuery(Builder $query): Builder
    {
        $query->whereNull('deleted_at');
        $user = Auth::user();

        if (! $user instanceof User || $user->canManageAllStudyPrograms()) {
            return $query;
        }

        if ($user->study_program_id === null) {
            return $query->whereKey([]);
        }

        $user->loadMissing('studyProgram:id,faculty_id');

        if ($user->studyProgram?->faculty_id === null) {
            return $query->whereKey([]);
        }

        return $query->whereKey($user->studyProgram->faculty_id);
    }

    private static function scopeStudyProgramQuery(Builder $query): Builder
    {
        $query->whereNull('deleted_at');
        $user = Auth::user();

        if (! $user instanceof User || $user->canManageAllStudyPrograms()) {
            return $query;
        }

        if ($user->study_program_id === null) {
            return $query->whereKey([]);
        }

        return $query->whereKey($user->study_program_id);
    }
}
