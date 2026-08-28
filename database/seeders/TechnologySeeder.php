<?php

namespace Database\Seeders;

use App\Models\Technology;
use Illuminate\Database\Seeder;

class TechnologySeeder extends Seeder
{
    public function run(): void
    {
        $technologies = [
            // Backend
            ['name' => 'Laravel',        'slug' => 'laravel',       'aliases' => []],
            ['name' => 'Symfony',        'slug' => 'symfony',       'aliases' => []],
            ['name' => 'Django',         'slug' => 'django',        'aliases' => []],
            ['name' => 'FastAPI',        'slug' => 'fastapi',       'aliases' => ['fast api']],
            ['name' => 'Node.js',        'slug' => 'nodejs',        'aliases' => ['node', 'nodejs', 'node js']],
            ['name' => 'Express',        'slug' => 'express',       'aliases' => ['expressjs', 'express.js'], 'ambiguous' => true],
            ['name' => 'Spring Boot',    'slug' => 'spring-boot',   'aliases' => ['spring', 'springboot', 'spring framework', 'spring mvc']],
            ['name' => '.NET',           'slug' => 'dotnet',        'aliases' => ['dotnet', 'asp.net', 'aspnet', '.net core', 'dotnet core', 'asp.net core', 'blazor']],
            ['name' => 'Ruby on Rails',  'slug' => 'rails',         'aliases' => ['rails', 'ror']],

            // Frontend
            ['name' => 'React',          'slug' => 'react',         'aliases' => ['reactjs', 'react.js']],
            ['name' => 'Vue',            'slug' => 'vue',           'aliases' => ['vuejs', 'vue.js', 'vue3', 'vue 3', 'vue 2']],
            ['name' => 'Angular',        'slug' => 'angular',       'aliases' => ['angularjs', 'angular.js', 'angular 2', 'angular2']],
            ['name' => 'Next.js',        'slug' => 'nextjs',        'aliases' => ['nextjs', 'next js']],
            ['name' => 'Nuxt.js',        'slug' => 'nuxtjs',        'aliases' => ['nuxtjs', 'nuxt js', 'nuxt3']],
            ['name' => 'Svelte',         'slug' => 'svelte',        'aliases' => ['sveltejs', 'sveltekit', 'svelte kit']],

            // Mobile
            ['name' => 'Flutter',        'slug' => 'flutter',       'aliases' => []],
            ['name' => 'React Native',   'slug' => 'react-native',  'aliases' => ['react native', 'rn']],
            ['name' => 'Swift',          'slug' => 'swift',         'aliases' => ['swiftui', 'swift ui'], 'ambiguous' => true],
            ['name' => 'Kotlin',         'slug' => 'kotlin',        'aliases' => ['kotlin multiplatform', 'kmp']],

            // Databases
            ['name' => 'MySQL',          'slug' => 'mysql',         'aliases' => []],
            ['name' => 'PostgreSQL',     'slug' => 'postgresql',    'aliases' => ['postgres', 'psql']],
            ['name' => 'MongoDB',        'slug' => 'mongodb',       'aliases' => ['mongo']],
            ['name' => 'Redis',          'slug' => 'redis',         'aliases' => []],
            ['name' => 'Elasticsearch',  'slug' => 'elasticsearch', 'aliases' => ['elastic search', 'elastic', 'opensearch']],

            // DevOps / Infra
            ['name' => 'Docker',         'slug' => 'docker',        'aliases' => []],
            ['name' => 'Kubernetes',     'slug' => 'kubernetes',    'aliases' => ['k8s', 'kube']],
            ['name' => 'AWS',            'slug' => 'aws',           'aliases' => ['amazon web services', 'amazon aws']],
            ['name' => 'Azure',          'slug' => 'azure',         'aliases' => ['microsoft azure', 'azure cloud']],
            ['name' => 'GCP',            'slug' => 'gcp',           'aliases' => ['google cloud', 'google cloud platform']],
            ['name' => 'Terraform',      'slug' => 'terraform',     'aliases' => ['terraform cloud']],
            ['name' => 'Linux',          'slug' => 'linux',         'aliases' => ['ubuntu', 'debian', 'centos']],

            // Languages / Other
            ['name' => 'GraphQL',        'slug' => 'graphql',       'aliases' => []],
            ['name' => 'TypeScript',     'slug' => 'typescript',    'aliases' => []],
            ['name' => 'Python',         'slug' => 'python',        'aliases' => []],
            ['name' => 'Java',           'slug' => 'java',          'aliases' => []],
            ['name' => 'PHP',            'slug' => 'php',           'aliases' => []],
            ['name' => 'Go',             'slug' => 'go',            'aliases' => ['golang'], 'ambiguous' => true],
            ['name' => 'Rust',           'slug' => 'rust',          'aliases' => []],
            ['name' => 'C#',             'slug' => 'csharp',        'aliases' => ['csharp', 'c sharp']],
        ];

        foreach ($technologies as $tech) {
            Technology::query()->updateOrCreate(
                ['slug' => $tech['slug']],
                [
                    'name'      => $tech['name'],
                    'aliases'   => $tech['aliases'] ?: null,
                    'ambiguous' => $tech['ambiguous'] ?? false,
                ]
            );
        }
    }
}
