<?php

declare(strict_types=1);

namespace Modules\Security\Services;

use Illuminate\Support\Facades\Http;
use Jenssegers\Agent\Agent;

class DeviceDetectionService
{
    /**
     * Detect device from user agent
     */
    public function detectDevice(string $userAgent): array
    {
        $agent = new Agent();
        $agent->setUserAgent($userAgent);

        return [
            'device_type' => $this->getDeviceType($agent),
            'device_name' => $this->getDeviceName($agent),
            'browser' => $agent->browser(),
            'platform' => $agent->platform(),
            'is_mobile' => $agent->isMobile(),
            'is_tablet' => $agent->isTablet(),
            'is_desktop' => $agent->isDesktop(),
            'is_robot' => $agent->isRobot(),
        ];
    }

    /**
     * Get geolocation from IP address
     */
    public function getGeolocation(string $ip): array
    {
        // Skip for local/private IPs
        if ($this->isLocalIp($ip)) {
            return $this->getDefaultLocation();
        }

        try {
            // Use ip-api.com (free, no API key needed)
            $response = Http::timeout(5)->get("http://ip-api.com/json/{$ip}");

            if ($response->successful() && $response->json('status') === 'success') {
                $data = $response->json();

                return [
                    'country' => $data['country'] ?? null,
                    'country_code' => $data['countryCode'] ?? null,
                    'city' => $data['city'] ?? null,
                    'latitude' => $data['lat'] ?? null,
                    'longitude' => $data['lon'] ?? null,
                    'timezone' => $data['timezone'] ?? null,
                    'isp' => $data['isp'] ?? null,
                ];
            }
        } catch (\Exception $e) {
            \Log::warning('Geolocation API failed', [
                'ip' => $ip,
                'error' => $e->getMessage(),
            ]);
        }

        return $this->getDefaultLocation();
    }

    /**
     * Generate device fingerprint
     */
    public function generateFingerprint(string $ip, string $userAgent): string
    {
        $data = $ip . '|' . $userAgent;
        return hash('sha256', $data);
    }

    /**
     * Get device type
     */
    private function getDeviceType(Agent $agent): string
    {
        if ($agent->isTablet()) {
            return 'tablet';
        }

        if ($agent->isMobile()) {
            return 'mobile';
        }

        if ($agent->isDesktop()) {
            return 'desktop';
        }

        return 'unknown';
    }

    /**
     * Get device name
     */
    private function getDeviceName(Agent $agent): string
    {
        $device = $agent->device();
        $platform = $agent->platform();
        $browser = $agent->browser();

        if ($device && $device !== 'WebKit') {
            return $device;
        }

        return "{$browser} on {$platform}";
    }

    /**
     * Check if IP is local/private
     */
    private function isLocalIp(string $ip): bool
    {
        $localIps = [
            '127.0.0.1',
            '::1',
            'localhost',
        ];

        if (in_array($ip, $localIps)) {
            return true;
        }

        // Check private IP ranges
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }

    /**
     * Get default location for local IPs
     */
    private function getDefaultLocation(): array
    {
        return [
            'country' => 'Unknown',
            'country_code' => null,
            'city' => 'Unknown',
            'latitude' => null,
            'longitude' => null,
            'timezone' => null,
            'isp' => null,
        ];
    }
}
