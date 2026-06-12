const fs = require('fs');
const htmlToDocx = require('html-to-docx');

const [,, inputPath, outputPath] = process.argv;

if (!inputPath || !outputPath) {
    console.error('Usage: node export-docx.js <input.html> <output.docx>');
    process.exit(1);
}

const rawHtml = fs.readFileSync(inputPath, 'utf8');
const trimmed = (rawHtml || '').trim();
const html = trimmed
    ? (trimmed.includes('<html') ? trimmed : `<!doctype html><html><body>${trimmed}</body></html>`)
    : '<!doctype html><html><body><p></p></body></html>';

htmlToDocx(html)
    .then((buffer) => {
        fs.writeFileSync(outputPath, buffer);
    })
    .catch((error) => {
        console.error(error);
        process.exit(1);
    });
