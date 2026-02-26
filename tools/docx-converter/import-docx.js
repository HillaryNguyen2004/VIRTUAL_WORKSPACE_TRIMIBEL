const fs = require('fs');
const path = require('path');
const mammoth = require('mammoth');

const [,, inputPath, outputPath] = process.argv;

if (!inputPath || !outputPath) {
    console.error('Usage: node import-docx.js <input.docx> <output.html>');
    process.exit(1);
}

const styleMapPath = process.env.DOCX_STYLE_MAP_PATH
    ? path.resolve(process.env.DOCX_STYLE_MAP_PATH)
    : path.resolve(__dirname, 'style-map.txt');

const styleMap = fs.existsSync(styleMapPath)
    ? fs.readFileSync(styleMapPath, 'utf8')
        .split(/\r?\n/)
        .map((line) => line.trim())
        .filter((line) => line && !line.startsWith('#'))
    : [];

const options = {
    styleMap,
    includeDefaultStyleMap: true,
    convertImage: mammoth.images.inline((image) =>
        image.read('base64').then((data) => ({
            src: `data:${image.contentType};base64,${data}`
        }))
    )
};

mammoth.convertToHtml({ path: inputPath }, options)
    .then((result) => {
        fs.writeFileSync(outputPath, result.value || '', 'utf8');
        if (result.messages && result.messages.length) {
            console.warn(JSON.stringify(result.messages));
        }
    })
    .catch((error) => {
        console.error(error);
        process.exit(1);
    });
