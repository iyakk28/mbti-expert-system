<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('quiz_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->unique();
            $table->string('name');
            $table->integer('age');
            $table->json('answers')->nullable();
            $table->string('mbti_result', 4)->nullable();
            $table->json('result_details')->nullable();
            $table->string('contact_method')->nullable();
            $table->string('contact_address')->nullable();
            $table->boolean('contact_sent')->default(false);
            $table->timestamps();
            
            $table->index('session_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('quiz_sessions');
    }
};