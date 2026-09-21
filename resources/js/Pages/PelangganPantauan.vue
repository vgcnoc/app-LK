<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import * as XLSX from 'xlsx';

const props = defineProps({
    customers: {
        type: Array,
        default: () => [],
    }
});

// Pagination
const itemsPerPage = ref(10);
const currentPage = ref(1);
const searchQuery = ref('');
const startDateFilter = ref('');
const endDateFilter = ref('');

const filteredCustomers = computed(() => {
    let base = props.customers || [];
    
    // Filter by search query
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        base = base.filter(c => 
            (c.name || '').toLowerCase().includes(q) || 
            (c.area || '').toLowerCase().includes(q)
        );
    }
    
    // Filter by date
    if (startDateFilter.value || endDateFilter.value) {
        base = base.filter(c => {
            let dateToCheck = c.promise_date || c.suspend_start_date || c.stop_date || c.created_at;
            if (!dateToCheck) return false;
            
            const itemDate = new Date(dateToCheck).getTime();
            const start = startDateFilter.value ? new Date(startDateFilter.value).getTime() : 0;
            const end = endDateFilter.value ? new Date(endDateFilter.value + 'T23:59:59').getTime() : Infinity;
            return itemDate >= start && itemDate <= end;
        });
    }
    
    return base;
});

import { watch } from 'vue';
watch([searchQuery, startDateFilter, endDateFilter], () => {
    currentPage.value = 1;
});

const totalPages = computed(() => Math.ceil(filteredCustomers.value.length / itemsPerPage.value));

const paginatedCustomers = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    return filteredCustomers.value.slice(start, start + itemsPerPage.value);
});

const formatRupiah = (number) => {
    if (!number) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number);
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
};

const getBulanMenunggak = (c) => {
    if (!c.base_amount || !c.amount || c.amount <= 0) return 0;
    // Calculate how many months they owe by dividing total debt by base monthly fee
    const months = Math.ceil(c.amount / c.base_amount);
    return months;
};

const getStatusColor = (status) => {
    if (!status) return 'bg-gray-100 text-gray-800 border-gray-200';
    const s = status.toLowerCase();
    if (['suspend', 'isolir', 'berhenti sementara'].includes(s)) return 'bg-red-100 text-red-700 border-red-200';
    if (s === 'janji bayar') return 'bg-yellow-100 text-yellow-800 border-yellow-200';
    if (['berhenti', 'nonaktif', 'stop permanen', 'putus'].includes(s)) return 'bg-gray-200 text-gray-700 border-gray-300';
    if (s === 'aktif') return 'bg-emerald-100 text-emerald-700 border-emerald-200';
    if (s === 'gratis') return 'bg-blue-100 text-blue-700 border-blue-200';
    return 'bg-gray-100 text-gray-800 border-gray-200';
};

const exportExcel = () => {
    let dataToExport = filteredCustomers.value.map((c, index) => ({
        'No': index + 1,
        'Nama Pelanggan': c.name,
        'No Telepon': c.no_telp || '-',
        'Alamat / Area': `${c.alamat || '-'} / ${c.area || '-'}`,
        'Status Pelanggan': c.status_pelanggan || '-',
        'Status Tagihan': c.status === 'nunggak' ? `Nunggak (${getBulanMenunggak(c)} Bulan)` : (c.status || '-'),
        'Tagihan Saat Ini': c.amount || 0,
        'Tgl Janji Bayar': c.promise_date ? formatDate(c.promise_date) : '-',
        'Riwayat Suspend': `${c.suspensions_count || 0} kali`
    }));

    const ws = XLSX.utils.json_to_sheet(dataToExport);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "Pantauan Pelanggan");
    XLSX.writeFile(wb, `Data_Pelanggan_Pantauan_${new Date().getTime()}.xlsx`);
};

</script>

<template>
    <Head title="Pantauan Pelanggan" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Pantauan Pelanggan (Bermasalah)</h2>
        </template>

        <div class="py-12">
            <div class="max-w-full mx-auto">
                <!-- Data Overview Cards -->
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
                    <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6 border-l-4 border-red-500">
                        <div class="text-xs sm:text-sm font-medium text-gray-500 mb-1">Total Sedang Suspend</div>
                        <div class="text-2xl font-bold text-gray-900">
                            {{ customers.filter(c => ['suspend', 'berhenti sementara', 'isolir'].includes((c.status_pelanggan || '').toLowerCase())).length }}
                        </div>
                    </div>
                    <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6 border-l-4 border-orange-500">
                        <div class="text-xs sm:text-sm font-medium text-gray-500 mb-1">Total Nunggak</div>
                        <div class="text-2xl font-bold text-gray-900">
                            {{ customers.filter(c => c.status === 'nunggak').length }}
                        </div>
                    </div>
                    <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6 border-l-4 border-yellow-500">
                        <div class="text-xs sm:text-sm font-medium text-gray-500 mb-1">Total Janji Bayar</div>
                        <div class="text-2xl font-bold text-gray-900">
                            {{ customers.filter(c => c.promise_date).length }}
                        </div>
                    </div>
                    <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6 border-l-4 border-blue-500">
                        <div class="text-xs sm:text-sm font-medium text-gray-500 mb-1">Sering Suspend (> 1 kali)</div>
                        <div class="text-2xl font-bold text-gray-900">
                            {{ customers.filter(c => c.suspensions_count > 1).length }}
                        </div>
                    </div>
                    <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6 border-l-4 border-gray-500">
                        <div class="text-xs sm:text-sm font-medium text-gray-500 mb-1">Berhenti < 4 Bulan</div>
                        <div class="text-2xl font-bold text-gray-900">
                            {{ customers.filter(c => ['berhenti', 'nonaktif', 'stop permanen', 'putus'].includes((c.status_pelanggan || '').toLowerCase())).length }}
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        
                        <!-- Actions & Filters -->
                        <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                            <div class="flex flex-col md:flex-row items-center gap-2 w-full md:w-auto">
                                <input v-model="searchQuery" type="text" placeholder="Cari nama atau area..." class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full md:w-64">
                                <div class="flex items-center gap-2 w-full md:w-auto">
                                    <input v-model="startDateFilter" type="date" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full md:w-auto" title="Dari Tanggal">
                                    <span class="text-gray-500">-</span>
                                    <input v-model="endDateFilter" type="date" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full md:w-auto" title="Sampai Tanggal">
                                </div>
                            </div>
                            
                            <div class="flex gap-2">
                                <button @click="exportExcel" class="inline-flex items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 active:bg-emerald-900 focus:outline-none focus:border-emerald-900 focus:ring ring-emerald-300 disabled:opacity-25 transition ease-in-out duration-150 shadow-sm">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    Export Excel
                                </button>
                            </div>
                        </div>

                        <!-- Table Data -->
                        <div class="overflow-x-auto rounded-lg border border-gray-200 shadow-sm">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-3 py-2 sm:px-6 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pelanggan</th>
                                        <th scope="col" class="px-3 py-2 sm:px-6 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status Berlangganan</th>
                                        <th scope="col" class="px-3 py-2 sm:px-6 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status Tagihan</th>
                                        <th scope="col" class="px-3 py-2 sm:px-6 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tgl Janji Bayar</th>
                                        <th scope="col" class="px-3 py-2 sm:px-6 sm:py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Total Suspend</th>
                                        <th scope="col" class="px-3 py-2 sm:px-6 sm:py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="c in paginatedCustomers" :key="c.id" class="hover:bg-gray-50 transition-colors">
                                        <td class="px-3 py-3 sm:px-6 sm:py-4 whitespace-nowrap">
                                            <div class="font-medium text-gray-900">{{ c.name }}</div>
                                            <div class="text-xs sm:text-sm text-gray-500">{{ c.alamat || '-' }} / {{ c.area }}</div>
                                        </td>
                                        <td class="px-3 py-3 sm:px-6 sm:py-4 whitespace-nowrap">
                                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full border"
                                                  :class="getStatusColor(c.status_pelanggan)">
                                                {{ c.status_pelanggan || 'Aktif' }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-3 sm:px-6 sm:py-4 whitespace-nowrap">
                                            <div v-if="c.status === 'nunggak'" class="flex flex-col gap-1 items-start">
                                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 border border-red-200">
                                                    Nunggak {{ getBulanMenunggak(c) }} Bulan
                                                </span>
                                                <span class="text-xs text-red-600 font-medium">Total: {{ formatRupiah(c.amount) }}</span>
                                            </div>
                                            <span v-else class="text-xs sm:text-sm text-gray-500">{{ c.status === 'pending' ? 'Belum Bayar' : (c.status || '-') }}</span>
                                        </td>
                                        <td class="px-3 py-3 sm:px-6 sm:py-4 whitespace-nowrap">
                                            <span v-if="c.promise_date" class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800 border border-yellow-200">
                                                {{ formatDate(c.promise_date) }}
                                            </span>
                                            <span v-else class="text-gray-400">-</span>
                                        </td>
                                        <td class="px-3 py-3 sm:px-6 sm:py-4 whitespace-nowrap text-center">
                                            <span v-if="c.suspensions_count > 0" :class="c.suspensions_count > 1 ? 'font-bold text-red-600' : 'text-gray-700'">
                                                {{ c.suspensions_count }} kali
                                            </span>
                                            <span v-else class="text-gray-400">-</span>
                                        </td>
                                        <td class="px-3 py-3 sm:px-6 sm:py-4 whitespace-nowrap text-right text-xs sm:text-sm font-medium">
                                            <Link :href="route('pelanggan.index') + '?search=' + encodeURIComponent(c.name)" class="text-indigo-600 hover:text-indigo-900 border border-indigo-200 px-3 py-1 rounded hover:bg-indigo-50 transition-colors">Lihat Detail</Link>
                                        </td>
                                    </tr>
                                    <tr v-if="paginatedCustomers.length === 0">
                                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                            <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            Tidak ada pelanggan yang masuk dalam pantauan saat ini.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination Controls -->
                        <div v-if="totalPages > 1" class="flex justify-between items-center mt-6 bg-gray-50 p-4 rounded-lg border border-gray-200">
                            <span class="text-xs sm:text-sm text-gray-700">
                                Menampilkan <span class="font-semibold">{{ ((currentPage - 1) * itemsPerPage) + 1 }}</span> 
                                sampai <span class="font-semibold">{{ Math.min(currentPage * itemsPerPage, filteredCustomers.length) }}</span> 
                                dari <span class="font-semibold">{{ filteredCustomers.length }}</span> data
                            </span>
                            <div class="flex gap-2">
                                <button @click="currentPage--" :disabled="currentPage === 1" class="px-4 py-2 border rounded shadow-sm text-xs sm:text-sm font-medium" :class="currentPage === 1 ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white text-gray-700 hover:bg-gray-50'">Sebelumnya</button>
                                <button @click="currentPage++" :disabled="currentPage === totalPages" class="px-4 py-2 border rounded shadow-sm text-xs sm:text-sm font-medium" :class="currentPage === totalPages ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white text-gray-700 hover:bg-gray-50'">Selanjutnya</button>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
