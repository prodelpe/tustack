<?php

namespace App\Console\Commands;

use App\Actions\DetectTechnologiesAction;
use App\Models\JobOffer;
use App\Models\Technology;
use Illuminate\Console\Command;

class RedetectTechnologies extends Command
{
    protected $signature = 'technologies:redetect
                            {--dry-run : Report what would change without touching anything}
                            {--prune : Also drop tags the current text no longer supports}';

    protected $description = 'Read every stored offer again and correct the technologies attached to it';

    public function handle(DetectTechnologiesAction $detect): int
    {
        $dryRun       = (bool) $this->option('dry-run');
        $prune        = (bool) $this->option('prune');
        $technologies = Technology::query()->get();
        $names        = $technologies->pluck('name', 'id');
        $ambiguous    = $technologies->where('ambiguous', true)->pluck('id');

        $added   = [];
        $removed = [];
        $emptied = 0;
        $total   = JobOffer::query()->count();

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        JobOffer::query()->with('technologies:id')->chunkById(500, function ($offers) use (
            $detect, $technologies, $ambiguous, $dryRun, $prune, &$added, &$removed, &$emptied, $bar
        ) {
            foreach ($offers as $offer) {
                $before = $offer->technologies->pluck('id')->sort()->values();
                $found  = $detect->handle($offer->title, $offer->description, $technologies)
                    ->pluck('id');

                // Boards rewrite an offer after we store it, often into a shorter
                // snippet, and the technologies were never read again. A tag whose
                // words are gone was still true when it was written, so without
                // --prune only the ones the rules now reject are dropped.
                $after = $prune
                    ? $found->sort()->values()
                    : $found->merge($before->reject(fn (int $id) => $ambiguous->contains($id)))
                        ->unique()->sort()->values();

                foreach ($after->diff($before) as $id) {
                    $added[$id] = ($added[$id] ?? 0) + 1;
                }

                foreach ($before->diff($after) as $id) {
                    $removed[$id] = ($removed[$id] ?? 0) + 1;
                }

                if ($after->isEmpty()) {
                    $emptied++;
                }

                if (! $dryRun && $before->all() !== $after->all()) {
                    $offer->technologies()->sync($after);
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        $this->report('Removed', $removed, $names, 'red');
        $this->report('Added', $added, $names, 'green');

        $this->newLine();
        $this->info(($dryRun ? 'Would leave ' : 'Left ') . $emptied . ' of ' . $total . ' offers with no technology at all.');

        if ($emptied > 0) {
            $this->line('<fg=gray>Those offers are kept: nothing is deleted here.</>');
        }

        return self::SUCCESS;
    }

    private function report(string $heading, array $counts, $names, string $colour): void
    {
        if ($counts === []) {
            $this->line($heading . ': nothing.');

            return;
        }

        arsort($counts);

        $this->line("<fg={$colour}>{$heading}</>");

        foreach ($counts as $id => $count) {
            $this->line('  ' . str_pad($names[$id] ?? (string) $id, 20) . $count);
        }
    }
}
