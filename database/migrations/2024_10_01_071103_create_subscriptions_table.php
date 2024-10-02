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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->bigIncrements('subscription_id');
            $table->integer('subscription_days')->default(0);
            $table->integer('subscription_months')->default(0);
            $table->integer('subscription_amt_per_month');
            $table->integer('subscription_amt_total');
            $table->text('subscription_package_description_1');
            $table->text('subscription_package_description_2');
            $table->text('subscription_package_description_3');
            $table->text('subscription_package_description_4')->nullable();
            $table->string('subscription_adder_admin_name', 255);
            $table->timestamps();
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->unsignedBigInteger('subscription_country_id');
            $table->foreign('subscription_country_id')->references('country_id')->on('countries');
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
