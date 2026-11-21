<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddScreenToDeepLinksTable extends Migration
{
    /**
     * The screen is supplied by the caller when the link is created, so two
     * links to the same target but different screens must get different codes.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('deep_links', function (Blueprint $table) {
            $table->string('screen', 16)->nullable()->after('target_id');

            $table->dropIndex(['target_type', 'target_id']);
            $table->unique(
                ['target_type', 'target_id', 'screen'],
                'deep_links_target_screen_unique'
            );
        });
    }

    /**
     * @return void
     */
    public function down()
    {
        Schema::table('deep_links', function (Blueprint $table) {
            $table->dropUnique('deep_links_target_screen_unique');
            $table->index(['target_type', 'target_id']);
            $table->dropColumn('screen');
        });
    }
}
