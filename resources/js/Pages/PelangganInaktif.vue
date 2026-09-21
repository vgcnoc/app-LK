<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import * as XLSX from 'xlsx';

const startDateFilter = ref('');
const endDateFilter = ref('');

const props = defineProps({
    customers: {
        type: Array,
        default: () => [],
    },
    allSuspensionsData: {
        type: Array,
        default: () => [],
    },
    areas: {
        type: Array,
        default: () => [],
    }
});

const activeTab = ref('Suspend');

// Use the allSuspensionsData from backend for the "Riwayat" tab
const allSuspensions = computed(() => {
    let data = props.allSuspensionsData || [];
    
    if (startDateFilter.value || endDateFilter.value) {
        data = data.filter(s => {
            if (!s.start_date) return false;
            const itemDate = new Date(s.start_date).getTime();
            const start = startDateFilter.value ? new Date(startDateFilter.value).getTime() : 0;
            const end = endDateFilter.value ? new Date(endDateFilter.value + 'T23:59:59').getTime() : Infinity;
            return itemDate >= start && itemDate <= end;
        });
    }
    
    return data;
});

const filteredCustomers = computed(() => {
    let base = [];
    if (activeTab.value === 'Suspend') {
        base = props.customers.filter(c => ['suspend', 'isolir', 'berhenti sementara'].includes((c.status_pelanggan || '').toLowerCase()));
    } else if (activeTab.value === 'Berhenti') {
        base = props.customers.filter(c => ['berhenti', 'nonaktif', 'stop permanen', 'putus'].includes((c.status_pelanggan || '').toLowerCase()));
    } else if (activeTab.value === 'Gratis') {
        base = props.customers.filter(c => {
            const isStatusGratis = (c.status_pelanggan || '').toLowerCase() === 'gratis';
            const isAreaGratis = (c.area || '').toLowerCase().startsWith('gratis');
            return isStatusGratis || isAreaGratis;
        });
    }

    if (startDateFilter.value || endDateFilter.value) {
        base = base.filter(c => {
            let dateToCheck = null;
            if (activeTab.value === 'Suspend') dateToCheck = c.suspend_start_date;
            else if (activeTab.value === 'Berhenti') dateToCheck = c.stop_date;
            
            if (!dateToCheck) dateToCheck = c.tanggal_register || c.created_at;

            if (!dateToCheck) return false;
            
            const itemDate = new Date(dateToCheck).getTime();
            const start = startDateFilter.value ? new Date(startDateFilter.value).getTime() : 0;
            const end = endDateFilter.value ? new Date(endDateFilter.value + 'T23:59:59').getTime() : Infinity;
            return itemDate >= start && itemDate <= end;
        });
    }

    return base;
});

// Pagination
const itemsPerPage = ref(10);
const currentPage = ref(1);

const totalPages = computed(() => Math.ceil(filteredCustomers.value.length / itemsPerPage.value));

const paginatedCustomers = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    return filteredCustomers.value.slice(start, start + itemsPerPage.value);
});

const currentPageSuspensions = ref(1);
const totalPagesSuspensions = computed(() => Math.ceil(allSuspensions.value.length / itemsPerPage.value));
const paginatedSuspensions = computed(() => {
    const start = (currentPageSuspensions.value - 1) * itemsPerPage.value;
    return allSuspensions.value.slice(start, start + itemsPerPage.value);
});

import { watch } from 'vue';
watch([activeTab, startDateFilter, endDateFilter], () => {
    currentPage.value = 1;
    currentPageSuspensions.value = 1;
});

const exportExcel = () => {
    let dataToExport = [];
    
    if (activeTab.value === 'Riwayat') {
        dataToExport = allSuspensions.value.map((s, index) => ({
            'No': index + 1,
            'Nama Pelanggan': s.customer ? s.customer.name : '-',
            'Tanggal Suspend': s.start_date ? new Date(s.start_date).toLocaleDateString() : '-',
            'Sampai': s.end_date ? new Date(s.end_date).toLocaleDateString() : '-',
            'Tanggal Aktif (Real)': s.reactivated_at ? new Date(s.reactivated_at).toLocaleDateString() : 'Belum Aktif',
            'Alasan': s.reason || '-',
        }));
    } else {
        dataToExport = filteredCustomers.value.map((c, index) => ({
            'No': index + 1,
            'Nama Pelanggan': c.name,
            'Area': c.area || '-',
            'Alamat': c.alamat || '-',
            'Nama Paket': c.paket || '-',
            'Tanggal': activeTab.value === 'Suspend' ? (c.suspend_start_date ? new Date(c.suspend_start_date).toLocaleDateString() : '-') : (c.stop_date ? new Date(c.stop_date).toLocaleDateString() : '-'),
            'Lama Berlangganan': getLamaBerlangganan(c),
            'Status Pelanggan': c.status_pelanggan,
        }));
    }
    
    const ws = XLSX.utils.json_to_sheet(dataToExport);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, `Data ${activeTab.value}`);
    XLSX.writeFile(wb, `Data_Pelanggan_${activeTab.value}_${new Date().toISOString().split('T')[0]}.xlsx`);
};

const exportPDF = () => {
    window.print();
};

// Format Currency
const formatRupiah = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value);
};

// Format Date
const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    }).format(date);
};

// Calculate Days
const calculateDays = (startDate, endDate) => {
    if (!startDate || !endDate) return '-';
    const start = new Date(startDate);
    const end = new Date(endDate);
    const diffTime = end - start;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return diffDays >= 0 ? diffDays : 0;
};

// Calculate Days from Today
const calculateDaysFromToday = (startDate) => {
    if (!startDate) return 0;
    const start = new Date(startDate);
    const today = new Date();
    // Reset time part to accurately count full days
    today.setHours(0, 0, 0, 0);
    start.setHours(0, 0, 0, 0);
    const diffTime = today - start;
    const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
    return diffDays >= 0 ? diffDays : 0;
};

// Calculate Lama Berlangganan
const getLamaBerlangganan = (customer) => {
    const startDate = customer.register_date || customer.created_at;
    if (!startDate) return '-';
    
    let endDate = new Date();
    const stoppedStatuses = ['berhenti', 'nonaktif', 'stop permanen', 'putus'];
    if (customer.stop_date && stoppedStatuses.includes(String(customer.status_pelanggan || '').toLowerCase())) {
        endDate = new Date(customer.stop_date);
    }
    
    const start = new Date(startDate);
    start.setHours(0, 0, 0, 0);
    const end = new Date(endDate);
    end.setHours(0, 0, 0, 0);
    
    if (start > end) return '0 Hari';
    
    const diffTime = end - start;
    let diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    // Jika hari yang sama, bisa dianggap 1 hari atau 0 hari, kita set ke 1 hari untuk logisnya berlangganan
    if (diffDays === 0) diffDays = 1;
    
    if (diffDays >= 30) {
        const months = Math.floor(diffDays / 30);
        const days = diffDays % 30;
        return `${months} Bln${days > 0 ? ` ${days} Hari` : ''}`;
    }
    return `${diffDays} Hari`;
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
    amount: 0,
    base_amount: 0,
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
    editForm.amount = customer.amount || 0;
    editForm.base_amount = customer.base_amount || 0;
    editForm.clearErrors();
    isEditModalOpen.value = true;
};

const closeEditModal = () => {
    isEditModalOpen.value = false;
    setTimeout(() => {
        editingCustomer.value = null;
        editForm.reset();
    }, 200);
};

const submitEdit = () => {
    editForm.put(route('pelanggan.update', editingCustomer.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeEditModal();
        },
    });
};

const confirmDelete = (id) => {
    if (confirm('Apakah Anda yakin ingin menghapus pelanggan ini? Data transaksi yang terkait mungkin akan terpengaruh.')) {
        router.delete(route('pelanggan.destroy', id), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Pelanggan Non-Aktif" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-slate-800">
                        Pelanggan Non-Aktif
                    </h2>
                    <p class="mt-1 text-xs sm:text-sm text-slate-500">
                        Kelola data pelanggan yang berhenti sementara atau stop permanen.
                    </p>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                
                <!-- Controls Section -->
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6 print:hidden">
                    <!-- Tabs -->
                    <div class="flex space-x-1 rounded-xl bg-slate-200/50 p-1 w-full max-w-lg">
                    <button
                        @click="activeTab = 'Suspend'"
                        :class="[
                            'w-full rounded-lg py-2.5 text-xs sm:text-sm font-medium leading-5 transition-all',
                            activeTab === 'Suspend'
                                ? 'bg-white text-indigo-700 shadow ring-1 ring-black/5'
                                : 'text-slate-600 hover:bg-white/50 hover:text-slate-900'
                        ]"
                    >
                        Suspend / Berhenti Sementara
                    </button>
                    <button
                        @click="activeTab = 'Berhenti'"
                        :class="[
                            'w-full rounded-lg py-2.5 text-xs sm:text-sm font-medium leading-5 transition-all',
                            activeTab === 'Berhenti'
                                ? 'bg-white text-indigo-700 shadow ring-1 ring-black/5'
                                : 'text-slate-600 hover:bg-white/50 hover:text-slate-900'
                        ]"
                    >
                        Pelanggan Berhenti
                    </button>
                    <button
                        @click="activeTab = 'Gratis'"
                        :class="[
                            'w-full rounded-lg py-2.5 text-xs sm:text-sm font-medium leading-5 transition-all',
                            activeTab === 'Gratis'
                                ? 'bg-white text-indigo-700 shadow ring-1 ring-black/5'
                                : 'text-slate-600 hover:bg-white/50 hover:text-slate-900'
                        ]"
                    >
                        Gratis
                    </button>
                    <button
                        @click="activeTab = 'Riwayat'"
                        :class="[
                            'w-full rounded-lg py-2.5 text-xs sm:text-sm font-medium leading-5 transition-all',
                            activeTab === 'Riwayat'
                                ? 'bg-white text-indigo-700 shadow ring-1 ring-black/5'
                                : 'text-slate-600 hover:bg-white/50 hover:text-slate-900'
                        ]"
                    >
                        Riwayat Suspend
                    </button>
                    </div>

                    <!-- Date Filter & Export -->
                    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                        <div class="flex items-center gap-2">
                            <label class="text-xs font-semibold text-slate-500 whitespace-nowrap">Tanggal</label>
                            <input type="date" v-model="startDateFilter" class="w-full sm:w-auto rounded-lg border border-slate-300 py-1.5 px-3 text-xs text-slate-700 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            <span class="text-xs text-slate-500">s/d</span>
                            <input type="date" v-model="endDateFilter" class="w-full sm:w-auto rounded-lg border border-slate-300 py-1.5 px-3 text-xs text-slate-700 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        </div>
                        <div class="flex items-center gap-2">
                            <button @click="exportExcel" type="button" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-700 hover:bg-emerald-100 transition-colors border border-emerald-200">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                Export
                            </button>
                            <button @click="exportPDF" type="button" class="inline-flex items-center gap-1.5 rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-100 transition-colors border border-rose-200">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                </svg>
                                Cetak
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Print Header -->
                <div class="hidden print:block mb-6 text-center">
                    <h2 class="text-2xl font-bold text-slate-800">Laporan Pelanggan Non-Aktif</h2>
                    <p class="text-xs sm:text-sm text-slate-600 mt-1">
                        Kategori: <span class="font-semibold">{{ activeTab.toUpperCase() }}</span>
                    </p>
                    <p class="text-xs text-slate-500 mt-1">
                        Tanggal: {{ startDateFilter ? new Date(startDateFilter).toLocaleDateString('id-ID') : 'Awal' }} s/d {{ endDateFilter ? new Date(endDateFilter).toLocaleDateString('id-ID') : 'Sekarang' }}
                    </p>
                </div>

                <!-- Main Table Card -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 print:shadow-none print:border-none print:ring-0">
                    
                    <div class="overflow-x-auto print:overflow-visible print:w-full">
                        <table class="min-w-full divide-y divide-slate-200 print:text-[11px]">
                            <thead class="bg-slate-50/50 print:text-[10px]">
                                <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    <th scope="col" class="w-16 px-3 py-2.5 sm:px-6 sm:py-3.5 text-center">No</th>
                                    <th scope="col" class="px-3 py-2.5 sm:px-6 sm:py-3.5">Nama Pelanggan</th>
                                    <th scope="col" class="px-3 py-2.5 sm:px-6 sm:py-3.5">Area / Wilayah</th>
                                    <th scope="col" class="px-3 py-2.5 sm:px-6 sm:py-3.5">Alamat</th>
                                    <th scope="col" class="px-3 py-2.5 sm:px-6 sm:py-3.5">Nama Paket</th>
                                    
                                    <!-- Conditional Headers based on Tab -->
                                    <th v-if="activeTab === 'Suspend' || activeTab === 'Riwayat'" scope="col" class="px-3 py-2.5 sm:px-6 sm:py-3.5 text-center">Tanggal Mulai</th>
                                    <th v-if="activeTab === 'Suspend'" scope="col" class="px-3 py-2.5 sm:px-6 sm:py-3.5 text-center">Tanggal Selesai</th>
                                    <th v-if="activeTab === 'Riwayat'" scope="col" class="px-3 py-2.5 sm:px-6 sm:py-3.5 text-center">Tanggal Pengaktifan</th>
                                    <th v-if="activeTab === 'Suspend' || activeTab === 'Riwayat'" scope="col" class="px-3 py-2.5 sm:px-6 sm:py-3.5 text-center">Jumlah Hari</th>
                                    <th v-if="activeTab === 'Berhenti'" scope="col" class="px-3 py-2.5 sm:px-6 sm:py-3.5 text-center">Tanggal Berhenti</th>
                                    <th v-if="activeTab === 'Gratis'" scope="col" class="px-3 py-2.5 sm:px-6 sm:py-3.5 text-center">Tanggal Register</th>
                                    
                                    <!-- Lama Berlangganan -->
                                    <th scope="col" class="px-3 py-2.5 sm:px-6 sm:py-3.5 text-center">Lama Berlangganan</th>
                                    
                                    <th v-if="activeTab !== 'Riwayat'" scope="col" class="w-36 px-3 py-2.5 sm:px-6 sm:py-3.5 text-center print:hidden">Aksi</th>
                                </tr>
                            </thead>
                            <!-- Table Body for Non-Riwayat Tabs -->
                            <tbody v-if="activeTab !== 'Riwayat'" class="divide-y divide-slate-100 bg-white">
                                <tr
                                    v-for="(customer, index) in paginatedCustomers"
                                    :key="customer.id || index"
                                    class="transition-colors duration-150 hover:bg-slate-50/80"
                                >
                                    <!-- Row Number -->
                                    <td class="whitespace-nowrap print:whitespace-normal px-2 py-3 sm:px-4 sm:py-4 text-center text-xs font-medium text-slate-400">
                                        {{ index + 1 }}
                                    </td>

                                    <!-- Customer Name -->
                                    <td class="whitespace-nowrap print:whitespace-normal px-3 py-3 sm:px-6 sm:py-4">
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
                                    <td class="whitespace-nowrap px-3 py-3 sm:px-6 sm:py-4 text-xs sm:text-sm text-slate-600">
                                        {{ customer.area || '-' }}
                                    </td>

                                    <!-- Alamat -->
                                    <td class="whitespace-nowrap px-3 py-3 sm:px-6 sm:py-4 text-xs sm:text-sm text-slate-600">
                                        {{ customer.alamat || '-' }}
                                    </td>

                                    <!-- Package -->
                                    <td class="whitespace-nowrap print:whitespace-normal px-3 py-3 sm:px-6 sm:py-4">
                                        <div class="text-xs sm:text-sm font-medium text-slate-900">{{ customer.paket || '-' }}</div>
                                    </td>

                                    <!-- Conditional Dates -->
                                    <td v-if="activeTab === 'Suspend'" class="whitespace-nowrap px-3 py-3 sm:px-6 sm:py-4 text-center text-xs font-medium text-amber-700">
                                        {{ formatDate(customer.suspend_start_date) }}
                                    </td>
                                    <td v-if="activeTab === 'Suspend'" class="whitespace-nowrap px-3 py-3 sm:px-6 sm:py-4 text-center text-xs font-medium text-amber-700">
                                        {{ formatDate(customer.suspend_end_date) }}
                                    </td>
                                    <td v-if="activeTab === 'Suspend'" class="whitespace-nowrap px-3 py-3 sm:px-6 sm:py-4 text-center text-xs font-medium">
                                        <div v-if="calculateDays(customer.suspend_start_date, customer.suspend_end_date) !== '-'"
                                            class="inline-flex items-center gap-1.5"
                                            :class="calculateDaysFromToday(customer.suspend_start_date) > 30 ? 'text-rose-600 font-bold' : 'text-indigo-600'"
                                        >
                                            {{ calculateDays(customer.suspend_start_date, customer.suspend_end_date) }} Hari
                                            
                                            <span v-if="calculateDaysFromToday(customer.suspend_start_date) > 30" 
                                                  title="Telah disuspend lebih dari 1 bulan!" 
                                                  class="flex items-center justify-center rounded-full bg-rose-100 p-0.5 text-rose-600">
                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                            </span>
                                        </div>
                                        <span v-else class="text-slate-400">-</span>
                                    </td>
                                    
                                    <td v-if="activeTab === 'Berhenti'" class="whitespace-nowrap px-3 py-3 sm:px-6 sm:py-4 text-center text-xs font-medium text-rose-700">
                                        {{ formatDate(customer.stop_date) }}
                                    </td>

                                    <td v-if="activeTab === 'Gratis'" class="whitespace-nowrap px-3 py-3 sm:px-6 sm:py-4 text-center text-xs font-medium text-emerald-700">
                                        {{ formatDate(customer.register_date) }}
                                    </td>
                                    
                                    <!-- Lama Berlangganan Data -->
                                    <td class="whitespace-nowrap px-3 py-3 sm:px-6 sm:py-4 text-center text-xs font-medium text-slate-700">
                                        {{ getLamaBerlangganan(customer) }}
                                    </td>

                                    <!-- Actions -->
                                    <td class="whitespace-nowrap px-3 py-3 sm:px-6 sm:py-4 text-center print:hidden">
                                        <div class="flex items-center justify-center gap-2">
                                            <!-- Edit Button -->
                                            <button
                                                @click="openEditModal(customer)"
                                                class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-indigo-600 focus:outline-none"
                                                title="Edit Data"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                                </svg>
                                            </button>
                                            <button
                                                @click="confirmDelete(customer.id)"
                                                class="rounded-lg p-1.5 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600 focus:outline-none"
                                                title="Hapus"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Empty State -->
                                <tr v-if="filteredCustomers.length === 0">
                                    <td colspan="8" class="px-6 py-12 text-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <h3 class="mt-2 text-xs sm:text-sm font-medium text-slate-900">Belum Ada Data</h3>
                                        <p class="mt-1 text-xs sm:text-sm text-slate-500">
                                            Tidak ada pelanggan dengan status ini.
                                        </p>
                                    </td>
                                </tr>
                            </tbody>

                            <!-- Table Body for Riwayat Tab -->
                            <tbody v-else class="divide-y divide-slate-100 bg-white">
                                <tr
                                    v-for="(suspension, index) in paginatedSuspensions"
                                    :key="suspension.id || index"
                                    class="transition-colors duration-150 hover:bg-slate-50/80"
                                >
                                    <td class="whitespace-nowrap print:whitespace-normal px-2 py-3 sm:px-4 sm:py-4 text-center text-xs font-medium text-slate-400">
                                        {{ index + 1 }}
                                    </td>
                                    <td class="whitespace-nowrap print:whitespace-normal px-3 py-3 sm:px-6 sm:py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-600 ring-1 ring-slate-200">
                                                {{ (suspension.customer?.name || '?').charAt(0).toUpperCase() }}
                                            </div>
                                            <div>
                                                <div class="font-semibold text-slate-800">
                                                    {{ suspension.customer?.name }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3 sm:px-6 sm:py-4 text-xs sm:text-sm text-slate-600">
                                        {{ suspension.customer?.area || '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3 sm:px-6 sm:py-4 text-xs sm:text-sm text-slate-600">
                                        {{ suspension.customer?.alamat || '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3 sm:px-6 sm:py-4 text-xs text-slate-600">
                                        {{ suspension.customer?.paket || '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3 sm:px-6 sm:py-4 text-center text-xs font-medium text-amber-700">
                                        {{ formatDate(suspension.suspend_start_date) }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3 sm:px-6 sm:py-4 text-center text-xs font-medium text-amber-700">
                                        {{ formatDate(suspension.suspend_end_date) }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3 sm:px-6 sm:py-4 text-center text-xs font-medium text-indigo-600">
                                        {{ calculateDays(suspension.suspend_start_date, suspension.suspend_end_date) }} <span v-if="calculateDays(suspension.suspend_start_date, suspension.suspend_end_date) !== '-'">Hari</span>
                                    </td>
                                </tr>
                                <tr v-if="allSuspensions.length === 0">
                                    <td colspan="8" class="px-6 py-12 text-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <h3 class="mt-2 text-xs sm:text-sm font-medium text-slate-900">Belum Ada Riwayat</h3>
                                        <p class="mt-1 text-xs sm:text-sm text-slate-500">
                                            Belum ada pelanggan yang disuspend.
                                        </p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination Controls -->
                    <div class="px-3 py-3 sm:px-6 sm:py-4 flex items-center justify-between border-t border-slate-200 bg-white print:hidden" v-if="activeTab !== 'riwayat'">
                        <div class="text-xs sm:text-sm text-slate-500">
                            Menampilkan <span class="font-medium text-slate-900">{{ (currentPage - 1) * itemsPerPage + 1 }}</span> - 
                            <span class="font-medium text-slate-900">{{ Math.min(currentPage * itemsPerPage, filteredCustomers.length) }}</span> 
                            dari <span class="font-medium text-slate-900">{{ filteredCustomers.length }}</span> data
                        </div>
                        <div class="flex items-center gap-1">
                            <button 
                                @click="currentPage--" 
                                :disabled="currentPage === 1"
                                class="px-3 py-1.5 border border-slate-200 rounded-lg text-slate-500 bg-white hover:bg-slate-50 hover:text-slate-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium">
                                &lt;
                            </button>
                            <button class="px-3 py-1.5 rounded-lg bg-indigo-600 text-white font-medium shadow-sm">{{ currentPage }}</button>
                            <button 
                                @click="currentPage++" 
                                :disabled="currentPage === totalPages"
                                class="px-3 py-1.5 border border-slate-200 rounded-lg text-slate-500 bg-white hover:bg-slate-50 hover:text-slate-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium">
                                &gt;
                            </button>
                        </div>
                    </div>
                    
                    <div class="px-3 py-3 sm:px-6 sm:py-4 flex items-center justify-between border-t border-slate-200 bg-white print:hidden" v-if="activeTab === 'riwayat'">
                        <div class="text-xs sm:text-sm text-slate-500">
                            Menampilkan <span class="font-medium text-slate-900">{{ (currentPageSuspensions - 1) * itemsPerPage + 1 }}</span> - 
                            <span class="font-medium text-slate-900">{{ Math.min(currentPageSuspensions * itemsPerPage, allSuspensions.length) }}</span> 
                            dari <span class="font-medium text-slate-900">{{ allSuspensions.length }}</span> data
                        </div>
                        <div class="flex items-center gap-1">
                            <button 
                                @click="currentPageSuspensions--" 
                                :disabled="currentPageSuspensions === 1"
                                class="px-3 py-1.5 border border-slate-200 rounded-lg text-slate-500 bg-white hover:bg-slate-50 hover:text-slate-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium">
                                &lt;
                            </button>
                            <button class="px-3 py-1.5 rounded-lg bg-indigo-600 text-white font-medium shadow-sm">{{ currentPageSuspensions }}</button>
                            <button 
                                @click="currentPageSuspensions++" 
                                :disabled="currentPageSuspensions === totalPagesSuspensions"
                                class="px-3 py-1.5 border border-slate-200 rounded-lg text-slate-500 bg-white hover:bg-slate-50 hover:text-slate-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium">
                                &gt;
                            </button>
                        </div>
                    </div>

                </div>

            </div>
        </div>

        <!-- Edit Customer Modal -->
        <div
            v-if="isEditModalOpen"
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title"
            role="dialog"
            aria-modal="true"
        >
            <div class="flex min-h-screen items-center justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" @click="closeEditModal" aria-hidden="true"></div>

                <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>

                <div
                    class="inline-block transform overflow-hidden rounded-2xl bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle"
                >
                    <div class="border-b border-slate-100 bg-white px-6 py-5 sm:px-6">
                        <h3 class="text-lg font-bold text-slate-800" id="modal-title">
                            Edit Pelanggan
                        </h3>
                        <p class="mt-1 text-xs text-slate-500">
                            Perbarui informasi data pelanggan.
                        </p>
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
                                        class="w-full rounded-xl border border-slate-300 py-2.5 px-3 text-xs sm:text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                        required
                                    />
                                    <p v-if="editForm.errors.name" class="mt-1 text-xs text-rose-600">{{ editForm.errors.name }}</p>
                                </div>

                                <!-- Area -->
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-600">
                                        Area / Wilayah
                                    </label>
                                    <select
                                        v-model="editForm.area"
                                        class="w-full rounded-xl border border-slate-300 py-2.5 px-3 text-xs sm:text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 bg-white"
                                    >
                                        <option value="">-- Pilih Area --</option>
                                        <option v-for="a in areas" :key="a" :value="a">{{ a }}</option>
                                    </select>
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
                                        class="w-full rounded-xl border border-slate-300 py-2.5 px-3 text-xs sm:text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
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
                                        class="w-full rounded-xl border border-slate-300 py-2.5 px-3 text-xs sm:text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
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
                                        class="w-full rounded-xl border border-slate-300 py-2.5 px-3 text-xs sm:text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
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
                                        class="w-full rounded-xl border border-slate-300 py-2.5 px-3 text-xs sm:text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                    >
                                        <option value="Aktif">Aktif</option>
                                        <option value="Suspend">Suspend</option>
                                        <option value="Berhenti">Berhenti</option>
                                        <option value="Gratis">Gratis</option>
                                    </select>
                                    <p v-if="editForm.errors.status_pelanggan" class="mt-1 text-xs text-rose-600">{{ editForm.errors.status_pelanggan }}</p>
                                </div>

                                <!-- Conditional Fields for Suspend -->
                                <div v-if="editForm.status_pelanggan === 'Suspend' || editForm.status_pelanggan === 'Berhenti sementara'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-600">
                                            Tanggal Mulai
                                        </label>
                                        <input
                                            v-model="editForm.suspend_start_date"
                                            type="date"
                                            class="w-full rounded-xl border border-slate-300 py-2.5 px-3 text-xs sm:text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
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
                                            class="w-full rounded-xl border border-slate-300 py-2.5 px-3 text-xs sm:text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                        />
                                        <p v-if="editForm.errors.suspend_end_date" class="mt-1 text-xs text-rose-600">{{ editForm.errors.suspend_end_date }}</p>
                                    </div>
                                </div>

                                <!-- Conditional Field for Pengaktifan (Aktif dari Suspend) -->
                                <div v-if="editForm.status_pelanggan === 'Aktif' && (editingCustomer?.status_pelanggan === 'Suspend' || editingCustomer?.status_pelanggan === 'Berhenti sementara')">
                                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-600">
                                        Tanggal Pengaktifan <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="editForm.suspend_end_date"
                                        type="date"
                                        class="w-full rounded-xl border border-slate-300 py-2.5 px-3 text-xs sm:text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                        required
                                    />
                                    <p class="mt-1 text-xs text-slate-500">Tanggal ini akan dicatat ke dalam riwayat suspend sebagai tanggal akhir.</p>
                                    <p v-if="editForm.errors.suspend_end_date" class="mt-1 text-xs text-rose-600">{{ editForm.errors.suspend_end_date }}</p>
                                </div>

                                <!-- Conditional Field for Berhenti -->
                                <div v-if="editForm.status_pelanggan === 'Berhenti' || editForm.status_pelanggan === 'Stop Permanen' || editForm.status_pelanggan === 'Nonaktif'">
                                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-600">
                                        Tanggal Berhenti
                                    </label>
                                    <input
                                        v-model="editForm.stop_date"
                                        type="date"
                                        class="w-full rounded-xl border border-slate-300 py-2.5 px-3 text-xs sm:text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                    />
                                    <p v-if="editForm.errors.stop_date" class="mt-1 text-xs text-rose-600">{{ editForm.errors.stop_date }}</p>
                                </div>

                                <!-- Tagihan -->
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-600">
                                        Tagihan (Rp) <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="editForm.amount"
                                        type="number"
                                        min="0"
                                        class="w-full rounded-xl border border-slate-300 py-2.5 px-3 text-xs sm:text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
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
        </div>

        <!-- Delete Confirmation Modal (Native confirm is used, but if needed, can build a modal) -->
    </AuthenticatedLayout>
</template>
