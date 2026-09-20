<?php

namespace App\Http\Controllers;

use App\Models\ApiBillingSetting;
use App\Models\Customer;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class IntegrasiBillingController extends Controller
{
    public function index()
    {
        $setting = ApiBillingSetting::first();
        if (!$setting) {
            $setting = ApiBillingSetting::create([
                'provider_name' => 'custom',
            ]);
        }

        return Inertia::render('IntegrasiBilling/Index', [
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

        $setting = ApiBillingSetting::first();
        if ($setting) {
            $setting->update([
                'base_url' => $request->base_url,
                'api_key' => $request->api_key,
                'json_mapping' => $request->json_mapping,
            ]);
        }

        return redirect()->back()->with('success', 'Pengaturan API Billing berhasil disimpan.');
    }

    public function sync()
    {
        $setting = ApiBillingSetting::first();
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
            
            if (!is_array($data)) {
                return redirect()->back()->with('error', 'Format respons API tidak valid. Harus berupa Array JSON.');
            }

            $mapping = is_array($setting->json_mapping) ? $setting->json_mapping : [];
            
            $keyName = $mapping['name_key'] ?? 'name';
            $keyStatus = $mapping['status_key'] ?? 'status';

            $syncedCount = 0;
            
            foreach ($data as $item) {
                $name = $item[$keyName] ?? $item['nama'] ?? null;
                $status = $item[$keyStatus] ?? $item['status'] ?? null;
                
                if (!$name || !$status) {
                    continue; 
                }

                // Normalisasi status Lunas
                $isPaid = strtolower(trim($status)) === 'lunas' || strtolower(trim($status)) === 'paid' || $status == 1;

                if ($isPaid) {
                    $customer = Customer::where('name', $name)->first();

                    if ($customer && $customer->status !== 'paid') {
                        DB::transaction(function () use ($customer) {
                            $customer->update([
                                'status' => 'paid',
                                'amount' => 0,
                            ]);
                
                            Transaction::create([
                                'type' => 'income',
                                'amount' => $customer->base_amount,
                                'description' => 'Pembayaran tagihan (via API Sync) - ' . $customer->name,
                                'area' => $customer->area,
                                'payment_method' => 'API Transfer',
                                'date' => now(),
                            ]);
                        });
                        $syncedCount++;
                    }
                }
            }

            $setting->update([
                'last_sync_at' => now(),
            ]);

            return redirect()->back()->with('success', "Sinkronisasi berhasil: $syncedCount tagihan pelanggan berhasil diubah menjadi Lunas.");
            
        } catch (\Exception $e) {
            Log::error('API Billing Sync Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat sinkronisasi: ' . $e->getMessage());
        }
    }
}
