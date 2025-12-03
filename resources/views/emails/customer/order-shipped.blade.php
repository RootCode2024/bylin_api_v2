@extends('emails.layout.base')

@section('title', 'Commande Expédiée')

@section('content')
    <h2>🚚 Votre commande est en route !</h2>
    
    <p>Bonjour {{ $customer->first_name }},</p>
    
    <p>Bonne nouvelle ! Votre commande a été expédiée et sera bientôt chez vous.</p>
    
    <div class="success-box">
        <p><strong>Commande :</strong> #{{ $order->id }}</p>
        @if($trackingNumber)
            <p><strong>Numéro de suivi :</strong> {{ $trackingNumber }}</p>
        @endif
        <p><strong>Date d'expédition :</strong> {{ now()->format('d/m/Y') }}</p>
    </div>
    
    @if($trackingNumber)
        <p>Vous pouvez suivre votre colis en temps réel avec le numéro de suivi ci-dessus.</p>
    @endif
    
    <p style="text-align: center;">
        <a href="{{ config('app.frontend_url') }}/orders/{{ $order->id }}" class="email-button">
            Suivre ma commande
        </a>
    </p>
    
    <h3>📦 Articles expédiés</h3>
    <div class="info-box">
        @foreach($order->items as $item)
            <p>{{ $item->product_name }} × {{ $item->quantity }}</p>
        @endforeach
    </div>
    
    <p style="margin-top: 30px;">Merci pour votre confiance !<br><strong>L'équipe {{ config('app.name') }}</strong></p>
@endsection
