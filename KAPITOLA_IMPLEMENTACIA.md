# 4. Implementácia

V tejto kapitole podrobne a chronologicky opisujem proces implementácie webového portálu. Vývoj bol rozdelený do troch logicky na seba nadväzujúcich fáz, presne v takom poradí, v akom prebiehal reálny vývoj. Najprv bola vybudovaná kompletná **backendová vrstva** (serverová aplikačná logika a aplikačné programovacie rozhranie – API). Túto vrstvu sme v počiatočnej fáze testovali len prostredníctvom minimalistického a dočasného používateľského rozhrania (základné HTML/JS formuláre a testovacie HTTP požiadavky). Až po plnej stabilizácii aplikačnej logiky sme pristúpili k vývoju moderného **frontendového používateľského rozhrania**. Paralelne s týmito fázami, so silným dôrazom v záverečných fázach vývoja, sme navrhovali a optimalizovali **databázovú štruktúru** na spoľahlivé uchovávanie dát a pokročilú manipuláciu s nimi.

---

## 4.1. Backend (Aplikačná logika a API)

Keďže základným predpokladom pre funkčný systém je robustné spracovanie a ukladanie dát, vývoj celého portálu začal práve pri backende. Počas celej prvej fázy sme abstrahovali od dizajnu a pracovali výhradne s testovacími dátami a odpoveďami vo formáte JSON. Tento prístup zabezpečil, že samotná biznis logika funguje autonómne a nezávisle od vizuálnej prezentácie.

### 4.1.1. Inštalácia a konfigurácia prostredia
Ako základ serverovej časti sme zvolili framework Laravel vo verzii 11. Celú štruktúru priečinkov sme inicializovali cez správcu balíkov Composer (`composer create-project`) a hneď po inštalácii sme vygenerovali unikátny bezpečnostný kľúč aplikácie (`php artisan key:generate`). V konfiguračnom súbore `.env` sme nastavili parametre pripojenia k databáze MySQL a explicitne sme upravili adresy lokálneho vývojového servera a frontendu (`APP_URL`, `FRONTEND_URL`) pre správne fungovanie pravidiel CORS. Na zabezpečenie API komunikácie s frontendom sme integrovali balík Laravel Sanctum. V rámci novej zjednodušenej štruktúry frameworku sme v súbore `bootstrap/app.php` povolili middleware na spracovanie stavových požiadaviek (stateful requests). Zvolený prístup nám umožnil bezpečne overovať používateľov pomocou zašifrovaných *HTTP-only* cookies v rámci rovnakej domény s využitím natívnej ochrany proti CSRF útokom.

### 4.1.2. Základy implementácie
Po počiatočnej konfigurácii frameworku sme prešli k implementácii jadra systému na báze architektonického vzoru MVC (Model-View-Controller). "View" vrstva bola v tomto prípade nahradená API odpoveďami.

**Príklad 1: Definícia dátového modelu (Project.php)**
Dátový model predstavuje v architektúre MVC objektovú reprezentáciu konkrétnej databázovej tabuľky a slúži ako komunikačný bod pre čítanie a zápis dát bez nutnosti písania priamych SQL dopytov. Tieto modely sme definovali v ORM (Object-Relational Mapping) systéme Eloquent. Atribútom `$fillable` sme striktne vymedzili polia povolené pre hromadný zápis (mass assignment). Atribútom `$casts` sme zabezpečili automatickú transformáciu dátových typov pri interakcii s databázou.

```php
<?php
class Project extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'team_id', 'academic_year_id', 'title', 'description', 
        'type', 'school_type', 'release_date', 'rating'
    ];

    protected $casts = [
        'files' => 'array',
        'metadata' => 'array',
        'release_date' => 'date',
        'rating' => 'decimal:2',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
```

**Príklad 2: Vytvorenie základného kontroléra pre projekty (ProjectController.php)**
Následne boli vytvorené kontroléry slúžiace ako sprostredkovatelia medzi modelmi a HTTP požiadavkami. `ProjectController` spravuje kompletný životný cyklus projektu.

```php
// Ukážka z backend/app/Http/Controllers/Api/ProjectController.php
class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $projects = Project::with(['team', 'academicYear'])->get();
        return response()->json($projects);
    }

    public function show($id)
    {
        $project = Project::with(['team', 'ratings'])->findOrFail($id);
        return response()->json($project);
    }
}
```

**Príklad 3: Vytvorenie základného kontroléra pre tímy (TeamController.php)**
Podobným spôsobom bol naimplementovaný aj `TeamController`, ktorý zabezpečoval úvodnú logiku pre vytváranie tímov a pridávanie používateľov.

```php
// Ukážka z backend/app/Http/Controllers/Api/TeamController.php
class TeamController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'academic_year_id' => 'required|exists:academic_years,id',
        ]);

        $team = Team::create($validated);
        return response()->json($team, 201);
    }
}
```

### 4.1.3. Implementácia vlastného middlewaru pre ochranu trás
Dôležitou bezpečnostnou vrstvou na backende sú tzv. middlewary, ktoré analyzujú a filtrujú prichádzajúce HTTP požiadavky ešte pred tým, než dorazia do kontroléra. Na obmedzenie prístupu k citlivým administrátorským funkciám sme naimplementovali vlastný middleware `EnsureUserIsAdmin`.

```php
// Ukážka z backend/app/Http/Middleware/EnsureUserIsAdmin.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Nepovolený prístup'], 403);
        }

        return $next($request);
    }
}
```

### 4.1.4. Architektúra RESTful API a Aplikačné smerovanie (Routing)
V kontexte vývoja webových aplikácií hovoríme o tzv. aplikačnom smerovaní (URL Routing), ktoré funguje ako hlavný komunikačný uzol na aplikačnej vrstve. Jeho úlohou je prijať HTTP požiadavku z frontendu a presne ju nasmerovať na príslušnú vnútornú logiku. 

Tieto konkrétne prístupové URL adresy nazývame **API koncové body (Endpoints)**. Každý endpoint (napríklad `POST /api/projects`) predstavuje presne definovanú adresu, na ktorej backend "počúva". Akonáhle na ňu dorazia dáta, smerovací mechanizmus (Router) vie, ktorý Kontrolér má zavolať a aké ochranné Middlewary má na požiadavku uplatniť. Na základe týchto pravidiel bol skompletizovaný definičný súbor `api.php`, ktorý zoskupuje koncové body do verejnej sféry a do sféry chránenej autentifikačným tokenom.

```php
// Ukážka z backend/routes/api.php
// Verejné routy – dostupné bez tokenu s ochranou proti zahlteniu (throttle)
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

// Chránené routy – vyžadujú autentifikačný token (Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/projects', [ProjectController::class, 'store'])->middleware('throttle:projects');
});

// Admin routy - aplikovaný vytvorený vlastný middleware
Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::delete('/projects/{project}', [AdminController::class, 'deleteProject']);
});
```

### 4.1.5. Streamovanie veľkých mediálnych súborov (Partial Content)
Pri prezentácii rozsiahlych multimediálnych súborov dochádza pri štandardnom prenose celého súboru k extrémnemu vyťaženiu pamäte servera a predĺženiu odozvy. Z tohto dôvodu bolo nevyhnutné implementovať mechanizmus postupného načítavania obsahu po menších blokoch dát. V rámci triedy `VideoStreamController` bola aplikovaná stratégia spracovania HTTP hlavičky `Range`, na základe ktorej server odpovedá stavovým kódom `206 Partial Content`. Tento prístup umožňuje asynchrónne streamovanie (tzv. dátový "chunking"), čím sa diametrálne znižuje pamäťová náročnosť na strane servera a optimalizuje sa celková priepustnosť siete.

```php
// Ukážka z backend/app/Http/Controllers/VideoStreamController.php
$rangeHeader = $request->header('Range');
if (preg_match('/bytes=(\d+)-(\d*)/', $rangeHeader, $matches)) {
    $start = (int) $matches[1];
    $end = $matches[2] !== '' ? (int) $matches[2] : $fileSize - 1;
    $length = $end - $start + 1;
    
    $headers['Content-Length'] = $length;
    $headers['Content-Range'] = "bytes $start-$end/$fileSize";
    
    return response()->stream(function() use ($fullPath, $start, $length) {
        $handle = fopen($fullPath, 'rb');
        fseek($handle, $start);
        $bytesRemaining = $length;
        
        while ($bytesRemaining > 0 && !feof($handle)) {
            $bytesToRead = min(1024 * 8, $bytesRemaining);
            echo fread($handle, $bytesToRead);
            $bytesRemaining -= $bytesToRead;
            flush();
        }
        fclose($handle);
    }, 206, $headers);
}
```

### 4.1.6. Transakcie a pesimistické zamykanie pri hodnoteniach
Pri asynchrónnom spracovávaní používateľských požiadaviek môže dôjsť k časovému súbehu (Race Condition), kedy systém prijme viacero paralelných požiadaviek na zápis, čo by bez adekvátnej ochrany viedlo k vzniku duplicitných záznamov. Pre zachovanie integrity dát bolo potrebné zaistiť exkluzívny prístup k upravovanému záznamu. Za týmto účelom implementuje `ProjectController` databázové transakcie v kombinácii s mechanizmom pesimistického zamykania záznamov (`lockForUpdate`). Táto technika garantuje, že počas vyhodnocovania a zápisu hodnotenia je daný riadok uzamknutý pre ostatné procesy, čím sa absolútne zamedzuje narušeniu konzistencie dát v distribuovanom prostredí.

```php
// Ukážka z backend/app/Http/Controllers/Api/ProjectController.php
return DB::transaction(function () use ($id, $user, $request, $project) {
    // Atomické zamknutie tabuľky pre daný riadok počas čítania
    $existingRating = GameRating::where('project_id', $id)
        ->where('user_id', $user->id)
        ->lockForUpdate()
        ->first();
        
    if ($existingRating) {
        return response()->json(['message' => 'Projekt už bol hodnotený.'], 422);
    }
    
    GameRating::create(['project_id' => $id, 'user_id' => $user->id, 'rating' => $request->rating]);
    $avgRating = GameRating::where('project_id', $id)->avg('rating');
    $project->update(['rating' => round($avgRating, 2)]);
    
    return response()->json(['message' => 'Hodnotenie uložené', 'rating' => $project->rating]);
});
```

### 4.1.7. Extrahovanie a validácia archívov pre WebGL projekty
Podpora natívneho spúšťania projektov vo formáte WebGL vyžaduje proces automatizovanej dekompresie nahraných archívov na strane servera, čo prináša bezpečnostné riziká spojené s možným prepisovaním systémových súborov. Z tohto dôvodu bolo nevyhnutné implementovať robustnú validáciu extrahovaných ciest ešte pred samotným zápisom na disk. Backend pri spracovaní dekompresie analyzuje každý súbor v archíve a aplikuje dôslednú prevenciu voči zraniteľnosti typu *Directory Traversal* (resp. *Path Traversal*). Akýkoľvek pokus o navigáciu do nadradených adresárov pomocou špecifických znakov spôsobí okamžité prerušenie dekompresného procesu a ochráni pracovný priestor aplikácie.

```php
// Ukážka z backend/app/Http/Controllers/Api/ProjectController.php
private function extractWebGLBuild(\Illuminate\Http\UploadedFile $zip, int $projectId): ?string
{
    $za = new \ZipArchive();
    if ($za->open($zip->getRealPath()) !== true) return null;

    for ($i = 0; $i < $za->count(); $i++) {
        $name = $za->statIndex($i)['name'];
        // Defenzívne programovanie: Zamedzenie útokom typu Path Traversal
        if (str_contains($name, '..') || str_starts_with($name, '/')) {
            $za->close();
            return null;
        }
    }
    $destPath = Storage::disk('public')->path('webgl/' . $projectId);
    $za->extractTo($destPath);
    $za->close();
    return $destPath;
}
```

### 4.1.8. Dynamické generovanie konfiguračných súborov servera (.htaccess)
Moderné herné enginy exportujú komprimované WebGL súbory, ktoré si pre správnu interpretáciu v klientskom prehliadači vyžadujú odosielanie špecifických HTTP hlavičiek o type kódovania. Aby sa predišlo manuálnym zásahom do konfigurácie webového servera pri každom novom projekte, bol navrhnutý plne automatizovaný systém modifikácie správania servera. Po úspešnom nahraní a dekompresii WebGL zostavy backend dynamicky vygeneruje lokálny konfiguračný súbor `.htaccess`. Tento súbor obsahuje presné HTTP direktívy pre webový server (v tomto prípade Apache), ktoré inštruujú prehliadač o použití kompresných formátov `gzip` a `brotli`, čím sa zabezpečí bezproblémové načítanie a spustenie virtuálneho prostredia.

```php
// Ukážka z backend/app/Http/Controllers/Api/ProjectController.php
$htaccessContent = <<<HTACCESS
<IfModule mod_mime.c>
  AddType application/wasm .wasm
  AddType application/javascript .js
  AddType application/octet-stream .data
  AddEncoding gzip .gz
  AddEncoding brotli .br
</IfModule>
<FilesMatch "\.wasm\.gz$">
  Header set Content-Encoding gzip
  ForceType application/wasm
</FilesMatch>
HTACCESS;
Storage::disk('public')->put('webgl/' . $projectId . '/.htaccess', $htaccessContent);
```

### 4.1.9. Dynamické zostavovanie databázových dopytov a autorizácia
Pre zabezpečenie flexibilného vyhľadávania nad rozsiahlymi dátovými štruktúrami je nevyhnutné dynamicky adaptovať databázové dopyty na základe premenlivých vstupných parametrov. Zároveň bolo dôležité prepojiť túto obsluhu dát s jemne zrnitým (fine-grained) autorizačným modelom. Na obsluhu variabilných URL Query parametrov bol implementovaný mechanizmus inkrementálneho zostavovania databázových dopytov (Query Building), ktorý na základe prítomnosti argumentov postupne rozširuje komplexnosť podmienok. Na úrovni business logiky je následne aplikovaná sofistikovaná kontrola oprávnení, kde sú operácie manipulácie s entitou projektu podmienené nielen autentifikáciou, ale aj verifikáciou kontextuálnej roly *Scrum Master* v rámci konkrétneho projektového tímu.

```php
// Ukážka z backend/app/Http/Controllers/Api/ProjectController.php
if (!$isAdmin) {
    // Verifikácia vlastníctva projektu a presnej roly v intersekčnej (pivot) tabuľke tímu
    $isScrumMaster = $project->team->members()
        ->where('users.id', $user->id)
        ->where('team_user.role_in_team', 'scrum_master')
        ->exists();

    if (!$isScrumMaster) {
        return response()->json(['message' => 'Iba Scrum Master tímu môže upravovať projekt.'], 403);
    }
}
```

#### 📌 Zoznam odkazov na zdrojový kód (Backend)
Kompletný zoznam referencií na zdrojové súbory prezentované v predchádzajúcej podkapitole:
*   **Bod 4.1.2:** Súbory `backend/app/Models/Project.php`, `backend/app/Http/Controllers/Api/ProjectController.php` a `backend/app/Http/Controllers/Api/TeamController.php`
*   **Bod 4.1.3:** Súbor `backend/app/Http/Middleware/EnsureUserIsAdmin.php`
*   **Bod 4.1.4:** Súbor `backend/routes/api.php`
*   **Bod 4.1.5:** Súbor `backend/app/Http/Controllers/VideoStreamController.php`
*   **Bod 4.1.6 až 4.1.9:** Súbor `backend/app/Http/Controllers/Api/ProjectController.php`

---

## 4.2. Frontend (Používateľské rozhranie)

Po úspešnej implementácii a otestovaní serverovej logiky sme našu pozornosť presunuli na vývoj komplexného používateľského rozhrania. Keďže moderné herné portály vyžadujú vysokú mieru interaktivity, rozhodli sme sa pre architektúru Single Page Application (SPA). Tento koncept nám umožnil stiahnuť väčšinu prezentačnej logiky priamo do klientskeho prehliadača. Vďaka tomu sme rapídne znížili záťaž na server, keďže asynchrónne žiadame iba nevyhnutné JSON dáta, zatiaľ čo vizuálne komponenty zostávajú staticky prednačítané. Takýto prístup nám zabezpečil mimoriadne plynulý chod aplikácie bez akéhokoľvek sekania alebo nutnosti úplného znovunačítavania stránky pri navigácii medzi sekciami.

### 4.2.1. Inštalácia a príprava prostredia
Ako hlavný technologický pilier prezentačnej vrstvy sme nasadili framework Vue.js vo verzii 3. Vývoj sme cielene stavali na modernom koncepte *Composition API*, ktorý nám umožnil lepšie zapuzdrenie a znovupoužiteľnosť reaktívnej logiky vo väčších komponentoch. Pre kompiláciu celého projektu sme sa vyhli pomalšiemu Webpacku a integrovali sme nástroj Vite, ktorý vďaka technológii Hot Module Replacement (HMR) extrémne zrýchlil náš vývojový cyklus. Samotnú inicializáciu klientskej časti sme zrealizovali prostredníctvom správcu balíkov Node Package Manager (NPM) spustením príkazu `npm create vite@latest`. Následnú správu závislostí sme taktiež riešili cez NPM. Do čistého prostredia sme pomocou príkazu `npm install` postupne doinštalovali kľúčové externé knižnice: `vue-router` pre správu klientskych trás, HTTP klienta `axios` na spoľahlivú komunikáciu s naším REST API a balík `vue-i18n` pre správu jazykových mutácií v reálnom čase. Všetky tieto moduly a ich exaktné verzie sa automaticky evidujú v konfiguračnom súbore `package.json`, čo nám zaručuje stopercentnú konzistenciu prostredia pri nasadzovaní do produkcie.

### 4.2.2. Základy implementácie
Aby sme predišli neprehľadnosti pri raste kódovej bázy, aplikovali sme striktnú separáciu komponentov. Zložité celky predstavujúce celé podstránky (napríklad zoznam projektov) sme zapuzdrili do adresára `Views`. Naopak, izolované a často opakované vizuálne prvky, ako sú tlačidlá alebo modálne okná, sme centralizovali v adresári `Components`.

**Príklad 1: Inicializácia hlavnej inštancie aplikácie (main.js)**
Životný cyklus našej Vue aplikácie štartuje vytvorením hlavnej inštancie v súbore `main.js`. Do tohto hlavného uzla sme následne injektovali všetky globálne závislosti, čím sme zabezpečili, že smerovač (`router`) a knižnica vizuálnych prvkov (`PrimeVue`) budú dostupné v celej hierarchii klientskeho rozhrania.

```javascript
// Ukážka z frontend/src/main.js
import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import PrimeVue from 'primevue/config'
import ToastService from 'primevue/toastservice'

const app = createApp(App)

app.use(router)
app.use(PrimeVue)
app.use(ToastService) 

app.mount('#app')
```

**Príklad 2: Mapovanie virtuálnych ciest (index.js)**
Komplexnú logiku prechodov medzi stránkami sme presunuli výhradne na stranu klienta pomocou virtuálneho smerovača. Jeho algoritmus aktívne sleduje zmeny v URL adrese prehliadača prostredníctvom History API. Akonáhle používateľ klikne na odkaz, smerovač zachytí túto požiadavku, analyzuje vopred definované cesty a namiesto klasického HTTP requestu iba dynamicky vymení príslušný Vue komponent v štruktúre DOM.

```javascript
// Ukážka z frontend/src/router/index.js
import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'
import LoginView from '../views/LoginView.vue'

const routes = [
  { path: '/', name: 'Home', component: HomeView },
  { path: '/login', name: 'Login', component: LoginView },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

export default router
```

**Príklad 3: Aplikačná brána a ochrana trás**
Keďže SPA architektúra sťahuje prezentačnú logiku priamo k používateľovi, museli sme implementovať robustnú autorizačnú bariéru už na úrovni frontendu. Zabezpečili sme to pomocou globálneho záchytného bodu smerovača, známeho ako `beforeEach` hook. Tento skript sa automaticky aktivuje pred každým pokusom o zmenu trasy a overuje prítomnosť autorizačného prístupového tokenu (Access Token) v lokálnom úložisku prehliadača. Ak používateľ token nemá a pokúša sa pristúpiť k chránenej sekcii, systém ho asynchrónne presmeruje na prihlasovaciu obrazovku.

```javascript
// Ukážka z frontend/src/router/index.js
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('access_token')

  if (to.meta.requiresAuth && !token) {
    next('/login')
  } else {
    next()
  }
})
```

### 4.2.3. Asynchrónne nahrávanie komplexných dát
Pri nahrávaní projektov sme narazili na fundamentálne obmedzenie formátu JSON, ktorý nie je stavaný na efektívny prenos rozsiahlych binárnych dát. Odosielanie gigabajtových WebGL archívov súčasne s textovými metadátami by pri použití štandardných požiadaviek spôsobovalo zlyhania. Tento problém sme vyriešili nasadením objektu `FormData`, ktorý nám umožnil dynamicky zostaviť dáta vo formáte `multipart/form-data`. Výsledkom tohto technického riešenia je schopnosť klienta asynchrónne odoslať heterogénnu zmes textu, obrázkov a archívov v rámci jedinej ucelenej HTTP požiadavky na náš backend.

```javascript
// Ukážka z frontend/src/views/AddProjectView.vue
const formData = new FormData()
formData.append('title', name.value)
formData.append('type', projectType.value)
formData.append('description', description.value || '')

if (files.value.splash_screen.file) formData.append('splash_screen', files.value.splash_screen.file)
if (projectType.value === 'webgl' && webglMode.value === 'build' && files.value.webgl_build.file) {
    formData.append('webgl_build', files.value.webgl_build.file)
}

const res = await apiFetch(url, { 
    method: 'POST', 
    headers: { 'Authorization': 'Bearer ' + token.value, 'Accept': 'application/json' }, 
    body: formData 
})
```

### 4.2.4. Dynamické formuláre so stavovým vykresľovaním
Vzhľadom na širokú paletu podporovaných prác, od klasických videí až po interaktívne WebGL buildy, sme museli navrhnúť vysoko adaptabilný používateľský formulár. Namiesto programovania viacerých separátnych obrazoviek sme siahli po technike podmienečného vykresľovania (conditional rendering). Vďaka reaktivite frameworku Vue systém v reálnom čase sleduje zmenu zvoleného typu projektu a dynamicky do štruktúry DOM vkladá, alebo z nej odstraňuje špecifické vstupné polia. Používateľom sme tak značne uľahčili prácu, keďže vidia vždy iba tie formulárové prvky, ktoré sú pre ich konkrétny projekt nevyhnutné.

```html
<!-- Ukážka z frontend/src/views/AddProjectView.vue -->
<template v-if="projectType === 'webgl'">
  <div class="apv-field">
    <label class="apv-label">{{ t('add_project.webgl_source_label') }}</label>
    <div class="apv-toggle">
      <button type="button" :class="['apv-toggle-btn', webglMode === 'build' ? 'apv-toggle-active' : '']" @click="webglMode = 'build'">
        {{ t('add_project.webgl_mode_build') }}
      </button>
      <button type="button" :class="['apv-toggle-btn', webglMode === 'url' ? 'apv-toggle-active' : '']" @click="webglMode = 'url'">
        {{ t('add_project.webgl_mode_url') }}
      </button>
    </div>
    <div v-if="webglMode === 'build'">
      <FileUpload name="webgl_build" mode="basic" accept=".zip" />
    </div>
  </div>
</template>
```

### 4.2.5. Komplexná integrácia internacionalizácie (i18n)
Pre zabezpečenie lepšej prístupnosti portálu pre zahraničných používateľov sme do klientskej vrstvy integrovali plnohodnotnú internacionalizáciu (i18n). Naše technické riešenie nevyužíva statické preklady, ale asynchrónny prekladový mechanizmus, ktorý dokáže na základe výberu používateľa reaktívne prepísať všetky textové reťazce v aplikácii bez straty aktuálneho stavu formulárov. Portál vďaka tomuto modulu aktuálne podporuje preklady až do 11 svetových jazykov, pričom predvoleným jazykom je slovenčina. Aby sme udržali plynulý používateľský zážitok, zvolený jazykový identifikátor okamžite serializujeme do perzistentného lokálneho úložiska, vďaka čomu si aplikácia pamätá preferenciu aj po zatvorení prehliadača.

```html
<!-- Ukážka z frontend/src/components/Navbar.vue -->
<div class="nav-lang-switcher" v-click-outside="() => showLangMenu = false">
  <button class="nav-btn-ghost nav-lang-btn" @click="showLangMenu = !showLangMenu">
    <span :class="`fi fi-${currentLocaleInfo.country} nav-lang-flag`"></span>
  </button>
  <div v-if="showLangMenu" class="nav-lang-menu">
    <button v-for="loc in SUPPORTED_LOCALES" :key="loc.code" @click="switchLocale(loc.code)">
      <span>{{ loc.label }}</span>
    </button>
  </div>
</div>
```

### 4.2.6. Riadenie vizuálnej kompozície pomocou Scroll Eventov
Pri dizajnovaní interakcií používateľského rozhrania sme kládli veľký dôraz na poskytovanie jemnej vizuálnej spätnej väzby. Navigačný panel sme preto obohatili o reaktívny algoritmus analyzujúci vektory rolovania stránky. Ak náš event listener zaznamená špecifickú intenzitu vertikálneho pohybu smerom nahor, JavaScript dočasne modifikuje triedy v objektovom modeli dokumentu. Tento zásah vyvolá plynulú CSS transformáciu v podobe dynamického svetelného záblesku. Takýmito drobnými mikroskopickými animáciami sme dosiahli výrazne modernejší a profesionálnejší vizuál celého systému.

```javascript
// Ukážka z frontend/src/components/Navbar.vue
let lastScrollY = 0;
let lastShineAt = 0;
function handleScroll() {
  const sy = window.scrollY;
  const now = Date.now();
  if (sy < lastScrollY - 6 && now - lastShineAt > 900) {
    lastShineAt = now;
    navRef.value?.classList.add('nav-glow', 'nav-shine');
    setTimeout(() => navRef.value?.classList.remove('nav-glow'), 500);
    setTimeout(() => navRef.value?.classList.remove('nav-shine'), 700);
  }
  lastScrollY = sy;
}
```

### 4.2.7. Svetlý a tmavý režim
Architektúru tmavého a svetlého režimu sme postavili na natívnych CSS premenných. Vyhli sme sa tak zbytočnému zaťažovaniu projektu externými knižnicami. Prepínanie režimov sme naprogramovali ako priamu manipuláciu s dátovými atribútmi na úrovni koreňového elementu `<html>`. Vďaka tomuto prístupu dokáže prehliadač v zlomku sekundy prepočítať celú farebnú paletu portálu. Aktualizovaný stav témy aplikácia následne ukladá do lokálneho úložiska. Týmto spôsobom garantujeme zachovanie zvoleného vzhľadu aj pri budúcich návštevách stránky.

```javascript
// Ukážka z frontend/src/components/Navbar.vue
function applyTheme(theme) {
  const resolved = theme === 'light' ? 'light' : 'dark';
  themePreference.value = resolved;
  document.documentElement.setAttribute('data-theme', resolved);
  localStorage.setItem('theme_preference', resolved);
}
```

### 4.2.8. Interakcia profilu a ochranné blokovanie pozadia
V záujme redukcie nadbytočných transakcií pri smerovaní sme zrušili samostatnú podstránku pre profil používateľa a celú túto sekciu sme vložili do modálneho dialógu priamo v navigačnom komponente. Kľúčovou technickou výzvou pri tomto riešení bolo zabrániť nežiaducemu prekrývaniu interakcií. Implementovali sme preto techniku Body Scroll Locking, ktorá po zobrazení profilového okna programaticky modifikuje štýl `overflow` hlavného dokumentu. Táto úprava zamedzuje posúvaniu obsahu na pozadí, čím používateľa udržuje v absolútnom vizuálnom kontexte.

```html
<!-- Ukážka z frontend/src/components/Navbar.vue -->
<Dialog 
  v-model:visible="showUserProfileDialog" 
  :modal="true" :blockScroll="true" :showHeader="false"
  class="w-11/12 md:w-[450px]"
>
  <div class="relative flex flex-col pt-10">
    <div class="relative mb-4 group cursor-pointer shadow-lg rounded-full">
      <img v-if="currentUser.avatar_path" :src="getAvatarUrl(currentUser.avatar_path)" />
      <div v-else :style="{ background: avatarColor }">
        <span class="avatar-letter-large">{{ currentUser.name?.charAt(0).toUpperCase() }}</span>
      </div>
    </div>
  </div>
</Dialog>
```

### 4.2.9. Grafická propagácia výnimiek
Distribuovaná architektúra nášho portálu prirodzene prináša riziko výskytu chýb v sieti alebo na strane servera. Pre robustné ošetrenie takýchto výnimiek sme v celej asynchrónnej vrstve aplikovali striktné zachytávanie pomocou konštruktov `try...catch`. Akékoľvek zlyhanie požiadavky aplikácia automaticky deleguje globálnej notifikačnej službe. Tá zabezpečí bezproblémové vykreslenie vizuálnych "toast" upozornení, ktoré používateľovi poskytnú presné detaily o povahe problému bez toho, aby agresívne prerušili jeho plynulý tok práce.

```javascript
// Ukážka z frontend/src/views/AddProjectView.vue
} catch (parseError) {
  toast.add({ 
    severity: 'error', 
    summary: t('add_project.response_error'), 
    detail: t('add_project.response_error_desc'), 
    life: 7000 
  });
  loadingSubmit.value = false;
  return;
}
```

#### 📌 Zoznam odkazov na zdrojový kód (Frontend)
Kompletný zoznam referencií na zdrojové súbory prezentované v predchádzajúcej podkapitole:
*   **Bod 4.2.2:** Súbory `frontend/src/main.js` a `frontend/src/router/index.js`
*   **Bod 4.2.3 a 4.2.4:** Súbor `frontend/src/views/AddProjectView.vue`
*   **Bod 4.2.5 až 4.2.8:** Súbor `frontend/src/components/Navbar.vue`
*   **Bod 4.2.9:** Súbor `frontend/src/views/AddProjectView.vue`

---

## 4.3. Databáza

Základom pre bezpečnú a konzistentnú správu dát v portáli je relačná databáza. Pre zabezpečenie dlhodobej udržateľnosti sme sa vyhli priamemu zápisu SQL dopytov a nasadili sme migračné skripty frameworku Laravel. Získali sme tak spoľahlivý mechanizmus na plynulé verziovanie databázovej schémy. Každá štrukturálna úprava tabuliek je reprezentovaná PHP kódom, čo umožňuje automatizovanú a presnú replikáciu databázovej infraštruktúry v akomkoľvek prostredí. Všetky zmeny v dátovom modeli tak zostávajú exaktne zdokumentované a priamo prepojené so zdrojovým kódom aplikácie.

### 4.3.1. Inštalácia a príprava prostredia
Ako databázový systém portálu sme zvolili technológiu MariaDB, ktorá vyniká vysokým výkonom a plnou kompatibilitou so štandardom MySQL. Z bezpečnostných dôvodov sme kompletnú sieťovú konfiguráciu a prihlasovacie údaje striktne oddelili od zdrojového kódu. Parametre spojenia, ako sú IP adresa lokálneho servera (`DB_HOST=127.0.0.1`), komunikačný port (`DB_PORT=3306`) a samotné prihlasovacie údaje, sme uložili výhradne do lokálneho konfiguračného súboru `.env`. Týmto krokom sme eliminovali riziko úniku citlivých dát vo verziovacom systéme (napríklad v repozitári na GitHube). Takto zabezpečené prostredie následne umožnilo vrstve ORM Eloquent nadviazať stabilné spojenie s databázovým uzlom. Namiesto manuálneho vytvárania tabuliek v SQL sme na inicializáciu dátovej štruktúry využili príkazový riadok frameworku spustením príkazu `php artisan migrate`. Celý proces prípravy prostredia sa vďaka tomu stal nielen maximálne bezpečným, ale aj plne reprodukovateľným pre iných vývojárov.

### 4.3.2. Základy implementácie a formovanie entít
Pri návrhu databázovej schémy sme postupovali od tých najzákladnejších tabuliek. Vývoj sme logicky začali tabuľkou používateľov, ktorá tvorí základ pre autentifikáciu v portáli. Okrem štandardného primárneho kľúča sme už na úrovni databázy zadefinovali, aby bol stĺpec s e-mailovou adresou striktne unikátny. Vďaka tomuto obmedzeniu máme istotu, že v systéme nikdy nevzniknú duplicitné účty, a to ani v prípade, ak by zlyhala validácia dát na klientskej strane. Štruktúru sme zároveň doplnili o pomocné stĺpce, ktoré evidujú čas vytvorenia záznamu a stav overenia e-mailu.

```php
// Ukážka z backend/database/migrations/..._create_users_table.php
public function up(): void
{
    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->timestamp('email_verified_at')->nullable();
        $table->string('password');
        $table->rememberToken();
        $table->timestamps();
    });
}
```

### 4.3.3. Algoritmická optimalizácia výkonu indexovaním (B-Trees)
Rastúci počet projektov by skôr či neskôr donútil databázu pri každom vyhľadávaní sekvenčne skenovať celú tabuľku rad za radom (Full Table Scan). Aby sme predišli spomaleniu portálu, najčastejšie dopytované stĺpce sme osadili indexmi na báze B-stromov (B-Trees) a pre zložitejšie filtre aj kompozitnými indexmi. Databáza vďaka tomu ide vždy "naisto". Zrazili sme tak časovú zložitosť hľadania z lineárnej O(n) na logaritmickú O(log n). Výsledok? Blesková odozva portálu aj pri tisíckach nahratých prác.

```php
// Ukážka z backend/database/migrations/2025_11_25_153720_add_performance_indexes_to_tables.php
public function up(): void
{
    Schema::table('projects', function (Blueprint $table) {
        $table->index('type', 'idx_projects_type');
        $table->index('team_id', 'idx_projects_team_id');
        $table->index('academic_year_id', 'idx_projects_academic_year_id');
    });

    Schema::table('team_user', function (Blueprint $table) {
        // Implementácia komplexného kompozitného indexu
        $table->index(['team_id', 'user_id', 'role_in_team'], 'idx_team_user_composite');
    });
}
```

### 4.3.4. Implementácia semi-štruktúrovaných polí (JSON) pre dynamické metadáta
Študentské projekty sa od seba výrazne líšia formátom. Interaktívna WebGL hra totiž vyžaduje uloženie úplne iných parametrov ako statický 3D model. Keby sme pre každý možný atribút vytvorili v tabuľke samostatný stĺpec, databáza by sa rýchlo stala neefektívnou a preplnenou prázdnymi `NULL` hodnotami. Tento limit sme prekonali nasadením natívnych `JSON` stĺpcov, čím sme do MariaDB integrovali flexibilitu NoSQL databáz. Zostali nám tak silné relačné väzby pre kľúčové entity (ako je vlastníctvo projektu), no zároveň sme získali plne variabilný priestor pre špecifické metadáta bez nutnosti narúšať dátovú štruktúru.

```php
// Ukážka z backend/database/migrations/2025_11_23_154309_create_projects_table.php
public function up()
{
    Schema::create('projects', function (Blueprint $table) {
        $table->id();
        $table->foreignId('team_id')->constrained()->onDelete('cascade');
        $table->string('title');
        
        // Implementácia flexibilných NoSQL štruktúr v relačnej mape
        $table->json('files')->nullable(); 
        $table->json('metadata')->nullable(); 
        
        $table->timestamps();
    });
}
```

#### 📌 Zoznam odkazov na zdrojový kód (Databáza)
Kompletný zoznam referencií na zdrojové súbory prezentované v predchádzajúcej podkapitole:
*   **Bod 4.3.2:** Migrácia `backend/database/migrations/..._create_users_table.php`
*   **Bod 4.3.3:** Migrácia `backend/database/migrations/2025_11_25_153720_add_performance_indexes_to_tables.php`
*   **Bod 4.3.4:** Migrácia `backend/database/migrations/2025_11_23_154309_create_projects_table.php`
