<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    setting: Object,
});

const form = useForm({
    base_url: props.setting?.base_url || '',
    api_key: props.setting?.api_key || '',
});

const isSaving = ref(false);
const isSyncing = ref(false);

const saveSettings = () => {
    isSaving.value = true;
    form.post(route('integrasi.update'), {
        preserveScroll: true,
        onFinish: () => (isSaving.value = false),
    });
};

const syncData = () => {
    if (!confirm('Apakah Anda yakin ingin menarik data pelanggan dari API sekarang? Pastikan API telah terhubung.')) {
        return;
    }
    isSyncing.value = true;
    useForm({}).post(route('integrasi.sync'), {
        preserveScroll: true,
        onFinish: () => (isSyncing.value = false),
    });
};
</script>

<template>
    <Head title="Integrasi & API" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">Integrasi & Sinkronisasi API</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Alert Messages -->
                <div v-if="$page.props.flash?.success" class="bg-emerald-50 border-l-4 border-emerald-500 p-4 rounded-md">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-emerald-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-emerald-700">{{ $page.props.flash.success }}</p>
                        </div>
                    </div>
                </div>

                <div v-if="$page.props.flash?.error" class="bg-rose-50 border-l-4 border-rose-500 p-4 rounded-md">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-rose-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-rose-700">{{ $page.props.flash.error }}</p>
                        </div>
                    </div>
                </div>


                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Setting Section -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200">
                        <div class="p-6 border-b border-slate-200 bg-slate-50/50">
                            <h3 class="text-lg font-bold text-slate-800">Pengaturan API</h3>
                            <p class="text-sm text-slate-500 mt-1">Konfigurasi alamat endpoint dari aplikasi billing lama Anda.</p>
                        </div>
                        <div class="p-6">
                            <form @submit.prevent="saveSettings" class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Base URL Endpoint API</label>
                                    <input 
                                        type="url" 
                                        v-model="form.base_url" 
                                        class="w-full border-slate-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" 
                                        placeholder="Contoh: https://billinglama.com/api/customers" 
                                    />
                                    <p class="text-xs text-slate-500 mt-1">Pastikan URL lengkap dengan http:// atau https://</p>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">API Key / Token (Opsional)</label>
                                    <input 
                                        type="text" 
                                        v-model="form.api_key" 
                                        class="w-full border-slate-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" 
                                        placeholder="Masukkan Bearer Token jika diperlukan" 
                                    />
                                </div>

                                <div class="flex justify-end pt-4">
                                    <button 
                                        type="submit" 
                                        :disabled="isSaving || form.processing" 
                                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50"
                                    >
                                        <svg v-if="isSaving" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Simpan Pengaturan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Sync Section -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200">
                        <div class="p-6 border-b border-slate-200 bg-slate-50/50">
                            <h3 class="text-lg font-bold text-slate-800">Sinkronisasi Data</h3>
                            <p class="text-sm text-slate-500 mt-1">Tarik data pelanggan dari billing external.</p>
                        </div>
                        <div class="p-6">
                            <div class="mb-6">
                                <h4 class="text-sm font-semibold text-slate-700 mb-2">Panduan Struktur JSON:</h4>
                                <p class="text-xs text-slate-500 mb-3">API yang Anda sediakan harus merespon dengan array JSON menggunakan *key* berikut:</p>
                                <div class="bg-slate-900 text-emerald-400 p-4 rounded-lg text-xs font-mono overflow-x-auto">
                                    [<br>
                                    &nbsp;&nbsp;{<br>
                                    &nbsp;&nbsp;&nbsp;&nbsp;"name": "Nama Pelanggan",<br>
                                    &nbsp;&nbsp;&nbsp;&nbsp;"address": "Alamat Lengkap",<br>
                                    &nbsp;&nbsp;&nbsp;&nbsp;"phone": "08123456789",<br>
                                    &nbsp;&nbsp;&nbsp;&nbsp;"package": "Paket 10Mbps",<br>
                                    &nbsp;&nbsp;&nbsp;&nbsp;"price": 150000,<br>
                                    &nbsp;&nbsp;&nbsp;&nbsp;"status": "Aktif"<br>
                                    &nbsp;&nbsp;}<br>
                                    ]
                                </div>
                            </div>
                            
                            <div class="border-t border-slate-100 pt-4">
                                <div class="flex items-center justify-between">
                                    <div class="text-sm text-slate-600">
                                        Terakhir Sinkron: 
                                        <span class="font-semibold text-slate-800">
                                            {{ setting?.last_sync_at ? new Date(setting.last_sync_at).toLocaleString('id-ID') : 'Belum pernah' }}
                                        </span>
                                    </div>
                                    <button 
                                        @click="syncData" 
                                        :disabled="isSyncing || !setting?.base_url" 
                                        class="inline-flex items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50"
                                    >
                                        <svg v-if="isSyncing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                        {{ isSyncing ? 'Menyinkronkan...' : 'Tarik Data' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
