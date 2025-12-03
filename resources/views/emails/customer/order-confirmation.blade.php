@extends('emails.layout.base')

@section('title', 'Confirmation de Commande')

@section('content')
    <h2>✅ Commande Confirmée !</h2>
    
    <p>Bonjour {{ $customer->first_name }},</p>
    
    <p>Nous avons bien reçu votre commande et la préparons avec soin. Merci pour votre confiance !</p>
    
    <div class="success-box">
        <p><strong>Numéro de commande :</strong> #{{ $order->id }}</p>
        <p><strong>Date :</strong> {{ $order->created_at->format('d/m/Y à H:i') }}</p>
        <p><strong>Montant total :</strong> {{ number_format($order->total, 0, ',', ' ') }} FCFA</p>
    </div>
    
    <h3>📦 Détails de la commande</h3>
    
    <div class="info-box">
        @foreach($order->items as $item)
            <p>
                <strong>{{ $item->product_name }}</strong>
                @if($item->variation_name)
                    ({{ $item->variation_name }})
                @endif
                <br>
                Quantité : {{ $item->quantity }} × {{ number_format($item->price, 0, ',', ' ') }} FCFA
                = {{ number_format($item->total, 0, ',', ' ') }} FCFA
            </p>
        @endforeach
        
        <hr style="margin: 15px 0; border: none; border-top: 1px solid #ddd;">
        
        <p><strong>Sous-total :</strong> {{ number_format($order->subtotal, 0, ',', ' ') }} FCFA</p>
        @if($order->discount_amount > 0)
            <p><strong>Réduction :</strong> -{{ number_format($order->discount_amount, 0, ',', ' ') }} FCFA</p>
        @endif
        <p><strong>Livraison :</strong> {{ number_format($order->shipping_amount, 0, ',', ' ') }} FCFA</p>
        <p><strong>Total :</strong> {{ number_format($order->total, 0, ',', ' ') }} FCFA</p>
    </div>
    
    <h3>📍 Adresse de livraison</h3>
    <div class="info-box">
        <p>{{ $order->shipping_address['address_line_1'] ?? '' }}</p>
        @if(isset($order->shipping_address['address_line_2']))
            <p>{{ $order->shipping_address['address_line_2'] }}</p>
        @endif
        <p>{{ $order->shipping_address['city'] ?? '' }}, {{ $order->shipping_address['country'] ?? '' }}</p>
    </div>
    
    <p style="text-align: center;">
        <a href="{{ config('app.frontend_url') }}/orders/{{ $order->id }}" class="email-button">
            Suivre ma commande
        </a>
    </p>
    
    <p>Vous recevrez un email dès que votre commande sera expédiée.</p>
    
    <p style="margin-top: 30px;">Cordialement,<br><strong>L'équipe {{ config('app.name') }}</strong></p>
@endsection
