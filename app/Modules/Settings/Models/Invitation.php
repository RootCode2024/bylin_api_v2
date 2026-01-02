<?php

declare(strict_types=1);

namespace Modules\Settings\Models;

use Modules\User\Models\User;
use Modules\Core\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle Invitation
 *
 * Gère les invitations de membres à rejoindre la plateforme
 */
class Invitation extends BaseModel
{
    protected $fillable = [
        'email',
        'name',
        'role',
        'token',
        'message',
        'invited_by_id',
        'accepted_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'accepted_at' => 'datetime',
            'expires_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * L'utilisateur qui a envoyé l'invitation
     */
    public function invited_by(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_id');
    }

    /**
     * Vérifie si l'invitation est expirée
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast() && is_null($this->accepted_at);
    }

    /**
     * Vérifie si l'invitation a été acceptée
     */
    public function isAccepted(): bool
    {
        return !is_null($this->accepted_at);
    }

    /**
     * Nombre de jours avant expiration
     */
    public function daysUntilExpiry(): int
    {
        if ($this->isExpired() || $this->isAccepted()) {
            return 0;
        }

        return (int) ceil(now()->diffInDays($this->expires_at, false));
    }

    /**
     * Scope pour les invitations en attente
     */
    public function scopePending($query)
    {
        return $query->whereNull('accepted_at')
            ->where('expires_at', '>', now());
    }

    /**
     * Scope pour les invitations expirées
     */
    public function scopeExpired($query)
    {
        return $query->whereNull('accepted_at')
            ->where('expires_at', '<=', now());
    }

    /**
     * Scope pour les invitations acceptées
     */
    public function scopeAccepted($query)
    {
        return $query->whereNotNull('accepted_at');
    }

    /**
     * Accepter l'invitation
     */
    public function accept(): void
    {
        if ($this->isExpired()) {
            throw new \Exception('Cette invitation a expiré');
        }

        if ($this->isAccepted()) {
            throw new \Exception('Cette invitation a déjà été acceptée');
        }

        $this->update(['accepted_at' => now()]);
    }

    /**
     * Obtenir l'attribut calculé is_expired
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->isExpired();
    }

    /**
     * Obtenir l'attribut calculé is_accepted
     */
    public function getIsAcceptedAttribute(): bool
    {
        return $this->isAccepted();
    }

    /**
     * Obtenir l'attribut calculé days_until_expiry
     */
    public function getDaysUntilExpiryAttribute(): int
    {
        return $this->daysUntilExpiry();
    }
}
