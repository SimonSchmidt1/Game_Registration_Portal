const fs = require('fs');
let c = fs.readFileSync('src/views/AdminView.vue', 'utf8');

c = c.replace(
  />\s*<\/div>\s*<!-- Pending Teams Section \(if any\) -->/,
  '>\n          </div>\n        </div>\n      </div>\n    </div>\n\n    <!-- Pending Teams Section (if any) -->'
);

fs.writeFileSync('src/views/AdminView.vue', c, 'utf8');
