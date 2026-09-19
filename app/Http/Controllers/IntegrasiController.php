<?php

namespace App\Http\Controllers;

use App\Models\ApiSetting;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class IntegrasiController extends Controller
{
    public function index()
    {
        $setting = ApiSetting::first();
        if (!$setting) {
            $setting = ApiSetting::create([
                'provider_name' => 'custom',
            ]);
        }

        return Inertia::render('Integrasi/Index', [
            'setting' => $setting
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'base_url' => 'nullable|url',
            'api_key' => 'nullable|string',
            'json_mapping' => 'nullable|array',
        ]);

        $setting = ApiSetting::first();
        if ($setting) {
            $setting->update([
                'base_url' => $request->base_url,
                'api_key' => $request->api_key,
                'json_mapping' => $request->json_mapping,
            ]);
        }

        return redirect()->back()->with('success', 'Pengaturan API berhasil disimpan.');
    }

    public function sync()
    {
        $setting = ApiSetting::first();
        if (!$setting || !$setting->base_url) {
            return redirect()->back()->with('error', 'URL Endpoint API belum diatur.');
        }

        try {
            $headers = [
                'Accept' => 'application/json',
            ];
            
            if ($setting->api_key) {
                $headers['Authorization'] = 'Bearer ' . $setting->api_key;
            }

            $response = Http::withHeaders($headers)
                            ->timeout(30)
                            ->get($setting->base_url);

            if (!$response->successful()) {
                return redirect()->back()->with('error', 'Gagal menghubungi API: ' . $response->status());
            }

            $data = $response->json();
            
            // Asumsi API mengembalikan Array of Objects
            if (!is_array($data)) {
                return redirect()->back()->with('error', 'Format respons API tidak valid. Harus berupa Array JSON.');
            }

            $mapping = is_array($setting->json_mapping) ? $setting->json_mapping : [];
            
            $keyName = $mapping['name_key'] ?? 'name';
            $keyAddress = $mapping['address_key'] ?? 'address';
            $keyPhone = $mapping['phone_key'] ?? 'phone';
            $keyPackage = $mapping['package_key'] ?? 'package';
            $keyPrice = $mapping['price_key'] ?? 'price';
            $keyStatus = $mapping['status_key'] ?? 'status';

            $syncedCount = 0;
            $updatedCount = 0;
            
            foreach ($data as $item) {
                // Gunakan mapping atau default key (bisa di-nest? Kita asumsikan flat json array of objects)
                $name = $item[$keyName] ?? $item['nama'] ?? null;
                
                if (!$name) {
                    continue; // Skip kalau tidak ada nama
                }

                $address = $item[$keyAddress] ?? $item['alamat'] ?? null;
                $phone = $item[$keyPhone] ?? $item['no_wa'] ?? null;
                $package = $item[$keyPackage] ?? $item['paket'] ?? null;
                $price = $item[$keyPrice] ?? $item['base_amount'] ?? $item['harga'] ?? 0;
                $status = $item[$keyStatus] ?? 'Aktif';

                // Cek apakah pelanggan sudah ada berdasarkan nama
                $customer = Customer::where('name', $name)->first();

                if ($customer) {
                    // Update
                    $customer->update([
                        'alamat' => $address ?? $customer->alamat,
                        'no_wa' => $phone ?? $customer->no_wa,
                        'paket' => $package ?? $customer->paket,
                        'base_amount' => $price ? floatval($price) : $customer->base_amount,
                        'status' => $status,
                    ]);
                    $updatedCount++;
                } else {
                    // Create
                    Customer::create([
                        'name' => $name,
                        'alamat' => $address,
                        'no_wa' => $phone,
                        'paket' => $package,
                        'base_amount' => floatval($price),
                        'status' => $status,
                        'register_date' => now()->format('Y-m-d'),
                    ]);
                    $syncedCount++;
                }
            }

            $setting->update([
                'last_sync_at' => now(),
            ]);

            return redirect()->back()->with('success', "Sinkronisasi berhasil: $syncedCount pelanggan baru ditambahkan, $updatedCount diperbarui.");
            
        } catch (\Exception $e) {
            Log::error('API Sync Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat sinkronisasi: ' . $e->getMessage());
        }
    }
}
