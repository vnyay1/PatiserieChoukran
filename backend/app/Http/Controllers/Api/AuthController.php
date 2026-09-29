<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Telephone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Inscription d'un nouvel utilisateur
     */
    public function register(Request $request)
    {
        $request->merge([
            'telephone' => Telephone::normaliser($request->telephone),
        ]);

        $validated = $request->validate([
            'nom_complet' => 'required|string|max:255',
            'telephone' => ['required', 'string', 'unique:users,telephone', Telephone::REGLE],
            'email' => 'nullable|email|unique:users,email',
            'mot_de_passe' => ['required', 'string', 'confirmed', Password::min(8)],
        ]);

        $user = User::create([
            'nom_complet' => $validated['nom_complet'],
            'telephone' => $validated['telephone'],
            'email' => $validated['email'] ?? null,
            'mot_de_passe' => $validated['mot_de_passe'],
            'role' => 'client',
            'statut' => 'actif',
        ]);

        // Créer le token d'authentification
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Inscription réussie',
            'data' => [
                'user' => $user,
                'token' => $token,
            ],
        ], 201);
    }

    /**
     * Connexion utilisateur
     */
    public function login(Request $request)
    {
        $request->merge([
            'telephone' => Telephone::normaliser($request->telephone),
        ]);

        $request->validate([
            'telephone' => 'required|string',
            'mot_de_passe' => 'required|string',
        ]);

        // Chercher l'utilisateur par téléphone
        $user = User::where('telephone', $request->telephone)->first();

        // Vérifier si l'utilisateur existe et si le mot de passe est correct
        if (! $user || ! Hash::check($request->mot_de_passe, $user->mot_de_passe)) {
            throw ValidationException::withMessages([
                'telephone' => ['Les identifiants fournis sont incorrects.'],
            ]);
        }

        // Vérifier si le compte est actif
        if ($user->statut !== 'actif') {
            return response()->json([
                'success' => false,
                'message' => 'Votre compte a été suspendu. Contactez l\'administrateur.',
            ], 403);
        }

        // Supprimer les anciens tokens
        $user->tokens()->delete();

        // Créer un nouveau token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Connexion réussie',
            'data' => [
                'user' => $user,
                'token' => $token,
            ],
        ]);
    }

    /**
     * Déconnexion
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Déconnexion réussie',
        ]);
    }

    /**
     * Récupérer les informations de l'utilisateur connecté
     */
    public function user(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $request->user()->load(['adresses', 'adressePrincipale']),
        ]);
    }

    /**
     * Mettre à jour le profil
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        if ($request->has('telephone')) {
            $request->merge([
                'telephone' => Telephone::normaliser($request->telephone),
            ]);
        }

        if ($request->has('email')) {
            $email = trim((string) $request->email);
            $request->merge([
                'email' => $email === '' ? null : $email,
            ]);
        }

        if ($user->role === 'client') {
            $telephoneChanged = $request->has('telephone')
                && $request->input('telephone') !== $user->telephone;
            $emailChanged = $request->has('email')
                && $request->input('email') !== $user->email;

            if ($telephoneChanged || $emailChanged) {
                return response()->json([
                    'success' => false,
                    'message' => 'Les clients ne peuvent pas modifier leur email ou numéro de téléphone.',
                ], 422);
            }
        }

        $validated = $request->validate([
            'nom_complet' => 'sometimes|string|max:255',
            // L'e-mail fait partie du profil boutique obligatoire d'un vendeur
            'email' => ($user->isVendeur() ? 'sometimes|required' : 'sometimes|nullable').'|email|unique:users,email,'.$user->id,
            'telephone' => ['sometimes', 'string', 'unique:users,telephone,'.$user->id, Telephone::REGLE],
        ]);

        // Le téléphone sert à se connecter : un jeton volé ne doit pas suffire à en changer
        // (le vrai titulaire se retrouverait enfermé dehors)
        if (isset($validated['telephone']) && $validated['telephone'] !== $user->telephone) {
            $request->validate([
                'mot_de_passe_actuel' => ['required', 'string', function (string $attribut, mixed $valeur, \Closure $echec) use ($user) {
                    if (! Hash::check($valeur, $user->mot_de_passe)) {
                        $echec('Le mot de passe actuel est incorrect.');
                    }
                }],
            ], [
                'mot_de_passe_actuel.required' => 'Saisissez votre mot de passe actuel pour changer de numéro de téléphone.',
            ]);
        }

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Profil mis à jour avec succès',
            'data' => $user,
        ]);
    }

    /**
     * Changer le mot de passe
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'ancien_mot_de_passe' => 'required|string',
            'nouveau_mot_de_passe' => ['required', 'string', 'confirmed', Password::min(8)],
        ]);

        $user = $request->user();

        // Vérifier l'ancien mot de passe
        if (! Hash::check($request->ancien_mot_de_passe, $user->mot_de_passe)) {
            return response()->json([
                'success' => false,
                'message' => 'L\'ancien mot de passe est incorrect',
            ], 400);
        }

        $user->update([
            'mot_de_passe' => $request->nouveau_mot_de_passe,
        ]);

        // Supprimer tous les tokens
        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe changé avec succès. Veuillez vous reconnecter.',
        ]);
    }
}
