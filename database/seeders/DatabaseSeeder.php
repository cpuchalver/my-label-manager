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
     * Seed two demo labels whose members have different roles from one label to the other.
     * Every account uses the password "password".
     */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        $testUser = User::factory()->admin()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $managerUser = User::factory()->create([
            'name' => 'Manager User',
            'email' => 'manager@example.com',
        ]);

        $viewerUser = User::factory()->create([
            'name' => 'Viewer User',
            'email' => 'viewer@example.com',
        ]);

        $secondOwner = User::factory()->create([
            'name' => 'Second Owner',
            'email' => 'owner@example.com',
        ]);

        $demoLabel = Label::factory()->create([
            'name' => 'Demo Label',
            'slug' => 'demo-label',
        ]);

        $demoLabel->addMember($testUser, LabelRole::Owner);
        $demoLabel->addMember($managerUser, LabelRole::Manager);
        $demoLabel->addMember($viewerUser, LabelRole::Viewer);

        $secondLabel = Label::factory()->create([
            'name' => 'Second Label',
            'slug' => 'second-label',
        ]);

        $secondLabel->addMember($secondOwner, LabelRole::Owner);
        $secondLabel->addMember($managerUser, LabelRole::Admin);
        $secondLabel->addMember($testUser, LabelRole::Viewer);
    }
}
