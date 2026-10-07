import re

with open(r'C:\Users\v\Documents\XAMPP\htdocs\Manajement Pelanggan\old_web.php', 'r', encoding='utf-8') as f:
    old_content = f.read()

# Extract from Route::get('/settings/api' down to the end
match = re.search(r'(Route::get\(' + repr('/settings/api').strip("'") + r'.*?)(?=\s*// Route::resource\(' + repr('users').strip("'") + r'\))', old_content, re.DOTALL)
if match:
    sync_routes = match.group(1)
    
    # ADD area and registration_date
    sync_routes = sync_routes.replace(
        "'email' => $cust['email'] ?? null,",
        "'email' => $cust['email'] ?? null,\n                            'area' => $cust['area'] ?? null,\n                            'registration_date' => $cust['registration_date'] ?? null,"
    )

    with open(r'C:\Users\v\Documents\XAMPP\htdocs\Manajement Pelanggan\routes\web.php', 'r', encoding='utf-8') as f:
        current_content = f.read()

    # Find the current Route::get('/settings/api' and replace it with the new sync_routes
    new_content = re.sub(
        r'(Route::get\(' + repr('/settings/api').strip("'") + r'.*?)(?=\s*// Route::resource\(' + repr('users').strip("'") + r'\))',
        sync_routes.replace('\\', '\\\\'),
        current_content,
        flags=re.DOTALL
    )

    with open(r'C:\Users\v\Documents\XAMPP\htdocs\Manajement Pelanggan\routes\web.php', 'w', encoding='utf-8') as f:
        f.write(new_content)
    print('Updated web.php')
else:
    print('Failed to extract')
