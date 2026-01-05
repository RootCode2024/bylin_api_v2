<?php

declare(strict_types=1);

namespace Modules\Security\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Modules\Security\Models\UserDevice;
use Modules\Security\Models\LoginHistory;

class LoginHistoryService
{
    public function __construct(
        private DeviceDetectionService $deviceDetection
    ) {}

    /**
     * Record a successful login attempt
     * Alias pour recordLogin avec userAgent
     */
    public function recordSuccessfulLogin(Model $user, string $ip, ?string $userAgent = null): LoginHistory
    {
        // Si pas de userAgent fourni, essayer de le récupérer de la requête
        if (!$userAgent) {
            $userAgent = request()->userAgent() ?? 'Unknown';
        }

        // Réinitialiser le compteur d'échecs pour cette IP
        Cache::forget("login_failures:{$ip}");

        return $this->recordLogin($user, $ip, $userAgent);
    }

    /**
     * Record a login attempt
     */
    public function recordLogin(Model $user, string $ip, string $userAgent): LoginHistory
    {
        // Detect device
        $deviceInfo = $this->deviceDetection->detectDevice($userAgent);

        // Get geolocation
        $location = $this->deviceDetection->getGeolocation($ip);

        // Generate device fingerprint
        $fingerprint = $this->deviceDetection->generateFingerprint($ip, $userAgent);

        // Check if new device
        $isNewDevice = !$this->deviceExists($user, $fingerprint);

        // Check if new location
        $isNewLocation = $location['country'] &&
            !$this->locationExists($user, $location['country_code']);

        // Create login record
        $login = LoginHistory::create([
            'user_id' => $user->id,
            'user_type' => get_class($user),
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'device_type' => $deviceInfo['device_type'],
            'device_name' => $deviceInfo['device_name'],
            'browser' => $deviceInfo['browser'],
            'platform' => $deviceInfo['platform'],
            'country' => $location['country'],
            'country_code' => $location['country_code'],
            'city' => $location['city'],
            'latitude' => $location['latitude'],
            'longitude' => $location['longitude'],
            'is_new_device' => $isNewDevice,
            'is_new_location' => $isNewLocation,
            'is_suspicious' => $this->isSuspicious($user, $ip, $location),
            'login_at' => now(),
        ]);

        // Update or create device
        $this->updateOrCreateDevice($user, $fingerprint, $deviceInfo, $ip, $location);

        // Send security alerts if enabled
        if (config('security.new_device_alert_enabled', true) && $isNewDevice) {
            \Modules\Notification\Jobs\SendSecurityAlert::dispatch($user, $deviceInfo, $location);
        }

        return $login;
    }

    /**
     * Record logout
     */
    public function recordLogout(Model $user): void
    {
        $lastLogin = LoginHistory::forUser($user->id, get_class($user))
            ->whereNull('logout_at')
            ->latest('login_at')
            ->first();

        if ($lastLogin) {
            $lastLogin->markAsLoggedOut();
        }
    }

    /**
     * Record a failed login attempt
     */
    public function recordFailedLogin(string $ip, ?string $email = null, ?string $reason = null): void
    {
        $key = "login_failures:{$ip}";

        // Increment failure count, expire after 1 hour
        $failures = Cache::increment($key);

        if ($failures === 1) {
            Cache::put($key, 1, now()->addHour());
        }

        // Log if high number of failures
        if ($failures >= 5) {
            Log::warning('Nombre élevé de tentatives de connexion échouées depuis une IP', [
                'ip' => $ip,
                'count' => $failures,
                'last_email_attempt' => $email,
                'reason' => $reason,
            ]);
        }

        // Optionnel : Enregistrer dans la base de données
        if ($failures >= 3) {
            $this->recordFailedLoginInDatabase($ip, $email, $reason, $failures);
        }
    }

    /**
     * Enregistrer les échecs de connexion dans la base de données
     */
    private function recordFailedLoginInDatabase(string $ip, ?string $email, ?string $reason, int $attemptCount): void
    {
        // Si vous avez une table pour les échecs de connexion
        // FailedLoginAttempt::create([
        //     'ip_address' => $ip,
        //     'email' => $email,
        //     'reason' => $reason,
        //     'attempt_count' => $attemptCount,
        //     'attempted_at' => now(),
        // ]);
    }

    /**
     * Get the number of failed login attempts for an IP
     */
    public function getFailedAttempts(string $ip): int
    {
        return Cache::get("login_failures:{$ip}", 0);
    }

    /**
     * Check if IP is temporarily locked out
     */
    public function isLockedOut(string $ip, int $maxAttempts = 5): bool
    {
        return $this->getFailedAttempts($ip) >= $maxAttempts;
    }

    /**
     * Get remaining lockout time in seconds
     */
    public function getRemainingLockoutTime(string $ip): ?int
    {
        $key = "login_failures:{$ip}";

        if (!Cache::has($key)) {
            return null;
        }

        // Cache expire après 1 heure (3600 secondes)
        // Vous pourriez stocker le timestamp exact pour plus de précision
        return 3600;
    }

    /**
     * Clear failed login attempts for an IP
     */
    public function clearFailedAttempts(string $ip): void
    {
        Cache::forget("login_failures:{$ip}");
    }

    /**
     * Get user's login history
     */
    public function getLoginHistory(Model $user, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return LoginHistory::forUser($user->id, get_class($user))
            ->latest('login_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get active sessions for user
     */
    public function getActiveSessions(Model $user): \Illuminate\Database\Eloquent\Collection
    {
        return LoginHistory::forUser($user->id, get_class($user))
            ->whereNull('logout_at')
            ->where('login_at', '>=', now()->subDays(30))
            ->latest('login_at')
            ->get();
    }

    /**
     * Logout all other sessions except current
     */
    public function logoutOtherSessions(Model $user, string $currentLoginId): int
    {
        return LoginHistory::forUser($user->id, get_class($user))
            ->whereNull('logout_at')
            ->where('id', '!=', $currentLoginId)
            ->update(['logout_at' => now()]);
    }

    /**
     * Check if device exists for user
     */
    private function deviceExists(Model $user, string $fingerprint): bool
    {
        return UserDevice::forUser($user->id, get_class($user))
            ->where('device_fingerprint', $fingerprint)
            ->exists();
    }

    /**
     * Check if location exists for user
     */
    private function locationExists(Model $user, ?string $countryCode): bool
    {
        if (!$countryCode) {
            return true; // Don't alert for unknown locations
        }

        return LoginHistory::forUser($user->id, get_class($user))
            ->where('country_code', $countryCode)
            ->exists();
    }

    /**
     * Check if login is suspicious
     */
    private function isSuspicious(Model $user, string $ip, array $location): bool
    {
        // Check for rapid location changes
        $lastLogin = LoginHistory::forUser($user->id, get_class($user))
            ->latest('login_at')
            ->first();

        if ($lastLogin && $lastLogin->country_code && $location['country_code']) {
            $timeDiff = now()->diffInHours($lastLogin->login_at);

            // If different country within 1 hour = suspicious
            if ($lastLogin->country_code !== $location['country_code'] && $timeDiff < 1) {
                return true;
            }
        }

        // Check for multiple failed attempts from this IP
        $failures = $this->getFailedAttempts($ip);

        if ($failures >= 3) {
            return true;
        }

        return false;
    }

    /**
     * Update or create device record
     */
    private function updateOrCreateDevice(
        Model $user,
        string $fingerprint,
        array $deviceInfo,
        string $ip,
        array $location
    ): UserDevice {
        $device = UserDevice::forUser($user->id, get_class($user))
            ->where('device_fingerprint', $fingerprint)
            ->first();

        if ($device) {
            // Update existing device
            $device->updateActivity($ip, $location['country'], $location['city']);
            return $device;
        }

        // Create new device
        return UserDevice::create([
            'user_id' => $user->id,
            'user_type' => get_class($user),
            'device_fingerprint' => $fingerprint,
            'device_name' => $deviceInfo['device_name'],
            'device_type' => $deviceInfo['device_type'],
            'browser' => $deviceInfo['browser'],
            'platform' => $deviceInfo['platform'],
            'last_ip' => $ip,
            'last_country' => $location['country'],
            'last_city' => $location['city'],
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);
    }
}
