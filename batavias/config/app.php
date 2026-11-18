<?php
return [
 'name' => getenv('APP_NAME') ?: 'Batavias',
 'debug' => filter_var(getenv('APP_DEBUG') ?: false, FILTER_VALIDATE_BOOLEAN),
 'base_url' => rtrim(getenv('APP_URL') ?: '', '/'),
 'session_name' => getenv('SESSION_NAME') ?: 'batavias_session',
];
