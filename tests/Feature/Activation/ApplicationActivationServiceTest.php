<?php

namespace Tests\Feature\Activation;

use App\Models\ApplicationLicense;
use App\Services\Activation\ApplicationActivationService;
use App\Models\User;
use App\Models\Usergroup;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * ApplicationActivationServiceTest
 *
 * Tests cover all 23 scenarios from the specification plus additional coverage.
 *
 * NOTE: Installation IDs must match the pattern /^PHARM-[A-F0-9]{8}$/
 * Valid test IDs use uppercase hex characters only (A-F, 0-9).
 */
class ApplicationActivationServiceTest extends TestCase
{
    use RefreshDatabase;

    private ApplicationActivationService $service;

    // Known-good test installation ID (valid hex suffix, matches service regex)
    private const TEST_INSTALL_ID = 'PHARM-DEADBEEF';

    // Ed25519 keypair shared across all tests in this class
    private static string $secretKey;
    private static string $publicKey;
    private static string $publicPem;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $keypair         = sodium_crypto_sign_keypair();
        self::$secretKey = sodium_crypto_sign_secretkey($keypair);
        self::$publicKey = sodium_crypto_sign_publickey($keypair);

        // Build Ed25519 SubjectPublicKeyInfo PEM (RFC 8410 OID 1.3.101.112)
        $der             = hex2bin('302a300506032b6570032100') . self::$publicKey;
        self::$publicPem = "-----BEGIN PUBLIC KEY-----\n"
            . chunk_split(base64_encode($der), 64, "\n")
            . "-----END PUBLIC KEY-----\n";
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Use in-memory array cache so tests never share cache state
        config(['cache.default' => 'array']);
        Cache::flush();

        // Fake the local storage disk — resets for every test
        Storage::fake('local');

        // Deploy the test public key to the faked disk
        Storage::put('keys/license_public.pem', self::$publicPem);

        // Pin the installation ID to a known valid hex value
        // IMPORTANT: must match regex /^PHARM-[A-F0-9]{8}$/
        Storage::put('private/installation.id', self::TEST_INSTALL_ID);

        // Create a fresh service instance for each test
        $this->service = new ApplicationActivationService();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helper: produce a signed (or deliberately broken) activation JSON string
    // ─────────────────────────────────────────────────────────────────────────

    private function makeActivation(array $overrides = [], bool $sign = true): string
    {
        $defaults = [
            'activated_at'    => Carbon::now('UTC')->subDay()->format('Y-m-d\TH:i:s\Z'),
            'activation_id'   => 'ACT-DEADBEEF00000001',
            'expires_at'      => Carbon::now('UTC')->addYear()->format('Y-m-d\TH:i:s\Z'),
            'installation_id' => self::TEST_INSTALL_ID,
            'product'         => 'pharmacy_inventory',
            'version'         => 1,
        ];

        $payload = array_merge($defaults, $overrides);
        ksort($payload); // deterministic key order — must match service canonicalJson()

        $canonical = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($sign) {
            $rawSig = sodium_crypto_sign_detached($canonical, self::$secretKey);
            $sig    = base64_encode($rawSig);
        } else {
            // Produce an invalid signature of the correct byte length
            $sig = base64_encode(random_bytes(SODIUM_CRYPTO_SIGN_BYTES));
        }

        return json_encode(['payload' => $payload, 'signature' => $sig], JSON_PRETTY_PRINT);
    }

    /** Place activation JSON in protected storage and flush cache + service. */
    private function installActivation(string $json): void
    {
        Storage::put('private/activation.dat', $json);
        Cache::flush();
        $this->service = new ApplicationActivationService();
    }

    /** Write a DB license record for DB-consistency tests. */
    private function syncDb(
        string $installId,
        string $expiresAt,
        string $activationId = 'ACT-DEADBEEF00000001'
    ): void {
        ApplicationLicense::updateOrCreate(
            ['installation_id' => $installId],
            [
                'activation_id' => $activationId,
                'product'       => 'pharmacy_inventory',
                'activated_at'  => Carbon::now()->subDay(),
                'expires_at'    => Carbon::parse($expiresAt),
                'status'        => 'active',
            ]
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Test 1: Valid activation
    // ─────────────────────────────────────────────────────────────────────────
    public function test_1_valid_activation_returns_active_status(): void
    {
        $this->installActivation($this->makeActivation());

        $result = $this->service->getActivation();

        $this->assertTrue($result->signatureValid,    'Signature must be valid');
        $this->assertTrue($result->installationValid, 'Installation ID must match');
        $this->assertTrue($result->productValid,      'Product must match');
        $this->assertTrue($result->notExpired,        'Must not be expired');
        $this->assertSame('active', $result->status);
        $this->assertTrue($result->isFullyValid());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Test 2: Missing activation file
    // ─────────────────────────────────────────────────────────────────────────
    public function test_2_missing_activation_file_returns_missing_status(): void
    {
        $result = $this->service->getActivation();

        $this->assertSame('missing', $result->status);
        $this->assertFalse($result->activationFileExists);
        $this->assertFalse($result->isFullyValid());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Test 3: Expired activation
    // ─────────────────────────────────────────────────────────────────────────
    public function test_3_expired_activation_returns_expired_status(): void
    {
        $json = $this->makeActivation([
            'expires_at' => Carbon::now('UTC')->subMonth()->format('Y-m-d\TH:i:s\Z'),
        ]);
        $this->installActivation($json);

        $result = $this->service->getActivation();

        $this->assertTrue($result->signatureValid);
        $this->assertFalse($result->notExpired);
        $this->assertSame('expired', $result->status);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Test 4: Invalid signature
    // ─────────────────────────────────────────────────────────────────────────
    public function test_4_invalid_signature_returns_invalid_status(): void
    {
        $json = $this->makeActivation(sign: false);
        $this->installActivation($json);

        $result = $this->service->getActivation();

        $this->assertFalse($result->signatureValid);
        $this->assertSame('invalid', $result->status);
        $this->assertTrue($result->isLocked());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Test 5: Modified expiry date after signing
    // ─────────────────────────────────────────────────────────────────────────
    public function test_5_tampered_expiry_date_invalidates_signature(): void
    {
        $data = json_decode($this->makeActivation(), true);
        $data['payload']['expires_at'] = '2099-12-31T00:00:00Z';

        $this->installActivation(json_encode($data));

        $result = $this->service->getActivation();

        $this->assertFalse($result->signatureValid, 'Changing expiry after signing must break verification');
        $this->assertSame('invalid', $result->status);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Test 6: Activation signed for a different installation, field modified to match
    // ─────────────────────────────────────────────────────────────────────────
    public function test_6_tampered_installation_id_field_invalidates_signature(): void
    {
        // Sign an activation for a DIFFERENT installation
        $originalJson = $this->makeActivation(['installation_id' => 'PHARM-F00F1234']);
        $data = json_decode($originalJson, true);

        // Attacker changes the field to match this machine — but signature breaks
        $data['payload']['installation_id'] = self::TEST_INSTALL_ID;

        $this->installActivation(json_encode($data));

        $result = $this->service->getActivation();

        $this->assertFalse($result->signatureValid, 'Changing installation_id post-sign must break signature');
        $this->assertSame('invalid', $result->status);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Test 7: Modified product ID after signing
    // ─────────────────────────────────────────────────────────────────────────
    public function test_7_tampered_product_invalidates_signature(): void
    {
        $data = json_decode($this->makeActivation(), true);
        $data['payload']['product'] = 'other_software';

        $this->installActivation(json_encode($data));

        $result = $this->service->getActivation();

        $this->assertFalse($result->signatureValid);
        $this->assertSame('invalid', $result->status);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Test 8: Modified activation ID after signing
    // ─────────────────────────────────────────────────────────────────────────
    public function test_8_tampered_activation_id_invalidates_signature(): void
    {
        $data = json_decode($this->makeActivation(), true);
        $data['payload']['activation_id'] = 'ACT-FAKEEEEEEEEEEEEE';

        $this->installActivation(json_encode($data));

        $result = $this->service->getActivation();

        $this->assertFalse($result->signatureValid);
        $this->assertSame('invalid', $result->status);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Test 9: Modified activated_at after signing
    // ─────────────────────────────────────────────────────────────────────────
    public function test_9_tampered_activated_at_invalidates_signature(): void
    {
        $data = json_decode($this->makeActivation(), true);
        $data['payload']['activated_at'] = '2020-01-01T00:00:00Z';

        $this->installActivation(json_encode($data));

        $result = $this->service->getActivation();

        $this->assertFalse($result->signatureValid);
        $this->assertSame('invalid', $result->status);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Test 10: Database expiry matches signed — consistent
    // ─────────────────────────────────────────────────────────────────────────
    public function test_10_db_expiry_matching_signed_is_consistent(): void
    {
        $expiresAt = Carbon::now('UTC')->addYear()->format('Y-m-d\TH:i:s\Z');
        $this->installActivation($this->makeActivation(['expires_at' => $expiresAt]));
        $this->syncDb(self::TEST_INSTALL_ID, $expiresAt);

        $result = $this->service->getActivation();

        $this->assertTrue($result->dbConsistent);
        $this->assertSame('active', $result->status);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Test 11: Database expiry extended past signed — tampering detected
    // ─────────────────────────────────────────────────────────────────────────
    public function test_11_db_expiry_extended_manually_is_detected_as_tampered(): void
    {
        $signedExpiry = Carbon::now('UTC')->addYear()->format('Y-m-d\TH:i:s\Z');
        $this->installActivation($this->makeActivation(['expires_at' => $signedExpiry]));

        // Customer manually edits DB expiry to extend subscription by 5 years
        $this->syncDb(self::TEST_INSTALL_ID, Carbon::now()->addYears(5)->format('Y-m-d H:i:s'));

        $result = $this->service->getActivation();

        $this->assertFalse($result->dbConsistent, 'DB date extended past signed date must be detected');
        $this->assertSame('tampered', $result->status);
        $this->assertTrue($result->isLocked());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Test 12: Database expiry reduced below signed — NOT tampering
    // ─────────────────────────────────────────────────────────────────────────
    public function test_12_db_expiry_reduced_is_not_flagged_as_tampered(): void
    {
        $signedExpiry = Carbon::now('UTC')->addYear()->format('Y-m-d\TH:i:s\Z');
        $this->installActivation($this->makeActivation(['expires_at' => $signedExpiry]));

        // DB has an earlier date — signed file is still authoritative and more generous
        $this->syncDb(self::TEST_INSTALL_ID, Carbon::now()->addMonths(3)->format('Y-m-d H:i:s'));

        $result = $this->service->getActivation();

        $this->assertTrue($result->dbConsistent, 'Earlier DB date is not tampering');
        $this->assertSame('active', $result->status);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Test 13: Activation copied to another installation
    // ─────────────────────────────────────────────────────────────────────────
    public function test_13_activation_for_different_installation_is_rejected(): void
    {
        // Valid signed activation but for a DIFFERENT installation
        $json = $this->makeActivation(['installation_id' => 'PHARM-F00DF00D']);
        $this->installActivation($json);

        $result = $this->service->getActivation();

        // Signature IS valid (correctly signed for the other installation)
        // But this machine's ID (PHARM-DEADBEEF) does NOT match the signed ID (PHARM-F00DF00D)
        $this->assertTrue($result->signatureValid,     'Signature is valid for the other installation');
        $this->assertFalse($result->installationValid, 'Installation ID mismatch must be rejected');
        $this->assertSame('invalid', $result->status);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Test 14: Renewal before expiry (developer extends from current expiry)
    // ─────────────────────────────────────────────────────────────────────────
    public function test_14_renewal_before_expiry_produces_valid_activation(): void
    {
        $currentExpiry = Carbon::now('UTC')->addMonths(6);
        $renewedExpiry = $currentExpiry->copy()->addYear(); // developer adds 12 months to current expiry

        $json = $this->makeActivation([
            'expires_at'    => $renewedExpiry->format('Y-m-d\TH:i:s\Z'),
            'activation_id' => 'ACT-RENEWED0DEADBEEF',
        ]);
        $this->installActivation($json);

        $result = $this->service->getActivation();

        $this->assertSame('active', $result->status);
        $this->assertTrue($result->signatureValid);
        // Renewed expiry should be more than 1 year from now
        $this->assertGreaterThan(365, $result->daysRemaining);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Test 15: Renewal after expiry (developer extends from today)
    // ─────────────────────────────────────────────────────────────────────────
    public function test_15_renewal_after_expiry_produces_valid_activation(): void
    {
        $newExpiry = Carbon::now('UTC')->addYear();

        $json = $this->makeActivation([
            'expires_at'    => $newExpiry->format('Y-m-d\TH:i:s\Z'),
            'activation_id' => 'ACT-POSTEXPDEADBEEF1',
        ]);
        $this->installActivation($json);

        $result = $this->service->getActivation();

        $this->assertSame('active', $result->status);
        $this->assertGreaterThan(0, $result->daysRemaining);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Test 16: Malformed activation file
    // ─────────────────────────────────────────────────────────────────────────
    public function test_16_malformed_activation_file_returns_invalid(): void
    {
        Storage::put('private/activation.dat', 'not valid json {{ broken !!');
        $this->service = new ApplicationActivationService();

        $result = $this->service->getActivation();

        $this->assertSame('invalid', $result->status);
        $this->assertFalse($result->signatureValid);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Test 17: Missing public key
    // ─────────────────────────────────────────────────────────────────────────
    public function test_17_missing_public_key_returns_invalid(): void
    {
        Storage::delete('keys/license_public.pem');
        $this->installActivation($this->makeActivation());

        $result = $this->service->getActivation();

        $this->assertFalse($result->signatureValid, 'Without public key, signature cannot be verified');
        $this->assertSame('invalid', $result->status);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Test 18: Dashboard warning at 30 days
    // ─────────────────────────────────────────────────────────────────────────
    public function test_18_days_remaining_correctly_reported_at_30_days(): void
    {
        $expiresAt = Carbon::now('UTC')->addDays(30)->format('Y-m-d\TH:i:s\Z');
        $this->installActivation($this->makeActivation(['expires_at' => $expiresAt]));

        $days = $this->service->daysRemaining();

        $this->assertNotNull($days);
        $this->assertLessThanOrEqual(30, $days);
        $this->assertGreaterThanOrEqual(29, $days);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Test 19: Dashboard warning at 14 days
    // ─────────────────────────────────────────────────────────────────────────
    public function test_19_days_remaining_correctly_reported_at_14_days(): void
    {
        $expiresAt = Carbon::now('UTC')->addDays(14)->format('Y-m-d\TH:i:s\Z');
        $this->installActivation($this->makeActivation(['expires_at' => $expiresAt]));

        $days = $this->service->daysRemaining();

        $this->assertLessThanOrEqual(14, $days);
        $this->assertGreaterThanOrEqual(13, $days);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Test 20: Dashboard warning at 7 days
    // ─────────────────────────────────────────────────────────────────────────
    public function test_20_days_remaining_correctly_reported_at_7_days(): void
    {
        $expiresAt = Carbon::now('UTC')->addDays(7)->format('Y-m-d\TH:i:s\Z');
        $this->installActivation($this->makeActivation(['expires_at' => $expiresAt]));

        $days = $this->service->daysRemaining();

        $this->assertLessThanOrEqual(7, $days);
        $this->assertGreaterThanOrEqual(6, $days);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Test 21: Application locked/redirected after expiry (middleware)
    // ─────────────────────────────────────────────────────────────────────────
    public function test_21_expired_activation_causes_middleware_redirect(): void
    {
        $json = $this->makeActivation([
            'expires_at' => Carbon::now('UTC')->subDay()->format('Y-m-d\TH:i:s\Z'),
        ]);
        $this->installActivation($json);

        // Bind our test service instance into the container so middleware uses it
        $service = $this->service;
        $this->app->instance(ApplicationActivationService::class, $service);

        // Fetch an existing user from the test database (avoids FK issues)
        $user = User::first();
        if (!$user) {
            $this->markTestSkipped('No users in database to test middleware redirect.');
        }

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertRedirect(route('activation.expired'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Test 22: No web POST route for normal users to activate
    // ─────────────────────────────────────────────────────────────────────────
    public function test_22_no_web_post_route_exists_for_users_to_activate(): void
    {
        $postActivationRoutes = collect(\Illuminate\Support\Facades\Route::getRoutes()->getRoutes())
            ->filter(fn ($r) => str_contains($r->uri(), 'activation') && in_array('POST', $r->methods()));

        $this->assertCount(
            0,
            $postActivationRoutes,
            'No POST activation route should be accessible to normal users.'
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Test 23: Valid developer activation file (end-to-end)
    // ─────────────────────────────────────────────────────────────────────────
    public function test_23_valid_developer_activation_file_is_accepted_end_to_end(): void
    {
        // Simulate a real developer-generated file with all optional fields
        $json = $this->makeActivation([
            'customer'      => 'PS General Drug Centre',
            'activation_id' => 'ACT-E2EDEADBEEF0001',
        ]);
        $this->installActivation($json);

        $result = $this->service->getActivation();

        $this->assertSame('active', $result->status, 'End-to-end activation must be active');
        $this->assertTrue($result->signatureValid);
        $this->assertTrue($result->installationValid);
        $this->assertTrue($result->productValid);
        $this->assertTrue($result->notExpired);
        $this->assertTrue($result->isFullyValid());
        $this->assertIsInt($result->daysRemaining);
        $this->assertGreaterThan(0, $result->daysRemaining);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Additional: Installation ID is stable once generated
    // ─────────────────────────────────────────────────────────────────────────
    public function test_installation_id_is_stable_once_generated(): void
    {
        // Remove the preset ID to force generation
        Storage::delete('private/installation.id');
        $this->service = new ApplicationActivationService();

        $id1 = $this->service->installationId();
        $id2 = $this->service->installationId();

        $this->assertSame($id1, $id2, 'Installation ID must be stable across calls');
        $this->assertMatchesRegularExpression(
            '/^PHARM-[A-F0-9]{8}$/',
            $id1,
            'Installation ID must match PHARM-XXXXXXXX format (uppercase hex)'
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Additional: daysRemaining is null when no activation file
    // ─────────────────────────────────────────────────────────────────────────
    public function test_days_remaining_is_null_when_activation_missing(): void
    {
        $this->assertNull($this->service->daysRemaining());
    }
}
