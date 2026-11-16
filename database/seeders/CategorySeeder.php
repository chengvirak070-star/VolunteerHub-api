<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['slug' => 'environment', 'name_en' => 'Environment', 'name_km' => 'បរិស្ថាន'],
            ['slug' => 'education', 'name_en' => 'Education', 'name_km' => 'អប់រំ'],
            ['slug' => 'health', 'name_en' => 'Health', 'name_km' => 'សុខភាព'],
            ['slug' => 'community', 'name_en' => 'Community', 'name_km' => 'សហគមន៍'],
        ];

        foreach ($categories as $c) {
            Category::updateOrCreate(['slug' => $c['slug']], $c);
        }
    }
}
