<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // owner: the ktp-photo endpoint is owner-only, and nothing else here cares about role.
        Sanctum::actingAs(User::factory()->create(['role' => 'owner']));
    }

    public function test_store_creates_customer_with_ktp_photo(): void
    {
        Storage::fake('local');

        $response = $this->postJson('/api/customers', [
            'name' => 'Gilang Ramadhan',
            'identity_number' => '3171234567890099',
            'phone' => '081298765432',
            'address' => 'Jl. Merdeka',
            'ktp_photo' => UploadedFile::fake()->create('ktp.jpg', 100, 'image/jpeg'),
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.name', 'Gilang Ramadhan');
        $response->assertJsonPath('data.identity_number', '3171234567890099');
        $response->assertJsonPath('data.has_ktp_photo', true);

        $customer = Customer::firstOrFail();
        Storage::disk('local')->assertExists($customer->ktp_photo_path);
    }

    public function test_store_rejects_missing_required_fields(): void
    {
        $response = $this->postJson('/api/customers', ['name' => 'Tanpa Data Lain']);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['identity_number', 'phone']);
    }

    public function test_index_searches_by_name_identity_and_phone_despite_encryption(): void
    {
        Customer::create(['name' => 'Andi Wijaya', 'identity_number' => '3171234567890001', 'phone' => '081234567890']);
        Customer::create(['name' => 'Budi Santoso', 'identity_number' => '3171234567890002', 'phone' => '081234567891']);

        $byName = $this->getJson('/api/customers?search=Andi');
        $byName->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Andi Wijaya');

        $byPhone = $this->getJson('/api/customers?search=567891');
        $byPhone->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Budi Santoso');

        $byIdentity = $this->getJson('/api/customers?search=7890002');
        $byIdentity->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Budi Santoso');
    }

    public function test_show_returns_customer_detail(): void
    {
        $customer = Customer::create(['name' => 'Andi Wijaya', 'identity_number' => '3171234567890001', 'phone' => '081234567890']);

        $response = $this->getJson("/api/customers/{$customer->id}");

        $response->assertOk();
        $response->assertJsonPath('data.name', 'Andi Wijaya');
    }

    public function test_show_returns_404_for_unknown_customer(): void
    {
        $response = $this->getJson('/api/customers/999');

        $response->assertNotFound();
    }

    public function test_update_changes_customer_fields(): void
    {
        $customer = Customer::create(['name' => 'Andi Wijaya', 'identity_number' => '3171234567890001', 'phone' => '081234567890']);

        $response = $this->putJson("/api/customers/{$customer->id}", [
            'name' => 'Andi Wijaya Kusuma',
            'identity_number' => '3171234567890001',
            'phone' => '081234567899',
            'address' => 'Jl. Sudirman',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.name', 'Andi Wijaya Kusuma');
        $response->assertJsonPath('data.phone', '081234567899');
        $this->assertEquals('Andi Wijaya Kusuma', $customer->fresh()->name);
    }

    public function test_destroy_deletes_customer(): void
    {
        $customer = Customer::create(['name' => 'Andi Wijaya', 'identity_number' => '3171234567890001', 'phone' => '081234567890']);

        $response = $this->deleteJson("/api/customers/{$customer->id}");

        $response->assertNoContent();
        $this->assertModelMissing($customer);
    }

    public function test_upload_ktp_photo_stores_file_and_replaces_previous(): void
    {
        Storage::fake('local');
        $customer = Customer::create(['name' => 'Andi Wijaya', 'identity_number' => '3171234567890001', 'phone' => '081234567890']);

        $first = $this->postJson("/api/customers/{$customer->id}/ktp-photo", [
            'ktp_photo' => UploadedFile::fake()->create('ktp.jpg', 100, 'image/jpeg'),
        ]);
        $first->assertOk();
        $first->assertJsonPath('data.has_ktp_photo', true);
        $firstPath = $customer->fresh()->ktp_photo_path;
        Storage::disk('local')->assertExists($firstPath);

        $second = $this->postJson("/api/customers/{$customer->id}/ktp-photo", [
            'ktp_photo' => UploadedFile::fake()->create('ktp-baru.jpg', 100, 'image/jpeg'),
        ]);
        $second->assertOk();
        Storage::disk('local')->assertMissing($firstPath);
        Storage::disk('local')->assertExists($customer->fresh()->ktp_photo_path);
    }

    public function test_upload_ktp_photo_rejects_non_image(): void
    {
        Storage::fake('local');
        $customer = Customer::create(['name' => 'Andi Wijaya', 'identity_number' => '3171234567890001', 'phone' => '081234567890']);

        $response = $this->postJson("/api/customers/{$customer->id}/ktp-photo", [
            'ktp_photo' => UploadedFile::fake()->create('ktp.pdf', 100, 'application/pdf'),
        ]);

        $response->assertUnprocessable();
    }

    public function test_ktp_photo_returns_the_stored_file(): void
    {
        Storage::fake('local');
        $customer = Customer::create(['name' => 'Andi Wijaya', 'identity_number' => '3171234567890001', 'phone' => '081234567890']);
        $this->postJson("/api/customers/{$customer->id}/ktp-photo", [
            'ktp_photo' => UploadedFile::fake()->create('ktp.jpg', 100, 'image/jpeg'),
        ]);

        $response = $this->get("/api/customers/{$customer->id}/ktp-photo");

        $response->assertOk();
    }

    public function test_ktp_photo_returns_404_when_customer_has_none(): void
    {
        $customer = Customer::create(['name' => 'Andi Wijaya', 'identity_number' => '3171234567890001', 'phone' => '081234567890']);

        $response = $this->get("/api/customers/{$customer->id}/ktp-photo");

        $response->assertNotFound();
    }

    public function test_ktp_photo_rejects_admin_role(): void
    {
        Storage::fake('local');
        $customer = Customer::create(['name' => 'Andi Wijaya', 'identity_number' => '3171234567890001', 'phone' => '081234567890']);
        $this->postJson("/api/customers/{$customer->id}/ktp-photo", [
            'ktp_photo' => UploadedFile::fake()->create('ktp.jpg', 100, 'image/jpeg'),
        ]);

        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));
        $response = $this->get("/api/customers/{$customer->id}/ktp-photo");

        $response->assertForbidden();
    }

    public function test_transactions_lists_only_that_customers_history(): void
    {
        $customer = Customer::create(['name' => 'Andi Wijaya', 'identity_number' => '3171234567890001', 'phone' => '081234567890']);
        $otherCustomer = Customer::create(['name' => 'Budi Santoso', 'identity_number' => '3171234567890002', 'phone' => '081234567891']);
        $currency = Currency::create(['code' => 'USD']);
        $employee = Employee::create(['name' => 'Dewi', 'position' => 'Teller']);
        $user = User::create(['name' => 'Admin', 'email' => 'admin@test.local', 'password' => 'secret']);

        $ownTransaction = Transaction::create([
            'transaction_number' => 'TRX-20260816-001',
            'type' => 'buy',
            'currency_id' => $currency->id,
            'amount' => 500,
            'rate_default' => 15750,
            'rate_actual' => 15780,
            'total_amount' => 7890000,
            'customer_id' => $customer->id,
            'employee_id' => $employee->id,
            'user_id' => $user->id,
        ]);

        Transaction::create([
            'transaction_number' => 'TRX-20260816-002',
            'type' => 'sell',
            'currency_id' => $currency->id,
            'amount' => 200,
            'rate_default' => 15750,
            'rate_actual' => 15780,
            'total_amount' => 3156000,
            'customer_id' => $otherCustomer->id,
            'employee_id' => $employee->id,
            'user_id' => $user->id,
        ]);

        $response = $this->getJson("/api/customers/{$customer->id}/transactions");

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $ownTransaction->id);
    }
}
