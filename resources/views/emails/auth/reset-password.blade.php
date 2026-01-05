@component('mail::message')
# Bonjour {{ $notifiable->name }},

Vous recevez cet e-mail car nous avons reçu une demande de réinitialisation de mot de passe pour votre compte.

@component('mail::button', ['url' => $url, 'color' => 'primary'])
Réinitialiser le mot de passe
@endcomponent

**Ce lien expirera dans 60 minutes.**

Si vous n'avez pas demandé de réinitialisation, aucune action n'est requise.

Cordialement,
L'équipe {{ config('app.name') }}

---

<small style="color: #999;">
Si vous rencontrez des difficultés pour cliquer sur le bouton, copiez et collez ce lien dans votre navigateur :<br>
{{ $url }}
</small>

<small style="color: #999;">
© {{ date('Y') }} {{ config('app.name') }}. Tous droits réservés.
</small>
@endcomponent
