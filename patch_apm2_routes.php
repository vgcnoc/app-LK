<?php
$old_content = file_get_contents('C:\Users\v\Documents\XAMPP\htdocs\Manajement Pelanggan\old_web.php');

$start = "Route::get('/settings/api'";
$end = "    // Route::resource('users'";

$start_pos = strpos($old_content, $start);
$end_pos = strpos($old_content, $end, $start_pos);

if ($start_pos !== false && $end_pos !== false) {
    $sync_routes = substr($old_content, $start_pos, $end_pos - $start_pos);
    
    // ADD area and registration_date
    $sync_routes = str_replace(
        "'email' => \$cust['email'] ?? null,",
        "'email' => \$cust['email'] ?? null,\n                            'area' => \$cust['area'] ?? null,\n                            'registration_date' => \$cust['registration_date'] ?? null,",
        $sync_routes
    );

    $current_content = file_get_contents('C:\Users\v\Documents\XAMPP\htdocs\Manajement Pelanggan\routes\web.php');

    $curr_start_pos = strpos($current_content, $start);
    $curr_end_pos = strpos($current_content, $end, $curr_start_pos);

    if ($curr_start_pos !== false && $curr_end_pos !== false) {
        $new_content = substr($current_content, 0, $curr_start_pos) . $sync_routes . substr($current_content, $curr_end_pos);
        file_put_contents('C:\Users\v\Documents\XAMPP\htdocs\Manajement Pelanggan\routes\web.php', $new_content);
        echo "Updated web.php\n";
    } else {
        echo "Failed to find boundaries in current web.php\n";
    }
} else {
    echo "Failed to find boundaries in old_web.php\n";
}
