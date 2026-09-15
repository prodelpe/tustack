<?php

namespace Tests\Feature;

use App\Jobs\EnrichCompanyJob;
use App\Jobs\FetchJobOffersJob;
use Tests\TestCase;

class QueueTimeoutsTest extends TestCase
{
    /**
     * When a job may run longer than retry_after, the queue decides the worker
     * died and gives the same job to another one while the first is still busy.
     * That is how AWS and Python were fetched twice at once on 15 September.
     */
    public function test_the_queue_waits_longer_than_any_job_may_run(): void
    {
        $retryAfter = config('queue.connections.redis.retry_after');

        foreach ([new FetchJobOffersJob('Laravel'), new EnrichCompanyJob(1)] as $job) {
            $this->assertGreaterThan(
                $job->timeout,
                $retryAfter,
                class_basename($job) . ' may run longer than the queue waits for it'
            );
        }
    }
}
