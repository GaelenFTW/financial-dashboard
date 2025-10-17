<?php

return [
    'secret' => env('JWT_SECRET', 'TestingJWT123'),
    'api_url' => env('JSON_LINK_API_URL', 'http://localhost/json_link/index.php'),
    'api_url2' => env('JSON_LINK_API_URL2', 'http://localhost/json_link/index2.php'),
    'api_url3' => env('JSON_LINK_API_URL3', 'http://localhost/json_link/escrow.php'),
    'api_url4' => env('JSON_LINK_API_URL4', 'http://localhost/json_link/target.php'),
    'api_url5' => env('JSON_LINK_API_URL5', 'http://localhost/json_link/upload.php'),
    'api_url6' => env('JSON_LINK_API_URL6', 'http://localhost/json_link/projects.php'),
];
