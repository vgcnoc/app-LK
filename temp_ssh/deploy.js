const { Client } = require('ssh2');

const conn = new Client();
conn.on('ready', () => {
  console.log('Client :: ready');
  const appPath = '/var/www/lk.viruzs.my.id';
  
  const cmd = `cd ${appPath} && git pull && npm run build`;
  console.log('Executing:', cmd);
  
  conn.exec(cmd, (err, stream) => {
    if (err) throw err;
    stream.on('close', (code, signal) => {
      console.log('Deploy completed with code:', code);
      conn.end();
    }).on('data', (data) => {
      process.stdout.write(data.toString());
    }).stderr.on('data', (data) => {
      process.stderr.write(data.toString());
    });
  });
}).connect({
  host: '157.66.140.17',
  port: 22,
  username: 'root',
  password: 'viruzs123'
});
