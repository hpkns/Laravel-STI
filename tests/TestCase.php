<?php

namespace Tests;


use Tests\Concerns\UsesDatabase;

class TestCase extends \PHPUnit\Framework\TestCase
{
    use UsesDatabase;

    public function setUp(): void
    {
        $this->bootstrapApplication();
        $this->createTables();
    }

    public function tearDown(): void
    {
        $this->dropTables();

        parent::tearDown();
    }
}