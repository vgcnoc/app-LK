<script setup>
import { ref, computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import KemitraanLayout from '@/Layouts/KemitraanLayout.vue';

const props = defineProps({
    invoices: { type: Array, default: () => [] },
});

const formatRupiah = (number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency', currency: 'IDR',
        minimumFractionDigits: 0, maximumFractionDigits: 0,
    }).format(Number(number) || 0);
};

const formattedTodayDate = computed(() => {
    return new Intl.DateTimeFormat('id-ID', {
        weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
    }).format(new Date());
});

const statusColor = (status) => {
    const map = {
        'Lunas': 'bg-emerald-100 text-emerald-700',
        'Belum Bayar': 'bg-red-100 text-red-700',
        'Jatuh Tempo': 'bg-amber-100 text-amber-700',
        'Dibatalkan': 'bg-slate-100 text-slate-600',
    };
    return map[status] || 'bg-slate-100 text-slate-700';
};

const totalBelumBayar = computed(() => {
    return props.invoices.filter(i => i.status === 'Belum Bayar').reduce((sum, i) => sum + (i.total || 0), 0);
});
const totalLunas = computed(() => {
    return props.invoices.filter(i => i.status === 'Lunas').reduce((sum, i) => sum + (i.total || 0), 0);
});
</script>

<template>
    <Head title="Invoice - Kemitraan" />

    <KemitraanLayout>
        <template #header>
            <div class="hidden sm:flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex-1 min-w-0">
                    <h2 class="text-xl sm:text-3xl font-bold text-slate-800 tracking-tight">
                        Invoice
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">
                        Kelola tagihan dan pembayaran kemitraan Anda.
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
            <div class="max-w-full mx-auto space-y-6">

                <!-- Summary Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 flex items-center gap-4">
                        <div class="bg-red-50 text-red-600 p-3 rounded-xl">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xl font-black text-slate-800">{{ formatRupiah(totalBelumBayar) }}</p>
                            <p class="text-xs text-slate-500">Belum Dibayar</p>
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 flex items-center gap-4">
                        <div class="bg-emerald-50 text-emerald-600 p-3 rounded-xl">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xl font-black text-slate-800">{{ formatRupiah(totalLunas) }}</p>
                            <p class="text-xs text-slate-500">Sudah Lunas</p>
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 flex items-center gap-4">
                        <div class="bg-indigo-50 text-indigo-600 p-3 rounded-xl">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xl font-black text-slate-800">{{ invoices.length }}</p>
                            <p class="text-xs text-slate-500">Total Invoice</p>
                        </div>
                    </div>
                </div>

                <!-- Table Invoice -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                        <h3 class="font-bold text-slate-800">Daftar Invoice</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-slate-600">
                            <thead class="text-xs uppercase text-slate-400 border-b border-slate-100 bg-slate-50/50">
                                <tr>
                                    <th class="py-4 px-6 font-semibold">No. Invoice</th>
                                    <th class="py-4 px-6 font-semibold">Deskripsi</th>
                                    <th class="py-4 px-6 font-semibold">Tanggal</th>
                                    <th class="py-4 px-6 font-semibold">Jatuh Tempo</th>
                                    <th class="py-4 px-6 font-semibold text-right">Total</th>
                                    <th class="py-4 px-6 font-semibold text-right">Status</th>
                                    <th class="py-4 px-6 font-semibold text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <template v-if="invoices.length > 0">
                                    <tr v-for="inv in invoices" :key="inv.id" class="hover:bg-slate-50 transition-colors">
                                        <td class="py-4 px-6 font-mono font-medium text-indigo-600 text-sm">{{ inv.nomor }}</td>
                                        <td class="py-4 px-6">
                                            <p class="font-medium text-slate-800">{{ inv.deskripsi }}</p>
                                        </td>
                                        <td class="py-4 px-6 text-slate-500">{{ inv.tanggal }}</td>
                                        <td class="py-4 px-6 text-slate-500">{{ inv.jatuh_tempo }}</td>
                                        <td class="py-4 px-6 text-right font-semibold text-slate-800">{{ formatRupiah(inv.total) }}</td>
                                        <td class="py-4 px-6 text-right">
                                            <span :class="[statusColor(inv.status), 'px-2.5 py-1 rounded-full text-xs font-semibold']">
                                                {{ inv.status }}
                                            </span>
                                        </td>
                                        <td class="py-4 px-6 text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                <button class="px-3 py-1.5 text-xs font-medium text-indigo-600 bg-indigo-50 hover:bg-indigo-100 rounded-lg transition-colors">
                                                    Lihat
                                                </button>
                                                <button v-if="inv.status === 'Belum Bayar'" class="px-3 py-1.5 text-xs font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors">
                                                    Bayar
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                <tr v-else>
                                    <td colspan="7" class="py-16 text-center">
                                        <div class="flex flex-col items-center gap-3">
                                            <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                            </div>
                                            <p class="text-sm text-slate-500">Belum ada invoice</p>
                                            <p class="text-xs text-slate-400">Invoice akan muncul setelah ada tagihan dari ISP</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </KemitraanLayout>
</template>
