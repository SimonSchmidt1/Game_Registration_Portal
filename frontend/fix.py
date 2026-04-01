import sys

with open('src/views/AdminView.vue', 'r', encoding='utf-8') as f:
    lines = f.readlines()

new_lines = []
in_pending = False
for line in lines:
    if '<!-- Pending Teams -->' in line:
        in_pending = True
        new_lines.append(line)
        new_lines.append('          <div \n')
        new_lines.append('            :class="[\n')
        new_lines.append('              \'rounded-2xl p-5 border shadow-lg transition-all duration-300 flex flex-col items-center justify-center text-center\',\n')
        new_lines.append('              (stats.pending_teams || 0) > 0 \n')
        new_lines.append('                ? \'bg-gradient-to-br from-amber-900/40 to-orange-950/40 border-amber-500/50 shadow-amber-500/20 hover:border-amber-400/80 hover:shadow-amber-500/40\' \n')
        new_lines.append('                : \'bg-gradient-to-br from-gray-800 to-gray-900 border-gray-700 hover:border-amber-500/50 hover:shadow-amber-500/10\'\n')
        new_lines.append('            ]"\n')
        new_lines.append('          >\n')
        continue
        
    if in_pending:
        if '<span class="text-gray-400 text-xs' in line:
            in_pending = False
            new_lines.append(line)
        continue
        
    if not in_pending:
        new_lines.append(line)

with open('src/views/AdminView.vue', 'w', encoding='utf-8') as f:
    f.writelines(new_lines)
