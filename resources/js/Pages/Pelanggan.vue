<script setup>
import { ref, computed, watch } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    customers: {
        type: [Array, Object],
        default: () => []
    },
    areas: {
        type: Array,
        default: () => []
    },
    pakets: {
        type: Array,
        default: () => []
    }
});

// Extract array from customers (handles both flat array and paginator object)
const customerList = computed(() => {
    if (!props.customers) return [];
    if (Array.isArray(props.customers)) return props.customers;
    if (Array.isArray(props.customers.data)) return props.customers.data;
    return [];
});

// Search and Filter states
const searchQuery = ref('');
const selectedArea = ref('');
const selectedStatus = ref('');
const sortBy = ref('name');
const sortDirection = ref('asc');

// Pagination states
const perPage = ref(15);
const currentPage = ref(1);

// Available unique areas
const availableAreas = computed(() => {
    const set = new Set();
    if (Array.isArray(props.areas)) {
        props.areas.forEach(a => {
            if (typeof a === 'string' && a.trim()) set.add(a.trim());
            else if (a && a.name) set.add(a.name.trim());
        });
    }
    customerList.value.forEach(c => {
        if (c.area && typeof c.area === 'string' && c.area.trim()) {
            set.add(c.area.trim());
        }
    });
    return Array.from(set).sort();
});

// Available unique pakets
const availablePakets = computed(() => {
    const set = new Set();
    if (Array.isArray(props.pakets)) {
        props.pakets.forEach(p => {
            if (typeof p === 'string' && p.trim()) set.add(p.trim());
            else if (p && p.name) set.add(p.name.trim());
        });
    }
    customerList.value.forEach(c => {
        if (c.paket && typeof c.paket === 'string' && c.paket.trim()) {
            set.add(c.paket.trim());
        }
    });
    return Array.from(set).sort();
});

// Summary Counts (Master Data only)
const totalCount = computed(() => customerList.value.length);
const aktifCount = computed(() => customerList.value.filter(c => (c.status_pelanggan || 'Aktif').toLowerCase() === 'aktif').length);
const nonaktifCount = computed(() => customerList.value.filter(c => (c.status_pelanggan || '').toLowerCase() === 'nonaktif').length);
const suspendCount = computed(() => customerList.value.filter(c => ['suspend', 'isolir', 'berhenti'].includes((c.status_pelanggan || '').toLowerCase())).length);

// Filtered and sorted customers
const filteredCustomers = computed(() => {
    const query = searchQuery.value.toLowerCase().trim();

    return customerList.value.filter(customer => {
        const name = (customer.name || '').toLowerCase();
        const area = (customer.area || '').toLowerCase();
        const alamat = (customer.alamat || '').toLowerCase();
        const paket = (customer.paket || '').toLowerCase();

        const matchesSearch = !query ||
            name.includes(query) ||
            area.includes(query) ||
            alamat.includes(query) ||
            paket.includes(query);

        const matchesArea = !selectedArea.value || customer.area === selectedArea.value;
        const matchesStatus = !selectedStatus.value || (customer.status_pelanggan || 'Aktif') === selectedStatus.value;

        return matchesSearch && matchesArea && matchesStatus;
    }).sort((a, b) => {
        let valA = a[sortBy.value] ?? '';
        let valB = b[sortBy.value] ?? '';

        if (sortBy.value === 'base_amount') {
            valA = Number(a.base_amount ?? a.amount ?? 0);
            valB = Number(b.base_amount ?? b.amount ?? 0);
            return sortDirection.value === 'asc' ? valA - valB : valB - valA;
        }

        if (typeof valA === 'string') valA = valA.toLowerCase();
        if (typeof valB === 'string') valB = valB.toLowerCase();

        if (valA < valB) return sortDirection.value === 'asc' ? -1 : 1;
        if (valA > valB) return sortDirection.value === 'asc' ? 1 : -1;
        return 0;
    });
});

// Reset pagination on filter change
watch([searchQuery, selectedArea, selectedStatus], () => {
    currentPage.value = 1;
});

const totalPages = computed(() => Math.ceil(filteredCustomers.value.length / perPage.value) || 1);

const paginatedCustomers = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    return filteredCustomers.value.slice(start, start + perPage.value);
});

const toggleSort = (field) => {
    if (sortBy.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = field;
        sortDirection.value = 'asc';
    }
};

const resetFilters = () => {
    searchQuery.value = '';
    selectedArea.value = '';
    selectedStatus.value = '';
    currentPage.value = 1;
};

// Currency Formatter
const formatRupiah = (val) => {
    const num = Number(val) || 0;
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(num);
};

// Date Formatter
const formatDate = (val) => {
    if (!val) return '-';
    try {
        const d = new Date(val);
        if (isNaN(d.getTime())) return val;
        return new Intl.DateTimeFormat('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric'
        }).format(d);
    } catch {
        return val;
    }
};

// Status Badge Styling
const getStatusBadge = (status) => {
    const s = (status || 'Aktif').toLowerCase();
    if (s === 'aktif') {
        return {
            label: 'Aktif',
            classes: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20 border-emerald-200'
        };
    }
    if (s === 'suspend' || s === 'isolir') {
        return {
            label: status || 'Suspend',
            classes: 'bg-amber-50 text-amber-700 ring-1 ring-amber-600/20 border-amber-200'
        };
    }
    if (s === 'berhenti' || s === 'putus') {
        return {
            label: status || 'Berhenti',
            classes: 'bg-rose-50 text-rose-700 ring-1 ring-rose-600/20 border-rose-200'
        };
    }
    return {
        label: status || 'Nonaktif',
        classes: 'bg-slate-100 text-slate-700 ring-1 ring-slate-600/10 border-slate-200'
    };
};

// Avatar Initials
const getInitials = (name) => {
    if (!name) return '?';
    return name
        .trim()
        .split(' ')
        .slice(0, 2)
        .map(n => n.charAt(0).toUpperCase())
        .join('');
};

// =================== CREATE MODAL ===================
const showCreateModal = ref(false);
const createForm = useForm({
    name: '',
    area: '',
    alamat: '',
    paket: '',
    register_date: new Date().toISOString().split('T')[0],
    status_pelanggan: 'Aktif',
    base_amount: '',
    amount: ''
});

const openCreateModal = () => {
    createForm.reset();
    createForm.clearErrors();
    createForm.register_date = new Date().toISOString().split('T')[0];
    createForm.status_pelanggan = 'Aktif';
    showCreateModal.value = true;
};

const closeCreateModal = () => {
    showCreateModal.value = false;
    createForm.reset();
    createForm.clearErrors();
};

const submitCreate = () => {
    createForm.amount = createForm.base_amount;
    createForm.post(route('pelanggan.store'), {
        preserveScroll: true,
        onSuccess: () => {
            closeCreateModal();
        }
    });
};

// =================== EDIT MODAL ===================
const showEditModal = ref(false);
const selectedCustomer = ref(null);
const editForm = useForm({
    id: null,
    name: '',
    area: '',
    alamat: '',
    paket: '',
    register_date: '',
    status_pelanggan: 'Aktif',
    base_amount: '',
    amount: ''
});

const openEditModal = (customer) => {
    selectedCustomer.value = customer;
    const baseVal = customer.base_amount !== undefined && customer.base_amount !== null
        ? customer.base_amount
        : (customer.amount !== undefined && customer.amount !== null ? customer.amount : '');

    editForm.id = customer.id;
    editForm.name = customer.name || '';
    editForm.area = customer.area || '';
    editForm.alamat = customer.alamat || '';
    editForm.paket = customer.paket || '';
    editForm.register_date = customer.register_date ? customer.register_date.split('T')[0] : '';
    editForm.status_pelanggan = customer.status_pelanggan || 'Aktif';
    editForm.base_amount = baseVal;
    editForm.amount = baseVal;
    editForm.clearErrors();
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
    selectedCustomer.value = null;
    editForm.reset();
    editForm.clearErrors();
};

const submitEdit = () => {
    if (!editForm.id) return;
    editForm.amount = editForm.base_amount;
    editForm.put(route('pelanggan.update', editForm.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeEditModal();
        }
    });
};

// =================== DELETE MODAL ===================
const showDeleteModal = ref(false);
const customerToDelete = ref(null);
const deleteForm = useForm({});

const confirmDelete = (customer) => {
    customerToDelete.value = customer;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    customerToDelete.value = null;
};

const submitDelete = () => {
    if (!customerToDelete.value) return;
    deleteForm.delete(route('pelanggan.destroy', customerToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeDeleteModal();
        }
    });
};
</script>

<template>
    <Head title="Data Master Pelanggan" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-slate-800">
                        Data Master Pelanggan
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Kelola data pelanggan, wilayah area, paket langganan, dan tarif dasar.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        @click="openCreateModal"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition duration-150 ease-in-out hover:bg-indigo-700 hover:shadow focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        <span>Tambah Pelanggan</span>
                    </button>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- Master Stats Overview (Pure Customer Counters) -->
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-sm transition hover:border-slate-300">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-slate-500">Total Pelanggan</p>
                                <p class="text-xl font-bold text-slate-800">{{ totalCount }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-sm transition hover:border-slate-300">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-slate-500">Pelanggan Aktif</p>
                                <p class="text-xl font-bold text-emerald-600">{{ aktifCount }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-sm transition hover:border-slate-300">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-slate-500">Nonaktif</p>
                                <p class="text-xl font-bold text-slate-700">{{ nonaktifCount }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-sm transition hover:border-slate-300">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-slate-500">Suspend / Berhenti</p>
                                <p class="text-xl font-bold text-amber-600">{{ suspendCount }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Search and Filter Bar -->
                <div class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-sm">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <!-- Search Box -->
                        <div class="relative flex-1">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                                </svg>
                            </div>
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Cari nama, area, alamat, paket..."
                                class="w-full rounded-lg border border-slate-300 bg-white py-2 pl-10 pr-4 text-sm text-slate-800 placeholder-slate-400 transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                            />
                            <button
                                v-if="searchQuery"
                                @click="searchQuery = ''"
                                class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Filters -->
                        <div class="flex flex-wrap items-center gap-2">
                            <!-- Area Filter -->
                            <select
                                v-model="selectedArea"
                                class="rounded-lg border border-slate-300 bg-white py-2 pl-3 pr-8 text-sm text-slate-700 transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                            >
                                <option value="">Semua Area</option>
                                <option v-for="area in availableAreas" :key="area" :value="area">
                                    {{ area }}
                                </option>
                            </select>

                            <!-- Status Filter -->
                            <select
                                v-model="selectedStatus"
                                class="rounded-lg border border-slate-300 bg-white py-2 pl-3 pr-8 text-sm text-slate-700 transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                            >
                                <option value="">Semua Status</option>
                                <option value="Aktif">Aktif</option>
                                <option value="Nonaktif">Nonaktif</option>
                                <option value="Suspend">Suspend</option>
                                <option value="Berhenti">Berhenti</option>
                            </select>

                            <!-- Reset Button -->
                            <button
                                v-if="searchQuery || selectedArea || selectedStatus"
                                @click="resetFilters"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-800"
                                title="Reset Filter"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                                </svg>
                                <span>Reset</span>
                            </button>
                        </div>
                    </div>

                    <!-- Active Filter Information -->
                    <div class="mt-3 flex items-center justify-between border-t border-slate-100 pt-3 text-xs text-slate-500">
                        <div>
                            Menampilkan <span class="font-semibold text-slate-700">{{ filteredCustomers.length }}</span> dari <span class="font-semibold text-slate-700">{{ totalCount }}</span> pelanggan
                        </div>
                        <div class="flex items-center gap-2">
                            <span>Tampilkan:</span>
                            <select
                                v-model="perPage"
                                class="rounded border border-slate-200 bg-white py-1 px-2 text-xs text-slate-700 focus:border-indigo-500 focus:outline-none"
                            >
                                <option :value="10">10</option>
                                <option :value="15">15</option>
                                <option :value="25">25</option>
                                <option :value="50">50</option>
                                <option :value="100">100</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Table Card -->
                <div class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                            <thead class="bg-slate-50/80 text-xs font-semibold uppercase tracking-wider text-slate-600">
                                <tr>
                                    <th scope="col" class="py-3.5 pl-4 pr-2 text-center sm:pl-6 w-12">
                                        #
                                    </th>
                                    <th
                                        scope="col"
                                        @click="toggleSort('name')"
                                        class="cursor-pointer py-3.5 px-3 transition hover:text-indigo-600"
                                    >
                                        <div class="flex items-center gap-1.5">
                                            <span>Nama Pelanggan</span>
                                            <svg v-if="sortBy === 'name'" class="h-4 w-4" :class="sortDirection === 'asc' ? '' : 'rotate-180 transform'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                                            </svg>
                                        </div>
                                    </th>
                                    <th
                                        scope="col"
                                        @click="toggleSort('area')"
                                        class="cursor-pointer py-3.5 px-3 transition hover:text-indigo-600"
                                    >
                                        <div class="flex items-center gap-1.5">
                                            <span>Area</span>
                                            <svg v-if="sortBy === 'area'" class="h-4 w-4" :class="sortDirection === 'asc' ? '' : 'rotate-180 transform'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                                            </svg>
                                        </div>
                                    </th>
                                    <th scope="col" class="py-3.5 px-3">
                                        Alamat
                                    </th>
                                    <th
                                        scope="col"
                                        @click="toggleSort('paket')"
                                        class="cursor-pointer py-3.5 px-3 transition hover:text-indigo-600"
                                    >
                                        <div class="flex items-center gap-1.5">
                                            <span>Paket</span>
                                            <svg v-if="sortBy === 'paket'" class="h-4 w-4" :class="sortDirection === 'asc' ? '' : 'rotate-180 transform'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                                            </svg>
                                        </div>
                                    </th>
                                    <th
                                        scope="col"
                                        @click="toggleSort('register_date')"
                                        class="cursor-pointer py-3.5 px-3 transition hover:text-indigo-600"
                                    >
                                        <div class="flex items-center gap-1.5">
                                            <span>Tgl Register</span>
                                            <svg v-if="sortBy === 'register_date'" class="h-4 w-4" :class="sortDirection === 'asc' ? '' : 'rotate-180 transform'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                                            </svg>
                                        </div>
                                    </th>
                                    <th
                                        scope="col"
                                        @click="toggleSort('base_amount')"
                                        class="cursor-pointer py-3.5 px-3 transition hover:text-indigo-600 text-right"
                                    >
                                        <div class="flex items-center justify-end gap-1.5">
                                            <span>Tarif / Base Amount</span>
                                            <svg v-if="sortBy === 'base_amount'" class="h-4 w-4" :class="sortDirection === 'asc' ? '' : 'rotate-180 transform'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                                            </svg>
                                        </div>
                                    </th>
                                    <th
                                        scope="col"
                                        @click="toggleSort('status_pelanggan')"
                                        class="cursor-pointer py-3.5 px-3 text-center transition hover:text-indigo-600"
                                    >
                                        <div class="flex items-center justify-center gap-1.5">
                                            <span>Status</span>
                                            <svg v-if="sortBy === 'status_pelanggan'" class="h-4 w-4" :class="sortDirection === 'asc' ? '' : 'rotate-180 transform'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                                            </svg>
                                        </div>
                                    </th>
                                    <th scope="col" class="py-3.5 pl-3 pr-4 text-center sm:pr-6 w-28">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <tr
                                    v-for="(customer, idx) in paginatedCustomers"
                                    :key="customer.id"
                                    class="transition duration-100 ease-in-out hover:bg-slate-50/70"
                                >
                                    <!-- No -->
                                    <td class="py-4 pl-4 pr-2 text-center text-xs font-medium text-slate-400 sm:pl-6">
                                        {{ (currentPage - 1) * perPage + idx + 1 }}
                                    </td>

                                    <!-- Nama Pelanggan -->
                                    <td class="py-4 px-3">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-tr from-indigo-600 to-indigo-400 text-xs font-bold text-white shadow-sm">
                                                {{ getInitials(customer.name) }}
                                            </div>
                                            <div class="min-w-0">
                                                <div class="font-semibold text-slate-800 truncate max-w-[180px] sm:max-w-none">
                                                    {{ customer.name }}
                                                </div>
                                                <div v-if="customer.id" class="text-xs text-slate-400">
                                                    ID: #{{ customer.id }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Area -->
                                    <td class="py-4 px-3 whitespace-nowrap">
                                        <span class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                                            <svg class="h-3 w-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                                            </svg>
                                            {{ customer.area || '-' }}
                                        </span>
                                    </td>

                                    <!-- Alamat -->
                                    <td class="py-4 px-3 text-xs text-slate-600">
                                        <div class="max-w-xs truncate" :title="customer.alamat">
                                            {{ customer.alamat || '-' }}
                                        </div>
                                    </td>

                                    <!-- Paket -->
                                    <td class="py-4 px-3 whitespace-nowrap">
                                        <span v-if="customer.paket" class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-medium text-indigo-700">
                                            {{ customer.paket }}
                                        </span>
                                        <span v-else class="text-xs text-slate-400">-</span>
                                    </td>

                                    <!-- Tgl Register -->
                                    <td class="py-4 px-3 whitespace-nowrap text-xs text-slate-600">
                                        {{ formatDate(customer.register_date) }}
                                    </td>

                                    <!-- Base Amount / Tarif -->
                                    <td class="py-4 px-3 whitespace-nowrap text-right text-xs font-semibold text-slate-800">
                                        {{ formatRupiah(customer.base_amount ?? customer.amount ?? 0) }}
                                    </td>

                                    <!-- Status Pelanggan -->
                                    <td class="py-4 px-3 whitespace-nowrap text-center">
                                        <span
                                            :class="getStatusBadge(customer.status_pelanggan).classes"
                                            class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium"
                                        >
                                            {{ getStatusBadge(customer.status_pelanggan).label }}
                                        </span>
                                    </td>

                                    <!-- Aksi -->
                                    <td class="py-4 pl-3 pr-4 text-center sm:pr-6 whitespace-nowrap">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <!-- Edit Button -->
                                            <button
                                                type="button"
                                                @click="openEditModal(customer)"
                                                class="rounded-lg p-1.5 text-slate-500 transition hover:bg-indigo-50 hover:text-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                                title="Edit Pelanggan"
                                            >
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                                </svg>
                                            </button>

                                            <!-- Delete Button -->
                                            <button
                                                type="button"
                                                @click="confirmDelete(customer)"
                                                class="rounded-lg p-1.5 text-slate-500 transition hover:bg-rose-50 hover:text-rose-600 focus:outline-none focus:ring-2 focus:ring-rose-500"
                                                title="Hapus Pelanggan"
                                            >
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Empty State -->
                                <tr v-if="paginatedCustomers.length === 0">
                                    <td colspan="9" class="py-12 text-center">
                                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                            </svg>
                                        </div>
                                        <h3 class="mt-3 text-sm font-semibold text-slate-800">Tidak ada data pelanggan</h3>
                                        <p class="mt-1 text-xs text-slate-500">
                                            {{ searchQuery || selectedArea || selectedStatus ? 'Coba ubah kata kunci atau bersihkan filter pencarian.' : 'Belum ada data pelanggan yang ditambahkan.' }}
                                        </p>
                                        <div v-if="searchQuery || selectedArea || selectedStatus" class="mt-4">
                                            <button
                                                type="button"
                                                @click="resetFilters"
                                                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
                                            >
                                                Bersihkan Filter
                                            </button>
                                        </div>
                                        <div v-else class="mt-4">
                                            <button
                                                type="button"
                                                @click="openCreateModal"
                                                class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-indigo-700"
                                            >
                                                + Tambah Pelanggan Pertama
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination Controls -->
                    <div
                        v-if="totalPages > 1"
                        class="flex flex-col items-center justify-between gap-3 border-t border-slate-200 px-4 py-3 sm:flex-row sm:px-6"
                    >
                        <div class="text-xs text-slate-500">
                            Menampilkan halaman <span class="font-medium text-slate-700">{{ currentPage }}</span> dari <span class="font-medium text-slate-700">{{ totalPages }}</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <button
                                type="button"
                                :disabled="currentPage === 1"
                                @click="currentPage--"
                                class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                            >
                                Sebelumnya
                            </button>
                            <div class="flex items-center gap-1 px-1">
                                <template v-for="page in totalPages" :key="page">
                                    <button
                                        v-if="page === 1 || page === totalPages || (page >= currentPage - 1 && page <= currentPage + 1)"
                                        type="button"
                                        @click="currentPage = page"
                                        :class="page === currentPage ? 'bg-indigo-600 text-white font-semibold' : 'bg-white text-slate-700 border border-slate-300 hover:bg-slate-50'"
                                        class="h-7 w-7 rounded-lg text-xs transition"
                                    >
                                        {{ page }}
                                    </button>
                                    <span
                                        v-else-if="(page === currentPage - 2 && page > 1) || (page === currentPage + 2 && page < totalPages)"
                                        class="px-1 text-xs text-slate-400"
                                    >
                                        ...
                                    </span>
                                </template>
                            </div>
                            <button
                                type="button"
                                :disabled="currentPage === totalPages"
                                @click="currentPage++"
                                class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                            >
                                Selanjutnya
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- ================= CREATE CUSTOMER MODAL ================= -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="closeCreateModal"></div>
            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div class="relative w-full max-w-xl transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 border border-slate-100">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/75 px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-800">Tambah Pelanggan Baru</h3>
                                <p class="text-xs text-slate-500">Masukkan data master pelanggan baru ke dalam sistem.</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="closeCreateModal"
                            class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 focus:outline-none"
                        >
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Modal Body Form -->
                    <form @submit.prevent="submitCreate">
                        <div class="p-6 space-y-4">
                            <!-- Nama Pelanggan -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Nama Pelanggan <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="createForm.name"
                                    type="text"
                                    required
                                    placeholder="Contoh: Budi Santoso"
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 placeholder-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                                />
                                <p v-if="createForm.errors.name" class="mt-1 text-xs text-rose-600">{{ createForm.errors.name }}</p>
                            </div>

                            <!-- Area & Paket -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                        Area / Wilayah <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="createForm.area"
                                        type="text"
                                        list="create-areas-list"
                                        required
                                        placeholder="Contoh: Area Timur, RW 01"
                                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 placeholder-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                                    />
                                    <datalist id="create-areas-list">
                                        <option v-for="a in availableAreas" :key="a" :value="a" />
                                    </datalist>
                                    <p v-if="createForm.errors.area" class="mt-1 text-xs text-rose-600">{{ createForm.errors.area }}</p>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                        Paket Langganan
                                    </label>
                                    <input
                                        v-model="createForm.paket"
                                        type="text"
                                        list="create-pakets-list"
                                        placeholder="Contoh: 20 Mbps, Home 2"
                                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 placeholder-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                                    />
                                    <datalist id="create-pakets-list">
                                        <option v-for="p in availablePakets" :key="p" :value="p" />
                                    </datalist>
                                    <p v-if="createForm.errors.paket" class="mt-1 text-xs text-rose-600">{{ createForm.errors.paket }}</p>
                                </div>
                            </div>

                            <!-- Alamat -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Alamat Lengkap
                                </label>
                                <textarea
                                    v-model="createForm.alamat"
                                    rows="2"
                                    placeholder="Jl. Melati No. 12, RT 02/RW 04..."
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 placeholder-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                                ></textarea>
                                <p v-if="createForm.errors.alamat" class="mt-1 text-xs text-rose-600">{{ createForm.errors.alamat }}</p>
                            </div>

                            <!-- Base Amount / Tarif & Tgl Register -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                        Tarif / Base Amount (Rp) <span class="text-rose-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                            <span class="text-xs font-semibold text-slate-400">Rp</span>
                                        </div>
                                        <input
                                            v-model="createForm.base_amount"
                                            type="number"
                                            min="0"
                                            step="1000"
                                            required
                                            placeholder="150000"
                                            class="w-full rounded-lg border border-slate-300 py-2 pl-9 pr-3 text-sm text-slate-800 placeholder-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                                        />
                                    </div>
                                    <p v-if="createForm.errors.base_amount" class="mt-1 text-xs text-rose-600">{{ createForm.errors.base_amount }}</p>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                        Tanggal Registrasi
                                    </label>
                                    <input
                                        v-model="createForm.register_date"
                                        type="date"
                                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                                    />
                                    <p v-if="createForm.errors.register_date" class="mt-1 text-xs text-rose-600">{{ createForm.errors.register_date }}</p>
                                </div>
                            </div>

                            <!-- Status Pelanggan -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Status Pelanggan <span class="text-rose-500">*</span>
                                </label>
                                <select
                                    v-model="createForm.status_pelanggan"
                                    required
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                                >
                                    <option value="Aktif">Aktif</option>
                                    <option value="Nonaktif">Nonaktif</option>
                                    <option value="Suspend">Suspend</option>
                                    <option value="Berhenti">Berhenti</option>
                                </select>
                                <p v-if="createForm.errors.status_pelanggan" class="mt-1 text-xs text-rose-600">{{ createForm.errors.status_pelanggan }}</p>
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="flex items-center justify-end gap-3 border-t border-slate-100 bg-slate-50/75 px-6 py-4">
                            <button
                                type="button"
                                @click="closeCreateModal"
                                class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="createForm.processing"
                                class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none disabled:opacity-50"
                            >
                                <svg v-if="createForm.processing" class="h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>{{ createForm.processing ? 'Menyimpan...' : 'Simpan Pelanggan' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ================= EDIT CUSTOMER MODAL ================= -->
        <div v-if="showEditModal" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="closeEditModal"></div>
            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div class="relative w-full max-w-xl transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 border border-slate-100">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/75 px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100 text-amber-600">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-800">Edit Data Pelanggan</h3>
                                <p class="text-xs text-slate-500">Perbarui master data pelanggan dan tarif dasar langganan.</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="closeEditModal"
                            class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 focus:outline-none"
                        >
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Modal Body Form -->
                    <form @submit.prevent="submitEdit">
                        <div class="p-6 space-y-4">
                            <!-- Nama Pelanggan -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Nama Pelanggan <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="editForm.name"
                                    type="text"
                                    required
                                    placeholder="Contoh: Budi Santoso"
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 placeholder-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                                />
                                <p v-if="editForm.errors.name" class="mt-1 text-xs text-rose-600">{{ editForm.errors.name }}</p>
                            </div>

                            <!-- Area & Paket -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                        Area / Wilayah <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="editForm.area"
                                        type="text"
                                        list="edit-areas-list"
                                        required
                                        placeholder="Contoh: Area Timur, RW 01"
                                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 placeholder-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                                    />
                                    <datalist id="edit-areas-list">
                                        <option v-for="a in availableAreas" :key="a" :value="a" />
                                    </datalist>
                                    <p v-if="editForm.errors.area" class="mt-1 text-xs text-rose-600">{{ editForm.errors.area }}</p>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                        Paket Langganan
                                    </label>
                                    <input
                                        v-model="editForm.paket"
                                        type="text"
                                        list="edit-pakets-list"
                                        placeholder="Contoh: 20 Mbps, Home 2"
                                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 placeholder-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                                    />
                                    <datalist id="edit-pakets-list">
                                        <option v-for="p in availablePakets" :key="p" :value="p" />
                                    </datalist>
                                    <p v-if="editForm.errors.paket" class="mt-1 text-xs text-rose-600">{{ editForm.errors.paket }}</p>
                                </div>
                            </div>

                            <!-- Alamat -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Alamat Lengkap
                                </label>
                                <textarea
                                    v-model="editForm.alamat"
                                    rows="2"
                                    placeholder="Jl. Melati No. 12, RT 02/RW 04..."
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 placeholder-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                                ></textarea>
                                <p v-if="editForm.errors.alamat" class="mt-1 text-xs text-rose-600">{{ editForm.errors.alamat }}</p>
                            </div>

                            <!-- Base Amount / Tarif & Tgl Register -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                        Tarif / Base Amount (Rp) <span class="text-rose-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                            <span class="text-xs font-semibold text-slate-400">Rp</span>
                                        </div>
                                        <input
                                            v-model="editForm.base_amount"
                                            type="number"
                                            min="0"
                                            step="1000"
                                            required
                                            placeholder="150000"
                                            class="w-full rounded-lg border border-slate-300 py-2 pl-9 pr-3 text-sm text-slate-800 placeholder-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                                        />
                                    </div>
                                    <p v-if="editForm.errors.base_amount" class="mt-1 text-xs text-rose-600">{{ editForm.errors.base_amount }}</p>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                        Tanggal Registrasi
                                    </label>
                                    <input
                                        v-model="editForm.register_date"
                                        type="date"
                                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                                    />
                                    <p v-if="editForm.errors.register_date" class="mt-1 text-xs text-rose-600">{{ editForm.errors.register_date }}</p>
                                </div>
                            </div>

                            <!-- Status Pelanggan -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Status Pelanggan <span class="text-rose-500">*</span>
                                </label>
                                <select
                                    v-model="editForm.status_pelanggan"
                                    required
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                                >
                                    <option value="Aktif">Aktif</option>
                                    <option value="Nonaktif">Nonaktif</option>
                                    <option value="Suspend">Suspend</option>
                                    <option value="Berhenti">Berhenti</option>
                                </select>
                                <p v-if="editForm.errors.status_pelanggan" class="mt-1 text-xs text-rose-600">{{ editForm.errors.status_pelanggan }}</p>
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="flex items-center justify-end gap-3 border-t border-slate-100 bg-slate-50/75 px-6 py-4">
                            <button
                                type="button"
                                @click="closeEditModal"
                                class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="editForm.processing"
                                class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none disabled:opacity-50"
                            >
                                <svg v-if="editForm.processing" class="h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>{{ editForm.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ================= DELETE CONFIRMATION MODAL ================= -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="closeDeleteModal"></div>
            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div class="relative w-full max-w-md transform overflow-hidden rounded-2xl bg-white p-6 text-left shadow-2xl transition-all sm:my-8 border border-slate-100">
                    <div class="flex items-start gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-600 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 sm:h-5 sm:w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-800">Hapus Data Pelanggan?</h3>
                            <p class="mt-2 text-xs text-slate-500 leading-relaxed">
                                Apakah Anda yakin ingin menghapus pelanggan <strong class="font-semibold text-slate-800">{{ customerToDelete?.name }}</strong>?
                                Data yang sudah dihapus tidak dapat dikembalikan.
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-3">
                        <button
                            type="button"
                            @click="closeDeleteModal"
                            class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-medium text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            @click="submitDelete"
                            :disabled="deleteForm.processing"
                            class="inline-flex items-center gap-2 rounded-xl bg-rose-600 px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-rose-700 focus:outline-none disabled:opacity-50"
                        >
                            <svg v-if="deleteForm.processing" class="h-3.5 w-3.5 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>{{ deleteForm.processing ? 'Menghapus...' : 'Hapus Pelanggan' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>
