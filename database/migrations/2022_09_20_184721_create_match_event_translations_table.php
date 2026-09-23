<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMatchEventTranslationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('match_event_translations', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('match_event_id')->unsigned();
            $table->text('description');
            $table->string('locale')->index();
            $table->unique(['match_event_id','locale']);
            $table->foreign('match_event_id')->references('id')->on('match_events')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('match_event_translations');
    }
}
