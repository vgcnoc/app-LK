const fs = require('fs');
const path = require('path');

const directoryPath = path.join(__dirname, 'resources', 'js', 'Pages');

function removePadding(filePath) {
    if (filePath.endsWith('Welcome.vue') || filePath.endsWith('Login.vue') || filePath.endsWith('Register.vue')) {
        return;
    }
    
    let content = fs.readFileSync(filePath, 'utf8');
    
    if (!content.includes('AuthenticatedLayout')) {
        return;
    }
    
    let original = content;
    
    content = content.replace(/(class="[^"]*max-w-full mx-auto[^"]*)\s+px-4 sm:px-6 lg:px-8([^"]*")/g, '$1$2');
    content = content.replace(/(class="[^"]*mx-auto max-w-full[^"]*)\s+px-4 sm:px-6 lg:px-8([^"]*")/g, '$1$2');
    content = content.replace(/(class="[^"]*max-w-full mx-auto[^"]*)\s+sm:px-6 lg:px-8([^"]*")/g, '$1$2');
    content = content.replace(/(class="[^"]*mx-auto max-w-full[^"]*)\s+sm:px-6 lg:px-8([^"]*")/g, '$1$2');
    
    content = content.replace(/(class="[^"]*)(\s{2,})([^"]*")/g, '$1 $3');
    
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
            removePadding(fullPath);
        }
    });
}

processDirectory(directoryPath);
console.log('Done');
