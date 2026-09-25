const fs = require('fs');
const pdf = require('pdf-parse');
 
let dataBuffer = fs.readFileSync('C:\\Users\\v\\.gemini\\antigravity-ide\\brain\\c13c727a-f82a-4b16-8984-32a2a448460a\\.user_uploaded\\media_1790328161001.pdf');
 
pdf(dataBuffer).then(function(data) {
    console.log("NUMPAGES:", data.numpages);
    console.log("TEXT:", data.text);
}).catch(err => console.error(err));
