const fs = require('fs');
const htmlToDocx = require('html-to-docx');

const [,, inputPath, outputPath] = process.argv;

if (!inputPath || !outputPath) {
    console.error('Usage: node export-docx.js <input.html> <output.docx>');
    process.exit(1);
}

const html = fs.readFileSync(inputPath, 'utf8');

htmlToDocx(html)
    .then((buffer) => {
        fs.writeFileSync(outputPath, buffer);
    })
    .catch((error) => {
        console.error(error);
        process.exit(1);
    });
