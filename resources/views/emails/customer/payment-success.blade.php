@extends('emails.layout.base')

@section('title', 'Paiement Confirmé')

@section('content')
    <h2>💳 Paiement Réussi !</h2>
    
    <p>Bonjour {{ $customer->first_name }},</p>
    
    <p>Votre paiement a été traité avec succès. Nous préparons votre commande !</p>
    
    <div class="success-box">
        <p><strong>✓ Paiement confirmé</strong></p>
        <p><strong>Montant payé :</strong> {{ number_format($order->total, 0, ',', ' ') }} FCFA</p>
        <p><strong>Commande :</strong> #{{ $order->id }}</p>
        <p><strong>Date :</strong> {{ now()->format('d/m/Y à H:i') }}</p>
    </div>
    
    <p>Votre commande est maintenant en cours de préparation. Vous recevrez une notification dès qu'elle sera expédiée.</p>
    
    <p style="text-align: center;">
        <a href="{{ config('app.frontend_url') }}/orders/{{ $order->id }}" class="email-button">
            Voir ma commande
        </a>
    </p>
    
    <p style="color: #999; font-size: 14px; margin-top: 30px;">
        <strong>Note :</strong> Conservez cet email comme preuve de paiement.
    </p>
    
    <p style="margin-top: 20px;">Merci pour votre achat !<br><strong>L'équipe {{ config('app.name') }}</strong></p>
@endsection
