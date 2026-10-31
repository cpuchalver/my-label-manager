<?php

namespace Database\Seeders;

use App\Models\Label;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $label = Label::factory()->create([
            'name' => 'Demo Label',
            'slug' => 'demo-label',
        ]);

        User::factory()
            ->admin()
            ->hasAttached($label)
            ->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);
    }
}
