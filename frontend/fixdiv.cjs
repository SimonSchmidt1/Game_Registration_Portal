const fs = require('fs');
let c = fs.readFileSync('src/views/AdminView.vue', 'utf8');
c = c.replace('</div>\n    </div>\n\n      <!-- Pending Teams Section (if any) -->', '</div>\n\n      <!-- Pending Teams Section (if any) -->');
c = c.replace('</div>\r\n    </div>\r\n\r\n      <!-- Pending Teams Section (if any) -->', '</div>\r\n\r\n      <!-- Pending Teams Section (if any) -->');
c = c.replace(/<\/div>\s*<\/div>\s*<!-- Pending Teams Section \(if any\) -->/g, '</div>\n\n      <!-- Pending Teams Section (if any) -->');
fs.writeFileSync('src/views/AdminView.vue', c, 'utf8');
console.log('Fixed div');
