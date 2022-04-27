<?php

use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Schema\Blueprint;

return new class {
    public function up()
    {
        Manager::schema()->create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('content_id');
            $table->string('name');
            $table->text('content');
            $table->timestamps();
        });
    }

    public function down()
    {
        Manager::schema()->dropIfExists('comments');
    }
};