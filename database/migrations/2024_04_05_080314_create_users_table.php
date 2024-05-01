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
            $table->bigIncrements('user_id');
            $table->string('user_sys_id', 255)->unique();
            $table->string('user_first_name', 255);
            $table->string('user_last_name', 255);
            $table->string('user_phone', 255)->unique();
            $table->string('user_referral_code', 255)->unique();
            $table->string('user_invitors_referral_code', 255)->nullable();
            //$table->bigInteger('user_country_id')->default(0);
            $table->string('user_notification_token_android', 255)->default("");
            $table->string('user_notification_token_web', 255)->default("");
            $table->string('user_notification_token_ios', 255)->default("");
            $table->string('user_android_app_version_code', 255)->default("");
            $table->string('user_ios_app_version_code', 255)->default("");
            $table->boolean('user_flagged')->default(false);
            $table->text('user_flagged_reason')->nullable();
            $table->timestamps();
        });


        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('user_country_id');
            $table->foreign('user_country_id')->references('country_id')->on('countries');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Schema::dropIfExists('users');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
};
