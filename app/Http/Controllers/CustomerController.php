<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        // ponytail: identity_number/phone are encrypted (no LIKE on ciphertext), so we
        // filter in PHP after loading. Fine at shop scale; add a blind-index hash column
        // (e.g. identity_number_hash) for indexed search if the customer base grows large.
        $customers = Customer::query()->latest()->get();

        if ($search !== '') {
            $needle = Str::lower($search);
            $customers = $customers->filter(
                fn (Customer $c) => Str::contains(Str::lower($c->name), $needle)
                    || Str::contains(Str::lower($c->identity_number), $needle)
                    || Str::contains(Str::lower($c->phone), $needle)
            )->values();
        }

        return CustomerResource::collection($customers->take(50));
    }

    public function store(StoreCustomerRequest $request)
    {
        $data = $request->validated();
        unset($data['ktp_photo']);

        if ($request->hasFile('ktp_photo')) {
            $data['ktp_photo_path'] = $request->file('ktp_photo')->store('ktp-photos', 'local');
        }

        $customer = Customer::create($data);

        return new CustomerResource($customer);
    }
}
