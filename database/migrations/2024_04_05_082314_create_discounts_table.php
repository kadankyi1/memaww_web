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
        Schema::create('discounts', function (Blueprint $table) {
            $table->bigIncrements('discount_id');
            $table->string('discount_code', 255)->unique();
            $table->integer('discount_percentage')->default(0);
            $table->string('discount_admin_name', 255);
            $table->bigInteger('discount_restricted_to_user_id')->nullable();
            $table->boolean('discount_reusable')->default(false);
            $table->boolean('discount_can_be_used')->default(false);
            $table->string('discount_collection_location_raw', 255)->nullable();
            $table->string('discount_collection_location_gps', 255)->nullable();
            $table->dateTime('discount_collection_date', precision: 0)->nullable();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discounts');
    }
};
