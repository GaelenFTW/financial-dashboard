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
    protected $apiUrl;

    public function __construct()
    {
        $this->key = 'TestingJWT123';
        $this->apiUrl = config('jwt.api_url');
    }

    // public function generateToken($username)
    // {
    //     $payload = [
    //         "iss" => "http://localhost",
    //         "aud" => "http://localhost",
    //         "iat" => time(),
    //         "exp" => time()+10,
    //         "user" => $username
    //     ];

    //     return JWT::encode($payload, $this->key, 'HS256');
    // }


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

    public function login($username = '12345678', $password = '12345678')
    {
        try {
            $loginUrl = str_replace('index.php', 'login.php', $this->apiUrl);
            $response = Http::post($loginUrl, compact('username', 'password'));

            if (!$response->successful()) {
                Log::error('Login failed: ' . $response->status());
                return null;
            }
            

            $data = $response->json();
            if (!isset($data['token'])) {
                Log::error('No token in response');
                return null;
            }

            return $data['token'];

        } catch (\Exception $e) {
            Log::error('Login error: ' . $e->getMessage());
            return null;
        }
        
    }

    public function getToken()
    {
        return $this->login();
    }

    // Centralized fetch logic
    public function fetchData()
    {
        try {
            // $token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbG9jYWxob3N0IiwiYXVkIjoiaHR0cDovL2xvY2FsaG9zdCIsImlhdCI6MTc1ODI2OTE0MCwiZXhwIjoxNzU4MjY5MTUwLCJ1c2VyIjoidGVzdHVzZXIifQ.-bQiKZENdi8rhvPJh_1LxtXjnM1pYGbuvBeHTOHVMSw";
            $token = $this->getToken();
            // echo $token;
            if (!$token) {
                return ['error' => 'Authentication failed'];
            }

            $check = $this->validateToken($token);
            if (!$check['valid']) {
                if ($check['expired']) {
                    return ['error' => 'Token expired'];  
                    // Or refresh if you want: $token = $this->getToken();
                } else {
                    return ['error' => $check['message']];
                }
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json'
            ])->get($this->apiUrl);

            // Retry on expired/invalid token
            if ($response->status() === 401) {
                $token = $this->getToken();
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
