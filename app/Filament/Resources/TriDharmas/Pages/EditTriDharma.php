<?php

namespace App\Filament\Resources\TriDharmas\Pages;

use App\Filament\Resources\TriDharmas\TriDharmaResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class EditTriDharma extends EditRecord
{
    protected static string $resource = TriDharmaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Hapus Tri Dharma')
                ->visible(fn (): bool => Auth::user()?->can('delete', $this->getRecord()) ?? false),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            throw ValidationException::withMessages([
                'updated_by' => 'Sesi pengguna tidak valid.',
            ]);
        }

        if (! $user->canManageAllStudyPrograms()) {
            $user->loadMissing('studyProgram:id,faculty_id');

            if ($user->study_program_id === null || ! $user->studyProgram) {
                throw ValidationException::withMessages([
                    'study_program_id' => 'Akun Anda belum terhubung ke program studi.',
                ]);
            }

            $data['faculty_id'] = $user->studyProgram->faculty_id;
            $data['study_program_id'] = $user->study_program_id;
        }

        return $data;
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Berhasil')
            ->body('Tri Dharma berhasil diperbarui.');
    }
}
