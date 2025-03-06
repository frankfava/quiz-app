<?php

namespace App\Policies;

use App\Models\QuizSubmission;
use App\Models\SuperUser;

class QuizSubmissionPolicy
{
    public function viewAny($user): bool
    {
        return true;
    }

    public function view($user, QuizSubmission $submission): bool
    {
        return $user instanceof SuperUser;
    }

    public function create($user): bool
    {
        return $user instanceof SuperUser;
    }

    public function update($user, QuizSubmission $submission): bool
    {
        return $user instanceof SuperUser;
    }

    public function delete($user, QuizSubmission $submission): bool
    {
        return $user instanceof SuperUser;
    }

    public function restore($user, QuizSubmission $submission): bool
    {
        return $user instanceof SuperUser;
    }

    public function forceDelete($user, QuizSubmission $submission): bool
    {
        return $user instanceof SuperUser;
    }
}
