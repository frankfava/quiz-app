<?php

namespace App\Policies;

use App\Models\Quiz;
use App\Models\SuperUser;
use App\Models\User;

class QuizPolicy
{
    public function viewAny($user): bool
    {
        return $user instanceof SuperUser;
    }

    public function view($user, Quiz $quiz): bool
    {
        return $user instanceof SuperUser || ($user instanceof User && $user->canAccessTenant());
    }

    public function create($user): bool
    {
        return $user instanceof SuperUser || $user instanceof User;
    }

    public function update($user, Quiz $quiz): bool
    {
        return $user instanceof SuperUser || ($user instanceof User && $user->canAccessTenant());
    }

    public function delete($user, Quiz $quiz): bool
    {
        return $user instanceof SuperUser || ($user instanceof User && $user->canAccessTenant());
    }

    public function restore($user, Quiz $quiz): bool
    {
        return $user instanceof SuperUser;
    }

    public function forceDelete($user, Quiz $quiz): bool
    {
        return $user instanceof SuperUser;
    }
}
