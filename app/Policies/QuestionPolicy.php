<?php

namespace App\Policies;

use App\Models\Question;
use App\Models\SuperUser;

class QuestionPolicy
{
    public function viewAny($user): bool
    {
        return true;
    }

    public function view($user, Question $question): bool
    {
        return $user instanceof SuperUser;
    }

    public function create($user): bool
    {
        return $user instanceof SuperUser;
    }

    public function update($user, Question $question): bool
    {
        return $user instanceof SuperUser;
    }

    public function delete($user, Question $question): bool
    {
        return $user instanceof SuperUser;
    }

    public function restore($user, Question $question): bool
    {
        return $user instanceof SuperUser;
    }

    public function forceDelete($user, Question $question): bool
    {
        return $user instanceof SuperUser;
    }

    public function attach($user): bool
    {
        return $user instanceof SuperUser;
    }

    public function detach($user, Question $question): bool
    {
        return $user instanceof SuperUser;
    }

    public function detachAny($user): bool
    {
        return $user instanceof SuperUser;
    }
}
