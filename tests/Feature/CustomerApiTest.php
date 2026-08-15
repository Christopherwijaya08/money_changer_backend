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
}
