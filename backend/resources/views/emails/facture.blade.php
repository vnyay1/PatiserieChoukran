{{-- E-mail texte : valeurs non échappées ({!! !!}), sinon « L'Atelier » deviendrait « L&#039;Atelier » --}}
Bonjour {!! $client?->nom_complet !!},

Votre commande {!! $commande->numero_commande !!} a été confirmée{!! $vendeur ? ' par '.$vendeur->nom_complet : '' !!}.

Vous trouverez ci-joint la facture {!! $facture->numero_facture !!} d'un montant de {!! $montant !!} FCFA.

Vous pouvez aussi la télécharger à tout moment depuis le détail de la commande, dans votre espace client.

Merci de votre confiance et bonne dégustation !

{!! config('app.name') !!}
