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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->integer('otp')->nullable();
            $table->string('full_name')->nullable();
            $table->string('email')->unique();
            $table->tinyInteger('is_active')->default(1);
            $table->enum('user_type',['admin','user','coach','staff'])->default('user');
            $table->string('phone_number')->nullable();
            $table->string('dob')->nullable();
            $table->string('password')->nullable();
            $table->string('profile_image')->nullable();
            $table->string('bio')->nullable();
            $table->string('language')->nullable();

            $table->string('legal_name')->nullable();
            $table->string('driver_license')->nullable();
            $table->integer('is_verified')->default(0);
            $table->string('customer_id')->nullable();
            $table->string('account_number')->nullable();

            $table->integer("account_verified")->default(0);
            $table->timestamp('email_verified_at')->nullable();
            $table->string('device_type')->nullable();
            $table->string('device_token')->nullable();
            $table->integer('is_social')->default(0);
            $table->integer('is_forgot')->default(0);
            $table->integer('is_signup')->default(0);
            $table->string('user_social_token')->nullable();
            $table->string('user_social_type')->nullable();
            $table->integer('is_profile_complete')->default(0);
            $table->integer('notification')->default(1);
            $table->integer('is_blocked')->nullable()->default(0);
            // $table->string('api_token')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
