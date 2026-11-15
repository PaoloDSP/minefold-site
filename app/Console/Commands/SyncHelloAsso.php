<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Services\HelloAsso;
use Illuminate\Console\Command;
use Throwable;

class SyncHelloAsso extends Command
{
    protected $signature = 'helloasso:sync';

    protected $description = 'Met à jour les places vendues / restantes depuis HelloAsso';

    public function handle(HelloAsso $helloAsso): int
    {
        if (! $helloAsso->configured()) {
            $this->warn('HelloAsso non configuré (HELLOASSO_CLIENT_ID, HELLOASSO_CLIENT_SECRET, HELLOASSO_ORGANIZATION).');

            return self::SUCCESS;
        }

        $events = Event::published()->whereNotNull('helloasso_form_slug')
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()->subDay()))
            ->get();

        $failed = false;
        foreach ($events as $event) {
            try {
                $n = $helloAsso->syncEvent($event);
                $this->info("{$event->title} : {$n} billet(s) mis à jour.");
            } catch (Throwable $e) {
                $failed = true;
                $this->error("{$event->title} : ".$e->getMessage());
                report($e);
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
