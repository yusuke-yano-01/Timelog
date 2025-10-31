<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTimesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('times', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->date('date');
            $table->string('arrival_time');
            $table->string('departure_time')->nullable();
            $table->string('start_break_time1')->nullable();
            $table->string('end_break_time1')->nullable();
            $table->string('start_break_time2')->nullable();
            $table->string('end_break_time2')->nullable();
            $table->string('note')->nullable();
            $table->boolean('application_flg')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('times');
    }
}
