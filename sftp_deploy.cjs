const { Client } = require('ssh2');
const fs = require('fs');

const conn = new Client();
conn.on('ready', () => {
    console.log('SSH connection established');
    conn.sftp((err, sftp) => {
        if (err) throw err;
        
        console.log('SFTP session started');
        const filesToUpload = [
            { local: 'c:/xampp/htdocs/app-LK/routes/web.php', remote: '/var/www/lk.viruzs.my.id/routes/web.php' },
            { local: 'c:/xampp/htdocs/app-LK/resources/js/Pages/Billing.vue', remote: '/var/www/lk.viruzs.my.id/resources/js/Pages/Billing.vue' },
            { local: 'c:/xampp/htdocs/app-LK/resources/js/Pages/Pelanggan.vue', remote: '/var/www/lk.viruzs.my.id/resources/js/Pages/Pelanggan.vue' },
            { local: 'c:/xampp/htdocs/app-LK/resources/js/Pages/PelangganPantauan.vue', remote: '/var/www/lk.viruzs.my.id/resources/js/Pages/PelangganPantauan.vue' }
        ];

        let uploaded = 0;
        filesToUpload.forEach((file) => {
            sftp.fastPut(file.local, file.remote, (err) => {
                if (err) {
                    console.error('Failed to upload', file.local, err);
                } else {
                    console.log('Successfully uploaded', file.remote);
                }
                
                uploaded++;
                if (uploaded === filesToUpload.length) {
                    console.log('All files uploaded via SFTP. Running build...');
                    conn.exec('cd /var/www/lk.viruzs.my.id && npm run build && php artisan optimize:clear', (err, stream) => {
                        if (err) throw err;
                        stream.on('close', () => {
                            console.log('Build completed');
                            conn.end();
                        }).on('data', (data) => console.log('STDOUT: ' + data))
                          .stderr.on('data', (data) => console.log('STDERR: ' + data));
                    });
                }
            });
        });
    });
}).connect({
    host: '157.66.140.17',
    port: 22,
    username: 'root',
    password: 'viruzs123',
    readyTimeout: 99999
});
