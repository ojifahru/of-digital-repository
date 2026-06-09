<?php

namespace App\Filament\Resources\Users\Tables;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        $canAdminManageRecord = function ($user, $record): bool {
            if ($record->getKey() === $user->getKey()) {
                return true;
            }

            return $record->hasRole('editor')
                && ! $record->hasAnyRole(['admin', 'super_admin'])
                && $user->study_program_id !== null
                && $record->study_program_id === $user->study_program_id;
        };

        return $table
            ->columns([

                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->description(fn ($record) => $record->email),

                TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge()
                    ->separator(', ')
                    ->colors([
                        'primary',
                        'success' => 'admin',
                        'secondary' => 'user',
                    ]),

                TextColumn::make('studyProgram.name')
                    ->label('Program Studi')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),
            ])

            ->recordUrl(function ($record) use ($canAdminManageRecord): ?string {
                $user = Auth::user();

                if (! $user?->can('Update:User')) {
                    return null;
                }

                if ($user->hasRole('admin') && ! $user->hasRole('super_admin')) {
                    return $canAdminManageRecord($user, $record)
                        ? UserResource::getUrl('edit', ['record' => $record])
                        : null;
                }

                return UserResource::getUrl('edit', ['record' => $record]);
            })

            ->recordActions([
                EditAction::make()
                    ->label('ubah')
                    ->visible(
                        function ($record) use ($canAdminManageRecord): bool {
                            $user = Auth::user();

                            if (! $user?->can('Update:User')) {
                                return false;
                            }

                            if ($user->hasRole('admin') && ! $user->hasRole('super_admin')) {
                                return $canAdminManageRecord($user, $record);
                            }

                            return true;
                        }
                    ),
                RestoreAction::make()
                    ->label('pulihkan')
                    ->visible(
                        function ($record) use ($canAdminManageRecord): bool {
                            $user = Auth::user();

                            if (is_null($record->deleted_at)) {
                                return false;
                            }

                            if (! $user?->can('Restore:User')) {
                                return false;
                            }

                            if ($user->hasRole('admin') && ! $user->hasRole('super_admin')) {
                                return $canAdminManageRecord($user, $record);
                            }

                            return true;
                        }
                    ),
                DeleteAction::make()
                    ->label('hapus')
                    ->visible(
                        function ($record) use ($canAdminManageRecord): bool {
                            $user = Auth::user();

                            if (! is_null($record->deleted_at)) {
                                return false;
                            }

                            if (! $user?->can('Delete:User')) {
                                return false;
                            }

                            if ($user->hasRole('admin') && ! $user->hasRole('super_admin')) {
                                return $canAdminManageRecord($user, $record);
                            }

                            return true;
                        }
                    ),
                ForceDeleteAction::make()
                    ->label('hapus permanen')
                    ->visible(
                        function ($record) use ($canAdminManageRecord): bool {
                            $user = Auth::user();

                            if (is_null($record->deleted_at)) {
                                return false;
                            }

                            if (! $user?->can('ForceDelete:User')) {
                                return false;
                            }

                            if ($user->hasRole('admin') && ! $user->hasRole('super_admin')) {
                                return $canAdminManageRecord($user, $record);
                            }

                            return true;
                        }
                    ),
            ])

            ->toolbarActions([])

            ->defaultSort('name');
    }
}
