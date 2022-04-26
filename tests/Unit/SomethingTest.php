<?php

namespace Tests\Unit;

use Tests\Fakes\User;
use Tests\TestCase;

class SomethingTest extends TestCase
{
    public function test_something()
    {
        User::factory()->create();
        $this->assertTrue(true);
    }

    public function test_Count()
    {
die(var_dump(User::count()));
    }
}