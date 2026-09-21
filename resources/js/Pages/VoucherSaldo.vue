<script setup>
import { ref, computed, watch } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import * as XLSX from 'xlsx';

const props = defineProps({
    transactions: {
        type: Array,
        default: () => [],
    },
    paymentMethods: {
        type: Array,
        default: () => [],
    },
    resellers: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    areas: {
        type: Array,
        default: () => [],
    }
});

// Format Currency
const formatRupiah = (number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(number || 0);
};

// Format Date
const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    try {
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        return new Intl.DateTimeFormat('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric'
        }).format(d);
    } catch {
        return dateStr;
    }
};

// TABS
const activeTab = ref('reseller'); // 'reseller' or 'riwayat'

// --- RESELLER TAB LOGIC ---
const searchReseller = ref('');
const filteredResellers = computed(() => {
    if (!searchReseller.value) return props.resellers;
    const lower = searchReseller.value.toLowerCase();
    return props.resellers.filter(r => 
        (r.name || '').toLowerCase().includes(lower) ||
        (r.area || '').toLowerCase().includes(lower) ||
        (r.alamat || '').toLowerCase().includes(lower) ||
        (r.phone || '').toLowerCase().includes(lower)
    );
});

// Excel Import for Reseller
const fileInput = ref(null);
const selectedFileName = ref('');
const parsedCount = ref(0);
const parseError = ref('');
const isDragging = ref(false);

const importForm = useForm({
    resellersData: [],
});

const handleFile = (file) => {
    if (!file) return;

    const ext = file.name.split('.').pop().toLowerCase();
    if (!['xlsx', 'xls', 'csv'].includes(ext)) {
        alert('Format file tidak didukung. Harap unggah file .xlsx, .xls, atau .csv');
        return;
    }

    selectedFileName.value = file.name;
    parseError.value = '';

    const reader = new FileReader();
    reader.onload = (e) => {
        try {
            const data = new Uint8Array(e.target.result);
            const workbook = XLSX.read(data, { type: 'array' });
            const sheetName = workbook.SheetNames[0];
            const worksheet = workbook.Sheets[sheetName];
            const jsonData = XLSX.utils.sheet_to_json(worksheet, { defval: '', raw: false });

            if (!jsonData || jsonData.length === 0) {
                parseError.value = 'File Excel tidak memiliki baris data.';
                importForm.resellersData = [];
                parsedCount.value = 0;
                return;
            }

            const mappedData = jsonData.map((row) => {
                const keys = Object.keys(row);
                
                const nameKey = keys.find((k) => /^(nama|name|pelanggan|reseller)/i.test(k.trim()));
                const areaKey = keys.find((k) => /^(area|wilayah|cabang)/i.test(k.trim()));
                const alamatKey = keys.find((k) => /^(alamat|address)/i.test(k.trim()));
                const phoneKey = keys.find((k) => /^(telp|telepon|phone|hp|no)/i.test(k.trim()));

                return {
                    name: nameKey ? String(row[nameKey]).trim() : '',
                    area: areaKey ? String(row[areaKey]).trim() : '',
                    alamat: alamatKey ? String(row[alamatKey]).trim() : '',
                    phone: phoneKey ? String(row[phoneKey]).trim() : ''
                };
            }).filter((item) => item.name.length > 0);

            if (mappedData.length === 0) {
                parseError.value = 'Tidak ditemukan kolom nama yang valid.';
                importForm.resellersData = [];
                parsedCount.value = 0;
                return;
            }

            importForm.resellersData = mappedData;
            parsedCount.value = mappedData.length;
        } catch (error) {
            console.error('Error parsing excel:', error);
            parseError.value = 'Gagal membaca file Excel. Pastikan struktur file valid.';
        }
    };
    reader.readAsArrayBuffer(file);
};

const onFileInputChange = (event) => {
    const file = event.target.files[0];
    if (file) handleFile(file);
};

const triggerFileInput = () => {
    if (fileInput.value) fileInput.value.click();
};

const removeSelectedFile = () => {
    selectedFileName.value = '';
    parsedCount.value = 0;
    parseError.value = '';
    importForm.resellersData = [];
    if (fileInput.value) fileInput.value.value = '';
};

const submitImport = () => {
    if (!importForm.resellersData || importForm.resellersData.length === 0) {
        alert('Mohon pilih file Excel yang memiliki data Reseller.');
        return;
    }

    importForm.post(route('reseller.import'), {
        preserveScroll: true,
        onSuccess: () => {
            removeSelectedFile();
        },
    });
};

const deleteReseller = (id) => {
    if (confirm('Yakin ingin menghapus reseller ini?')) {
        router.delete(route('reseller.destroy', id), { preserveScroll: true });
    }
};

// Edit / Add Reseller
const isResellerModalOpen = ref(false);
const resellerForm = useForm({
    id: null,
    name: '',
    area: '',
    alamat: '',
    phone: '',
});

const openAddResellerModal = () => {
    resellerForm.reset();
    resellerForm.id = null;
    isResellerModalOpen.value = true;
};

const openEditResellerModal = (r) => {
    resellerForm.id = r.id;
    resellerForm.name = r.name;
    resellerForm.area = r.area;
    resellerForm.alamat = r.alamat;
    resellerForm.phone = r.phone;
    isResellerModalOpen.value = true;
};

const closeResellerModal = () => {
    isResellerModalOpen.value = false;
};

const submitReseller = () => {
    if (resellerForm.id) {
        resellerForm.put(route('reseller.update', resellerForm.id), {
            preserveScroll: true,
            onSuccess: () => closeResellerModal()
        });
    } else {
        resellerForm.post(route('reseller.store'), {
            preserveScroll: true,
            onSuccess: () => closeResellerModal()
        });
    }
};

// --- TRANSACTION LOGIC ---
const filterForm = useForm({
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
    status: props.filters.status || 'Semua',
    area: props.filters.area || 'Semua',
});

const applyFilters = () => {
    filterForm.get(route('voucher-saldo.index'), {
        preserveState: true,
        preserveScroll: true,
    });
};

const exportExcel = () => {
    const data = filteredTransactions.value.map((t, index) => ({
        'No': index + 1,
        'Tanggal': formatDate(t.date),
        'Reseller': t.reseller ? t.reseller.name : '-',
        'Area': t.area || (t.reseller ? t.reseller.area : '-'),
        'Keterangan': t.description,
        'Jenis': t.income_source,
        'Metode Bayar': t.payment_method || '-',
        'Mode': t.transaction_mode,
        'Nominal': (t.transaction_mode === 'Piutang' && t.payment_status === 'unpaid') 
                   ? (Number(t.amount) - Number(t.children_sum_amount || 0)) 
                   : Number(t.amount),
        'Status': t.payment_status === 'paid' ? 'Lunas' : 'Piutang',
    }));
    
    const ws = XLSX.utils.json_to_sheet(data);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "Rekap Transaksi");
    XLSX.writeFile(wb, `Rekap_Voucher_Saldo_${filterForm.start_date}_sd_${filterForm.end_date}.xlsx`);
};

const exportPDF = () => {
    window.print();
};

// Add Transaction Modal
const isModalOpen = ref(false);
const transactionForm = useForm({
    income_source: 'voucher',
    amount: '',
    description: '',
    date: new Date().toISOString().split('T')[0],
    payment_method: 'Tunai',
    transaction_mode: 'Tunai',
    reseller_id: '',
    area: '',
});

// Used from both tabs
const openModal = (defaultResellerId = '') => {
    transactionForm.reset();
    transactionForm.date = new Date().toISOString().split('T')[0];
    transactionForm.reseller_id = defaultResellerId;
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
};

const submitTransaction = () => {
    transactionForm.post(route('voucher-saldo.store'), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            activeTab.value = 'riwayat';
        },
    });
};

const deleteTransaction = (id) => {
    if (confirm('Apakah Anda yakin ingin menghapus transaksi ini?')) {
        router.delete(route('voucher-saldo.destroy', id), {
            preserveScroll: true,
        });
    }
};

const batalLunas = (id) => {
    if (confirm('Apakah Anda yakin ingin membatalkan pelunasan piutang ini? Semua catatan pembayaran untuk piutang ini akan dihapus.')) {
        router.post(route('voucher-saldo.batal-lunas', id), {}, {
            preserveScroll: true,
        });
    }
};

const isLunasModalOpen = ref(false);
const selectedPiutang = ref(null);

const lunasForm = useForm({
    amount: '',
    date: new Date().toISOString().slice(0, 10),
    payment_method: 'Tunai',
    collector: '',
    proof: null,
});

const openLunasModal = (piutang) => {
    selectedPiutang.value = piutang;
    
    const paidAmount = Number(piutang.children_sum_amount || 0);
        
    const remaining = Number(piutang.amount) - paidAmount;
    
    lunasForm.amount = remaining > 0 ? remaining : 0;
    lunasForm.date = new Date().toISOString().slice(0, 10);
    lunasForm.payment_method = 'Tunai';
    lunasForm.collector = '';
    lunasForm.proof = null;
    isLunasModalOpen.value = true;
};

const closeLunasModal = () => {
    isLunasModalOpen.value = false;
    selectedPiutang.value = null;
};

const submitLunas = () => {
    lunasForm.post(route('voucher-saldo.lunas', selectedPiutang.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeLunasModal();
        },
    });
};

const isDetailModalOpen = ref(false);
const selectedDetail = ref(null);

const openDetailModal = (transaction) => {
    selectedDetail.value = transaction;
    isDetailModalOpen.value = true;
};

const closeDetailModal = () => {
    isDetailModalOpen.value = false;
    selectedDetail.value = null;
};
const searchQuery = ref('');
const filteredTransactions = computed(() => {
    if (!searchQuery.value) return props.transactions;
    const lowerQuery = searchQuery.value.toLowerCase();
    return props.transactions.filter(t => 
        (t.description || '').toLowerCase().includes(lowerQuery) ||
        (t.payment_method || '').toLowerCase().includes(lowerQuery) ||
        (t.reseller?.name || '').toLowerCase().includes(lowerQuery)
    );
});

const getRemainingPiutang = (piutang) => {
    const paidAmount = Number(piutang.children_sum_amount || 0);
    return Number(piutang.amount) - paidAmount;
};

const getDaysOverdue = (dateStr) => {
    if (!dateStr) return 0;
    const today = new Date();
    today.setHours(0,0,0,0);
    const d = new Date(dateStr);
    d.setHours(0,0,0,0);
    const diffTime = today - d;
    if (diffTime <= 0) return 0;
    return Math.floor(diffTime / (1000 * 60 * 60 * 24));
};

const totalVoucher = computed(() => props.transactions.filter(t => t.income_source === 'voucher' && t.transaction_mode !== 'Piutang').reduce((sum, t) => sum + Number(t.amount), 0));
const totalSaldo = computed(() => props.transactions.filter(t => t.income_source === 'saldo' && t.transaction_mode !== 'Piutang').reduce((sum, t) => sum + Number(t.amount), 0));
const totalPiutang = computed(() => props.transactions.filter(t => t.payment_status === 'unpaid' && t.transaction_mode === 'Piutang').reduce((sum, t) => sum + getRemainingPiutang(t), 0));

const totalFilteredNominal = computed(() => {
    return filteredTransactions.value.reduce((sum, t) => {
        let nominal = Number(t.amount);
        if (t.transaction_mode === 'Piutang' && t.payment_status === 'unpaid') {
            nominal = nominal - Number(t.children_sum_amount || 0);
        }
        return sum + nominal;
    }, 0);
});

// --- PAGINATION LOGIC ---
const itemsPerPage = ref(10);

const currentPageResellers = ref(1);
const totalPagesResellers = computed(() => Math.ceil(filteredResellers.value.length / itemsPerPage.value));
const paginatedResellers = computed(() => {
    const start = (currentPageResellers.value - 1) * itemsPerPage.value;
    return filteredResellers.value.slice(start, start + itemsPerPage.value);
});

const currentPageTransactions = ref(1);
const totalPagesTransactions = computed(() => Math.ceil(filteredTransactions.value.length / itemsPerPage.value));
const paginatedTransactions = computed(() => {
    const start = (currentPageTransactions.value - 1) * itemsPerPage.value;
    return filteredTransactions.value.slice(start, start + itemsPerPage.value);
});

// Reset page when search or filters change
watch(searchReseller, () => currentPageResellers.value = 1);
watch([searchQuery, () => props.filters], () => currentPageTransactions.value = 1, { deep: true });
</script>

<template>
    <Head title="Voucher & Saldo" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-xl font-bold tracking-tight text-slate-800">
                        Voucher & Saldo
                    </h2>
                    <p class="mt-1 text-xs sm:text-sm text-slate-500">
                        Kelola data reseller, penjualan voucher, dan pengisian saldo.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        @click="openModal('')"
                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-xs sm:text-sm font-semibold text-white shadow-sm transition-all duration-200 hover:bg-indigo-700 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Tambah Penjualan Baru
                    </button>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- Tabs Navigation -->
                <div class="flex border-b border-slate-200 print:hidden">
                    <button 
                        @click="activeTab = 'reseller'" 
                        :class="[
                            'py-3 px-6 text-xs sm:text-sm font-medium border-b-2 transition-colors duration-200 focus:outline-none',
                            activeTab === 'reseller' ? 'border-indigo-500 text-indigo-600 bg-indigo-50/50' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'
                        ]"
                    >
                        Daftar Reseller
                    </button>
                    <button 
                        @click="activeTab = 'riwayat'" 
                        :class="[
                            'py-3 px-6 text-xs sm:text-sm font-medium border-b-2 transition-colors duration-200 focus:outline-none',
                            activeTab === 'riwayat' ? 'border-indigo-500 text-indigo-600 bg-indigo-50/50' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'
                        ]"
                    >
                        Riwayat Transaksi
                    </button>
                </div>

                <!-- TAB: RESELLER -->
                <div v-show="activeTab === 'reseller'" class="space-y-6">
                    <!-- Excel Import Area -->
                    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200/60">
                        <div class="flex flex-col sm:flex-row gap-4 items-center justify-between">
                            <div class="flex-1">
                                <h3 class="text-base font-semibold text-slate-900">Import Reseller dari Excel</h3>
                                <p class="mt-1 text-xs sm:text-sm text-slate-500">Unggah file Excel (.xlsx, .xls) berisi data Reseller. Kolom akan dideteksi otomatis (Nama, Area, Alamat, Telp).</p>
                            </div>
                            
                            <div class="flex items-center gap-3 w-full sm:w-auto">
                                <input type="file" ref="fileInput" class="hidden" accept=".xlsx, .xls, .csv" @change="onFileInputChange" />
                                <div class="flex gap-2 w-full sm:w-auto">
                                    <button 
                                        type="button" 
                                        @click="triggerFileInput"
                                        class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 rounded-xl bg-white px-4 py-2.5 text-xs sm:text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 transition-all duration-200"
                                    >
                                        <svg class="h-5 w-5 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                        </svg>
                                        Pilih File
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Import Preview Area -->
                        <div v-if="selectedFileName" class="mt-6 rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="flex items-center justify-between flex-wrap gap-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-100 text-indigo-600">
                                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-xs sm:text-sm font-medium text-slate-900">{{ selectedFileName }}</p>
                                        <p class="text-xs text-slate-500">
                                            <span v-if="parsedCount > 0" class="text-emerald-600 font-medium">{{ parsedCount }} data ditemukan</span>
                                            <span v-else-if="parseError" class="text-rose-500">{{ parseError }}</span>
                                            <span v-else>Memproses...</span>
                                        </p>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <button @click="removeSelectedFile" class="rounded-lg px-3 py-2 text-xs sm:text-sm font-semibold text-rose-600 hover:bg-rose-50 transition-colors duration-200">
                                        Batal
                                    </button>
                                    <button v-if="parsedCount > 0" @click="submitImport" :disabled="importForm.processing" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-xs sm:text-sm font-semibold text-white hover:bg-indigo-500 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                                        <svg v-if="importForm.processing" class="animate-spin -ml-1 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                        Import Sekarang
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Reseller List & Search -->
                    <div class="flex flex-col gap-4 sm:flex-row justify-between">
                        <div class="flex items-center gap-3">
                            <button @click="openAddResellerModal" class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-xs sm:text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 transition-all duration-200">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Tambah Manual
                            </button>
                        </div>
                        <div class="relative rounded-md shadow-sm">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <svg class="h-5 w-5 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <input
                                type="text"
                                v-model="searchReseller"
                                placeholder="Cari reseller..."
                                class="block w-full sm:w-64 rounded-xl border-0 py-2.5 pl-10 text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                            />
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/60">
                        <div class="overflow-x-auto">
                            <div class="overflow-x-auto w-full pb-4">
<table class="min-w-full divide-y divide-slate-200">
                                <thead>
                                    <tr class="bg-slate-50/50">
                                        <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Nama Reseller</th>
                                        <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Area</th>
                                        <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Telepon</th>
                                        <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Alamat</th>
                                        <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Sisa Piutang</th>
                                        <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 bg-white">
                                    <tr v-for="r in paginatedResellers" :key="r.id" class="transition-colors hover:bg-slate-50">
                                        <td class="whitespace-nowrap px-3 py-4 text-xs sm:text-sm font-semibold text-slate-900 border-b border-slate-100">
                                            {{ r.name }}
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-xs sm:text-sm text-slate-600 border-b border-slate-100">
                                            {{ r.phone || '-' }}
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-xs sm:text-sm text-slate-600 border-b border-slate-100 max-w-xs truncate" :title="r.alamat">
                                            {{ r.alamat || '-' }}
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-xs sm:text-sm text-slate-600 border-b border-slate-100">
                                            {{ r.area || '-' }}
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-xs sm:text-sm font-semibold text-slate-900 border-b border-slate-100">
                                            {{ formatRupiah(r.total_piutang) }}
                                        </td>
                                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-center text-xs sm:text-sm font-medium border-b border-slate-100">
                                            <div class="flex items-center justify-center gap-2">
                                                <button @click="openEditResellerModal(r)" type="button" title="Edit Reseller" class="p-1.5 text-blue-500 hover:text-white hover:bg-blue-500 bg-blue-50 rounded-lg transition-colors border border-blue-100">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                      <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125" />
                                                    </svg>
                                                </button>
                                                <button @click="deleteReseller(r.id)" type="button" title="Hapus Reseller" class="p-1.5 text-red-500 hover:text-white hover:bg-red-500 bg-red-50 rounded-lg transition-colors border border-red-100">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                      <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="filteredResellers.length === 0">
                                        <td colspan="6" class="px-6 py-8 text-center text-xs sm:text-sm text-slate-500">
                                            Tidak ada data reseller.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
</div>
                            
                            <!-- Pagination Controls for Reseller -->
                            <div class="px-3 py-3 sm:px-6 sm:py-4 flex items-center justify-between border-t border-slate-200 bg-white">
                                <div class="text-xs sm:text-sm text-slate-500">
                                    Menampilkan <span class="font-medium text-slate-900">{{ (currentPageResellers - 1) * itemsPerPage + 1 }}</span> - 
                                    <span class="font-medium text-slate-900">{{ Math.min(currentPageResellers * itemsPerPage, filteredResellers.length) }}</span> 
                                    dari <span class="font-medium text-slate-900">{{ filteredResellers.length }}</span> data
                                </div>
                                <div class="flex items-center gap-1">
                                    <button 
                                        @click="currentPageResellers--" 
                                        :disabled="currentPageResellers === 1"
                                        class="px-3 py-1.5 border border-slate-200 rounded-lg text-slate-500 bg-white hover:bg-slate-50 hover:text-slate-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium">
                                        &lt;
                                    </button>
                                    <button class="px-3 py-1.5 rounded-lg bg-indigo-600 text-white font-medium shadow-sm">{{ currentPageResellers }}</button>
                                    <button 
                                        @click="currentPageResellers++" 
                                        :disabled="currentPageResellers === totalPagesResellers"
                                        class="px-3 py-1.5 border border-slate-200 rounded-lg text-slate-500 bg-white hover:bg-slate-50 hover:text-slate-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium">
                                        &gt;
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB: RIWAYAT TRANSAKSI -->
                <div v-show="activeTab === 'riwayat'" class="space-y-6">
                    <!-- Print Header -->
                    <div class="hidden print:block mb-8 text-center border-b-2 border-slate-800 pb-4">
                        <h1 class="text-2xl font-bold text-slate-900 uppercase tracking-widest">Laporan Keuangan</h1>
                        <h2 class="text-lg font-semibold text-slate-700 mt-1">Riwayat Transaksi Voucher & Saldo</h2>
                        <p class="text-xs sm:text-sm text-slate-500 mt-2">
                            Periode: 
                            <span class="font-medium text-slate-800">{{ formatDate(filterForm.start_date) }}</span> 
                            s/d 
                            <span class="font-medium text-slate-800">{{ formatDate(filterForm.end_date) }}</span>
                        </p>
                    </div>

                    <!-- Stats -->
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-3 print:hidden">
                        <div class="relative overflow-hidden rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200/60 transition-all hover:shadow-md">
                            <dt>
                                <div class="absolute rounded-xl bg-indigo-50 p-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                                    </svg>
                                </div>
                                <p class="ml-16 truncate text-xs sm:text-sm font-medium text-slate-500">Pemasukan Voucher</p>
                            </dt>
                            <dd class="ml-16 flex items-baseline pb-1 sm:pb-2">
                                <p class="text-2xl font-bold text-slate-900">{{ formatRupiah(totalVoucher) }}</p>
                            </dd>
                        </div>

                        <div class="relative overflow-hidden rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200/60 transition-all hover:shadow-md">
                            <dt>
                                <div class="absolute rounded-xl bg-emerald-50 p-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <p class="ml-16 truncate text-xs sm:text-sm font-medium text-slate-500">Pemasukan Saldo</p>
                            </dt>
                            <dd class="ml-16 flex items-baseline pb-1 sm:pb-2">
                                <p class="text-2xl font-bold text-slate-900">{{ formatRupiah(totalSaldo) }}</p>
                            </dd>
                        </div>

                        <div class="relative overflow-hidden rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200/60 transition-all hover:shadow-md">
                            <dt>
                                <div class="absolute rounded-xl bg-amber-50 p-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <p class="ml-16 truncate text-xs sm:text-sm font-medium text-slate-500">Total Piutang</p>
                            </dt>
                            <dd class="ml-16 flex items-baseline pb-1 sm:pb-2">
                                <p class="text-2xl font-bold text-slate-900">{{ formatRupiah(totalPiutang) }}</p>
                            </dd>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-end justify-between bg-white p-4 rounded-2xl shadow-sm ring-1 ring-slate-200/60 print:hidden">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
                            <div>
                                <label for="start_date" class="block text-xs font-medium text-slate-700">Tanggal Mulai</label>
                                <input
                                    type="date"
                                    id="start_date"
                                    v-model="filterForm.start_date"
                                    class="mt-1 block w-full rounded-lg border-0 py-2 pl-3 pr-10 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                />
                            </div>
                            <div>
                                <label for="end_date" class="block text-xs font-medium text-slate-700">Tanggal Akhir</label>
                                <input
                                    type="date"
                                    id="end_date"
                                    v-model="filterForm.end_date"
                                    class="mt-1 block w-full rounded-lg border-0 py-2 pl-3 pr-10 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                />
                            </div>
                            <div>
                                <label for="status" class="block text-xs font-medium text-slate-700">Status</label>
                                <select
                                    id="status"
                                    v-model="filterForm.status"
                                    class="mt-1 block w-full rounded-lg border-0 py-2 pl-3 pr-10 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                >
                                    <option value="Semua">Semua</option>
                                    <option value="Lunas">Lunas</option>
                                    <option value="Piutang">Piutang</option>
                                </select>
                            </div>
                            <div>
                                <label for="area" class="block text-xs font-medium text-slate-700">Area</label>
                                <select
                                    id="area"
                                    v-model="filterForm.area"
                                    class="mt-1 block w-full rounded-lg border-0 py-2 pl-3 pr-10 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                >
                                    <option value="Semua">Semua Area</option>
                                    <option v-for="area in areas" :key="area" :value="area">{{ area }}</option>
                                </select>
                            </div>
                            <div class="flex flex-wrap items-center gap-2 mt-2 sm:mt-0 w-full sm:w-auto">
                                <button
                                    @click="applyFilters"
                                    class="inline-flex items-center gap-2 rounded-lg bg-slate-800 px-4 py-2 text-xs sm:text-sm font-semibold text-white shadow-sm hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2"
                                >
                                    Terapkan Filter
                                </button>
                                <button
                                    @click="exportExcel"
                                    class="inline-flex items-center gap-2 rounded-lg bg-emerald-50 px-4 py-2 text-xs sm:text-sm font-semibold text-emerald-700 shadow-sm ring-1 ring-inset ring-emerald-600/20 hover:bg-emerald-100 focus:outline-none transition-colors"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                    </svg>
                                    Export Excel
                                </button>
                                <button
                                    @click="exportPDF"
                                    class="inline-flex items-center gap-2 rounded-lg bg-rose-50 px-4 py-2 text-xs sm:text-sm font-semibold text-rose-700 shadow-sm ring-1 ring-inset ring-rose-600/20 hover:bg-rose-100 focus:outline-none transition-colors"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z" clip-rule="evenodd" />
                                    </svg>
                                    Cetak PDF
                                </button>
                            </div>
                        </div>
                        <div class="print:hidden">
                            <div class="relative rounded-md shadow-sm">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <svg class="h-5 w-5 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <input
                                    type="text"
                                    v-model="searchQuery"
                                    placeholder="Cari transaksi..."
                                    class="block w-full sm:w-64 rounded-xl border-0 py-2.5 pl-10 text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200/60">
                        <div class="overflow-x-auto">
                            <div class="overflow-x-auto w-full pb-4">
<table class="min-w-full divide-y divide-slate-200">
                                <thead>
                                    <tr class="bg-slate-50/50">
                                        <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Tanggal</th>
                                        <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Reseller</th>
                                        <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Area</th>
                                        <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Jenis</th>
                                        <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Keterangan</th>
                                        <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Metode</th>
                                        <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Mode</th>
                                        <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Nominal</th>
                                        <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6 text-center text-xs font-semibold text-slate-500 uppercase tracking-wider print:hidden">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 bg-white">
                                    <tr v-for="t in paginatedTransactions" :key="t.id" class="transition-colors hover:bg-slate-50">
                                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-xs sm:text-sm text-slate-900 font-medium">
                                            {{ formatDate(t.date) }}
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-xs sm:text-sm font-semibold text-slate-900">
                                            {{ t.reseller ? t.reseller.name : '-' }}
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-xs sm:text-sm text-slate-600">
                                            {{ t.area || (t.reseller ? t.reseller.area : '-') }}
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-xs sm:text-sm">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                                :class="t.income_source === 'voucher' ? 'bg-indigo-50 text-indigo-700 ring-1 ring-inset ring-indigo-200' : 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-200'">
                                                {{ t.income_source === 'voucher' ? 'Voucher' : 'Saldo' }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-4 text-xs sm:text-sm text-slate-600 max-w-sm truncate" :title="t.description">
                                            {{ t.description }}
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-xs sm:text-sm text-slate-600">
                                            <span v-if="t.transaction_mode !== 'Piutang'" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-700">
                                                {{ t.payment_method || '-' }}
                                            </span>
                                            <span v-else>-</span>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-xs sm:text-sm">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                                :class="t.transaction_mode === 'Tunai' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'">
                                                {{ t.transaction_mode }}
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-xs sm:text-sm font-bold text-slate-900">
                                            <span v-if="t.transaction_mode === 'Piutang' && t.payment_status === 'unpaid'" class="text-amber-600">
                                                {{ formatRupiah(getRemainingPiutang(t)) }}
                                                <div v-if="getDaysOverdue(t.date) > 0" class="text-xs text-rose-500 font-medium mt-1">
                                                    (Nunggak {{ getDaysOverdue(t.date) }} hari)
                                                </div>
                                            </span>
                                            <span v-else>
                                                {{ formatRupiah(t.amount) }}
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-xs sm:text-sm text-center print:hidden">
                                            <div class="flex items-center justify-center gap-2">
                                                <!-- Only show Set Lunas button for unpaid Piutang that is not a child payment itself -->
                                                <button v-if="t.transaction_mode === 'Piutang' && t.payment_status === 'unpaid' && !t.parent_id"
                                                    @click="openLunasModal(t)"
                                                    type="button" 
                                                    title="Set Lunas"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-emerald-50 text-emerald-600 hover:bg-emerald-500 hover:text-white rounded-lg text-xs font-semibold transition-colors border border-emerald-200">
                                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                                    </svg>
                                                    Set Lunas
                                                </button>
                                                <button v-if="t.transaction_mode === 'Piutang' && (t.payment_status === 'paid' || Number(t.children_sum_amount) > 0) && !t.parent_id"
                                                    @click="batalLunas(t.id)"
                                                    type="button" 
                                                    title="Batalkan Semua Pembayaran"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-rose-50 text-rose-600 hover:bg-rose-500 hover:text-white rounded-lg text-xs font-semibold transition-colors border border-rose-200">
                                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                    Batalkan
                                                </button>
                                                <button @click="openDetailModal(t)" type="button" title="Detail Transaksi" class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                                                    </svg>
                                                </button>
                                                <button v-if="t.parent_id" @click="deleteTransaction(t.id)" type="button" title="Batalkan Pembayaran Ini" class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-rose-50 text-rose-600 hover:bg-rose-500 hover:text-white rounded-lg text-xs font-semibold transition-colors border border-rose-200">
                                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                    Batalkan
                                                </button>
                                                <button v-else @click="deleteTransaction(t.id)" type="button" title="Hapus" class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="filteredTransactions.length === 0">
                                        <td colspan="8" class="px-6 py-8 text-center text-xs sm:text-sm text-slate-500">
                                            Tidak ada riwayat transaksi.
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot class="hidden print:table-footer-group bg-slate-50 border-t-2 border-slate-200">
                                    <tr>
                                        <td colspan="7" class="py-4 pl-4 pr-3 text-right text-xs sm:text-sm font-bold text-slate-900 uppercase tracking-widest">
                                            Total Nominal
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-xs sm:text-sm font-bold text-slate-900">
                                            {{ formatRupiah(totalFilteredNominal) }}
                                        </td>
                                        <td class="print:hidden"></td>
                                    </tr>
                                </tfoot>
                            </table>
</div>
                            
                            <!-- Pagination Controls for Transactions -->
                            <div class="px-3 py-3 sm:px-6 sm:py-4 flex items-center justify-between border-t border-slate-200 bg-white">
                                <div class="text-xs sm:text-sm text-slate-500">
                                    Menampilkan <span class="font-medium text-slate-900">{{ (currentPageTransactions - 1) * itemsPerPage + 1 }}</span> - 
                                    <span class="font-medium text-slate-900">{{ Math.min(currentPageTransactions * itemsPerPage, filteredTransactions.length) }}</span> 
                                    dari <span class="font-medium text-slate-900">{{ filteredTransactions.length }}</span> data
                                </div>
                                <div class="flex items-center gap-1">
                                    <button 
                                        @click="currentPageTransactions--" 
                                        :disabled="currentPageTransactions === 1"
                                        class="px-3 py-1.5 border border-slate-200 rounded-lg text-slate-500 bg-white hover:bg-slate-50 hover:text-slate-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium">
                                        &lt;
                                    </button>
                                    <button class="px-3 py-1.5 rounded-lg bg-indigo-600 text-white font-medium shadow-sm">{{ currentPageTransactions }}</button>
                                    <button 
                                        @click="currentPageTransactions++" 
                                        :disabled="currentPageTransactions === totalPagesTransactions"
                                        class="px-3 py-1.5 border border-slate-200 rounded-lg text-slate-500 bg-white hover:bg-slate-50 hover:text-slate-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium">
                                        &gt;
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Transaction Modal -->
        <div v-if="isModalOpen" class="relative z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity"></div>
            <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                        <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                </div>
                                <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                                    <h3 class="text-lg font-semibold leading-6 text-slate-900" id="modal-title">Tambah Penjualan Baru</h3>
                                    
                                    <div class="mt-4 space-y-4">
                                        <!-- Reseller Select -->
                                        <div>
                                            <label class="block text-xs sm:text-sm font-medium leading-6 text-slate-900">Pilih Reseller (Opsional)</label>
                                            <select
                                                v-model="transactionForm.reseller_id"
                                                class="mt-2 block w-full rounded-xl border-0 py-2.5 pl-3 pr-10 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                            >
                                                <option value="">- Transaksi Umum (Tanpa Reseller) -</option>
                                                <option v-for="r in resellers" :key="r.id" :value="r.id">
                                                    {{ r.name }} {{ r.area ? `(${r.area})` : '' }}
                                                </option>
                                            </select>
                                            <div v-if="transactionForm.errors.reseller_id" class="mt-1 text-xs sm:text-sm text-rose-500">
                                                {{ transactionForm.errors.reseller_id }}
                                            </div>
                                        </div>

                                        <!-- Area Select -->
                                        <div>
                                            <label class="block text-xs sm:text-sm font-medium leading-6 text-slate-900">Area</label>
                                            <select
                                                v-model="transactionForm.area"
                                                class="mt-2 block w-full rounded-xl border-0 py-2.5 pl-3 pr-10 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                            >
                                                <option value="">-- Pilih Area --</option>
                                                <option v-for="area in areas" :key="area" :value="area">{{ area }}</option>
                                            </select>
                                            <div v-if="transactionForm.errors.area" class="mt-1 text-xs sm:text-sm text-rose-500">
                                                {{ transactionForm.errors.area }}
                                            </div>
                                        </div>

                                        <!-- Jenis -->
                                        <div>
                                            <label class="block text-xs sm:text-sm font-medium leading-6 text-slate-900">Jenis Transaksi</label>
                                            <select
                                                v-model="transactionForm.income_source"
                                                class="mt-2 block w-full rounded-xl border-0 py-2.5 pl-3 pr-10 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                            >
                                                <option value="voucher">Penjualan Voucher</option>
                                                <option value="saldo">Pengisian Saldo</option>
                                            </select>
                                            <div v-if="transactionForm.errors.income_source" class="mt-1 text-xs sm:text-sm text-rose-500">
                                                {{ transactionForm.errors.income_source }}
                                            </div>
                                        </div>

                                        <!-- Date -->
                                        <div>
                                            <label class="block text-xs sm:text-sm font-medium leading-6 text-slate-900">Tanggal</label>
                                            <input
                                                type="date"
                                                v-model="transactionForm.date"
                                                class="mt-2 block w-full rounded-xl border-0 py-2.5 text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                            />
                                            <div v-if="transactionForm.errors.date" class="mt-1 text-xs sm:text-sm text-rose-500">
                                                {{ transactionForm.errors.date }}
                                            </div>
                                        </div>

                                        <!-- Description -->
                                        <div>
                                            <label class="block text-xs sm:text-sm font-medium leading-6 text-slate-900">Keterangan</label>
                                            <input
                                                type="text"
                                                v-model="transactionForm.description"
                                                placeholder="Contoh: Voucher 10 Mbps atau Top up"
                                                class="mt-2 block w-full rounded-xl border-0 py-2.5 text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                            />
                                            <div v-if="transactionForm.errors.description" class="mt-1 text-xs sm:text-sm text-rose-500">
                                                {{ transactionForm.errors.description }}
                                            </div>
                                        </div>

                                        <!-- Amount -->
                                        <div>
                                            <label class="block text-xs sm:text-sm font-medium leading-6 text-slate-900">Nominal (Rp)</label>
                                            <div class="relative mt-2">
                                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                                    <span class="text-slate-500 sm:text-xs sm:text-sm">Rp</span>
                                                </div>
                                                <input
                                                    type="number"
                                                    v-model="transactionForm.amount"
                                                    placeholder="0"
                                                    class="block w-full rounded-xl border-0 py-2.5 pl-10 text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                                />
                                            </div>
                                            <div v-if="transactionForm.errors.amount" class="mt-1 text-xs sm:text-sm text-rose-500">
                                                {{ transactionForm.errors.amount }}
                                            </div>
                                        </div>

                                        <!-- Transaction Mode -->
                                        <div>
                                            <label class="block text-xs sm:text-sm font-medium leading-6 text-slate-900">Mode Transaksi</label>
                                            <select
                                                v-model="transactionForm.transaction_mode"
                                                class="mt-2 block w-full rounded-xl border-0 py-2.5 pl-3 pr-10 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                            >
                                                <option value="Tunai">Tunai</option>
                                                <option value="Piutang">Piutang</option>
                                            </select>
                                            <div v-if="transactionForm.errors.transaction_mode" class="mt-1 text-xs sm:text-sm text-rose-500">
                                                {{ transactionForm.errors.transaction_mode }}
                                            </div>
                                        </div>

                                        <!-- Payment Method -->
                                        <div v-show="transactionForm.transaction_mode === 'Tunai'">
                                            <label class="block text-xs sm:text-sm font-medium leading-6 text-slate-900">Metode Pembayaran</label>
                                            <select
                                                v-model="transactionForm.payment_method"
                                                class="mt-2 block w-full rounded-xl border-0 py-2.5 pl-3 pr-10 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                            >
                                                <option v-for="pm in paymentMethods" :key="pm.id" :value="pm.name">
                                                    {{ pm.name }}
                                                </option>
                                                <option v-if="paymentMethods.length === 0" value="Tunai">Tunai</option>
                                            </select>
                                            <div v-if="transactionForm.errors.payment_method" class="mt-1 text-xs sm:text-sm text-rose-500">
                                                {{ transactionForm.errors.payment_method }}
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-slate-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                            <button
                                type="button"
                                @click="submitTransaction"
                                :disabled="transactionForm.processing"
                                class="inline-flex w-full justify-center rounded-xl bg-indigo-600 px-3 py-2.5 text-xs sm:text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 sm:ml-3 sm:w-auto disabled:opacity-70"
                            >
                                <svg v-if="transactionForm.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Simpan Transaksi
                            </button>
                            <button
                                type="button"
                                @click="closeModal"
                                class="mt-3 inline-flex w-full justify-center rounded-xl bg-white px-3 py-2.5 text-xs sm:text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:mt-0 sm:w-auto"
                            >
                                Batal
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add/Edit Reseller Modal -->
        <div v-if="isResellerModalOpen" class="relative z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity"></div>
            <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-md">
                        <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-slate-100 sm:mx-0 sm:h-10 sm:w-10">
                                    <svg class="h-6 w-6 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                                </div>
                                <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                                    <h3 class="text-lg font-semibold leading-6 text-slate-900" id="modal-title">
                                        {{ resellerForm.id ? 'Edit Reseller' : 'Tambah Reseller' }}
                                    </h3>
                                    
                                    <div class="mt-4 space-y-4">
                                        <div>
                                            <label class="block text-xs sm:text-sm font-medium leading-6 text-slate-900">Nama Reseller <span class="text-rose-500">*</span></label>
                                            <input type="text" v-model="resellerForm.name" class="mt-2 block w-full rounded-xl border-0 py-2.5 text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" />
                                            <div v-if="resellerForm.errors.name" class="mt-1 text-xs sm:text-sm text-rose-500">{{ resellerForm.errors.name }}</div>
                                        </div>
                                        <div>
                                            <label class="block text-xs sm:text-sm font-medium leading-6 text-slate-900">Area</label>
                                            <select v-model="resellerForm.area" class="mt-2 block w-full rounded-xl border-0 py-2.5 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 bg-white">
                                                <option value="">-- Pilih Area --</option>
                                                <option v-for="area in areas" :key="area" :value="area">{{ area }}</option>
                                            </select>
                                            <div v-if="resellerForm.errors.area" class="mt-1 text-xs sm:text-sm text-rose-500">{{ resellerForm.errors.area }}</div>
                                        </div>
                                        <div>
                                            <label class="block text-xs sm:text-sm font-medium leading-6 text-slate-900">Nomor Telepon / WA</label>
                                            <input type="text" v-model="resellerForm.phone" class="mt-2 block w-full rounded-xl border-0 py-2.5 text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" />
                                            <div v-if="resellerForm.errors.phone" class="mt-1 text-xs sm:text-sm text-rose-500">{{ resellerForm.errors.phone }}</div>
                                        </div>
                                        <div>
                                            <label class="block text-xs sm:text-sm font-medium leading-6 text-slate-900">Alamat Lengkap</label>
                                            <textarea v-model="resellerForm.alamat" rows="2" class="mt-2 block w-full rounded-xl border-0 py-2.5 text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"></textarea>
                                            <div v-if="resellerForm.errors.alamat" class="mt-1 text-xs sm:text-sm text-rose-500">{{ resellerForm.errors.alamat }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-slate-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                            <button
                                type="button"
                                @click="submitReseller"
                                :disabled="resellerForm.processing"
                                class="inline-flex w-full justify-center rounded-xl bg-indigo-600 px-3 py-2.5 text-xs sm:text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 sm:ml-3 sm:w-auto disabled:opacity-70"
                            >
                                <svg v-if="resellerForm.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                Simpan
                            </button>
                            <button
                                type="button"
                                @click="closeResellerModal"
                                class="mt-3 inline-flex w-full justify-center rounded-xl bg-white px-3 py-2.5 text-xs sm:text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:mt-0 sm:w-auto"
                            >
                                Batal
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Set Lunas (Pembayaran Piutang) Modal -->
        <div v-if="isLunasModalOpen" class="relative z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity"></div>
            <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                        <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-emerald-100 sm:mx-0 sm:h-10 sm:w-10">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                                    <h3 class="text-lg font-semibold leading-6 text-slate-900" id="modal-title">Bayar Piutang</h3>
                                    
                                    <div class="mt-4 space-y-4">
                                        <!-- Amount -->
                                        <div>
                                            <label class="block text-xs sm:text-sm font-medium leading-6 text-slate-900">Nominal Pembayaran (Rp)</label>
                                            <div class="relative mt-2">
                                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                                    <span class="text-slate-500 sm:text-xs sm:text-sm">Rp</span>
                                                </div>
                                                <input
                                                    type="number"
                                                    v-model="lunasForm.amount"
                                                    class="block w-full rounded-xl border-0 py-2.5 pl-10 text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-emerald-600 sm:text-sm sm:leading-6"
                                                />
                                            </div>
                                            <div v-if="lunasForm.errors.amount" class="mt-1 text-xs sm:text-sm text-rose-500">{{ lunasForm.errors.amount }}</div>
                                        </div>

                                        <!-- Date -->
                                        <div>
                                            <label class="block text-xs sm:text-sm font-medium leading-6 text-slate-900">Tanggal Pembayaran</label>
                                            <input
                                                type="date"
                                                v-model="lunasForm.date"
                                                class="mt-2 block w-full rounded-xl border-0 py-2.5 text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-emerald-600 sm:text-sm sm:leading-6"
                                            />
                                            <div v-if="lunasForm.errors.date" class="mt-1 text-xs sm:text-sm text-rose-500">{{ lunasForm.errors.date }}</div>
                                        </div>

                                        <!-- Payment Method -->
                                        <div>
                                            <label class="block text-xs sm:text-sm font-medium leading-6 text-slate-900">Metode Pembayaran</label>
                                            <select
                                                v-model="lunasForm.payment_method"
                                                class="mt-2 block w-full rounded-xl border-0 py-2.5 pl-3 pr-10 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-emerald-600 sm:text-sm sm:leading-6"
                                            >
                                                <option v-for="pm in paymentMethods" :key="pm.id" :value="pm.name">{{ pm.name }}</option>
                                                <option v-if="paymentMethods.length === 0" value="Tunai">Tunai</option>
                                            </select>
                                            <div v-if="lunasForm.errors.payment_method" class="mt-1 text-xs sm:text-sm text-rose-500">{{ lunasForm.errors.payment_method }}</div>
                                        </div>

                                        <!-- Collector -->
                                        <div>
                                            <label class="block text-xs sm:text-sm font-medium leading-6 text-slate-900">Penagih / Penerima</label>
                                            <input
                                                type="text"
                                                v-model="lunasForm.collector"
                                                placeholder="Nama yang menerima pembayaran"
                                                class="mt-2 block w-full rounded-xl border-0 py-2.5 text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-emerald-600 sm:text-sm sm:leading-6"
                                            />
                                            <div v-if="lunasForm.errors.collector" class="mt-1 text-xs sm:text-sm text-rose-500">{{ lunasForm.errors.collector }}</div>
                                        </div>

                                        <!-- Proof (Bukti Pembayaran) -->
                                        <div>
                                            <label class="block text-xs sm:text-sm font-medium leading-6 text-slate-900">Bukti Pembayaran (Opsional)</label>
                                            <input
                                                type="file"
                                                @input="lunasForm.proof = $event.target.files[0]"
                                                accept="image/*"
                                                class="mt-2 block w-full text-xs sm:text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs sm:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100"
                                            />
                                            <div v-if="lunasForm.errors.proof" class="mt-1 text-xs sm:text-sm text-rose-500">{{ lunasForm.errors.proof }}</div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-slate-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                            <button
                                type="button"
                                @click="submitLunas"
                                :disabled="lunasForm.processing"
                                class="inline-flex w-full justify-center rounded-xl bg-emerald-600 px-3 py-2.5 text-xs sm:text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 sm:ml-3 sm:w-auto disabled:opacity-70"
                            >
                                <svg v-if="lunasForm.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                Simpan Pembayaran
                            </button>
                            <button
                                type="button"
                                @click="closeLunasModal"
                                class="mt-3 inline-flex w-full justify-center rounded-xl bg-white px-3 py-2.5 text-xs sm:text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:mt-0 sm:w-auto"
                            >
                                Batal
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detail Transaksi Modal -->
        <div v-if="isDetailModalOpen && selectedDetail" class="relative z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity"></div>
            <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-md">
                        <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-blue-50 sm:mx-0 sm:h-10 sm:w-10">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                                    <h3 class="text-lg font-semibold leading-6 text-slate-900" id="modal-title">Detail Transaksi</h3>
                                    
                                    <div class="mt-4 space-y-4">
                                        <div>
                                            <p class="text-xs sm:text-sm text-slate-500">Tanggal</p>
                                            <p class="text-xs sm:text-sm font-semibold text-slate-900">{{ formatDate(selectedDetail.date) }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs sm:text-sm text-slate-500">Keterangan</p>
                                            <p class="text-xs sm:text-sm font-semibold text-slate-900">{{ selectedDetail.description }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs sm:text-sm text-slate-500">Reseller</p>
                                            <p class="text-xs sm:text-sm font-semibold text-slate-900">{{ selectedDetail.reseller?.name || 'Transaksi Umum' }}</p>
                                        </div>
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <p class="text-xs sm:text-sm text-slate-500">Nominal</p>
                                                <p class="text-xs sm:text-sm font-semibold text-slate-900">{{ formatRupiah(selectedDetail.amount) }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs sm:text-sm text-slate-500">Mode</p>
                                                <p class="text-xs sm:text-sm font-semibold text-slate-900">{{ selectedDetail.transaction_mode }}</p>
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-2 gap-4">
                                            <div v-if="selectedDetail.payment_method">
                                                <p class="text-xs sm:text-sm text-slate-500">Metode Bayar</p>
                                                <p class="text-xs sm:text-sm font-semibold text-slate-900">{{ selectedDetail.payment_method }}</p>
                                            </div>
                                            <div v-if="selectedDetail.collector">
                                                <p class="text-xs sm:text-sm text-slate-500">Penagih / Penerima</p>
                                                <p class="text-xs sm:text-sm font-semibold text-slate-900">{{ selectedDetail.collector }}</p>
                                            </div>
                                        </div>

                                        <div v-if="selectedDetail.proof" class="mt-4">
                                            <p class="text-xs sm:text-sm font-medium text-slate-700 mb-2">Bukti Pembayaran</p>
                                            <div class="overflow-hidden rounded-xl border border-slate-200">
                                                <img :src="'/storage/' + selectedDetail.proof" alt="Bukti Pembayaran" class="w-full object-cover" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-slate-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                            <button
                                type="button"
                                @click="closeDetailModal"
                                class="mt-3 inline-flex w-full justify-center rounded-xl bg-white px-3 py-2.5 text-xs sm:text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:mt-0 sm:w-auto"
                            >
                                Tutup
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
@media print {
    @page {
        margin: 1cm;
        size: landscape;
    }
    body {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}
</style>
