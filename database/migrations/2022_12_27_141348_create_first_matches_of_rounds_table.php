<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFirstMatchesOfRoundsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('first_matches_of_rounds', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('competition_id')->unsigned();
            $table->integer('fixture_id');
            $table->string('round');
            $table->timestamp('match_date');
            $table->timestamps();
            $table->foreign('competition_id')->references('id')->on('competitions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('first_matches_of_rounds');
    }
}
