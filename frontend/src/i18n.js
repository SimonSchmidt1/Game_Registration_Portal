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
  { code: 'sk', label: 'Slovenčina', country: 'sk' },
  { code: 'en', label: 'English',    country: 'gb' },
  { code: 'cs', label: 'Čeština',    country: 'cz' },
  { code: 'pl', label: 'Polski',     country: 'pl' },
  { code: 'hu', label: 'Magyar',     country: 'hu' },
  { code: 'de', label: 'Deutsch',    country: 'de' },
  { code: 'fr', label: 'Français',   country: 'fr' },
  { code: 'it', label: 'Italiano',   country: 'it' },
  { code: 'es', label: 'Español',    country: 'es' },
  { code: 'pt', label: 'Português',  country: 'pt' },
  { code: 'nl', label: 'Nederlands', country: 'nl' },
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
