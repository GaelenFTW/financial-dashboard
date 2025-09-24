<?php

return [
    'secret' => env('JWT_SECRET', 'TestingJWT123'),
    'api_url' => env('JSON_LINK_API_URL', 'http://localhost/json_link/index.php'),
    'api_url2' => env('JSON_LINK_API_URL2', 'http://localhost/json_link/index2.php'),
    'api_url3' => env('JSON_LINK_API_URL3', 'http://localhost/json_link/escrow.php'),
];  