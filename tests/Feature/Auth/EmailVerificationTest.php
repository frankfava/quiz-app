<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    #[Test]
    public function email_can_be_verified()
    {
        $context = $this->makeUserAndTenant()
            ->user(fn ($f) => $f->unverified())
            ->authenticate()
            ->create();

        $user = $context->user;
        $tenant = $context->tenant;

        if (! (new User) instanceof MustVerifyEmail) {
            $this->addToAssertionCount(1);

            return;
        }

        Event::fake([Verified::class]);

        $verificationUrl = Filament::getVerifyEmailUrl($user);

        $response = $this->get($verificationUrl);

        Event::assertDispatched(Verified::class);

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(Filament::getUrl());
    }

    #[Test]
    public function verification_email_is_sent_when_registered()
    {
        $user = $this->makeUserAndTenant()
            ->user(fn ($f) => $f->unverified())
            ->withoutTenant()
            ->create()
            ->user;

        if (! (new User) instanceof MustVerifyEmail) {
            $this->addToAssertionCount(1);

            return;
        }

        Notification::fake();

        event(new Registered($user));

        Notification::assertSentTo($user, VerifyEmail::class);
    }
}
