<?php

namespace Biuc;

$settings = [
  'db' => [
    'server' => 'DBHOST',
    'user' => 'DBUSERNAME',
    'password' => 'DBPASSWORD',
    'database' => 'DBNAME',
    'table' => 'DBTABLE',
  ],
  'storage' => [
    'path' => 'cache/',
    'ext' => '.jpg',
  ],
  'server' => [
    'host' => ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http") . "://$_SERVER[HTTP_HOST]",
    'root' => substr($_SERVER['SCRIPT_NAME'], 0, strlen($_SERVER['SCRIPT_NAME']) - strlen(strrchr($_SERVER['SCRIPT_NAME'], "/"))),
  ],
  'hash' => [
    'admin' => 'ADMINPASSWORD',
    'user' => 'USERPASSWORD',
  ],
  'bing' => [
    'url' => 'https://api.cognitive.microsoft.com/bing/v5.0/images/search',
    'key' => 'YOUR_SERVICE_BING_KEY',
  ],
];