const fs = require('fs');
const path = require('path');

const locales_dir = path.join(__dirname, 'src/locales');

const translations = {
    'sk': {
        'profile.remove_avatar_btn': 'Odstrániť avatar',
        'profile.remove_avatar_success': 'Avatar bol odstránený. Znovu sa zobrazujú iniciály.'
    },
    'cs': {
        'profile.remove_avatar_btn': 'Odebrat avatar',
        'profile.remove_avatar_success': 'Avatar byl odstraněn. Znovu se zobrazují iniciály.'
    },
    'de': {
        'profile.remove_avatar_btn': 'Avatar entfernen',
        'profile.remove_avatar_success': 'Avatar entfernt. Initialen werden wieder angezeigt.'
    },
    'es': {
        'profile.remove_avatar_btn': 'Eliminar avatar',
        'profile.remove_avatar_success': 'Avatar eliminado. Las iniciales se muestran de nuevo.'
    },
    'fr': {
        'profile.remove_avatar_btn': "Supprimer l'avatar",
        'profile.remove_avatar_success': 'Avatar supprimé. Les initiales sont de nouveau affichées.'
    },
    'hu': {
        'profile.remove_avatar_btn': 'Avatár eltávolítása',
        'profile.remove_avatar_success': 'Avatár eltávolítva. Újra a kezdőbetűk jelennek meg.'
    },
    'it': {
        'profile.remove_avatar_btn': 'Rimuovi avatar',
        'profile.remove_avatar_success': 'Avatar rimosso. Vengono mostrate di nuovo le iniziali.'
    },
    'nl': {
        'profile.remove_avatar_btn': 'Avatar verwijderen',
        'profile.remove_avatar_success': 'Avatar verwijderd. Initialen worden weer getoond.'
    },
    'pl': {
        'profile.remove_avatar_btn': 'Usuń avatar',
        'profile.remove_avatar_success': 'Avatar usunięty. Ponownie wyświetlane są inicjały.'
    },
    'pt': {
        'profile.remove_avatar_btn': 'Remover avatar',
        'profile.remove_avatar_success': 'Avatar removido. As iniciais são mostradas novamente.'
    }
};

function setValue(obj, path, value) {
    const keys = path.split('.');
    for (let i = 0; i < keys.length - 1; i++) {
        if (!obj[keys[i]]) obj[keys[i]] = {};
        obj = obj[keys[i]];
    }
    obj[keys[keys.length - 1]] = value;
}

for (const [lang, map] of Object.entries(translations)) {
    const fp = path.join(locales_dir, lang + '.json');
    if (fs.existsSync(fp)) {
        const data = JSON.parse(fs.readFileSync(fp, 'utf-8'));
        for (const [k, v] of Object.entries(map)) {
            setValue(data, k, v);
        }
        fs.writeFileSync(fp, JSON.stringify(data, null, 2) + '\n', 'utf-8');
        console.log("Updated " + lang + ".json");
    } else {
        console.log("Skipped " + lang + ".json (not found)");
    }
}
