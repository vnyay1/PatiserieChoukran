<?php

namespace Tests\Feature;

use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreeDonneesBoutique;
use Tests\TestCase;

class ComptesEtNotificationsTest extends TestCase
{
    use CreeDonneesBoutique;
    use RefreshDatabase;

    public function test_supprimer_les_notifications_lues_atteint_la_bonne_route(): void
    {
        $client = $this->creerUtilisateur('client');
        foreach ([true, true, false] as $lue) {
            Notification::create([
                'user_id' => $client->id,
                'titre' => 'Info',
                'message' => 'Message',
                'type' => 'commande',
                'est_lu' => $lue,
            ]);
        }

        Sanctum::actingAs($client);

        $this->deleteJson('/api/v1/notifications/clear-read')->assertOk()->assertJsonPath('success', true);
        $this->assertSame(1, Notification::where('user_id', $client->id)->count());
    }

    public function test_un_compte_suspendu_ne_peut_plus_utiliser_son_token(): void
    {
        $client = $this->creerUtilisateur('client');
        $token = $client->createToken('test')->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/auth/user')->assertOk();

        $client->update(['statut' => 'suspendu']);
        $this->app['auth']->forgetGuards();

        $this->withToken($token)->getJson('/api/v1/auth/user')
            ->assertStatus(401)
            ->assertJsonPath('message', 'Votre compte a été suspendu. Contactez l\'administrateur.');

        $this->assertSame(0, $client->tokens()->count());
    }

    public function test_un_admin_ne_peut_pas_se_suspendre_et_la_suspension_deconnecte(): void
    {
        $admin = $this->creerUtilisateur('admin');
        $vendeur = $this->creerUtilisateur('vendeur');
        $vendeur->createToken('telephone');

        Sanctum::actingAs($admin);

        $this->patchJson("/api/v1/admin/users/{$admin->id}/status", ['statut' => 'suspendu'])
            ->assertStatus(400);
        $this->assertSame('actif', $admin->fresh()->statut);

        $this->patchJson("/api/v1/admin/users/{$vendeur->id}/status", ['statut' => 'suspendu'])->assertOk();
        $this->assertSame(0, $vendeur->tokens()->count());
    }
}
