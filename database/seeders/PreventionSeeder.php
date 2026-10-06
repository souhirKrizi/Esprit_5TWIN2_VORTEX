<?php

namespace Database\Seeders;

use App\Models\EquipementSensible;
use App\Models\FicheConseil;
use App\Models\QuestionCitoyen;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Données d'exemple du module « Conseils de prévention ».
 * Commande : php artisan db:seed --class=PreventionSeeder (peut être relancée sans créer de doublons).
 */
class PreventionSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->fiches() as $donnees) {
            $gestes = $donnees['gestes'];
            unset($donnees['gestes']);

            $fiche = FicheConseil::updateOrCreate(['titre' => $donnees['titre']], $donnees);

            foreach ($gestes as $ordre => [$moment, $intitule]) {
                $fiche->gestes()->updateOrCreate(['intitule' => $intitule], ['moment' => $moment, 'ordre' => $ordre + 1]);
            }
        }

        $citoyen = User::where('role', 'user')->first();
        if ($citoyen === null) {
            return;
        }

        foreach ([
            ['nom' => 'Frigo de la cuisine', 'type' => 'refrigerateur', 'sensibilite' => 'les_deux', 'autonomie_heures' => 4, 'description' => 'Le frigo contient de l’insuline et s’arrête à chaque coupure de courant.'],
            ['nom' => 'Appareil à oxygène', 'type' => 'appareil_medical', 'sensibilite' => 'coupure', 'autonomie_heures' => 2, 'description' => 'La batterie de secours ne tient que 2 heures pendant les délestages.'],
            ['nom' => 'Box internet', 'type' => 'internet_telephone', 'sensibilite' => 'coupure', 'autonomie_heures' => null, 'description' => null],
        ] as $donnees) {
            $equipement = EquipementSensible::firstOrNew(['user_id' => $citoyen->getKey(), 'nom' => $donnees['nom']]);
            $equipement->fill($donnees);
            $equipement->user_id = $citoyen->getKey();
            $equipement->save();
        }

        $this->question($citoyen, 'Bien s’hydrater pendant la canicule', 'Le thé compte-t-il comme de l’eau ?', 'Oui, mais il est souvent très sucré. Buvez surtout de l’eau et gardez le thé en plus.');
        $this->question($citoyen, 'Se préparer à une coupure de courant', 'Faut-il débrancher le climatiseur pendant une coupure ?', null);
    }

    private function question(User $citoyen, string $titreFiche, string $texte, ?string $reponse): void
    {
        $fiche = FicheConseil::where('titre', $titreFiche)->first();
        if ($fiche === null || QuestionCitoyen::where('fiche_conseil_id', $fiche->getKey())->where('question', $texte)->exists()) {
            return;
        }

        $question = new QuestionCitoyen(['question' => $texte, 'reponse' => $reponse, 'statut' => $reponse ? 'repondue' : 'en_attente']);
        $question->fiche_conseil_id = $fiche->getKey();
        $question->user_id = $citoyen->getKey();
        $question->save();
    }

    private function fiches(): array
    {
        return [
            [
                'titre' => 'Bien s’hydrater pendant la canicule',
                'resume' => 'Buvez souvent, même sans avoir soif, pour éviter la déshydratation.',
                'contenu' => "Quand il fait très chaud, le corps perd beaucoup d’eau en transpirant. La déshydratation donne de la fatigue, des maux de tête et des vertiges.\n\nBuvez environ 1,5 à 2 litres d’eau par jour, par petites quantités. Pensez à faire boire les enfants et les personnes âgées, car ils ressentent moins la soif.",
                'thematique' => 'hydratation',
                'niveau_alerte' => 'jaune',
                'type_equipement' => null,
                'statut' => 'publiee',
                'gestes' => [
                    ['avant', 'Mettre des bouteilles d’eau au frais'],
                    ['pendant', 'Boire un verre d’eau toutes les heures'],
                    ['pendant', 'Manger des fruits riches en eau (pastèque, melon)'],
                    ['pendant', 'Éviter les boissons très sucrées'],
                    ['apres', 'Continuer à bien boire pendant quelques jours'],
                ],
            ],
            [
                'titre' => 'Se préparer à une coupure de courant',
                'resume' => 'Quelques préparatifs simples pour passer une coupure de courant sans stress.',
                'contenu' => "Quand il fait très chaud, beaucoup de climatiseurs fonctionnent en même temps. Le réseau électrique peut être surchargé et des coupures (délestages) peuvent avoir lieu.\n\nGardez vos téléphones chargés, une lampe torche à portée de main et de l’eau fraîche. Pendant la coupure, débranchez les appareils électroniques pour les protéger quand le courant revient.",
                'thematique' => 'coupure_courant',
                'niveau_alerte' => 'orange',
                'type_equipement' => null,
                'statut' => 'publiee',
                'gestes' => [
                    ['avant', 'Charger les téléphones et la batterie externe'],
                    ['avant', 'Préparer une lampe torche avec des piles'],
                    ['pendant', 'Débrancher la télévision et l’ordinateur'],
                    ['pendant', 'Garder le frigo fermé'],
                    ['apres', 'Rebrancher les appareils un par un'],
                ],
            ],
            [
                'titre' => 'Protéger le frigo et les aliments pendant une coupure',
                'resume' => 'Un frigo fermé garde le froid environ 4 heures. Voici comment éviter de perdre vos aliments.',
                'contenu' => "Un frigo fermé garde le froid environ 4 heures, un congélateur plein jusqu’à 48 heures. Chaque ouverture fait perdre du froid.\n\nAu retour du courant, jetez la viande, le poisson et les produits laitiers restés plus de 2 heures au chaud.",
                'thematique' => 'equipements',
                'niveau_alerte' => 'jaune',
                'type_equipement' => 'refrigerateur',
                'statut' => 'publiee',
                'gestes' => [
                    ['avant', 'Remplir le congélateur avec des bouteilles d’eau'],
                    ['pendant', 'Ouvrir le frigo le moins possible'],
                    ['apres', 'Jeter les aliments restés trop longtemps au chaud'],
                ],
            ],
            [
                'titre' => 'Appareil médical et coupure de courant',
                'resume' => 'Si un proche utilise un appareil médical électrique, préparez une solution de secours.',
                'contenu' => "Certains appareils médicaux (appareil à oxygène, pompe…) fonctionnent sur le courant. Une coupure peut être dangereuse.\n\nDemandez à votre prestataire l’autonomie de la batterie, gardez son numéro près de l’appareil et prévenez un proche qui peut vous aider.",
                'thematique' => 'equipements',
                'niveau_alerte' => 'orange',
                'type_equipement' => 'appareil_medical',
                'statut' => 'publiee',
                'gestes' => [
                    ['avant', 'Vérifier que la batterie de secours est chargée'],
                    ['avant', 'Noter le numéro du prestataire près de l’appareil'],
                    ['pendant', 'Appeler le prestataire si la coupure dure'],
                ],
            ],
            [
                'titre' => 'Utiliser le climatiseur sans gaspiller',
                'resume' => 'Régler la climatisation à 26 °C aide à éviter les coupures dans le quartier.',
                'contenu' => "Chaque degré en moins sur le climatiseur augmente beaucoup la consommation d’électricité. Réglez-le autour de 26 °C et fermez les portes et fenêtres de la pièce.\n\nUtilisez le four et la machine à laver le soir plutôt que l’après-midi.",
                'thematique' => 'economie_energie',
                'niveau_alerte' => 'jaune',
                'type_equipement' => 'climatiseur',
                'statut' => 'publiee',
                'gestes' => [
                    ['pendant', 'Régler le climatiseur sur 26 °C'],
                    ['pendant', 'Fermer portes et fenêtres de la pièce climatisée'],
                ],
            ],
            [
                'titre' => 'Garder la maison au frais',
                'resume' => 'Fermez les volets le jour et aérez la nuit pour garder la fraîcheur.',
                'contenu' => "Fermez volets et rideaux dès le matin du côté du soleil. Aérez la nuit et tôt le matin, quand l’air est plus frais.\n\nUn linge humide devant une fenêtre ouverte aide aussi à rafraîchir la pièce.",
                'thematique' => 'logement',
                'niveau_alerte' => 'vert',
                'type_equipement' => null,
                'statut' => 'publiee',
                'gestes' => [
                    ['pendant', 'Fermer les volets le jour'],
                    ['pendant', 'Aérer la nuit'],
                ],
            ],
            [
                'titre' => 'Prendre des nouvelles des personnes âgées',
                'resume' => 'Les personnes âgées supportent mal la chaleur. Un appel par jour peut les aider.',
                'contenu' => "Avec l’âge, on ressent moins la chaleur et la soif. Les personnes âgées qui vivent seules sont les plus exposées.\n\nAppelez ou rendez visite à vos voisins âgés chaque jour pendant la canicule. Si une personne a la peau chaude et sèche ou semble perdue, appelez les secours.",
                'thematique' => 'personnes_fragiles',
                'niveau_alerte' => 'orange',
                'type_equipement' => null,
                'statut' => 'publiee',
                'gestes' => [
                    ['avant', 'Noter les voisins âgés qui vivent seuls'],
                    ['pendant', 'Prendre de leurs nouvelles chaque jour'],
                ],
            ],
            [
                'titre' => 'Box internet pendant une coupure',
                'resume' => 'Sans courant, la box s’arrête : gardez votre téléphone portable chargé.',
                'contenu' => "La box internet et le téléphone fixe s’arrêtent pendant une coupure. Le téléphone portable devient le seul moyen d’appeler.\n\nGardez une batterie externe chargée et notez les numéros importants sur papier.",
                'thematique' => 'equipements',
                'niveau_alerte' => 'vert',
                'type_equipement' => 'internet_telephone',
                'statut' => 'brouillon',
                'gestes' => [
                    ['avant', 'Noter les numéros importants sur papier'],
                ],
            ],
        ];
    }
}
