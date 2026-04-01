const fs = require('fs');
let c = fs.readFileSync('src/views/AdminView.vue', 'utf8');

c = c.replace(/(\s*)<!-- Team Details Dialog -->/, '\</div>\</div>\</div>\</div>\</div>\<!-- Team Details Dialog -->');

fs.writeFileSync('src/views/AdminView.vue', c, 'utf8');
