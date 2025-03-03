<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\SuperUser;

class CategoryPolicy
{
    public function viewAny($user): bool
    {
        return $user instanceof SuperUser;
    }

    public function view($user, Category $category): bool
    {
        return $user instanceof SuperUser;
    }

    public function create($user): bool
    {
        return $user instanceof SuperUser;
    }

    public function update($user, Category $category): bool
    {
        return $user instanceof SuperUser;
    }

    public function delete($user, Category $category): bool
    {
        return $user instanceof SuperUser;
    }

    public function restore($user, Category $category): bool
    {
        return $user instanceof SuperUser;
    }

    public function forceDelete($user, Category $category): bool
    {
        return $user instanceof SuperUser;
    }
}
