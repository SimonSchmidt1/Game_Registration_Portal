const fs = require('fs');
let c = fs.readFileSync('src/views/AdminView.vue', 'utf8');
c = c.replace(/<\/template>/, '    </div>\n    </div>\n    </div>\n    </div>\n    </div>\n</template>');
fs.writeFileSync('src/views/AdminView.vue', c, 'utf8');
