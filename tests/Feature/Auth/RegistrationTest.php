<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function testRegistrationScreenCanBeRendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function testNewUsersCanRegister(): void
    {
        Http::fake([
            config('api-server.base_url') . '/__/auth/register' => Http::response(
                [
                    'user' => [
                        'id' => Str::uuid()->toString(),
                        'name' => 'Test User',
                        'email' => 'test@example.com',
                    ],
                    'token' => Str::random(35),
                ],
                201
            ),
        ]);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(RouteServiceProvider::HOME);
    }
}
