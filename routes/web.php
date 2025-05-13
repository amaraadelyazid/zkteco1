<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use App\Models\Presence;

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


use MehediJaman\LaravelZkteco\LaravelZkteco;

Route::get('/test-zkteco', function () {
    try {
        // Check if the socket extension is loaded
        if (!function_exists('socket_create')) {
            throw new \Exception("The PHP socket extension is not enabled. Please enable 'sockets' in php.ini.");
        }

        $zk = new LaravelZkteco('127.0.0.1', 4370);  // Localhost just for testing

        // Optional: Mocking connect and getTime if testing without device
        // Override or extend LaravelZkteco to mock if needed
        if (app()->environment('local')) {
            // Simulate behavior instead of real socket calls
            return response()->json([
                'time' => now()->toDateTimeString(),
                'note' => 'Simulated response. No real device connected.'
            ]);
        }

        $zk->connect();
        $deviceTime = $zk->getTime();

        // Basic validation on $deviceTime to avoid unpack error
        if (empty($deviceTime)) {
            throw new \Exception("No data received from device. Make sure it is connected and reachable.");
        }

        return response()->json(['time' => $deviceTime]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()]);
    }
});


use App\Http\Controllers\EmployeController;

Route::resource('employes', EmployeController::class);

Route::get('/test-presence-user', function () {
    // On récupère les dernières présences avec l'utilisateur lié
    $presences = Presence::with('user')->latest()->take(10)->get();

    foreach ($presences as $presence) {
        echo "<strong>Presence ID:</strong> {$presence->id}<br>";
        echo "<strong>User Type:</strong> {$presence->user_type}<br>";
        echo "<strong>User ID:</strong> {$presence->user_id}<br>";

        // Vérifie si un utilisateur est lié
        if ($presence->user) {
            echo "<strong>User Name:</strong> " . $presence->user->name ?? 'Not found' . "<br>";
        } else {
            echo "<strong>User:</strong> Not found<br>";
        }

        echo "<hr>";
    }
});