<?php

/**
 * =============================================================================
 * DEVELOPER-ONLY ACTIVATION FILE GENERATOR
 * PS General Drug Centre — Pharmacy Inventory System
 * =============================================================================
 *
 * THIS FILE MUST NEVER BE PLACED ON THE CUSTOMER'S MACHINE.
 * Uses the PRIVATE Ed25519 key to sign activation payloads.
 * The private key must only exist on the developer's secure workstation.
 *
 * Usage:
 *   php generate-activation.php --keygen
 *   php generate-activation.php --installation-id=PHARM-XXXXXXXX [--duration=12] [--customer=NAME]
 *   php generate-activation.php --renew=/path/to/activation.dat [--duration=12]
 */

declare(strict_types=1);

define('TOOL_VERSION',           '1.0.0');
define('DEFAULT_PRODUCT',        'invent');
define('DEFAULT_MONTHS',         12);
define('PRIVATE_KEY_FILE',       __DIR__ . '/keys/license_private.key');
define('PUBLIC_KEY_FILE',        __DIR__ . '/keys/license_public.pem');
define('DEFAULT_OUTPUT',         __DIR__ . '/activation.dat');

if (PHP_SAPI !== 'cli') { die("CLI only.\n"); }
if (!function_exists('sodium_crypto_sign_keypair')) { die("ERROR: libsodium not available.\n"); }

$opts = getopt('', ['keygen','installation-id:','duration:','customer:','product:','renew:','output:','help']);

if (isset($opts['help']) || !$opts) { printHelp(); exit(0); }
if (isset($opts['keygen'])) { actionKeygen(); exit(0); }
if (isset($opts['renew']))  { actionRenew($opts['renew'], (int)($opts['duration'] ?? DEFAULT_MONTHS), $opts['output'] ?? DEFAULT_OUTPUT); exit(0); }
if (isset($opts['installation-id'])) { actionGenerate($opts['installation-id'], $opts['product'] ?? DEFAULT_PRODUCT, (int)($opts['duration'] ?? DEFAULT_MONTHS), $opts['customer'] ?? null, $opts['output'] ?? DEFAULT_OUTPUT); exit(0); }

printHelp(); exit(1);

// ---------------------------------------------------------------------------

function actionKeygen(): void {
    $dir = dirname(PRIVATE_KEY_FILE);
    if (!is_dir($dir)) mkdir($dir, 0700, true);
    if (file_exists(PRIVATE_KEY_FILE) || file_exists(PUBLIC_KEY_FILE)) {
        echo "WARNING: Keys already exist. Regenerating invalidates all activations.\nType YES to continue: ";
        if (trim(fgets(STDIN)) !== 'YES') { echo "Aborted.\n"; return; }
    }
    $kp  = sodium_crypto_sign_keypair();
    $sk  = sodium_crypto_sign_secretkey($kp);
    $pk  = sodium_crypto_sign_publickey($kp);
    // Ed25519 SubjectPublicKeyInfo DER prefix (RFC 8410 OID 1.3.101.112)
    $der = hex2bin('302a300506032b6570032100') . $pk;
    $pem = "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END PUBLIC KEY-----\n";
    file_put_contents(PRIVATE_KEY_FILE, bin2hex($sk) . "\n"); chmod(PRIVATE_KEY_FILE, 0600);
    file_put_contents(PUBLIC_KEY_FILE, $pem);                 chmod(PUBLIC_KEY_FILE, 0644);
    echo "\nKeypair generated.\n";
    echo "  PRIVATE (never share): " . PRIVATE_KEY_FILE . "\n";
    echo "  PUBLIC  (deploy to customer app storage/app/keys/license_public.pem):\n  " . PUBLIC_KEY_FILE . "\n\n";
}

function actionGenerate(string $id, string $product, int $months, ?string $customer, string $out): void {
    $sk  = loadPrivateKey();
    $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
    $exp = $now->modify("+{$months} months");
    $aid = 'ACT-' . strtoupper(bin2hex(random_bytes(8)));
    $pl  = buildPayload($product, $id, $aid, $now->format('Y-m-d\TH:i:s\Z'), $exp->format('Y-m-d\TH:i:s\Z'), $customer);
    $sig = sodium_crypto_sign_detached(canonicalJson($pl), $sk);
    writeFile($out, ['payload' => $pl, 'signature' => base64_encode($sig)]);
    echo "\nActivation generated for {$id}\nExpires: " . $exp->format('d F Y') . "\nFile: " . realpath($out) . "\n\n";
    echo "Transfer activation.dat to customer, then run:\n  php artisan app:install-activation /path/to/activation.dat\n\n";
}

function actionRenew(string $existing, int $months, string $out): void {
    if (!file_exists($existing)) die("ERROR: File not found: {$existing}\n");
    $data = json_decode(file_get_contents($existing), true);
    if (!$data || !isset($data['payload']['installation_id'])) die("ERROR: Malformed activation file.\n");
    $pl0 = $data['payload'];
    $sk  = loadPrivateKey();
    $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
    $old = new DateTimeImmutable($pl0['expires_at']);
    $base = ($old > $now) ? $old : $now;
    $exp  = $base->modify("+{$months} months");
    echo "\nRenewal: old expiry=" . $old->format('d M Y') . "  new expiry=" . $exp->format('d M Y') . "\n";
    $aid = 'ACT-' . strtoupper(bin2hex(random_bytes(8)));
    $pl  = buildPayload($pl0['product'] ?? DEFAULT_PRODUCT, $pl0['installation_id'], $aid, $now->format('Y-m-d\TH:i:s\Z'), $exp->format('Y-m-d\TH:i:s\Z'), $pl0['customer'] ?? null);
    $sig = sodium_crypto_sign_detached(canonicalJson($pl), $sk);
    writeFile($out, ['payload' => $pl, 'signature' => base64_encode($sig)]);
    echo "File: " . realpath($out) . "\n\n";
    echo "Transfer activation.dat to customer, then run:\n  php artisan app:install-activation /path/to/activation.dat\n\n";
}

function loadPrivateKey(): string {
    if (!file_exists(PRIVATE_KEY_FILE)) die("ERROR: Private key not found. Run --keygen first.\n");
    $hex = trim(file_get_contents(PRIVATE_KEY_FILE));
    if (!ctype_xdigit($hex) || strlen($hex) !== 128) die("ERROR: Private key corrupted.\n");
    return hex2bin($hex);
}

function buildPayload(string $product, string $id, string $aid, string $act, string $exp, ?string $cust = null): array {
    $pl = ['activated_at' => $act, 'activation_id' => $aid, 'expires_at' => $exp, 'installation_id' => $id, 'product' => $product, 'version' => 1];
    if ($cust !== null) $pl['customer'] = $cust;
    ksort($pl);
    return $pl;
}

function canonicalJson(array $d): string { ksort($d); return json_encode($d, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); }

function writeFile(string $path, array $data): void {
    $dir = dirname($path);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
}

function printHelp(): void {
    echo "\nPS General Drug Centre — Developer Activation Tool v" . TOOL_VERSION . "\n\n";
    echo "  --keygen                     Generate Ed25519 keypair (first-time setup)\n";
    echo "  --installation-id=PHARM-XXXX Generate activation for installation\n";
    echo "  --duration=12                Duration in months (default 12)\n";
    echo "  --customer=\"Name\"             Optional customer reference\n";
    echo "  --product=invent Product identifier\n";
    echo "  --renew=/path/activation.dat Renew existing activation\n";
    echo "  --output=/path/out.dat       Output file path\n\n";
    echo "PRIVATE KEY: keys/license_private.key — NEVER share or deploy to customer\n";
    echo "PUBLIC KEY:  keys/license_public.pem  — deploy to storage/app/keys/ on customer machine\n\n";
}
