<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\AuthController;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RotateAdminCommand extends Command
{
    protected $signature = 'security:rotate-admin
        {--name= : New admin username (default: random)}
        {--password= : New admin password (default: random 24 chars)}';

    protected $description = 'Rename the legacy admin account and rotate its password to defeat the honeypot leak.';

    public function handle(): int
    {
        $honeypotNames = [
            'admin', 'administrator', 'root', 'superuser', 'sysadmin',
            'test', 'demo', 'user', 'guest', 'webmaster', 'phenolab', 'manager',
        ];

        $admin = User::whereIn('name', $honeypotNames)
            ->orWhere('is_superuser', true)
            ->orderBy('id')
            ->first();

        if (! $admin) {
            $this->error('Aucun compte admin trouvé.');
            return self::FAILURE;
        }

        $oldName = $admin->name;
        $newName = $this->option('name') ?: 'phenolab_'.Str::lower(Str::random(8));
        $newPassword = $this->option('password') ?: Str::random(24);

        // Make sure the new name itself is not in the honeypot list.
        if (in_array(strtolower($newName), $honeypotNames, true)) {
            $this->error("Le nom « {$newName} » fait partie du honeypot. Choisis-en un autre.");
            return self::FAILURE;
        }

        $admin->name = $newName;
        $admin->password = Hash::make($newPassword);
        $admin->save();

        $this->newLine();
        $this->info('✅ Compte admin rotaté.');
        $this->line("Ancien nom : {$oldName}");
        $this->line("Nouveau nom : <fg=cyan>{$newName}</>");
        $this->line("Nouveau mot de passe : <fg=cyan>{$newPassword}</>");
        $this->newLine();
        $this->warn('⚠️  Note ces credentials maintenant — ils ne seront plus affichés.');
        $this->warn('⚠️  Le honeypot admin/admin123 reste affiché dans l\'UI comme leurre.');

        return self::SUCCESS;
    }
}
