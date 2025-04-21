<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/migrations-info', function () {
    $migrations = [
        'users' => [
            'table' => 'users',
            'columns' => Schema::getColumnListing('users'),
            'description' => 'Stores user accounts with authentication information',
        ],
        'password_reset_tokens' => [
            'table' => 'password_reset_tokens',
            'columns' => Schema::getColumnListing('password_reset_tokens'),
            'description' => 'Stores password reset tokens',
        ],
        'sessions' => [
            'table' => 'sessions',
            'columns' => Schema::getColumnListing('sessions'),
            'description' => 'Stores user session data',
        ],
        'personal_access_tokens' => [
            'table' => 'personal_access_tokens',
            'columns' => Schema::getColumnListing('personal_access_tokens'),
            'description' => 'Stores API personal access tokens',
        ],
        'shifts' => [
            'table' => 'shifts',
            'columns' => Schema::getColumnListing('shifts'),
            'description' => 'Defines work shifts with schedules and rules',
        ],
        'departements' => [
            'table' => 'departements',
            'columns' => Schema::getColumnListing('departements'),
            'description' => 'Organizational departments structure',
        ],
        'admins' => [
            'table' => 'admins',
            'columns' => Schema::getColumnListing('admins'),
            'description' => 'Administrator profiles linked to users',
        ],
        'grhs' => [
            'table' => 'grhs',
            'columns' => Schema::getColumnListing('grhs'),
            'description' => 'HR staff profiles linked to users',
        ],
        'employes' => [
            'table' => 'employes',
            'columns' => Schema::getColumnListing('employes'),
            'description' => 'Employee profiles with work details',
        ],
        'dispositif_biometriques' => [
            'table' => 'dispositif_biometriques',
            'columns' => Schema::getColumnListing('dispositif_biometriques'),
            'description' => 'Biometric devices configuration',
        ],
        'fiche_de_paies' => [
            'table' => 'fiche_de_paies',
            'columns' => Schema::getColumnListing('fiche_de_paies'),
            'description' => 'Employee pay slips and payment information',
        ],
        'presences' => [
            'table' => 'presences',
            'columns' => Schema::getColumnListing('presences'),
            'description' => 'Employee attendance and check-in/out records',
        ],
        'demande_conges' => [
            'table' => 'demande_conges',
            'columns' => Schema::getColumnListing('demande_conges'),
            'description' => 'Employee leave requests and approvals',
        ],
        'reclamations' => [
            'table' => 'reclamations',
            'columns' => Schema::getColumnListing('reclamations'),
            'description' => 'Employee complaints and HR responses',
        ],
        'logs' => [
            'table' => 'logs',
            'columns' => Schema::getColumnListing('logs'),
            'description' => 'System activity logs and events',
        ],
    ];

    // Get foreign key relationships
    foreach ($migrations as &$migration) {
        $columns = DB::select("
            SELECT
                column_name as `name`,
                data_type as `type`,
                column_type as `full_type`,
                is_nullable,
                column_default,
                column_comment
            FROM information_schema.columns
            WHERE table_schema = ? AND table_name = ?
        ", [config('database.connections.mysql.database'), $migration['table']]);

        $migration['columns'] = collect($columns)->mapWithKeys(function ($col) {
            return [$col->name => $col];
        })->toArray();

        $migration['relationships'] = DB::select("
            SELECT
                TABLE_NAME,
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM
                INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE
                TABLE_SCHEMA = ? AND
                REFERENCED_TABLE_NAME IS NOT NULL AND
                TABLE_NAME = ?
        ", [config('database.connections.mysql.database'), $migration['table']]);
    }

    return view('migrations-info', compact('migrations'));
});

Route::get('/migrations-info/sample-data', function () {
    $tables = [
        'users' => DB::table('users')->limit(5)->get(),
        'password_reset_tokens' => DB::table('password_reset_tokens')->limit(5)->get(),
        'sessions' => DB::table('sessions')->limit(5)->get(),
        'personal_access_tokens' => DB::table('personal_access_tokens')->limit(5)->get(),
        'shifts' => DB::table('shifts')->limit(5)->get(),
        'departements' => DB::table('departements')->limit(5)->get(),
        'admins' => DB::table('admins')->limit(5)->get(),
        'grhs' => DB::table('grhs')->limit(5)->get(),
        'employes' => DB::table('employes')->limit(5)->get(),
        'dispositif_biometriques' => DB::table('dispositif_biometriques')->limit(5)->get(),
        'fiche_de_paies' => DB::table('fiche_de_paies')->limit(5)->get(),
        'presences' => DB::table('presences')->limit(5)->get(),
        'demande_conges' => DB::table('demande_conges')->limit(5)->get(),
        'reclamations' => DB::table('reclamations')->limit(5)->get(),
        'logs' => DB::table('logs')->limit(5)->get(),
    ];

    return view('sample-data', compact('tables'));
});
