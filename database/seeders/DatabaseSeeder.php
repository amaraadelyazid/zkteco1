<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        $this->truncateTables();
        $this->seedUsers();
        $this->seedDepartements();
        $this->seedShifts();
        $this->seedBiometricDevices();
        $this->seedAdmins();
        $this->seedGRHs();
        $this->seedEmployes();
        $this->seedPaySlips();
        $this->seedPresences();
        $this->seedLeaveRequests();
        $this->seedComplaints();
        $this->seedLogs();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    protected function truncateTables(): void
    {
        $tables = [
            'users',
            'admins',
            'grhs',
            'employes',
            'departements',
            'shifts',
            'dispositif_biometriques',
            'fiche_de_paies',
            'presences',
            'demande_conges',
            'reclamations',
            'logs',
            'personal_access_tokens',
        ];
        foreach ($tables as $table) {
            DB::table($table)->truncate();
        }
    }

    protected function seedUsers(): void
    {
        $users = [
            [
                'name' => 'Generic User',
                'email' => 'user@gmail.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];
        DB::table('users')->insert($users);
    }

    protected function seedDepartements(): void
    {
        $departements = [
            ['nom' => 'Ressources Humaines', 'description' => 'Département des RH'],
            ['nom' => 'Informatique', 'description' => 'Département IT'],
            ['nom' => 'Comptabilité', 'description' => 'Département financier'],
            ['nom' => 'Production', 'description' => 'Département de production'],
        ];
        DB::table('departements')->insert($departements);
    }

    protected function seedShifts(): void
    {
        $shifts = [
            [
                'nom' => 'Shift Matin',
                'heure_debut' => '08:00:00',
                'heure_fin' => '16:00:00',
                'pause' => true,
                'heure_debut_pause' => '12:00:00',
                'heure_fin_pause' => '13:00:00',
                'duree_pause' => 60,
                'jours_travail' => json_encode(['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi']),
                'tolerance_retard' => 15,
                'depart_anticipe' => 10,
                'duree_min_presence' => 480,
                'is_decalable' => false,
                'description' => 'Shift standard du matin',
            ],
            [
                'nom' => 'Shift Nuit',
                'heure_debut' => '20:00:00',
                'heure_fin' => '04:00:00',
                'pause' => true,
                'heure_debut_pause' => '00:00:00',
                'heure_fin_pause' => '01:00:00',
                'duree_pause' => 60,
                'jours_travail' => json_encode(['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi']),
                'tolerance_retard' => 30,
                'depart_anticipe' => 15,
                'duree_min_presence' => 480,
                'is_decalable' => true,
                'description' => 'Shift de nuit flexible',
            ],
        ];
        DB::table('shifts')->insert($shifts);
    }

    protected function seedBiometricDevices(): void
    {
        $devices = [
            [
                'ip' => '192.168.1.100',
                'port' => 8080,
                'version' => '2.5',
                'status' => 'active',
            ],
            [
                'ip' => '192.168.1.101',
                'port' => 8080,
                'version' => '2.5',
                'status' => 'active',
            ],
        ];
        DB::table('dispositif_biometriques')->insert($devices);
    }

    protected function seedAdmins(): void
    {
        $admins = [
            [
                'name' => 'Admin',
                'email' => 'admin@gmail.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
                'biometric_id' => 1001,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];
        DB::table('admins')->insert($admins);
    }

    protected function seedGRHs(): void
    {
        $shift = DB::table('shifts')->first();
        $grhs = [
            [
                'nom' => 'GRH',
                'prenom' => 'Manager',
                'email' => 'grh@gmail.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
                'biometric_id' => 2001,
                'salaire' => 5000.00,
                'shift_id' => $shift->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];
        DB::table('grhs')->insert($grhs);
    }

    protected function seedEmployes(): void
    {
        $departementIT = DB::table('departements')->where('nom', 'Informatique')->first();
        $departementRH = DB::table('departements')->where('nom', 'Ressources Humaines')->first();
        $shiftMatin = DB::table('shifts')->where('nom', 'Shift Matin')->first();
        $shiftNuit = DB::table('shifts')->where('nom', 'Shift Nuit')->first();

        $employes = [
            [
                'nom' => 'Adel',
                'prenom' => 'Adel',
                'email' => 'adel@gmail.com',
                'password' => Hash::make('adel1234'),
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
                'biometric_id' => 3001,
                'salaire' => 3000.00,
                'poste' => 'Développeur',
                'departement_id' => $departementIT->id,
                'shift_id' => $shiftMatin->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom' => 'Adem',
                'prenom' => 'Adem',
                'email' => 'adem@gmail.com',
                'password' => Hash::make('adem1234'),
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
                'biometric_id' => 3002,
                'salaire' => 3500.00,
                'poste' => 'Recruteur',
                'departement_id' => $departementRH->id,
                'shift_id' => $shiftNuit->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];
        DB::table('employes')->insert($employes);
    }

    protected function seedPaySlips(): void
    {
        $adel = DB::table('employes')->where('email', 'adel@gmail.com')->first();
        $adem = DB::table('employes')->where('email', 'adem@gmail.com')->first();
        $currentMonth = now()->format('F Y');

        $paySlips = [
            [
                'employe_id' => $adel->id,
                'mois' => $currentMonth,
                'montant' => $adel->salaire,
                'avance' => 500.00,
                'heures_sup' => 200.00,
                'primes' => 100.00,
                'status' => 'paid',
                'date_generation' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'employe_id' => $adem->id,
                'mois' => $currentMonth,
                'montant' => $adem->salaire,
                'avance' => 0,
                'heures_sup' => 150.00,
                'primes' => 150.00,
                'status' => 'pending',
                'date_generation' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];
        DB::table('fiche_de_paies')->insert($paySlips);
    }

    protected function seedPresences(): void
    {
        $employes = DB::table('employes')->get();
        $devices = DB::table('dispositif_biometriques')->get();
        $today = now()->format('Y-m-d');

        foreach ($employes as $employe) {
            $shift = DB::table('shifts')->find($employe->shift_id);
            $startTime = now()->setTimeFromTimeString($shift->heure_debut)->subMinutes(5);
            $endTime = now()->setTimeFromTimeString($shift->heure_fin)->addMinutes(5);

            DB::table('presences')->insert([
                'employe_id' => $employe->id,
                'timestamp' => $startTime,
                'type' => 'entrée',
                'methode' => 'biométrique',
                'is_anomalie' => false,
                'etat' => 'present',
'jour' => $today,
'dispositif_id' => $devices->random()->id,
'created_at' => now(),
'updated_at' => now(),
]);

DB::table('presences')->insert([
'employe_id' => $employe->id,
'timestamp' => $endTime,
'type' => 'sortie',
'methode' => 'biométrique',
'is_anomalie' => false,
'etat' => 'present',
'jour' => $today,
'dispositif_id' => $devices->random()->id,
'created_at' => now(),
'updated_at' => now(),
]);

if (rand(0, 1)) {
DB::table('presences')->insert([
'employe_id' => $employe->id,
'timestamp' => $startTime->addMinutes(rand(20, 60)),
'type' => 'entrée',
'methode' => 'manuel',
'is_anomalie' => true,
'etat' => 'retard',
'jour' => $today,
'dispositif_id' => null,
'created_at' => now(),
'updated_at' => now(),
]);
}
}
}

protected function seedLeaveRequests(): void
{
$employes = DB::table('employes')->get();
$grhs = DB::table('grhs')->get();

foreach ($employes as $employe) {
$status = ['en_attente', 'approuvee', 'refusee'][rand(0, 2)];

$leaveRequest = [
'employe_id' => $employe->id,
'type' => ['annuel', 'maladie', 'maternité', 'exceptionnel'][rand(0, 3)],
'message' => 'Je souhaite prendre des congés pour raison personnelle.',
'photo' => null,
'status' => $status,
'reponse' => $status !== 'en_attente' ? 'Votre demande a été ' . $status : null,
'grh_id' => $status !== 'en_attente' ? $grhs->random()->id : null,
'date_demande' => now()->subDays(rand(1, 30)),
'date_debut' => now()->addDays(rand(5, 10))->format('Y-m-d'),
'date_fin' => now()->addDays(rand(11, 20))->format('Y-m-d'),
'created_at' => now(),
'updated_at' => now(),
];

DB::table('demande_conges')->insert($leaveRequest);
}
}

protected function seedComplaints(): void
{
$employes = DB::table('employes')->get();
$grhs = DB::table('grhs')->get();

foreach ($employes as $employe) {
$status = ['nouveau', 'en_cours', 'resolu'][rand(0, 2)];

$complaint = [
'employe_id' => $employe->id,
'message' => 'J ai un problème avec ' . ['mon salaire', 'mes horaires', 'mon manager', 'mes congés'][rand(0, 3)] . '.',
'statut' => $status,
'reponse' => $status === 'resolu' ? 'Votre réclamation a été traitée.' : null,
'grh_id' => $status !== 'nouveau' ? $grhs->random()->id : null,
'date_reclamation' => now()->subDays(rand(1, 30)),
'created_at' => now(),
'updated_at' => now(),
];

DB::table('reclamations')->insert($complaint);
}
}

protected function seedLogs(): void
{
$admins = DB::table('admins')->get();
$grhs = DB::table('grhs')->get();
$employes = DB::table('employes')->get();
$users = DB::table('users')->get();
$actions = [
'login', 'logout', 'create', 'update', 'delete',
'demande_conge', 'presence', 'paiement', 'reclamation'
];

$allUsers = $admins->merge($grhs)->merge($employes)->merge($users);

for ($i = 0; $i < 50; $i++) {
$user = rand(0, 1) ? $allUsers->random() : null;
$table = $user ? ($admins->contains($user) ? 'admins' : ($grhs->contains($user) ? 'grhs' : ($employes->contains($user) ? 'employes' : 'users'))) : null;

DB::table('logs')->insert([
'user_id' => $user && $table === 'users' ? $user->id : null,
'admin_id' => $user && $table === 'admins' ? $user->id : null,
'grh_id' => $user && $table === 'grhs' ? $user->id : null,
'employe_id' => $user && $table === 'employes' ? $user->id : null,
'level' => ['info', 'warning', 'error'][rand(0, 2)],
'action' => $actions[rand(0, count($actions) - 1)],
'message' => 'User performed ' . $actions[rand(0, count($actions) - 1)] . ' action',
'created_at' => now()->subDays(rand(0, 30)),
'updated_at' => now()->subDays(rand(0, 30)),
]);
}
}
}
