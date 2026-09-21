<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    setting: Object,
});

// Default mapping entries
const defaultMappings = [
    { db_field: 'name_key', label: 'Nama Pelanggan', json_key: 'name', required: true },
    { db_field: 'status_key', label: 'Status Pembayaran', json_key: 'status', required: true },
];

// Build initial mappings from saved setting or defaults
const buildInitialMappings = () => {
    const saved = props.setting?.json_mapping;
    if (saved && typeof saved === 'object') {
        return defaultMappings.map(dm => ({
            ...dm,
            json_key: saved[dm.db_field] ?? dm.json_key,
        }));
    }
    return defaultMappings.map(dm => ({ ...dm }));
};

const mappings = ref(buildInitialMappings());
const showCodeView = ref(false);
const codeContent = ref('');
const codeError = ref('');

// Convert mappings array to the json_mapping object for form submission
const buildJsonMapping = () => {
    const result = {};
    mappings.value.forEach(m => {
        if (m.db_field && m.json_key) {
            result[m.db_field] = m.json_key;
        }
    });
    return result;
};

// Generate pretty JSON for code view
const generateCodeJson = () => {
    const obj = {};
    mappings.value.forEach(m => {
        obj[m.db_field] = m.json_key;
    });
    return JSON.stringify(obj, null, 2);
};

const toggleCodeView = () => {
    if (!showCodeView.value) {
        codeContent.value = generateCodeJson();
        codeError.value = '';
    } else {
        // Exiting code view → parse and apply
        applyCodeContent();
    }
    showCodeView.value = !showCodeView.value;
};

const applyCodeContent = () => {
    try {
        const parsed = JSON.parse(codeContent.value);
        if (typeof parsed !== 'object' || Array.isArray(parsed)) {
            codeError.value = 'JSON harus berupa object {}.';
            return;
        }
        // Rebuild mappings from parsed code
        const newMappings = [];
        for (const [dbField, jsonKey] of Object.entries(parsed)) {
            const existing = defaultMappings.find(dm => dm.db_field === dbField);
            newMappings.push({
                db_field: dbField,
                label: existing?.label || dbField.replace('_key', ''),
                json_key: String(jsonKey),
                required: existing?.required || false,
            });
        }
        mappings.value = newMappings;
        codeError.value = '';
    } catch (e) {
        codeError.value = 'JSON tidak valid: ' + e.message;
    }
};

const addMapping = () => {
    mappings.value.push({
        db_field: 'custom_' + Date.now(),
        label: '',
        json_key: '',
        required: false,
    });
};

const removeMapping = (index) => {
    mappings.value.splice(index, 1);
};

const form = useForm({
    base_url: props.setting?.base_url || '',
    api_key: props.setting?.api_key || '',
    json_mapping: buildJsonMapping(),
});

const isSaving = ref(false);
const isSyncing = ref(false);

const saveSettings = () => {
    isSaving.value = true;
    form.json_mapping = buildJsonMapping();
    form.post(route('integrasi-billing.update'), {
        preserveScroll: true,
        onFinish: () => (isSaving.value = false),
    });
};

const syncData = () => {
    if (!confirm('Apakah Anda yakin ingin menarik data pelanggan dari API sekarang? Pastikan API telah terhubung.')) {
        return;
    }
    isSyncing.value = true;
    useForm({}).post(route('integrasi-billing.sync'), {
        preserveScroll: true,
        onFinish: () => (isSyncing.value = false),
    });
};
</script>

<template>
    <Head title="Integrasi API Billing" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">Integrasi API Billing</h2>
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
                            <p class="text-xs sm:text-sm text-emerald-700">{{ $page.props.flash.success }}</p>
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
                            <p class="text-xs sm:text-sm text-rose-700">{{ $page.props.flash.error }}</p>
                        </div>
                    </div>
                </div>


                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Setting Section -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200">
                        <div class="p-6 border-b border-slate-200 bg-slate-50/50">
                            <h3 class="text-xs sm:text-sm font-semibold text-slate-800 mb-1">Pengaturan Integrasi API Billing</h3>
                            <p class="text-xs text-slate-500 mb-6">Konfigurasi endpoint dan kredensial untuk menarik data pembayaran tagihan dari sistem lain.</p>
                        </div>
                        <div class="p-6">
                            <form @submit.prevent="saveSettings" class="space-y-4">
                                <div>
                                    <label class="block text-xs sm:text-sm font-medium text-slate-700 mb-1">Base URL Endpoint API</label>
                                    <input 
                                        type="url" 
                                        v-model="form.base_url" 
                                        class="w-full border-slate-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-xs sm:text-sm" 
                                        placeholder="Contoh: https://billinglama.com/api/customers" 
                                    />
                                    <p class="text-xs text-slate-500 mt-1">Pastikan URL lengkap dengan http:// atau https://</p>
                                </div>
                                
                                <div>
                                    <label class="block text-xs sm:text-sm font-medium text-slate-700 mb-1">API Key / Token (Opsional)</label>
                                    <input 
                                        type="text" 
                                        v-model="form.api_key" 
                                        class="w-full border-slate-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-xs sm:text-sm" 
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
                            <p class="text-xs sm:text-sm text-slate-500 mt-1">Tarik data pelanggan dari billing external.</p>
                        </div>
                        <div class="p-6">
                            <div class="mb-6 space-y-4">
                                <!-- Header with toggle -->
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="text-xs sm:text-sm font-semibold text-slate-800 mb-1">Pemetaan Data (Mapping) - Billing</h3>
                                        <p class="text-xs text-slate-500 mb-6">Sesuaikan nama field JSON dari API dengan field yang dikenali oleh sistem billing.</p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="toggleCodeView"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg transition-colors"
                                        :class="showCodeView 
                                            ? 'bg-indigo-100 text-indigo-700 hover:bg-indigo-200' 
                                            : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                                        </svg>
                                        {{ showCodeView ? 'Form View' : 'Code View' }}
                                    </button>
                                </div>

                                <!-- Code View (textarea) -->
                                <div v-if="showCodeView">
                                    <div class="rounded-lg border border-slate-300 overflow-hidden">
                                        <div class="bg-slate-800 px-4 py-2 flex items-center justify-between">
                                            <span class="text-xs text-slate-400 font-mono">json_mapping.json</span>
                                            <span class="text-[10px] text-slate-500 uppercase tracking-wider">JSON</span>
                                        </div>
                                        <textarea
                                            v-model="codeContent"
                                            class="w-full bg-slate-900 text-emerald-400 font-mono text-xs sm:text-sm p-4 border-0 focus:ring-0 resize-y"
                                            rows="10"
                                            spellcheck="false"
                                            placeholder='{ "name_key": "name", ... }'
                                        ></textarea>
                                    </div>
                                    <p v-if="codeError" class="text-xs text-rose-500 mt-2 flex items-center gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                        </svg>
                                        {{ codeError }}
                                    </p>
                                    <p class="text-xs text-slate-500 mt-2">Edit JSON langsung. Klik <b>Form View</b> untuk kembali ke mode form.</p>
                                </div>

                                <!-- Form View (dynamic rows) -->
                                <div v-else class="space-y-3">
                                    <!-- Table Header -->
                                    <div class="grid grid-cols-12 gap-2 px-1">
                                        <div class="col-span-4 text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Kolom Database</div>
                                        <div class="col-span-4 text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Key JSON API</div>
                                        <div class="col-span-3 text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Label</div>
                                        <div class="col-span-1"></div>
                                    </div>

                                    <!-- Mapping Rows -->
                                    <div 
                                        v-for="(mapping, index) in mappings" 
                                        :key="index"
                                        class="grid grid-cols-12 gap-2 items-center group"
                                    >
                                        <!-- DB Field -->
                                        <div class="col-span-4">
                                            <input
                                                type="text"
                                                v-model="mapping.db_field"
                                                class="w-full font-mono text-xs border-slate-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500 py-1.5 bg-slate-50"
                                                :class="{ 'bg-slate-100 text-slate-500': mapping.required }"
                                                :readonly="mapping.required"
                                                placeholder="custom_key"
                                            />
                                        </div>
                                        <!-- JSON Key -->
                                        <div class="col-span-4">
                                            <input
                                                type="text"
                                                v-model="mapping.json_key"
                                                class="w-full font-mono text-xs border-slate-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500 py-1.5"
                                                placeholder="api_key_name"
                                            />
                                        </div>
                                        <!-- Label -->
                                        <div class="col-span-3">
                                            <input
                                                type="text"
                                                v-model="mapping.label"
                                                class="w-full text-xs border-slate-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500 py-1.5"
                                                placeholder="Label"
                                            />
                                        </div>
                                        <!-- Delete -->
                                        <div class="col-span-1 flex justify-center">
                                            <button
                                                v-if="!mapping.required"
                                                type="button"
                                                @click="removeMapping(index)"
                                                class="p-1 text-slate-400 hover:text-rose-500 hover:bg-rose-50 rounded-md transition-colors opacity-0 group-hover:opacity-100"
                                                title="Hapus mapping"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                            <span v-else class="text-amber-500" title="Field wajib, tidak bisa dihapus">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v.01M12 12a1 1 0 00-.894.553l-3 6A1 1 0 009 20h6a1 1 0 00.894-1.447l-3-6A1 1 0 0012 12z" />
                                                </svg>
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Add Button -->
                                    <button
                                        type="button"
                                        @click="addMapping"
                                        class="w-full py-2 border-2 border-dashed border-slate-300 rounded-lg text-xs text-slate-500 font-medium hover:border-indigo-400 hover:text-indigo-500 hover:bg-indigo-50/50 transition-colors flex items-center justify-center gap-1.5"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                        </svg>
                                        Tambah Mapping Baru
                                    </button>
                                </div>
                            </div>
                            
                            <div class="border-t border-slate-100 pt-4">
                                <p class="text-xs text-amber-600 mb-3 bg-amber-50 p-2 rounded border border-amber-200">
                                    <span class="font-bold">Penting:</span> Pastikan Anda sudah mengklik <b>Simpan Pengaturan</b> di form sebelah kiri sebelum menarik data jika ada perubahan mapping.
                                </p>
                                <div class="flex items-center justify-between">
                                    <div class="text-xs sm:text-sm text-slate-600">
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
