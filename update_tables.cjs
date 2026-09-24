const fs = require('fs');
const path = require('path');

function walkDir(dir, callback) {
  fs.readdirSync(dir).forEach(f => {
    let dirPath = path.join(dir, f);
    let isDirectory = fs.statSync(dirPath).isDirectory();
    isDirectory ? walkDir(dirPath, callback) : callback(path.join(dir, f));
  });
}

function updateClasses(classString) {
    let classes = classString.split(/\s+/);
    let newClasses = [];
    for (let c of classes) {
        if (c === 'px-6') newClasses.push('px-3', 'sm:px-6');
        else if (c === 'px-4') newClasses.push('px-2', 'sm:px-4');
        else if (c === 'py-4') newClasses.push('py-2', 'sm:py-4');
        else if (c === 'py-3.5') newClasses.push('py-2', 'sm:py-3.5');
        else if (c === 'py-3') newClasses.push('py-2', 'sm:py-3');
        else if (c === 'text-sm') newClasses.push('text-xs', 'sm:text-sm');
        else newClasses.push(c);
    }
    return newClasses.join(' ');
}

function updateDivClasses(classString) {
    let classes = classString.split(/\s+/);
    let newClasses = [];
    let isAvatar = classes.includes('w-10') && classes.includes('h-10') && classes.includes('rounded-full');
    
    for (let c of classes) {
        if (isAvatar) {
            if (c === 'w-10') newClasses.push('w-8', 'sm:w-10');
            else if (c === 'h-10') newClasses.push('h-8', 'sm:h-10');
            else if (c === 'text-base') newClasses.push('text-sm', 'sm:text-base');
            else if (c === 'text-sm') newClasses.push('text-xs', 'sm:text-sm');
            else newClasses.push(c);
        } else {
            newClasses.push(c);
        }
    }
    return newClasses.join(' ');
}

walkDir('resources/js/Pages', function(filePath) {
  if (filePath.endsWith('.vue')) {
    let content = fs.readFileSync(filePath, 'utf8');
    let original = content;

    content = content.replace(/<(th|td|table|thead|tbody|tr)([^>]*)class="([^"]*)"([^>]*)>/gi, (match, tag, before, classString, after) => {
        return '<' + tag + before + 'class="' + updateClasses(classString) + '"' + after + '>';
    });
    
    content = content.replace(/<div([^>]*)class="([^"]*)"([^>]*)>/gi, (match, before, classString, after) => {
        let newClasses = updateDivClasses(classString);
        if (newClasses !== classString) {
            return '<div' + before + 'class="' + newClasses + '"' + after + '>';
        }
        return match;
    });

    if (content !== original) {
      fs.writeFileSync(filePath, content, 'utf8');
      console.log('Updated: ' + filePath);
    }
  }
});
