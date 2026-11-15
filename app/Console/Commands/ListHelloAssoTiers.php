<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Services\HelloAsso;
use Illuminate\Console\Command;
use Throwable;

class ListHelloAssoTiers extends Command
{
    protected $signature = 'helloasso:tiers';

    protected $description = 'Affiche les tarifs HelloAsso (ID, nom, prix, places) des événements, pour les relier aux billets';

    public function handle(HelloAsso $helloAsso): int
    {
        if (! $helloAsso->configured()) {
            $this->error('HelloAsso non configuré (HELLOASSO_CLIENT_ID, HELLOASSO_CLIENT_SECRET, HELLOASSO_ORGANIZATION).');

            return self::FAILURE;
        }

        $events = Event::whereNotNull('helloasso_form_slug')->orderByDesc('starts_at')->get();
        if ($events->isEmpty()) {
            $this->warn('Aucun événement n’a de slug de formulaire HelloAsso.');

            return self::SUCCESS;
        }

        foreach ($events as $event) {
            $this->newLine();
            $this->info("{$event->title} (formulaire « {$event->helloasso_form_slug} »)");
            try {
                $tiers = $helloAsso->tierStats($event->helloasso_form_type ?: 'Event', $event->helloasso_form_slug);
            } catch (Throwable $e) {
                $this->error('   Impossible de lire ce formulaire : '.$e->getMessage());

                continue;
            }
            $this->table(['ID du tarif', 'Nom', 'Prix', 'Vendus', 'Places max'], collect($tiers)->map(fn (array $tier): array => [
                $tier['id'],
                $tier['label'] ?? '',
                $tier['price'] === null ? '' : number_format($tier['price'] / 100, 2, ',', ' ').' €',
                $tier['entriesTaken'],
                $tier['maxEntries'] ?? '—',
            ])->values()->all());
        }

        $this->line('Reportez chaque « ID du tarif » dans Admin › Événements › Billets › ID du tarif HelloAsso.');

        return self::SUCCESS;
    }
}
