<?php

namespace Tests\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ContentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => 'Some title',
            'content' => 'qsdfmldkjf',
            'excerpt' => '...',
        ];
    }
}