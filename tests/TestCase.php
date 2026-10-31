<?php

namespace Tests;

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Seed the label roles and permissions after each database refresh.
     */
    protected bool $seed = true;

    protected string $seeder = RolesAndPermissionsSeeder::class;
}
