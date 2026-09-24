const fs = require('fs');
const path = require('path');

function walkDir(dir, callback) {
  fs.readdirSync(dir).forEach(f => {
    let dirPath = path.join(dir, f);
    let isDirectory = fs.statSync(dirPath).isDirectory();
    isDirectory ? walkDir(dirPath, callback) : callback(path.join(dir, f));
  });
}

walkDir('resources/js/Pages', function(filePath) {
  if (filePath.endsWith('.vue')) {
    let content = fs.readFileSync(filePath, 'utf8');
    let original = content;

    // exact string matches for common table padding (only if they don't already have sm:)
    content = content
        .replace(/\bpx-6 py-4\b(?! sm:)/g, 'px-3 py-3 sm:px-6 sm:py-4')
        .replace(/\bpx-6 py-3\.5\b(?! sm:)/g, 'px-3 py-2.5 sm:px-6 sm:py-3.5')
        .replace(/\bpx-6 py-3\b(?! sm:)/g, 'px-3 py-2 sm:px-6 sm:py-3')
        .replace(/\bpx-4 py-4\b(?! sm:)/g, 'px-2 py-3 sm:px-4 sm:py-4')
        .replace(/\bpx-4 py-3\.5\b(?! sm:)/g, 'px-2 py-2.5 sm:px-4 sm:py-3.5')
        .replace(/\bpx-4 py-3\b(?! sm:)/g, 'px-2 py-2 sm:px-4 sm:py-3')
        .replace(/\btext-sm\b(?! sm:)/g, 'text-xs sm:text-sm')
        .replace(/\bw-10 h-10 rounded-full\b(?! sm:)/g, 'w-8 h-8 sm:w-10 sm:h-10 rounded-full')
        .replace(/\bw-12 h-12 rounded-full\b(?! sm:)/g, 'w-10 h-10 sm:w-12 sm:h-12 rounded-full');

    if (content !== original) {
      fs.writeFileSync(filePath, content, 'utf8');
      console.log('Updated: ' + filePath);
    }
  }
});
