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
        Schema::create('collection_call_back_requests', function (Blueprint $table) {
            $table->bigIncrements('col_callback_req_id');
            $table->boolean('col_callback_req_status')->default(false);
            $table->text('col_callback_req_status_message')->nullable();
            $table->timestamps();
        });


        Schema::table('collection_call_back_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('col_callback_req_user_id');
            $table->foreign('col_callback_req_user_id')->references('user_id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Schema::dropIfExists('collection_call_back_requests');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
};
