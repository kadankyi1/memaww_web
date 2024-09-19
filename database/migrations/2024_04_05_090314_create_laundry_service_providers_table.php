<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatelaundryServiceProvidersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('laundry_service_providers', function (Blueprint $table) {
            $table->bigIncrements('laundrysp_id');
            $table->string('laundrysp_sys_id', 255)->unique();
            $table->string('laundrysp_name', 255)->unique();
            $table->string('laundrysp_location_raw', 255);
            $table->string('laundrysp_location_gps', 255);
            $table->string('laundrysp_phone_1', 255)->unique();
            $table->string('laundrysp_phone_2', 255)->nullable();
            $table->string('laundrysp_email', 255)->nullable();
            $table->boolean('laundrysp_flagged')->default(false);
            $table->text('laundrysp_flagged_reason')->nullable();
            $table->timestamps();
        });


        Schema::table('laundry_service_providers', function (Blueprint $table) {
            $table->unsignedBigInteger('laundrysp_country_id');
            $table->foreign('laundrysp_country_id')->references('country_id')->on('countries');
        });

        DB::unprepared("insert into laundry_service_providers 
        (laundrysp_id, laundrysp_sys_id, laundrysp_name, laundrysp_location_raw, 
        laundrysp_location_gps, laundrysp_phone_1, laundrysp_phone_2, laundrysp_email, 
        laundrysp_flagged, laundrysp_flagged_reason, laundrysp_country_id)  
        values (1, 'memaww-gh-sys-id-1', 'Memaww Ghana', 'Adenta West', '5.712578, -0.172639', '053-506-5535', '', 'info@infodefa.com', false, '', 81);");
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Schema::dropIfExists('laundry_service_providers');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
