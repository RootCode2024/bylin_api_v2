<?php

declare(strict_types=1);

namespace Modules\Security\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Modules\Security\Models\LoginHistory;
use Modules\Security\Models\UserDevice;

class LoginHistoryService
{
    public function __construct(
        private DeviceDetectionService $deviceDetection
    ) {}

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
    public function recordFailedLogin(string $ip, ?string $email = null): void
    {
        $key = "login_failures:{$ip}";
        
        // Increment failure count, expire after 1 hour
        $failures = Cache::increment($key);
        
        if ($failures === 1) {
            Cache::put($key, 1, now()->addHour());
        }
        
        // Log if high number of failures
        if ($failures >= 5) {
            \Log::warning('High number of failed login attempts from IP', [
                'ip' => $ip,
                'count' => $failures,
                'last_email_attempt' => $email,
            ]);
        }
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
        $failures = Cache::get("login_failures:{$ip}", 0);
        
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
