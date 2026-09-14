<?php

namespace Tests\Feature;

use App\Models\CommandLog;
use App\Models\Technology;
use App\Models\User;
use App\Notifications\Channels\TelegramChannel;
use App\Notifications\FetchReport;
use App\Support\FetchRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class FetchReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_good_night_is_one_telegram_message_and_no_mail(): void
    {
        $report = new FetchReport($this->log('success', ['new_offers' => 87]));

        $this->assertSame([TelegramChannel::class], $report->via(new User));
    }

    public function test_a_board_failing_quietly_adds_the_mail(): void
    {
        $report = new FetchReport($this->log('partial', [
            'new_offers'      => 40,
            'source_failures' => ['adzuna' => 12],
        ]));

        $this->assertSame([TelegramChannel::class, 'mail'], $report->via(new User), 'Telegram goes out before a mailer that might fail');
        $this->assertStringContainsString('Adzuna en 12 cerques', $report->toTelegram(new User));
    }

    /** Every board broken without an exception looks exactly like this. */
    public function test_a_night_with_nothing_new_adds_the_mail_even_without_errors(): void
    {
        $report = new FetchReport($this->log('success', ['new_offers' => 0]));

        $this->assertContains('mail', $report->via(new User));
        $this->assertStringContainsString('No ha entrat cap oferta nova', $report->toTelegram(new User));
    }

    public function test_the_message_says_what_the_night_brought(): void
    {
        $text = (new FetchReport($this->log('success', [
            'total_queries'        => 39,
            'new_offers'           => 1087,
            'total_offers'         => 7967,
            'new_companies'        => 12,
            'total_companies'      => 1767,
            'new_offers_by_source' => ['jooble' => 51, 'adzuna' => 29],
        ])))->toTelegram(new User);

        $this->assertStringContainsString('39 cerques, 0 fallides', $text);
        $this->assertStringContainsString('+1.087 ofertes noves (total 7.967)', $text);
        $this->assertStringContainsString('+12 empreses noves (total 1.767)', $text);
        $this->assertStringContainsString('Tecnoempleo 0', $text, 'a silent board must still be listed');
    }

    public function test_an_error_message_cannot_break_the_telegram_markup(): void
    {
        $log = $this->log('failed', ['new_offers' => 0]);
        $log->update(['error_message' => 'queue <b>down</b>']);

        $text = (new FetchReport($log))->toTelegram(new User);

        $this->assertStringContainsString('queue &lt;b&gt;down&lt;/b&gt;', $text);
        $this->assertStringContainsString('❌', $text);
    }

    public function test_board_failures_are_counted_per_batch_and_per_board(): void
    {
        Cache::flush();

        FetchRun::recordSourceFailure('batch-1', 'adzuna');
        FetchRun::recordSourceFailure('batch-1', 'adzuna');
        FetchRun::recordSourceFailure('batch-2', 'jooble');

        $this->assertSame(['adzuna' => 2], FetchRun::sourceFailures('batch-1'));
        $this->assertSame(['jooble' => 1], FetchRun::sourceFailures('batch-2'));
    }

    public function test_the_nightly_run_reports_to_the_admins(): void
    {
        [$admin, $user] = $this->adminAndUser();

        $this->artisan('jobs:fetch Laravel --pages=1 --report')->assertExitCode(0);

        Notification::assertSentTo($admin, FetchReport::class);
        Notification::assertNotSentTo($user, FetchReport::class);
        $this->assertArrayHasKey('new_offers_by_source', CommandLog::latest('id')->first()->stats);
    }

    /** What happened on production the first time: Resend was not installed. */
    public function test_a_broken_mailer_does_not_crash_the_fetch(): void
    {
        // No Notification::fake() here: the point is to reach a real mailer.
        Http::preventStrayRequests();
        Http::fake();
        Technology::create(['name' => 'Laravel', 'slug' => 'laravel']);
        User::factory()->create(['is_admin' => true, 'telegram_chat_id' => null]);

        config([
            'mail.default'         => 'broken',
            'mail.mailers.broken'  => ['transport' => 'does-not-exist'],
        ]);

        $this->artisan('jobs:fetch Laravel --pages=1 --report')
            ->expectsOutputToContain('Report not delivered')
            ->assertExitCode(0);

        $this->assertNotSame('running', CommandLog::latest('id')->first()->status, 'the run must be recorded before the report');
    }

    public function test_a_stale_config_cache_does_not_make_the_run_give_up_at_once(): void
    {
        $this->adminAndUser();
        config(['jobs.fetch_timeout_hours' => null]);

        $this->artisan('jobs:fetch Laravel --pages=1')
            ->doesntExpectOutputToContain('Gave up')
            ->assertExitCode(0);
    }

    public function test_a_manual_run_stays_quiet(): void
    {
        [$admin] = $this->adminAndUser();

        $this->artisan('jobs:fetch Laravel --pages=1')->assertExitCode(0);

        Notification::assertNothingSent();
    }

    private function adminAndUser(): array
    {
        Notification::fake();
        Http::preventStrayRequests();
        Http::fake();

        Technology::create(['name' => 'Laravel', 'slug' => 'laravel']);

        return [
            User::factory()->create(['is_admin' => true]),
            User::factory()->create(['is_admin' => false]),
        ];
    }

    private function log(string $status, array $stats): CommandLog
    {
        return CommandLog::create([
            'command'     => 'jobs:fetch',
            'status'      => $status,
            'started_at'  => now()->subMinutes(4),
            'finished_at' => now(),
            'stats'       => $stats,
        ]);
    }
}
