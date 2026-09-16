<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Facture PDF d'une commande, générée à sa confirmation (voir App\Services\Factures).
 */
class Facture extends Model
{
    protected $fillable = [
        'commande_id',
        'numero_facture',
        'fichier',
        'montant_total',
        'email_destinataire',
        'envoyee_le',
    ];

    protected $casts = [
        'montant_total' => 'decimal:2',
        'envoyee_le' => 'datetime',
    ];

    // Le chemin du fichier privé ne sort pas de l'API
    protected $hidden = ['fichier'];

    public function commande()
    {
        return $this->belongsTo(Commande::class);
    }

    public function nomFichier(): string
    {
        return $this->numero_facture.'.pdf';
    }
}
