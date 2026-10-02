<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_scans', function (Blueprint $table) {
            $table->renameColumn('document_type', 'doc_type');
            $table->renameColumn('file_path', 'stored_path');
            $table->renameColumn('mime_type', 'mime');
            $table->renameColumn('file_size', 'size');
            $table->renameColumn('analysis', 'result');
            $table->renameColumn('failure_message', 'error');
        });

        Schema::table('document_scans', function (Blueprint $table) {
            $table->string('doc_type', 12)->change();
            $table->string('stored_path')->nullable()->change();
            $table->unsignedInteger('size')->change();
            $table->string('status', 12)->default('queued')->change();
            $table->unsignedTinyInteger('progress')->default(0)->after('status');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('document_scans', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn('progress');
            $table->string('doc_type', 24)->change();
            $table->string('stored_path')->nullable()->change();
            $table->unsignedBigInteger('size')->change();
            $table->string('status', 24)->default('processing')->change();
        });

        Schema::table('document_scans', function (Blueprint $table) {
            $table->renameColumn('doc_type', 'document_type');
            $table->renameColumn('stored_path', 'file_path');
            $table->renameColumn('mime', 'mime_type');
            $table->renameColumn('size', 'file_size');
            $table->renameColumn('result', 'analysis');
            $table->renameColumn('error', 'failure_message');
        });
    }
};
