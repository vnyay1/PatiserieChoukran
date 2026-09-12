<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParametreSite extends Model
{
    protected $fillable = [
        'cle',
        'valeur',
        'type',
        'description',
        'groupe',
    ];

    // Méthode statique pour récupérer une valeur
    public static function get($cle, $default = null)
    {
        $parametre = static::where('cle', $cle)->first();

        if (! $parametre) {
            return $default;
        }

        return static::castValue($parametre->valeur, $parametre->type);
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
