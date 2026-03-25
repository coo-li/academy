<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_materials', function (Blueprint $table) {
            $table->string('type')->default('file')->after('uploaded_by');
            $table->string('url', 2000)->nullable()->after('type');
            $table->string('link_title')->nullable()->after('url');

            $table->string('storage_path')->nullable()->change();
            $table->string('mime_type')->nullable()->change();
            $table->unsignedBigInteger('file_size')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('training_materials', function (Blueprint $table) {
            $table->dropColumn(['type', 'url', 'link_title']);

            $table->string('storage_path')->nullable(false)->change();
            $table->string('mime_type')->nullable(false)->change();
            $table->unsignedBigInteger('file_size')->nullable(false)->change();
        });
    }
};
