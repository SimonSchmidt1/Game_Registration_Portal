const fs = require('fs');
let c = fs.readFileSync('src/views/AdminView.vue', 'utf8');

c = c.replace(/<\/div>(\s*)<\/div>(\s*)<\/div>(\s*)<\/div>(\s*)<\/div>(\s*)<\/div><!-- Team Details Dialog -->/, '<!-- Team Details Dialog -->');
c = c.replace(/  <\/div>\r?\n<\/template>/, '  </div>\n  </div>\n</template>');

fs.writeFileSync('src/views/AdminView.vue', c, 'utf8');
