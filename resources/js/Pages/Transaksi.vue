<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    areaSummaries: {
        type: Array,
        default: () => [],
    },
    areas: {
        type: Array,
        default: () => [],
    },
    customers: {
        type: Array,
        default: () => [],
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
    incomeCategories: {
        type: Array,
        default: () => [],
    },
    materials: {
        type: Array,
        default: () => [],
    },
    companyExpenseTypes: {
        type: Array,
        default: () => [],
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

// Chart.js removed from Transaksi.vue

// Search and Filter State
const searchQuery = ref('');
const filterType = ref('all');
const filterStartDate = ref('');
const filterEndDate = ref('');

const filteredTransactions = computed(() => {
    let result = props.transactions || [];
    
    // Filter by type
    if (filterType.value !== 'all') {
        const isInc = filterType.value === 'income';
        result = result.filter(t => isIncome(t.type) === isInc);
    }
    
    // Filter by date range
    if (filterStartDate.value) {
        result = result.filter(t => t.date && String(t.date).substring(0, 10) >= filterStartDate.value);
    }
    if (filterEndDate.value) {
        result = result.filter(t => t.date && String(t.date).substring(0, 10) <= filterEndDate.value);
    }
    
    // Filter by search query (description, area, payment method)
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        result = result.filter(t => {
            return (t.description && t.description.toLowerCase().includes(q)) ||
                   (t.area && String(t.area).toLowerCase().includes(q)) ||
                   (t.payment_method && String(t.payment_method).toLowerCase().includes(q));
        });
    }
    
    return result;
});

// Pagination State
const currentPage = ref(1);
const itemsPerPage = ref(20);

const totalPages = computed(() => {
    return Math.ceil(filteredTransactions.value.length / itemsPerPage.value) || 1;
});

const paginatedTransactions = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    const end = start + itemsPerPage.value;
    return filteredTransactions.value.slice(start, end);
});

// Reset page when filters change
watch([searchQuery, filterType, filterStartDate, filterEndDate], () => {
    currentPage.value = 1;
});

// Add Transaction Modal & Inertia Form
const showModal = ref(false);
const isEdit = ref(false);
const editId = ref(null);

const customerSearchText = ref('');
const showCustomerDropdown = ref(false);

const form = useForm({
    date: new Date().toISOString().split('T')[0],
    type: 'income',
    amount: '',
    description: '',
    area: '',
    payment_method: '',
    paid_at: '',
    expense_category_id: '',
    income_category_id: '',
    company_expense_type_id: '',
    material_id: '',
    customer_id: '',
});

// Detect if selected expense category is "Perusahaan"
const isPerusahaanCategory = computed(() => {
    if (form.type !== 'expense' || !form.expense_category_id) return false;
    const selectedCat = props.expenseCategories.find(c => c.id == form.expense_category_id);
    return selectedCat && selectedCat.name && selectedCat.name.toLowerCase().includes('perusahaan');
});

// Detect if selected company expense type is "Material"
const isMaterialSubCategory = computed(() => {
    if (!isPerusahaanCategory.value || !form.company_expense_type_id) return false;
    const selectedType = props.companyExpenseTypes.find(t => t.id == form.company_expense_type_id);
    return selectedType && selectedType.name && selectedType.name.toLowerCase() === 'material';
});

// Detect if selected income category is "Pemasangan Baru"
const isPemasanganBaru = computed(() => {
    if (form.type !== 'income' || !form.income_category_id) return false;
    const selectedCat = props.incomeCategories.find(c => c.id == form.income_category_id);
    return selectedCat && selectedCat.name && selectedCat.name.toLowerCase().includes('pemasangan baru');
});

const newCustomers = computed(() => {
    const now = new Date();
    const currentMonth = now.getMonth();
    const currentYear = now.getFullYear();
    return props.customers.filter(c => {
        if (!c.created_at) return false;
        const d = new Date(c.created_at);
        return d.getMonth() === currentMonth && d.getFullYear() === currentYear;
    });
});

const filteredCustomers = computed(() => {
    let list = newCustomers.value;
    if (customerSearchText.value) {
        const search = customerSearchText.value.toLowerCase();
        list = list.filter(c => c.name.toLowerCase().includes(search) || (c.area && c.area.toLowerCase().includes(search)));
    }
    return list;
});

const selectCustomer = (c) => {
    form.customer_id = c.id;
    customerSearchText.value = c.name;
    showCustomerDropdown.value = false;
};

const onCustomerInput = () => {
    form.customer_id = '';
};

watch(() => form.customer_id, (newId) => {
    if (newId) {
        const c = props.customers.find(x => x.id === newId);
        if (c) customerSearchText.value = c.name;
    } else {
        customerSearchText.value = '';
    }
}, { immediate: true });

// Reset child dropdowns when category changes
watch(() => form.expense_category_id, () => {
    if (!isPerusahaanCategory.value) {
        form.company_expense_type_id = '';
        form.material_id = '';
    }
});

// Reset material_id when company expense type changes
watch(() => form.company_expense_type_id, () => {
    if (!isMaterialSubCategory.value) {
        form.material_id = '';
    }
});

// Reset customer_id when income category changes
watch(() => form.income_category_id, () => {
    if (!isPemasanganBaru.value) {
        form.customer_id = '';
    }
});

const changeType = (newType) => {
    if (form.type !== newType) {
        form.type = newType;
        form.expense_category_id = '';
        form.income_category_id = '';
        form.company_expense_type_id = '';
        form.material_id = '';
        form.customer_id = '';
        customerSearchText.value = '';
    }
};

const openModal = () => {
    isEdit.value = false;
    editId.value = null;
    form.reset();
    form.clearErrors();
    form.date = new Date().toISOString().split('T')[0];
    form.type = 'income';
    form.paid_at = new Date().toISOString().split('T')[0];
    if (props.paymentMethods && props.paymentMethods.length > 0) {
        form.payment_method = props.paymentMethods[0].name || props.paymentMethods[0].id;
    }
    showModal.value = true;
};

const editTransaction = (item) => {
    try {
        isEdit.value = true;
        editId.value = item.id;
        form.reset();
        form.clearErrors();
        
        // Format date string from DB safely
        form.date = item.date ? String(item.date).split(' ')[0] : new Date().toISOString().split('T')[0];
        
        form.type = item.type || 'income';
        form.amount = item.amount || '';
        form.description = item.description || '';
        form.area = item.area || '';
        form.payment_method = item.payment_method || '';
        form.paid_at = item.paid_at ? String(item.paid_at).split(' ')[0] : '';
        form.expense_category_id = item.expense_category_id || '';
        form.income_category_id = item.income_category_id || '';
        form.company_expense_type_id = item.company_expense_type_id || '';
        form.material_id = item.material_id || '';
        form.customer_id = item.customer_id || '';
        
        showModal.value = true;
    } catch (error) {
        alert("Error saat membuka edit: " + error.message);
        console.error(error);
    }
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
    form.clearErrors();
    isEdit.value = false;
    editId.value = null;
};

const submitForm = () => {
    if (isEdit.value) {
        const updateUrl = typeof route === 'function' ? route('transactions.update', editId.value) : `/transactions/${editId.value}`;
        form.put(updateUrl, {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
            },
        });
    } else {
        const submitUrl = typeof route === 'function' ? route('transactions.store') : '/transactions';
        form.post(submitUrl, {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
            },
        });
    }
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
    <Head title="Transaksi Keuangan" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
                        Menu Transaksi
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        {{ formattedTodayDate }}
                    </p>
                </div>
            </div>
        </template>

        <div class="py-8 bg-slate-50/50 min-h-screen">
            <div class="max-w-full mx-auto space-y-8">
<!-- Removed Summary Cards -->

                <!-- 2. Area Summaries Section -->
                <div class="space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 mb-2">
                        <div class="flex items-center gap-3">
                            <div class="p-2.5 bg-indigo-50 text-indigo-600 rounded-xl shrink-0">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-slate-800 tracking-tight">Ringkasan Area</h3>
                                <p class="text-xs text-slate-500 mt-0.5">Distribusi keuangan berdasarkan wilayah operasional</p>
                            </div>
                        </div>
                        <div class="flex flex-col items-end gap-2 w-full sm:w-auto">
                            <button
                                @click="openModal"
                                type="button"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-[#4F46E5] hover:bg-indigo-700 active:bg-indigo-800 text-white text-xs sm:text-sm font-semibold rounded-xl shadow-sm hover:shadow-md transition-all duration-200 transform hover:-translate-y-0.5"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                                </svg>
                                <span>Tambah Transaksi</span>
                            </button>
                            <div class="hidden sm:inline-flex items-center gap-2 px-3 py-1.5 bg-white border border-slate-100 rounded-lg text-xs font-medium text-slate-500 shadow-sm">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                {{ formattedTodayDate }}
                            </div>
                        </div>
                    </div>

                    <div v-if="areaSummaries && areaSummaries.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                        <div
                            v-for="(area, index) in areaSummaries"
                            :key="index"
                            class="relative overflow-hidden bg-white rounded-2xl shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)] border border-slate-100 p-5 transition-all duration-300 hover:shadow-[0_8px_20px_-6px_rgba(6,81,237,0.15)] hover:-translate-y-1 group min-w-0"
                        >
                            <div class="flex items-center justify-between pb-3 border-b border-slate-50 mb-4">
                                <h4 class="font-bold text-slate-800 text-xs sm:text-sm flex items-center gap-3 truncate group-hover:text-indigo-600 transition-colors" :title="area.area_name || area.area || area.name">
                                    <div class="p-2 bg-indigo-50/80 text-indigo-500 rounded-full shrink-0">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <span class="truncate text-base">{{ area.area_name || area.area || area.name || 'Area Tanpa Nama' }}</span>
                                </h4>
                                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                            </div>
                            <div class="space-y-3 text-xs sm:text-sm">
                                <div class="flex items-center justify-between text-slate-500">
                                    <span class="flex items-center gap-2">
                                        <div class="p-1 rounded-full bg-emerald-50 text-emerald-500">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18" /></svg>
                                        </div>
                                        Pemasukan
                                    </span>
                                    <span class="font-bold text-emerald-600">{{ formatRupiah(area.total_income) }}</span>
                                </div>
                                <div class="flex items-center justify-between text-slate-500">
                                    <span class="flex items-center gap-2">
                                        <div class="p-1 rounded-full bg-rose-50 text-rose-500">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3" /></svg>
                                        </div>
                                        Pengeluaran
                                    </span>
                                    <span class="font-bold text-rose-600">{{ formatRupiah(area.total_expense) }}</span>
                                </div>
                                <div class="pt-4 mt-2">
                                    <div :class="(Number(area.total_income || 0) - Number(area.total_expense || 0)) >= 0 ? 'bg-emerald-50/80 text-emerald-700' : 'bg-rose-50/80 text-rose-700'" class="flex items-center justify-between px-3 py-2.5 rounded-xl font-bold">
                                        <span class="flex items-center gap-2 text-xs sm:text-sm">
                                            <svg class="w-5 h-5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                                            Saldo Net
                                        </span>
                                        <span class="text-[15px]">
                                            <span v-if="(Number(area.total_income || 0) - Number(area.total_expense || 0)) < 0">- </span>{{ formatRupiah(Math.abs(Number(area.total_income || 0) - Number(area.total_expense || 0))) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="bg-white rounded-xl border border-slate-100 p-6 text-center text-xs sm:text-sm text-slate-400">
                        Belum ada data ringkasan area yang tersedia.
                    </div>
                </div>

<!-- Removed Chart Section -->

                <!-- 4. Transactions Table Section -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden min-w-0">
                    <div class="p-6 border-b border-slate-100 flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-indigo-50 text-indigo-500 rounded-lg shrink-0">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <h3 class="text-xl font-bold text-slate-800 tracking-tight">Daftar Transaksi</h3>
                                <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-indigo-100 text-indigo-600">
                                    {{ filteredTransactions.length }} Data
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 mt-1.5 xl:ml-11">Riwayat lengkap catatan arus kas masuk dan keluar operasional</p>
                        </div>
                        <div class="flex flex-col md:flex-row gap-3 w-full xl:w-auto items-stretch md:items-center">
                            <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                                <div class="relative w-full sm:w-auto">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                    </div>
                                    <input
                                        v-model="filterStartDate"
                                        type="date"
                                        title="Dari Tanggal"
                                        class="pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 rounded-xl text-xs sm:text-sm transition-all duration-200 text-slate-700 w-full sm:w-[145px]"
                                    >
                                </div>
                                <span class="text-slate-400 text-xs font-medium text-center hidden sm:block">s/d</span>
                                <div class="relative w-full sm:w-auto">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                    </div>
                                    <input
                                        v-model="filterEndDate"
                                        type="date"
                                        title="Sampai Tanggal"
                                        class="pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 rounded-xl text-xs sm:text-sm transition-all duration-200 text-slate-700 w-full sm:w-[145px]"
                                    >
                                </div>
                            </div>
                            <!-- Search -->
                            <div class="relative w-full md:w-auto">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Cari transaksi..."
                                    class="pl-9 pr-4 py-2 w-full md:w-48 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 rounded-xl text-xs sm:text-sm transition-all duration-200"
                                >
                            </div>
                            <!-- Filter Type -->
                            <div class="flex gap-2">
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                                    </div>
                                    <select
                                        v-model="filterType"
                                        class="pl-9 pr-10 py-2 w-full md:w-auto bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 rounded-xl text-xs sm:text-sm font-medium text-slate-700 appearance-none transition-all duration-200 cursor-pointer"
                                    >
                                        <option value="all">Semua Tipe</option>
                                        <option value="income">Pemasukan</option>
                                        <option value="expense">Pengeluaran</option>
                                    </select>
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <div class="overflow-x-auto w-full pb-4">
<table class="w-full text-left text-xs sm:text-sm border-collapse">
                            <thead class="bg-slate-50/80 text-[11px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100">
                                <tr>
                                    <th scope="col" class="px-3 py-3 sm:px-6 sm:py-4 w-12 text-center whitespace-nowrap">#</th>
                                    <th scope="col" class="px-3 py-3 sm:px-6 sm:py-4 whitespace-nowrap">Tanggal <span class="ml-1 inline-block text-slate-300">↕</span></th>
                                    <th scope="col" class="px-3 py-3 sm:px-6 sm:py-4 whitespace-nowrap">Keterangan</th>
                                    <th scope="col" class="px-3 py-3 sm:px-6 sm:py-4 whitespace-nowrap">Area</th>
                                    <th scope="col" class="px-3 py-3 sm:px-6 sm:py-4 text-center whitespace-nowrap">Metode Bayar</th>
                                    <th scope="col" class="px-3 py-3 sm:px-6 sm:py-4 text-center whitespace-nowrap">Tanggal Bayar</th>
                                    <th scope="col" class="px-3 py-3 sm:px-6 sm:py-4 text-center whitespace-nowrap">Tipe</th>
                                    <th scope="col" class="px-3 py-3 sm:px-6 sm:py-4 text-right whitespace-nowrap">Jumlah</th>
                                    <th scope="col" class="px-3 py-3 sm:px-6 sm:py-4 text-center whitespace-nowrap">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                <tr
                                    v-for="(item, index) in paginatedTransactions"
                                    :key="item.id"
                                    class="hover:bg-slate-50/75 transition-colors group"
                                >
                                    <td class="px-3 py-3 sm:px-6 sm:py-4 text-center text-xs sm:text-sm font-medium text-slate-900">
                                        {{ (currentPage - 1) * itemsPerPage + index + 1 }}
                                    </td>
                                    <td class="px-3 py-3 sm:px-6 sm:py-4 whitespace-nowrap">
                                        <div class="text-xs sm:text-sm font-medium text-slate-800">{{ formatDate(item.date) }}</div>
                                        <div class="text-xs text-slate-400 mt-0.5">{{ new Date(item.created_at || item.date).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) }}</div>
                                    </td>
                                    <td class="px-3 py-3 sm:px-6 sm:py-4 text-xs sm:text-sm font-medium text-slate-700 max-w-xs truncate" :title="item.description">
                                        {{ item.description || '-' }}
                                        <div v-if="item.type === 'income' && item.income_category_id" class="mt-1 text-xs text-slate-500 truncate max-w-xs">
                                            Kategori: {{ (incomeCategories.find(c => c.id === item.income_category_id) || {}).name || '-' }}
                                            <span v-if="item.customer">
                                                - {{ item.customer.name }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-3 py-3 sm:px-6 sm:py-4 whitespace-nowrap text-xs sm:text-sm text-slate-600">
                                        <span class="inline-flex items-center gap-1.5">
                                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            {{ item.area || '-' }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-3 sm:px-6 sm:py-4 whitespace-nowrap text-center">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-[11px] font-bold tracking-wide uppercase bg-blue-50 text-blue-600">
                                            {{ item.payment_method || '-' }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-3 sm:px-6 sm:py-4 whitespace-nowrap text-center text-xs sm:text-sm text-slate-600">
                                        {{ item.paid_at ? formatDate(item.paid_at) : '-' }}
                                    </td>
                                    <td class="px-3 py-3 sm:px-6 sm:py-4 whitespace-nowrap text-center">
                                        <span
                                            v-if="isIncome(item.type)"
                                            class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-md text-[11px] font-bold tracking-wide uppercase bg-emerald-50/80 text-emerald-600"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                            </svg>
                                            Pemasukan
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-md text-[11px] font-bold tracking-wide uppercase bg-rose-50/80 text-rose-600"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                            </svg>
                                            Pengeluaran
                                        </span>
                                    </td>
                                    <td class="px-3 py-3 sm:px-6 sm:py-4 whitespace-nowrap text-right font-bold text-xs sm:text-sm">
                                        <span :class="isIncome(item.type) ? 'text-emerald-600' : 'text-rose-600'">
                                            {{ isIncome(item.type) ? '+ ' : '- ' }}{{ formatRupiah(item.amount) }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-3 sm:px-6 sm:py-4 whitespace-nowrap text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <button
                                                @click="editTransaction(item)"
                                                type="button"
                                                title="Edit Transaksi"
                                                class="p-1.5 text-blue-500 hover:text-white hover:bg-blue-500 bg-blue-50 rounded-lg transition-colors border border-blue-100"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                            </button>
                                            <button
                                                @click="deleteTransaction(item.id)"
                                                type="button"
                                                title="Hapus Transaksi"
                                                class="p-1.5 text-rose-500 hover:text-white hover:bg-rose-500 bg-rose-50 rounded-lg transition-colors border border-rose-100"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="filteredTransactions.length === 0">
                                    <td colspan="9" class="px-6 py-12 text-center text-slate-500">
                                        <div class="flex flex-col items-center justify-center space-y-3">
                                            <svg class="w-12 h-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                            </svg>
                                            <p class="text-xs sm:text-sm font-medium">Tidak ada transaksi yang ditemukan.</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
</div>
                        <div class="p-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between text-xs sm:text-sm gap-4">
                            <div class="text-slate-500">
                                Menampilkan {{ paginatedTransactions.length > 0 ? (currentPage - 1) * itemsPerPage + 1 : 0 }} - 
                                {{ Math.min(currentPage * itemsPerPage, filteredTransactions.length) }} dari {{ filteredTransactions.length }} data
                            </div>
                            <div class="flex items-center gap-1">
                                <button 
                                    @click="currentPage > 1 ? currentPage-- : null"
                                    :disabled="currentPage === 1"
                                    class="px-3 py-1.5 border border-slate-200 rounded-lg text-slate-400 bg-slate-50 hover:bg-slate-100 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    &lt;
                                </button>
                                <div class="flex items-center gap-1 mx-2 text-slate-600 font-medium">
                                    <span>Halaman {{ currentPage }} dari {{ totalPages }}</span>
                                </div>
                                <button 
                                    @click="currentPage < totalPages ? currentPage++ : null"
                                    :disabled="currentPage === totalPages"
                                    class="px-3 py-1.5 border border-slate-200 rounded-lg text-slate-400 bg-slate-50 hover:bg-slate-100 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    &gt;
                                </button>
                            </div>
                        </div>
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
                                    <h3 class="text-xl font-bold text-slate-800" id="modal-title">
                                        {{ isEdit ? 'Edit Transaksi' : 'Tambah Transaksi Baru' }}
                                    </h3>
                                    <p class="text-xs sm:text-sm text-slate-500 mt-1">
                                        {{ isEdit ? 'Perbarui detail transaksi yang sudah ada.' : 'Catat data pemasukan atau pengeluaran baru ke dalam sistem.' }}
                                    </p>
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
                                            @click="changeType('income')"
                                            :class="form.type === 'income' ? 'bg-emerald-50 border-emerald-500 text-emerald-700 ring-2 ring-emerald-500/20 font-semibold' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100'"
                                            class="flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl border text-xs sm:text-sm transition-all"
                                        >
                                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                            </svg>
                                            Pemasukan
                                        </button>
                                        <button
                                            type="button"
                                            @click="changeType('expense')"
                                            :class="form.type === 'expense' ? 'bg-rose-50 border-rose-500 text-rose-700 ring-2 ring-rose-500/20 font-semibold' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100'"
                                            class="flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl border text-xs sm:text-sm transition-all"
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
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div>
                                        <label for="date" class="block text-xs font-medium text-slate-700 mb-1">Tanggal Transaksi</label>
                                        <input
                                            id="date"
                                            v-model="form.date"
                                            type="date"
                                            required
                                            class="w-full rounded-xl border-slate-200 text-xs sm:text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        />
                                        <p v-if="form.errors.date" class="mt-1 text-xs text-rose-600">{{ form.errors.date }}</p>
                                    </div>

                                    <div>
                                        <label for="paid_at" class="block text-xs font-medium text-slate-700 mb-1">Tanggal Bayar</label>
                                        <input
                                            id="paid_at"
                                            v-model="form.paid_at"
                                            type="date"
                                            class="w-full rounded-xl border-slate-200 text-xs sm:text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        />
                                        <p v-if="form.errors.paid_at" class="mt-1 text-xs text-rose-600">{{ form.errors.paid_at }}</p>
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
                                            class="w-full rounded-xl border-slate-200 text-xs sm:text-sm focus:border-indigo-500 focus:ring-indigo-500"
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
                                        class="w-full rounded-xl border-slate-200 text-xs sm:text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    />
                                    <p v-if="form.errors.description" class="mt-1 text-xs text-rose-600">{{ form.errors.description }}</p>
                                </div>

                                <!-- Kategori Pengeluaran -->
                                <div v-if="form.type === 'expense'">
                                    <label for="expense_category_id" class="block text-xs font-medium text-slate-700 mb-1">Kategori Pengeluaran</label>
                                    <select
                                        id="expense_category_id"
                                        v-model="form.expense_category_id"
                                        class="w-full rounded-xl border-slate-200 text-xs sm:text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        <option value="">-- Pilih Kategori --</option>
                                        <option v-for="cat in expenseCategories" :key="cat.id" :value="cat.id">
                                            {{ cat.name }}
                                        </option>
                                    </select>
                                    <p v-if="form.errors.expense_category_id" class="mt-1 text-xs text-rose-600">{{ form.errors.expense_category_id }}</p>
                                </div>

                                <!-- Kategori Pemasukan -->
                                <div v-if="form.type === 'income'">
                                    <label for="income_category_id" class="block text-xs font-medium text-slate-700 mb-1">Kategori Pemasukan</label>
                                    <select
                                        id="income_category_id"
                                        v-model="form.income_category_id"
                                        class="w-full rounded-xl border-slate-200 text-xs sm:text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        <option value="">-- Pilih Kategori --</option>
                                        <option v-for="c in incomeCategories" :key="c.id" :value="c.id">{{ c.name }}</option>
                                    </select>
                                    <p v-if="form.errors.income_category_id" class="mt-1 text-xs text-rose-600">{{ form.errors.income_category_id }}</p>
                                </div>

                                <!-- Nama Pelanggan (muncul jika kategori = Pemasangan Baru) -->
                                <div v-if="form.type === 'income' && isPemasanganBaru" class="mb-4">
                                    <label class="block text-xs font-medium text-slate-700 mb-1">Nama Pelanggan Baru</label>
                                    <div class="relative">
                                        <input 
                                            type="text" 
                                            v-model="customerSearchText" 
                                            @input="onCustomerInput"
                                            @focus="showCustomerDropdown = true"
                                            @blur="setTimeout(() => showCustomerDropdown = false, 200)"
                                            placeholder="Cari nama pelanggan..." 
                                            class="w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-xs sm:text-sm py-2.5 px-3 bg-white"
                                        >
                                        <div v-if="showCustomerDropdown" class="absolute z-10 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-lg max-h-60 overflow-y-auto">
                                            <div v-if="filteredCustomers.length === 0" class="px-3 py-2 text-xs sm:text-sm text-slate-500">Tidak ada pelanggan baru (bulan ini) ditemukan.</div>
                                            <div 
                                                v-for="c in filteredCustomers" 
                                                :key="c.id" 
                                                @mousedown.prevent="selectCustomer(c)"
                                                class="px-3 py-2 text-xs sm:text-sm cursor-pointer hover:bg-indigo-50 text-slate-700"
                                            >
                                                {{ c.name }} <span class="text-xs text-slate-400">({{ c.area || 'Tanpa Area' }})</span>
                                            </div>
                                        </div>
                                    </div>
                                    <p v-if="form.errors.customer_id" class="mt-1 text-xs text-rose-600">{{ form.errors.customer_id }}</p>
                                </div>

                                <!-- Jenis Pengeluaran Perusahaan (muncul jika kategori = Perusahaan) -->
                                <div v-if="isPerusahaanCategory">
                                    <label for="company_expense_type_id" class="block text-xs font-medium text-slate-700 mb-1">Jenis Pengeluaran Perusahaan</label>
                                    <select
                                        id="company_expense_type_id"
                                        v-model="form.company_expense_type_id"
                                        class="w-full rounded-xl border-slate-200 text-xs sm:text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        <option value="">-- Pilih Jenis --</option>
                                        <option v-for="type in companyExpenseTypes" :key="type.id" :value="type.id">
                                            {{ type.name }}
                                        </option>
                                    </select>
                                    <p v-if="companyExpenseTypes.length === 0" class="mt-1 text-xs text-amber-600">Belum ada data jenis pengeluaran. Tambahkan di Master Data.</p>
                                    <p v-if="form.errors.company_expense_type_id" class="mt-1 text-xs text-rose-600">{{ form.errors.company_expense_type_id }}</p>
                                </div>

                                <!-- Material Dropdown (muncul jika jenis pengeluaran = Material) -->
                                <div v-if="isMaterialSubCategory">
                                    <label for="material_id" class="block text-xs font-medium text-slate-700 mb-1">Nama Material</label>
                                    <select
                                        id="material_id"
                                        v-model="form.material_id"
                                        class="w-full rounded-xl border-slate-200 text-xs sm:text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        <option value="">-- Pilih Material --</option>
                                        <option v-for="mat in materials" :key="mat.id" :value="mat.id">
                                            {{ mat.name }}
                                        </option>
                                    </select>
                                    <p v-if="materials.length === 0" class="mt-1 text-xs text-amber-600">Belum ada data material. Tambahkan di Master Data.</p>
                                    <p v-if="form.errors.material_id" class="mt-1 text-xs text-rose-600">{{ form.errors.material_id }}</p>
                                </div>

                                <!-- Area & Payment Method -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label for="area" class="block text-xs font-medium text-slate-700 mb-1">Area / Cabang</label>
                                        <select
                                            id="area"
                                            v-model="form.area"
                                            class="w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-xs sm:text-sm py-2.5 px-3 bg-white"
                                        >
                                            <option value="">-- Pilih Area --</option>
                                            <option v-for="a in areas" :key="a" :value="a">{{ a }}</option>
                                        </select>
                                        <p v-if="form.errors.area" class="mt-1 text-xs text-rose-600">{{ form.errors.area }}</p>
                                    </div>

                                    <div>
                                        <label for="payment_method" class="block text-xs font-medium text-slate-700 mb-1">Metode Pembayaran</label>
                                        <select
                                            id="payment_method"
                                            v-model="form.payment_method"
                                            required
                                            class="w-full rounded-xl border-slate-200 text-xs sm:text-sm focus:border-indigo-500 focus:ring-indigo-500"
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
                                        class="px-4 py-2 text-xs sm:text-sm font-medium text-slate-600 hover:bg-slate-100 rounded-xl transition-colors"
                                    >
                                        Batal
                                    </button>
                                    <button
                                        type="submit"
                                        :disabled="form.processing"
                                        class="inline-flex justify-center w-full sm:w-auto px-6 py-2.5 bg-indigo-600 text-white rounded-xl text-xs sm:text-sm font-semibold hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        {{ form.processing ? 'Menyimpan...' : (isEdit ? 'Simpan Perubahan' : 'Simpan Transaksi') }}
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