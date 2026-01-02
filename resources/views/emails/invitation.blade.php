<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitation à rejoindre {{ config('app.name') }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 40px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #4F46E5;
            margin-bottom: 10px;
        }
        h1 {
            color: #1F2937;
            font-size: 24px;
            margin-bottom: 20px;
        }
        .content {
            color: #4B5563;
            margin-bottom: 30px;
        }
        .invitation-details {
            background-color: #F3F4F6;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .invitation-details p {
            margin: 8px 0;
        }
        .invitation-details strong {
            color: #1F2937;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .button {
            display: inline-block;
            background-color: #4F46E5;
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
        }
        .button:hover {
            background-color: #4338CA;
        }
        .message-box {
            background-color: #EEF2FF;
            border-left: 4px solid #4F46E5;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #E5E7EB;
            text-align: center;
            color: #6B7280;
            font-size: 14px;
        }
        .expiry-notice {
            background-color: #FEF3C7;
            border-left: 4px solid #F59E0B;
            padding: 12px;
            margin: 20px 0;
            border-radius: 4px;
            font-size: 14px;
        }
        .alternative-link {
            margin-top: 20px;
            padding: 15px;
            background-color: #F9FAFB;
            border-radius: 6px;
            font-size: 12px;
            color: #6B7280;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">{{ config('app.name') }}</div>
        </div>

        <h1>Vous avez été invité !</h1>

        <div class="content">
            <p>Bonjour{{ $invitation->name ? ' ' . $invitation->name : '' }},</p>

            <p>
                <strong>{{ $invitedBy->name }}</strong> vous invite à rejoindre
                <strong>{{ config('app.name') }}</strong>.
            </p>
        </div>

        @if($invitation->message)
        <div class="message-box">
            <p style="margin: 0;"><strong>Message personnel :</strong></p>
            <p style="margin: 10px 0 0 0;">{{ $invitation->message }}</p>
        </div>
        @endif

        <div class="invitation-details">
            <p><strong>Détails de l'invitation :</strong></p>
            <p>📧 Email : {{ $invitation->email }}</p>
            <p>👤 Rôle : {{ ucfirst($invitation->role) }}</p>
            <p>⏰ Expire le : {{ $expiresAt }}</p>
        </div>

        <div class="expiry-notice">
            ⚠️ Cette invitation expire dans {{ $invitation->daysUntilExpiry() }} jour(s).
            Veuillez l'accepter avant le {{ $expiresAt }}.
        </div>

        <div class="button-container">
            <a href="{{ $acceptUrl }}" class="button">
                Accepter l'invitation
            </a>
        </div>

        <div class="alternative-link">
            <p style="margin: 0 0 10px 0;"><strong>Le bouton ne fonctionne pas ?</strong></p>
            <p style="margin: 0;">Copiez et collez ce lien dans votre navigateur :</p>
            <p style="margin: 5px 0 0 0;">{{ $acceptUrl }}</p>
        </div>

        <div class="footer">
            <p>
                Vous avez reçu cet email car {{ $invitedBy->name }} vous a invité à rejoindre
                {{ config('app.name') }}.
            </p>
            <p>
                Si vous n'avez pas demandé cette invitation, vous pouvez ignorer cet email.
            </p>
            <p style="margin-top: 20px;">
                © {{ date('Y') }} {{ config('app.name') }}. Tous droits réservés.
            </p>
        </div>
    </div>
</body>
</html>
