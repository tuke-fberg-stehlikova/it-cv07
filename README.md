# cv06 – Sessions a prihlásenie

*(English version below)*

Teória, príklad krok za krokom a súbory na stiahnutie sú na stránke predmetu: [Týždeň 7](https://beata.stehlikova.website.tuke.sk/it/07.html). Vzorová aplikácia beží na <https://sigma.tuke.sk/beata.stehlikova/it/ukazky/cv06/>.

Čo dnes urobíš: zapneš session (`session_start()`), pridáš tabuľku `users` a `UserModel`, napíšeš `AuthController` s registráciou (`password_hash()`), prihlásením (`password_verify()`) a odhlásením, v menu bude meno prihláseného a flash správy, formulár na nový záznam bude len pre prihlásených a aplikácia si v session zapamätá jednu vec navyše.

## Úlohy

**Úloha 7.1: Repository cv06 z cv05, session.zip a session_start().** Prijmi zadanie *cv06* ([link](https://classroom50.org/tuke-fberg-stehlikova/informacne-technologie-5k/assignments/cv06/accept?k=xzmai3oh)) a repository naklonuj do `~/web/it/cv06` podľa návodu [04 Odovzdávanie](https://beata.stehlikova.website.tuke.sk/navody/04-odovzdavanie.html), časti 2 a 3 (posledné slovo príkazu je `cv06`). Potom v termináli v Kubeflow:

```bash
cd ~/web/it
cp cv05/config.php cv05/config.example.php cv05/.gitignore cv05/.htaccess cv05/composer.json cv05/composer.lock cv05/index.php cv06/
cp -r cv05/sql cv05/src cv05/views cv06/
cd cv06
curl -O https://beata.stehlikova.website.tuke.sk/it/session.zip
unzip -o session.zip && rm session.zip
composer install
```

Čo sa stalo: z `cv05` sú v `cv06` všetky súbory okrem `.git/`, `vendor/` a `README.md`. `unzip -o` prepísal `src/helpers.php` a `views/debug.twig` a pridal `src/Auth.php`, `views/register.twig`, `views/login.twig`. `composer install` vyrobil `vendor/` presne podľa `composer.lock`.

V `index.php` dopíš riadok `session_start();` hneď za `require __DIR__ . '/vendor/autoload.php';` (riadky `use` ostávajú za ním; dôležité je, aby bol pred `Request::fromGlobals()` a pred akýmkoľvek výstupom). Nový `helpers.php` používa `UserModel`, ktorý ešte nemáš – vadiť to nebude, kým sa niekto neprihlási; napíšeš ho v úlohe 7.3. V `src/Controller/HomeController.php` dopíš do `debug()` počítadlo a kľúč `session` podľa stránky predmetu, časť „Kontrola a debug: session na stránke“.

Pravý klik na priečinok `cv06` → **Upload Folder**. Kontrola: `https://sigma.tuke.sk/student/meno.priezvisko/it/cv06/` ukáže úvodnú stránku; `…/cv06/debug` ukáže dole tabuľku *Session* – stlač `F5` trikrát, `$_SESSION['count']` rastie; v anonymnom okne (`Ctrl+Shift+N`, vo Firefoxe `Ctrl+Shift+P`) je `count` 1 a iné `session_id()`. V `F12` → Application → Cookies → `sigma.tuke.sk` je `PHPSESSID` s tou istou hodnotou ako `session_id()`. Commit a push: `cv06: session`.

**Úloha 7.2: Tabuľka users.** Do `sql/schema.sql` dopíš `CREATE TABLE users` podľa pojmu „Tabuľka users a UNIQUE“ na stránke predmetu – kamkoľvek medzi tabuľky, `users` nemá cudzí kľúč ani naň nikto neukazuje (a `DROP TABLE IF EXISTS users;` k ostatným `DROP`). V Adminer → **SQL command** vlož **len** príkaz `CREATE TABLE users …` a klikni na **Execute** (celý `schema.sql` by zmazal tvoje dáta). Kontrola: v Adminer je tabuľka `users` so stĺpcami `id`, `username`, `password_hash`, `created_at`, prázdna; v zozname tabuliek na `…/cv06/kontrola` pribudla `users`.

**Úloha 7.3: UserModel.** Vytvor `src/Model/UserModel.php` podľa pojmu „UserModel: findByUsername() a insert()“. V `HomeController::kontrola()` pridaj `new UserModel()` do poľa modelov a `use App\Model\UserModel;` k riadkom `use`. Kontrola: `…/cv06/kontrola` ukáže *Používatelia – 0 záznamov* a pod tým hlášku o `seed.sql` – tá je tu v poriadku, používatelia pribudnú registráciou.

**Úloha 7.4: AuthController a jeho route.** Vytvor `src/Controller/AuthController.php` podľa vzoru na stránke predmetu, časť „Ako to funguje“ (`registerForm()`, `register()`, `loginForm()`, `login()`, `logout()`). V `index.php` pridaj `use App\Controller\AuthController;` a route: `get('/register')`, `post('/register')`, `get('/login')`, `post('/login')`, `post('/logout')`. Kontrola (menu zatiaľ odkazy nemá, adresy napíš do riadku prehliadača): `…/cv06/register` ukáže formulár; po vyplnení mena a hesla a kliknutí na *Vytvoriť účet* sa otvorí `…/cv06/login`; v Adminer je v `users` riadok a v `password_hash` je 60 znakov začínajúcich `$2y$`; registrácia s tým istým menom vráti formulár s hláškou; prihlásenie so zlým heslom vráti formulár s hláškou; prihlásenie so správnym heslom otvorí úvod a `…/cv06/debug` ukáže v `$_SESSION['user_id']` tvoje id.

**Úloha 7.5: Meno a flash v layout.twig.** Do `views/layout.twig` dopíš na koniec `<nav>` blok `{% if user %} … {% else %} … {% endif %}` (formulár s tlačidlom *Odhlásiť*, odkazy *Prihlásiť* a *Registrovať*) a pod `<h1>` blok `{% if flash %}` podľa pojmov „addGlobal()“ a „Flash message“; do `<style>` pridaj `.flash` a `nav form` podľa toho istého bloku. Kontrola: bez prihlásenia sú v menu *Prihlásiť* a *Registrovať*; po prihlásení je v menu tvoje meno a tlačidlo, pod nadpisom zelená správa „Vitaj, …“; `F5` správu zmaže; klik na *Odhlásiť* vráti úvod so správou „Si odhlásený.“ a menu bez mena; `…/cv06/logout` napísané do adresy vráti stránku „Nenájdené“.

**Úloha 7.6: Formulár len pre prihlásených.** Do `create()` a `store()` v controlleri hlavnej entity dopíš na začiatok kontrolu `Auth::check()` podľa časti „Peter otvorí formulár bez prihlásenia“ (a `use App\Auth;`). V `views/list.twig` zmeň `{% if create is defined %}` na `{% if create is defined and user %}`. Kontrola v anonymnom okne: v zozname hlavnej entity odkaz *Pridať záznam* nie je; `…/cv06/tvoja-entita/new` napísané do adresy otvorí prihlásenie so správou „… najprv prihlás.“; po prihlásení v tom istom okne je odkaz v zozname a formulár uloží záznam ako doteraz.

**Úloha 7.7: Niečo v session bez prihlásenia.** Vymysli jednu vec, ktorú si tvoja aplikácia zapamätá o návštevníkovi v session aj bez prihlásenia, a urob ju. Námety: naposledy hľadaný text (`$_SESSION['last_q']` v `index()` hlavnej entity, vypísaný v `list.twig` ako „Naposledy si hľadal: …“); posledné tri otvorené záznamy (`show()` pridá id do poľa v session, `layout.twig` alebo vlastná stránka ich vypíše ako odkazy); zvolená hodnota číselníka ako filter zoznamu, ktorý ostane zvolený aj po návrate zo stránky detailu; počet návštev s časom prvej návštevy. Podmienky: zápis do `$_SESSION` v controlleri, hodnota do template cez pole pre `render()` (alebo cez `addGlobal`), výpis s `{% if %}` pre prípad, že v session ešte nič nie je. Čo si aplikácia pamätá a kde, napíš do tohto `README.md`. Kontrola: v jednom okne hodnota ostáva medzi kliknutiami, v anonymnom okne začína od nuly.

**Úloha 7.8: Odovzdanie.** Commit a push podľa návodu 04 Odovzdávanie, časť 5. Na GitHub skontroluj, že je tam `sql/schema.sql` s tabuľkou `users`, `src/Auth.php`, `src/Model/UserModel.php`, `src/Controller/AuthController.php`, `views/register.twig` a `views/login.twig`, `index.php` so `session_start()` a route pre účty. `config.php` a `vendor/` tam nie sú.

**Úloha 7.9: Kontrola na sigma.tuke.sk.** Pravý klik na `cv06` → **Upload Folder**. V bežnom okne: registrácia, prihlásenie, pridanie záznamu, odhlásenie. V anonymnom okne: menu bez mena, formulár hlavnej entity presmeruje na prihlásenie, tvoja vec zo 7.7 začína od nuly. Over, že `…/cv06/nic` ukáže stránku „Nenájdené“, `…/cv06/vendor/autoload.php` vráti 404 a `…/cv06/config.php` vráti 403. Do tohto `README.md` doplň tabuľku všetkých route (aj riadky pre registráciu, prihlásenie a odhlásenie) a pod ňu zoznam, čo všetko aplikácia ukladá do session:

| HTTP metóda a relatívna adresa | Controller a metóda | Čo zobrazí alebo urobí |
|---|---|---|
| GET `/` | `HomeController::index` | úvodná stránka |
| GET `/register` | `AuthController::registerForm` | formulár registrácie |
| … | … | … |

| Kľúč v `$_SESSION` | Kto zapisuje | Kto číta |
|---|---|---|
| `user_id` | `Auth::login()` | `Auth::check()`, `Auth::user()` |
| … | … | … |

**Hodnotenie:** 1 bod. Aplikácia vo webovom priestore na sigma.tuke.sk má registráciu, prihlásenie a odhlásenie, heslá v tabuľke `users` sú ako hash, v menu je meno prihláseného, formulár hlavnej entity je len pre prihlásených a aplikácia si v session pamätá jednu vec navyše. Commit a push najneskôr do nasledujúceho cvičenia.

---

# cv06 – Sessions and login

Theory, a step-by-step example and the files to download are on the course page: [Week 7](https://beata.stehlikova.website.tuke.sk/it/07.html). The sample application runs at <https://sigma.tuke.sk/beata.stehlikova/it/ukazky/cv06/>.

What you do today: you switch the session on (`session_start()`), add the table `users` and `UserModel`, write `AuthController` with registration (`password_hash()`), login (`password_verify()`) and logout, the menu shows the logged-in user's name and flash messages, the form for a new record is for logged-in users only and the application remembers one more thing in the session.

## Tasks

**Task 7.1: Repository cv06 from cv05, session.zip and session_start().** Accept the assignment *cv06* ([link](https://classroom50.org/tuke-fberg-stehlikova/informacne-technologie-5k/assignments/cv06/accept?k=xzmai3oh)) and clone the repository into `~/web/it/cv06` following the guide [04 Submitting](https://beata.stehlikova.website.tuke.sk/navody/04-odovzdavanie.html), parts 2 and 3 (the last word of the command is `cv06`). Then in the terminal in Kubeflow:

```bash
cd ~/web/it
cp cv05/config.php cv05/config.example.php cv05/.gitignore cv05/.htaccess cv05/composer.json cv05/composer.lock cv05/index.php cv06/
cp -r cv05/sql cv05/src cv05/views cv06/
cd cv06
curl -O https://beata.stehlikova.website.tuke.sk/it/session.zip
unzip -o session.zip && rm session.zip
composer install
```

What happened: all files from `cv05` are in `cv06` except `.git/`, `vendor/` and `README.md`. `unzip -o` replaced `src/helpers.php` and `views/debug.twig` and added `src/Auth.php`, `views/register.twig`, `views/login.twig`. `composer install` made `vendor/` exactly according to `composer.lock`.

In `index.php` add the line `session_start();` right after `require __DIR__ . '/vendor/autoload.php';` (the `use` lines stay after it; what matters is that it comes before `Request::fromGlobals()` and before any output). The new `helpers.php` uses `UserModel`, which you do not have yet – that does no harm until somebody logs in; you write it in task 7.3. In `src/Controller/HomeController.php` add the counter and the key `session` to `debug()` following the course page, part "Check and debug: the session on the page".

Right-click the folder `cv06` → **Upload Folder**. Check: `https://sigma.tuke.sk/student/meno.priezvisko/it/cv06/` shows the home page; `…/cv06/debug` shows the *Session* table at the bottom – press `F5` three times, `$_SESSION['count']` grows; in an incognito window (`Ctrl+Shift+N`, in Firefox `Ctrl+Shift+P`) `count` is 1 and `session_id()` differs. In `F12` → Application → Cookies → `sigma.tuke.sk` there is `PHPSESSID` with the same value as `session_id()`. Commit and push: `cv06: session`.

**Task 7.2: The table users.** Add `CREATE TABLE users` to `sql/schema.sql` following the term "The table users and UNIQUE" on the course page – anywhere among the tables, `users` has no foreign key and nothing points to it (and `DROP TABLE IF EXISTS users;` next to the other `DROP`s). In Adminer → **SQL command** paste **only** the command `CREATE TABLE users …` and click **Execute** (the whole `schema.sql` would delete your data). Check: Adminer shows the table `users` with the columns `id`, `username`, `password_hash`, `created_at`, empty; `users` appeared in the list of tables on `…/cv06/kontrola`.

**Task 7.3: UserModel.** Create `src/Model/UserModel.php` following the term "UserModel: findByUsername() and insert()". In `HomeController::kontrola()` add `new UserModel()` to the array of models and `use App\Model\UserModel;` to the `use` lines. Check: `…/cv06/kontrola` shows *Používatelia – 0 záznamov* and under it the message about `seed.sql` – that is fine here, users are added by registration.

**Task 7.4: AuthController and its routes.** Create `src/Controller/AuthController.php` following the sample on the course page, part "How it works" (`registerForm()`, `register()`, `loginForm()`, `login()`, `logout()`). In `index.php` add `use App\Controller\AuthController;` and the routes: `get('/register')`, `post('/register')`, `get('/login')`, `post('/login')`, `post('/logout')`. Check (the menu has no links yet, type the addresses into the browser bar): `…/cv06/register` shows the form; after filling in a name and a password and clicking *Vytvoriť účet*, `…/cv06/login` opens; in Adminer the table `users` has a row and `password_hash` holds 60 characters starting with `$2y$`; registration with the same name returns the form with a message; login with a wrong password returns the form with a message; login with the correct password opens the home page and `…/cv06/debug` shows your id in `$_SESSION['user_id']`.

**Task 7.5: Name and flash in layout.twig.** Add to the end of `<nav>` in `views/layout.twig` the block `{% if user %} … {% else %} … {% endif %}` (the form with the button *Odhlásiť*, the links *Prihlásiť* and *Registrovať*) and under `<h1>` the block `{% if flash %}` following the terms "addGlobal()" and "Flash message"; add `.flash` and `nav form` to `<style>` following the same block. Check: without login the menu has *Prihlásiť* and *Registrovať*; after login the menu has your name and the button, under the heading a green message "Vitaj, …"; `F5` removes the message; a click on *Odhlásiť* returns the home page with the message "Si odhlásený." and the menu without the name; `…/cv06/logout` typed into the address returns the "Nenájdené" page.

**Task 7.6: The form for logged-in users only.** At the beginning of `create()` and `store()` in the main entity controller add the `Auth::check()` check following the part "Peter opens the form without login" (and `use App\Auth;`). In `views/list.twig` change `{% if create is defined %}` to `{% if create is defined and user %}`. Check in an incognito window: the main entity list has no link *Pridať záznam*; `…/cv06/your-entity/new` typed into the address opens login with the message "… najprv prihlás."; after logging in in the same window the link is in the list and the form saves a record as before.

**Task 7.7: Something in the session without login.** Think up one thing your application remembers about a visitor in the session even without login, and make it. Ideas: the last searched text (`$_SESSION['last_q']` in the main entity's `index()`, printed in `list.twig` as "Naposledy si hľadal: …"); the last three opened records (`show()` adds the id to an array in the session, `layout.twig` or a page of your own prints them as links); a chosen lookup table value as a list filter that stays chosen after returning from the detail page; the number of visits with the time of the first visit. Conditions: a write into `$_SESSION` in a controller, the value into the template through the array for `render()` (or through `addGlobal`), output with `{% if %}` for the case that there is nothing in the session yet. Write what the application remembers and where into this `README.md`. Check: in one window the value stays between clicks, in an incognito window it starts from zero.

**Task 7.8: Submission.** Commit and push following the guide 04 Submitting, part 5. On GitHub check that there is `sql/schema.sql` with the table `users`, `src/Auth.php`, `src/Model/UserModel.php`, `src/Controller/AuthController.php`, `views/register.twig` and `views/login.twig`, `index.php` with `session_start()` and the routes for accounts. `config.php` and `vendor/` are not there.

**Task 7.9: Check on sigma.tuke.sk.** Right-click `cv06` → **Upload Folder**. In a normal window: registration, login, adding a record, logout. In an incognito window: the menu without a name, the main entity form redirects to login, your thing from 7.7 starts from zero. Verify that `…/cv06/nic` shows the "Nenájdené" page, `…/cv06/vendor/autoload.php` returns 404 and `…/cv06/config.php` returns 403. Add a table of all routes (including the rows for registration, login and logout) to this `README.md` and under it a list of everything the application stores in the session:

| HTTP method and relative address | Controller and method | What it shows or does |
|---|---|---|
| GET `/` | `HomeController::index` | home page |
| GET `/register` | `AuthController::registerForm` | registration form |
| … | … | … |

| Key in `$_SESSION` | Who writes | Who reads |
|---|---|---|
| `user_id` | `Auth::login()` | `Auth::check()`, `Auth::user()` |
| … | … | … |

**Grading:** 1 point. The application in your web space on sigma.tuke.sk has registration, login and logout, the passwords in the table `users` are hashes, the menu shows the logged-in user's name, the main entity form is for logged-in users only and the application remembers one more thing in the session. Commit and push by the next lab at the latest.
