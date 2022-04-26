<?php

use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Schema\Blueprint;

return new class {
    public function up()
    {
        Manager::schema()->create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->enum('type', ['user', 'admin']);
            $table->string('email');
            $table->string('password');
            $table->timestamps();
        });
    }

    public function down()
    {
        Manager::schema()->dropIfExists('users');
    }
};