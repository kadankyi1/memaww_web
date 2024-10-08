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
            $table->integer('subscription_items_washed')->default(0);
            $table->integer('subscription_pickups_done')->default(0);
            $table->decimal('subscription_amount_paid',9,2);
            $table->integer('subscription_max_number_of_people_in_home');
            $table->integer('subscription_number_of_months');
            $table->string('subscription_pickup_time', 255);
            $table->string('subscription_pickup_day', 255)->default("Saturday");
            $table->string('subscription_pickup_location', 255);
            $table->string('subscription_payment_transaction_id', 255)->unique();
            $table->text('subscription_payment_response');
            $table->text('subscription_package_description');
            $table->timestamps();
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->unsignedBigInteger('subscription_country_id');
            $table->foreign('subscription_country_id')->references('country_id')->on('countries');
            $table->unsignedBigInteger('subscription_user_id');
            $table->foreign('subscription_user_id')->references('user_id')->on('users');
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
