const fs = require('fs');
let c = fs.readFileSync('src/views/AdminView.vue', 'utf8');
const goodText = fs.readFileSync('goodText.txt', 'utf8');

const sIdx = c.indexOf('    <!-- Dashboard Wrapping Container -->');
const eIdx = c.indexOf('      <!-- Master Teams View Data Table -->');

if (sIdx > -1 && eIdx > -1) {
  const badStr = c.substring(sIdx, eIdx);
  c = c.replace(badStr, goodText);
  fs.writeFileSync('src/views/AdminView.vue', c, 'utf8');
  console.log('Successfully replaced module.');
} else {
  console.log('Could not find boundaries.', sIdx, eIdx);
}
