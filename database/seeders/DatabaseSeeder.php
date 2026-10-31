<?php

namespace Database\Seeders;

use App\Enums\LabelRole;
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
        $this->call(RolesAndPermissionsSeeder::class);

        $label = Label::factory()->create([
            'name' => 'Demo Label',
            'slug' => 'demo-label',
        ]);

        $label->addMember(
            User::factory()->admin()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]),
            LabelRole::Owner,
        );

        $label->addMember(
            User::factory()->create([
                'name' => 'Manager User',
                'email' => 'manager@example.com',
            ]),
            LabelRole::Manager,
        );

        $label->addMember(
            User::factory()->create([
                'name' => 'Viewer User',
                'email' => 'viewer@example.com',
            ]),
            LabelRole::Viewer,
        );
    }
}
