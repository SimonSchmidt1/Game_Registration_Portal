const fs = require('fs');
let c = fs.readFileSync('src/views/AdminView.vue', 'utf8');

c = c.replace(/<\/tr>\s*<\/div>\s*<\/div>\s*<\/div>\s*<\/div>\s*<\/div>\s*<\/template>/, '</tr>\n            </template>');

c = c.replace(/<\/template>\s*<script setup>/, '  </div>\n  </div>\n  </div>\n  </div>\n  </div>\n</template>\n\n<script setup>');

fs.writeFileSync('src/views/AdminView.vue', c, 'utf8');
console.log('Fixed exactly!');
