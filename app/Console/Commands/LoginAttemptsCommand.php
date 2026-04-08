<?php

namespace App\Console\Commands;

use App\Models\LoginAttempt;
use Illuminate\Console\Command;

class LoginAttemptsCommand extends Command
{
    protected $signature = 'security:login-attempts
        {--limit=30 : Number of rows to display}
        {--honeypot : Only show honeypot hits}
        {--ip= : Filter by IP}
        {--since= : Only attempts since (e.g. "1 hour ago", "2026-04-01")}
        {--top-ips : Show top offending IPs instead of the raw list}';

    protected $description = 'Display recent login attempts (honeypot, invalid, success).';

    public function handle(): int
    {
        $query = LoginAttempt::query()->orderByDesc('created_at');

        if ($this->option('honeypot')) {
            $query->where('is_honeypot', true);
        }

        if ($ip = $this->option('ip')) {
            $query->where('ip', $ip);
        }

        if ($since = $this->option('since')) {
            try {
                $query->where('created_at', '>=', \Carbon\Carbon::parse($since));
            } catch (\Throwable $e) {
                $this->error("Date invalide: {$since}");
                return self::FAILURE;
            }
        }

        if ($this->option('top-ips')) {
            $rows = (clone $query)
                ->selectRaw('ip, count(*) as hits, sum(case when is_honeypot then 1 else 0 end) as honeypot_hits, max(created_at) as last_seen')
                ->groupBy('ip')
                ->orderByDesc('hits')
                ->limit((int) $this->option('limit'))
                ->get()
                ->map(fn ($r) => [
                    'ip' => $r->ip,
                    'hits' => $r->hits,
                    'honeypot' => $r->honeypot_hits,
                    'last_seen' => (string) $r->last_seen,
                ])
                ->all();

            $this->table(['IP', 'Tentatives', 'Honeypot', 'Dernière'], $rows);
            return self::SUCCESS;
        }

        $rows = $query->limit((int) $this->option('limit'))
            ->get(['created_at', 'ip', 'reason', 'username', 'user_agent', 'is_honeypot'])
            ->map(fn ($a) => [
                'date'     => (string) $a->created_at,
                'ip'       => $a->ip,
                'reason'   => $a->is_honeypot ? "🍯 {$a->reason}" : $a->reason,
                'username' => $a->username,
                'ua'       => substr((string) $a->user_agent, 0, 50),
            ])
            ->all();

        $this->table(['Date', 'IP', 'Raison', 'Username', 'User-Agent'], $rows);
        $this->line('Total affiché : '.count($rows));

        return self::SUCCESS;
    }
}
