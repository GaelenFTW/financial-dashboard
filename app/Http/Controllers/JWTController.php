<?php

namespace App\Http\Controllers;

use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class JWTController extends Controller
{
    protected $key;

    protected $apiUrls;

    public function __construct()
    {
        $this->key = 'TestingJWT123';
        $this->apiUrls = [
            // 'api1' => config('jwt.api_url'),
            // 'api2' => config('jwt.api_url2'),
            'api3' => config('jwt.api_url3'),
            'api4' => config('jwt.api_url4'),
            'api5' => config('jwt.api_url5'),
            'api6' => config('jwt.api_url6'),
        ];
    }

    /** Validate a JWT token */
    public function validateToken($token)
    {
        try {
            $decoded = JWT::decode($token, new Key($this->key, 'HS256'));

            return ['valid' => true, 'expired' => false, 'data' => (array) $decoded];
        } catch (ExpiredException $e) {
            return ['valid' => false, 'expired' => true, 'message' => 'Token expired'];
        } catch (\Exception $e) {
            return ['valid' => false, 'expired' => false, 'message' => 'Invalid token: '.$e->getMessage()];
        }
    }

    /** Generic login method */
    public function login($apiKey, $username = '1234567890', $password = '1234567890', $loginReplace = null)
    {
        try {
            $url = $this->apiUrls[$apiKey];
            if ($loginReplace) {
                [$search, $replace] = $loginReplace;
                $url = str_replace($search, $replace, $url);
            }

            $response = Http::post($url, compact('username', 'password'));
            $data = $response->json();

            // Accept different token keys
            return $data['token'] ?? $data['access_token'] ?? null;

        } catch (\Exception $e) {
            Log::error("Login error [$apiKey]: ".$e->getMessage());

            return null;
        }
    }

    /** Generic getToken method */
    public function getToken($apiKey, $username = '1234567890', $password = '1234567890', $loginReplace = null)
    {
        return $this->login($apiKey, $username, $password, $loginReplace);
    }

    /** Generic fetchData method - now handles APIs with JWT disabled */
    public function fetchData($apiKey, $loginReplace = null)
    {
        try {
            // First, try without authentication (for APIs with JWT disabled)
            $response = Http::get($this->apiUrls[$apiKey]);

            // If successful, return data
            if ($response->successful()) {
                $data = $response->json();

                return is_array($data) && ! empty($data) ? $data : [];
            }

            // If 401, try with JWT authentication
            if ($response->status() === 401) {
                $token = $this->getToken($apiKey, '1234567890', '1234567890', $loginReplace);
                if (! $token) {
                    Log::warning("Authentication failed for [$apiKey], returning empty array");

                    return [];
                }

                $response = Http::withHeaders([
                    'Authorization' => 'Bearer '.$token,
                    'Accept' => 'application/json',
                ])->get($this->apiUrls[$apiKey]);

                if (! $response->successful()) {
                    Log::warning("API request failed for [$apiKey]: ".$response->status());

                    return [];
                }

                $data = $response->json();

                return is_array($data) && ! empty($data) ? $data : [];
            }

            // For other errors, log and return empty array
            Log::warning("API request failed for [$apiKey]: ".$response->status());

            return [];

        } catch (\Exception $e) {
            Log::error("fetchData error [$apiKey]: ".$e->getMessage());

            return [];
        }
    }

    public function projectsMap(bool $withIdSuffix = true, int $cacheSeconds = 300): array
    {
        // Cache raw API response
        $raw = Cache::remember('projects.api6.raw', $cacheSeconds, function () {
            return $this->fetchData6() ?? [];
        });

        $map = [];

        if (is_array($raw)) {
            $isAssoc = array_keys($raw) !== range(0, count($raw) - 1);

            if ($isAssoc) {
                // Style: { "2": "CitraGarden City Jakarta (2)", ... }
                foreach ($raw as $id => $name) {
                    if (! is_numeric($id)) {
                        continue;
                    }
                    $id = (int) $id;
                    $label = is_string($name) ? trim($name) : '';
                    if ($withIdSuffix && $label !== '' && stripos($label, "($id)") === false) {
                        $label .= " ($id)";
                    }
                    $map[$id] = $label ?: "Project ($id)";
                }
            } else {
                // Style: [ {id:2,name:"..."}, {project_id:3,project_name:"..."} ]
                foreach ($raw as $item) {
                    $id = $item['id'] ?? $item['project_id'] ?? null;
                    $name = $item['name'] ?? $item['project_name'] ?? null;
                    if (! $id || ! $name) {
                        continue;
                    }
                    $id = (int) $id;
                    $label = trim((string) $name);
                    if ($withIdSuffix && stripos($label, "($id)") === false) {
                        $label .= " ($id)";
                    }
                    $map[$id] = $label;
                }
            }
        }

        ksort($map, SORT_NUMERIC);

        return $map;
    }

    public function projectLabel(int $id, ?string $default = null): string
    {
        $map = $this->projectsMap();

        return $map[$id] ?? ($default ?? "Project ($id)");
    }

    /** Convenience methods for each API */
    // public function fetchData1() { return $this->fetchData('api1', ['index.php', 'login.php']); }
    // public function fetchData2() { return $this->fetchData('api2', ['index2.php', 'login.php']); }
    public function fetchData3()
    {
        return $this->fetchData('api3', ['escrow.php', 'login.php']);
    }

    public function fetchData4()
    {
        return $this->fetchData('api4', ['target.php', 'login.php']);
    }

    public function fetchData5()
    {
        return $this->fetchData('api5', ['upload.php', 'login.php']);
    }

    public function fetchData6()
    {
        return $this->fetchData('api6', ['projects.php', 'login.php']);
    }
}
