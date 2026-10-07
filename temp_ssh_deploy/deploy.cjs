const { Client } = require('ssh2'); 
const conn = new Client(); 
conn.on('ready', () => { 
    console.log('Client :: ready'); 
    conn.exec('cd /var/www/lk.viruzs.my.id && git reset --hard HEAD && git pull origin main && php artisan migrate --force && npm install && npm run build', (err, stream) => {
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
    password: 'viruzs123' 
});
