<?php

namespace Tests\Feature;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CustomerApiTest extends TestCase
{
    use RefreshDatabase;

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
}
