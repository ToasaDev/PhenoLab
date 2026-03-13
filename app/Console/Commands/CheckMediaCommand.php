<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CheckMediaCommand extends Command
{
    protected $signature = 'media:check';
    protected $description = "Vérifier l'intégrité du répertoire médias";

    public function handle(): int
    {
        $this->info('Vérification du répertoire médias...');

        $dirs = [
            'photos/plants',
            'photos/observations',
            'site_plans',
        ];

        $disk = Storage::disk('public');
        $ok = true;

        foreach ($dirs as $dir) {
            if (!$disk->exists($dir)) {
                $disk->makeDirectory($dir);
                $this->warn("  Créé : storage/app/public/{$dir}");
            } else {
                $this->info("  OK : storage/app/public/{$dir}");
            }
        }

        // Check symlink
        $publicPath = public_path('storage');
        if (!file_exists($publicPath)) {
            $this->warn('  Le lien symbolique public/storage est manquant.');
            $this->info('  Exécutez : php artisan storage:link');
            $ok = false;
        } else {
            $this->info('  OK : lien symbolique public/storage');
        }

        // Check write permissions
        $testFile = 'test_write_' . time() . '.tmp';
        try {
            $disk->put($testFile, 'test');
            $disk->delete($testFile);
            $this->info('  OK : permissions d\'écriture');
        } catch (\Throwable $e) {
            $this->error('  ERREUR : pas de permissions d\'écriture - ' . $e->getMessage());
            $ok = false;
        }

        $this->newLine();
        $this->info($ok ? 'Tout est en ordre !' : 'Des corrections sont nécessaires.');

        return $ok ? Command::SUCCESS : Command::FAILURE;
    }
}
