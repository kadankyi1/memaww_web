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
        Schema::create('messages', function (Blueprint $table) {
            $table->bigIncrements('message_id');
            $table->text('message_text');
            $table->timestamps();
        });


        Schema::table('messages', function (Blueprint $table) {
            $table->unsignedBigInteger('message_sender_user_id');
            $table->foreign('message_sender_user_id')->references('user_id')->on('users');

            $table->unsignedBigInteger('message_receiver_id');
            $table->foreign('message_receiver_id')->references('user_id')->on('users');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
