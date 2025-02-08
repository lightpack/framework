<?php

return [
    'host' => $_ENV['PGSQL_HOST'],
    'port' => $_ENV['PGSQL_PORT'],
    'username' => $_ENV['PGSQL_USER'],
    'password' => $_ENV['PGSQL_PASSWORD'],
    'database' => $_ENV['PGSQL_DB'],
    'options' => null,
];
