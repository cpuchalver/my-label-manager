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
            'slug' => 'demo-label',
            'name' => 'Demo Label',
        ]);

        $user = User::factory()
            ->admin()
            ->create([
                'email' => 'test@example.com',
                'name' => 'Test User',
            ]);

        if (!$user->labels()->where('labels.id', $label->id)->exists()) {
            $user->labels()->attach($label);
        }
    }
}
