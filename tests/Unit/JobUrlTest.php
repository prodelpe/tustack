<?php

namespace Tests\Unit;

use App\Support\JobUrl;
use PHPUnit\Framework\TestCase;

class JobUrlTest extends TestCase
{
    public function test_tracking_parameters_are_dropped(): void
    {
        $this->assertSame(
            'https://es.jooble.org/away/-2850124486643505143',
            JobUrl::canonical('https://es.jooble.org/away/-2850124486643505143?p=1&pos=12&rgn=-1'),
        );
    }

    public function test_the_same_advert_seen_on_two_result_pages_gives_one_url(): void
    {
        $first  = JobUrl::canonical('https://es.jooble.org/away/123?p=1&pos=12');
        $second = JobUrl::canonical('https://es.jooble.org/away/123?p=9&pos=174');

        $this->assertSame($first, $second);
    }

    public function test_the_identifier_in_the_path_is_kept(): void
    {
        $url = 'https://www.tecnoempleo.com/developer-net/net-core/rf-b1651fe5829853dbf14d';

        $this->assertSame($url, JobUrl::canonical($url));
    }

    public function test_fragments_are_dropped(): void
    {
        $this->assertSame('https://example.test/job', JobUrl::canonical('https://example.test/job#apply'));
    }

    public function test_a_missing_url_stays_empty(): void
    {
        $this->assertSame('', JobUrl::canonical(null));
        $this->assertSame('', JobUrl::canonical('   '));
    }
}
