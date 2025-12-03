@extends('emails.layout.base')

@section('title', 'Bienvenue')

@section('content')
    <h2>🎉 Bienvenue chez {{ config('app.name') }} !</h2>
    
    <p>Bonjour {{ $customer->first_name }},</p>
    
    <p>Nous sommes ravis de vous compter parmi nous ! Votre inscription a été effectuée avec succès.</p>
    
    <div class="success-box">
        <p><strong>✓ Votre compte est actif</strong></p>
        <p>Vous pouvez maintenant profiter de tous nos avantages :</p>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <li>Commandes rapides et sécurisées</li>
            <li>Suivi de vos commandes en temps réel</li>
            <li>Offres exclusives et promotions</li>
            <li>Historique d'achats accessible</li>
        </ul>
    </div>
    
    <p style="text-align: center;">
        <a href="{{ config('app.frontend_url') }}/products" class="email-button">
            Découvrir nos produits
        </a>
    </p>
    
    <h3>💡 Prochaines étapes</h3>
    <p>Pour une expérience optimale, nous vous recommandons de :</p>
    <ol style="color: #666; margin-left: 20px;">
        <li>Compléter votre profil</li>
        <li>Ajouter une adresse de livraison</li>
        <li>Explorer notre catalogue</li>
    </ol>
    
    <p style="margin-top: 30px;">Besoin d'aide ? Notre équipe est là pour vous !</p>
    
    <p>À très bientôt,<br><strong>L'équipe {{ config('app.name') }}</strong></p>
@endsection
