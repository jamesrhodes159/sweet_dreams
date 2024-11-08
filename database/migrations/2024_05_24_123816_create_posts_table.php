<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');

            $table->string('dream_type');
            $table->string('title');
            $table->longText('description')->nullable();
            $table->string('image')->nullable();
            $table->string('post_type')->default('public');
            $table->string('topic')->nullable();
            $table->string('feeling')->nullable();

            $table->integer('isHide')->default(0);

            $table->enum('type',['post','draft'])->default('post');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
