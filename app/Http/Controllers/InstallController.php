<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class InstallController extends Controller
{
    // ── Step Views ────────────────────────────────────────────────

    /**
     * Step 1: System Check (requirements + permissions combined)
     */
    public function check()
    {
        return view('install.check', [
            'requirements' => $this->checkRequirements(),
            'permissions'  => $this->checkPermissions(),
        ]);
    }

    /**
     * Step 2: Setup (database + admin + site name)
     */
    public function setup()
    {
        return view('install.setup');
    }

    /**
     * Finish: Installation complete
     */
    public function finish()
    {
        return view('install.finish');
    }

    // ── AJAX Endpoints ───────────────────────────────────────────

    /**
     * AJAX: Test database connection
     */
    public function testDatabase(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'db_host'     => 'required|string',
            'db_port'     => 'required|string',
            'db_name'     => 'required|string',
            'db_user'     => 'required|string',
            'db_password' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            new \PDO(
                "mysql:host={$request->db_host};port={$request->db_port};dbname={$request->db_name}",
                $request->db_user,
                $request->db_password ?? '',
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );
        } catch (\PDOException $e) {
            $message = match ($e->getCode()) {
                1049    => "Database \"{$request->db_name}\" does not exist. Please create it first.",
                1045    => 'Access denied. Please check your username and password.',
                2002    => "Cannot connect to \"{$request->db_host}:{$request->db_port}\". Please check host and port.",
                default => 'Connection failed: ' . $e->getMessage(),
            };

            return response()->json(['success' => false, 'message' => $message], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Database connection successful.',
        ]);
    }

    /**
     * AJAX: Run full installation (migrate, seed, create admin)
     */
    public function runInstall(Request $request)
    {
        @set_time_limit(300);
        @ini_set('max_execution_time', '300');

        $validator = Validator::make($request->all(), [
            'db_host'               => 'required|string',
            'db_port'               => 'required|string',
            'db_name'               => 'required|string',
            'db_user'               => 'required|string',
            'db_password'           => 'nullable|string',
            'site_name'             => 'required|string|max:255',
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|email|max:255',
            'password'              => array_merge(['required', 'string'], password_rules(), ['confirmed']),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        // 1. Test DB connection
        try {
            new \PDO(
                "mysql:host={$request->db_host};port={$request->db_port};dbname={$request->db_name}",
                $request->db_user,
                $request->db_password ?? '',
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );
        } catch (\PDOException $e) {
            $message = match ($e->getCode()) {
                1049    => "Database \"{$request->db_name}\" does not exist. Please create it first.",
                1045    => 'Access denied. Please check your database username and password.',
                2002    => "Cannot connect to \"{$request->db_host}:{$request->db_port}\". Please check host and port.",
                default => 'Connection failed: ' . $e->getMessage(),
            };

            return response()->json(['success' => false, 'message' => $message], 422);
        }

        $host = $request->getHost();
        $isLocal = in_array($host, ['localhost', '127.0.0.1', '::1'])
            || str_ends_with($host, '.test')
            || str_ends_with($host, '.local');

        $appEnv = $isLocal ? 'local' : (env('APP_ENV') ?: 'production');
        $appDebug = $isLocal ? 'true' : (env('APP_DEBUG') ? 'true' : 'false');

        // 2. Update .env
        $this->updateEnv([
            'APP_NAME'       => '"' . $request->site_name . '"',
            'APP_ENV'        => $appEnv,
            'APP_DEBUG'      => $appDebug,
            'APP_INSTALLED'  => 'false',   // will be set to true after successful install
            'APP_URL'        => $request->root(),
            'DB_HOST'        => $request->db_host,
            'DB_PORT'        => $request->db_port,
            'DB_DATABASE'    => $request->db_name,
            'DB_USERNAME'    => $request->db_user,
            'DB_PASSWORD'    => $request->db_password ?? '',
            'CACHE_STORE'    => 'file',
            'CACHE_DRIVER'   => 'file',
            'SESSION_DRIVER' => 'file',
        ]);

        // 3. Reconnect with new DB config
        config([
            'database.connections.mysql.host'     => $request->db_host,
            'database.connections.mysql.port'     => $request->db_port,
            'database.connections.mysql.database'  => $request->db_name,
            'database.connections.mysql.username'  => $request->db_user,
            'database.connections.mysql.password'  => $request->db_password ?? '',
        ]);

        DB::purge('mysql');
        DB::reconnect('mysql');

        // 4. Run migrations
        try {
            Artisan::call('migrate', ['--force' => true]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Migration failed: ' . $e->getMessage(),
            ], 500);
        }

        // 5. Run seeders
        try {
            Artisan::call('db:seed', ['--force' => true]);

            if ($request->boolean('demo_data')) {
                Artisan::call('db:seed', [
                    '--class' => 'Database\\Seeders\\DemoSeeder',
                    '--force' => true,
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Seeding failed: ' . $e->getMessage(),
            ], 500);
        }

        // 6. Create admin user
        try {
            $user = \App\Models\User::first();
            $user->update([
                'name'     => ['en' => $request->name],
                'email'    => $request->email,
                'password' => Hash::make($request->password),
            ]);

            \App\Models\Setting::where('group', 'general')
                ->where('key', 'site_name')
                ->update(['value' => $request->site_name]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Admin setup failed: ' . $e->getMessage(),
            ], 500);
        }

        // 7. Finalize
        try {
            Artisan::call('config:clear');
        } catch (\Throwable) {}

        try {
            Artisan::call('cache:clear');
        } catch (\Throwable) {}

        try {
            Artisan::call('view:clear');
        } catch (\Throwable) {}

        try {
            Artisan::call('route:clear');
        } catch (\Throwable) {}

        try {
            Artisan::call('storage:link');
        } catch (\Throwable) {}

        file_put_contents(storage_path('installed'), json_encode([
            'installed_at'  => now()->toDateTimeString(),
            'version'       => config('app-version.version', '1.0.0'),
            'domain'        => request()->getHost(),
        ]));

        // Mark installed in .env so the installer is bypassed even if storage/installed is missing
        $this->updateEnv(['APP_INSTALLED' => 'true']);

        return response()->json([
            'success' => true,
            'message' => 'Installation completed successfully.',
            'redirect' => route('install.finish'),
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────

    protected function checkRequirements(): array
    {
        return [
            ['name' => 'PHP Version (>= 8.2)',   'status' => version_compare(PHP_VERSION, '8.2.0', '>='), 'current' => PHP_VERSION],
            ['name' => 'BCMath Extension',        'status' => extension_loaded('bcmath'),    'current' => extension_loaded('bcmath') ? 'Enabled' : 'Disabled'],
            ['name' => 'Ctype Extension',         'status' => extension_loaded('ctype'),     'current' => extension_loaded('ctype') ? 'Enabled' : 'Disabled'],
            ['name' => 'JSON Extension',          'status' => extension_loaded('json'),      'current' => extension_loaded('json') ? 'Enabled' : 'Disabled'],
            ['name' => 'Mbstring Extension',      'status' => extension_loaded('mbstring'),  'current' => extension_loaded('mbstring') ? 'Enabled' : 'Disabled'],
            ['name' => 'OpenSSL Extension',       'status' => extension_loaded('openssl'),   'current' => extension_loaded('openssl') ? 'Enabled' : 'Disabled'],
            ['name' => 'PDO Extension',           'status' => extension_loaded('pdo'),       'current' => extension_loaded('pdo') ? 'Enabled' : 'Disabled'],
            ['name' => 'PDO MySQL Extension',     'status' => extension_loaded('pdo_mysql'), 'current' => extension_loaded('pdo_mysql') ? 'Enabled' : 'Disabled'],
            ['name' => 'Tokenizer Extension',     'status' => extension_loaded('tokenizer'), 'current' => extension_loaded('tokenizer') ? 'Enabled' : 'Disabled'],
            ['name' => 'XML Extension',           'status' => extension_loaded('xml'),       'current' => extension_loaded('xml') ? 'Enabled' : 'Disabled'],
            ['name' => 'cURL Extension',          'status' => extension_loaded('curl'),      'current' => extension_loaded('curl') ? 'Enabled' : 'Disabled'],
            ['name' => 'Fileinfo Extension',      'status' => extension_loaded('fileinfo'),  'current' => extension_loaded('fileinfo') ? 'Enabled' : 'Disabled'],
            ['name' => 'GD Extension',            'status' => extension_loaded('gd'),        'current' => extension_loaded('gd') ? 'Enabled' : 'Disabled'],
        ];
    }

    protected function checkPermissions(): array
    {
        $folders = [
            'storage/app',
            'storage/framework',
            'storage/framework/cache',
            'storage/framework/sessions',
            'storage/framework/views',
            'storage/logs',
            'bootstrap/cache',
            'public',
        ];

        return collect($folders)->map(function ($folder) {
            $path = base_path($folder);
            return [
                'folder'     => $folder,
                'status'     => is_writable($path),
                'permission' => substr(sprintf('%o', fileperms($path)), -4),
            ];
        })->toArray();
    }

    protected function updateEnv(array $data): void
    {
        $envPath = base_path('.env');

        if (!file_exists($envPath)) {
            copy(base_path('.env.example'), $envPath);
        }

        $env = file_get_contents($envPath);

        foreach ($data as $key => $value) {
            if (preg_match("/^{$key}=.*/m", $env)) {
                $env = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $env);
            } else {
                $env .= "\n{$key}={$value}";
            }
        }

        file_put_contents($envPath, $env);
    }
}
