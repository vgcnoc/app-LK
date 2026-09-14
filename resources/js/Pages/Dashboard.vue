<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Chart from 'chart.js/auto';

const props = defineProps({
    summary: {
        type: Object,
        default: () => ({ income: 0, expense: 0, balance: 0 }),
    },
    areaSummaries: {
        type: Array,
        default: () => [],
    },
    chart: {
        type: Object,
        default: () => ({ labels: [], income: [], expense: [] }),
    },
    transactions: {
        type: Array,
        default: () => [],
    },
    paymentMethods: {
        type: Array,
        default: () => [],
    },
    expenseCategories: {
        type: Array,
        default: () => [],
    },
    unpaid: {
        type: Object,
        default: () => ({ count: 0, total: 0 }),
    },
});

// Helper: Indonesian Currency Formatting
const formatRupiah = (number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(Number(number) || 0);
};

// Helper: Indonesian Date Formatting
const formattedTodayDate = computed(() => {
    return new Intl.DateTimeFormat('id-ID', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    }).format(new Date());
});

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    try {
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        return new Intl.DateTimeFormat('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
        }).format(d);
    } catch {
        return dateStr;
    }
};

const isIncome = (type) => {
    if (!type) return false;
    const t = String(type).toLowerCase();
    return t === 'income' || t === 'pemasukan';
};

// Chart.js Setup
const chartCanvas = ref(null);
let chartInstance = null;

const renderChart = () => {
    if (!chartCanvas.value) return;
    if (chartInstance) {
        chartInstance.destroy();
    }

    const ctx = chartCanvas.value.getContext('2d');
    chartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: props.chart?.labels || [],
            datasets: [
                {
                    label: 'Pemasukan',
                    data: props.chart?.income || [],
                    backgroundColor: 'rgba(16, 185, 129, 0.85)',
                    borderColor: 'rgb(16, 185, 129)',
                    borderWidth: 1,
                    borderRadius: 6,
                    maxBarThickness: 40,
                },
                {
                    label: 'Pengeluaran',
                    data: props.chart?.expense || [],
                    backgroundColor: 'rgba(244, 63, 94, 0.85)',
                    borderColor: 'rgb(244, 63, 94)',
                    borderWidth: 1,
                    borderRadius: 6,
                    maxBarThickness: 40,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    position: 'top',
                    align: 'end',
                    labels: {
                        usePointStyle: true,
                        boxWidth: 8,
                        boxHeight: 8,
                        padding: 16,
                        font: {
                            family: "'Inter', sans-serif",
                            size: 12,
                            weight: 500,
                        },
                    },
                },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.9)',
                    titleFont: { size: 13, weight: 'bold' },
                    bodyFont: { size: 12 },
                    padding: 12,
                    cornerRadius: 10,
                    boxPadding: 4,
                    callbacks: {
                        label: function (context) {
                            let label = context.dataset.label || '';
                            if (label) label += ': ';
                            if (context.parsed.y !== null) {
                                label += formatRupiah(context.parsed.y);
                            }
                            return label;
                        },
                    },
                },
            },
            scales: {
                x: {
                    grid: {
                        display: false,
                    },
                    ticks: {
                        color: '#64748b',
                        font: { size: 12 },
                    },
                },
                y: {
                    grid: {
                        color: '#f1f5f9',
                    },
                    ticks: {
                        color: '#64748b',
                        font: { size: 11 },
                        callback: function (val) {
                            return formatRupiah(val);
                        },
                    },
                },
            },
        },
    });
};

onMounted(() => {
    renderChart();
});

watch(
    () => props.chart,
    () => {
        renderChart();
    },
    { deep: true }
);

// Add Transaction Modal & Inertia Form
const showModal = ref(false);

const form = useForm({
    date: new Date().toISOString().split('T')[0],
    type: 'income',
    amount: '',
    description: '',
    area: '',
    payment_method: '',
    expense_category_id: '',
});

const openModal = () => {
    form.reset();
    form.clearErrors();
    form.date = new Date().toISOString().split('T')[0];
    form.type = 'income';
    if (props.paymentMethods && props.paymentMethods.length > 0) {
        form.payment_method = props.paymentMethods[0].name || props.paymentMethods[0].id;
    }
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
    form.clearErrors();
};

const submitForm = () => {
    const submitUrl = typeof route === 'function' ? route('transactions.store') : '/transactions';
    form.post(submitUrl, {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
        },
    });
};

// Delete Transaction
const deleteTransaction = (id) => {
    if (confirm('Apakah Anda yakin ingin menghapus transaksi ini?')) {
        const deleteUrl = typeof route === 'function' ? route('transactions.destroy', id) : `/transactions/${id}`;
        router.delete(deleteUrl, {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Dashboard Keuangan" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-slate-800 tracking-tight flex items-center gap-2">
                        Selamat Datang! 👋
                    </h2>
                    <p class="text-sm text-slate-500 mt-1 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        {{ formattedTodayDate }}
                    </p>
                </div>
                <div>
                    <button
                        @click="openModal"
                        type="button"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white text-sm font-semibold rounded-xl shadow-sm hover:shadow-md transition-all duration-200 transform hover:-translate-y-0.5 active:translate-y-0"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Tambah Transaksi</span>
                    </button>
                </div>
            </div>
        </template>

        <div class="py-8 bg-slate-50/50 min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
                              <!-- 1. Summary Cards Section -->
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
                    <!-- Income Card -->
                    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-emerald-500 to-emerald-600 p-6 text-white shadow hover:scale-105 hover:shadow-lg transition-all duration-300">
                        <div class="relative z-10 flex items-center justify-between">
                            <div>
                                <p class="text-xs font-semibold text-emerald-100 uppercase tracking-wider">Total Pemasukan</p>
                                <h3 class="mt-2 text-2xl lg:text-3xl font-bold tracking-tight">
                                    {{ formatRupiah(summary.income) }}
                                </h3>
                                <p class="mt-2 text-xs text-emerald-100/80">Akumulasi seluruh penerimaan kas</p>
                            </div>
                            <div class="rounded-xl bg-white/20 p-3.5 backdrop-blur-xs">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                            </div>
                        </div>
                        <div class="absolute -right-4 -bottom-4 text-white opacity-10 pointer-events-none transform rotate-12">
                            <svg class="w-32 h-32" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>

                    <!-- Expense Card -->
                    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-rose-500 to-rose-600 p-6 text-white shadow hover:scale-105 hover:shadow-lg transition-all duration-300">
                        <div class="relative z-10 flex items-center justify-between">
                            <div>
                                <p class="text-xs font-semibold text-rose-100 uppercase tracking-wider">Total Pengeluaran</p>
                                <h3 class="mt-2 text-2xl lg:text-3xl font-bold tracking-tight">
                                    {{ formatRupiah(summary.expense) }}
                                </h3>
                                <p class="mt-2 text-xs text-rose-100/80">Akumulasi seluruh biaya &amp; beban</p>
                            </div>
                            <div class="rounded-xl bg-white/20 p-3.5 backdrop-blur-xs">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                                </svg>
                            </div>
                        </div>
                        <div class="absolute -right-4 -bottom-4 text-white opacity-10 pointer-events-none transform rotate-12">
                            <svg class="w-32 h-32" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                        </div>
                    </div>

                    <!-- Balance Card -->
                    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-indigo-500 to-indigo-600 p-6 text-white shadow hover:scale-105 hover:shadow-lg transition-all duration-300">
                        <div class="relative z-10 flex items-center justify-between">
                            <div>
                                <p class="text-xs font-semibold text-indigo-100 uppercase tracking-wider">Sisa Saldo Kas</p>
                                <h3 class="mt-2 text-2xl lg:text-3xl font-bold tracking-tight">
                                    {{ formatRupiah(summary.balance) }}
                                </h3>
                                <p class="mt-2 text-xs text-indigo-100/80">Net saldo aktif saat ini</p>
                            </div>
                            <div class="rounded-xl bg-white/20 p-3.5 backdrop-blur-xs">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                                </svg>
                            </div>
                        </div>
                        <div class="absolute -right-4 -bottom-4 text-white opacity-10 pointer-events-none transform rotate-12">
                            <svg class="w-32 h-32" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                    </div>

                    <!-- Belum Lunas Card -->
                    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-amber-500 to-orange-500 p-6 text-white shadow hover:scale-105 hover:shadow-lg transition-all duration-300">
                        <div class="relative z-10 flex items-center justify-between">
                            <div>
                                <p class="text-xs font-semibold text-amber-100 uppercase tracking-wider">Belum Lunas</p>
                                <h3 class="mt-2 text-2xl lg:text-3xl font-bold tracking-tight">
                                    {{ formatRupiah(unpaid.total) }}
                                </h3>
                                <p class="mt-2 text-xs text-amber-100/80">{{ unpaid.count }} pelanggan belum bayar</p>
                            </div>
                            <div class="rounded-xl bg-white/20 p-3.5 backdrop-blur-xs">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="absolute -right-4 -bottom-4 text-white opacity-10 pointer-events-none transform rotate-12">
                            <svg class="w-32 h-32" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- 2. Area Summaries Section -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-slate-800">Ringkasan Area</h3>
                            <p class="text-xs text-slate-500">Distribusi keuangan berdasarkan wilayah operasional</p>
                        </div>
                    </div>

                    <div v-if="areaSummaries && areaSummaries.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        <div
                            v-for="(area, index) in areaSummaries"
                            :key="index"
                            class="bg-white rounded-xl shadow-sm border border-slate-100 border-l-4 border-indigo-400 p-4 transition-all duration-200 hover:shadow-md"
                        >
                            <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                                <h4 class="font-semibold text-slate-800 text-sm flex items-center gap-1.5 truncate" :title="area.area_name || area.area || area.name">
                                    <svg class="w-4 h-4 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <span class="truncate">{{ area.area_name || area.area || area.name || 'Area Tanpa Nama' }}</span>
                                </h4>
                            </div>
                            <div class="mt-3 space-y-1.5 text-xs">
                                <div class="flex items-center justify-between text-slate-600">
                                    <span>Pemasukan</span>
                                    <span class="font-semibold text-emerald-600">{{ formatRupiah(area.total_income) }}</span>
                                </div>
                                <div class="flex items-center justify-between text-slate-600">
                                    <span>Pengeluaran</span>
                                    <span class="font-semibold text-rose-600">{{ formatRupiah(area.total_expense) }}</span>
                                </div>
                                <div class="pt-2 border-t border-slate-100 flex items-center justify-between font-medium">
                                    <span class="text-slate-500">Saldo Net</span>
                                    <span :class="(Number(area.total_income || 0) - Number(area.total_expense || 0)) >= 0 ? 'text-indigo-600 font-bold' : 'text-rose-600 font-bold'">
                                        {{ formatRupiah(Number(area.total_income || 0) - Number(area.total_expense || 0)) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="bg-white rounded-xl border border-slate-100 p-6 text-center text-sm text-slate-400">
                        Belum ada data ringkasan area yang tersedia.
                    </div>
                </div>

                <!-- 3. Chart Section -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 transition-all hover:shadow-md">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-4 mb-4 border-b border-slate-100 gap-2">
                        <div>
                            <h3 class="text-lg font-bold text-slate-800">Statistik Arus Kas</h3>
                            <p class="text-xs text-slate-500">Perbandingan pemasukan dan pengeluaran per periode</p>
                        </div>
                        <div class="flex items-center gap-3 text-xs text-slate-500">
                            <span class="inline-flex items-center gap-1.5">
                                <span class="w-3 h-3 rounded-sm bg-emerald-500"></span> Pemasukan
                            </span>
                            <span class="inline-flex items-center gap-1.5">
                                <span class="w-3 h-3 rounded-sm bg-rose-500"></span> Pengeluaran
                            </span>
                        </div>
                    </div>
                    <div class="relative h-72 sm:h-80 w-full">
                        <canvas ref="chartCanvas"></canvas>
                    </div>
                </div>

                <!-- 4. Transactions Table Section -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                    <div class="p-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-lg font-bold text-slate-800">Daftar Transaksi</h3>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700">
                                    {{ transactions ? transactions.length : 0 }} Data
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 mt-0.5">Riwayat catatan kas masuk dan keluar</p>
                        </div>
                        <button
                            @click="openModal"
                            type="button"
                            class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-semibold rounded-lg transition-colors"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>Transaksi Baru</span>
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm border-collapse">
                            <thead class="bg-slate-50/75 text-xs font-semibold text-slate-500 uppercase tracking-wider border-b border-slate-100">
                                <tr>
                                    <th scope="col" class="px-6 py-3.5">Tanggal</th>
                                    <th scope="col" class="px-6 py-3.5">Keterangan</th>
                                    <th scope="col" class="px-6 py-3.5">Area</th>
                                    <th scope="col" class="px-6 py-3.5">Metode Bayar</th>
                                    <th scope="col" class="px-6 py-3.5">Tipe</th>
                                    <th scope="col" class="px-6 py-3.5 text-right">Jumlah</th>
                                    <th scope="col" class="px-6 py-3.5 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                <tr
                                    v-for="item in transactions"
                                    :key="item.id"
                                    class="even:bg-gray-50/50 hover:bg-indigo-50/30 transition-colors"
                                >
                                    <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-500 font-medium">
                                        {{ formatDate(item.date) }}
                                    </td>
                                    <td class="px-6 py-4 font-medium text-slate-900 max-w-xs truncate" :title="item.description">
                                        {{ item.description || '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-600">
                                        <span class="inline-flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            </svg>
                                            {{ item.area || '-' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                                            {{ item.payment_method || '-' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            v-if="isIncome(item.type)"
                                            class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20"
                                        >
                                            <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                            </svg>
                                            Pemasukan
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-600/20"
                                        >
                                            <svg class="w-3 h-3 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                            </svg>
                                            Pengeluaran
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right font-semibold">
                                        <span :class="isIncome(item.type) ? 'text-emerald-600' : 'text-rose-600'">
                                            {{ isIncome(item.type) ? '+' : '-' }} {{ formatRupiah(item.amount) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <button
                                            @click="deleteTransaction(item.id)"
                                            type="button"
                                            title="Hapus Transaksi"
                                            class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="!transactions || transactions.length === 0">
                                    <td colspan="7" class="px-6 py-12 text-center text-slate-400 text-sm">
                                        <svg class="w-12 h-12 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        Belum ada catatan transaksi.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

        <!-- 5. Add Transaction Modal -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto">
                <!-- Smooth Overlay with Backdrop Blur -->
                <div class="fixed inset-0 bg-black/40 backdrop-blur-sm transition-opacity" @click="closeModal"></div>

                <div class="flex min-h-full items-center justify-center p-4 text-center">
                    <Transition
                        enter-active-class="transition duration-200 ease-out"
                        enter-from-class="opacity-0 scale-95 translate-y-2"
                        enter-to-class="opacity-100 scale-100 translate-y-0"
                        leave-active-class="transition duration-150 ease-in"
                        leave-from-class="opacity-100 scale-100 translate-y-0"
                        leave-to-class="opacity-0 scale-95 translate-y-2"
                    >
                        <div v-if="showModal" class="relative w-full max-w-lg transform overflow-hidden rounded-2xl bg-white p-6 text-left shadow-2xl transition-all border border-slate-100">
                            <!-- Modal Header -->
                            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                                <div>
                                    <h3 class="text-lg font-bold text-slate-800">Tambah Transaksi</h3>
                                    <p class="text-xs text-slate-500 mt-0.5">Catat arus keuangan baru ke dalam sistem</p>
                                </div>
                                <button
                                    @click="closeModal"
                                    type="button"
                                    class="p-1.5 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-colors"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Form -->
                            <form @submit.prevent="submitForm" class="mt-4 space-y-4">
                                <!-- Type Selector -->
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Tipe Transaksi</label>
                                    <div class="grid grid-cols-2 gap-3">
                                        <button
                                            type="button"
                                            @click="form.type = 'income'"
                                            :class="form.type === 'income' ? 'bg-emerald-50 border-emerald-500 text-emerald-700 ring-2 ring-emerald-500/20 font-semibold' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100'"
                                            class="flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl border text-sm transition-all"
                                        >
                                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                            </svg>
                                            Pemasukan
                                        </button>
                                        <button
                                            type="button"
                                            @click="form.type = 'expense'"
                                            :class="form.type === 'expense' ? 'bg-rose-50 border-rose-500 text-rose-700 ring-2 ring-rose-500/20 font-semibold' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100'"
                                            class="flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl border text-sm transition-all"
                                        >
                                            <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                                            </svg>
                                            Pengeluaran
                                        </button>
                                    </div>
                                    <p v-if="form.errors.type" class="mt-1 text-xs text-rose-600">{{ form.errors.type }}</p>
                                </div>

                                <!-- Date & Amount -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label for="date" class="block text-xs font-medium text-slate-700 mb-1">Tanggal</label>
                                        <input
                                            id="date"
                                            v-model="form.date"
                                            type="date"
                                            required
                                            class="w-full rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        />
                                        <p v-if="form.errors.date" class="mt-1 text-xs text-rose-600">{{ form.errors.date }}</p>
                                    </div>

                                    <div>
                                        <label for="amount" class="block text-xs font-medium text-slate-700 mb-1">Jumlah (Rp)</label>
                                        <input
                                            id="amount"
                                            v-model="form.amount"
                                            type="number"
                                            min="0"
                                            step="any"
                                            placeholder="Contoh: 100000"
                                            required
                                            class="w-full rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        />
                                        <p v-if="form.errors.amount" class="mt-1 text-xs text-rose-600">{{ form.errors.amount }}</p>
                                    </div>
                                </div>

                                <!-- Description -->
                                <div>
                                    <label for="description" class="block text-xs font-medium text-slate-700 mb-1">Keterangan</label>
                                    <input
                                        id="description"
                                        v-model="form.description"
                                        type="text"
                                        placeholder="Keterangan transaksi..."
                                        required
                                        class="w-full rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    />
                                    <p v-if="form.errors.description" class="mt-1 text-xs text-rose-600">{{ form.errors.description }}</p>
                                </div>

                                <!-- Kategori Pengeluaran -->
                                <div v-if="form.type === 'expense'">
                                    <label for="expense_category_id" class="block text-xs font-medium text-slate-700 mb-1">Kategori Pengeluaran</label>
                                    <select
                                        id="expense_category_id"
                                        v-model="form.expense_category_id"
                                        class="w-full rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        <option value="">-- Pilih Kategori --</option>
                                        <option v-for="cat in expenseCategories" :key="cat.id" :value="cat.id">
                                            {{ cat.name }}
                                        </option>
                                    </select>
                                    <p v-if="form.errors.expense_category_id" class="mt-1 text-xs text-rose-600">{{ form.errors.expense_category_id }}</p>
                                </div>

                                <!-- Area & Payment Method -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label for="area" class="block text-xs font-medium text-slate-700 mb-1">Area / Cabang</label>
                                        <input
                                            id="area"
                                            v-model="form.area"
                                            list="areaOptions"
                                            type="text"
                                            placeholder="Nama area..."
                                            class="w-full rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        />
                                        <datalist id="areaOptions">
                                            <option
                                                v-for="(area, i) in areaSummaries"
                                                :key="i"
                                                :value="area.area_name || area.area || area.name"
                                            />
                                        </datalist>
                                        <p v-if="form.errors.area" class="mt-1 text-xs text-rose-600">{{ form.errors.area }}</p>
                                    </div>

                                    <div>
                                        <label for="payment_method" class="block text-xs font-medium text-slate-700 mb-1">Metode Pembayaran</label>
                                        <select
                                            id="payment_method"
                                            v-model="form.payment_method"
                                            required
                                            class="w-full rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                            <option value="" disabled>Pilih Metode Bayar</option>
                                            <option
                                                v-for="pm in paymentMethods"
                                                :key="pm.id"
                                                :value="pm.name || pm.id"
                                            >
                                                {{ pm.name || pm.id }}
                                            </option>
                                        </select>
                                        <p v-if="form.errors.payment_method" class="mt-1 text-xs text-rose-600">{{ form.errors.payment_method }}</p>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                                    <button
                                        type="button"
                                        @click="closeModal"
                                        class="px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 rounded-xl transition-colors"
                                    >
                                        Batal
                                    </button>
                                    <button
                                        type="submit"
                                        :disabled="form.processing"
                                        class="inline-flex items-center gap-2 px-5 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 rounded-xl shadow-sm hover:shadow transition-all disabled:opacity-50"
                                    >
                                        <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Transaksi' }}</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </Transition>
                </div>
            </div>
        </Transition>
    </AuthenticatedLayout>
</template>