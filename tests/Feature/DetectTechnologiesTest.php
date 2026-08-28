<?php

namespace Tests\Feature;

use App\Actions\DetectTechnologiesAction;
use App\Models\Technology;
use Database\Seeders\TechnologySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DetectTechnologiesTest extends TestCase
{
    use RefreshDatabase;

    private DetectTechnologiesAction $detect;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TechnologySeeder::class);

        $this->detect = app(DetectTechnologiesAction::class);
    }

    public function test_an_unambiguous_name_counts_wherever_it_reads(): void
    {
        $this->assertDetects('PostgreSQL', 'Backend Engineer', 'We store everything in PostgreSQL.');
    }

    public function test_a_tag_never_glues_two_technologies_together(): void
    {
        $this->assertDetects('AWS', 'Platform Engineer', '<ul><li>AWS</li><li>Docker</li></ul>');
        $this->assertDetects('Docker', 'Platform Engineer', '<ul><li>AWS</li><li>Docker</li></ul>');
    }

    public function test_go_to_market_is_not_the_language(): void
    {
        $this->assertDoesNotDetect('Go', 'Product Manager', 'You will own the go-to-market strategy.');
    }

    public function test_go_live_is_not_the_language(): void
    {
        $this->assertDoesNotDetect('Go', 'Project Manager', 'Owning timelines, cutover and go-live schedules.');
    }

    public function test_a_dash_the_board_typed_oddly_is_still_read_as_a_dash(): void
    {
        $this->assertDoesNotDetect('Go', 'Director', 'Serve as the <b>go&#8209;</b>to person for the region.');
    }

    public function test_a_game_called_go_is_not_the_language(): void
    {
        $this->assertDoesNotDetect('Go', '2D Artist - Monopoly GO!', 'Join the art team.');
    }

    public function test_the_language_counts_inside_a_list_of_technologies(): void
    {
        $this->assertDetects('Go', 'Software Engineer III', 'Languages including c/c++, python, go, java.');
    }

    public function test_the_language_counts_when_the_title_names_the_trade(): void
    {
        $this->assertDetects('Go', 'Desarrollador Go (100% remoto)', 'Únete a nuestro equipo.');
    }

    public function test_a_title_naming_the_trade_is_still_refused_a_blocked_phrase(): void
    {
        $this->assertDoesNotDetect('Go', 'Staff Engineer - Monopoly GO!', 'Build the game.');
    }

    public function test_a_blocked_phrase_does_not_swallow_a_longer_word(): void
    {
        $this->assertDetects('Go', 'Go Backend Engineer', 'Build services for us.');
    }

    public function test_an_alias_is_specific_enough_on_its_own(): void
    {
        $this->assertDetects('Go', 'Backend Developer', 'We are looking for golang experience.');
    }

    public function test_american_express_is_not_the_framework(): void
    {
        $this->assertDoesNotDetect('Express', 'Customer Service Agent', 'Working for American Express in Madrid.');
    }

    public function test_express_counts_beside_node(): void
    {
        $this->assertDetects('Express', 'Backend Developer', 'Our stack is node.js with express and mongodb.');
    }

    public function test_a_singer_is_not_a_language(): void
    {
        $this->assertDoesNotDetect('Swift', 'Talent Manager', 'Clients like Taylor Swift and Cara Delevingne.');
    }

    public function test_the_banking_network_is_not_a_language(): void
    {
        $this->assertDoesNotDetect('Swift', 'Operaciones Financieras', 'Liquidación de valores mediante Iberclear / SWIFT.');
    }

    public function test_swift_counts_beside_ios(): void
    {
        $this->assertDetects('Swift', 'Mobile Developer', 'Experiencia en ios, swift, objective-c y mvvm.');
    }

    private function assertDetects(string $name, string $title, string $description): void
    {
        $this->assertTrue(
            $this->detected($title, $description)->contains($name),
            $name . ' should have been detected in: ' . $title
        );
    }

    private function assertDoesNotDetect(string $name, string $title, string $description): void
    {
        $this->assertFalse(
            $this->detected($title, $description)->contains($name),
            $name . ' should not have been detected in: ' . $title
        );
    }

    private function detected(string $title, string $description)
    {
        return $this->detect
            ->handle($title, $description, Technology::all())
            ->pluck('name');
    }
}
