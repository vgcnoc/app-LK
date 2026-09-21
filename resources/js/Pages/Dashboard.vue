<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { Head, useForm, router, Link, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Chart from 'chart.js/auto';

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({ area: '' }),
    },
    areas: {
        type: Array,
        default: () => [],
    },
    summary: {
        type: Object,
        default: () => ({ income: 0, expense: 0, balance: 0 }),
    },
    percentages: {
        type: Object,
        default: () => ({ income: 0, expense: 0, balance: 0 }),
    },
    customerStats: {
        type: Object,
        default: () => ({ total: 0, aktif: 0, nonaktif: 0, suspend: 0, janji_bayar: 0, pasang_berbayar: 0, pasang_gratis: 0 }),
    },
    recentCustomers: {
        type: Array,
        default: () => [],
    },
    overdueBills: {
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
    unpaid: {
        type: Object,
        default: () => ({ count: 0, total: 0 }),
    },
    globalInstallationFee: {
        type: [Number, String],
        default: 0
    },
    paketStats: {
        type: Array,
        default: () => [],
    },
    areaSummaries: {
        type: Array,
        default: () => [],
    },
    paymentMethods: {
        type: Array,
        default: () => [],
    },
});

const permissions = computed(() => usePage().props.auth?.permissions || []);
const can = (perm) => permissions.value.includes(perm);

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

const getDiffDays = (dateStr) => {
    if (!dateStr) return null;
    const target = new Date(dateStr).getTime();
    const now = new Date().getTime();
    const diff = target - now;
    return Math.ceil(diff / (1000 * 3600 * 24));
};

const getDueBadge = (dateStr) => {
    const diff = getDiffDays(dateStr);
    if (diff === null) return { text: 'Terlambat', class: 'bg-rose-100 text-rose-600' };
    if (diff < 0) return { text: 'Terlambat', class: 'bg-rose-100 text-rose-600' };
    if (diff === 0) return { text: 'Hari Ini', class: 'bg-amber-100 text-amber-600' };
    return { text: `${diff} Hari`, class: 'bg-blue-100 text-blue-600' };
};

// Chart.js Setup for Bar Chart (Arus Kas)
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
                    borderRadius: 4,
                    maxBarThickness: 24,
                },
                {
                    label: 'Pengeluaran',
                    data: props.chart?.expense || [],
                    backgroundColor: 'rgba(244, 63, 94, 0.85)',
                    borderColor: 'rgb(244, 63, 94)',
                    borderWidth: 1,
                    borderRadius: 4,
                    maxBarThickness: 24,
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
                legend: { display: false },
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
                    grid: { display: false },
                    ticks: { color: '#94a3b8', font: { size: 11 } },
                },
                y: {
                    grid: { color: '#f1f5f9', drawBorder: false },
                    ticks: {
                        color: '#94a3b8',
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

// Donut Chart
const donutCanvas = ref(null);
let donutInstance = null;

const renderDonut = () => {
    if (!donutCanvas.value) return;
    if (donutInstance) donutInstance.destroy();
    
    const ctx = donutCanvas.value.getContext('2d');
    donutInstance = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Aktif', 'Nonaktif', 'Suspend', 'Janji Bayar'],
            datasets: [{
                data: [
                    props.customerStats?.aktif || 0,
                    props.customerStats?.nonaktif || 0,
                    props.customerStats?.suspend || 0,
                    props.customerStats?.janji_bayar || 0
                ],
                backgroundColor: [
                    '#10b981', // Aktif - Green
                    '#f43f5e', // Nonaktif - Red
                    '#f59e0b', // Suspend - Amber
                    '#8b5cf6'  // Trial - Purple
                ],
                borderWidth: 0,
                cutout: '75%',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.9)',
                    titleFont: { size: 13, weight: 'bold' },
                    bodyFont: { size: 12 },
                    padding: 12,
                    cornerRadius: 10,
                    callbacks: {
                        label: function(context) {
                            return ` ${context.label}: ${context.raw} Pelanggan`;
                        }
                    }
                }
            }
        }
    });
};

const formFilters = ref({
    area: props.filters?.area || ''
});

const applyFilter = () => {
    router.get(route('dashboard'), { area: formFilters.value.area }, { preserveState: true, preserveScroll: true });
};

// Paket Chart
const paketCanvas = ref(null);
let paketInstance = null;

const renderPaketDonut = () => {
    if (!paketCanvas.value) return;
    if (paketInstance) paketInstance.destroy();
    
    if (!props.paketStats || props.paketStats.length === 0) return;

    const ctx = paketCanvas.value.getContext('2d');
    
    const colors = [
        '#f59e0b', // Amber 500
        '#64748b', // Slate 500
        '#fb923c', // Orange 400
        '#60a5fa', // Blue 400
        '#a78bfa', // Purple 400
        '#34d399', // Emerald 400
    ];

    paketInstance = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: props.paketStats.map(p => p.paket),
            datasets: [{
                data: props.paketStats.map(p => p.total),
                backgroundColor: colors.slice(0, props.paketStats.length),
                borderWidth: 0,
                cutout: '70%',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.9)',
                    titleFont: { size: 13, weight: 'bold' },
                    bodyFont: { size: 12 },
                    padding: 12,
                    cornerRadius: 10,
                    callbacks: {
                        label: function(context) {
                            return ` ${context.label}: ${context.raw} Pelanggan`;
                        }
                    }
                }
            }
        }
    });
};

onMounted(() => {
    renderChart();
    renderDonut();
    renderPaketDonut();
});

watch(
    () => props.chart,
    () => { renderChart(); },
    { deep: true }
);

watch(
    () => props.customerStats,
    () => { renderDonut(); },
    { deep: true }
);

watch(
    () => props.paketStats,
    () => { renderPaketDonut(); },
    { deep: true }
);

</script>

<template>
    <Head title="Dashboard Keuangan" />

    <AuthenticatedLayout>
        <template #header>
            <div class="hidden sm:flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex-1 min-w-0">
                    <h2 class="text-xl sm:text-3xl font-bold text-slate-800 tracking-tight break-words">
                        Selamat Datang, {{ $page.props.auth.user.name }}! 👋
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1 break-words">
                        Pantau bisnis internet Anda dalam satu dashboard.
                    </p>
                </div>
                <div class="flex items-center gap-2 bg-white border border-slate-200 px-4 py-2.5 rounded-xl shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span class="text-xs sm:text-sm font-medium text-slate-700">{{ formattedTodayDate }}</span>
                </div>
            </div>
        </template>



        <div class="py-4 sm:py-8 bg-slate-50 min-h-screen">
            <div class="max-w-full mx-auto space-y-4 sm:space-y-6">
                
                <!-- Mobile Welcome Banner -->
                <div class="sm:hidden relative overflow-hidden bg-blue-50 rounded-2xl p-5 mb-4">
                    <div class="relative z-10 w-2/3">
                        <p class="text-xs sm:text-sm text-slate-600 mb-1">Selamat datang,</p>
                        <h2 class="text-xl font-bold text-slate-900 leading-tight">
                            {{ $page.props.auth.user.name }} 👋
                        </h2>
                        <p class="text-xs text-slate-500 mt-2">Pantau bisnis internet Anda dalam satu dashboard.</p>
                    </div>
                    <!-- Illustration Placeholder (Circles for effect) -->
                    <div class="absolute right-0 top-0 h-full w-1/3 flex items-center justify-center pointer-events-none opacity-80">
                        <div class="w-24 h-24 bg-blue-100 rounded-full flex items-center justify-center">
                            <svg class="w-12 h-12 text-blue-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path></svg>
                        </div>
                    </div>
                </div>

                <!-- Mobile Date Picker -->
                <div class="sm:hidden flex items-center justify-between bg-white border border-slate-200 px-2 py-2 sm:px-4 sm:py-3 rounded-2xl shadow-sm">
                    <div class="flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span class="text-xs sm:text-sm font-medium text-slate-700">{{ formattedTodayDate }}</span>
                    </div>
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                </div>

                <!-- 1. Top 4 Cards -->
                <div class="grid grid-cols-2 md:grid-cols-2 xl:grid-cols-4 gap-3 sm:gap-4">
                    <!-- Income Card -->
                    <div class="relative overflow-hidden rounded-2xl bg-emerald-50 sm:bg-gradient-to-br sm:from-emerald-100 sm:to-emerald-50 p-4 sm:p-6 border border-emerald-100 min-w-0">
                        <div class="relative z-10 flex flex-col h-full">
                            <div class="flex items-center gap-2 mb-2 sm:mb-3">
                                <div class="bg-emerald-500 text-white p-1.5 sm:p-3 rounded-lg sm:rounded-xl shadow-sm flex-shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-6 sm:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                    </svg>
                                </div>
                                <p class="text-[10px] sm:text-xs font-bold text-emerald-800 uppercase tracking-wider truncate">Total Pemasukan</p>
                            </div>
                            <h3 class="text-lg sm:text-3xl font-black text-emerald-900 tracking-tight truncate">
                                {{ formatRupiah(summary.income) }}
                            </h3>
                            <div class="mt-auto pt-2 flex flex-wrap items-center gap-1.5 sm:gap-2 text-[9px] sm:text-xs">
                                <span class="inline-flex items-center gap-0.5 sm:gap-1 font-semibold text-emerald-700 bg-emerald-200/50 px-1 sm:px-2 py-0.5 rounded-md whitespace-nowrap">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-2 w-2 sm:h-3 sm:w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18" /></svg>
                                    {{ percentages.income }}%
                                </span>
                                <span class="text-emerald-600/80 truncate flex-1 min-w-0 leading-tight">dari periode sebelumnya</span>
                            </div>
                        </div>
                    </div>

                    <!-- Expense Card -->
                    <div class="relative overflow-hidden rounded-2xl bg-rose-50 sm:bg-gradient-to-br sm:from-rose-100 sm:to-rose-50 p-4 sm:p-6 border border-rose-100 min-w-0">
                        <div class="relative z-10 flex flex-col h-full">
                            <div class="flex items-center gap-2 mb-2 sm:mb-3">
                                <div class="bg-rose-500 text-white p-1.5 sm:p-3 rounded-lg sm:rounded-xl shadow-sm flex-shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-6 sm:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                    </svg>
                                </div>
                                <p class="text-[10px] sm:text-xs font-bold text-rose-800 uppercase tracking-wider truncate">Total Pengeluaran</p>
                            </div>
                            <h3 class="text-lg sm:text-3xl font-black text-rose-900 tracking-tight truncate">
                                {{ formatRupiah(summary.expense) }}
                            </h3>
                            <div class="mt-auto pt-2 flex flex-wrap items-center gap-1.5 sm:gap-2 text-[9px] sm:text-xs">
                                <span class="inline-flex items-center gap-0.5 sm:gap-1 font-semibold text-rose-700 bg-rose-200/50 px-1 sm:px-2 py-0.5 rounded-md whitespace-nowrap">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-2 w-2 sm:h-3 sm:w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18" /></svg>
                                    {{ percentages.expense }}%
                                </span>
                                <span class="text-rose-600/80 truncate flex-1 min-w-0 leading-tight">dari periode sebelumnya</span>
                            </div>
                        </div>
                    </div>

                    <!-- Balance Card -->
                    <div class="relative overflow-hidden rounded-2xl bg-blue-50 sm:bg-gradient-to-br sm:from-blue-100 sm:to-blue-50 p-4 sm:p-6 border border-blue-100 min-w-0">
                        <div class="relative z-10 flex flex-col h-full">
                            <div class="flex items-center gap-2 mb-2 sm:mb-3">
                                <div class="bg-blue-500 text-white p-1.5 sm:p-3 rounded-lg sm:rounded-xl shadow-sm flex-shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-6 sm:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                                    </svg>
                                </div>
                                <p class="text-[10px] sm:text-xs font-bold text-blue-800 uppercase tracking-wider truncate">Sisa Saldo Kas</p>
                            </div>
                            <h3 class="text-lg sm:text-3xl font-black text-blue-900 tracking-tight truncate">
                                {{ formatRupiah(summary.balance) }}
                            </h3>
                            <div class="mt-auto pt-2 flex flex-wrap items-center gap-1.5 sm:gap-2 text-[9px] sm:text-xs">
                                <span class="inline-flex items-center gap-0.5 sm:gap-1 font-semibold text-blue-700 bg-blue-200/50 px-1 sm:px-2 py-0.5 rounded-md whitespace-nowrap">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-2 w-2 sm:h-3 sm:w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18" /></svg>
                                    {{ percentages.balance }}%
                                </span>
                                <span class="text-blue-600/80 truncate flex-1 min-w-0 leading-tight">saldo saat ini</span>
                            </div>
                        </div>
                    </div>

                    <!-- Unpaid Card -->
                    <div class="relative overflow-hidden rounded-2xl bg-amber-50 sm:bg-gradient-to-br sm:from-amber-100 sm:to-amber-50 p-4 sm:p-6 border border-amber-100 min-w-0">
                        <div class="relative z-10 flex flex-col h-full">
                            <div class="flex items-center gap-2 mb-2 sm:mb-3">
                                <div class="bg-amber-500 text-white p-1.5 sm:p-3 rounded-lg sm:rounded-xl shadow-sm flex-shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-6 sm:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <p class="text-[10px] sm:text-xs font-bold text-amber-800 uppercase tracking-wider truncate">Belum Lunas</p>
                            </div>
                            <h3 class="text-lg sm:text-3xl font-black text-amber-900 tracking-tight truncate">
                                {{ formatRupiah(unpaid.total) }}
                            </h3>
                            <div class="mt-auto pt-2 flex flex-wrap items-center gap-1.5 sm:gap-2 text-[9px] sm:text-xs">
                                <span class="text-amber-700 font-medium truncate flex-1 min-w-0">{{ unpaid.count }} pelanggan belum bayar</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Charts Section -->
                <div class="grid grid-cols-1 xl:grid-cols-4 lg:grid-cols-3 gap-6">
                    <!-- Bar Chart -->
                    <div class="xl:col-span-2 lg:col-span-2 bg-white rounded-2xl p-4 sm:p-6 border border-slate-100 shadow-sm min-w-0">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 sm:gap-0 mb-6">
                            <div class="flex items-center gap-3">
                                <div class="bg-indigo-50 text-indigo-500 p-2 rounded-lg">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-base font-bold text-slate-800 truncate">Statistik Arus Kas</h3>
                                    <p class="text-[10px] sm:text-xs text-slate-500 truncate">Perbandingan pemasukan dan pengeluaran</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 sm:gap-4">
                                <select class="text-xs border-slate-200 rounded-lg py-1.5 pl-3 pr-8 text-slate-600 focus:ring-indigo-500 focus:border-indigo-500">
                                    <option>6 Bulan</option>
                                </select>
                            </div>
                        </div>
                        <div class="relative h-48 sm:h-64 w-full">
                            <canvas ref="chartCanvas"></canvas>
                        </div>
                        <div class="flex sm:hidden justify-center items-center gap-4 text-[10px] text-slate-500 mt-2">
                            <span class="inline-flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Pemasukan
                            </span>
                            <span class="inline-flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-rose-500"></span> Pengeluaran
                            </span>
                        </div>
                    </div>

                    <!-- Mobile Menu Cepat (Quick Menu) -->
                    <div class="sm:hidden mt-2">
                        <div class="flex items-center justify-between mb-4 px-1">
                            <h3 class="text-base font-bold text-slate-800">Menu Cepat</h3>
                            <Link href="#" class="text-xs font-medium text-blue-600 hover:text-blue-700 flex items-center">Lihat Semua <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg></Link>
                        </div>
                        <div class="flex justify-between items-center bg-transparent gap-2">
                            <!-- Menu 1: Pelanggan -->
                            <Link v-if="can('akses_data_pelanggan')" :href="route('pelanggan.index')" class="flex flex-col items-center gap-2 w-16">
                                <div class="w-14 h-14 bg-emerald-50 border border-emerald-100 rounded-2xl shadow-sm flex items-center justify-center text-emerald-600">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                </div>
                                <span class="text-[10px] text-slate-700 font-medium text-center leading-tight">Pelanggan</span>
                            </Link>
                            
                            <!-- Menu 2: Tagihan (Billing) -->
                            <Link v-if="can('akses_billing')" :href="route('billing.index')" class="flex flex-col items-center gap-2 w-16">
                                <div class="w-14 h-14 bg-blue-50 border border-blue-100 rounded-2xl shadow-sm flex items-center justify-center text-blue-600">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </div>
                                <span class="text-[10px] text-slate-700 font-medium text-center leading-tight">Tagihan</span>
                            </Link>
                            
                            <!-- Menu 3: Transaksi -->
                            <Link v-if="can('akses_transaksi')" :href="route('transaksi')" class="flex flex-col items-center gap-2 w-16">
                                <div class="w-14 h-14 bg-amber-50 border border-amber-100 rounded-2xl shadow-sm flex items-center justify-center text-amber-500">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </div>
                                <span class="text-[10px] text-slate-700 font-medium text-center leading-tight">Transaksi</span>
                            </Link>
                            
                            <!-- Menu 4: Laporan -->
                            <Link v-if="can('akses_laporan')" :href="route('laporan')" class="flex flex-col items-center gap-2 w-16">
                                <div class="w-14 h-14 bg-purple-50 border border-purple-100 rounded-2xl shadow-sm flex items-center justify-center text-purple-600">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                </div>
                                <span class="text-[10px] text-slate-700 font-medium text-center leading-tight">Laporan</span>
                            </Link>
                            
                            <!-- Menu 5: Lainnya -->
                            <button @click="sidebarOpen = true" class="flex flex-col items-center gap-2 w-16">
                                <div class="w-14 h-14 bg-slate-100 border border-slate-200 rounded-2xl shadow-sm flex items-center justify-center text-slate-500">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z"/></svg>
                                </div>
                                <span class="text-[10px] text-slate-700 font-medium text-center leading-tight">Lainnya</span>
                            </button>
                        </div>
                    </div>

                    <!-- Donut Chart -->
                    <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm flex flex-col min-w-0">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 sm:gap-0 mb-4">
                            <div class="flex items-center gap-3">
                                <div class="bg-blue-50 text-blue-500 p-2 rounded-lg">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                </div>
                                <h3 class="text-base font-bold text-slate-800 truncate">Data Pelanggan</h3>
                            </div>
                            <select class="text-xs border-slate-200 rounded-lg py-1.5 pl-3 pr-8 text-slate-600 focus:ring-indigo-500 focus:border-indigo-500">
                                <option>Semua Status</option>
                            </select>
                        </div>
                        
                        <div class="flex-1 flex flex-col justify-center">
                            <div class="flex flex-col 2xl:flex-row items-center justify-center gap-4 xl:gap-8 flex-wrap">
                                <!-- Chart Canvas -->
                                <div class="relative w-32 h-32 xl:w-40 xl:h-40 flex-shrink-0">
                                    <canvas ref="donutCanvas"></canvas>
                                    <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                                        <span class="text-xl xl:text-2xl font-bold text-slate-800">{{ customerStats.total }}</span>
                                        <span class="text-[10px] text-slate-500 uppercase tracking-wider mt-0.5">Pelanggan</span>
                                    </div>
                                </div>
                                <!-- Legend -->
                                <div class="space-y-2 xl:space-y-3 min-w-[120px] flex-1">
                                    <div class="flex items-center justify-between gap-2 xl:gap-4 text-xs xl:text-xs sm:text-sm">
                                        <div class="flex items-center gap-2">
                                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 flex-shrink-0"></span>
                                            <span class="text-slate-600 truncate">Aktif</span>
                                        </div>
                                        <div class="font-semibold text-slate-800 flex-shrink-0">{{ customerStats.aktif }} <span class="text-slate-400 font-normal text-[10px] xl:text-xs">({{ customerStats.total ? Math.round((customerStats.aktif/customerStats.total)*100) : 0 }}%)</span></div>
                                    </div>
                                    <div class="flex items-center justify-between gap-2 xl:gap-4 text-xs xl:text-xs sm:text-sm">
                                        <div class="flex items-center gap-2">
                                            <span class="w-2.5 h-2.5 rounded-full bg-rose-500 flex-shrink-0"></span>
                                            <span class="text-slate-600 truncate">Nonaktif</span>
                                        </div>
                                        <div class="font-semibold text-slate-800 flex-shrink-0">{{ customerStats.nonaktif }} <span class="text-slate-400 font-normal text-[10px] xl:text-xs">({{ customerStats.total ? Math.round((customerStats.nonaktif/customerStats.total)*100) : 0 }}%)</span></div>
                                    </div>
                                    <div class="flex items-center justify-between gap-2 xl:gap-4 text-xs xl:text-xs sm:text-sm">
                                        <div class="flex items-center gap-2">
                                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500 flex-shrink-0"></span>
                                            <span class="text-slate-600 truncate">Suspend</span>
                                        </div>
                                        <div class="font-semibold text-slate-800 flex-shrink-0">{{ customerStats.suspend }} <span class="text-slate-400 font-normal text-[10px] xl:text-xs">({{ customerStats.total ? Math.round((customerStats.suspend/customerStats.total)*100) : 0 }}%)</span></div>
                                    </div>
                                    <div class="flex items-center justify-between gap-2 xl:gap-4 text-xs xl:text-xs sm:text-sm">
                                        <div class="flex items-center gap-2">
                                            <span class="w-2.5 h-2.5 rounded-full bg-purple-500 flex-shrink-0"></span>
                                            <span class="text-slate-600 truncate">Janji</span>
                                        </div>
                                        <div class="font-semibold text-slate-800 flex-shrink-0">{{ customerStats.janji_bayar }} <span class="text-slate-400 font-normal text-[10px] xl:text-xs">({{ customerStats.total ? Math.round((customerStats.janji_bayar/customerStats.total)*100) : 0 }}%)</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Paket Terlaris -->
                    <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm flex flex-col xl:col-span-1 lg:col-span-3 min-w-0">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-0 mb-6">
                            <div class="flex items-center gap-3">
                                <div class="bg-amber-50 text-amber-500 p-2 rounded-lg">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-base font-bold text-slate-800 truncate">Paket Terlaris</h3>
                                    <p class="text-[10px] text-slate-500 truncate">Pelanggan Aktif</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex flex-col sm:flex-row 2xl:flex-col gap-6 mb-4 flex-wrap">
                            <div class="relative w-32 h-32 mx-auto sm:mx-0 flex-shrink-0">
                                <canvas ref="paketCanvas"></canvas>
                            </div>
                            <div class="flex-1 overflow-y-auto pr-2 min-w-[120px]">
                                <ul class="space-y-4">
                                    <li v-for="(paket, index) in paketStats" :key="paket.paket" class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-0 group">
                                        <div class="flex items-center gap-3">
                                            <div class="w-3 h-3 rounded-full flex-shrink-0" :style="{ backgroundColor: ['#f59e0b', '#64748b', '#fb923c', '#60a5fa', '#a78bfa', '#34d399'][index % 6] }"></div>
                                            <span class="text-xs sm:text-sm font-semibold text-slate-700 group-hover:text-indigo-600 transition-colors">{{ paket.paket }}</span>
                                        </div>
                                        <div class="text-right">
                                            <span class="font-bold text-slate-800">{{ paket.total }}</span>
                                            <span class="text-[10px] text-slate-400 ml-1">user</span>
                                        </div>
                                    </li>
                                    <li v-if="paketStats?.length === 0" class="text-center text-xs sm:text-sm text-slate-500 py-4">Belum ada data paket.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Bottom Section: Lists -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <!-- Pelanggan Baru -->
                    <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm flex flex-col min-w-0">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 sm:gap-0 mb-5">
                            <div class="flex items-center gap-3">
                                <div class="bg-blue-50 text-blue-500 p-2 rounded-lg">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                    </svg>
                                </div>
                                <h3 class="text-base font-bold text-slate-800 truncate">Pelanggan Baru</h3>
                            </div>
                            <div class="flex items-center gap-3 min-w-0">
                                <select v-model="formFilters.area" @change="applyFilter" class="text-xs border-slate-200 rounded-lg py-1.5 pl-3 pr-8 text-slate-600 focus:ring-indigo-500 focus:border-indigo-500 max-w-full">
                                    <option value="">Semua Area</option>
                                    <option v-for="area in areas" :key="area" :value="area">{{ area }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="flex-1">
                            <ul class="space-y-4">
                                <li v-for="customer in recentCustomers" :key="customer.id" class="flex items-center justify-between group">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-xs sm:text-sm ring-1 ring-indigo-100">
                                            {{ (customer.name || '?').substring(0, 2).toUpperCase() }}
                                        </div>
                                        <div>
                                            <p class="text-xs sm:text-sm font-semibold text-slate-800 group-hover:text-indigo-600 transition-colors">{{ customer.name }}</p>
                                            <p class="text-xs text-slate-500">{{ formatDate(customer.created_at) }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <span class="text-xs text-slate-600 hidden sm:block">{{ customer.paket || '-' }}</span>
                                        <span class="px-2.5 py-1 text-[10px] font-semibold rounded-md bg-emerald-100 text-emerald-700">Baru</span>
                                    </div>
                                </li>
                                <li v-if="recentCustomers.length === 0" class="text-center text-xs sm:text-sm text-slate-500 py-4">Belum ada pelanggan.</li>
                            </ul>
                        </div>
                        <div class="mt-4 pt-4 border-t border-slate-100 text-center">
                            <Link :href="route('pelanggan.index')" class="text-xs font-medium text-indigo-600 hover:text-indigo-700 flex items-center justify-center gap-1">
                                Lihat Semua Pelanggan ({{ customerStats.total || 0 }}) <span aria-hidden="true">&rarr;</span>
                            </Link>
                        </div>
                    </div>

                    <!-- Tagihan Jatuh Tempo -->
                    <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm flex flex-col min-w-0">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 sm:gap-0 mb-5">
                            <div class="flex items-center gap-3">
                                <div class="bg-indigo-50 text-indigo-500 p-2 rounded-lg">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <h3 class="text-base font-bold text-slate-800 truncate">Tagihan Jatuh Tempo</h3>
                            </div>
                        </div>
                        <div class="flex-1 overflow-x-auto">
                            <table class="w-full text-left text-xs sm:text-sm">
                                <thead>
                                    <tr class="text-slate-400 text-xs border-b border-slate-100">
                                        <th class="pb-2 font-medium w-8 whitespace-nowrap">#</th>
                                        <th class="pb-2 font-medium whitespace-nowrap">Nama Pelanggan</th>
                                        <th class="pb-2 font-medium hidden sm:table-cell whitespace-nowrap">Paket</th>
                                        <th class="pb-2 font-medium text-right whitespace-nowrap">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50">
                                    <tr v-for="(bill, i) in overdueBills" :key="bill.id" class="group">
                                        <td class="py-3 text-xs text-slate-400 whitespace-nowrap">{{ i + 1 }}</td>
                                        <td class="py-3 whitespace-nowrap">
                                            <p class="font-medium text-slate-800 group-hover:text-indigo-600 transition-colors">{{ bill.name }}</p>
                                        </td>
                                        <td class="py-3 text-slate-500 text-xs hidden sm:table-cell whitespace-nowrap">{{ bill.paket || '-' }}</td>
                                        <td class="py-3 text-right whitespace-nowrap">
                                            <span :class="['px-2 py-1 text-[10px] font-semibold rounded-md', getDueBadge(bill.promise_date || bill.register_date).class]">
                                                {{ getDueBadge(bill.promise_date || bill.register_date).text }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr v-if="overdueBills.length === 0">
                                        <td colspan="4" class="py-6 text-center text-xs sm:text-sm text-slate-500">Tidak ada tagihan tertunggak.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4 pt-4 border-t border-slate-100 text-center">
                            <Link :href="route('billing.index')" class="text-xs font-medium text-indigo-600 hover:text-indigo-700 flex items-center justify-center gap-1">
                                Lihat Semua Tagihan ({{ unpaid.count || 0 }}) <span aria-hidden="true">&rarr;</span>
                            </Link>
                        </div>
                    </div>

                    <!-- Aktivitas Terbaru -->
                    <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm flex flex-col min-w-0">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 sm:gap-0 mb-5">
                            <div class="flex items-center gap-3">
                                <div class="bg-blue-50 text-blue-500 p-2 rounded-lg">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                    </svg>
                                </div>
                                <h3 class="text-base font-bold text-slate-800 truncate">Aktivitas Terbaru</h3>
                            </div>
                        </div>
                        <div class="flex-1">
                            <ul class="space-y-4">
                                <li v-for="t in transactions.slice(0,5)" :key="'t'+t.id" class="flex items-start gap-3">
                                    <div class="mt-0.5 flex-shrink-0">
                                        <div v-if="t.type === 'income'" class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                            </svg>
                                        </div>
                                        <div v-else class="w-8 h-8 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs sm:text-sm font-semibold text-slate-800 truncate">
                                            {{ t.type === 'income' ? 'Pembayaran diterima' : 'Pengeluaran dicatat' }}
                                        </p>
                                        <p class="text-xs text-slate-500 truncate">{{ t.description }} - {{ formatRupiah(t.amount) }}</p>
                                    </div>
                                    <div class="text-[10px] font-medium text-slate-400 whitespace-nowrap">
                                        {{ formatDate(t.date) }}
                                    </div>
                                </li>
                                <li v-if="transactions.length === 0" class="text-center text-xs sm:text-sm text-slate-500 py-4">Belum ada aktivitas.</li>
                            </ul>
                        </div>
                        <div class="mt-4 pt-4 border-t border-slate-100 text-center">
                            <Link :href="route('transaksi')" class="text-xs font-medium text-indigo-600 hover:text-indigo-700 flex items-center justify-center gap-1">
                                Lihat Semua Aktivitas ({{ transactions.length || 0 }}) <span aria-hidden="true">&rarr;</span>
                            </Link>
                        </div>
                    </div>

                </div>

                <div class="mt-6 bg-blue-50 border border-blue-100 rounded-2xl p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-sm min-w-0">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded-full flex shrink-0 items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="text-xs sm:text-sm font-medium text-slate-700 truncate">Informasi Biaya Pemasangan Saat Ini</h4>
                            <p class="text-[10px] sm:text-xs text-slate-500 truncate">Biaya pasang global yang berlaku untuk pelanggan baru.</p>
                        </div>
                    </div>
                    <div class="text-base sm:text-lg font-bold text-blue-700 shrink-0">
                        {{ globalInstallationFee > 0 ? formatRupiah(globalInstallationFee) : 'Gratis' }}
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>