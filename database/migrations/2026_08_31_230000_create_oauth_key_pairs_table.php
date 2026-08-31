<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oauth_key_pairs', function (Blueprint $table) {
            $table->id();
            $table->mediumText('private_key');
            $table->mediumText('public_key');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oauth_key_pairs');
    }
};
