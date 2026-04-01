const fs = require('fs');
let c = fs.readFileSync('src/views/AdminView.vue', 'utf8');

c = c.replace(/<\/Dialog>\s*(<\/div>\s*)+<\/template>/, '</Dialog>\n  </div>\n</template>');

fs.writeFileSync('src/views/AdminView.vue', c, 'utf8');
console.log('Fixed bottom tags!');
