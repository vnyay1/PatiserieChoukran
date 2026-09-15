<?php

namespace Tests\Feature;

use App\Models\ParametreSite;
use App\Services\Images;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreeDonneesBoutique;
use Tests\TestCase;

class PerformancesTest extends TestCase
{
    use CreeDonneesBoutique;
    use RefreshDatabase;

    public function test_une_grande_photo_est_redimensionnee_et_convertie_en_webp(): void
    {
        $chemin = Images::enregistrer(UploadedFile::fake()->image('gateau.jpg', 3000, 2000), 'produits');

        $this->assertStringEndsWith('.webp', $chemin);
        Storage::disk('public')->assertExists($chemin);
        [$largeur, $hauteur, $type] = getimagesizefromstring(Storage::disk('public')->get($chemin));
        $this->assertSame([1200, 800, IMAGETYPE_WEBP], [$largeur, $hauteur, $type]);

        // Petite image : convertie sans être agrandie
        $logo = Images::enregistrer(UploadedFile::fake()->image('logo.png', 200, 150), 'boutiques', 400);
        $this->assertSame([200, 150], array_slice(getimagesizefromstring(Storage::disk('public')->get($logo)), 0, 2));

        // Fichier illisible par GD : conservé tel quel
        $brut = Images::enregistrer(UploadedFile::fake()->create('photo.jpg', 10, 'image/jpeg'), 'produits');
        $this->assertStringEndsWith('.jpg', $brut);
    }

    public function test_last_used_at_n_est_ecrit_qu_une_fois_toutes_les_cinq_minutes(): void
    {
        $client = $this->creerUtilisateur('client');
        $jeton = $client->createToken('test')->plainTextToken;
        $entetes = ['Authorization' => "Bearer {$jeton}"];

        // Le garde garde l'utilisateur en mémoire d'une requête de test à l'autre : on le vide
        $requete = function () use ($entetes) {
            $this->app['auth']->forgetGuards();
            $this->getJson('/api/v1/auth/user', $entetes)->assertOk();
        };

        $requete();
        $premier = DB::table('personal_access_tokens')->value('last_used_at');
        $this->assertNotNull($premier);

        $this->travel(2)->minutes();
        $requete();
        $this->assertSame($premier, DB::table('personal_access_tokens')->value('last_used_at'));

        $this->travel(4)->minutes();
        $requete();
        $this->assertNotSame($premier, DB::table('personal_access_tokens')->value('last_used_at'));
    }

    public function test_les_parametres_sont_lus_en_cache_et_rafraichis_a_la_modification(): void
    {
        ParametreSite::set('frais_livraison_standard', '1500', 'integer');
        $this->assertSame(1500, ParametreSite::get('frais_livraison_standard'));

        DB::enableQueryLog();
        $this->assertSame(1500, ParametreSite::get('frais_livraison_standard'));
        $this->assertSame('défaut', ParametreSite::get('cle_inconnue', 'défaut'));
        $this->assertCount(0, DB::getQueryLog());

        ParametreSite::set('frais_livraison_standard', '2000', 'integer');
        $this->assertSame(2000, ParametreSite::get('frais_livraison_standard'));

        ParametreSite::where('cle', 'frais_livraison_standard')->first()->delete();
        $this->assertSame(1500, ParametreSite::get('frais_livraison_standard', 1500));
    }
}
