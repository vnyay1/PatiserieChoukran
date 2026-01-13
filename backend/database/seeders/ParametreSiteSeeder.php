<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ParametreSite;

class ParametreSiteSeeder extends Seeder
{
    public function run(): void
    {
        $parametres = [
            [
                'cle' => 'nom_site',
                'valeur' => 'Pâtisserie Délices',
                'type' => 'string',
                'description' => 'Nom de la pâtisserie',
                'groupe' => 'general',
            ],
            [
                'cle' => 'telephone_contact',
                'valeur' => '+237699123456',
                'type' => 'string',
                'description' => 'Numéro de téléphone principal',
                'groupe' => 'general',
            ],
            [
                'cle' => 'email_contact',
                'valeur' => 'contact@patisserie.cm',
                'type' => 'string',
                'description' => 'Email de contact',
                'groupe' => 'general',
            ],
            [
                'cle' => 'adresse_boutique',
                'valeur' => 'Akwa, Douala - Cameroun',
                'type' => 'string',
                'description' => 'Adresse de la boutique',
                'groupe' => 'general',
            ],
            [
                'cle' => 'horaires_ouverture',
                'valeur' => json_encode([
                    'lundi' => '08:00-18:00',
                    'mardi' => '08:00-18:00',
                    'mercredi' => '08:00-18:00',
                    'jeudi' => '08:00-18:00',
                    'vendredi' => '08:00-18:00',
                    'samedi' => '09:00-17:00',
                    'dimanche' => 'Fermé',
                ]),
                'type' => 'json',
                'description' => 'Horaires d\'ouverture',
                'groupe' => 'general',
            ],
            [
                'cle' => 'frais_livraison_defaut',
                'valeur' => '1000',
                'type' => 'integer',
                'description' => 'Frais de livraison par défaut (XAF)',
                'groupe' => 'livraison',
            ],
            [
                'cle' => 'delai_preparation_defaut',
                'valeur' => '24',
                'type' => 'integer',
                'description' => 'Délai de préparation en heures',
                'groupe' => 'livraison',
            ],
            [
                'cle' => 'panier_expiration_heures',
                'valeur' => '24',
                'type' => 'integer',
                'description' => 'Durée avant expiration du panier',
                'groupe' => 'panier',
            ],
            [
                'cle' => 'orange_money_numero',
                'valeur' => '+237699123456',
                'type' => 'string',
                'description' => 'Numéro Orange Money',
                'groupe' => 'paiement',
            ],
            [
                'cle' => 'mtn_momo_numero',
                'valeur' => '+237677123456',
                'type' => 'string',
                'description' => 'Numéro MTN Mobile Money',
                'groupe' => 'paiement',
            ],
        ];

        foreach ($parametres as $parametre) {
            ParametreSite::create($parametre);
        }
    }
}