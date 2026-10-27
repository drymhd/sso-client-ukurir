<?php

namespace Saitrans\SsoClient\Console\Commands;

use Illuminate\Console\Command;

class InstallSsoClientCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sso:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Instalasi dan konfigurasi otomatis SSO Client SDK untuk aplikasi ini';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('====================================================');
        $this->info('  🚀 Konfigurasi SAI SSO Client SDK (Versi 3.0)     ');
        $this->info('====================================================');

        // 1. Publish Config File
        $this->comment('1. Menerbitkan file konfigurasi...');
        $this->call('vendor:publish', [
            '--provider' => 'Saitrans\\SsoClient\\SsoClientServiceProvider',
            '--tag'      => 'sso-client-config',
            '--force'    => true,
        ]);

        // 2. Tanya Pengaturan Lingkungan
        $serverUrl = $this->ask('Masukkan URL SSO Server pusat', config('sso-client.server_url', 'http://localhost:8000'));
        $appKey = $this->ask('Masukkan SSO App Key aplikasi ini (terdaftar di dashboard SSO)');
        $clientSecret = $this->secret('Masukkan OAuth Client Secret aplikasi ini');
        $redirectRoute = $this->ask('Nama rute tujuan setelah login berhasil (contoh: home atau dashboard)', 'home');

        // 3. Update file .env
        $this->comment('2. Memperbarui file .env...');
        $this->updateEnvFile([
            'SSO_SERVER_URL'     => $serverUrl,
            'SSO_APP_KEY'        => $appKey,
            'SSO_CLIENT_SECRET'  => $clientSecret,
            'SSO_REDIRECT_ROUTE' => $redirectRoute,
            'SSO_AUTO_PROVISION' => 'true',
        ]);

        $this->newLine();
        $this->info('✅ Instalasi SSO Client SDK Berhasil!');
        $this->line('Rute otomatis yang telah aktif:');
        $this->line(' - Login Redirect : <info>' . url('/sso/login') . '</info>');
        $this->line(' - Callback URL   : <info>' . url('/sso/callback') . '</info>');
        $this->line(' - Logout         : <info>' . url('/sso/logout') . '</info>');
        $this->newLine();
        $this->line('Anda dapat memproteksi rute menggunakan middleware: <comment>middleware(\'sso.auth\')</comment>');
        $this->info('====================================================');

        return Command::SUCCESS;
    }

    /**
     * Perbarui variabel di file .env lokal.
     *
     * @param array $data
     */
    protected function updateEnvFile(array $data)
    {
        $envPath = base_path('.env');

        if (!file_exists($envPath)) {
            $this->warn('File .env tidak ditemukan. Silakan tambahkan variabel secara manual.');
            return;
        }

        $envContent = file_get_contents($envPath);

        foreach ($data as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            // Jika key sudah ada di .env, replace
            if (preg_match("/^{$key}=.*/m", $envContent)) {
                $envContent = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $envContent);
            } else {
                // Tambahkan di baris paling bawah
                $envContent .= "\n{$key}={$value}";
            }
        }

        file_put_contents($envPath, $envContent);
    }
}
