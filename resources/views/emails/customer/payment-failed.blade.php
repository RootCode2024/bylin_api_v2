@extends('emails.layout.base')

@section('title', 'Paiement Échoué')

@section('content')
    <h2>⚠️ Problème avec votre paiement</h2>
    
    <p>Bonjour {{ $customer->first_name }},</p>
    
    <p>Malheureusement, nous n'avons pas pu traiter votre paiement pour la commande #{{ $order->id }}.</p>
    
    <div class="alert-box">
        <p><strong>Raison du refus :</strong></p>
        <p>{{ $reason }}</p>
        <p style="margin-top: 10px;"><strong>Montant :</strong> {{ number_format($order->total, 0, ',', ' ') }} FCFA</p>
    </div>
    
    <h3>💳 Que faire maintenant ?</h3>
    <p>Nous vous invitons à :</p>
    <ol style="color: #666; margin-left: 20px;">
        <li>Vérifier les informations de votre moyen de paiement</li>
        <li>Vous assurer que le solde est suffisant</li>
        <li>Réessayer le paiement</li>
    </ol>
    
    <p style="text-align: center;">
        <a href="{{ config('app.frontend_url') }}/orders/{{ $order->id }}/retry-payment" class="email-button">
            Réessayer le paiement
        </a>
    </p>
    
    <div class="info-box" style="margin-top: 30px;">
        <p><strong>Note :</strong> Votre commande sera conservée pendant 24h. Passé ce délai, elle sera automatiquement annulée si le paiement n'est pas effectué.</p>
    </div>
    
    <p style="margin-top: 20px;">Besoin d'aide ? Contactez notre support.</p>
    
    <p>Cordialement,<br><strong>L'équipe {{ config('app.name') }}</strong></p>
@endsection
