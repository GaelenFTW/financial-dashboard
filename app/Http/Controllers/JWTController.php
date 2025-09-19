<?php

namespace App\Http\Controllers;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class JWTController extends Controller
{
    protected $key;
    protected $apiUrl;

    public function __construct()
    {
        $this->key = 'TestingJWT123'; // ideally from .env
        $this->apiUrl = config('jwt.api_url');
    }

    public function login($username = 'testuser', $password = 'Test123!')
    {
        try {
            $loginUrl = str_replace('index.php', 'login.php', $this->apiUrl);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->post($loginUrl, [
                'username' => $username,
                'password' => $password
            ]);

            if (!$response->successful()) {
                Log::error('Login failed: ' . $response->status());
                return null;
            }

            $data = $response->json();
            if (!isset($data['token'])) {
                Log::error('No token in response');
                return null;
            }

            session(['api_token' => $data['token']]);
            return $data['token'];

        } catch (\Exception $e) {
            Log::error('Login error: ' . $e->getMessage());
            return null;
        }
    }

    public function getToken()
    {
        return session('api_token') ?? $this->login();
    }

    public function fetchData()
    {
        try {
            $token = $this->getToken();
            if (!$token) {
                return ['error' => 'Authentication failed'];
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json'
            ])->get($this->apiUrl);

            if ($response->status() === 401) {
                $token = $this->login();
                if (!$token) {
                    return ['error' => 'Authentication failed after retry'];
                }

                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json'
                ])->get($this->apiUrl);
            }

            if (!$response->successful()) {
                return ['error' => 'API request failed: ' . $response->body()];
            }

            $data = $response->json();
            return !empty($data) ? $data : ['error' => 'No data received from API'];

        } catch (\Exception $e) {
            Log::error('fetchData error: ' . $e->getMessage());
            return ['error' => 'Failed to fetch data: ' . $e->getMessage()];
        }
    }
}
