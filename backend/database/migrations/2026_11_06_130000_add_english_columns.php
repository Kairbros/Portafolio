<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('about', function (Blueprint $table) {
            $table->text('body_en')->nullable()->after('body');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->text('description')->change();
            $table->text('description_en')->nullable()->after('description');
        });

        Schema::table('type_projects', function (Blueprint $table) {
            $table->string('name_en')->nullable()->after('name');
            $table->string('description_en')->nullable()->after('description');
        });

        Schema::table('type_skill', function (Blueprint $table) {
            $table->string('name_en')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('type_skill', fn (Blueprint $table) => $table->dropColumn('name_en'));
        Schema::table('type_projects', fn (Blueprint $table) => $table->dropColumn(['name_en', 'description_en']));
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('description_en');
            $table->string('description')->change();
        });
        Schema::table('about', fn (Blueprint $table) => $table->dropColumn('body_en'));
    }
};
