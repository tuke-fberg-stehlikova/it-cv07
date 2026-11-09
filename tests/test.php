<?php
// Autograder cv06: 10 kontrol × 10 bodov. Spúšťa sa v GitHub Actions (.github/workflows/classroom.yml) alebo lokálne: php tests/test.php
declare(strict_types=1);

$root = dirname(__DIR__);
$prefix = '/student/test.user/it/cv06';
$port = 8765;
$score = 0;
$results = [];
$cookies = [];     // cookie jar: to, čo server poslal v Set-Cookie, ide v ďalších požiadavkách späť v hlavičke Cookie (ako prehliadač)

function check(string $name, bool $ok, string $hint = ''): void
{
    global $score, $results;
    $score += $ok ? 10 : 0;
    $results[] = sprintf('%s %s%s', $ok ? '✔' : '✘', $name, $ok || $hint === '' ? '' : "  → $hint");
}

/** HTTP požiadavka na php -S; vráti [stavový kód, telo, hlavičky]. Presmerovanie nesleduje. Cookies si pamätá v $cookies. */
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

function hasForm(string $html): bool
{
    return (bool) preg_match('/<form\b[^>]*method=["\']post["\']/i', $html);
}

// --- statické kontroly súborov -------------------------------------------------
$schema = @file_get_contents("$root/sql/schema.sql") ?: '';
check('sql/schema.sql: CREATE TABLE users s username UNIQUE a password_hash',
    preg_match('/CREATE\s+TABLE\s+`?users`?/i', $schema) && preg_match('/username[^,]*UNIQUE|UNIQUE[^,]*\(\s*`?username`?\s*\)/i', $schema) && str_contains($schema, 'password_hash'),
    'tabuľka users (id, username UNIQUE, password_hash, created_at) v sql/schema.sql (úloha 7.2)');

$um = @file_get_contents("$root/src/Model/UserModel.php") ?: '';
check('src/Model/UserModel.php: findByUsername() a insert()',
    str_contains($um, 'function findByUsername(') && str_contains($um, 'function insert(') && str_contains($um, 'password_hash'),
    'UserModel s metódami findByUsername(string $username): ?array a insert(string $username, string $passwordHash): int (úloha 7.3)');

$ix = @file_get_contents("$root/index.php") ?: '';
$pair = '\[\s*\\\\?[A-Za-z0-9_\\\\]+::class\s*,\s*[\'"][a-zA-Z_][a-zA-Z0-9_]*[\'"]\s*\]';
$route = fn(string $m, string $p): bool => (bool) preg_match('/->' . $m . '\(\s*[\'"]' . preg_quote($p, '/') . '[\'"]\s*,\s*' . $pair . '/', $ix);
check('index.php: session_start() a päť route: GET+POST /register, GET+POST /login, POST /logout',
    str_contains($ix, 'session_start()') && $route('get', '/register') && $route('post', '/register') && $route('get', '/login') && $route('post', '/login') && $route('post', '/logout'),
    'session_start() pred Request::fromGlobals(); route podľa stránky predmetu (úlohy 7.1, 7.4)');

$ac = @file_get_contents("$root/src/Controller/AuthController.php") ?: '';
check('src/Controller/AuthController.php: password_hash(), password_verify(), Auth::login() a Auth::logout()',
    str_contains($ac, 'password_hash(') && str_contains($ac, 'password_verify(') && str_contains($ac, 'Auth::login(') && str_contains($ac, 'Auth::logout('),
    'AuthController podľa stránky predmetu (úloha 7.4)');

$au = @file_get_contents("$root/src/Auth.php") ?: '';
$hp = @file_get_contents("$root/src/helpers.php") ?: '';
check('src/Auth.php a src/helpers.php zo session.zip (Auth::check(), addGlobal user a flash)',
    str_contains($au, 'function check(') && str_contains($au, "\$_SESSION['user_id']") && preg_match('/addGlobal\(\s*[\'"]user[\'"]/', $hp) && preg_match('/addGlobal\(\s*[\'"]flash[\'"]/', $hp),
    'rozbaľ session.zip v priečinku projektu (úloha 7.1)');

// --- HTTP kontroly cez php -S ----------------------------------------------------
$cmd = sprintf('php -S 127.0.0.1:%d %s > /dev/null 2>&1 & echo $!', $port, escapeshellarg("$root/tests/router.php"));
$pid = (int) trim((string) shell_exec($cmd));
usleep(700000);

preg_match_all('/->get\(\s*[\'"]([^\'"]+\/new)[\'"]\s*,\s*' . $pair . '/', $ix, $nm);
$newPath = $nm[1][0] ?? '/recipes/new';

[$s, $b, $h] = get('/', false);
$hasCookie = false;
foreach ($h['set-cookie'] ?? [] as $c) {
    if (str_starts_with($c, 'PHPSESSID=')) {
        $hasCookie = true;
    }
}
check('GET / → 200, odpoveď má Set-Cookie: PHPSESSID a menu má odkaz na /login',
    $s === 200 && $hasCookie && str_contains($b, url('/login')),
    "stavový kód $s; session_start() v index.php; v layout.twig odkaz {{ url('/login') }} pre neprihláseného (úlohy 7.1, 7.5)");

[$s, $b, $h] = get($newPath);
$loc = $h['location'] ?? '';
check("GET $newPath bez prihlásenia → 302 na /login",
    $s === 302 && str_ends_with($loc, '/login'),
    "stavový kód $s, Location: " . ($loc ?: '(žiadna)') . '; create() má bez prihlásenia vrátiť Response::redirect(\'/login\') (úloha 7.6)');

$user = 'autograder' . random_int(10000, 99999);
$pass = 'Heslo' . random_int(1000, 9999);
[$s, $b] = get('/register');
$formOk = $s === 200 && hasForm($b) && preg_match('/name=["\']username["\']/', $b) && preg_match('/type=["\']password["\'][^>]*name=["\']password["\']|name=["\']password["\'][^>]*type=["\']password["\']/', $b);
[$s2, $b2, $h2] = req('POST', '/register', ['username' => $user, 'password' => $pass]);
$loc2 = $h2['location'] ?? '';
check('GET /register → 200 s formulárom (username, password); POST /register nový používateľ → 302 na /login',
    $formOk && $s2 === 302 && str_ends_with($loc2, '/login'),
    "GET /register → $s; POST /register → $s2, Location: " . ($loc2 ?: '(žiadna)') . '; register() má po insert() vrátiť Response::redirect(\'/login\') (úloha 7.4)');

[$s, $b] = req('POST', '/register', ['username' => $user, 'password' => $pass]);
[$s2, $b2] = req('POST', '/login', ['username' => $user, 'password' => 'zle-' . $pass]);
check('POST /register s obsadeným menom → 200 a formulár s hláškou; POST /login so zlým heslom → 200 a formulár s hláškou',
    $s === 200 && hasForm($b) && $s2 === 200 && hasForm($b2),
    "obsadené meno → $s; zlé heslo → $s2; obe majú vrátiť formulár znova (200), nie 302 ani 500 (úloha 7.4)");

[$s, $b, $h] = req('POST', '/login', ['username' => $user, 'password' => $pass]);
$loc = $h['location'] ?? '';
$ok = $s === 302;
$why = "POST /login so správnym heslom → stavový kód $s; login() má po Auth::login() vrátiť Response::redirect('/')";
if ($ok) {
    [$s2, $b2] = get('/');
    [$s3, $b3] = get($newPath);
    $ok = $s2 === 200 && str_contains($b2, $user) && str_contains($b2, url('/logout')) && $s3 === 200 && hasForm($b3);
    $why = "po prihlásení GET / → $s2 (má obsahovať meno $user a formulár na /logout), GET $newPath → $s3 (má byť 200 s formulárom) (úlohy 7.5, 7.6)";
}
check('POST /login správne → 302; potom GET / ukazuje meno a tlačidlo Odhlásiť; GET /entita/new → 200 s formulárom', $ok, $ok ? '' : $why);

if ($pid) {
    exec("kill $pid 2>/dev/null");
}

echo implode("\n", $results), "\n\n", "Body: $score / 100\n";
exit($score === 100 ? 0 : 1);

function url(string $path): string
{
    global $prefix;
    return $prefix . $path;
}
