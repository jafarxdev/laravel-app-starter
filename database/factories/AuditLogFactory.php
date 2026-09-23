<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AuditLogFactory extends Factory
{
    public function definition(): array
    {
        return ['action' => 'user.updated', 'description' => 'User updated', 'old_values' => [], 'new_values' => []];
    }
}
