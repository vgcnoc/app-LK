<?php
$file = '/var/www/APM2/routes/web.php';
$content = file_get_contents($file);
$old = "'status' => 'booking'";
$new = "'status' => 'booking',\n                            'area' => \$cust['area'] ?? null,\n                            'registration_date' => \$cust['register_date'] ?? now()";
if (strpos($content, $old) !== false) {
    $content = str_replace($old, $new, $content);
    file_put_contents($file, $content);
    echo "Replaced successfully\n";
} else {
    echo "Old text not found\n";
}
