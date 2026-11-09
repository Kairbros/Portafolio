<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('folder');
            $table->string('slug')->nullable()->unique()->after('name');
            $table->longText('body')->nullable()->after('description_en');
            $table->longText('body_en')->nullable()->after('body');
            $table->string('status', 16)->default('in_progress')->after('body_en');
            $table->unsignedSmallInteger('year')->nullable()->after('status');
            $table->json('tags')->nullable()->after('year');
            $table->boolean('published')->default(false)->after('tags');
        });

        Schema::table('type_projects', function (Blueprint $table) {
            $table->dropColumn('extension');
        });

        Schema::create('project_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('label', 60);
            $table->string('url', 2048);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('project_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('label', 80);
            $table->string('path');
            $table->string('original_name');
            $table->unsignedBigInteger('size');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_files');
        Schema::dropIfExists('project_links');

        Schema::table('type_projects', function (Blueprint $table) {
            $table->string('extension')->nullable();
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn(['slug', 'body', 'body_en', 'status', 'year', 'tags', 'published']);
            $table->string('folder')->default('');
        });
    }
};
