<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'titre',
        'message',
        'type',
        'est_lu',
        'url_action',
        'date_envoi',
        'date_lecture',
    ];

    protected $casts = [
        'est_lu' => 'boolean',
        'date_envoi' => 'datetime',
        'date_lecture' => 'datetime',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeNonLu($query)
    {
        return $query->where('est_lu', false);
    }

    public function scopeLu($query)
    {
        return $query->where('est_lu', true);
    }

    public function scopeType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Méthodes utiles
    public function marquerCommeLu()
    {
        $this->est_lu = true;
        $this->date_lecture = now();
        $this->save();
    }
}
