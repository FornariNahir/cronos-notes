<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tables = DB::select('SHOW TABLES');
        $tableNames = array_map(fn($t) => array_values((array)$t)[0], $tables);
        
        if (in_array('password_reset_tokens', $tableNames)) {
            Schema::rename('password_reset_tokens', 'TokenReseteoPassword');
        }
        if (in_array('sessions', $tableNames)) {
            Schema::rename('sessions', 'Sesion');
        }
    }

    public function down(): void
    {
        $tables = DB::select('SHOW TABLES');
        $tableNames = array_map(fn($t) => array_values((array)$t)[0], $tables);
        
        if (in_array('TokenReseteoPassword', $tableNames)) {
            Schema::rename('TokenReseteoPassword', 'password_reset_tokens');
        }
        if (in_array('Sesion', $tableNames)) {
            Schema::rename('Sesion', 'sessions');
        }
    }
};
