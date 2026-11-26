<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertSee('name="rendered_at"', false);
        $response->assertSee('name="website"', false);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', $this->form());

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    /** 108 of the 109 accounts in production were bots filling the form. */
    public function test_a_form_with_the_hidden_field_filled_creates_nobody(): void
    {
        $response = $this->post('/register', $this->form(['website' => 'https://example.com']));

        $response->assertRedirect(route('home'));
        $this->assertGuest();
        $this->assertSame(0, User::count());
    }

    public function test_a_form_sent_the_moment_it_was_rendered_creates_nobody(): void
    {
        $response = $this->post('/register', $this->form(['rendered_at' => Crypt::encryptString((string) now()->timestamp)]));

        $response->assertSessionHasErrors('name');
        $this->assertGuest();
        $this->assertSame(0, User::count());
    }

    public function test_a_form_without_a_valid_render_time_creates_nobody(): void
    {
        $this->post('/register', $this->form(['rendered_at' => 'made-up']))->assertSessionHasErrors('name');
        $this->post('/register', collect($this->form())->except('rendered_at')->all())->assertSessionHasErrors('name');

        $this->assertSame(0, User::count());
    }

    private function form(array $overrides = []): array
    {
        return $overrides + [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'rendered_at' => Crypt::encryptString((string) now()->subSeconds(10)->timestamp),
        ];
    }
}
