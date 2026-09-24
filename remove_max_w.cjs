const fs = require('fs');
const path = require('path');

const directoryPath = path.join(__dirname, 'resources', 'js', 'Pages');

function removeMaxW(filePath) {
    if (filePath.endsWith('Welcome.vue') || filePath.endsWith('Login.vue') || filePath.endsWith('Register.vue')) {
        return;
    }
    
    let content = fs.readFileSync(filePath, 'utf8');
    
    // Only modify if the file has AuthenticatedLayout
    if (!content.includes('AuthenticatedLayout')) {
        return;
    }
    
    let original = content;
    
    // Replace max-w-7xl or max-w-[1400px] with max-w-full in the top-level divs that have mx-auto
    content = content.replace(/max-w-7xl\s+mx-auto/g, 'max-w-full mx-auto');
    content = content.replace(/mx-auto\s+max-w-7xl/g, 'mx-auto max-w-full');
    content = content.replace(/max-w-\[1400px\]\s+mx-auto/g, 'max-w-full mx-auto');
    
    if (original !== content) {
        fs.writeFileSync(filePath, content, 'utf8');
        console.log('Updated: ' + filePath);
    }
}

function processDirectory(dir) {
    fs.readdirSync(dir).forEach(file => {
        const fullPath = path.join(dir, file);
        if (fs.statSync(fullPath).isDirectory()) {
            processDirectory(fullPath);
        } else if (fullPath.endsWith('.vue')) {
            removeMaxW(fullPath);
        }
    });
}

processDirectory(directoryPath);
console.log('Done');
