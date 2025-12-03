@extends('emails.layout.base')

@section('title', 'Nouvelle Connexion Détectée')

@section('content')
    <h2>🔒 Alerte de Sécurité</h2>
    
    <p>Bonjour {{ $user->first_name ?? $user->name }},</p>
    
    <p>Nous avons détecté une nouvelle connexion à votre compte depuis un appareil que nous ne reconnaissons pas.</p>
    
    <div class="alert-box">
        <p><strong>⚠️ Détails de la connexion</strong></p>
        <p><strong>Appareil :</strong> {{ $deviceInfo['device_name'] }}</p>
        <p><strong>Type :</strong> {{ ucfirst($deviceInfo['device_type']) }}</p>
        <p><strong>Navigateur :</strong> {{ $deviceInfo['browser'] }}</p>
        <p><strong>Système :</strong> {{ $deviceInfo['platform'] }}</p>
        <p><strong>Localisation :</strong> {{ $location['city'] }}, {{ $location['country'] }}</p>
        <p><strong>Date :</strong> {{ now()->format('d/m/Y à H:i') }}</p>
    </div>
    
    <p><strong>Était-ce vous ?</strong></p>
    
    <p>Si vous êtes à l'origine de cette connexion, vous pouvez ignorer cet email en toute sécurité.</p>
    
    <p><strong>Si ce n'était pas vous :</strong></p>
    <ul style="color: #666; margin-left: 20px;">
        <li>Changez immédiatement votre mot de passe</li>
        <li>Vérifiez l'activité de votre compte</li>
        <li>Contactez notre support si nécessaire</li>
    </ul>
    
    <p style="text-align: center;">
        <a href="{{ config('app.frontend_url') }}/security/sessions" class="email-button">
            Gérer mes appareils
        </a>
    </p>
    
    <div class="info-box" style="margin-top: 30px;">
        <p style="font-size: 14px; color: #666;">
            <strong>💡 Conseil de sécurité :</strong> Ne partagez jamais votre mot de passe et activez l'authentification à deux facteurs pour plus de sécurité.
        </p>
    </div>
    
    <p style="margin-top: 20px;">Cordialement,<br><strong>L'équipe Sécurité {{ config('app.name') }}</strong></p>
@endsection
