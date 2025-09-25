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
    protected $apiUrl2;


    public function __construct()
    {
        $this->key = 'TestingJWT123';
        $this->apiUrl = config('jwt.api_url');
        $this->apiUrl2 = config('jwt.api_url2');
        $this->apiUrl3 = config('jwt.api_url3');
        $this->apiurl4 = config('jwt.api_url4');

    }

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

            $data = $response->json();

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


    // login2 - same as login but uses index2.php and apiUrl2
public function login2($username = '12345678', $password = '12345678')
{
    try {
        $loginUrl = str_replace('index2.php', 'login.php', $this->apiUrl2);
        Log::info('login2 URL: ' . $loginUrl);

        $response = Http::post($loginUrl, compact('username', 'password'));
        Log::info('login2 response: ' . $response->body());

        $data = $response->json();
        Log::info('login2 decoded data: ' . json_encode($data));

        return $data['token'];

    } catch (\Exception $e) {
        Log::error('Login2 error: ' . $e->getMessage());
        return null;
    }
}


// getToken2 - same as getToken but calls login2
public function getToken2()
{
    return $this->login2();
}

// fetchData2 - same as fetchData but uses getToken2 and apiUrl2
public function fetchData2()
{
    try {
        $token = $this->getToken2();
        if (!$token) {
            return ['error' => 'Authentication failed'];
        }

        $check = $this->validateToken($token);
        if (!$check['valid']) {
            if ($check['expired']) {
                return ['error' => 'Token expired'];
            } else {
                return ['error' => $check['message']];
            }
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ])->get($this->apiUrl2);

        // Retry on expired/invalid token
        if ($response->status() === 401) {
            $token = $this->getToken2(); // retry using getToken2
            if (!$token) {
                return ['error' => 'Authentication failed after retry'];
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json'
            ])->get($this->apiUrl2);
        }

        if (!$response->successful()) {
            return ['error' => 'API request failed: ' . $response->body()];
        }

        $data = $response->json();
        return !empty($data) ? $data : ['error' => 'No data received from API'];

    } catch (\Exception $e) {
        Log::error('fetchData2 error: ' . $e->getMessage());
        return ['error' => 'Failed to fetch data: ' . $e->getMessage()];
    }
}


    public function login3($username = '12345678', $password = '12345678')
    {
        try {
            $loginUrl = str_replace('escrow.php', 'login.php', $this->apiUrl3);
            Log::info('login2 URL: ' . $loginUrl);

            $response = Http::post($loginUrl, compact('username', 'password'));
            Log::info('login2 response: ' . $response->body());

            $data = $response->json();
            Log::info('login2 decoded data: ' . json_encode($data));

            return $data['token'];

        } catch (\Exception $e) {
            Log::error('Login2 error: ' . $e->getMessage());
            return null;
        }
    }


    public function getToken3()
    {
        return $this->login3();
    }

    public function fetchData3()
    {
        try {
            $token = $this->getToken3();
            if (!$token) {
                return ['error' => 'Authentication failed'];
            }

            $check = $this->validateToken($token);
            if (!$check['valid']) {
                if ($check['expired']) {
                    return ['error' => 'Token expired'];
                } else {
                    return ['error' => $check['message']];
                }
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json'
            ])->get($this->apiUrl3);

            // Retry on expired/invalid token
            if ($response->status() === 401) {
                $token = $this->getToken3(); // retry using getToken2
                if (!$token) {
                    return ['error' => 'Authentication failed after retry'];
                }

                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json'
                ])->get($this->apiUrl3);
            }

            if (!$response->successful()) {
                return ['error' => 'API request failed: ' . $response->body()];
            }

            $data = $response->json();
            return !empty($data) ? $data : ['error' => 'No data received from API'];

        } catch (\Exception $e) {
            Log::error('fetchData2 error: ' . $e->getMessage());
            return ['error' => 'Failed to fetch data: ' . $e->getMessage()];
        }
    }

    

    public function login4($username = '12345678', $password = '12345678')
    {
        try {
            $loginUrl = str_replace('target.php', 'login.php', $this->apiUrl4);
            Log::info('login2 URL: ' . $loginUrl);

            $response = Http::post($loginUrl, compact('username', 'password'));
            Log::info('login2 response: ' . $response->body());

            $data = $response->json();
            Log::info('login2 decoded data: ' . json_encode($data));

            return $data['token'];

        } catch (\Exception $e) {
            Log::error('Login2 error: ' . $e->getMessage());
            return null;
        }
    }


    public function getToken4()
    {
        return $this->login4();
    }

    public function fetchData4()
    {
        try {
            $token = $this->getToken4();
            if (!$token) {
                return ['error' => 'Authentication failed'];
            }

            $check = $this->validateToken($token);
            if (!$check['valid']) {
                if ($check['expired']) {
                    return ['error' => 'Token expired'];
                } else {
                    return ['error' => $check['message']];
                }
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json'
            ])->get($this->apiUrl4);

            // Retry on expired/invalid token
            if ($response->status() === 401) {
                $token = $this->getToken4(); // retry using getToken2
                if (!$token) {
                    return ['error' => 'Authentication failed after retry'];
                }

                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json'
                ])->get($this->apiUrl4);
            }

            if (!$response->successful()) {
                return ['error' => 'API request failed: ' . $response->body()];
            }

            $data = $response->json();
            return !empty($data) ? $data : ['error' => 'No data received from API'];

        } catch (\Exception $e) {
            Log::error('fetchData2 error: ' . $e->getMessage());
            return ['error' => 'Failed to fetch data: ' . $e->getMessage()];
        }
    }

}
