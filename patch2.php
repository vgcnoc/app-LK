<?php
$file = '/var/www/APM2/routes/web.php';
$content = file_get_contents($file);

// check if we find the create block
if (strpos($content, "'email' => \$cust['email'] ?? null,") !== false) {
    // we found it. let's replace it
    $new = "'email' => \$cust['email'] ?? null,\n                            'area' => \$cust['area'] ?? null,\n                            'registration_date' => \$cust['register_date'] ?? now(),";
    $content = str_replace("'email' => \$cust['email'] ?? null,", $new, $content);
    file_put_contents($file, $content);
    echo "Replaced successfully\n";
} else {
    echo "String not found\n";
}
