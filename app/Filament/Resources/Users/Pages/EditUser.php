<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    public function canView(): bool
    {
        $user = Auth::user();

        if (! $user?->can('Update:User')) {
            return false;
        }

        if ($user instanceof User && $user->hasRole('admin') && ! $user->hasRole('super_admin')) {
            $record = $this->getRecord();

            if (! $record) {
                return false;
            }

            if ($record->getKey() === $user->getKey()) {
                return true;
            }

            return $record->hasRole('editor')
                && ! $record->hasAnyRole(['admin', 'super_admin'])
                && $record->study_program_id === $user->study_program_id;
        }

        return true;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Hapus Pengguna')
                ->visible(fn () => Auth::user()?->can('Delete:User') ?? false),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = Auth::user();

        if ($user instanceof User && $user->hasRole('admin') && ! $user->hasRole('super_admin')) {
            if ($user->study_program_id === null) {
                throw ValidationException::withMessages([
                    'study_program_id' => 'Akun admin Anda belum terhubung ke program studi.',
                ]);
            }

            if ($this->getRecord()->getKey() !== $user->getKey()) {
                $editorRoleId = Role::query()->where('name', 'editor')->value('id');

                if (! $editorRoleId) {
                    throw ValidationException::withMessages([
                        'roles' => 'Role editor belum tersedia. Silakan buat role editor terlebih dahulu.',
                    ]);
                }

                $data['roles'] = [$editorRoleId];
            }

            $data['study_program_id'] = $user->study_program_id;
        }

        $this->ensureScopedRoleHasStudyProgram($data);

        return $data;
    }

    private function ensureScopedRoleHasStudyProgram(array $data): void
    {
        $roleIds = collect($data['roles'] ?? [])
            ->filter(fn (mixed $role): bool => filled($role))
            ->map(fn (mixed $role): int => (int) $role)
            ->all();

        if ($roleIds === []) {
            return;
        }

        $requiresStudyProgram = Role::query()
            ->whereKey($roleIds)
            ->whereIn('name', ['admin', 'editor'])
            ->exists();

        if ($requiresStudyProgram && blank($data['study_program_id'] ?? null)) {
            throw ValidationException::withMessages([
                'study_program_id' => 'Program studi wajib diisi untuk role admin dan editor.',
            ]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Berhasil')
            ->body('Pengguna berhasil diperbarui.');
    }
}
