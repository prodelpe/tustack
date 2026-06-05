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
            ['name' => 'Laravel', 'slug' => 'laravel'],
            ['name' => 'Symfony', 'slug' => 'symfony'],
            ['name' => 'Django', 'slug' => 'django'],
            ['name' => 'FastAPI', 'slug' => 'fastapi'],
            ['name' => 'Node.js', 'slug' => 'nodejs'],
            ['name' => 'Express', 'slug' => 'express'],
            ['name' => 'Spring Boot', 'slug' => 'spring-boot'],
            ['name' => '.NET', 'slug' => 'dotnet'],
            ['name' => 'Ruby on Rails', 'slug' => 'rails'],

            // Frontend
            ['name' => 'React', 'slug' => 'react'],
            ['name' => 'Vue', 'slug' => 'vue'],
            ['name' => 'Angular', 'slug' => 'angular'],
            ['name' => 'Next.js', 'slug' => 'nextjs'],
            ['name' => 'Nuxt.js', 'slug' => 'nuxtjs'],
            ['name' => 'Svelte', 'slug' => 'svelte'],

            // Mobile
            ['name' => 'Flutter', 'slug' => 'flutter'],
            ['name' => 'React Native', 'slug' => 'react-native'],
            ['name' => 'Swift', 'slug' => 'swift'],
            ['name' => 'Kotlin', 'slug' => 'kotlin'],

            // Databases
            ['name' => 'MySQL', 'slug' => 'mysql'],
            ['name' => 'PostgreSQL', 'slug' => 'postgresql'],
            ['name' => 'MongoDB', 'slug' => 'mongodb'],
            ['name' => 'Redis', 'slug' => 'redis'],
            ['name' => 'Elasticsearch', 'slug' => 'elasticsearch'],

            // DevOps / Infra
            ['name' => 'Docker', 'slug' => 'docker'],
            ['name' => 'Kubernetes', 'slug' => 'kubernetes'],
            ['name' => 'AWS', 'slug' => 'aws'],
            ['name' => 'Azure', 'slug' => 'azure'],
            ['name' => 'GCP', 'slug' => 'gcp'],
            ['name' => 'Terraform', 'slug' => 'terraform'],
            ['name' => 'Linux', 'slug' => 'linux'],

            // Other
            ['name' => 'GraphQL', 'slug' => 'graphql'],
            ['name' => 'TypeScript', 'slug' => 'typescript'],
            ['name' => 'Python', 'slug' => 'python'],
            ['name' => 'Java', 'slug' => 'java'],
            ['name' => 'PHP', 'slug' => 'php'],
            ['name' => 'Go', 'slug' => 'go'],
            ['name' => 'Rust', 'slug' => 'rust'],
            ['name' => 'C#', 'slug' => 'csharp'],
        ];

        foreach ($technologies as $tech) {
            Technology::updateOrCreate(
                ['slug' => $tech['slug']],
                ['name' => $tech['name']]
            );
        }
    }
}
