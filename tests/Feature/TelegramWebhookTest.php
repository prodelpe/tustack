<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelegramWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET_HEADER = 'X-Telegram-Bot-Api-Secret-Token';

    public function test_without_a_configured_secret_any_request_is_accepted(): void
    {
        config(['telegram.bots.tustack_bot.webhook_secret' => null]);

        $this->postJson(route('telegram.webhook'), [])->assertOk();
    }

    public function test_with_a_configured_secret_a_request_without_it_is_rejected(): void
    {
        config(['telegram.bots.tustack_bot.webhook_secret' => 'right-secret']);

        $this->postJson(route('telegram.webhook'), [])->assertForbidden();
    }

    public function test_with_a_configured_secret_a_wrong_secret_is_rejected(): void
    {
        config(['telegram.bots.tustack_bot.webhook_secret' => 'right-secret']);

        $this->postJson(route('telegram.webhook'), [], [self::SECRET_HEADER => 'wrong-secret'])->assertForbidden();
    }

    public function test_with_a_configured_secret_the_right_secret_is_accepted(): void
    {
        config(['telegram.bots.tustack_bot.webhook_secret' => 'right-secret']);

        $user = User::factory()->create(['telegram_connect_token' => 'token']);

        $this->postJson(
            route('telegram.webhook'),
            ['message' => ['text' => '/start unknown-token', 'chat' => ['id' => 1]]],
            [self::SECRET_HEADER => 'right-secret'],
        )->assertOk();

        $this->assertSame('token', $user->fresh()->telegram_connect_token);
    }
}
