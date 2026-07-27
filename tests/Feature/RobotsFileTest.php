<?php

namespace Tests\Feature;

use Tests\TestCase;

class RobotsFileTest extends TestCase
{
    private string $path;
    private ?string $backup = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->path = public_path('robots.txt');

        if (file_exists($this->path)) {
            $this->backup = file_get_contents($this->path);
        }
    }

    public function test_it_writes_a_closed_file_while_the_site_is_flagged_noindex(): void
    {
        config(['app.noindex' => true]);

        $this->artisan('robots:build')->assertSuccessful();

        $this->assertStringContainsString('Disallow: /', file_get_contents($this->path));
    }

    public function test_it_writes_the_sitemap_once_the_site_is_open(): void
    {
        config(['app.noindex' => false, 'app.available' => true]);

        $this->artisan('robots:build')->assertSuccessful();

        $contents = file_get_contents($this->path);

        $this->assertStringContainsString('Sitemap: ' . route('sitemap'), $contents);
        $this->assertStringContainsString('Disallow: /admin', $contents);
    }

    protected function tearDown(): void
    {
        if ($this->backup === null) {
            @unlink($this->path);
        } else {
            file_put_contents($this->path, $this->backup);
        }

        parent::tearDown();
    }
}
