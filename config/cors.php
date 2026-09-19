<?php
return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    */

    // 1. Define which route paths should apply CORS headers
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    // 2. Define the HTTP methods allowed (e.g., GET, POST, PUT, DELETE)
    'allowed_methods' => ['*'],

    // 3. Define the specific frontend URLs allowed to make requests
    // Change '*' to your explicit frontend URL for better security
    // 'allowed_origins' => ['http://localhost:5173', 'https://yourfrontend.com'],
'allowed_origins' => [
    'https://lintaspackaging.in/',      // Your local frontend port (React/Next.js)
    'http://localhost:5173',      // Your local frontend port (Vite/Vue)
    'http://localhost:5174',     // Your production frontend domain
],

    // 4. Use patterns if you have dynamic subdomains (e.g., https://*.yourdomain.com)
    'allowed_origins_patterns' => [],
    

    // 5. Define HTTP headers allowed in the request
    'allowed_headers' => ['*'],

    // 6. Headers the browser is allowed to access from the response
    'exposed_headers' => [],

    // 7. Maximum time (in seconds) the browser caches preflight results
    'max_age' => 0,

    // 8. Set to true if you are sending cookies, sessions, or Auth headers (e.g., Sanctum/Passport)
    'supports_credentials' => false,




];
