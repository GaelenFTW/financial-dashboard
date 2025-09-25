<?php

namespace App\Http\Controllers;

use Firebase\JWT\JWT;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\Key;
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
            'api1' => config('jwt.api_url'),
            'api2' => config('jwt.api_url2'),
            'api3' => config('jwt.api_url3'),
            'api4' => config('jwt.api_url4'),
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
            return ['valid' => false, 'expired' => false, 'message' => 'Invalid token: ' . $e->getMessage()];
        }
    }

    /** Generic login method */
    public function login($apiKey, $username = '12345678', $password = '12345678', $loginReplace = null)
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
            Log::error("Login error [$apiKey]: " . $e->getMessage());
            return null;
        }
    }

    /** Generic getToken method */
    public function getToken($apiKey, $username = '12345678', $password = '12345678', $loginReplace = null)
    {
        return $this->login($apiKey, $username, $password, $loginReplace);
    }

    /** Generic fetchData method */
    public function fetchData($apiKey, $loginReplace = null)
    {
        try {
            $token = $this->getToken($apiKey, '12345678', '12345678', $loginReplace);
            if (!$token) return ['error' => 'Authentication failed'];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json'
            ])->get($this->apiUrls[$apiKey]);

            // Retry once on 401
            if ($response->status() === 401) {
                $token = $this->getToken($apiKey, '12345678', '12345678', $loginReplace);
                if (!$token) return ['error' => 'Authentication failed after retry'];

                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json'
                ])->get($this->apiUrls[$apiKey]);
            }

            if (!$response->successful()) {
                return ['error' => 'API request failed: ' . $response->body()];
            }

            $data = $response->json();
            return is_array($data) && !empty($data) ? $data : ['error' => 'No data received from API'];

        } catch (\Exception $e) {
            Log::error("fetchData error [$apiKey]: " . $e->getMessage());
            return ['error' => 'Failed to fetch data: ' . $e->getMessage()];
        }
    }

    /** Convenience methods for each API */
    public function fetchData1() { return $this->fetchData('api1', ['index.php', 'login.php']); }
    public function fetchData2() { return $this->fetchData('api2', ['index2.php', 'login.php']); }
    public function fetchData3() { return $this->fetchData('api3', ['escrow.php', 'login.php']); }
    public function fetchData4() { return $this->fetchData('api4', ['target.php', 'login.php']); }
}
