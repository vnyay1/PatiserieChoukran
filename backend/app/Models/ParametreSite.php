<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ParametreSite extends Model
{
    protected $fillable = [
        'cle',
        'valeur',
        'type',
        'description',
        'groupe',
    ];

    private const CLE_CACHE = 'parametres_site';

    // Toute modification par le modèle vide le cache ; une écriture SQL directe (migration) est prise en compte sous 10 minutes
    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CLE_CACHE));
        static::deleted(fn () => Cache::forget(self::CLE_CACHE));
    }

    // Méthode statique pour récupérer une valeur (tous les paramètres en une requête, mis en cache)
    public static function get($cle, $default = null)
    {
        $parametres = Cache::remember(self::CLE_CACHE, now()->addMinutes(10), fn () => static::query()
            ->get(['cle', 'valeur', 'type'])
            ->mapWithKeys(fn (self $parametre) => [$parametre->cle => [$parametre->valeur, $parametre->type]])
            ->all());

        if (! array_key_exists($cle, $parametres)) {
            return $default;
        }

        return static::castValue(...$parametres[$cle]);
    }

    // Méthode statique pour définir une valeur
    public static function set($cle, $valeur, $type = 'string')
    {
        return static::updateOrCreate(
            ['cle' => $cle],
            ['valeur' => $valeur, 'type' => $type]
        );
    }

    // Cast de la valeur selon le type
    protected static function castValue($valeur, $type)
    {
        return match ($type) {
            'integer' => (int) $valeur,
            'boolean' => (bool) $valeur,
            'json' => json_decode($valeur, true),
            default => $valeur,
        };
    }
}
