<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('mux_videos', function (Blueprint $table) {
            $table->id();
            $table->string('asset_id')->unique(); // Mux asset ID
            $table->string('playback_id')->unique(); // Mux playback ID
            $table->unsignedBigInteger('order_id')->nullable(); // Link to the order
            $table->timestamps();

            // Optionally, add a foreign key to the orders table if it exists
            // $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('mux_videos');
    }
};
