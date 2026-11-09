# cv07 – Autorizácia a bezpečnosť

*(English version below)*

Teória, príklad krok za krokom a súbory na stiahnutie sú na stránke predmetu: [Týždeň 8](https://beata.stehlikova.website.tuke.sk/it/08.html). Vzorová aplikácia beží na <https://sigma.tuke.sk/beata.stehlikova/it/ukazky/cv07/>.

Čo dnes urobíš: pridáš autora záznamu (`user_id`) a rolu usera (`role`), úpravu a mazanie záznamu len pre autora alebo admina (inak 403), CSRF token do každého formulára, nové id session pri prihlásení, validáciu formulára a pravidlo pre heslo, a nastavíš vlastnosti cookie session.

## Terminológia

main entity = hlavná tabuľka aplikácie; lookup table = číselník; pivot table = tabuľka väzby M:N; record = jeden riadok; user = účet v `users`; admin = user s `role = 'admin'`; request/response = HTTP požiadavka/odpoveď; route = adresa priradená metóde controllera; token = náhodný text na overenie; origin = schéma + server + port webu.

## Úlohy

**Úloha 8.1: Repository cv07 z cv06, bezpecnost.zip, cookie session.** Prijmi zadanie *cv07* ([link](https://classroom50.org/tuke-fberg-stehlikova/informacne-technologie-5k/assignments/cv07/accept?k=xzmai3oh)) a repository naklonuj do `~/web/it/cv07` podľa návodu [04 Odovzdávanie](https://beata.stehlikova.website.tuke.sk/navody/04-odovzdavanie.html). Potom v termináli v Kubeflow:

```bash
cd ~/web/it
cp cv06/config.php cv06/config.example.php cv06/.gitignore cv06/.htaccess cv06/composer.json cv06/composer.lock cv06/index.php cv07/
cp -r cv06/sql cv06/src cv06/views cv07/
cd cv07
curl -O https://beata.stehlikova.website.tuke.sk/it/bezpecnost.zip
unzip -o bezpecnost.zip && rm bezpecnost.zip
composer install
```

`unzip -o` prepísal `src/Router.php`, `src/helpers.php` a pridal `src/Csrf.php`, `views/403.twig`. V `index.php` presuň `session_start()` **za** `Request::fromGlobals()` a pred neho pridaj `session_set_cookie_params([...])` podľa stránky predmetu, pojem „Vlastnosti cookie session“. Upload Folder. Kontrola: `…/cv07/` funguje; v `F12` → Application → Cookies má `PHPSESSID` `path` s tvojím prefixom (nie `/`); zmaž starú cookie s `path=/`. Commit a push: `cv07: bezpečnosť`.

**Úloha 8.2: Autor a rola.** V Adminer spusti štyri príkazy z pojmu „Autor record a rola usera“. Svojmu účtu nastav `role = 'admin'`. V `insert()` modelu hlavnej entity pridaj stĺpec `user_id` a v `store()` `$data['user_id'] = Auth::id();`. Kontrola: nový záznam má v `user_id` tvoje id.

**Úloha 8.3: requireLogin() a authorize().** Do `BaseController` pridaj `requireLogin()` a `authorize()`. V `create()` a `store()` nahraď kód z minulého týždňa volaním `requireLogin()`. Kontrola: bez prihlásenia ťa formulár presmeruje na prihlásenie.

**Úloha 8.4: edit, update, delete.** Do controllera hlavnej entity pridaj `edit()`, `update()`, `delete()` (každá: `requireLogin()`, načítanie záznamu alebo 404, `authorize()`). Do modelu pridaj `update()`. V `index.php` pridaj route `GET /<entita>/{id}/edit`, `POST /<entita>/{id}`, `POST /<entita>/{id}/delete`. V `form.twig` zmeň `action` na premennú a pridaj `{{ csrf_field() }}`. V `show.twig` pridaj tlačidlá *Upraviť* a *Zmazať* pre autora a admina. Kontrola: vlastný záznam upravíš aj zmažeš; cudzí (druhý účet) vráti 403.

**Úloha 8.5: CSRF token do formulárov.** Pridaj `{{ csrf_field() }}` do každého formulára: `form.twig`, `login.twig`, `register.twig` a do *Odhlásiť* v `layout.twig`. Kontrola: keď v `F12` → Elements zmažeš skrytý `input` s tokenom a formulár odošleš, dostaneš 403.

**Úloha 8.6: Validácia a heslo.** Do controllera pridaj `validate()` s poľom `$errors`; v `store()`/`update()` pri chybe vráť formulár s hláškami. V `form.twig` vypíš hlášku pod každým poľom. V `register()` pridaj kontrolu dĺžky hesla (aspoň 8 znakov, `mb_strlen`) a pole `password2`. Kontrola: záporné číslo a krátke heslo vrátia hlášku.

**Úloha 8.7: session_regenerate_id.** Do `Auth::login()` a `Auth::logout()` pridaj `session_regenerate_id(true)`. Kontrola: `/debug` ukazuje iné `session_id()` po prihlásení.

**Úloha 8.8: Vlastné pravidlo oprávnení.** Urči a naprogramuj jedno vlastné pravidlo (napr. delete len admin; update len do 24 hodín; stránka *Moje záznamy*). Popíš a zdôvodni v tomto `README.md`.

**Úloha 8.9: Odovzdanie a kontrola na sigma.** Commit a push. Na GitHub skontroluj `src/Csrf.php`, `BaseController` s `requireLogin()`/`authorize()`, controller hlavnej entity s `edit()`/`update()`/`delete()`, `index.php` so `session_set_cookie_params`. `config.php` a `vendor/` tam nie sú. Upload Folder; v dvoch účtoch over autora vs. cudzieho (403). Do tohto `README.md` doplň tabuľku route a popis svojho pravidla z 8.8.

**Hodnotenie:** 1 bod. Commit a push najneskôr do nasledujúceho cvičenia.

---

# cv07 – Authorization and security

Theory, a step-by-step example and the files to download are on the course page: [Week 8](https://beata.stehlikova.website.tuke.sk/it/08.html). The sample application runs at <https://sigma.tuke.sk/beata.stehlikova/it/ukazky/cv07/>.

What you do today: add the record's author (`user_id`) and the user's role (`role`), editing and deleting a record only for the author or admin (otherwise 403), a CSRF token in every form, a new session id at login, form validation and a password rule, and set the session cookie properties.

## Terminology

main entity = the application's main table; lookup table = a code list; pivot table = an M:N link table; record = one row; user = an account in `users`; admin = a user with `role = 'admin'`; request/response = the HTTP request/response; route = an address assigned to a controller method; token = a random text for verification; origin = scheme + server + port of a web site.

## Tasks

**Task 8.1: Repository cv07 from cv06, bezpecnost.zip, session cookie.** Accept the assignment *cv07* ([link](https://classroom50.org/tuke-fberg-stehlikova/informacne-technologie-5k/assignments/cv07/accept?k=xzmai3oh)) and clone the repository into `~/web/it/cv07` following the guide [04 Submitting](https://beata.stehlikova.website.tuke.sk/navody/04-odovzdavanie.html). Then in the terminal in Kubeflow:

```bash
cd ~/web/it
cp cv06/config.php cv06/config.example.php cv06/.gitignore cv06/.htaccess cv06/composer.json cv06/composer.lock cv06/index.php cv07/
cp -r cv06/sql cv06/src cv06/views cv07/
cd cv07
curl -O https://beata.stehlikova.website.tuke.sk/it/bezpecnost.zip
unzip -o bezpecnost.zip && rm bezpecnost.zip
composer install
```

`unzip -o` replaced `src/Router.php`, `src/helpers.php` and added `src/Csrf.php`, `views/403.twig`. In `index.php` move `session_start()` **after** `Request::fromGlobals()` and add `session_set_cookie_params([...])` before it following the course page, term "Session cookie properties". Upload Folder. Check: `…/cv07/` works; in `F12` → Application → Cookies `PHPSESSID` has a `path` with your prefix (not `/`); delete the old cookie with `path=/`. Commit and push: `cv07: bezpečnosť`.

**Task 8.2: Author and role.** In Adminer run the four commands from the term "Author of the record and the user's role". Set `role = 'admin'` for your account. In the main entity model's `insert()` add the `user_id` column, and in `store()` add `$data['user_id'] = Auth::id();`. Check: a new record has your id in `user_id`.

**Task 8.3: requireLogin() and authorize().** Add `requireLogin()` and `authorize()` to `BaseController`. In `create()` and `store()` replace last week's code with a call to `requireLogin()`. Check: without login the form redirects you to login.

**Task 8.4: edit, update, delete.** Add `edit()`, `update()`, `delete()` to the main entity controller (each: `requireLogin()`, loading the record or 404, `authorize()`). Add `update()` to the model. In `index.php` add the routes `GET /<entity>/{id}/edit`, `POST /<entity>/{id}`, `POST /<entity>/{id}/delete`. In `form.twig` change `action` to the variable and add `{{ csrf_field() }}`. In `show.twig` add the *Upraviť* and *Zmazať* buttons for the author and admin. Check: you edit and delete your own record; someone else's (a second account) returns 403.

**Task 8.5: CSRF token into the forms.** Add `{{ csrf_field() }}` into every form: `form.twig`, `login.twig`, `register.twig` and into *Odhlásiť* in `layout.twig`. Check: when in `F12` → Elements you delete the hidden `input` with the token and submit, you get 403.

**Task 8.6: Validation and password.** Add `validate()` with the array `$errors` to the controller; in `store()`/`update()` return the form with messages on an error. In `form.twig` print the message under each field. In `register()` add a password length check (at least 8 characters, `mb_strlen`) and the `password2` field. Check: a negative number and a short password return a message.

**Task 8.7: session_regenerate_id.** Add `session_regenerate_id(true)` to `Auth::login()` and `Auth::logout()`. Check: `/debug` shows a different `session_id()` after login.

**Task 8.8: Your own permission rule.** Determine and program one permission rule of your own (e.g. delete only admin; update only within 24 hours; a *My records* page). Describe and justify it in this `README.md`.

**Task 8.9: Submission and check on sigma.** Commit and push. On GitHub check `src/Csrf.php`, `BaseController` with `requireLogin()`/`authorize()`, the main entity controller with `edit()`/`update()`/`delete()`, `index.php` with `session_set_cookie_params`. `config.php` and `vendor/` are not there. Upload Folder; in two accounts verify the author vs. someone else (403). Add a route table and the description of your rule from 8.8 to this `README.md`.

**Grading:** 1 point. Commit and push by the next lab at the latest.
