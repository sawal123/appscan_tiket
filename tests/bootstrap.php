<?php

// PHPUnit's <php><env force="true"> only populates $_ENV and getenv(). phpdotenv's
// default reader order is [$_SERVER, $_ENV], so Laravel would still read the real
// values from .env and every test would run against the development database —
// wiping it, because RefreshDatabase runs migrate:fresh.
//
// Mirroring $_ENV into $_SERVER makes the overrides declared in phpunit.xml win.
foreach ($_ENV as $key => $value) {
    $_SERVER[$key] = $value;
}

require __DIR__.'/../vendor/autoload.php';
