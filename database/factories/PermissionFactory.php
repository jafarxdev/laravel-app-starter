<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PermissionFactory extends Factory
{
    public function definition(): array
    {
        $module = fake()->unique()->lexify('module????????');

        return ['name' => ucfirst($module).' view', 'slug' => $module.'.view', 'module' => $module];
    }
}
