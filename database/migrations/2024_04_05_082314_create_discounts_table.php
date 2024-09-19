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
            $table->bigInteger('discount_restricted_to_user_id')->nullable();
            $table->string('discount_admin_name', 255);
            $table->boolean('discount_reusable')->default(false);
            $table->boolean('discount_can_be_used')->default(false);
            $table->dateTime('discount_expiry_date', precision: 0);
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
