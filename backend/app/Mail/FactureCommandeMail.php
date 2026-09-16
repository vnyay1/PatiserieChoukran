<?php

namespace App\Mail;

use App\Models\Commande;
use App\Models\Facture;
use App\Services\Factures;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class FactureCommandeMail extends Mailable
{
    public function __construct(public Facture $facture, public Commande $commande) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Votre facture {$this->facture->numero_facture} - commande {$this->commande->numero_commande}",
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.facture',
            with: [
                'client' => $this->commande->user,
                'vendeur' => $this->commande->vendeur,
                'montant' => number_format((float) $this->commande->montant_total, 0, ',', ' '),
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromStorageDisk(Factures::DISQUE, $this->facture->fichier)
                ->as($this->facture->nomFichier())
                ->withMime('application/pdf'),
        ];
    }
}
