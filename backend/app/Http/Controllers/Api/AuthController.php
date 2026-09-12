<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Inscription d'un nouvel utilisateur
     */
    public function register(Request $request)
    {
        $request->merge([
            'telephone' => $this->normalizeTelephone($request->telephone),
        ]);

        $validated = $request->validate([
            'nom_complet' => 'required|string|max:255',
            'telephone' => 'required|string|unique:users,telephone|regex:/^\+237[0-9]{9}$/',
            'email' => 'nullable|email|unique:users,email',
            'mot_de_passe' => 'required|string|min:6|confirmed',
            'adresse_principale' => 'nullable|string',
        ]);

        $user = User::create([
            'nom_complet' => $validated['nom_complet'],
            'telephone' => $validated['telephone'],
            'email' => $validated['email'] ?? null,
            'mot_de_passe' => $validated['mot_de_passe'],
            'adresse_principale' => $validated['adresse_principale'] ?? null,
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
            'telephone' => $this->normalizeTelephone($request->telephone),
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
                'telephone' => $this->normalizeTelephone($request->telephone),
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
            'email' => 'sometimes|nullable|email|unique:users,email,'.$user->id,
            'telephone' => 'sometimes|string|unique:users,telephone,'.$user->id.'|regex:/^\+237[0-9]{9}$/',
            'adresse_principale' => 'nullable|string',
            'photo_profil' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        // Upload de la photo de profil
        if ($request->hasFile('photo_profil')) {
            $path = $request->file('photo_profil')->store('profils', 'public');
            $validated['photo_profil'] = $path;
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
            'nouveau_mot_de_passe' => 'required|string|min:6|confirmed',
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

    private function normalizeTelephone(?string $telephone): ?string
    {
        if ($telephone === null) {
            return null;
        }

        $cleaned = preg_replace('/[\s-]+/', '', trim($telephone));

        if ($cleaned === '') {
            return $cleaned;
        }

        if (str_starts_with($cleaned, '+')) {
            return $cleaned;
        }

        if (str_starts_with($cleaned, '237')) {
            return '+'.$cleaned;
        }

        return '+237'.$cleaned;
    }
}
