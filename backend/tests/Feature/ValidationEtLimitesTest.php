<?php

namespace Tests\Feature;

use App\Models\ParametreSite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreeDonneesBoutique;
use Tests\TestCase;

/**
 * Entrées bornées et validées : pagination, numéros de téléphone, mots de passe,
 * champs libres de l'admin, limitation de débit des actions sensibles.
 */
class ValidationEtLimitesTest extends TestCase
{
    use CreeDonneesBoutique;
    use RefreshDatabase;

    public function test_la_taille_des_pages_est_bornee(): void
    {
        Sanctum::actingAs($this->creerUtilisateur('admin'));
        $this->getJson('/api/v1/admin/users?per_page=100000')->assertOk()->assertJsonPath('data.per_page', 100);
        $this->getJson('/api/v1/admin/commandes?per_page=-5')->assertOk()->assertJsonPath('data.per_page', 1);
        $this->getJson('/api/v1/notifications?per_page=100000')->assertOk()->assertJsonPath('data.per_page', 100);
        // Le formulaire produit charge toutes les catégories d'un coup
        $this->getJson('/api/v1/admin/categories?per_page=200')->assertOk()->assertJsonPath('data.per_page', 200);

        Sanctum::actingAs($this->creerUtilisateur('vendeur'));
        $this->getJson('/api/v1/vendeur/commandes?per_page=100000')->assertOk()->assertJsonPath('data.per_page', 100);
        $this->getJson('/api/v1/vendeur/catalogue/produits?per_page=100000')->assertOk()->assertJsonPath('data.per_page', 100);
    }

    public function test_les_telephones_de_la_commande_sont_valides_et_normalises(): void
    {
        $client = $this->creerUtilisateur('client');
        $vendeur = $this->creerUtilisateur('vendeur');
        $produit = $this->creerProduit($vendeur);
        $adresse = $this->creerAdresse($client, $this->creerQuartier());
        $this->livrerVille($vendeur);

        Sanctum::actingAs($client);
        $this->postJson('/api/v1/panier', ['produit_id' => $produit->id, 'quantite' => 1])->assertCreated();

        $commande = [
            'type_livraison' => 'livraison',
            'adresse_livraison_id' => $adresse->id,
            'moyen_paiement' => 'orange_money',
        ];
        $this->postJson('/api/v1/commandes', [...$commande, 'telephone_livraison' => 'appelez-moi', 'telephone_paiement' => '690000999'])
            ->assertStatus(422)->assertJsonValidationErrors('telephone_livraison');
        $this->postJson('/api/v1/commandes', [...$commande, 'telephone_livraison' => '690000999', 'telephone_paiement' => str_repeat('6', 40)])
            ->assertStatus(422)->assertJsonValidationErrors('telephone_paiement');

        $cree = $this->postJson('/api/v1/commandes', [...$commande, 'telephone_livraison' => '690 00 09 99', 'telephone_paiement' => '237 690-000-998'])
            ->assertCreated();
        $this->assertSame('+237690000999', $cree->json('data.0.telephone_livraison'));
        $this->assertSame('+237690000998', $cree->json('data.0.telephone_paiement'));

        $this->putJson('/api/v1/commandes/'.$cree->json('data.0.id'), ['telephone_livraison' => '12'])
            ->assertStatus(422)->assertJsonValidationErrors('telephone_livraison');
    }

    public function test_changer_de_numero_de_connexion_exige_le_mot_de_passe(): void
    {
        $vendeur = $this->creerUtilisateur('vendeur');
        Sanctum::actingAs($vendeur);

        $this->putJson('/api/v1/auth/profile', ['telephone' => '+237699999999'])
            ->assertStatus(422)->assertJsonValidationErrors('mot_de_passe_actuel');
        $this->putJson('/api/v1/auth/profile', ['telephone' => '+237699999999', 'mot_de_passe_actuel' => 'mauvais'])
            ->assertStatus(422)->assertJsonValidationErrors('mot_de_passe_actuel');
        $this->assertNotSame('+237699999999', $vendeur->fresh()->telephone);

        $this->putJson('/api/v1/auth/profile', ['telephone' => '+237699999999', 'mot_de_passe_actuel' => 'password123'])->assertOk();
        $this->assertSame('+237699999999', $vendeur->fresh()->telephone);

        // Le nom et l'e-mail (pas des identifiants de connexion) restent modifiables directement
        $this->putJson('/api/v1/auth/profile', ['nom_complet' => 'Boutique Jean', 'email' => 'jean@exemple.cm'])->assertOk();
    }

    public function test_un_nouveau_mot_de_passe_fait_au_moins_8_caracteres(): void
    {
        $inscription = ['nom_complet' => 'Nouvelle Cliente', 'telephone' => '690123456'];

        $this->postJson('/api/v1/auth/register', [...$inscription, 'mot_de_passe' => 'abc1234', 'mot_de_passe_confirmation' => 'abc1234'])
            ->assertStatus(422)->assertJsonValidationErrors('mot_de_passe');
        $this->postJson('/api/v1/auth/register', [...$inscription, 'mot_de_passe' => 'abcd1234', 'mot_de_passe_confirmation' => 'abcd1234'])
            ->assertCreated();

        Sanctum::actingAs(User::where('telephone', '+237690123456')->firstOrFail());
        $this->postJson('/api/v1/auth/change-password', [
            'ancien_mot_de_passe' => 'abcd1234',
            'nouveau_mot_de_passe' => 'court12',
            'nouveau_mot_de_passe_confirmation' => 'court12',
        ])->assertStatus(422)->assertJsonValidationErrors('nouveau_mot_de_passe');
    }

    public function test_un_mot_de_passe_deja_hache_n_est_pas_hache_une_seconde_fois(): void
    {
        $client = $this->creerUtilisateur('client', ['mot_de_passe' => Hash::make('secret-deja-hache')]);

        $this->postJson('/api/v1/auth/login', ['telephone' => $client->telephone, 'mot_de_passe' => 'secret-deja-hache'])->assertOk();
    }

    public function test_les_champs_libres_de_l_admin_sont_bornes(): void
    {
        $admin = $this->creerUtilisateur('admin');
        $commande = $this->creerCommande($this->creerUtilisateur('client'), $this->creerUtilisateur('vendeur'));
        $parametre = ParametreSite::create(['cle' => 'slogan', 'valeur' => 'Bonjour', 'type' => 'string']);

        Sanctum::actingAs($admin);
        $this->postJson("/api/v1/admin/commandes/{$commande->id}/confirm-payment", ['reference_paiement' => str_repeat('x', 300)])
            ->assertStatus(422)->assertJsonValidationErrors('reference_paiement');
        $this->putJson("/api/v1/admin/parametres/{$parametre->id}", ['valeur' => ['a' => 1]])
            ->assertStatus(422)->assertJsonValidationErrors('valeur');
    }

    public function test_le_detail_admin_d_un_client_ne_charge_que_ses_dernieres_commandes(): void
    {
        $client = $this->creerUtilisateur('client');
        $vendeur = $this->creerUtilisateur('vendeur');
        for ($i = 0; $i < 7; $i++) {
            $this->creerCommande($client, $vendeur);
        }

        Sanctum::actingAs($this->creerUtilisateur('admin'));
        $this->getJson("/api/v1/admin/users/{$client->id}")
            ->assertOk()
            ->assertJsonCount(5, 'data.commandes')
            ->assertJsonPath('data.commandes_count', 7);
    }

    public function test_la_limite_de_connexion_vaut_pour_toutes_les_ecritures_d_un_numero(): void
    {
        $client = $this->creerUtilisateur('client');
        $local = substr($client->telephone, 4);

        // 10 échecs en variant l'écriture du même numéro : même compteur
        foreach (['+237'.$local, $local, '237'.$local, substr($local, 0, 3).' '.substr($local, 3), '+237 '.$local] as $ecriture) {
            for ($i = 0; $i < 2; $i++) {
                $this->postJson('/api/v1/auth/login', ['telephone' => $ecriture, 'mot_de_passe' => 'mauvais'])->assertStatus(422);
            }
        }

        $this->postJson('/api/v1/auth/login', ['telephone' => $local, 'mot_de_passe' => 'password123'])->assertStatus(429);
    }

    public function test_les_actions_sensibles_sont_limitees_par_utilisateur(): void
    {
        Sanctum::actingAs($this->creerUtilisateur('client'));
        $mauvais = ['ancien_mot_de_passe' => 'faux-mot-de-passe', 'nouveau_mot_de_passe' => 'nouveau-1234', 'nouveau_mot_de_passe_confirmation' => 'nouveau-1234'];

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/change-password', $mauvais)->assertStatus(400);
        }

        $this->postJson('/api/v1/auth/change-password', $mauvais)->assertStatus(429);
    }
}
