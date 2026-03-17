<?php

return [
    'document_server_url' => env('ONLYOFFICE_DOCUMENT_SERVER_URL', 'http://localhost:8080'),
    'jwt_secret' => env('ONLYOFFICE_JWT_SECRET', 'YourStrongSecret'),
    'public_url' => env('ONLYOFFICE_PUBLIC_URL', env('APP_URL', 'http://localhost:8000')),
];
