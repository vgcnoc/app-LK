<script setup>
import { ref, computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import KemitraanLayout from '@/Layouts/KemitraanLayout.vue';

const props = defineProps({
    profile: {
        type: Object,
        default: () => ({})
    }
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

// Dummy Data
const summary = {
    total_mitra: 145,
    mitra_baru: 12,
    total_komisi: 12500000,
    komisi_cair: 10000000
};

const recentMitras = [
    { id: 1, name: 'Budi Santoso', area: 'Cigadog', date: '24 Sep 2026', status: 'Aktif' },
    { id: 2, name: 'Siti Aminah', area: 'Sirnajaya', date: '23 Sep 2026', status: 'Baru' },
    { id: 3, name: 'Agus Pratama', area: 'Bojong Malang', date: '21 Sep 2026', status: 'Aktif' },
    { id: 4, name: 'Dewi Lestari', area: 'Pasapen', date: '20 Sep 2026', status: 'Aktif' },
];

const recentActivities = [
    { id: 1, title: 'Komisi dicairkan', desc: 'Pencairan komisi ke Budi Santoso - Rp 500.000', date: '24 Sep 2026' },
    { id: 2, title: 'Mitra Baru Bergabung', desc: 'Siti Aminah mendaftar sebagai mitra area Sirnajaya', date: '23 Sep 2026' },
    { id: 3, title: 'Komisi diterima', desc: 'Pembayaran dari pelanggan via Agus Pratama - Rp 50.000', date: '22 Sep 2026' },
    { id: 4, title: 'Komisi dicairkan', desc: 'Pencairan komisi ke Dewi Lestari - Rp 200.000', date: '21 Sep 2026' },
];
</script>

<template>
    <Head title="Dashboard Kemitraan 🤝" />

    <KemitraanLayout>
        <template #header>
            <div class="hidden sm:flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex-1 min-w-0">
                    <h2 class="text-xl sm:text-3xl font-bold text-slate-800 tracking-tight break-words">
                        Dashboard Kemitraan 🤝
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1 break-words">
                        Pantau performa mitra dan komisi Anda dalam satu dashboard.
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
                        <p class="text-xs text-slate-500 mt-2">Pantau kemitraan Anda dalam satu dashboard.</p>
                    </div>
                    <div class="absolute right-0 top-0 h-full w-1/3 flex items-center justify-center pointer-events-none opacity-80">
                        <div class="w-24 h-24 bg-blue-100 rounded-full flex items-center justify-center">
                            <svg class="w-12 h-12 text-blue-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path></svg>
                        </div>
                    </div>
                </div>

                <!-- 1. Top 4 Cards -->
                <div class="grid grid-cols-2 md:grid-cols-2 xl:grid-cols-4 gap-3 sm:gap-4">
                    <!-- Total Mitra Card -->
                    <div class="relative overflow-hidden rounded-2xl bg-blue-50 sm:bg-gradient-to-br sm:from-blue-100 sm:to-blue-50 p-4 sm:p-6 border border-blue-100 min-w-0">
                        <div class="relative z-10 flex flex-col h-full">
                            <div class="flex items-center gap-2 mb-2 sm:mb-3">
                                <div class="bg-blue-500 text-white p-1.5 sm:p-3 rounded-lg sm:rounded-xl shadow-sm flex-shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-6 sm:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                </div>
                                <p class="text-[10px] sm:text-xs font-bold text-blue-800 uppercase tracking-wider truncate">Total Mitra</p>
                            </div>
                            <h3 class="text-lg sm:text-3xl font-black text-blue-900 tracking-tight truncate">
                                {{ summary.total_mitra }}
                            </h3>
                            <div class="mt-auto pt-2 flex flex-wrap items-center gap-1.5 sm:gap-2 text-[9px] sm:text-xs">
                                <span class="inline-flex items-center gap-0.5 sm:gap-1 font-semibold text-blue-700 bg-blue-200/50 px-1 sm:px-2 py-0.5 rounded-md whitespace-nowrap">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-2 w-2 sm:h-3 sm:w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18" /></svg>
                                    5%
                                </span>
                                <span class="text-blue-600/80 truncate flex-1 min-w-0 leading-tight">mitra aktif saat ini</span>
                            </div>
                        </div>
                    </div>

                    <!-- Mitra Baru Card -->
                    <div class="relative overflow-hidden rounded-2xl bg-indigo-50 sm:bg-gradient-to-br sm:from-indigo-100 sm:to-indigo-50 p-4 sm:p-6 border border-indigo-100 min-w-0">
                        <div class="relative z-10 flex flex-col h-full">
                            <div class="flex items-center gap-2 mb-2 sm:mb-3">
                                <div class="bg-indigo-500 text-white p-1.5 sm:p-3 rounded-lg sm:rounded-xl shadow-sm flex-shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-6 sm:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                    </svg>
                                </div>
                                <p class="text-[10px] sm:text-xs font-bold text-indigo-800 uppercase tracking-wider truncate">Mitra Baru</p>
                            </div>
                            <h3 class="text-lg sm:text-3xl font-black text-indigo-900 tracking-tight truncate">
                                {{ summary.mitra_baru }}
                            </h3>
                            <div class="mt-auto pt-2 flex flex-wrap items-center gap-1.5 sm:gap-2 text-[9px] sm:text-xs">
                                <span class="inline-flex items-center gap-0.5 sm:gap-1 font-semibold text-indigo-700 bg-indigo-200/50 px-1 sm:px-2 py-0.5 rounded-md whitespace-nowrap">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-2 w-2 sm:h-3 sm:w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18" /></svg>
                                    12%
                                </span>
                                <span class="text-indigo-600/80 truncate flex-1 min-w-0 leading-tight">bulan ini</span>
                            </div>
                        </div>
                    </div>

                    <!-- Total Komisi Card -->
                    <div class="relative overflow-hidden rounded-2xl bg-emerald-50 sm:bg-gradient-to-br sm:from-emerald-100 sm:to-emerald-50 p-4 sm:p-6 border border-emerald-100 min-w-0">
                        <div class="relative z-10 flex flex-col h-full">
                            <div class="flex items-center gap-2 mb-2 sm:mb-3">
                                <div class="bg-emerald-500 text-white p-1.5 sm:p-3 rounded-lg sm:rounded-xl shadow-sm flex-shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-6 sm:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <p class="text-[10px] sm:text-xs font-bold text-emerald-800 uppercase tracking-wider truncate">Total Komisi</p>
                            </div>
                            <h3 class="text-lg sm:text-3xl font-black text-emerald-900 tracking-tight truncate">
                                {{ formatRupiah(summary.total_komisi) }}
                            </h3>
                            <div class="mt-auto pt-2 flex flex-wrap items-center gap-1.5 sm:gap-2 text-[9px] sm:text-xs">
                                <span class="inline-flex items-center gap-0.5 sm:gap-1 font-semibold text-emerald-700 bg-emerald-200/50 px-1 sm:px-2 py-0.5 rounded-md whitespace-nowrap">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-2 w-2 sm:h-3 sm:w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18" /></svg>
                                    8%
                                </span>
                                <span class="text-emerald-600/80 truncate flex-1 min-w-0 leading-tight">dari periode sebelumnya</span>
                            </div>
                        </div>
                    </div>

                    <!-- Komisi Cair Card -->
                    <div class="relative overflow-hidden rounded-2xl bg-amber-50 sm:bg-gradient-to-br sm:from-amber-100 sm:to-amber-50 p-4 sm:p-6 border border-amber-100 min-w-0">
                        <div class="relative z-10 flex flex-col h-full">
                            <div class="flex items-center gap-2 mb-2 sm:mb-3">
                                <div class="bg-amber-500 text-white p-1.5 sm:p-3 rounded-lg sm:rounded-xl shadow-sm flex-shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-6 sm:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <p class="text-[10px] sm:text-xs font-bold text-amber-800 uppercase tracking-wider truncate">Komisi Dicairkan</p>
                            </div>
                            <h3 class="text-lg sm:text-3xl font-black text-amber-900 tracking-tight truncate">
                                {{ formatRupiah(summary.komisi_cair) }}
                            </h3>
                            <div class="mt-auto pt-2 flex flex-wrap items-center gap-1.5 sm:gap-2 text-[9px] sm:text-xs">
                                <span class="text-amber-600/80 truncate flex-1 min-w-0 leading-tight">Total komisi yang telah dibayarkan</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Main Content Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
                    
                    <!-- Left Column: Mitra Terbaru -->
                    <div class="lg:col-span-2 space-y-4 sm:space-y-6">
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 sm:p-6">
                            <div class="flex items-center justify-between mb-4 sm:mb-6">
                                <div class="flex items-center gap-3">
                                    <div class="bg-blue-50 text-blue-600 p-2 rounded-xl">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                        </svg>
                                    </div>
                                    <h3 class="font-bold text-slate-800">Mitra Terbaru</h3>
                                </div>
                                <button class="text-sm text-indigo-600 font-medium hover:text-indigo-700">Lihat Semua</button>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-sm text-slate-600">
                                    <thead class="text-xs uppercase text-slate-400 border-b border-slate-100">
                                        <tr>
                                            <th class="py-3 px-4 font-semibold">Mitra</th>
                                            <th class="py-3 px-4 font-semibold">Area</th>
                                            <th class="py-3 px-4 font-semibold">Tanggal Gabung</th>
                                            <th class="py-3 px-4 font-semibold text-right">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr v-for="mitra in recentMitras" :key="mitra.id" class="hover:bg-slate-50 transition-colors">
                                            <td class="py-3 px-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-8 h-8 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center font-bold text-xs">
                                                        {{ mitra.name.substring(0,2).toUpperCase() }}
                                                    </div>
                                                    <span class="font-medium text-slate-800">{{ mitra.name }}</span>
                                                </div>
                                            </td>
                                            <td class="py-3 px-4">{{ mitra.area }}</td>
                                            <td class="py-3 px-4">{{ mitra.date }}</td>
                                            <td class="py-3 px-4 text-right">
                                                <span :class="[
                                                    mitra.status === 'Aktif' ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700',
                                                    'px-2.5 py-1 rounded-full text-xs font-semibold'
                                                ]">
                                                    {{ mitra.status }}
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Aktivitas Terbaru -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 sm:p-6">
                        <div class="flex items-center justify-between mb-4 sm:mb-6">
                            <div class="flex items-center gap-3">
                                <div class="bg-indigo-50 text-indigo-600 p-2 rounded-xl">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                    </svg>
                                </div>
                                <h3 class="font-bold text-slate-800">Aktivitas Terbaru</h3>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div v-for="act in recentActivities" :key="act.id" class="flex gap-4 p-3 rounded-xl hover:bg-slate-50 transition-colors">
                                <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0">
                                    <svg v-if="act.title.includes('diterima') || act.title.includes('dicairkan')" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                    </svg>
                                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-sm font-semibold text-slate-800">{{ act.title }}</h4>
                                    <p class="text-xs text-slate-500 mt-1">{{ act.desc }}</p>
                                    <p class="text-[10px] text-slate-400 mt-2">{{ act.date }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
    </KemitraanLayout>
</template>
