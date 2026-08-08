<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->unique()->after('name');
            $table->string('avatar_url')->nullable()->after('remember_token');
            $table->text('bio')->nullable()->after('avatar_url');
        });

        DB::statement('ALTER TABLE users MODIFY id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE users DROP PRIMARY KEY');
        DB::statement('ALTER TABLE users MODIFY id CHAR(36) NOT NULL');
        DB::statement('ALTER TABLE users ADD PRIMARY KEY (id)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE users MODIFY id CHAR(36) NOT NULL');
        DB::statement('ALTER TABLE users DROP PRIMARY KEY');
        DB::statement('ALTER TABLE users MODIFY id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL');
        DB::statement('ALTER TABLE users ADD PRIMARY KEY (id)');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'avatar_url', 'bio']);
        });
    }
};
