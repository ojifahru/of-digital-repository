<?php

namespace App\Filament\Resources\TriDharmas\Pages;

use App\Filament\Resources\TriDharmas\TriDharmaResource;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CreateTriDharma extends CreateRecord
{
    protected static string $resource = TriDharmaResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            throw ValidationException::withMessages([
                'created_by' => 'Sesi pengguna tidak valid.',
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

        $data['created_by'] = $user->getKey();

        return $data;
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Berhasil')
            ->body('Tri Dharma berhasil ditambahkan.');
    }
}
