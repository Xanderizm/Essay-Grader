<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('accounts:purge')]
#[Description('Command description')]
class PurgeAccounts extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(\App\Services\GoogleClient $google): int
    {
        $users = \App\Models\User::whereNotNull('deletion_requested_at')
            ->where('deletion_requested_at', '<=', now()->subDays(14))->get();

        foreach ($users as $user) {
            $google->disconnect($user);
            $user->delete();
        }

        $this->info($users->count() . ' akun dihapus.');

        return self::SUCCESS;
    }
}
