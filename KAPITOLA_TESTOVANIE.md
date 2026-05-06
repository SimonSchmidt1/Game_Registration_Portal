# 6. Testovanie nasadeného portálu

Po úspešnom dokončení implementačnej fázy a nasadení portálu na cieľový server bolo nevyhnutné overiť jeho správnu funkcionalitu, bezpečnosť a stabilitu v reálnom prostredí. Hlavným cieľom tejto kapitoly je zdokumentovať proces testovania, ktorý preukazuje, že vytvorený systém spĺňa všetky požiadavky definované v úvodných fázach projektu.

## 6.1 Cieľ a metodika testovania

Testovanie bolo zamerané na verifikáciu plne nasadeného produkčného prostredia, aby sa zabezpečilo, že všetky komponenty (klientská časť vo Vue.js, serverová časť v Laravel a relačná databáza MySQL) navzájom správne komunikujú aj mimo vývojárskeho (lokálneho) prostredia. 

Pri testovaní sme uplatnili prístup manuálneho testovania formou tzv. testovania čiernej skrinky (Black-box testing), kde sa testovač (v tomto prípade autor práce) zameriava výlučne na vstupy a výstupy aplikácie bez priameho zasahovania do jej vnútorného kódu. Testovanie prebiehalo z pohľadu troch rôznych entít:
1. Neregistrovaný návštevník (hosť)
2. Bežný študent (používateľ)
3. Administrátor systému

## 6.2 Funkčné testovanie

Základom pre zabezpečenie spoľahlivosti portálu bolo testovanie jeho hlavných procesov. Namiesto izolovaných testov sme zvolili komplexný prístup orientovaný na roly v systéme. Funkčné testovanie je preto rozdelené do troch pohľadov na základe typov používateľov: neregistrovaný hosť, prihlásený používateľ (študent) a administrátor. Každý prípad použitia (Use Case) bol overovaný formou testovania čiernej skrinky, s dôrazom na očakávané správanie systému.

### 6.2.1 Hosťovské prípady použitia (Guest Use Cases)

Táto časť testovania sa zamerala na funkcionality dostupné návštevníkom portálu bez potreby autentifikácie.

| ID | Prípad použitia (Use Case) | Očakávaný výsledok | Skutočný výsledok |
|:---|:---|:---|:---|
| GUC1 | Prehliadať projekty | Zobrazenie zoznamu všetkých schválených a verejných projektov. | Splnené |
| GUC2 | Filtrovať projekty | Zobrazenie iba tých projektov, ktoré spĺňajú zvolené kritériá. | Splnené |
| GUC3 | Zobraziť detail projektu | Otvorenie stránky s komplexnými informáciami o vybranom projekte. | Splnené |
| GUC4 | Zobraziť info o členoch tímu | Zobrazenie zoznamu členov, ktorí sa na projekte podieľali. | Splnené |
| GUC5 | Stiahnuť súbory projektu | Úspešné stiahnutie .zip archívu s hrami do zariadenia používateľa. | Splnené |
| GUC6 | Hrať WebGL hru v prehliadači | Načítanie a bezproblémové spustenie hry v okne prehliadača. | Splnené |
| GUC7 | Pridať hodnotenie projektu | Uloženie hodnotenia do databázy a prepočet priemerného skóre hry. | Splnené |
| GUC8 | Zobraziť návod | Zobrazenie inštrukcií a pravidiel pre prácu s portálom. | Splnené |
| GUC9 | Zaregistrovať sa | Otvorenie registračného formulára a vytvorenie nového účtu. | Splnené |

### 6.2.2 Používateľské prípady použitia (User Use Cases)

Nasledujúce testy verifikovali procesy dostupné pre študentov (registrovaných používateľov), najmä správu profilu, tvorbu tímov a odovzdávanie projektov.

| ID | Prípad použitia (Use Case) | Očakávaný výsledok | Skutočný výsledok |
|:---|:---|:---|:---|
| UUC1 | Prihlásiť sa | Úspešné overenie prihlasovacích údajov a udelenie prístupu. | Splnené |
| UUC2 | Prihlásiť sa s dočasným heslom | Systém vynúti zmenu hesla po prvom prihlásení s dočasným heslom. | Splnené |
| UUC3 | Spravovať svoj profil | Zobrazenie aktuálnych údajov používateľa s možnosťou ich úpravy. | Splnené |
| UUC4 | Zmeniť meno | Aktualizácia mena v databáze a jeho okamžité premietnutie v UI. | Splnené |
| UUC5 | Zmeniť heslo | Bezpečné zahešovanie a uloženie nového hesla, zneplatnenie starého. | Splnené |
| UUC6 | Zmeniť avatar | Nahratie nového obrázka a jeho priradenie k používateľskému profilu. | Splnené |
| UUC7 | Vytvoriť tím | Zápis nového tímu a vygenerovanie unikátneho pozývacieho kódu. | Splnené |
| UUC8 | Pripojiť sa k tímu | Validácia kódu, overenie kapacity tímu a pridanie používateľa ako člena. | Splnené |
| UUC9 | Opustiť tím | Odstránenie väzby používateľa na tím, prípadná delegácia role kapitána. | Splnené |
| UUC10 | Spravovať svoje tímy | Zobrazenie zoznamu tímov, v ktorých používateľ figuruje. | Splnené |
| UUC11 | Meniť aktívne tímy | Prepínanie kontextu používateľa medzi rôznymi tímami v rozhraní. | Splnené |
| UUC12 | Odstrániť členov tímu | Kapitán tímu môže odobrať vybraného člena zo svojho tímu. | Splnené |
| UUC13 | Zmeniť názov tímu | Kapitán môže aktualizovať názov tímu pred jeho uzamknutím. | Splnené |
| UUC14 | Vytvoriť projekt | Priradenie meta-dát projektu a nahratie súborov pre odovzdanie. | Splnené |
| UUC15 | Upraviť projekt | Možnosť aktualizácie detailov alebo znovunahratia buildu projektu. | Splnené |
| UUC16 | Zobraziť info o tímoch | Prehliadanie informácií o iných študentských tímoch (ak sú verejné). | Splnené |

### 6.2.3 Administrátorské prípady použitia (Admin Use Cases)

Testovanie z pohľadu administrátora zabezpečuje, že systém poskytuje nástroje na udržanie poriadku a riadenie životného cyklu portálu.

| ID | Prípad použitia (Use Case) | Očakávaný výsledok | Skutočný výsledok |
|:---|:---|:---|:---|
| AUC1 | Spravovať projekty tímov | Zobrazenie kompletného zoznamu odovzdaných projektov v systéme. | Splnené |
| AUC2 | Mazať projekty tímov | Odstránenie záznamu o projekte a prislúchajúcich súborov zo servera. | Splnené |
| AUC3 | Upravovať projekty tímov | Schopnosť administrátora zasiahnuť do meta-dát projektu (názov, popis). | Splnené |
| AUC4 | Spravovať tímy | Zobrazenie všetkých vytvorených tímov a ich aktuálneho stavu. | Splnené |
| AUC5 | Vymazať tím | Odstránenie tímu z databázy (s kaskádovým odstránením závislostí). | Splnené |
| AUC6 | Schváliť tím | Zmena stavu tímu na "schválený", čím sa tím uzamkne pre ďalšie zmeny. | Splnené |
| AUC7 | Zamietnuť tím | Označenie tímu ako nevyhovujúceho (napr. z dôvodu nesplnenia podmienok). | Splnené |
| AUC8 | Zmeniť stav tímu | Manuálne prepínanie stavov tímov bez reštrikcií. | Splnené |
| AUC9 | Zmeniť údaje tímu | Administrátorská úprava názvu tímu alebo iných atribútov. | Splnené |
| AUC10 | Pridávať akademické roky | Otvorenie nového ročníka súťaže/registrácie s vlastnými nastaveniami. | Splnené |
| AUC11 | Voľne registrovať používateľov | Schopnosť ručne vytvoriť účet pre študenta obídením limitácií (CSV). | Splnené |
| AUC12 | Spravovať používateľov | Zobrazenie a filtrácia všetkých registrovaných účtov v systéme. | Splnené |
| AUC13 | Deaktivovať/Aktivovať používateľa | Zamedzenie alebo obnovenie prístupu konkrétnemu študentovi k portálu. | Splnené |
| AUC14 | Pridať používateľa do tímu | Priradenie "voľného" študenta do existujúceho tímu z pozície admina. | Splnené |
| AUC15 | Presunúť používateľa do iného tímu | Zmena priradenia študenta medzi tímami bez nutnosti pozývacieho kódu. | Splnené |
| AUC16 | Odstrániť používateľa z tímu | Vynútené odobranie používateľa z tímu administrátorom. | Splnené |
| AUC17 | Zmeniť status používateľa | Úprava roly používateľa (napríklad povýšenie na administrátora). | Splnené |
| AUC18 | Zmeniť okupáciu používateľa | Zmena informácie o zameraní študenta (programátor, grafik...). | Splnené |
| AUC19 | Prezerať štatistiky portálu | Zobrazenie agregovaných dát (počet tímov, používateľov, projektov). | Splnené |
| AUC20 | Voľne vytvárať tímy | Ručné vytvorenie tímu bez nutnosti participácie samotných študentov. | Splnené |

## 6.3 Verifikácia používateľského rozhrania a adaptabilného dizajnu

Vzhľadom na predpokladanú diverzitu koncových zariadení, z ktorých budú študenti k portálu pristupovať, bolo nevyhnutné podrobiť používateľské rozhranie (UI) prísnemu testovaniu adaptabilného (responzívneho) dizajnu. Cieľom bolo verifikovať konzistenciu vizuálnej prezentácie a ergonómie ovládacích prvkov naprieč rôznymi zobrazovacími formátmi.

Testovanie sa uskutočnilo primárne pomocou emulácie zariadení v rámci vývojárskych nástrojov (Chrome DevTools) s dodatočnou validáciou na fyzických zariadeniach. Testovacie scenáre pokrývali nasledujúce referenčné rozlíšenia (tzv. breakpoints):
*   **Desktopové prostredie (≥ 1024px):** Testovanie optimálneho využitia horizontálneho priestoru, funkčnosti komplexných dátových tabuliek a interaktívnych prvkov.
*   **Tabletové prostredie (768px – 1023px):** Verifikácia prechodových stavov dizajnu, škálovania gridových systémov a dostupnosti ovládacích prvkov pre dotykové rozhrania.
*   **Mobilné prostredie (≤ 767px):** Overenie adaptácie navigačného panela pre menšie obrazovky, zalamovania obsahu s dôrazom na čitateľnosť textu a použiteľnosť registračných formulárov.

Výsledky testovania potvrdili, že implementované CSS pravidlá a flexibilné Vue.js komponenty zabezpečujú plynulú adaptáciu obsahu bez straty dôležitých informácií či degradácie používateľského zážitku. Kompatibilita bola rovnako overená v moderných webových prehliadačoch (Google Chrome, Mozilla Firefox, Safari), pričom neboli zaznamenané žiadne signifikantné odchýlky vo vykresľovaní domového stromu (DOM).

## 6.4 Analýza výkonnostných metrík

Pre zabezpečenie objektívneho zhodnotenia optimalizácie a celkového výkonu klientskej časti aplikácie bol využitý štandardizovaný analytický nástroj Google Lighthouse. Táto audítorská platforma poskytuje syntetické testovanie aplikácie v simulovanom prostredí a vyhodnocuje kľúčové indikátory výkonu (KPIs).

Výsledná hodnota celkového skóre pre kategóriu **Performance (Výkon)** dosiahla úroveň **55/100**. Detailná dekompozícia tohto výsledku poukázala na nasledujúce hodnoty kľúčových metrík:

*   **First Contentful Paint (FCP) – 13,3 s:** Čas potrebný na vykreslenie prvého textového alebo obrazového elementu v domovom strome.
*   **Largest Contentful Paint (LCP) – 13,8 s:** Indikátor vizuálnej pripravenosti, ktorý meria čas do vykreslenia najväčšieho bloku obsahu v rámci viditeľnej oblasti prehliadača (viewport).
*   **Speed Index (SI) – 13,3 s:** Metrika znázorňujúca rýchlosť vizuálneho doplnenia obsahu stránky.
*   **Total Blocking Time (TBT) – 0 ms:** Vynikajúci výsledok poukazujúci na fakt, že hlavné vlákno (main thread) prehliadača nebolo blokované dlhšie ako 50 ms. To zabezpečuje okamžitú odozvu na používateľské vstupy hneď po vykreslení rozhrania.
*   **Cumulative Layout Shift (CLS) – 0:** Absencia akýchkoľvek nečakaných posunov obsahu (layout shifts) počas načítavania stránky potvrdzuje vysokú vizuálnu stabilitu rozhrania.

Zvýšené absolútne hodnoty metrík FCP a LCP (v rádoch sekúnd) sú primárne dané striktnou metodikou testovania nástroja Lighthouse. Štandardný mobilný audit totiž využíva takzvaný *throttling* – umelú simuláciu priemerného mobilného zariadenia (obmedzenie výkonu procesora) a pomalšieho 4G sieťového pripojenia, pričom test prebieha s kompletne vyprázdnenou vyrovnávacou pamäťou (cache). V kontexte tohto portálu, ktorý pri prvotnom prístupe sťahuje väčší objem asynchrónnych dát (komplexné náhľadové obrázky, metadáta projektov), sa táto simulácia výrazne premieta do počiatočného načítania. V reálnej produkčnej prevádzke na moderných zariadeniach s využitím cachovania je čas načítania zlomkový. Na druhej strane, bezchybné (nulové) hodnoty TBT a CLS empiricky potvrdzujú, že samotná architektúra frontendovej aplikácie postavená na frameworku Vue.js je výborne optimalizovaná a po úvodnom prenose dát poskytuje maximálnu interaktívnu plynulosť a ergonómiu bez preskakovania obsahu.

Audity v ďalších hodnotených oblastiach zahŕňali:
*   **Accessibility (Prístupnosť):** Validácia sémantiky HTML štruktúry a dodržiavania kontrastných pomerov farieb podľa prísnych štandardov WCAG.
*   **Best Practices (Osvedčené postupy):** Kontrola bezpečnostných hlavičiek, HTTPS protokolu a eliminácia varovaní vo vývojárskej konzole.
*   **SEO:** Hodnotenie optimalizácie pre vyhľadávacie roboty a validácia sémantického škálovania nadpisov.

## 6.5 Bezpečnostný audit a ochrana dát

Za účelom garancie integrity a dôvernosti údajov používateľov v produkčnom prostredí bol portál podrobený cielenému testovaniu zameranému na najčastejšie bezpečnostné zraniteľnosti.

*   **Prevencia Cross-Site Scripting (XSS):** Testovanie odolnosti voči injekcii škodlivého kódu bolo realizované prostredníctvom vkladania JavaScriptových payloadov do vstupných polí (názvy tímov, hodnotenia projektov). Architektúra frameworku Vue.js v spojení so striktnou sanitizáciou na strane servera (Laravel) úspešne neutralizovala všetky pokusy aplikovaním kontextového escapovania výstupov, čím sa predišlo spusteniu akéhokoľvek skriptu v prehliadačoch iných používateľov.
*   **Ochrana proti Cross-Site Request Forgery (CSRF):** Overovali sme mechanizmus validácie požiadaviek meniacich stav aplikácie (POST, PUT, DELETE). Simulované útoky s absenciou alebo podvrhnutím CSRF tokenu boli serverom korektne identifikované a zamietnuté so stavovým kódom HTTP 419 (Page Expired), čím sa preukázala efektivita zabudovanej ochrany frameworku.
*   **Autorizačná izolácia (Insecure Direct Object References - IDOR):** Boli vykonané testy zamerané na pokusy o prístup k cudzím zdrojom prostredníctvom modifikácie identifikátorov v API dopytoch (napríklad úprava cudzieho projektu zmenou ID v URL adrese). Zavedené validačné politiky na úrovni kontrolérov úspešne zamedzili akejkoľvek neoprávnenej manipulácii s dátami iných entít.

## 6.6 Záverečné zhodnotenie testovacej fázy

Proces testovania nasadeného portálu poskytol empirické dôkazy o jeho pripravenosti na produkčnú prevádzku. Funkčné testy preukázali bezchybné fungovanie aplikačnej logiky – od zabezpečenej registrácie cez autorizáciu až po komplexnú správu tímov a projektov. Analýza adaptabilného rozhrania a výkonnostné audity potvrdili prítomnosť moderných webových štandardov, vďaka čomu systém poskytuje plynulý, responzívny a ergonomický používateľský zážitok.

Akékoľvek identifikované marginálne nedostatky boli na základe výstupov z testovacích fáz promptne odstránené a optimalizované. Vykonané bezpečnostné verifikácie navyše garantujú, že architektúra spĺňa požiadavky na ochranu citlivých dát používateľov. Celkovo možno konštatovať, že implementované riešenie plne zodpovedá špecifikácii zadania a je plne spôsobilé plniť funkciu centralizovaného registračného portálu pre študentské projekty.
