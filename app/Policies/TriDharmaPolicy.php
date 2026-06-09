<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TriDharma;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TriDharmaPolicy
{
    use HandlesAuthorization;

    public function before(User $authUser, string $ability): ?bool
    {
        if ($authUser->canManageAllStudyPrograms()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $authUser): bool
    {
        return $authUser->can('ViewAny:TriDharma')
            && $authUser->study_program_id !== null;
    }

    public function view(User $authUser, TriDharma $triDharma): bool
    {
        return $authUser->can('View:TriDharma')
            && $authUser->canAccessStudyProgram($triDharma->study_program_id);
    }

    public function create(User $authUser): bool
    {
        return $authUser->can('Create:TriDharma')
            && $authUser->study_program_id !== null;
    }

    public function update(User $authUser, TriDharma $triDharma): bool
    {
        return $authUser->can('Update:TriDharma')
            && $authUser->canAccessStudyProgram($triDharma->study_program_id);
    }

    public function delete(User $authUser, TriDharma $triDharma): bool
    {
        return $authUser->can('Delete:TriDharma')
            && $authUser->canAccessStudyProgram($triDharma->study_program_id);
    }

    public function restore(User $authUser, TriDharma $triDharma): bool
    {
        return $authUser->can('Restore:TriDharma')
            && $authUser->canAccessStudyProgram($triDharma->study_program_id);
    }

    public function forceDelete(User $authUser, TriDharma $triDharma): bool
    {
        return $authUser->can('ForceDelete:TriDharma')
            && $authUser->canAccessStudyProgram($triDharma->study_program_id);
    }

    public function forceDeleteAny(User $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:TriDharma')
            && $authUser->study_program_id !== null;
    }

    public function restoreAny(User $authUser): bool
    {
        return $authUser->can('RestoreAny:TriDharma')
            && $authUser->study_program_id !== null;
    }

    public function replicate(User $authUser, TriDharma $triDharma): bool
    {
        return $authUser->can('Replicate:TriDharma')
            && $authUser->canAccessStudyProgram($triDharma->study_program_id);
    }

    public function reorder(User $authUser): bool
    {
        return $authUser->can('Reorder:TriDharma')
            && $authUser->study_program_id !== null;
    }
}
