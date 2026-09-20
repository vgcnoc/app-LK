const { execSync } = require('child_process');
const { Client } = require('ssh2');

console.log('=== Step 1: Pushing to GitHub ===');
try {
    // This will block and wait. If GCM pops up, it will wait for the user.
    execSync('"C:\\Program Files\\Git\\cmd\\git.exe" push origin main', { stdio: 'inherit' });
    console.log('Git push successful.');
} catch (err) {
    console.error('Git push failed. Please check your credentials or network.', err.message);
    process.exit(1);
}

console.log('\n=== Step 2: Updating VPS ===');
const conn = new Client();
conn.on('ready', () => {
    console.log('SSH connection established');
    
    const deployCmd = `
    cd /var/www/lk.viruzs.my.id
    echo "Current Branch:"
    git branch
    
    echo "Fetching all..."
    git fetch origin
    
    echo "Checkout main..."
    git checkout main || git checkout -b main origin/main
    
    echo "Melakukan git reset dan pull..."
    git reset --hard HEAD
    git pull origin main
    
    echo "Update composer dan npm..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
    npm install
    npm run build
    
    echo "Membersihkan cache..."
    php artisan optimize:clear
    
    echo "Fix permissions..."
    chmod -R 775 storage bootstrap/cache
    chown -R www-data:www-data storage bootstrap/cache
    
    echo "Deploy selesai dengan sukses di branch main!"
    `;

    conn.exec(deployCmd, (err, stream) => {
        if (err) throw err;
        stream.on('close', (code, signal) => {
            console.log('Stream :: close :: code: ' + code);
            conn.end();
        }).on('data', (data) => {
            process.stdout.write('STDOUT: ' + data);
        }).stderr.on('data', (data) => {
            process.stderr.write('STDERR: ' + data);
        });
    });
}).connect({
    host: '157.66.140.17',
    port: 22,
    username: 'root',
    password: 'viruzs123',
    readyTimeout: 99999
});
