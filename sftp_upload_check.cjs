const Client = require('ssh2-sftp-client');
const sftp = new Client();
const config = {
    host: '157.66.140.17',
    port: 22,
    username: 'root',
    privateKey: require('fs').readFileSync('C:\\Users\\vgc43\\.ssh\\id_rsa')
};
sftp.connect(config).then(() => {
    return sftp.put('check_belum_lunas.php', '/var/www/lk.viruzs.my.id/check_belum_lunas.php');
}).then(() => {
    console.log('Upload success');
    sftp.end();
}).catch(err => {
    console.error(err.message);
    sftp.end();
});
