<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Replay;
use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Contenu de départ repris de l'ancien site WordPress (minefold.fr/lanparty).
 * Peut être relancé sans créer de doublons.
 */
class MinefoldContentSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['twitch_channel' => 'minefold_webtv', 'player_mode' => 'auto', 'fallback_label' => 'Minefold TV'] as $key => $value) {
            if (Setting::get($key) === null) {
                Setting::put([$key => $value]);
            }
        }

        $event = Event::updateOrCreate(['slug' => 'geek-area-party-2027'], [
            'title' => 'Geek Area Party 2027',
            'tagline' => 'Deux jours de gaming, de partage et de compétition à Bourg-Saint-Andéol.',
            'badges' => ['LAN PARTY', 'MULTIGAMING · BYOC'],
            'starts_at' => '2027-10-09 09:00:00',
            'ends_at' => '2027-10-10 18:00:00',
            'hours_label' => '9 h – 18 h',
            'venue' => 'Foyer Municipal de Bourg-Saint-Andéol',
            'contact_email' => 'contact@minefold.fr',
            'intro' => "Préparez votre matériel, retrouvez vos amis et rejoignez-nous pour un week-end consacré au jeu vidéo.\nAu programme : espace de jeu libre, animations multigaming, défis, rencontres entre passionnés et tournois dédiés.",
            'helloasso_form_type' => 'Event',
            'helloasso_form_slug' => 'geek-area-party',
            'challenge_title' => 'Challenge Multigaming en duo',
            'challenge_text' => 'Pour les joueurs libres : formez un duo, enchaînez les épreuves sur les deux jours et cumulez des points pour le classement général. Le freeplay reste ouvert en parallèle : participer n’est pas obligatoire.',
            'challenge_games' => 'Trackmania, Minecraft, jeux coop et versus, d’autres annoncés avant l’événement.',
            'challenge_modes' => [
                ['title' => 'Défis individuels', 'text' => 'Chacun marque des points pour son duo.'],
                ['title' => 'Épreuves coop', 'text' => 'Un objectif commun à atteindre à deux.'],
                ['title' => 'Défis en versus', 'text' => 'Duo contre duo, quand le jeu s’y prête.'],
                ['title' => 'Challenges express', 'text' => 'Mini-tournois courts pour varier les plaisirs.'],
            ],
            'program' => [
                ['day' => 'Samedi 9 octobre', 'subtitle' => 'Accueil et classements', 'items' => [
                    ['time' => '9:00', 'label' => 'Accueil des participants et installation du matériel'],
                    ['time' => '', 'label' => 'Premières rencontres Counter-Strike 2 et Super Smash Bros. Ultimate'],
                    ['time' => '', 'label' => 'Début des animations et du Challenge Multigaming en duo'],
                    ['time' => '', 'label' => 'Freeplay et rencontres entre participants'],
                    ['time' => '', 'label' => 'Classements provisoires en fin de journée'],
                ]],
                ['day' => 'Dimanche 10 octobre', 'subtitle' => 'Finales et récompenses', 'items' => [
                    ['time' => '', 'label' => 'Suite des tournois officiels'],
                    ['time' => '', 'label' => 'Phases finales Counter-Strike 2 et Super Smash Bros. Ultimate'],
                    ['time' => '', 'label' => 'Dernières épreuves du Challenge Multigaming'],
                    ['time' => '', 'label' => 'Classement général des joueurs libres'],
                    ['time' => '', 'label' => 'Remise des récompenses et clôture'],
                ]],
            ],
            'program_note' => 'Les horaires précis seront publiés dans le planning officiel avant la LAN.',
            'byoc_intro' => 'BYOC : Bring Your Own Computer. Pensez à installer et à mettre à jour vos jeux avant votre arrivée.',
            'byoc_items' => ['Ordinateur et alimentation', 'Écran', 'Clavier et souris', 'Casque audio', 'Câbles selon les consignes', 'Jeux installés et à jour'],
            'rewards_intro' => 'Périphériques gaming, goodies, figurines ou autres lots selon les partenariats et dotations confirmés.',
            'rewards' => ['Meilleures équipes Counter-Strike 2', 'Meilleurs joueurs Super Smash Bros. Ultimate', 'Meilleurs duos du Challenge Multigaming', 'Participants de certaines animations'],
            'is_published' => true,
            'is_featured' => true,
        ]);

        if ($event->ticketTypes()->doesntExist()) {
            $event->ticketTypes()->createMany([
                ['sort' => 1, 'tag' => 'JOUEUR', 'name' => 'Joueur libre — PC', 'perks' => ['Accès aux 2 jours', 'Freeplay sur votre PC (BYOC)', 'Animations multigaming', 'Challenge en duo (facultatif)']],
                ['sort' => 2, 'tag' => 'TOURNOI', 'name' => 'Counter-Strike 2 — équipe 5v5', 'capacity_label' => '4 équipes', 'perks' => ['Inscription par équipe de 5', 'Matchs de classement le samedi', 'Phases finales le dimanche']],
                ['sort' => 3, 'tag' => 'TOURNOI', 'name' => 'Super Smash Bros. Ultimate — 1v1', 'capacity' => 10, 'perks' => ['Tournoi individuel sur Nintendo Switch', 'Pas d’élimination dès le samedi', 'Finales le dimanche']],
            ]);
        }

        if ($event->tournaments()->doesntExist()) {
            $event->tournaments()->createMany([
                ['sort' => 1, 'game' => 'Counter-Strike 2', 'format' => 'Tournoi par équipes · 5v5 · PC', 'capacity_value' => '20', 'capacity_label' => 'joueurs · 4 équipes', 'cta_label' => 'Inscrire mon équipe', 'phases' => [
                    ['label' => 'Samedi', 'title' => 'Matchs de classement', 'text' => 'Aucune équipe éliminée le premier jour.'],
                    ['label' => 'Dimanche', 'title' => 'Phases finales', 'text' => 'Matchs de classement pour toutes les équipes.'],
                ]],
                ['sort' => 2, 'game' => 'Super Smash Bros. Ultimate', 'format' => 'Tournoi individuel · 1v1 · Nintendo Switch', 'capacity_value' => '10', 'capacity_label' => 'joueurs', 'cta_label' => 'M’inscrire', 'phases' => [
                    ['label' => 'Samedi', 'title' => 'Premières rencontres', 'text' => 'Format pensé pour ne pas éliminer dès le samedi.'],
                    ['label' => 'Dimanche', 'title' => 'Suite et finales', 'text' => 'Matchs complémentaires selon le format retenu.'],
                ]],
            ]);
        }

        foreach (['LAN Party 2022', 'LAN Party Halloween Édition', 'LAN Party 2020', 'LAN Party 2019'] as $i => $title) {
            Replay::firstOrCreate(['title' => $title], ['kind' => 'Souvenirs de LAN', 'sort' => $i]);
        }
    }
}
