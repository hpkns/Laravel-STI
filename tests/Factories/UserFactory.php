<?php

namespace Tests\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'type' => 'user',
            'email' => 'test@domain.com',
            'password' => password_hash(Str::random(32), PASSWORD_BCRYPT),
        ];
    }
}