<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drawing_pages', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        DB::table('drawing_pages')->insert(['created_at' => now(), 'updated_at' => now()]);

        Schema::create('drawings', function (Blueprint $table) {
            $table->id();
            $table->string('name', 24)->nullable();
            $table->string('comment', 140)->nullable();
            $table->string('pixels', 1024);
            $table->foreignId('drawing_page_id')->nullable()->constrained('drawing_pages')->nullOnDelete();
            $table->decimal('x', 6, 2)->default(0);
            $table->decimal('y', 6, 2)->default(0);
            $table->decimal('rotation', 6, 2)->default(0);
            $table->unsignedInteger('z')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drawings');
        Schema::dropIfExists('drawing_pages');
    }
};
