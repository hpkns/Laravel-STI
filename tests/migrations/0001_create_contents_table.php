<?php

use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Schema\Blueprint;

return new class {
    public function up()
    {
        Manager::schema()->create('contents', function (Blueprint $table) {
            $table->increments('id');
            $table->enum('type', ['page', 'post']);
            $table->string('title');
            $table->text('content')->nullable();
            $table->text('excerpt')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Manager::schema()->dropIfExists('contents');
    }
};