const { Client } = require('ssh2');
const fs = require('fs');

const conn = new Client();
conn.on('ready', () => {
  conn.sftp((err, sftp) => {
    if (err) throw err;
    sftp.fastPut('test_sync.php', '/var/www/lk.viruzs.my.id/test_sync.php', (err) => {
      if (err) throw err;
      console.log('File uploaded');
      conn.end();
    });
  });
}).connect({
  host: '157.66.140.17',
  port: 22,
  username: 'root',
  password: 'viruzs123'
});
