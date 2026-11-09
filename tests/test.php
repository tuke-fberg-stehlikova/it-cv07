<?php
// Autograder cv07: 10 kontrol × 10 bodov. Spúšťa sa v GitHub Actions (.github/workflows/classroom.yml) alebo lokálne: php tests/test.php
declare(strict_types=1);

$root = dirname(__DIR__);
$prefix = '/student/test.user/it/cv07';
$port = 8765;
$score = 0;
$results = [];
$cookies = [];

function check(string $name, bool $ok, string $hint = ''): void
{
    global $score, $results;
    $score += $ok ? 10 : 0;
    $results[] = sprintf('%s %s%s', $ok ? '✔' : '✘', $name, $ok || $hint === '' ? '' : "  → $hint");
}

/** HTTP požiadavka na php -S; vráti [stavový kód, telo, hlavičky]. Cookies si pamätá v $cookies (cookie jar ako prehliadač). */
function req(string $method, string $path, array $data = [], bool $withCookies = true): array
{
    global $prefix, $port, $cookies;
    $header = '';
    if ($withCookies && $cookies !== []) {
        $pairs = [];
        foreach ($cookies as $k => $v) {
            $pairs[] = "$k=$v";
        }
        $header .= 'Cookie: ' . implode('; ', $pairs) . "\r\n";
    }
    $opts = ['method' => $method, 'ignore_errors' => true, 'timeout' => 10, 'follow_location' => 0];
    if ($method === 'POST') {
        $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $opts['content'] = http_build_query($data);
    }
    $opts['header'] = $header;
    $ctx = stream_context_create(['http' => $opts]);
    $body = @file_get_contents("http://127.0.0.1:$port$prefix$path", false, $ctx);
    $status = 0;
    $headers = [];
    foreach ($http_response_header ?? [] as $h) {
        if (preg_match('#^HTTP/\S+ (\d{3})#', $h, $m)) {
            $status = (int) $m[1];
        } elseif (str_contains($h, ':')) {
            [$k, $v] = explode(':', $h, 2);
            $k = strtolower(trim($k));
            $v = trim($v);
            if ($k === 'set-cookie') {
                $headers['set-cookie'][] = $v;
                if (preg_match('/^([^=]+)=([^;]*)/', $v, $c)) {
                    $cookies[$c[1]] = $c[2];
                }
            } else {
                $headers[$k] = $v;
            }
        }
    }
    return [$status, (string) $body, $headers];
}

function get(string $path, bool $withCookies = true): array
{
    return req('GET', $path, [], $withCookies);
}

/** Vytiahne CSRF token zo skrytého inputu na stránke $path (zavolá GET, aby token vznikol a cookie sa uložila). */
function csrf(string $path): string
{
    [, $html] = get($path);
    return preg_match('/name=["\']csrf["\']\s+value=["\']([^"\']+)["\']/', $html, $m) ? $m[1] : '';
}

function hasForm(string $html): bool
{
    return (bool) preg_match('/<form\b[^>]*method=["\']post["\']/i', $html);
}

// --- statické kontroly súborov -------------------------------------------------
$schema = @file_get_contents("$root/sql/schema.sql") ?: '';
check('sql/schema.sql: users.role a user_id v hlavnej entite s FOREIGN KEY na users',
    preg_match('/\brole\b/', $schema) && preg_match('/\buser_id\b/', $schema)
        && preg_match('/FOREIGN\s+KEY\s*\(\s*`?user_id`?\s*\)\s*REFERENCES\s+`?users`?/i', $schema),
    'stĺpec users.role, stĺpec user_id v tabuľke hlavnej entity a FOREIGN KEY (user_id) REFERENCES users(id) (úlohy 8.1, 8.2)');

$base = @file_get_contents("$root/src/Controller/BaseController.php") ?: '';
check('src/Controller/BaseController.php: requireLogin() a authorize()',
    preg_match('/function\s+requireLogin\s*\(/', $base) && preg_match('/function\s+authorize\s*\(/', $base),
    'metódy requireLogin(): ?Response a authorize(array $record): ?Response v BaseController (úloha 8.3)');

// Controller hlavnej entity = súbor, ktorý má metódu edit()
$mainCtrl = '';
foreach (glob("$root/src/Controller/*.php") as $f) {
    $c = (string) file_get_contents($f);
    if (preg_match('/function\s+edit\s*\(/', $c)) {
        $mainCtrl = $c;
        break;
    }
}
check('Controller hlavnej entity: edit(), update() a delete()',
    $mainCtrl !== '' && preg_match('/function\s+update\s*\(/', $mainCtrl) && preg_match('/function\s+delete\s*\(/', $mainCtrl),
    'metódy edit(), update() a delete() v controlleri hlavnej entity (úloha 8.4)');

$ix = @file_get_contents("$root/index.php") ?: '';
$posParams = strpos($ix, 'session_set_cookie_params');
$posReq    = strpos($ix, 'Request::fromGlobals');
check('index.php: session_set_cookie_params s path za Request::fromGlobals(), route edit/update/delete',
    $posParams !== false && preg_match('/[\'"]path[\'"]\s*=>/', $ix)
        && $posReq !== false && $posParams > $posReq
        && preg_match('#\{id\}/edit#', $ix) && preg_match('#\{id\}/delete#', $ix),
    'session_set_cookie_params([...path...]) a session_start() AŽ ZA Request::fromGlobals(); route .../{id}/edit a .../{id}/delete (úlohy 8.1, 8.4, cookie session)');

$csrfFile = @file_get_contents("$root/src/Csrf.php") ?: '';
check('src/Csrf.php: token() a check()',
    preg_match('/function\s+token\s*\(/', $csrfFile) && preg_match('/function\s+check\s*\(/', $csrfFile),
    'trieda Csrf so static metódami token() a check() (zo súborov bezpecnost.zip)');

$router  = @file_get_contents("$root/src/Router.php") ?: '';
$helpers = @file_get_contents("$root/src/helpers.php") ?: '';
check('src/Router.php kontroluje CSRF token pri POST; src/helpers.php má csrf_field()',
    str_contains($router, 'Csrf') && str_contains($helpers, 'csrf_field'),
    'Router pri POST volá Csrf::check(); helpers.php registruje Twig funkciu csrf_field() (zo súborov bezpecnost.zip)');

$auth = @file_get_contents("$root/src/Auth.php") ?: '';
$ac   = @file_get_contents("$root/src/Controller/AuthController.php") ?: '';
check('src/Auth.php: session_regenerate_id(); AuthController: pravidlo pre heslo (8 znakov)',
    str_contains($auth, 'session_regenerate_id') && preg_match('/mb_strlen|strlen/', $ac) && str_contains($ac, 'password2'),
    'session_regenerate_id(true) v Auth::login(); v register() kontrola dĺžky hesla a pole password2 (úlohy 8.1, 8.4)');

// --- HTTP kontroly cez php -S ----------------------------------------------------
$cmd = sprintf('php -S 127.0.0.1:%d %s > /dev/null 2>&1 & echo $!', $port, escapeshellarg("$root/tests/router.php"));
$pid = (int) trim((string) shell_exec($cmd));
usleep(800000);

// discover main entity base path from the edit route '/X/{id}/edit'
$entity = preg_match('#[\'"](/[a-z0-9_\-]+)/\{id\}/edit[\'"]#i', $ix, $m) ? $m[1] : '/recipes';
$newPath = preg_match('#->get\(\s*[\'"](/[a-z0-9_\-]+/new)[\'"]#i', $ix, $mm) ? $mm[1] : $entity . '/new';

[$s, $b, $h] = get('/', false);
$setCookie = implode(' ', $h['set-cookie'] ?? []);
check('GET / → 200, Set-Cookie PHPSESSID s path (nie iba /) a SameSite',
    $s === 200 && preg_match('/PHPSESSID=/', $setCookie)
        && preg_match('#path=/[^;\s]+#', $setCookie) && !preg_match('#path=/;#', $setCookie . ';')
        && stripos($setCookie, 'samesite') !== false,
    "stavový kód $s; session_set_cookie_params nastavuje path na prefix a samesite; cookie: " . ($setCookie ?: '(žiadna)'));

[$s] = req('POST', '/login', ['username' => 'x', 'password' => 'y']);   // bez CSRF tokenu
check('POST /login bez CSRF tokenu → 403',
    $s === 403,
    "stavový kód $s; Router má pri každom POST bez platného tokenu vrátiť 403 (bezpecnost.zip: Router + Csrf)");

// plný tok: register (slabé heslo → formulár), register OK, login, GET entita/new → 200
$user = 'ag' . random_int(10000, 99999);
$t1 = csrf('/register');
[$sWeak, $bWeak] = req('POST', '/register', ['username' => $user, 'password' => 'abc', 'password2' => 'abc', 'csrf' => $t1]);
$t2 = csrf('/register');
[$sReg, , $hReg] = req('POST', '/register', ['username' => $user, 'password' => 'Heslo1234', 'password2' => 'Heslo1234', 'csrf' => $t2]);
$okReg = $sWeak === 200 && hasForm($bWeak) && $sReg === 302;
$why = "slabé heslo → $sWeak (má byť 200 s formulárom), platná registrácia → $sReg (má byť 302)";
if ($okReg) {
    $t3 = csrf('/login');
    [$sLog, , $hLog] = req('POST', '/login', ['username' => $user, 'password' => 'Heslo1234', 'csrf' => $t3]);
    [$sNew, $bNew] = get($newPath);
    $okReg = $sLog === 302 && $sNew === 200 && hasForm($bNew);
    $why = "login → $sLog (302), GET $newPath po prihlásení → $sNew (200 s formulárom)";
}
check('Registrácia (slabé heslo → formulár; platné → 302), prihlásenie s CSRF tokenom, formulár len pre prihláseného', $okReg, $okReg ? '' : $why);

if ($pid) {
    exec("kill $pid 2>/dev/null");
}

echo implode("\n", $results), "\n\n", "Body: $score / 100\n";
exit($score === 100 ? 0 : 1);
