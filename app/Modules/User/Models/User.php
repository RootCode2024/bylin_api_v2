<?php

declare(strict_types=1);

namespace Modules\User\Models;

use Laravel\Sanctum\HasApiTokens;
use Modules\Core\Traits\HasStatus;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Illuminate\Auth\Passwords\CanResetPassword;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Admin User Model
 *
 * @property string $id
 * @property string $name
 * @property string $email
 * @property string $phone
 * @property string $bio
 * @property string $avatar
 * @property string $avatar_url
 * @property string $status
 * @property string $password
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUuids, Notifiable, HasRoles, HasStatus;
    use CanResetPassword;

    /**
     * The primary key type
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'bio',
        'avatar',
        'avatar_url',
        'password',
        'status',
        'invited_by_id',
        'invited_at',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'invited_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Relationship: User who invited this user
     */
    public function invited_by(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_id');
    }

    /**
     * Get available statuses
     */
    public function getAvailableStatuses(): array
    {
        return ['active', 'inactive', 'suspended', 'banned'];
    }

    /**
     * Update user status safely
     */
    public function updateStatus(string $status): bool
    {
        if (!in_array($status, $this->getAvailableStatuses())) {
            throw new \InvalidArgumentException("Invalid status: {$status}");
        }

        $this->status = $status;
        return $this->save();
    }

    /**
     * Envoyer la notification de réinitialisation de mot de passe
     */
    public function sendPasswordResetNotification($token): void
    {
        try {
            Log::info('Envoi de notification de réinitialisation', [
                'user_id' => $this->id,
                'email' => $this->email,
                'token' => substr($token, 0, 10) . '...' // Log partiel du token
            ]);

            $this->notify(new ResetPasswordNotification($token));

            Log::info('Notification envoyée avec succès');
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi de la notification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }
}
