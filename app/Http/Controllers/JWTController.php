<?php

namespace App\Http\Controllers;

use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PDO;

class JWTController extends Controller  {
    protected $key;
    protected $apiUrl;
    protected $pdo;

    public function __construct()
    {
        $this->key = 'TestingdadasJWT123'; // Ensure this matches your .env JWT_SECRET
        $this->apiUrl = 'http://localhost/index.php';
        
        try {
            $this->pdo = new PDO(
                "sqlsrv:Server=localhost\\SQLEXPRESS;Database=DataTest",
                "intern01",
                "adelard123"
            );
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            Log::error('Database connection failed: ' . $e->getMessage());
        }
    }

    public function authenticate()
    {
        try {
            // Hardcoded test credentials - replace with your actual test user
            $username = 'testuser';
            $password = 'Test123!';

            $stmt = $this->pdo->prepare("SELECT * FROM Logins WHERE username = :username");
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                Log::error('Authentication failed: User not found');
                return false;
            }

            if (!password_verify($password, $user['password_hash'])) {
                Log::error('Authentication failed: Invalid password');
                return false;
            }

            $token = $this->generateToken($username);
            session(['api_token' => $token]);
            
            Log::info('Authentication successful for user: ' . $username);
            return true;

        } catch (\Exception $e) {
            Log::error('Authentication error: ' . $e->getMessage());
            return false;
        }
    }

    protected function generateToken($username)
    {
        $payload = [
            "iss" => "localhost",
            "aud" => "localhost",
            "iat" => time(),
            "exp" => time() + 300, // Token valid for 1 hour
            "user" => $username
        ];

        return JWT::encode($payload, $this->key, 'HS256');
    }
}