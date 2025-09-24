<?php
require __DIR__ . '/vendor/autoload.php';

use OTPHP\TOTP;

// Sustituye por el secreto de tu usuario
$secret = "JBSWY3DPEHPK3PXCP";

$totp = TOTP::create($secret);

// Muestra el código actual de 6 dígitos
echo "Código 2FA actual: " . $totp->now() . PHP_EOL;
