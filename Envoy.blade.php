{{--
    Deploys production from this machine: vendor/bin/envoy run deploy

    Everything runs as tustack, the user the site and Horizon already run as,
    so every file keeps the right owner and nothing needs a chown afterwards.
    tustack reads the repository through a read-only deploy key.

    Each task starts with set -e and Envoy stops at the first one that fails,
    so a half-finished deploy never goes on to restart the workers.

    Needs the prodelpe host in ~/.ssh/config and this machine's key in
    /home/tustack/.ssh/authorized_keys. On a new computer, add its key there.
--}}

@servers(['production' => 'tustack@prodelpe'])

@setup
    $app = '/home/tustack/htdocs/tustack.es';
@endsetup

@story('deploy')
    pull
    dependencies
    assets
    migrate
    configuration
    workers
    check
@endstory

@task('pull', ['on' => 'production'])
    set -e
    cd {{ $app }}
    {{-- --ff-only: a change made by hand on the server stops the deploy instead of being merged into it. --}}
    git pull --ff-only
    git log --oneline -1
@endtask

@task('dependencies', ['on' => 'production'])
    set -e
    cd {{ $app }}
    composer install --no-dev --optimize-autoloader --no-interaction
@endtask

@task('assets', ['on' => 'production'])
    set -e
    cd {{ $app }}
    {{-- public/build is not in git, so every deploy compiles it. --}}
    npm ci --no-audit --no-fund
    npm run build
@endtask

@task('migrate', ['on' => 'production'])
    set -e
    cd {{ $app }}
    php artisan migrate --force
@endtask

@task('configuration', ['on' => 'production'])
    set -e
    cd {{ $app }}
    {{-- Forgotten twice, and both times a new setting read as null in production. --}}
    php artisan config:cache
@endtask

@task('workers', ['on' => 'production'])
    set -e
    cd {{ $app }}
    {{-- Horizon keeps the old code in memory; systemd starts it again within seconds. --}}
    php artisan horizon:terminate
@endtask

@task('check', ['on' => 'production'])
    set -e
    cd {{ $app }}
    sleep 8
    php artisan horizon:status
    php artisan schedule:list
@endtask

@error
    echo "\n❌ Deploy stopped at task: {$task}\n";
@enderror

{{-- @finished runs after a failure too, so it has to look at the exit code before celebrating. --}}
@finished
    if ($exitCode === 0) {
        echo "\n✅ Deploy finished.\n";
    }
@endfinished
