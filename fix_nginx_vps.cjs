const { Client } = require('ssh2');
const conn = new Client();
conn.on('ready', () => {
    const fixCmd = `
    sed -i 's/tclient_max_body_size/client_max_body_size/g' /etc/nginx/nginx.conf
    nginx -t && systemctl restart nginx
    `;

    conn.exec(fixCmd, (err, stream) => {
        if (err) throw err;
        stream.on('close', (code, signal) => {
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
