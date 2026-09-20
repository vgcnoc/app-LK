const { Client } = require('ssh2');

const conn = new Client();
conn.on('ready', () => {
    console.log('SSH connection established');
    
    // Find all directories named app-LK and check git status
    const deployCmd = `
    echo "Mencari folder app-LK di seluruh VPS..."
    find / -type d -name "app-LK" -not -path "*/node_modules/*" -not -path "*/vendor/*" 2>/dev/null
    
    echo "Cek /var/www/html"
    ls -la /var/www/html
    
    echo "Cek document root apache/nginx"
    grep -R -i "DocumentRoot" /etc/apache2/sites-enabled/ 2>/dev/null || echo "Bukan apache"
    grep -R -i "root " /etc/nginx/sites-enabled/ 2>/dev/null || echo "Bukan nginx"
    
    if [ -d "/var/www/html/app-LK" ]; then
        echo "Git log di /var/www/html/app-LK:"
        cd /var/www/html/app-LK
        git log -n 3 --oneline
    fi
    `;

    conn.exec(deployCmd, (err, stream) => {
        if (err) throw err;
        stream.on('close', (code, signal) => {
            console.log('Stream :: close :: code: ' + code + ', signal: ' + signal);
            conn.end();
        }).on('data', (data) => {
            console.log('STDOUT: ' + data);
        }).stderr.on('data', (data) => {
            console.log('STDERR: ' + data);
        });
    });
}).connect({
    host: '157.66.140.17',
    port: 22,
    username: 'root',
    password: 'viruzs123',
    readyTimeout: 99999
});
