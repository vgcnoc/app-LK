<script setup>
import { ref, onMounted, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Chart from 'chart.js/auto';

const props = defineProps({
    chartKecamatan: Array,
    chartAlasan: Array,
    batalList: Array
});

const barChartCanvas = ref(null);
const doughnutChartCanvas = ref(null);

const totalBatal = computed(() => {
    return props.batalList.length;
});

const topKecamatan = computed(() => {
    if (props.chartKecamatan.length === 0) return '-';
    return props.chartKecamatan[0].label;
});

const topAlasan = computed(() => {
    if (props.chartAlasan.length === 0) return '-';
    return props.chartAlasan[0].label;
});

onMounted(() => {
    if (props.chartKecamatan.length > 0 && barChartCanvas.value) {
        new Chart(barChartCanvas.value, {
            type: 'bar',
            data: {
                labels: props.chartKecamatan.map(d => d.label || 'Tanpa Kecamatan'),
                datasets: [{
                    label: 'Jumlah Batal',
                    data: props.chartKecamatan.map(d => d.total),
                    backgroundColor: 'rgba(99, 102, 241, 0.8)',
                    borderColor: 'rgba(99, 102, 241, 1)',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });
    }

    if (props.chartAlasan.length > 0 && doughnutChartCanvas.value) {
        new Chart(doughnutChartCanvas.value, {
            type: 'doughnut',
            data: {
                labels: props.chartAlasan.map(d => d.label || 'Lainnya'),
                datasets: [{
                    data: props.chartAlasan.map(d => d.total),
                    backgroundColor: [
                        '#ef4444', '#f97316', '#f59e0b', '#84cc16', 
                        '#10b981', '#06b6d4', '#3b82f6', '#8b5cf6'
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: { position: 'right', labels: { boxWidth: 12, usePointStyle: true, font: { size: 11 } } }
                }
            }
        });
    }
});
</script>

<template>
    <Head title="Analisa Ekspansi" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-xl font-bold leading-tight text-slate-800">
                        Analisa Ekspansi Jaringan
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Pemetaan data kegagalan pemasangan (Batal) untuk analisa prioritas penarikan ODP baru.
                    </p>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                
                <!-- Summary Cards -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-3 mb-6">
                    <!-- Card 1 -->
                    <div class="overflow-hidden rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100 relative group transition-all hover:shadow-md">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-rose-100 text-rose-600 transition-transform group-hover:scale-110">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-slate-500">Total Batal Pemasangan</p>
                                <p class="text-2xl font-bold text-slate-800">{{ totalBatal }} <span class="text-sm font-normal text-slate-400">pelanggan</span></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Card 2 -->
                    <div class="overflow-hidden rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100 relative group transition-all hover:shadow-md">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600 transition-transform group-hover:scale-110">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-slate-500">Kecamatan Terbanyak Batal</p>
                                <p class="text-xl font-bold text-slate-800 truncate" :title="topKecamatan">{{ topKecamatan }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Card 3 -->
                    <div class="overflow-hidden rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100 relative group transition-all hover:shadow-md">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-100 text-amber-600 transition-transform group-hover:scale-110">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-slate-500">Alasan Utama Penolakan</p>
                                <p class="text-lg font-bold text-slate-800 leading-tight truncate" :title="topAlasan">{{ topAlasan }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 mb-6">
                    <!-- Bar Chart -->
                    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                        <h3 class="text-base font-semibold text-slate-800 mb-1">Gagal Pasang Berdasarkan Kecamatan</h3>
                        <p class="text-xs text-slate-500 mb-6">Analisa wilayah potensial untuk jalur kabel baru.</p>
                        
                        <div v-if="chartKecamatan.length > 0" class="h-64 w-full">
                            <canvas ref="barChartCanvas"></canvas>
                        </div>
                        <div v-else class="flex h-64 items-center justify-center rounded-xl border border-dashed border-slate-200 bg-slate-50">
                            <p class="text-sm text-slate-400">Belum ada data kecamatan dari booking yang batal.</p>
                        </div>
                    </div>

                    <!-- Doughnut Chart -->
                    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                        <h3 class="text-base font-semibold text-slate-800 mb-1">Sebaran Alasan Batal</h3>
                        <p class="text-xs text-slate-500 mb-6">Identifikasi kendala teknis dan non-teknis terbanyak.</p>
                        
                        <div v-if="chartAlasan.length > 0" class="h-64 w-full">
                            <canvas ref="doughnutChartCanvas"></canvas>
                        </div>
                        <div v-else class="flex h-64 items-center justify-center rounded-xl border border-dashed border-slate-200 bg-slate-50">
                            <p class="text-sm text-slate-400">Belum ada data alasan pembatalan.</p>
                        </div>
                    </div>
                </div>

                <!-- Data Table Section -->
                <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-100 overflow-hidden">
                    <div class="border-b border-slate-100 px-6 py-5">
                        <h3 class="text-base font-semibold text-slate-800">Rincian Data Booking Batal</h3>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-slate-600">
                            <thead class="bg-slate-50/50 text-xs uppercase text-slate-500">
                                <tr>
                                    <th class="px-6 py-4 font-semibold">Tgl Batal</th>
                                    <th class="px-6 py-4 font-semibold">Nama Pelanggan</th>
                                    <th class="px-6 py-4 font-semibold">Kecamatan</th>
                                    <th class="px-6 py-4 font-semibold">Desa / Kelurahan</th>
                                    <th class="px-6 py-4 font-semibold">Alasan Penolakan</th>
                                    <th class="px-6 py-4 font-semibold">Sales Terkait</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <template v-if="batalList.length > 0">
                                    <tr v-for="item in batalList" :key="item.id" class="hover:bg-slate-50/80 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap">{{ item.tanggal }}</td>
                                        <td class="px-6 py-4 font-medium text-slate-800">{{ item.name }}</td>
                                        <td class="px-6 py-4">{{ item.kecamatan }}</td>
                                        <td class="px-6 py-4">{{ item.desa_kelurahan }}</td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center rounded-full bg-rose-50 px-2.5 py-0.5 text-xs font-medium text-rose-700 ring-1 ring-inset ring-rose-600/10">
                                                {{ item.keterangan_status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">{{ item.sales_name }}</td>
                                    </tr>
                                </template>
                                <tr v-else>
                                    <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                        <div class="flex flex-col items-center justify-center">
                                            <svg class="h-10 w-10 text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            <p>Tidak ada data booking batal.</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
