<?php

return [
    // Internal server-to-server URL (PHP → OnlyOffice)
    'document_server_url' => env('ONLYOFFICE_DOCUMENT_SERVER_URL', 'http://localhost:8081'),
    'jwt_secret' => env('ONLYOFFICE_JWT_SECRET', 'YourStrongSecret'),
    // App public URL — base for callback signed routes (OnlyOffice → Laravel)
    'public_url' => env('ONLYOFFICE_PUBLIC_URL', env('APP_URL', 'http://localhost:8000')),
    // Browser-facing URL where OnlyOffice JS is served (browser → OnlyOffice)
    'editor_url' => env('ONLYOFFICE_EDITOR_URL', env('ONLYOFFICE_DOCUMENT_SERVER_URL', 'http://localhost:8081')),
];
