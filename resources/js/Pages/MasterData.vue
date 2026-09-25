<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref } from 'vue';

const props = defineProps({
    expenseCategories: {
        type: Array,
        default: () => [],
    },
    incomeCategories: {
        type: Array,
        default: () => [],
    },
    paymentMethods: {
        type: Array,
        default: () => [],
    },
    materials: {
        type: Array,
        default: () => [],
    },
    companyExpenseTypes: {
        type: Array,
        default: () => [],
    },
    internetPackages: {
        type: Array,
        default: () => [],
    },
    settings: {
        type: Object,
        default: () => ({}),
    },
    areas: {
        type: Array,
        default: () => [],
    }
});

// Form Kategori Pengeluaran
const form = useForm({
    name: ''
});

// Form Settings Global
const settingsForm = useForm({
    global_due_date: props.settings.global_due_date || '',
    global_due_time: props.settings.global_due_time || '23:59',
    commission_sales_booking: props.settings.commission_sales_booking || '',
    commission_sales_monthly: props.settings.commission_sales_monthly || '',
    commission_upline_1_monthly: props.settings.commission_upline_1_monthly || '',
    commission_upline_2_monthly: props.settings.commission_upline_2_monthly || '',
    commission_payout_date: props.settings.commission_payout_date || '13',
    global_installation_fee: props.settings.global_installation_fee || '',
    support_wa_number: props.settings.support_wa_number || '',
    api_integration_url: props.settings.api_integration_url || '',
    api_integration_token: props.settings.api_integration_token || '',
});

const submitSettings = () => {
    settingsForm.post(route('master-data.settings.update'), {
        preserveScroll: true,
    });
};

const submit = () => {
    form.post(route('expense-categories.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};

const deleteCategory = (category) => {
    if (confirm(`Apakah Anda yakin ingin menghapus kategori "${category.name}"?`)) {
        router.delete(route('expense-categories.destroy', category.id), {
            preserveScroll: true,
        });
    }
};

// Form Kategori Pemasukan
const incomeCatForm = useForm({
    name: ''
});

const submitIncomeCat = () => {
    incomeCatForm.post(route('income-categories.store'), {
        preserveScroll: true,
        onSuccess: () => incomeCatForm.reset(),
    });
};

const deleteIncomeCategory = (category) => {
    if (confirm(`Apakah Anda yakin ingin menghapus kategori "${category.name}"?`)) {
        router.delete(route('income-categories.destroy', category.id), {
            preserveScroll: true,
        });
    }
};

// Form Metode Pembayaran
const pmForm = useForm({
    name: ''
});

const submitPm = () => {
    pmForm.post(route('payment-methods.store'), {
        preserveScroll: true,
        onSuccess: () => pmForm.reset(),
    });
};

const deletePaymentMethod = (method) => {
    if (confirm(`Apakah Anda yakin ingin menghapus metode pembayaran "${method.name}"?`)) {
        router.delete(route('payment-methods.destroy', method.id), {
            preserveScroll: true,
        });
    }
};

// Form Material
const matForm = useForm({
    name: ''
});

const submitMaterial = () => {
    matForm.post(route('materials.store'), {
        preserveScroll: true,
        onSuccess: () => matForm.reset(),
    });
};

const deleteMaterial = (material) => {
    if (confirm(`Apakah Anda yakin ingin menghapus material "${material.name}"?`)) {
        router.delete(route('materials.destroy', material.id), {
            preserveScroll: true,
        });
    }
};

// Form Jenis Pengeluaran Perusahaan
const cetForm = useForm({
    name: ''
});

const submitCet = () => {
    cetForm.post(route('company-expense-types.store'), {
        preserveScroll: true,
        onSuccess: () => cetForm.reset(),
    });
};

const deleteCet = (type) => {
    if (confirm(`Apakah Anda yakin ingin menghapus jenis pengeluaran "${type.name}"?`)) {
        router.delete(route('company-expense-types.destroy', type.id), {
            preserveScroll: true,
        });
    }
};

// Form Paket Internet
const ipForm = useForm({
    name: '',
    price: '',
    installation_fee: ''
});

const submitIp = () => {
    ipForm.post(route('internet-packages.store'), {
        preserveScroll: true,
        onSuccess: () => ipForm.reset(),
    });
};

const deleteIp = (pkg) => {
    if (confirm(`Apakah Anda yakin ingin menghapus paket internet "${pkg.name}"?`)) {
        router.delete(route('internet-packages.destroy', pkg.id), {
            preserveScroll: true,
        });
    }
};

const editingIpId = ref(null);
const editIpForm = useForm({
    name: '',
    price: '',
    installation_fee: ''
});

const startEditIp = (pkg) => {
    editingIpId.value = pkg.id;
    editIpForm.name = pkg.name;
    editIpForm.price = pkg.price;
    editIpForm.installation_fee = pkg.installation_fee;
};

const cancelEditIp = () => {
    editingIpId.value = null;
    editIpForm.reset();
    editIpForm.clearErrors();
};

const saveEditIp = (pkg) => {
    editIpForm.put(route('internet-packages.update', pkg.id), {
        preserveScroll: true,
        onSuccess: () => {
            editingIpId.value = null;
            editIpForm.reset();
        }
    });
};

const formatRupiah = (number) => {
    if (!number) return 'Rp 0';
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(number);
};

// Form Area
const areaForm = useForm({
    name: '',
    description: '',
    installation_fee: ''
});

const submitArea = () => {
    areaForm.post(route('areas.store'), {
        preserveScroll: true,
        onSuccess: () => areaForm.reset(),
    });
};

const deleteArea = (area) => {
    if (confirm(`Apakah Anda yakin ingin menghapus area "${area.name}"?`)) {
        router.delete(route('areas.destroy', area.id), {
            preserveScroll: true,
        });
    }
};

const editingAreaId = ref(null);
const editAreaForm = useForm({
    name: '',
    description: '',
    installation_fee: ''
});

const startEditArea = (area) => {
    editingAreaId.value = area.id;
    editAreaForm.name = area.name;
    editAreaForm.description = area.description || '';
    editAreaForm.installation_fee = area.installation_fee;
};

const cancelEditArea = () => {
    editingAreaId.value = null;
    editAreaForm.reset();
    editAreaForm.clearErrors();
};

const saveEditArea = (area) => {
    editAreaForm.put(route('areas.update', area.id), {
        preserveScroll: true,
        onSuccess: () => {
            editingAreaId.value = null;
            editAreaForm.reset();
        }
    });
};
</script>

<template>
    <Head title="Master Data" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-2xl font-bold tracking-tight text-slate-800">
                    Master Data
                </h2>
                <p class="text-xs sm:text-sm text-slate-500">
                    Kelola data master seperti Kategori Pengeluaran, Jenis Pengeluaran, Metode Pembayaran, dan Material.
                </p>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-full">
                <div class="mb-8">
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 overflow-hidden">
                        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-bold text-slate-800">Pengaturan Tagihan & Jatuh Tempo (Global)</h3>
                                <p class="text-xs text-slate-500 mt-1">Pengaturan ini berlaku untuk semua pelanggan. Kapan tagihan bulanan dianggap menunggak.</p>
                            </div>
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-rose-50 text-rose-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                        
                        <div class="p-6 bg-slate-50/50">
                            <form @submit.prevent="submitSettings" class="space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Tanggal Jatuh Tempo (1-31)</label>
                                        <input 
                                            type="number" 
                                            min="1" max="31"
                                            v-model="settingsForm.global_due_date" 
                                            placeholder="Contoh: 10"
                                            class="w-full rounded-xl border-slate-300 text-xs sm:text-sm focus:border-rose-500 focus:ring-rose-500 shadow-sm"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Jam Jatuh Tempo</label>
                                        <input 
                                            type="time" 
                                            v-model="settingsForm.global_due_time" 
                                            class="w-full rounded-xl border-slate-300 text-xs sm:text-sm focus:border-rose-500 focus:ring-rose-500 shadow-sm"
                                        />
                                    </div>
                                </div>
                                <div class="flex justify-end">
                                    <button 
                                        type="submit" 
                                        :disabled="settingsForm.processing"
                                        class="bg-rose-600 hover:bg-rose-700 text-white px-6 py-2.5 rounded-xl text-xs sm:text-sm font-semibold shadow-sm transition-colors disabled:opacity-50 flex items-center gap-2"
                                    >
                                        <svg v-if="settingsForm.processing" class="animate-spin -ml-1 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                        </svg>
                                        Simpan Pengaturan
                                    </button>
                                </div>
                            </form>
                            <div class="mt-4 text-xs text-slate-500">
                                <em>Catatan: Pelanggan yang belum membayar setelah melewati Tanggal & Jam di atas akan otomatis masuk ke daftar Tagihan Jatuh Tempo.</em>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-8">
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 overflow-hidden">
                        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-bold text-slate-800">Pengaturan Komisi Sales & Afiliasi</h3>
                                <p class="text-xs text-slate-500 mt-1">Pengaturan nilai komisi untuk agen sales dan afiliasi bertingkat.</p>
                            </div>
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                        
                        <div class="p-6 bg-slate-50/50">
                            <form @submit.prevent="submitSettings" class="space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Komisi Booking (Sekali Bayar)</label>
                                        <input 
                                            type="number" 
                                            v-model="settingsForm.commission_sales_booking" 
                                            placeholder="Contoh: 50000"
                                            class="w-full rounded-xl border-slate-300 text-xs sm:text-sm focus:border-rose-500 focus:ring-rose-500 shadow-sm"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Komisi Bulanan (Direct Sales)</label>
                                        <input 
                                            type="number" 
                                            v-model="settingsForm.commission_sales_monthly" 
                                            placeholder="Contoh: 10000"
                                            class="w-full rounded-xl border-slate-300 text-xs sm:text-sm focus:border-rose-500 focus:ring-rose-500 shadow-sm"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Komisi Upline (Downline 1) / Bulan</label>
                                        <input 
                                            type="number" 
                                            v-model="settingsForm.commission_upline_1_monthly" 
                                            placeholder="Contoh: 5000"
                                            class="w-full rounded-xl border-slate-300 text-xs sm:text-sm focus:border-rose-500 focus:ring-rose-500 shadow-sm"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Komisi Upline (Downline 2) / Bulan</label>
                                        <input 
                                            type="number" 
                                            v-model="settingsForm.commission_upline_2_monthly" 
                                            placeholder="Contoh: 2000"
                                            class="w-full rounded-xl border-slate-300 text-xs sm:text-sm focus:border-rose-500 focus:ring-rose-500 shadow-sm"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Tanggal Pencairan Komisi</label>
                                        <input 
                                            type="number" 
                                            min="1" max="31"
                                            v-model="settingsForm.commission_payout_date" 
                                            placeholder="Contoh: 13"
                                            class="w-full rounded-xl border-slate-300 text-xs sm:text-sm focus:border-rose-500 focus:ring-rose-500 shadow-sm"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Biaya Pemasangan Global</label>
                                        <input 
                                            type="number" 
                                            v-model="settingsForm.global_installation_fee" 
                                            placeholder="Contoh: 150000 (Kosongkan jika gratis)"
                                            class="w-full rounded-xl border-slate-300 text-xs sm:text-sm focus:border-rose-500 focus:ring-rose-500 shadow-sm"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Nomor WA Support</label>
                                        <input 
                                            type="text" 
                                            v-model="settingsForm.support_wa_number" 
                                            placeholder="Contoh: 6281234567890"
                                            class="w-full rounded-xl border-slate-300 text-xs sm:text-sm focus:border-rose-500 focus:ring-rose-500 shadow-sm"
                                        />
                                    </div>
                                </div>
                                <div class="flex justify-end">
                                    <button 
                                        type="submit" 
                                        :disabled="settingsForm.processing"
                                        class="bg-rose-600 hover:bg-rose-700 text-white px-6 py-2.5 rounded-xl text-xs sm:text-sm font-semibold shadow-sm transition-colors disabled:opacity-50 flex items-center gap-2"
                                    >
                                        <svg v-if="settingsForm.processing" class="animate-spin -ml-1 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                        </svg>
                                        Simpan Pengaturan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="mb-8">
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 overflow-hidden">
                        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-bold text-slate-800">Pengaturan Integrasi API</h3>
                                <p class="text-xs text-slate-500 mt-1">Integrasi dengan aplikasi lain (misal: mengirim data booking baru).</p>
                            </div>
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-teal-50 text-teal-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                </svg>
                            </div>
                        </div>
                        
                        <div class="p-6 bg-slate-50/50">
                            <form @submit.prevent="submitSettings" class="space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">API URL</label>
                                        <input 
                                            type="text" 
                                            v-model="settingsForm.api_integration_url" 
                                            placeholder="Contoh: https://app-lain.com/api/bookings"
                                            class="w-full rounded-xl border-slate-300 text-xs sm:text-sm focus:border-teal-500 focus:ring-teal-500 shadow-sm"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">API Token</label>
                                        <input 
                                            type="text" 
                                            v-model="settingsForm.api_integration_token" 
                                            placeholder="Token untuk otentikasi API"
                                            class="w-full rounded-xl border-slate-300 text-xs sm:text-sm focus:border-teal-500 focus:ring-teal-500 shadow-sm"
                                        />
                                    </div>
                                </div>
                                <div class="flex justify-end">
                                    <button 
                                        type="submit" 
                                        :disabled="settingsForm.processing"
                                        class="bg-teal-600 hover:bg-teal-700 text-white px-6 py-2.5 rounded-xl text-xs sm:text-sm font-semibold shadow-sm transition-colors disabled:opacity-50 flex items-center gap-2"
                                    >
                                        <svg v-if="settingsForm.processing" class="animate-spin -ml-1 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                        </svg>
                                        Simpan Pengaturan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Kategori Pengeluaran Section -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 overflow-hidden">
                        <div class="p-6 border-b border-slate-100">
                            <h3 class="text-lg font-bold text-slate-800">Kategori Pengeluaran</h3>
                            <p class="text-xs text-slate-500 mt-1">Tambahkan kategori untuk mengelompokkan pengeluaran Anda.</p>
                        </div>
                        
                        <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                            <form @submit.prevent="submit" class="flex gap-3 items-start">
                                <div class="flex-1">
                                    <input 
                                        type="text" 
                                        v-model="form.name" 
                                        placeholder="Nama Kategori (contoh: Operasional, Gaji, dll)"
                                        required
                                        class="w-full rounded-xl border-slate-300 text-xs sm:text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                                    />
                                    <p v-if="form.errors.name" class="mt-1 text-xs text-rose-600">{{ form.errors.name }}</p>
                                </div>
                                <button 
                                    type="submit" 
                                    :disabled="form.processing"
                                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold shadow-sm transition-colors disabled:opacity-50"
                                >
                                    Tambah
                                </button>
                            </form>
                        </div>

                        <div class="p-0">
                            <div class="overflow-x-auto w-full pb-4">
<table class="w-full text-left text-xs sm:text-sm">
                                <thead>
                                    <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold uppercase text-slate-500">
                                        <th class="px-3 py-2 sm:px-6 sm:py-3">Nama Kategori</th>
                                        <th class="px-3 py-2 sm:px-6 sm:py-3 w-24 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="cat in expenseCategories" :key="cat.id" class="hover:bg-slate-50/80">
                                        <td class="px-3 py-2 sm:px-6 sm:py-3 font-medium text-slate-700">
                                            {{ cat.name }}
                                        </td>
                                        <td class="px-3 py-2 sm:px-6 sm:py-3 text-right">
                                            <button 
                                                @click="deleteCategory(cat)"
                                                class="text-rose-500 hover:text-rose-700 hover:bg-rose-50 p-1.5 rounded-lg transition-colors"
                                                title="Hapus Kategori"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="expenseCategories.length === 0">
                                        <td colspan="2" class="px-6 py-8 text-center text-slate-400">
                                            Belum ada kategori pengeluaran.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
</div>
                        </div>
                    </div>

                    <!-- Kategori Pemasukan Section -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 overflow-hidden">
                        <div class="p-6 border-b border-slate-100">
                            <h3 class="text-lg font-bold text-slate-800">Kategori Pemasukan</h3>
                            <p class="text-xs text-slate-500 mt-1">Tambahkan kategori untuk mengelompokkan pemasukan Anda.</p>
                        </div>
                        
                        <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                            <form @submit.prevent="submitIncomeCat" class="flex gap-3 items-start">
                                <div class="flex-1">
                                    <input 
                                        type="text" 
                                        v-model="incomeCatForm.name" 
                                        placeholder="Nama Kategori (contoh: Biaya Pemasangan, Voucher, dll)"
                                        required
                                        class="w-full rounded-xl border-slate-300 text-xs sm:text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                                    />
                                    <p v-if="incomeCatForm.errors.name" class="mt-1 text-xs text-rose-600">{{ incomeCatForm.errors.name }}</p>
                                </div>
                                <button 
                                    type="submit" 
                                    :disabled="incomeCatForm.processing"
                                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold shadow-sm transition-colors disabled:opacity-50"
                                >
                                    Tambah
                                </button>
                            </form>
                        </div>

                        <div class="p-0">
                            <div class="overflow-x-auto w-full pb-4">
<table class="w-full text-left text-xs sm:text-sm">
                                <thead>
                                    <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold uppercase text-slate-500">
                                        <th class="px-3 py-2 sm:px-6 sm:py-3">Nama Kategori</th>
                                        <th class="px-3 py-2 sm:px-6 sm:py-3 w-24 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="cat in incomeCategories" :key="cat.id" class="hover:bg-slate-50/80">
                                        <td class="px-3 py-2 sm:px-6 sm:py-3 font-medium text-slate-700">
                                            {{ cat.name }}
                                        </td>
                                        <td class="px-3 py-2 sm:px-6 sm:py-3 text-right">
                                            <button 
                                                @click="deleteIncomeCategory(cat)"
                                                class="text-rose-500 hover:text-rose-700 hover:bg-rose-50 p-1.5 rounded-lg transition-colors"
                                                title="Hapus Kategori"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="incomeCategories.length === 0">
                                        <td colspan="2" class="px-6 py-8 text-center text-slate-400">
                                            Belum ada kategori pemasukan.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
</div>
                        </div>
                    </div>

                    <!-- Master Metode Pembayaran Section -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 overflow-hidden">
                        <div class="p-6 border-b border-slate-100">
                            <h3 class="text-lg font-bold text-slate-800">Metode Pembayaran</h3>
                            <p class="text-xs text-slate-500 mt-1">Kelola daftar metode pembayaran (contoh: Tunai, Transfer BCA, OVO, dll).</p>
                        </div>
                        
                        <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                            <form @submit.prevent="submitPm" class="flex gap-3 items-start">
                                <div class="flex-1">
                                    <input 
                                        type="text" 
                                        v-model="pmForm.name" 
                                        placeholder="Nama Metode Pembayaran"
                                        required
                                        class="w-full rounded-xl border-slate-300 text-xs sm:text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                                    />
                                    <p v-if="pmForm.errors.name" class="mt-1 text-xs text-rose-600">{{ pmForm.errors.name }}</p>
                                </div>
                                <button 
                                    type="submit" 
                                    :disabled="pmForm.processing"
                                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold shadow-sm transition-colors disabled:opacity-50"
                                >
                                    Tambah
                                </button>
                            </form>
                        </div>

                        <div class="p-0">
                            <div class="overflow-x-auto w-full pb-4">
<table class="w-full text-left text-xs sm:text-sm">
                                <thead>
                                    <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold uppercase text-slate-500">
                                        <th class="px-3 py-2 sm:px-6 sm:py-3">Nama Metode</th>
                                        <th class="px-3 py-2 sm:px-6 sm:py-3 w-24 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="pm in paymentMethods" :key="pm.id" class="hover:bg-slate-50/80">
                                        <td class="px-3 py-2 sm:px-6 sm:py-3 font-medium text-slate-700">
                                            {{ pm.name }}
                                        </td>
                                        <td class="px-3 py-2 sm:px-6 sm:py-3 text-right">
                                            <button 
                                                @click="deletePaymentMethod(pm)"
                                                class="text-rose-500 hover:text-rose-700 hover:bg-rose-50 p-1.5 rounded-lg transition-colors"
                                                title="Hapus Metode"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="paymentMethods.length === 0">
                                        <td colspan="2" class="px-6 py-8 text-center text-slate-400">
                                            Belum ada metode pembayaran.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
</div>
                        </div>
                    </div>
                </div>

                <!-- Master Material Section -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 overflow-hidden md:col-span-2">
                    <div class="p-6 border-b border-slate-100">
                        <h3 class="text-lg font-bold text-slate-800">Daftar Material</h3>
                        <p class="text-xs text-slate-500 mt-1">Kelola daftar material untuk pengeluaran perusahaan (contoh: Kabel FO, Konektor, Router, dll).</p>
                    </div>
                    
                    <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                        <form @submit.prevent="submitMaterial" class="flex gap-3 items-start">
                            <div class="flex-1">
                                <input 
                                    type="text" 
                                    v-model="matForm.name" 
                                    placeholder="Nama Material (contoh: Kabel FO, Router, Konektor)"
                                    required
                                    class="w-full rounded-xl border-slate-300 text-xs sm:text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                                />
                                <p v-if="matForm.errors.name" class="mt-1 text-xs text-rose-600">{{ matForm.errors.name }}</p>
                            </div>
                            <button 
                                type="submit" 
                                :disabled="matForm.processing"
                                class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold shadow-sm transition-colors disabled:opacity-50"
                            >
                                Tambah
                            </button>
                        </form>
                    </div>

                    <div class="p-0">
                        <div class="overflow-x-auto w-full pb-4">
<table class="w-full text-left text-xs sm:text-sm">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold uppercase text-slate-500">
                                    <th class="px-3 py-2 sm:px-6 sm:py-3">Nama Material</th>
                                    <th class="px-3 py-2 sm:px-6 sm:py-3 w-24 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="mat in materials" :key="mat.id" class="hover:bg-slate-50/80">
                                    <td class="px-3 py-2 sm:px-6 sm:py-3 font-medium text-slate-700">
                                        {{ mat.name }}
                                    </td>
                                    <td class="px-3 py-2 sm:px-6 sm:py-3 text-right">
                                        <button 
                                            @click="deleteMaterial(mat)"
                                            class="text-rose-500 hover:text-rose-700 hover:bg-rose-50 p-1.5 rounded-lg transition-colors"
                                            title="Hapus Material"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="materials.length === 0">
                                    <td colspan="2" class="px-6 py-8 text-center text-slate-400">
                                        Belum ada data material.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
</div>
                    </div>
                </div>

                <!-- Master Jenis Pengeluaran Perusahaan Section -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 overflow-hidden md:col-span-2">
                    <div class="p-6 border-b border-slate-100">
                        <h3 class="text-lg font-bold text-slate-800">Jenis Pengeluaran Perusahaan</h3>
                        <p class="text-xs text-slate-500 mt-1">Kelola daftar jenis pengeluaran ketika kategori "Perusahaan" dipilih (contoh: Material, Uang Mingguan).</p>
                    </div>
                    
                    <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                        <form @submit.prevent="submitCet" class="flex gap-3 items-start">
                            <div class="flex-1">
                                <input 
                                    type="text" 
                                    v-model="cetForm.name" 
                                    placeholder="Nama Jenis Pengeluaran (contoh: Material)"
                                    required
                                    class="w-full rounded-xl border-slate-300 text-xs sm:text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                                />
                                <p v-if="cetForm.errors.name" class="mt-1 text-xs text-rose-600">{{ cetForm.errors.name }}</p>
                            </div>
                            <button 
                                type="submit" 
                                :disabled="cetForm.processing"
                                class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold shadow-sm transition-colors disabled:opacity-50"
                            >
                                Tambah
                            </button>
                        </form>
                    </div>

                    <div class="p-0">
                        <div class="overflow-x-auto w-full pb-4">
<table class="w-full text-left text-xs sm:text-sm">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold uppercase text-slate-500">
                                    <th class="px-3 py-2 sm:px-6 sm:py-3">Nama Jenis</th>
                                    <th class="px-3 py-2 sm:px-6 sm:py-3 w-24 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="type in companyExpenseTypes" :key="type.id" class="hover:bg-slate-50/80">
                                    <td class="px-3 py-2 sm:px-6 sm:py-3 font-medium text-slate-700">
                                        {{ type.name }}
                                    </td>
                                    <td class="px-3 py-2 sm:px-6 sm:py-3 text-right">
                                        <button 
                                            @click="deleteCet(type)"
                                            class="text-rose-500 hover:text-rose-700 hover:bg-rose-50 p-1.5 rounded-lg transition-colors"
                                            title="Hapus Jenis"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="companyExpenseTypes.length === 0">
                                    <td colspan="2" class="px-6 py-8 text-center text-slate-400">
                                        Belum ada data jenis pengeluaran perusahaan.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
</div>
                    </div>
                </div>

                <!-- Master Paket Internet Section -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 overflow-hidden md:col-span-2">
                    <div class="p-6 border-b border-slate-100">
                        <h3 class="text-lg font-bold text-slate-800">Paket Internet</h3>
                        <p class="text-xs text-slate-500 mt-1">Kelola daftar pilihan paket internet untuk pelanggan.</p>
                    </div>
                    
                    <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                        <form @submit.prevent="submitIp" class="flex gap-3 items-start">
                            <div class="flex-1 grid grid-cols-1 md:grid-cols-3 gap-3">
                                <div>
                                    <input 
                                        type="text" 
                                        v-model="ipForm.name" 
                                        placeholder="Nama Paket (contoh: 10 Mbps)"
                                        required
                                        class="w-full rounded-xl border-slate-300 text-xs sm:text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                                    />
                                    <p v-if="ipForm.errors.name" class="mt-1 text-xs text-rose-600">{{ ipForm.errors.name }}</p>
                                </div>
                                <div>
                                    <input 
                                        type="number" 
                                        v-model="ipForm.price" 
                                        placeholder="Harga (contoh: 150000)"
                                        required
                                        min="0"
                                        class="w-full rounded-xl border-slate-300 text-xs sm:text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                                    />
                                    <p v-if="ipForm.errors.price" class="mt-1 text-xs text-rose-600">{{ ipForm.errors.price }}</p>
                                </div>
                                <div>
                                    <input 
                                        type="number" 
                                        v-model="ipForm.installation_fee" 
                                        placeholder="Biaya Pasang (opsional)"
                                        class="w-full rounded-xl border-slate-300 text-xs sm:text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                                    />
                                    <p v-if="ipForm.errors.installation_fee" class="mt-1 text-xs text-rose-600">{{ ipForm.errors.installation_fee }}</p>
                                </div>
                            </div>
                            <button 
                                type="submit" 
                                :disabled="ipForm.processing"
                                class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold shadow-sm transition-colors disabled:opacity-50"
                            >
                                Tambah
                            </button>
                        </form>
                    </div>

                    <div class="p-0">
                        <div class="overflow-x-auto w-full pb-4">
<table class="w-full text-left text-xs sm:text-sm">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold uppercase text-slate-500">
                                    <th class="px-3 py-2 sm:px-6 sm:py-3">Nama Paket</th>
                                    <th class="px-3 py-2 sm:px-6 sm:py-3">Harga</th>
                                    <th class="px-3 py-2 sm:px-6 sm:py-3 w-24 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="pkg in internetPackages" :key="pkg.id" class="hover:bg-slate-50/80">
                                    <template v-if="editingIpId === pkg.id">
                                        <td class="px-3 py-2 sm:px-6 sm:py-3">
                                            <input type="text" v-model="editIpForm.name" class="w-full rounded-lg border-slate-300 text-xs sm:text-sm py-1.5 focus:ring-indigo-500 focus:border-indigo-500" required>
                                            <p v-if="editIpForm.errors.name" class="mt-1 text-[10px] text-rose-600">{{ editIpForm.errors.name }}</p>
                                        </td>
                                        <td class="px-3 py-2 sm:px-6 sm:py-3">
                                            <input type="number" v-model="editIpForm.price" class="w-full rounded-lg border-slate-300 text-xs sm:text-sm py-1.5 focus:ring-indigo-500 focus:border-indigo-500" required>
                                            <input type="number" v-model="editIpForm.installation_fee" placeholder="Biaya pasang" class="w-full rounded-lg border-slate-300 text-xs sm:text-sm py-1.5 focus:ring-indigo-500 focus:border-indigo-500 mt-1">
                                            <p v-if="editIpForm.errors.price" class="mt-1 text-[10px] text-rose-600">{{ editIpForm.errors.price }}</p>
                                            <p v-if="editIpForm.errors.installation_fee" class="mt-1 text-[10px] text-rose-600">{{ editIpForm.errors.installation_fee }}</p>
                                        </td>
                                        <td class="px-3 py-2 sm:px-6 sm:py-3 text-right whitespace-nowrap">
                                            <button 
                                                @click="saveEditIp(pkg)"
                                                :disabled="editIpForm.processing"
                                                class="text-indigo-600 hover:text-indigo-800 text-xs font-semibold mr-3 disabled:opacity-50"
                                            >
                                                Simpan
                                            </button>
                                            <button 
                                                @click="cancelEditIp"
                                                class="text-slate-500 hover:text-slate-700 text-xs"
                                            >
                                                Batal
                                            </button>
                                        </td>
                                    </template>
                                    <template v-else>
                                        <td class="px-3 py-2 sm:px-6 sm:py-3 font-medium text-slate-700">
                                            {{ pkg.name }}
                                        </td>
                                        <td class="px-3 py-2 sm:px-6 sm:py-3">
                                            <div class="font-semibold text-slate-800">{{ formatRupiah(pkg.price) }}</div>
                                            <div v-if="pkg.installation_fee !== null" class="text-xs text-slate-500">Pasang: {{ formatRupiah(pkg.installation_fee) }}</div>
                                        </td>
                                        <td class="px-3 py-2 sm:px-6 sm:py-3 text-right whitespace-nowrap">
                                            <button 
                                                @click="startEditIp(pkg)"
                                                class="text-amber-500 hover:text-amber-700 hover:bg-amber-50 p-1.5 rounded-lg transition-colors mr-1"
                                                title="Edit Paket"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                </svg>
                                            </button>
                                            <button 
                                                @click="deleteIp(pkg)"
                                                class="text-rose-500 hover:text-rose-700 hover:bg-rose-50 p-1.5 rounded-lg transition-colors"
                                                title="Hapus Paket"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </td>
                                    </template>
                                </tr>
                                <tr v-if="internetPackages.length === 0">
                                    <td colspan="3" class="px-6 py-8 text-center text-slate-400">
                                        Belum ada data paket internet.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
</div>
                    </div>
                </div>

                <!-- Master Area Section -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 overflow-hidden md:col-span-2 mt-8">
                    <div class="p-6 border-b border-slate-100">
                        <h3 class="text-lg font-bold text-slate-800">Daftar Area</h3>
                        <p class="text-xs text-slate-500 mt-1">Kelola daftar area pelanggan Anda.</p>
                    </div>
                    
                    <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                        <form @submit.prevent="submitArea" class="flex gap-3 items-start">
                            <div class="flex-1 grid grid-cols-1 md:grid-cols-3 gap-3">
                                <div>
                                    <input 
                                        type="text" 
                                        v-model="areaForm.name" 
                                        placeholder="Nama Area (contoh: Komplek A)"
                                        required
                                        class="w-full rounded-xl border-slate-300 text-xs sm:text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                                    />
                                    <p v-if="areaForm.errors.name" class="mt-1 text-xs text-rose-600">{{ areaForm.errors.name }}</p>
                                </div>
                                <div>
                                    <input 
                                        type="text" 
                                        v-model="areaForm.description" 
                                        placeholder="Keterangan (opsional)"
                                        class="w-full rounded-xl border-slate-300 text-xs sm:text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                                    />
                                    <p v-if="areaForm.errors.description" class="mt-1 text-xs text-rose-600">{{ areaForm.errors.description }}</p>
                                </div>
                                <div>
                                    <input 
                                        type="number" 
                                        v-model="areaForm.installation_fee" 
                                        placeholder="Biaya Pasang (opsional)"
                                        class="w-full rounded-xl border-slate-300 text-xs sm:text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                                    />
                                    <p v-if="areaForm.errors.installation_fee" class="mt-1 text-xs text-rose-600">{{ areaForm.errors.installation_fee }}</p>
                                </div>
                            </div>
                            <button 
                                type="submit" 
                                :disabled="areaForm.processing"
                                class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold shadow-sm transition-colors disabled:opacity-50"
                            >
                                Tambah
                            </button>
                        </form>
                    </div>

                    <div class="p-0">
                        <div class="overflow-x-auto w-full pb-4">
<table class="w-full text-left text-xs sm:text-sm">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold uppercase text-slate-500">
                                    <th class="px-3 py-2 sm:px-6 sm:py-3">Nama Area</th>
                                    <th class="px-3 py-2 sm:px-6 sm:py-3">Keterangan</th>
                                    <th class="px-3 py-2 sm:px-6 sm:py-3 w-24 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="area in areas" :key="area.id" class="hover:bg-slate-50/80">
                                    <template v-if="editingAreaId === area.id">
                                        <td class="px-3 py-2 sm:px-6 sm:py-3">
                                            <input type="text" v-model="editAreaForm.name" class="w-full rounded-md border-slate-300 text-xs sm:text-sm focus:border-indigo-500 focus:ring-indigo-500 py-1 px-2" required>
                                            <p v-if="editAreaForm.errors.name" class="mt-1 text-[10px] text-rose-600">{{ editAreaForm.errors.name }}</p>
                                        </td>
                                        <td class="px-3 py-2 sm:px-6 sm:py-3">
                                            <input type="text" v-model="editAreaForm.description" class="w-full rounded-md border-slate-300 text-xs sm:text-sm focus:border-indigo-500 focus:ring-indigo-500 py-1 px-2">
                                            <input type="number" v-model="editAreaForm.installation_fee" placeholder="Biaya pasang" class="w-full rounded-md border-slate-300 text-xs sm:text-sm focus:border-indigo-500 focus:ring-indigo-500 py-1 px-2 mt-1">
                                            <p v-if="editAreaForm.errors.description" class="mt-1 text-[10px] text-rose-600">{{ editAreaForm.errors.description }}</p>
                                            <p v-if="editAreaForm.errors.installation_fee" class="mt-1 text-[10px] text-rose-600">{{ editAreaForm.errors.installation_fee }}</p>
                                        </td>
                                        <td class="px-3 py-2 sm:px-6 sm:py-3 text-right whitespace-nowrap">
                                            <button 
                                                @click="saveEditArea(area)"
                                                :disabled="editAreaForm.processing"
                                                class="text-indigo-600 hover:text-indigo-800 text-xs font-semibold mr-3 disabled:opacity-50"
                                            >
                                                Simpan
                                            </button>
                                            <button 
                                                @click="cancelEditArea"
                                                class="text-slate-500 hover:text-slate-700 text-xs"
                                            >
                                                Batal
                                            </button>
                                        </td>
                                    </template>
                                    <template v-else>
                                        <td class="px-3 py-2 sm:px-6 sm:py-3 font-medium text-slate-700">
                                            {{ area.name }}
                                        </td>
                                        <td class="px-3 py-2 sm:px-6 sm:py-3">
                                            <div class="text-xs sm:text-sm text-slate-600">{{ area.description || '-' }}</div>
                                            <div v-if="area.installation_fee !== null" class="text-xs text-slate-500 mt-0.5">Pasang: {{ formatRupiah(area.installation_fee) }}</div>
                                        </td>
                                        <td class="px-3 py-2 sm:px-6 sm:py-3 text-right whitespace-nowrap">
                                            <button 
                                                @click="startEditArea(area)"
                                                class="text-amber-500 hover:text-amber-700 hover:bg-amber-50 p-1.5 rounded-lg transition-colors mr-1"
                                                title="Edit Area"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                </svg>
                                            </button>
                                            <button 
                                                @click="deleteArea(area)"
                                                class="text-rose-500 hover:text-rose-700 hover:bg-rose-50 p-1.5 rounded-lg transition-colors"
                                                title="Hapus Area"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </td>
                                    </template>
                                </tr>
                                <tr v-if="areas.length === 0">
                                    <td colspan="3" class="px-6 py-8 text-center text-slate-400">
                                        Belum ada data area.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
</div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
