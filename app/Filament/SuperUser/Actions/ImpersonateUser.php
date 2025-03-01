<?php

namespace App\Filament\SuperUser\Actions;

use STS\FilamentImpersonate\Concerns\Impersonates;
use STS\FilamentImpersonate\Pages\Actions\Impersonate;

/**
 * To Extend `STS\FilamentImpersonate\Pages\Actions\Impersonate`
 */
class ImpersonateUser extends Impersonate
{
    use Impersonates;

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->guard('web')
            ->redirectTo(route('filament.admin.tenant'))
            ->backTo(route('filament.superadmin.resources.users.index'))
            ->requiresConfirmation()
            ->modalDescription('Login as this user?')
            ->color('gray')
            ->record($this->getRecord());
    }
}
