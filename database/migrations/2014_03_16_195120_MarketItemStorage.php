<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MarketItemStorage extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('marketItem')) {
            return;
        }

        Schema::create('marketItem', function($table) {
            $table->increments('id');
            $table->unsignedInteger('typeId');
            $table->unique('typeId');
            $table->unsignedInteger('buyVolume');
            $table->double('buyAvg', 20, 2);
            $table->double('buyMax', 20, 2);
            $table->double('buyMin', 20, 2);
            $table->double('buyStddev', 20, 2);
            $table->double('buyMedian', 20, 2);
            $table->double('buyPercentile', 20, 2);
            $table->unsignedInteger('sellVolume');
            $table->double('sellAvg', 20, 2);
            $table->double('sellMax', 20, 2);
            $table->double('sellMin', 20, 2);
            $table->double('sellStddev', 20, 2);
            $table->double('sellMedian', 20, 2);
            $table->double('sellPercentile', 20, 2);
            $table->unsignedInteger('allVolume');
            $table->double('allAvg', 20, 2);
            $table->double('allMax', 20, 2);
            $table->double('allMin', 20, 2);
            $table->double('allStddev', 20, 2);
            $table->double('allMedian', 20, 2);
            $table->double('allPercentile', 20, 2);
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
        Schema::dropIfExists('marketItem');
    }
}
