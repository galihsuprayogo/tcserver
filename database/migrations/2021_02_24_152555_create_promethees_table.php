<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePrometheesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('promethees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id')->default(0);
            $table->unsignedBigInteger('distance_id')->nullable();
            $table->double('type')->default(0);
            $table->double('procedure')->default(0);
            $table->double('output')->default(0);
            $table->double('grade')->default(0);
            $table->double('price')->default(0);
            $table->double('score')->nullable();
            $table->timestamps();
            $table->foreign('distance_id')->references('id')->on('distances')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('promethees');
    }
}
