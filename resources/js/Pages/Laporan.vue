<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({
            start_date: '',
            end_date: '',
            area: '',
        }),
    },
    summary: {
        type: Object,
        default: () => ({
            income: 0,
            expense: 0,
            balance: 0,
        }),
    },
    transactions: {
        type: Array,
        default: () => [],
    },
    areas: {
        type: Array,
        default: () => [],
    },
    unpaid: {
        type: Object,
        default: () => ({
            count: 0,
            total: 0,
        }),
    },
    unpaid_list: {
        type: Array,
        default: () => [],
    },
    expenseCategories: {
        type: Array,
        default: () => [],
    },
    companyExpenseTypes: {
        type: Array,
        default: () => [],
    },
    materials: {
        type: Array,
        default: () => [],
    },
    paymentMethods: {
        type: Array,
        default: () => [],
    }
});

const form = useForm({
    start_date: props.filters?.start_date || '',
    end_date: props.filters?.end_date || '',
    area: props.filters?.area || '',
    kategori: props.filters?.kategori || '',
    company_expense_type_id: props.filters?.company_expense_type_id || '',
    material_id: props.filters?.material_id || '',
    payment_method: props.filters?.payment_method || '',
});

const handleFilter = () => {
    currentPage.value = 1;
    form.get(route('laporan'), {
        preserveState: true,
        preserveScroll: true,
    });
};

const handleReset = () => {
    currentPage.value = 1;
    form.start_date = '';
    form.end_date = '';
    form.area = '';
    form.kategori = '';
    form.company_expense_type_id = '';
    form.material_id = '';
    form.payment_method = '';
    form.get(route('laporan'), {
        preserveState: true,
        preserveScroll: true,
    });
};

const currentPage = ref(1);
const itemsPerPage = 25;

const paginatedTransactions = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    return props.transactions.slice(start, end);
});

const paginatedUnpaidList = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    return props.unpaid_list.slice(start, end);
});

const totalPages = computed(() => {
    const data = form.kategori === 'belum_lunas' ? props.unpaid_list : props.transactions;
    return Math.ceil(data.length / itemsPerPage) || 1;
});

const nextPage = () => {
    if (currentPage.value < totalPages.value) currentPage.value++;
};

const prevPage = () => {
    if (currentPage.value > 1) currentPage.value--;
};

const printReport = () => {
    window.print();
};

const exportExcel = async () => {
    const dataToExport = form.kategori === 'belum_lunas' ? props.unpaid_list : props.transactions;
    
    if (!dataToExport || dataToExport.length === 0) {
        alert("Tidak ada data untuk diexport");
        return;
    }
    
    let formattedData = [];
    
    if (form.kategori === 'belum_lunas') {
        formattedData = dataToExport.map((item, index) => ({
            'No': index + 1,
            'Nama Pelanggan': item.name,
            'Area': item.area || '-',
            'Status': 'Belum Lunas',
            'Jumlah Tagihan': item.amount
        }));
    } else {
        formattedData = dataToExport.map((item, index) => {
            let jenis = '';
            if (item.type === 'income') {
                jenis = item.income_source === 'voucher' ? 'Pemasukan (Voucher)' : (item.income_source === 'saldo' ? 'Pemasukan (Saldo)' : 'Pemasukan');
            } else {
                jenis = item.expense_category?.name || item.expenseCategory?.name || 'Pengeluaran';
                if (item.company_expense_type || item.companyExpenseType) {
                    jenis += ' - ' + (item.company_expense_type?.name || item.companyExpenseType?.name);
                }
                if (item.material) {
                    jenis += ' (' + item.material.name + ')';
                }
            }
            return {
                'No': index + 1,
                'Tanggal': item.date,
                'Deskripsi': item.description || '-',
                'Area': item.area || '-',
                'Metode Bayar': item.payment_method || '-',
                'Tanggal Bayar': item.paid_at ? formatDate(item.paid_at) : '-',
                'Jenis': jenis,
                'Jumlah': item.type === 'income' ? item.amount : -item.amount
            };
        });
    }

    const XLSX = await import('xlsx');
    const ws = XLSX.utils.json_to_sheet(formattedData);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "Laporan");
    
    const fileName = `Laporan_${form.kategori === 'belum_lunas' ? 'Belum_Lunas' : 'Transaksi'}_${new Date().toISOString().slice(0,10)}.xlsx`;
    XLSX.writeFile(wb, fileName);
};

const formatCurrency = (number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(number || 0);
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    try {
        const date = new Date(dateString);
        return new Intl.DateTimeFormat('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
        }).format(date);
    } catch {
        return dateString;
    }
};

const isPerusahaanCategoryFilter = computed(() => {
    if (!form.kategori.startsWith('cat_')) return false;
    const catId = form.kategori.replace('cat_', '');
    const selectedCat = props.expenseCategories.find(c => c.id == catId);
    return selectedCat && selectedCat.name && selectedCat.name.toLowerCase().includes('perusahaan');
});

const isMaterialSubCategoryFilter = computed(() => {
    if (!isPerusahaanCategoryFilter.value || !form.company_expense_type_id) return false;
    const selectedType = props.companyExpenseTypes.find(t => t.id == form.company_expense_type_id);
    return selectedType && selectedType.name && selectedType.name.toLowerCase() === 'material';
});

watch(() => form.kategori, () => {
    if (!isPerusahaanCategoryFilter.value) {
        form.company_expense_type_id = '';
        form.material_id = '';
    }
});

watch(() => form.company_expense_type_id, () => {
    if (!isMaterialSubCategoryFilter.value) {
        form.material_id = '';
    }
});
</script>

<template>
    <Head title="Laporan Keuangan" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="font-bold text-2xl text-slate-800 tracking-tight leading-tight">
                        Laporan Keuangan
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                        Ringkasan dan detail riwayat transaksi keuangan
                    </p>
                </div>

                <div class="print:hidden flex items-center gap-3">
                    <button
                        type="button"
                        @click="exportExcel"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 text-white text-xs sm:text-sm font-medium hover:bg-emerald-700 active:bg-emerald-800 shadow-sm transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                    >
                        <svg class="w-4 h-4 text-emerald-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Export Excel</span>
                    </button>
                    <button
                        type="button"
                        @click="printReport"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-900 text-white text-xs sm:text-sm font-medium hover:bg-slate-800 active:bg-slate-950 shadow-sm transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-slate-900/20"
                    >
                        <!-- Printer SVG Icon -->
                        <svg class="w-4 h-4 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        <span>Cetak Laporan</span>
                    </button>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- PRINT-ONLY HEADER -->
            <div class="hidden print:block text-center border-b border-slate-300 pb-4 mb-6">
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 uppercase">
                    LAPORAN KEUANGAN
                </h1>
                <div class="flex items-center justify-center gap-6 mt-2 text-xs sm:text-sm text-slate-700">
                    <p>
                        <span class="font-semibold text-slate-900">Periode:</span>
                        <template v-if="filters?.start_date || filters?.end_date">
                            {{ filters?.start_date ? formatDate(filters.start_date) : 'Awal' }} s/d {{ filters?.end_date ? formatDate(filters.end_date) : 'Sekarang' }}
                        </template>
                        <template v-else>
                            Semua Periode
                        </template>
                    </p>
                    <p v-if="filters?.area">
                        <span class="font-semibold text-slate-900">Area:</span>
                        {{ filters.area }}
                    </p>
                </div>
            </div>

            <!-- FILTER SECTION (print:hidden) -->
            <div class="print:hidden bg-white rounded-xl shadow-sm border border-slate-200/80 p-5">
                <form @submit.prevent="handleFilter" class="flex flex-col lg:flex-row lg:items-end gap-4">
                    <!-- Start Date -->
                    <div class="flex-1">
                        <label for="start_date" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">
                            Tanggal Mulai
                        </label>
                        <input
                            id="start_date"
                            v-model="form.start_date"
                            type="date"
                            class="w-full rounded-lg border-slate-200 text-xs sm:text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                        />
                    </div>

                    <!-- End Date -->
                    <div class="flex-1">
                        <label for="end_date" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">
                            Tanggal Selesai
                        </label>
                        <input
                            id="end_date"
                            v-model="form.end_date"
                            type="date"
                            class="w-full rounded-lg border-slate-200 text-xs sm:text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                        />
                    </div>

                    <!-- Kategori Filter -->
                    <div class="flex-1">
                        <label for="kategori" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">
                            Kategori
                        </label>
                        <select
                            id="kategori"
                            v-model="form.kategori"
                            class="w-full rounded-lg border-slate-200 text-xs sm:text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                        >
                            <option value="">Semua Transaksi</option>
                            <option value="income">Pemasukan</option>
                            <option value="expense">Pengeluaran</option>
                            <option value="belum_lunas">Pelanggan Belum Lunas</option>
                            <optgroup v-if="expenseCategories && expenseCategories.length" label="Pengeluaran Spesifik">
                                <option v-for="cat in expenseCategories" :key="cat.id" :value="'cat_' + cat.id">
                                    {{ cat.name }}
                                </option>
                            </optgroup>
                        </select>
                    </div>

                    <!-- Area Filter -->
                    <div class="flex-1">
                        <label for="area" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">
                            Area
                        </label>
                        <select
                            id="area"
                            v-model="form.area"
                            class="w-full rounded-lg border-slate-200 text-xs sm:text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                        >
                            <option value="">Semua Area</option>
                            <option v-for="a in areas" :key="a" :value="a">
                                {{ a }}
                            </option>
                        </select>
                    </div>

                    <!-- Payment Method Filter -->
                    <div class="flex-1">
                        <label for="payment_method" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">
                            Metode Bayar
                        </label>
                        <select
                            id="payment_method"
                            v-model="form.payment_method"
                            class="w-full rounded-lg border-slate-200 text-xs sm:text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                        >
                            <option value="">Semua Metode</option>
                            <option v-for="method in paymentMethods" :key="method.id" :value="method.name">
                                {{ method.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Jenis Pengeluaran Perusahaan Filter -->
                    <div v-if="isPerusahaanCategoryFilter" class="flex-1">
                        <label for="company_expense_type_id" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">
                            Jenis Pengeluaran
                        </label>
                        <select
                            id="company_expense_type_id"
                            v-model="form.company_expense_type_id"
                            class="w-full rounded-lg border-slate-200 text-xs sm:text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                        >
                            <option value="">Semua Jenis</option>
                            <option v-for="type in companyExpenseTypes" :key="type.id" :value="type.id">
                                {{ type.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Material Filter -->
                    <div v-if="isMaterialSubCategoryFilter" class="flex-1">
                        <label for="material_id" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">
                            Material
                        </label>
                        <select
                            id="material_id"
                            v-model="form.material_id"
                            class="w-full rounded-lg border-slate-200 text-xs sm:text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                        >
                            <option value="">Semua Material</option>
                            <option v-for="mat in materials" :key="mat.id" :value="mat.id">
                                {{ mat.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center gap-2 pt-1 lg:pt-0">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg bg-indigo-600 text-white text-xs sm:text-sm font-medium hover:bg-indigo-700 active:bg-indigo-800 shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-indigo-500/20 disabled:opacity-50"
                        >
                            <!-- Filter SVG Icon -->
                            <svg class="w-4 h-4 text-indigo-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                            <span>Tampilkan</span>
                        </button>

                        <button
                            v-if="form.start_date || form.end_date || form.area || form.kategori || form.payment_method"
                            type="button"
                            @click="handleReset"
                            class="inline-flex items-center justify-center px-4 py-2.5 rounded-lg bg-slate-100 text-slate-600 text-xs sm:text-sm font-medium hover:bg-slate-200 active:bg-slate-300 transition-all focus:outline-none"
                            title="Reset Filter"
                        >
                            <!-- Reset/X Icon -->
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </form>
            </div>

            <!-- SUMMARY CARDS (print:hidden) -->
            <div class="print:hidden grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5">
                <!-- Income Card (Emerald) -->
                <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 p-6 text-white shadow-sm transition-all duration-200 hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wider text-emerald-100">
                                Total Pemasukan
                            </p>
                            <h3 class="text-2xl font-bold tracking-tight text-white mt-1">
                                {{ formatCurrency(summary.income) }}
                            </h3>
                        </div>
                        <div class="rounded-xl bg-white/20 p-3 backdrop-blur-sm">
                            <!-- Arrow Down Left (Income) -->
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center text-xs text-emerald-100">
                        <span>Pemasukan pada periode terpilih</span>
                    </div>
                </div>

                <!-- Expense Card (Rose) -->
                <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-rose-500 to-red-600 p-6 text-white shadow-sm transition-all duration-200 hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wider text-rose-100">
                                Total Pengeluaran
                            </p>
                            <h3 class="text-2xl font-bold tracking-tight text-white mt-1">
                                {{ formatCurrency(summary.expense) }}
                            </h3>
                        </div>
                        <div class="rounded-xl bg-white/20 p-3 backdrop-blur-sm">
                            <!-- Arrow Up Right (Expense) -->
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center text-xs text-rose-100">
                        <span>Pengeluaran pada periode terpilih</span>
                    </div>
                </div>

                <!-- Balance Card (Indigo) -->
                <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-indigo-500 to-blue-600 p-6 text-white shadow-sm transition-all duration-200 hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wider text-indigo-100">
                                Saldo Bersih
                            </p>
                            <h3 class="text-2xl font-bold tracking-tight text-white mt-1">
                                {{ formatCurrency(summary.balance) }}
                            </h3>
                        </div>
                        <div class="rounded-xl bg-white/20 p-3 backdrop-blur-sm">
                            <!-- Scale / Wallet Icon -->
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center text-xs text-indigo-100">
                        <span>Selisih pemasukan dan pengeluaran</span>
                    </div>
                </div>

                <!-- Unpaid Card (Amber) -->
                <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-amber-500 to-orange-500 p-6 text-white shadow-sm transition-all duration-200 hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wider text-amber-100">
                                Belum Lunas
                            </p>
                            <h3 class="text-2xl font-bold tracking-tight text-white mt-1">
                                {{ formatCurrency(unpaid?.total || 0) }}
                            </h3>
                        </div>
                        <div class="rounded-xl bg-white/20 p-3 backdrop-blur-sm">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center text-xs text-amber-100">
                        <span>{{ unpaid?.count || 0 }} pelanggan belum bayar</span>
                    </div>
                </div>
            </div>

            <!-- TABLE SECTION -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 overflow-hidden">
                <div class="print:hidden p-5 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-slate-800">
                            {{ form.kategori === 'belum_lunas' ? 'Daftar Pelanggan Belum Lunas' : 'Daftar Transaksi' }}
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Menampilkan {{ form.kategori === 'belum_lunas' ? unpaid_list.length : transactions.length }} baris data
                        </p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <!-- TABLE BELUM LUNAS -->
                    <div class="overflow-x-auto w-full pb-4" v-if="form.kategori === 'belum_lunas'">
                        <table class="w-full text-left border-collapse print-table">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-200 text-[11px] font-semibold text-slate-600 uppercase tracking-wider">
                                <th scope="col" class="py-3.5 px-4 text-center w-12 whitespace-nowrap">#</th>
                                <th scope="col" class="py-3.5 px-4 whitespace-nowrap">Nama Pelanggan</th>
                                <th scope="col" class="py-3.5 px-4 whitespace-nowrap">Area</th>
                                <th scope="col" class="py-3.5 px-4 text-center whitespace-nowrap">Status</th>
                                <th scope="col" class="py-3.5 px-4 text-right whitespace-nowrap">Jumlah Tagihan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs sm:text-sm">
                            <tr
                                v-for="(item, index) in paginatedUnpaidList"
                                :key="item.id || index"
                                class="even:bg-slate-50/50 hover:bg-slate-100/60 transition-colors duration-150"
                            >
                                <td class="py-3.5 px-4 text-center text-xs text-slate-400 font-medium whitespace-nowrap">
                                    {{ index + 1 }}
                                </td>
                                <td class="py-3.5 px-4 text-slate-800 font-semibold whitespace-nowrap">
                                    {{ item.name }}
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap text-slate-600 text-xs">
                                    <span v-if="item.area" class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 font-medium text-[11px]">
                                        {{ item.area }}
                                    </span>
                                    <span v-else class="text-slate-400">-</span>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap text-center">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        Belum Lunas
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap text-right font-semibold text-rose-600">
                                    {{ formatCurrency(item.amount) }}
                                </td>
                            </tr>

                            <tr v-if="unpaid_list.length === 0">
                                <td colspan="5" class="py-12 text-center text-slate-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-12 h-12 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <p class="text-xs sm:text-sm font-medium text-slate-600">Tidak ada pelanggan belum lunas</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot v-if="unpaid_list.length > 0" class="bg-slate-50/80 border-t border-slate-200 font-semibold text-xs text-slate-700">
                            <tr>
                                <td colspan="4" class="py-3.5 px-4 text-right uppercase tracking-wider">
                                    Total Tagihan Belum Lunas:
                                </td>
                                <td class="py-3.5 px-4 text-right text-rose-600 font-bold">
                                    {{ formatCurrency(unpaid?.total) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
</div>

                    <!-- TABLE TRANSAKSI -->
                    <div class="overflow-x-auto w-full pb-4" v-else>
                        <table class="w-full text-left border-collapse print-table">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-200 text-[11px] font-semibold text-slate-600 uppercase tracking-wider">
                                <th scope="col" class="py-3.5 px-4 text-center w-12 whitespace-nowrap">#</th>
                                <th scope="col" class="py-3.5 px-4 whitespace-nowrap">Tanggal</th>
                                <th scope="col" class="py-3.5 px-4 whitespace-nowrap">Deskripsi</th>
                                <th scope="col" class="py-3.5 px-4 whitespace-nowrap">Area</th>
                                <th scope="col" class="py-3.5 px-4 whitespace-nowrap">Metode Bayar</th>
                                <th scope="col" class="py-3.5 px-4 whitespace-nowrap">Tanggal Bayar</th>
                                <th scope="col" class="py-3.5 px-4 text-center whitespace-nowrap">Jenis</th>
                                <th scope="col" class="py-3.5 px-4 text-right whitespace-nowrap">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs sm:text-sm">
                            <tr
                                v-for="(item, index) in paginatedTransactions"
                                :key="item.id || index"
                                class="even:bg-slate-50/50 hover:bg-slate-100/60 transition-colors duration-150"
                            >
                                <td class="py-3.5 px-4 text-center text-xs text-slate-400 font-medium">
                                    {{ index + 1 }}
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap text-slate-700 font-medium text-xs">
                                    {{ formatDate(item.date) }}
                                </td>
                                <td class="py-3.5 px-4 text-slate-800">
                                    <div class="font-medium line-clamp-2">
                                        {{ item.description || '-' }}
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap text-slate-600 text-xs">
                                    <span v-if="item.area" class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 font-medium text-[11px]">
                                        {{ item.area }}
                                    </span>
                                    <span v-else class="text-slate-400">-</span>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap text-slate-600 text-xs">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 font-medium text-[11px]">
                                        {{ item.payment_method || '-' }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap text-slate-600 text-xs">
                                    {{ item.paid_at ? formatDate(item.paid_at) : '-' }}
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap text-center">
                                    <span
                                        v-if="item.type === 'income'"
                                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        <template v-if="item.income_source === 'voucher'">Pemasukan (Voucher)</template>
                                        <template v-else-if="item.income_source === 'saldo'">Pemasukan (Saldo)</template>
                                        <template v-else>Pemasukan</template>
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-700"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        {{ item.expense_category?.name || item.expenseCategory?.name || 'Pengeluaran' }}
                                        <template v-if="item.company_expense_type || item.companyExpenseType">
                                            - {{ item.company_expense_type?.name || item.companyExpenseType?.name }}
                                        </template>
                                        <template v-if="item.material">
                                            ({{ item.material.name }})
                                        </template>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap text-right font-semibold">
                                    <span :class="item.type === 'income' ? 'text-emerald-600' : 'text-rose-600'">
                                        {{ item.type === 'income' ? '+' : '-' }} {{ formatCurrency(item.amount) }}
                                    </span>
                                </td>
                            </tr>

                            <tr v-if="transactions.length === 0">
                                <td colspan="8" class="py-12 text-center text-slate-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-12 h-12 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <p class="text-xs sm:text-sm font-medium text-slate-600">Tidak ada transaksi ditemukan</p>
                                        <p class="text-xs text-slate-400 mt-1">Coba sesuaikan rentang tanggal atau filter area</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>

                        <tfoot v-if="transactions.length > 0" class="bg-slate-50/80 border-t border-slate-200 font-semibold text-xs text-slate-700">
                            <tr>
                                <td colspan="7" class="py-3.5 px-4 text-right uppercase tracking-wider">
                                    Total Pemasukan:
                                </td>
                                <td class="py-3.5 px-4 text-right text-emerald-600 font-bold">
                                    {{ formatCurrency(summary.income) }}
                                </td>
                            </tr>
                            <tr>
                                <td colspan="7" class="py-3.5 px-4 text-right uppercase tracking-wider">
                                    Total Pengeluaran:
                                </td>
                                <td class="py-3.5 px-4 text-right text-rose-600 font-bold">
                                    {{ formatCurrency(summary.expense) }}
                                </td>
                            </tr>
                            <tr class="border-t border-slate-200 bg-slate-100/70">
                                <td colspan="7" class="py-3.5 px-4 text-right uppercase tracking-wider text-slate-900 font-bold">
                                    Saldo Bersih:
                                </td>
                                <td class="py-3.5 px-4 text-right font-bold text-slate-900">
                                    {{ formatCurrency(summary.balance) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Pagination Controls -->
                <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 flex items-center justify-between rounded-b-3xl">
                    <div class="text-sm text-slate-500">
                        Menampilkan halaman <span class="font-bold text-slate-700">{{ currentPage }}</span> dari <span class="font-bold text-slate-700">{{ totalPages }}</span>
                        <span class="ml-2">
                            (Total {{ form.kategori === 'belum_lunas' ? unpaid_list.length : transactions.length }} data)
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button 
                            @click="prevPage" 
                            :disabled="currentPage === 1"
                            class="px-3 py-1.5 border border-slate-300 rounded-lg text-sm font-medium text-slate-600 bg-white hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            Sebelumnya
                        </button>
                        <button 
                            @click="nextPage" 
                            :disabled="currentPage === totalPages"
                            class="px-3 py-1.5 border border-slate-300 rounded-lg text-sm font-medium text-slate-600 bg-white hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            Selanjutnya
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
    /* Hide non-printable elements */
    :deep(nav),
    :deep(aside),
    :deep(header) {
        display: none !important;
    }

    body {
        background: #ffffff !important;
        font-size: 11pt;
        color: #0f172a !important;
        margin: 0;
        padding: 0;
    }

    /* Print Table Styles */
    .print-table {
        width: 100% !important;
        border-collapse: collapse !important;
    }

    .print-table th,
    .print-table td {
        border: 1px solid #cbd5e1 !important;
        padding: 6px 10px !important;
        color: #0f172a !important;
    }

    .print-table thead tr {
        background-color: #f1f5f9 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .print-table tfoot tr {
        background-color: #f8fafc !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    /* Page break optimization */
    tr {
        page-break-inside: avoid;
    }
}
</style>
