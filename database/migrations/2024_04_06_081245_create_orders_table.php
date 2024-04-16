<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->bigIncrements('order_id');
            $table->string('order_sys_id', 255)->unique();
            $table->string('order_collection_biker_name', 255)->nullable();
            $table->string('order_collection_location_raw', 255)->nullable();
            $table->string('order_collection_location_gps', 255)->nullable();
            $table->dateTime('order_collection_date', precision: 0)->nullable();
            $table->string('order_collection_contact_person_phone', 255);
            $table->string('order_dropoff_location_raw', 255)->nullable();
            $table->string('order_dropoff_location_gps', 255)->nullable();
            $table->dateTime('order_dropoff_date', precision: 0)->nullable();
            $table->string('order_dropoff_contact_person_phone', 255)->nullable();
            $table->string('order_dropoff_biker_name', 255)->nullable();
            $table->bigInteger('order_discount_id')->nullable();
            $table->bigInteger('order_lightweightitems_just_wash_quantity')->nullable();
            $table->bigInteger('order_lightweightitems_wash_and_iron_quantity')->nullable();
            $table->bigInteger('order_lightweightitems_just_iron_quantity')->nullable();
            $table->bigInteger('order_bulkyitems_just_wash_quantity')->nullable();
            $table->bigInteger('order_bulkyitems_wash_and_iron_quantity')->nullable();
            $table->string('order_user_countrys_currency', 255);
            $table->decimal('order_discount_amount_in_user_countrys_currency',9,2);
            $table->decimal('order_discount_amount_in_dollars_at_the_time',9,2);
            $table->decimal('order_final_price_in_user_countrys_currency',9,2);
            $table->decimal('order_final_price_in_dollars_at_the_time',9,2);
            $table->integer('order_status')->default(0);
            $table->integer('order_payment_status')->default(0);
            $table->text('order_payment_details')->nullable();
            $table->text('order_all_items_full_description')->nullable();
            $table->boolean('order_flagged')->default(false);
            $table->text('order_flagged_reason');
            $table->timestamps();
        });


        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('order_country_id');
            $table->foreign('order_country_id')->references('country_id')->on('countries');

            $table->unsignedBigInteger('order_user_id');
            $table->foreign('order_user_id')->references('user_id')->on('users');

            $table->unsignedBigInteger('order_laundrysp_id');
            $table->foreign('order_laundrysp_id')->references('laundrysp_id')->on('laundry_service_providers');
        });

    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Schema::dropIfExists('orders');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
