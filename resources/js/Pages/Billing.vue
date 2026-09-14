<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import * as XLSX from 'xlsx';

const props = defineProps({
    customers: {
        type: Array,
        default: () => [],
    },
    paymentMethods: {
        type: Array,
        default: () => [],
    },
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

// Helper to check if customer is active
const isAktif = (c) => !c.status_pelanggan || String(c.status_pelanggan).toLowerCase() === 'aktif';

// Summary Statistics
const totalCustomers = computed(() => props.customers.length);
const totalLunas = computed(() =>
    props.customers.filter((c) => String(c.status).toLowerCase() === 'paid').length
);
const totalBelumLunas = computed(() =>
    props.customers.filter((c) => String(c.status).toLowerCase() !== 'paid' && isAktif(c)).length
);
const totalTagihan = computed(() =>
    props.customers.reduce((sum, c) => sum + (isAktif(c) ? (Number(c.amount) || 0) : 0), 0)
);

const percentLunas = computed(() => {
    if (totalCustomers.value === 0) return 0;
    return Math.round((totalLunas.value / totalCustomers.value) * 100);
});

// Excel Import State
const fileInput = ref(null);
const selectedFileName = ref('');
const parsedCount = ref(0);
const parseError = ref('');
const isDragging = ref(false);

const importForm = useForm({
    customersData: [],
});

const handleFile = (file) => {
    if (!file) return;

    // Check extension
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
                importForm.customersData = [];
                parsedCount.value = 0;
                return;
            }

            // Map data and normalize properties while preserving original keys
            const mappedData = jsonData.map((row) => {
                const keys = Object.keys(row);
                // Auto detect name column
                const nameKey = keys.find((k) =>
                    /^(nama|name|pelanggan|customer)/i.test(k.trim())
                );

                // Auto detect amount column
                const amountKey = keys.find((k) =>
                    /^(amount|tagihan|tarif|nominal|harga|total|jumlah|biaya)/i.test(k.trim())
                );

                // Auto detect area
                const areaKey = keys.find((k) =>
                    /^(area|wilayah|cabang)/i.test(k.trim())
                );

                // Auto detect alamat
                const alamatKey = keys.find((k) =>
                    /^(alamat|address)/i.test(k.trim())
                );

                // Auto detect paket
                const paketKey = keys.find((k) =>
                    /^(paket|nama paket)/i.test(k.trim())
                );

                // Auto detect tanggal register
                const tglKey = keys.find((k) =>
                    /^(tanggal|tgl|register)/i.test(k.trim())
                );

                // Auto detect status pelanggan
                const statusKey = keys.find((k) =>
                    /^(status)/i.test(k.trim())
                );
                
                // Auto detect pembayaran terakhir
                const lastPaidKey = keys.find((k) =>
                    /^(pembayaran|terakhir|last|paid)/i.test(k.trim()) && !/^(tanggal|tgl|register)/i.test(k.trim())
                );

                const rawAmount = amountKey ? row[amountKey] : 0;
                const cleanAmount =
                    typeof rawAmount === 'number'
                        ? rawAmount
                        : Number(String(rawAmount).replace(/[^0-9.-]+/g, '')) || 0;

                const nameValue = nameKey ? String(row[nameKey]).trim() : '';
                const areaValue = areaKey ? String(row[areaKey]).trim() : '';
                const alamatValue = alamatKey ? String(row[alamatKey]).trim() : '';
                const paketValue = paketKey ? String(row[paketKey]).trim() : '';
                const tglValue = tglKey ? String(row[tglKey]).trim() : null;
                const statusValue = statusKey ? String(row[statusKey]).trim() : 'Aktif';
                
                let lastPaidValue = null;
                if (lastPaidKey && row[lastPaidKey]) {
                    // Try to parse the date to YYYY-MM-DD
                    const parsed = new Date(row[lastPaidKey]);
                    if (!isNaN(parsed.getTime())) {
                        lastPaidValue = parsed.toISOString().split('T')[0];
                    } else {
                        lastPaidValue = String(row[lastPaidKey]).trim();
                    }
                }

                return {
                    ...row,
                    name: nameValue,
                    amount: cleanAmount,
                    area: areaValue,
                    alamat: alamatValue,
                    paket: paketValue,
                    register_date: tglValue,
                    status_pelanggan: statusValue,
                    last_paid_date: lastPaidValue
                };
            }).filter((item) => item.name.length > 0);

            if (mappedData.length === 0) {
                parseError.value = 'Tidak ditemukan kolom nama pelanggan yang valid.';
                importForm.customersData = [];
                parsedCount.value = 0;
                return;
            }

            importForm.customersData = mappedData;
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

const onDrop = (event) => {
    isDragging.value = false;
    const file = event.dataTransfer.files[0];
    if (file) handleFile(file);
};

const triggerFileInput = () => {
    if (fileInput.value) fileInput.value.click();
};

const removeSelectedFile = () => {
    selectedFileName.value = '';
    parsedCount.value = 0;
    parseError.value = '';
    importForm.customersData = [];
    if (fileInput.value) fileInput.value.value = '';
};

const submitImport = () => {
    if (!importForm.customersData || importForm.customersData.length === 0) {
        alert('Mohon pilih file Excel yang memiliki Billing Data.');
        return;
    }

    importForm.post(route('billing.import'), {
        preserveScroll: true,
        onSuccess: () => {
            removeSelectedFile();
        },
    });
};

// Search and Filtering
const searchQuery = ref('');
const statusFilter = ref('all');
const areaFilter = ref('all');

const uniqueAreas = computed(() => {
    const areas = props.customers
        .map((c) => c.area)
        .filter((area) => Boolean(area) && String(area).trim() !== '');
    return [...new Set(areas)];
});

const filteredCustomers = computed(() => {
    const query = searchQuery.value.trim().toLowerCase();

    return props.customers.filter((customer) => {
        const matchesQuery =
            !query ||
            (customer.name && customer.name.toLowerCase().includes(query)) ||
            (customer.area && customer.area.toLowerCase().includes(query));

        const isLunas = String(customer.status).toLowerCase() === 'paid';
        const matchesStatus =
            statusFilter.value === 'all' ||
            (statusFilter.value === 'Lunas' && isLunas) ||
            (statusFilter.value === 'Belum Lunas' && !isLunas);

        const matchesArea =
            areaFilter.value === 'all' || customer.area === areaFilter.value;

        return matchesQuery && matchesStatus && matchesArea;
    });
});

const resetFilters = () => {
    searchQuery.value = '';
    statusFilter.value = 'all';
    areaFilter.value = 'all';
};

// Set Lunas Modal
const isLunasModalOpen = ref(false);
const activeCustomer = ref(null);

const lunasForm = useForm({
    payment_method_id: '',
    payment_method: '',
});

const openLunasModal = (customer) => {
    activeCustomer.value = customer;
    lunasForm.reset();
    lunasForm.clearErrors();
    // Default to first payment method if available
    if (props.paymentMethods.length > 0) {
        lunasForm.payment_method_id = props.paymentMethods[0].id;
        lunasForm.payment_method = props.paymentMethods[0].name;
    }
    isLunasModalOpen.value = true;
};

const closeLunasModal = () => {
    isLunasModalOpen.value = false;
    activeCustomer.value = null;
    lunasForm.reset();
};

const onPaymentMethodSelect = (event) => {
    const selectedId = event.target.value;
    const method = props.paymentMethods.find(
        (m) => String(m.id) === String(selectedId)
    );
    if (method) {
        lunasForm.payment_method_id = method.id;
        lunasForm.payment_method = method.name;
    }
};

const submitLunas = () => {
    if (!activeCustomer.value) return;

    lunasForm.post(route('billing.lunas', activeCustomer.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeLunasModal();
        },
    });
};

// Edit Customer Modal
const isEditModalOpen = ref(false);
const editingCustomer = ref(null);

const editForm = useForm({
    name: '',
    area: '',
    alamat: '',
    paket: '',
    register_date: '',
    status_pelanggan: '',
    suspend_start_date: '',
    suspend_end_date: '',
    stop_date: '',
    last_paid_date: '',
    base_amount: 0,
    amount: 0,
});

const openEditModal = (customer) => {
    editingCustomer.value = customer;
    editForm.name = customer.name || '';
    editForm.area = customer.area || '';
    editForm.alamat = customer.alamat || '';
    editForm.paket = customer.paket || '';
    editForm.register_date = customer.register_date || '';
    editForm.status_pelanggan = customer.status_pelanggan || 'Aktif';
    editForm.suspend_start_date = customer.suspend_start_date || '';
    editForm.suspend_end_date = customer.suspend_end_date || '';
    editForm.stop_date = customer.stop_date || '';
    editForm.last_paid_date = customer.last_paid_date || '';
    editForm.base_amount = customer.base_amount || 0;
    editForm.amount = customer.amount || 0;
    editForm.clearErrors();
    isEditModalOpen.value = true;
};

const closeEditModal = () => {
    isEditModalOpen.value = false;
    editingCustomer.value = null;
    editForm.reset();
};

const submitEdit = () => {
    if (!editingCustomer.value) return;

    editForm.put(route('pelanggan.update', editingCustomer.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeEditModal();
        },
    });
};

// Delete Customer
const deleteCustomer = (customer) => {
    if (
        confirm(
            `Apakah Anda yakin ingin menghapus pelanggan "${customer.name}"? Tindakan ini tidak dapat dibatalkan.`
        )
    ) {
        router.delete(route('pelanggan.destroy', customer.id), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Billing Data" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-slate-800">
                        Billing Data
                    </h2>
                    <p class="text-sm text-slate-500">
                        Kelola Billing Data, pantau status tagihan, dan import data dari Excel.
                    </p>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <!-- 1. STATS CARDS -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <!-- Total Pelanggan -->
                    <div class="relative overflow-hidden rounded-xl border border-slate-200/80 bg-white p-5 shadow-sm transition hover:shadow-md">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Total Pelanggan
                                </p>
                                <p class="mt-2 text-3xl font-bold tracking-tight text-slate-800">
                                    {{ totalCustomers }}
                                </p>
                                <p class="mt-1 text-xs text-slate-400">
                                    Semua data terdaftar
                                </p>
                            </div>
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 ring-1 ring-indigo-100">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Sudah Lunas -->
                    <div class="relative overflow-hidden rounded-xl border border-slate-200/80 bg-white p-5 shadow-sm transition hover:shadow-md">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Sudah Lunas
                                </p>
                                <p class="mt-2 text-3xl font-bold tracking-tight text-emerald-600">
                                    {{ totalLunas }}
                                </p>
                                <p class="mt-1 text-xs text-slate-400">
                                    {{ percentLunas }}% dari total pelanggan
                                </p>
                            </div>
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Belum Lunas -->
                    <div class="relative overflow-hidden rounded-xl border border-slate-200/80 bg-white p-5 shadow-sm transition hover:shadow-md">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Belum Lunas
                                </p>
                                <p class="mt-2 text-3xl font-bold tracking-tight text-amber-600">
                                    {{ totalBelumLunas }}
                                </p>
                                <p class="mt-1 text-xs text-slate-400">
                                    Menunggu pembayaran
                                </p>
                            </div>
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-50 text-amber-600 ring-1 ring-amber-100">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Total Tagihan -->
                    <div class="relative overflow-hidden rounded-xl border border-slate-200/80 bg-white p-5 shadow-sm transition hover:shadow-md">
                        <div class="flex items-center justify-between">
                            <div class="overflow-hidden">
                                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Total Tagihan
                                </p>
                                <p class="mt-2 truncate text-2xl font-bold tracking-tight text-slate-800" :title="formatRupiah(totalTagihan)">
                                    {{ formatRupiah(totalTagihan) }}
                                </p>
                                <p class="mt-1 text-xs text-slate-400">
                                    Akumulasi tagihan pelanggan
                                </p>
                            </div>
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-violet-600 ring-1 ring-violet-100">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. EXCEL IMPORT SECTION -->
                <div class="mx-auto max-w-3xl overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-6 py-4">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base font-semibold text-slate-800">Import Billing Data via Excel</h3>
                                <p class="text-xs text-slate-500">Unggah berkas spreadsheet untuk menambahkan pelanggan secara massal.</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-6">
                        <form @submit.prevent="submitImport" class="flex flex-col gap-3">
                            <!-- Drag and Drop Upload Area -->
                            <div class="w-full">
                                <input
                                    ref="fileInput"
                                    type="file"
                                    accept=".xlsx, .xls, .csv"
                                    class="hidden"
                                    @change="onFileInputChange"
                                />

                                <div
                                    @dragover.prevent="isDragging = true"
                                    @dragenter.prevent="isDragging = true"
                                    @dragleave.prevent="isDragging = false"
                                    @drop.prevent="onDrop"
                                    @click="triggerFileInput"
                                    :class="[
                                        'group relative flex flex-col items-center justify-center rounded-xl border-2 border-dashed py-4 px-6 text-center cursor-pointer transition-all duration-200',
                                        isDragging
                                            ? 'border-indigo-500 bg-indigo-50/60 ring-2 ring-indigo-500/20'
                                            : 'border-slate-300 bg-slate-50/50 hover:border-indigo-400 hover:bg-slate-50'
                                    ]"
                                >
                                    <div class="flex items-center justify-center gap-3">
                                        <!-- Upload Cloud Icon -->
                                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white shadow-sm ring-1 ring-slate-200 transition group-hover:scale-105 group-hover:ring-indigo-200">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                            </svg>
                                        </div>

                                        <div class="text-sm text-left">
                                            <div class="text-slate-600">
                                                <span class="font-semibold text-indigo-600 group-hover:underline">Pilih file</span> atau seret & lepas file ke sini
                                            </div>
                                            <p class="text-xs text-slate-400">
                                                Format .xlsx, .xls, .csv
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Selected File Feedback -->
                                    <div
                                        v-if="selectedFileName"
                                        class="mt-3 inline-flex items-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50/80 px-3.5 py-1.5 text-xs text-indigo-900"
                                        @click.stop
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <span class="font-medium">{{ selectedFileName }}</span>
                                        <span v-if="parsedCount > 0" class="rounded bg-indigo-200/70 px-1.5 py-0.5 text-[11px] font-semibold text-indigo-800">
                                            {{ parsedCount }} baris
                                        </span>
                                        <button
                                            type="button"
                                            @click="removeSelectedFile"
                                            class="ml-1 text-slate-400 transition hover:text-rose-600"
                                            title="Hapus file"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <p v-if="parseError" class="mt-2 text-xs font-medium text-rose-600">
                                    {{ parseError }}
                                </p>
                                <p v-if="importForm.errors.customersData" class="mt-2 text-xs font-medium text-rose-600">
                                    {{ importForm.errors.customersData }}
                                </p>
                            </div>

                            <!-- Footer (Info & Submit) -->
                            <div class="flex w-full flex-col sm:flex-row sm:items-center justify-between gap-4 border-t border-slate-100 pt-3 mt-1">
                                <!-- Quick info alert box -->
                                <div class="flex-1 rounded-lg border border-blue-100 bg-blue-50/70 py-2 px-3 text-[11px] text-blue-800">
                                    <div class="flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <p>
                                            Header kolom Excel: <strong>Nama Pelanggan, Area, Alamat, Nama Paket, Tanggal Register, Status Pelanggan, Tagihan</strong>.
                                        </p>
                                    </div>
                                </div>

                                <button
                                    type="submit"
                                    :disabled="importForm.processing"
                                    class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 hover:shadow focus:outline-none focus:ring-2 focus:ring-indigo-500/30 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <svg
                                        v-if="importForm.processing"
                                        class="h-4 w-4 animate-spin text-white"
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                    >
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                    </svg>
                                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                    </svg>
                                    <span>{{ importForm.processing ? 'Mengimpor...' : 'Mulai Import' }}</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- 3. CUSTOMER TABLE SECTION -->
                <div class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm">
                    <!-- Table Controls & Search Filter Bar -->
                    <div class="border-b border-slate-100 p-5">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <h3 class="text-base font-semibold text-slate-800">Daftar Tagihan</h3>
                                <p class="text-xs text-slate-500">
                                    Menampilkan {{ filteredCustomers.length }} dari {{ totalCustomers }} pelanggan
                                </p>
                            </div>

                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                                <!-- Search Input -->
                                <div class="relative w-full sm:w-64">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                    </div>
                                    <input
                                        v-model="searchQuery"
                                        type="text"
                                        placeholder="Cari nama atau area..."
                                        class="w-full rounded-xl border border-slate-300 py-2 pl-9 pr-3 text-xs text-slate-800 shadow-sm transition placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                                    />
                                    <button
                                        v-if="searchQuery"
                                        @click="searchQuery = ''"
                                        class="absolute inset-y-0 right-0 flex items-center pr-2.5 text-slate-400 hover:text-slate-600"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Status Filter -->
                                <select
                                    v-model="statusFilter"
                                    class="w-full rounded-xl border border-slate-300 py-2 pl-3 pr-8 text-xs text-slate-700 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 sm:w-auto"
                                >
                                    <option value="all">Semua Status</option>
                                    <option value="Lunas">Lunas</option>
                                    <option value="Belum Lunas">Belum Lunas</option>
                                </select>

                                <!-- Area Filter -->
                                <select
                                    v-if="uniqueAreas.length > 0"
                                    v-model="areaFilter"
                                    class="w-full rounded-xl border border-slate-300 py-2 pl-3 pr-8 text-xs text-slate-700 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 sm:w-auto"
                                >
                                    <option value="all">Semua Area</option>
                                    <option v-for="area in uniqueAreas" :key="area" :value="area">
                                        {{ area }}
                                    </option>
                                </select>

                                <!-- Reset Filters -->
                                <button
                                    v-if="searchQuery || statusFilter !== 'all' || areaFilter !== 'all'"
                                    @click="resetFilters"
                                    type="button"
                                    class="inline-flex items-center gap-1 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-600 shadow-sm transition hover:bg-slate-50"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    <span>Reset</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                            <thead class="bg-slate-50/80 text-xs font-semibold uppercase tracking-wider text-slate-500">
                                <tr>
                                    <th scope="col" class="w-16 px-4 py-3.5 text-center">No</th>
                                    <th scope="col" class="px-6 py-3.5">Nama Pelanggan</th>
                                    <th scope="col" class="px-6 py-3.5">Area</th>
                                    <th scope="col" class="px-6 py-3.5">Alamat</th>
                                    <th scope="col" class="px-6 py-3.5">Nama Paket</th>
                                    <th scope="col" class="px-6 py-3.5">Tanggal Register</th>
                                    <th scope="col" class="px-6 py-3.5">Pembayaran Terakhir</th>
                                    <th scope="col" class="px-6 py-3.5 text-right">Tagihan</th>
                                    <th scope="col" class="px-6 py-3.5 text-center">Status Pelanggan</th>
                                    <th scope="col" class="px-6 py-3.5 text-center">Status</th>
                                    <th scope="col" class="w-36 px-6 py-3.5 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <tr
                                    v-for="(customer, index) in filteredCustomers"
                                    :key="customer.id || index"
                                    class="transition-colors duration-150 hover:bg-slate-50/80"
                                >
                                    <!-- Row Number -->
                                    <td class="whitespace-nowrap px-4 py-4 text-center text-xs font-medium text-slate-400">
                                        {{ index + 1 }}
                                    </td>

                                    <!-- Customer Name -->
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-600 ring-1 ring-slate-200">
                                                {{ (customer.name || '?').charAt(0).toUpperCase() }}
                                            </div>
                                            <div>
                                                <div class="font-semibold text-slate-800">
                                                    {{ customer.name }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Area -->
                                    <td class="whitespace-nowrap px-6 py-4 text-xs text-slate-600">
                                        <span class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            {{ customer.area || '-' }}
                                        </span>
                                    </td>

                                    <!-- Alamat -->
                                    <td class="whitespace-nowrap px-6 py-4 text-xs text-slate-600">
                                        {{ customer.alamat || '-' }}
                                    </td>

                                    <!-- Paket -->
                                    <td class="whitespace-nowrap px-6 py-4 text-xs text-slate-600">
                                        {{ customer.paket || '-' }}
                                    </td>

                                    <!-- Tanggal Register -->
                                    <td class="whitespace-nowrap px-6 py-4 text-xs text-slate-600">
                                        {{ formatDate(customer.register_date) }}
                                    </td>

                                    <!-- Pembayaran Terakhir -->
                                    <td class="whitespace-nowrap px-6 py-4 text-xs text-slate-600">
                                        {{ formatDate(customer.last_paid_date) }}
                                    </td>

                                    <!-- Amount -->
                                    <td class="whitespace-nowrap px-6 py-4 text-right font-medium text-slate-800">
                                        {{ isAktif(customer) ? formatRupiah(customer.amount) : formatRupiah(0) }}
                                    </td>

                                    <!-- Status Pelanggan -->
                                    <td class="whitespace-nowrap px-6 py-4 text-center">
                                        <span
                                            v-if="String(customer.status_pelanggan || 'Aktif').toLowerCase() === 'aktif'"
                                            class="inline-flex items-center gap-1.5 rounded-md bg-blue-100 px-2.5 py-1 text-xs font-medium text-blue-700"
                                        >
                                            Aktif
                                        </span>
                                        <span
                                            v-else-if="String(customer.status_pelanggan).toLowerCase() === 'berhenti sementara'"
                                            class="inline-flex items-center gap-1.5 rounded-md bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-700"
                                        >
                                            Berhenti sementara
                                        </span>
                                        <span
                                            v-else-if="String(customer.status_pelanggan).toLowerCase() === 'stop permanen'"
                                            class="inline-flex items-center gap-1.5 rounded-md bg-rose-100 px-2.5 py-1 text-xs font-medium text-rose-700"
                                        >
                                            Stop Permanen
                                        </span>
                                        <span
                                            v-else-if="String(customer.status_pelanggan).toLowerCase() === 'gratis'"
                                            class="inline-flex items-center gap-1.5 rounded-md bg-purple-100 px-2.5 py-1 text-xs font-medium text-purple-700"
                                        >
                                            Gratis
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center gap-1.5 rounded-md bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700"
                                        >
                                            {{ customer.status_pelanggan }}
                                        </span>
                                    </td>

                                    <!-- Status Badge -->
                                    <td class="whitespace-nowrap px-6 py-4 text-center">
                                        <span
                                            v-if="String(customer.status_pelanggan || 'Aktif').toLowerCase() !== 'aktif'"
                                            class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 ring-1 ring-inset ring-slate-600/20"
                                        >
                                            <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                                            {{ customer.status_pelanggan }}
                                        </span>
                                        <span
                                            v-else-if="String(customer.status).toLowerCase() === 'paid'"
                                            class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20"
                                        >
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-600"></span>
                                            Lunas
                                        </span>
                                        <span
                                            v-else-if="String(customer.status).toLowerCase() === 'nunggak'"
                                            class="inline-flex items-center gap-1.5 rounded-full bg-rose-100 px-3 py-1 text-xs font-semibold text-rose-700 ring-1 ring-inset ring-rose-600/20"
                                        >
                                            <span class="h-1.5 w-1.5 rounded-full bg-rose-600"></span>
                                            Nunggak
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-600/20"
                                        >
                                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                            Belum Bayar
                                        </span>
                                    </td>

                                    <!-- Actions -->
                                    <td class="whitespace-nowrap px-6 py-4 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <!-- Set Lunas Button -->
                                            <button
                                                v-if="String(customer.status).toLowerCase() !== 'paid'"
                                                type="button"
                                                @click="openLunasModal(customer)"
                                                class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-2.5 py-1.5 text-xs font-medium text-white shadow-sm transition duration-150 hover:bg-emerald-700 hover:shadow focus:outline-none focus:ring-2 focus:ring-emerald-500/30"
                                                title="Tandai Sudah Lunas"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                </svg>
                                                <span>Set Lunas</span>
                                            </button>

                                            <!-- Edit Button -->
                                            <button
                                                type="button"
                                                @click="openEditModal(customer)"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition duration-150 hover:bg-indigo-50 hover:text-indigo-600 focus:outline-none"
                                                title="Edit Pelanggan"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>

                                            <!-- Delete Button -->
                                            <button
                                                type="button"
                                                @click="deleteCustomer(customer)"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition duration-150 hover:bg-rose-50 hover:text-rose-600 focus:outline-none"
                                                title="Hapus Pelanggan"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Empty State -->
                                <tr v-if="filteredCustomers.length === 0">
                                    <td colspan="6" class="px-6 py-12 text-center">
                                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                            </svg>
                                        </div>
                                        <h4 class="mt-3 text-sm font-semibold text-slate-800">
                                            Tidak Ada Billing Data
                                        </h4>
                                        <p class="mt-1 text-xs text-slate-500">
                                            <span v-if="searchQuery || statusFilter !== 'all' || areaFilter !== 'all'">
                                                Tidak ditemukan pelanggan yang sesuai dengan filter pencarian Anda.
                                            </span>
                                            <span v-else>
                                                Belum ada Billing Data yang ditambahkan. Gunakan formulir import Excel di atas untuk menambahkan pelanggan.
                                            </span>
                                        </p>
                                        <div v-if="searchQuery || statusFilter !== 'all' || areaFilter !== 'all'" class="mt-4">
                                            <button
                                                type="button"
                                                @click="resetFilters"
                                                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
                                            >
                                                Bersihkan Filter
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. SET LUNAS MODAL -->
        <div
            v-if="isLunasModalOpen"
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title"
            role="dialog"
            aria-modal="true"
        >
            <!-- Overlay with Backdrop Blur -->
            <div
                class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity duration-300"
                @click="closeLunasModal"
            ></div>

            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div
                    class="relative w-full max-w-md transform overflow-hidden rounded-2xl border border-slate-100 bg-white p-6 text-left shadow-2xl transition-all duration-300"
                >
                    <!-- Close Button -->
                    <button
                        type="button"
                        @click="closeLunasModal"
                        class="absolute right-4 top-4 rounded-lg p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>

                    <!-- Modal Header -->
                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 id="modal-title" class="text-lg font-bold text-slate-800">
                                Konfirmasi Pelunasan
                            </h3>
                            <p class="text-xs text-slate-500">
                                Tandai tagihan pelanggan sebagai sudah lunas.
                            </p>
                        </div>
                    </div>

                    <!-- Customer Info Card -->
                    <div v-if="activeCustomer" class="mt-5 rounded-xl border border-slate-100 bg-slate-50/80 p-4">
                        <div class="flex items-start justify-between">
                            <div>
                                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                                    Nama Pelanggan
                                </span>
                                <p class="text-sm font-bold text-slate-800">
                                    {{ activeCustomer.name }}
                                </p>
                                <span class="mt-1 inline-flex items-center gap-1 rounded bg-white px-2 py-0.5 text-xs text-slate-600 ring-1 ring-slate-200">
                                    Area: {{ activeCustomer.area || '-' }}
                                </span>
                            </div>
                            <div class="text-right">
                                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                                    Nominal Tagihan
                                </span>
                                <p class="text-base font-bold text-emerald-600">
                                    {{ formatRupiah(activeCustomer.amount) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Form -->
                    <form @submit.prevent="submitLunas" class="mt-5 space-y-4">
                        <div>
                            <label for="payment-method" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">
                                Metode Pembayaran <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative mt-1.5">
                                <select
                                    id="payment-method"
                                    v-model="lunasForm.payment_method_id"
                                    @change="onPaymentMethodSelect"
                                    class="w-full rounded-xl border border-slate-300 py-2.5 pl-3.5 pr-10 text-sm text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                                    required
                                >
                                    <option value="" disabled>Pilih metode pembayaran...</option>
                                    <option
                                        v-for="method in paymentMethods"
                                        :key="method.id"
                                        :value="method.id"
                                    >
                                        {{ method.name }}
                                    </option>
                                </select>
                            </div>
                            <p v-if="lunasForm.errors.payment_method_id" class="mt-1 text-xs font-medium text-rose-600">
                                {{ lunasForm.errors.payment_method_id }}
                            </p>
                            <p v-if="lunasForm.errors.payment_method" class="mt-1 text-xs font-medium text-rose-600">
                                {{ lunasForm.errors.payment_method }}
                            </p>
                        </div>

                        <!-- Modal Actions -->
                        <div class="mt-6 flex items-center justify-end gap-3 pt-2">
                            <button
                                type="button"
                                @click="closeLunasModal"
                                class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="lunasForm.processing"
                                class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-700 hover:shadow focus:outline-none focus:ring-2 focus:ring-emerald-500/30 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <svg
                                    v-if="lunasForm.processing"
                                    class="h-3.5 w-3.5 animate-spin text-white"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                <span>{{ lunasForm.processing ? 'Menyimpan...' : 'Tandai Lunas' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit Customer Modal -->
        <div v-if="isEditModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <!-- Backdrop -->
            <div
                class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity"
                @click="closeEditModal"
            ></div>

            <!-- Modal Panel -->
            <div class="relative w-full max-w-md transform overflow-hidden rounded-2xl bg-white shadow-2xl transition-all">
                <div class="border-b border-slate-100 bg-slate-50/50 px-6 py-4">
                    <h3 class="text-lg font-bold text-slate-800">Edit Pelanggan</h3>
                    <p class="mt-1 text-xs text-slate-500">Perbarui informasi Billing Data.</p>
                </div>

                <div class="p-6">
                    <form @submit.prevent="submitEdit">
                        <div class="space-y-4">
                            <!-- Name -->
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-600">
                                    Nama Pelanggan <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="editForm.name"
                                    type="text"
                                    class="w-full rounded-xl border border-slate-300 py-2.5 px-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                    required
                                />
                                <p v-if="editForm.errors.name" class="mt-1 text-xs text-rose-600">{{ editForm.errors.name }}</p>
                            </div>

                            <!-- Area -->
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-600">
                                    Area / Wilayah
                                </label>
                                <input
                                    v-model="editForm.area"
                                    type="text"
                                    class="w-full rounded-xl border border-slate-300 py-2.5 px-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                />
                                <p v-if="editForm.errors.area" class="mt-1 text-xs text-rose-600">{{ editForm.errors.area }}</p>
                            </div>

                            <!-- Alamat -->
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-600">
                                    Alamat Lengkap
                                </label>
                                <textarea
                                    v-model="editForm.alamat"
                                    rows="2"
                                    class="w-full rounded-xl border border-slate-300 py-2.5 px-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                ></textarea>
                                <p v-if="editForm.errors.alamat" class="mt-1 text-xs text-rose-600">{{ editForm.errors.alamat }}</p>
                            </div>

                            <!-- Paket -->
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-600">
                                    Nama Paket
                                </label>
                                <input
                                    v-model="editForm.paket"
                                    type="text"
                                    class="w-full rounded-xl border border-slate-300 py-2.5 px-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                />
                                <p v-if="editForm.errors.paket" class="mt-1 text-xs text-rose-600">{{ editForm.errors.paket }}</p>
                            </div>

                            <!-- Register Date -->
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-600">
                                    Tanggal Register
                                </label>
                                <input
                                    v-model="editForm.register_date"
                                    type="date"
                                    class="w-full rounded-xl border border-slate-300 py-2.5 px-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                />
                                <p v-if="editForm.errors.register_date" class="mt-1 text-xs text-rose-600">{{ editForm.errors.register_date }}</p>
                            </div>

                            <!-- Status Pelanggan -->
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-600">
                                    Status Pelanggan
                                </label>
                                <select
                                    v-model="editForm.status_pelanggan"
                                    class="w-full rounded-xl border border-slate-300 py-2.5 px-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                >
                                    <option value="Aktif">Aktif</option>
                                    <option value="Berhenti sementara">Berhenti sementara</option>
                                    <option value="Stop Permanen">Stop Permanen</option>
                                    <option value="Gratis">Gratis</option>
                                </select>
                                <p v-if="editForm.errors.status_pelanggan" class="mt-1 text-xs text-rose-600">{{ editForm.errors.status_pelanggan }}</p>
                            </div>

                            <!-- Conditional Fields for Berhenti Sementara -->
                            <div v-if="editForm.status_pelanggan === 'Berhenti sementara'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-600">
                                        Tanggal Mulai
                                    </label>
                                    <input
                                        v-model="editForm.suspend_start_date"
                                        type="date"
                                        class="w-full rounded-xl border border-slate-300 py-2.5 px-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                    />
                                    <p v-if="editForm.errors.suspend_start_date" class="mt-1 text-xs text-rose-600">{{ editForm.errors.suspend_start_date }}</p>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-600">
                                        Tanggal Selesai
                                    </label>
                                    <input
                                        v-model="editForm.suspend_end_date"
                                        type="date"
                                        class="w-full rounded-xl border border-slate-300 py-2.5 px-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                    />
                                    <p v-if="editForm.errors.suspend_end_date" class="mt-1 text-xs text-rose-600">{{ editForm.errors.suspend_end_date }}</p>
                                </div>
                            </div>

                            <!-- Conditional Field for Stop Permanen -->
                            <div v-if="editForm.status_pelanggan === 'Stop Permanen'">
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-600">
                                    Tanggal Berhenti
                                </label>
                                <input
                                    v-model="editForm.stop_date"
                                    type="date"
                                    class="w-full rounded-xl border border-slate-300 py-2.5 px-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                />
                                <p v-if="editForm.errors.stop_date" class="mt-1 text-xs text-rose-600">{{ editForm.errors.stop_date }}</p>
                            </div>

                            <!-- Pembayaran Terakhir -->
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-600">
                                    Pembayaran Terakhir
                                </label>
                                <input
                                    v-model="editForm.last_paid_date"
                                    type="date"
                                    class="w-full rounded-xl border border-slate-300 py-2.5 px-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                />
                                <p v-if="editForm.errors.last_paid_date" class="mt-1 text-xs text-rose-600">{{ editForm.errors.last_paid_date }}</p>
                            </div>

                            <!-- Biaya Bulanan (Base Amount) -->
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-600">
                                    Biaya Bulanan (Rp) <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="editForm.base_amount"
                                    type="number"
                                    min="0"
                                    class="w-full rounded-xl border border-slate-300 py-2.5 px-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                    required
                                />
                                <p v-if="editForm.errors.base_amount" class="mt-1 text-xs text-rose-600">{{ editForm.errors.base_amount }}</p>
                            </div>

                            <!-- Tagihan (Accumulated Amount) -->
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-600">
                                    Tagihan Saat Ini (Rp) <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="editForm.amount"
                                    type="number"
                                    min="0"
                                    class="w-full rounded-xl border border-slate-300 py-2.5 px-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                    required
                                />
                                <p v-if="editForm.errors.amount" class="mt-1 text-xs text-rose-600">{{ editForm.errors.amount }}</p>
                            </div>
                        </div>

                        <!-- Modal Actions -->
                        <div class="mt-6 flex items-center justify-end gap-3 pt-2">
                            <button
                                type="button"
                                @click="closeEditModal"
                                class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="editForm.processing"
                                class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-indigo-700 hover:shadow focus:outline-none focus:ring-2 focus:ring-indigo-500/30 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <svg
                                    v-if="editForm.processing"
                                    class="h-3.5 w-3.5 animate-spin text-white"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                <span>{{ editForm.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
