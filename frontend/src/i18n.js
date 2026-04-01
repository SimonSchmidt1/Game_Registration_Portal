import { createI18n } from 'vue-i18n'

import sk from './locales/sk.json'
import en from './locales/en.json'
import es from './locales/es.json'
import de from './locales/de.json'
import fr from './locales/fr.json'
import cs from './locales/cs.json'
import pl from './locales/pl.json'
import hu from './locales/hu.json'
import pt from './locales/pt.json'
import it from './locales/it.json'
import nl from './locales/nl.json'

export const SUPPORTED_LOCALES = [
  { code: 'sk', label: 'Slovenčina', flag: '🇸🇰' },
  { code: 'en', label: 'English',    flag: '🇬🇧' },
  { code: 'cs', label: 'Čeština',    flag: '🇨🇿' },
  { code: 'pl', label: 'Polski',     flag: '🇵🇱' },
  { code: 'hu', label: 'Magyar',     flag: '🇭🇺' },
  { code: 'de', label: 'Deutsch',    flag: '🇩🇪' },
  { code: 'fr', label: 'Français',   flag: '🇫🇷' },
  { code: 'it', label: 'Italiano',   flag: '🇮🇹' },
  { code: 'es', label: 'Español',    flag: '🇪🇸' },
  { code: 'pt', label: 'Português',  flag: '🇵🇹' },
  { code: 'nl', label: 'Nederlands', flag: '🇳🇱' },
]

const LOCALE_CODES = SUPPORTED_LOCALES.map(l => l.code)
const DEFAULT_LOCALE = 'sk'
const FALLBACK_LOCALE = 'en'

function detectLocale() {
  const saved = localStorage.getItem('locale_preference')
  if (saved && LOCALE_CODES.includes(saved)) return saved

  const browser = (navigator.language || navigator.userLanguage || '').split('-')[0].toLowerCase()
  return LOCALE_CODES.includes(browser) ? browser : DEFAULT_LOCALE
}

const i18n = createI18n({
  legacy: false,
  locale: detectLocale(),
  fallbackLocale: FALLBACK_LOCALE,
  messages: { sk, en, es, de, fr, cs, pl, hu, pt, it, nl }
})

export function setLocale(code) {
  if (!LOCALE_CODES.includes(code)) return
  i18n.global.locale.value = code
  localStorage.setItem('locale_preference', code)
  document.documentElement.setAttribute('lang', code)
}

export default i18n
