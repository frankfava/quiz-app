<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccessTokenTest extends TestCase
{
    use WithFaker;

    #[Test]
    public function user_with_correct_token_can_access_api_on_sanctum_api_gaurd()
    {
        $context = $this->makeUserAndTenant()
            ->authenticateWithToken()
            ->create();

        $this->getJson(route('api.test.ping'))->assertSeeText('pong');
    }

    #[Test]
    public function user_with_incorrect_token_cannot_access_api()
    {
        $token = $this->makeUserAndTenant()
            ->create()
            ->user
            ->createToken('testToken');

        $token->plainTextToken = 'wrongToken';

        $this->withToken($token->plainTextToken);

        $this->getJson(route('api.test.ping'))
            ->assertUnauthorized()
            ->assertDontSeeText('pong');
    }
}
