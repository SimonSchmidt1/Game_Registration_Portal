const fs = require('fs');
const c = fs.readFileSync('src/views/AdminView.vue', 'utf8');

const templateMatch = c.match(/<template>([\s\S]*?)<\/template>/);
if (!templateMatch) {
    console.log('No template found!');
    process.exit();
}
const templateStr = templateMatch[1];
const openDivs = (templateStr.match(/<div\b/g) || []).length;
const closeDivs = (templateStr.match(/<\/div>/g) || []).length;
console.log('Open:', openDivs, 'Close:', closeDivs);
