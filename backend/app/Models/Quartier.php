<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quartier extends Model
{
    // Villes desservies par la plateforme (valeurs de quartiers.ville et vendeur_villes.ville)
    public const VILLES = [
        'yaoundé' => 'Yaoundé',
        'douala' => 'Douala',
    ];

    protected $fillable = [
        'nom',
        'ville',
        'actif',
    ];

    protected $casts = [
        'actif' => 'boolean',
    ];

    public static function regleVille(): string
    {
        return 'in:'.implode(',', array_keys(self::VILLES));
    }

    public static function libelleVille(?string $ville): string
    {
        return self::VILLES[$ville] ?? (string) $ville;
    }

    public function adresses(): HasMany
    {
        return $this->hasMany(Adresse::class);
    }
}
