<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    affiliates: Array,
    sales: Array,
    areas: Array,
    is_sales: Boolean,
    commissions: Object,
});

const formatCurrency = (value) => {
    if (!value) return '0';
    return Number(value).toLocaleString('id-ID');
};

const activeTab = ref('sales'); // default to sales instead of affiliates

// --- Affiliate Logic ---
const isModalOpen = ref(false);
const isEdit = ref(false);
const currentAffiliate = ref(null);
const isViewTreeOpen = ref(false);
const treeType = ref('affiliate');

const form = useForm({
    name: '',
    email: '',
    phone: '',
    parent_id: '',
    status: 'Aktif',
    commission_rate: 0,
    bank_account: '',
    join_date: '',
});

const openModal = () => {
    isEdit.value = false;
    currentAffiliate.value = null;
    form.reset();
    isModalOpen.value = true;
};

const openEditModal = (affiliate) => {
    isEdit.value = true;
    currentAffiliate.value = affiliate;
    form.name = affiliate.name;
    form.email = affiliate.email;
    form.phone = affiliate.phone;
    form.parent_id = affiliate.parent_id;
    form.status = affiliate.status;
    form.commission_rate = affiliate.commission_rate;
    form.bank_account = affiliate.bank_account;
    form.join_date = affiliate.join_date;
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
    form.clearErrors();
};

const saveAffiliate = () => {
    if (isEdit.value) {
        form.put(route('affiliates.update', currentAffiliate.value.id), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('affiliates.store'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
};

const deleteAffiliate = (id) => {
    if (confirm('Apakah Anda yakin ingin menghapus afiliasi ini? Downline mereka akan menjadi tanpa upline.')) {
        router.delete(route('affiliates.destroy', id), {
            preserveScroll: true,
        });
    }
};

const viewTree = (item, type = 'affiliate') => {
    currentAffiliate.value = item;
    treeType.value = type;
    isViewTreeOpen.value = true;
};

const closeTreeModal = () => {
    isViewTreeOpen.value = false;
};

const getDownlines = (parentId) => {
    return props.affiliates.filter(a => a.parent_id === parentId);
};

const getSalesDownlines = (parentId) => {
    return props.sales.filter(s => s.parent_id === parentId);
};

const getActiveDownlines = (parentId) => {
    return treeType.value === 'affiliate' ? getDownlines(parentId) : getSalesDownlines(parentId);
};

const getLevel = (affiliate) => {
    if (!affiliate.parent_id) return 'Upline';
    
    let level = 0;
    let current = affiliate;
    while (current.parent_id) {
        level++;
        const parent = props.affiliates.find(a => a.id === current.parent_id);
        if (!parent) break;
        current = parent;
    }
    return `Downline ${level}`;
};

const getSalesLevel = (sale) => {
    if (sale.is_current_user) return 'Upline';
    
    const level = getSalesNumLevel(sale);
    if (level === 0) return 'Upline';
    
    return `Downline ${level}`;
};

const getAffiliateNumLevel = (affiliate) => {
    if (!affiliate.parent_id) return 0;
    let level = 0;
    let current = affiliate;
    while (current.parent_id) {
        level++;
        const parent = props.affiliates.find(a => a.id === current.parent_id);
        if (!parent) break;
        current = parent;
    }
    return level;
};

const getSalesNumLevel = (sale) => {
    let level = 0;
    let current = sale;
    while (current.parent_id) {
        const parent = props.sales.find(s => s.id === current.parent_id);
        if (!parent) break; // Relative root reached
        level++;
        current = parent;
    }
    return level;
};

// --- Sales Logic ---
const sortedSales = computed(() => {
    return [...props.sales].sort((a, b) => {
        const levelA = getSalesNumLevel(a);
        const levelB = getSalesNumLevel(b);
        if (levelA !== levelB) return levelA - levelB;
        return a.name.localeCompare(b.name);
    });
});

const isSalesModalOpen = ref(false);
const isSalesEdit = ref(false);
const currentSale = ref(null);

const salesForm = useForm({
    name: '',
    phone: '',
    email: '',
    area: '',
    status: 'Aktif',
    bank_account: '',
    join_date: '',
    parent_id: '',
});

// --- Sales Account Logic ---
const isAccountModalOpen = ref(false);
const currentSaleForAccount = ref(null);

const accountForm = useForm({
    password: '',
});

const openAccountModal = (sale) => {
    currentSaleForAccount.value = sale;
    accountForm.password = '';
    accountForm.clearErrors();
    isAccountModalOpen.value = true;
};

const closeAccountModal = () => {
    isAccountModalOpen.value = false;
    accountForm.reset();
};

const createAccount = () => {
    accountForm.post(route('sales.account.store', currentSaleForAccount.value.id), {
        preserveScroll: true,
        onSuccess: () => closeAccountModal(),
    });
};

const deleteAccount = (sale) => {
    if (confirm(`Apakah Anda yakin ingin menghapus akses login untuk sales "${sale.name}"? Data sales tetap akan tersimpan.`)) {
        router.delete(route('sales.account.destroy', sale.id), {
            preserveScroll: true,
        });
    }
};

const openSalesModal = () => {
    isSalesEdit.value = false;
    currentSale.value = null;
    salesForm.reset();
    isSalesModalOpen.value = true;
};

const openSalesEditModal = (sale) => {
    isSalesEdit.value = true;
    currentSale.value = sale;
    salesForm.name = sale.name;
    salesForm.phone = sale.phone;
    salesForm.email = sale.email;
    salesForm.area = sale.area;
    salesForm.status = sale.status;
    salesForm.bank_account = sale.bank_account;
    salesForm.join_date = sale.join_date;
    salesForm.parent_id = sale.parent_id;
    isSalesModalOpen.value = true;
};

const closeSalesModal = () => {
    isSalesModalOpen.value = false;
    salesForm.reset();
    salesForm.clearErrors();
};

const saveSales = () => {
    if (isSalesEdit.value) {
        salesForm.put(route('sales.update', currentSale.value.id), {
            preserveScroll: true,
            onSuccess: () => closeSalesModal(),
        });
    } else {
        salesForm.post(route('sales.store'), {
            preserveScroll: true,
            onSuccess: () => closeSalesModal(),
        });
    }
};

const deleteSales = (id) => {
    if (confirm('Apakah Anda yakin ingin menghapus Sales ini?')) {
        router.delete(route('sales.destroy', id), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Multi-Tier Affiliate & Sales" />
        <template #header>
            <h2 class="font-bold text-xl text-slate-800 leading-tight">Multi-Tier Affiliate & Sales</h2>
        </template>

        <div class="py-6">
            <div class="max-w-full mx-auto space-y-6">
                <!-- Notifications -->
                <div v-if="$page.props.flash?.success" class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl p-4 flex items-center shadow-sm">
                    <svg class="w-5 h-5 mr-3 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    {{ $page.props.flash.success }}
                </div>
                <div v-if="$page.props.flash?.error" class="bg-red-50 border border-red-200 text-red-800 rounded-2xl p-4 flex items-center shadow-sm">
                    <svg class="w-5 h-5 mr-3 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    {{ $page.props.flash.error }}
                </div>

                <!-- Tabs -->
                <div v-if="!is_sales" class="border-b border-slate-200">
                    <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                        <button 
                            @click="activeTab = 'sales'" 
                            :class="[
                                activeTab === 'sales' 
                                    ? 'border-indigo-500 text-indigo-600' 
                                    : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300',
                                'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-xs sm:text-sm transition-colors'
                           ]"
                        >
                            Daftar Sales (Multi-Tier)
                        </button>
                        <button 
                            @click="activeTab = 'sales_account'" 
                            :class="[
                                activeTab === 'sales_account' 
                                    ? 'border-indigo-500 text-indigo-600' 
                                    : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300',
                                'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-xs sm:text-sm transition-colors'
                           ]"
                        >
                            Akun Sales
                        </button>
                    </nav>
                </div>

                <!-- AFFILIATE TAB CONTENT -->
                <div v-if="activeTab === 'affiliates'" class="space-y-6">
                    <!-- Action Bar -->
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-bold text-slate-800">Daftar Afiliasi</h3>
                        <div class="flex items-center gap-3">
                            <Link :href="route('affiliates.diagram')" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 font-semibold rounded-xl text-sm transition-colors shadow-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>
                                Tampilan Diagram
                            </Link>
                            <PrimaryButton @click="openModal" class="flex items-center gap-2 rounded-xl py-2.5 shadow-md hover:shadow-lg transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                Tambah Afiliasi
                            </PrimaryButton>
                        </div>
                    </div>

                    <!-- Affiliates Table -->
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="overflow-x-auto w-full pb-4">
<table class="w-full text-left text-xs sm:text-sm whitespace-nowrap">
                                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold">
                                    <tr>
                                        <th class="px-3 py-3 sm:px-6 sm:py-4">Anggota</th>
                                        <th class="px-3 py-3 sm:px-6 sm:py-4">Kontak</th>
                                        <th class="px-3 py-3 sm:px-6 sm:py-4">Upline (Parent)</th>
                                        <th class="px-3 py-3 sm:px-6 sm:py-4">Rek. Bank</th>
                                        <th class="px-3 py-3 sm:px-6 sm:py-4">Tgl Gabung</th>
                                        <th class="px-3 py-3 sm:px-6 sm:py-4 text-center">Downline</th>
                                        <th class="px-3 py-3 sm:px-6 sm:py-4">Komisi</th>
                                        <th class="px-3 py-3 sm:px-6 sm:py-4 text-center">Status</th>
                                        <th class="px-3 py-3 sm:px-6 sm:py-4 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="affiliate in affiliates" :key="affiliate.id" class="hover:bg-slate-50/70 transition-colors">
                                        <td class="px-3 py-3 sm:px-6 sm:py-4">
                                            <div class="flex items-center gap-2 mb-1">
                                                <div class="font-bold text-slate-800">{{ affiliate.name }}</div>
                                                <span :class="[
                                                    'text-[10px] px-2 py-0.5 rounded-full font-bold uppercase tracking-wider',
                                                    !affiliate.parent_id ? 'bg-indigo-100 text-indigo-700' : 'bg-amber-100 text-amber-800'
                                               ]">
                                                    {{ getLevel(affiliate) }}
                                                </span>
                                            </div>
                                            <div class="text-xs text-slate-500">
                                                {{ affiliate.member_number || 'ID: ' + affiliate.id }}
                                            </div>
                                        </td>
                                        <td class="px-3 py-3 sm:px-6 sm:py-4">
                                            <div class="text-slate-700">{{ affiliate.phone || '-' }}</div>
                                            <div class="text-xs text-slate-500">{{ affiliate.email || '-' }}</div>
                                        </td>
                                        <td class="px-3 py-3 sm:px-6 sm:py-4">
                                            <span v-if="affiliate.parent" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-indigo-50 text-indigo-700 text-xs font-semibold border border-indigo-100">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" /></svg>
                                                {{ affiliate.parent.name }}
                                            </span>
                                            <span v-else class="text-slate-400 italic text-xs">Tanpa Upline</span>
                                        </td>
                                        <td class="px-3 py-3 sm:px-6 sm:py-4">
                                            <div class="text-slate-700 font-medium">{{ affiliate.bank_account || '-' }}</div>
                                        </td>
                                        <td class="px-3 py-3 sm:px-6 sm:py-4">
                                            <div class="text-slate-700">{{ affiliate.join_date || '-' }}</div>
                                        </td>
                                        <td class="px-3 py-3 sm:px-6 sm:py-4 text-center">
                                            <button @click="viewTree(affiliate, 'affiliate')" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100 hover:text-emerald-700 transition-colors tooltip" :title="'Lihat Hierarki (' + affiliate.children_count + ' langsung)'">
                                                <span class="font-bold">{{ affiliate.children_count || 0 }}</span>
                                            </button>
                                        </td>
                                        <td class="px-3 py-3 sm:px-6 sm:py-4">
                                            <span class="font-medium text-slate-700">{{ affiliate.commission_rate }}%</span>
                                        </td>
                                        <td class="px-3 py-3 sm:px-6 sm:py-4 text-center">
                                            <span :class="[
                                                'px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full',
                                                affiliate.status === 'Aktif' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'
                                           ]">
                                                {{ affiliate.status }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-3 sm:px-6 sm:py-4 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <button @click="openEditModal(affiliate)" class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 hover:bg-indigo-100 p-2 rounded-lg transition-colors" title="Edit">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                                </button>
                                                <button @click="deleteAffiliate(affiliate.id)" class="text-rose-600 hover:text-rose-900 bg-rose-50 hover:bg-rose-100 p-2 rounded-lg transition-colors" title="Hapus">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="affiliates.length === 0">
                                        <td colspan="9" class="px-6 py-12 text-center text-slate-500">
                                            Belum ada data afiliasi.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
</div>
                    </div>
                </div> <!-- END AFFILIATE TAB CONTENT -->

                <!-- SALES TAB CONTENT -->
                <div v-if="activeTab === 'sales'" class="space-y-6">
                    <!-- Action Bar -->
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-bold text-slate-800">{{ is_sales ? 'Jaringan Downline Saya' : 'Daftar Sales' }}</h3>
                        <div class="flex items-center gap-3">
                            <Link :href="route('affiliates.diagram')" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 font-semibold rounded-xl text-sm transition-colors shadow-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>
                                Tampilan Diagram
                            </Link>
                            <PrimaryButton @click="openSalesModal" class="flex items-center gap-2 rounded-xl py-2.5 shadow-md hover:shadow-lg transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                Tambah Sales
                            </PrimaryButton>
                        </div>
                    </div>

                    <!-- Sales Table -->
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="overflow-x-auto w-full pb-4">
<table class="w-full text-left text-xs sm:text-sm whitespace-nowrap">
                                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold">
                                    <tr>
                                        <th class="px-3 py-3 sm:px-6 sm:py-4">Anggota</th>
                                        <th class="px-3 py-3 sm:px-6 sm:py-4">Kontak</th>
                                        <th class="px-3 py-3 sm:px-6 sm:py-4">Upline (Parent)</th>
                                        <th class="px-3 py-3 sm:px-6 sm:py-4">Rek. Bank</th>
                                        <th class="px-3 py-3 sm:px-6 sm:py-4">Tgl Gabung</th>
                                        <th class="px-3 py-3 sm:px-6 sm:py-4 text-center">Downline</th>
                                        <th class="px-3 py-3 sm:px-6 sm:py-4">Area</th>
                                        <th class="px-3 py-3 sm:px-6 sm:py-4 text-center">Status</th>
                                        <th class="px-3 py-3 sm:px-6 sm:py-4 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="sale in sortedSales" :key="sale.id" class="hover:bg-slate-50/70 transition-colors">
                                        <td class="px-3 py-3 sm:px-6 sm:py-4">
                                            <div class="flex items-center gap-2 mb-1">
                                                <div class="font-bold text-slate-800">{{ sale.name }}</div>
                                                <span v-if="sale.is_current_user" class="text-[10px] px-2 py-0.5 rounded-full font-bold uppercase tracking-wider bg-emerald-100 text-emerald-800 ml-1">
                                                    UPLINE
                                                </span>
                                                <span v-else :class="[
                                                    'text-[10px] px-2 py-0.5 rounded-full font-bold uppercase tracking-wider',
                                                    !sale.parent_id ? 'bg-indigo-100 text-indigo-700' : 'bg-amber-100 text-amber-800'
                                               ]">
                                                    {{ getSalesLevel(sale) }}
                                                    <span v-if="getSalesLevel(sale) === 'Downline 1'" class="lowercase font-normal ml-1">(Rp {{ formatCurrency(commissions?.upline_1_monthly) }})</span>
                                                    <span v-if="getSalesLevel(sale) === 'Downline 2'" class="lowercase font-normal ml-1">(Rp {{ formatCurrency(commissions?.upline_2_monthly) }})</span>
                                                </span>
                                            </div>
                                            <div class="text-xs text-slate-500">
                                                {{ sale.member_number || 'ID: ' + sale.id }}
                                            </div>
                                        </td>
                                        <td class="px-3 py-3 sm:px-6 sm:py-4">
                                            <div class="text-slate-700">{{ sale.phone || '-' }}</div>
                                            <div class="text-xs text-slate-500">{{ sale.email || '-' }}</div>
                                        </td>
                                        <td class="px-3 py-3 sm:px-6 sm:py-4">
                                            <span v-if="sale.parent" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-indigo-50 text-indigo-700 text-xs font-semibold border border-indigo-100">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" /></svg>
                                                {{ sale.parent.name }}
                                            </span>
                                            <span v-else class="text-slate-400 italic text-xs">Tanpa Upline</span>
                                        </td>
                                        <td class="px-3 py-3 sm:px-6 sm:py-4">
                                            <div class="text-slate-700 font-medium">{{ sale.bank_account || '-' }}</div>
                                        </td>
                                        <td class="px-3 py-3 sm:px-6 sm:py-4">
                                            <div class="text-slate-700">{{ sale.join_date || '-' }}</div>
                                        </td>
                                        <td class="px-3 py-3 sm:px-6 sm:py-4 text-center">
                                            <button @click="viewTree(sale, 'sales')" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100 hover:text-emerald-700 transition-colors tooltip" :title="'Lihat Hierarki (' + sale.children_count + ' langsung)'">
                                                <span class="font-bold">{{ sale.children_count || 0 }}</span>
                                            </button>
                                        </td>
                                        <td class="px-3 py-3 sm:px-6 sm:py-4">
                                            <span class="text-slate-700">{{ sale.area || '-' }}</span>
                                        </td>
                                        <td class="px-3 py-3 sm:px-6 sm:py-4 text-center">
                                            <span :class="[
                                                'px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full',
                                                sale.status === 'Aktif' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'
                                           ]">
                                                {{ sale.status }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-3 sm:px-6 sm:py-4 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <button @click="openSalesEditModal(sale)" class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 hover:bg-indigo-100 p-2 rounded-lg transition-colors" title="Edit">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                                </button>
                                                <button @click="deleteSales(sale.id)" class="text-rose-600 hover:text-rose-900 bg-rose-50 hover:bg-rose-100 p-2 rounded-lg transition-colors" title="Hapus">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="sales.length === 0">
                                        <td colspan="9" class="px-6 py-12 text-center text-slate-500">
                                            Belum ada data sales.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
</div>
                    </div>
                </div> <!-- END SALES TAB CONTENT -->

                <!-- SALES ACCOUNT TAB CONTENT -->
                <div v-if="activeTab === 'sales_account'" class="space-y-6">
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="p-6 border-b border-slate-200">
                            <h3 class="text-lg font-bold text-slate-800">Manajemen Akun Login Sales</h3>
                            <p class="text-xs sm:text-sm text-slate-500 mt-1">Buat akun agar sales dapat login ke dalam aplikasi.</p>
                        </div>
                        <div class="overflow-x-auto w-full pb-4">
<table class="w-full text-left text-xs sm:text-sm whitespace-nowrap">
                                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold">
                                    <tr>
                                        <th class="px-3 py-3 sm:px-6 sm:py-4">Nama Sales</th>
                                        <th class="px-3 py-3 sm:px-6 sm:py-4">Email</th>
                                        <th class="px-3 py-3 sm:px-6 sm:py-4 text-center">Status Akun</th>
                                        <th class="px-3 py-3 sm:px-6 sm:py-4 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="sale in sales" :key="'acc-'+sale.id" class="hover:bg-slate-50/70 transition-colors">
                                        <td class="px-3 py-3 sm:px-6 sm:py-4">
                                            <div class="font-bold text-slate-800">{{ sale.name }}</div>
                                            <div class="text-xs text-slate-500">{{ sale.member_number }}</div>
                                        </td>
                                        <td class="px-3 py-3 sm:px-6 sm:py-4">
                                            <div class="text-slate-700">{{ sale.email || '-' }}</div>
                                        </td>
                                        <td class="px-3 py-3 sm:px-6 sm:py-4 text-center">
                                            <span v-if="sale.user_id" class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-emerald-100 text-emerald-800">
                                                Aktif (Bisa Login)
                                            </span>
                                            <span v-else class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-slate-100 text-slate-600">
                                                Belum Punya Akun
                                            </span>
                                        </td>
                                        <td class="px-3 py-3 sm:px-6 sm:py-4 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <button v-if="!sale.user_id" @click="openAccountModal(sale)" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 font-semibold rounded-lg text-xs transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                                    Buat Akun
                                                </button>
                                                <button v-else @click="deleteAccount(sale)" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-rose-50 text-rose-700 hover:bg-rose-100 font-semibold rounded-lg text-xs transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                    Hapus Akun
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="sales.length === 0">
                                        <td colspan="4" class="px-6 py-12 text-center text-slate-500">
                                            Belum ada data sales.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
</div>
                    </div>
                </div> <!-- END SALES ACCOUNT TAB CONTENT -->

                <!-- Add/Edit Affiliate Modal -->
                <Modal :show="isModalOpen" @close="closeModal" maxWidth="md">
                    <div class="p-6">
                        <h2 class="text-lg font-bold text-slate-800 mb-6 border-b border-slate-100 pb-4">
                            {{ isEdit ? 'Edit Afiliasi' : 'Tambah Afiliasi Baru' }}
                        </h2>
                        
                        <form @submit.prevent="saveAffiliate" class="space-y-5">
                            <div>
                                <InputLabel for="name" value="Nama Lengkap" />
                                <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full rounded-xl" required />
                                <InputError :message="form.errors.name" class="mt-2" />
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <InputLabel for="phone" value="No. WhatsApp" />
                                    <TextInput id="phone" v-model="form.phone" type="text" class="mt-1 block w-full rounded-xl" />
                                    <InputError :message="form.errors.phone" class="mt-2" />
                                </div>
                                <div>
                                    <InputLabel for="email" value="Email" />
                                    <TextInput id="email" v-model="form.email" type="email" class="mt-1 block w-full rounded-xl" />
                                    <InputError :message="form.errors.email" class="mt-2" />
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <InputLabel for="bank_account" value="Rekening Bank" />
                                    <TextInput id="bank_account" v-model="form.bank_account" type="text" class="mt-1 block w-full rounded-xl" />
                                    <InputError :message="form.errors.bank_account" class="mt-2" />
                                </div>
                                <div>
                                    <InputLabel for="join_date" value="Tanggal Gabung" />
                                    <TextInput id="join_date" v-model="form.join_date" type="date" class="mt-1 block w-full rounded-xl" />
                                    <InputError :message="form.errors.join_date" class="mt-2" />
                                </div>
                            </div>

                            <div>
                                <InputLabel for="parent_id" value="Upline / Parent (Opsional)" />
                                <select id="parent_id" v-model="form.parent_id" class="mt-1 block w-full border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm sm:text-xs sm:text-sm">
                                    <option value="">-- Tanpa Upline (Master) --</option>
                                    <option v-for="opt in affiliates.filter(a => a.id !== currentAffiliate?.id && getAffiliateNumLevel(a) < 2)" :key="opt.id" :value="opt.id">
                                        {{ opt.name }} (ID: {{ opt.id }})
                                    </option>
                                </select>
                                <InputError :message="form.errors.parent_id" class="mt-2" />
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <InputLabel for="commission_rate" value="Komisi (%)" />
                                    <TextInput id="commission_rate" v-model="form.commission_rate" type="number" step="0.01" min="0" class="mt-1 block w-full rounded-xl" />
                                    <InputError :message="form.errors.commission_rate" class="mt-2" />
                                </div>
                                <div>
                                    <InputLabel for="status" value="Status" />
                                    <select id="status" v-model="form.status" class="mt-1 block w-full border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm sm:text-xs sm:text-sm">
                                        <option value="Aktif">Aktif</option>
                                        <option value="Nonaktif">Nonaktif</option>
                                    </select>
                                    <InputError :message="form.errors.status" class="mt-2" />
                                </div>
                            </div>

                            <div class="mt-8 pt-5 border-t border-slate-100 flex justify-end gap-3">
                                <SecondaryButton @click="closeModal" class="rounded-xl px-5">Batal</SecondaryButton>
                                <PrimaryButton :disabled="form.processing" class="rounded-xl px-6 bg-indigo-600 hover:bg-indigo-700">
                                    {{ isEdit ? 'Simpan Perubahan' : 'Tambahkan' }}
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </Modal>

                <!-- Add/Edit Sales Modal -->
                <Modal :show="isSalesModalOpen" @close="closeSalesModal" maxWidth="md">
                    <div class="p-6">
                        <h2 class="text-lg font-bold text-slate-800 mb-6 border-b border-slate-100 pb-4">
                            {{ isSalesEdit ? 'Edit Sales' : 'Tambah Sales Baru' }}
                        </h2>
                        
                        <form @submit.prevent="saveSales" class="space-y-5">
                            <div>
                                <InputLabel for="sales_name" value="Nama Lengkap" />
                                <TextInput id="sales_name" v-model="salesForm.name" type="text" class="mt-1 block w-full rounded-xl" required />
                                <InputError :message="salesForm.errors.name" class="mt-2" />
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <InputLabel for="sales_phone" value="No. WhatsApp" />
                                    <TextInput id="sales_phone" v-model="salesForm.phone" type="text" class="mt-1 block w-full rounded-xl" />
                                    <InputError :message="salesForm.errors.phone" class="mt-2" />
                                </div>
                                <div>
                                    <InputLabel for="sales_email" value="Email" />
                                    <TextInput id="sales_email" v-model="salesForm.email" type="email" class="mt-1 block w-full rounded-xl" />
                                    <InputError :message="salesForm.errors.email" class="mt-2" />
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <InputLabel for="sales_area" value="Area / Wilayah" />
                                    <select id="sales_area" v-model="salesForm.area" class="mt-1 block w-full border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm sm:text-xs sm:text-sm">
                                        <option value="">-- Pilih Area --</option>
                                        <option v-for="area in areas" :key="area" :value="area">{{ area }}</option>
                                    </select>
                                    <InputError :message="salesForm.errors.area" class="mt-2" />
                                </div>
                                <div>
                                    <InputLabel for="sales_status" value="Status" />
                                    <select id="sales_status" v-model="salesForm.status" class="mt-1 block w-full border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm sm:text-xs sm:text-sm">
                                        <option value="Aktif">Aktif</option>
                                        <option value="Nonaktif">Nonaktif</option>
                                    </select>
                                    <InputError :message="salesForm.errors.status" class="mt-2" />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                <div>
                                    <InputLabel for="sales_parent" value="Upline (Parent)" />
                                    <select id="sales_parent" v-model="salesForm.parent_id" class="mt-1 block w-full border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm sm:text-xs sm:text-sm">
                                        <option value="">-- Tanpa Upline (Root) --</option>
                                        <option v-for="s in sales.filter(item => item.id !== currentSale?.id && getSalesNumLevel(item) < 2)" :key="s.id" :value="s.id">{{ s.name }} ({{ s.member_number || 'ID: '+s.id }})</option>
                                    </select>
                                    <InputError :message="salesForm.errors.parent_id" class="mt-2" />
                                </div>
                                <div>
                                    <InputLabel for="sales_bank_account" value="Nomor Rekening Bank" />
                                    <TextInput id="sales_bank_account" v-model="salesForm.bank_account" type="text" class="mt-1 block w-full rounded-xl" placeholder="Misal: BCA 1234567890 a.n. John Doe" />
                                    <InputError :message="salesForm.errors.bank_account" class="mt-2" />
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4 mt-4">
                                <div>
                                    <InputLabel for="sales_join_date" value="Tanggal Bergabung" />
                                    <TextInput id="sales_join_date" v-model="salesForm.join_date" type="date" class="mt-1 block w-full rounded-xl" />
                                    <InputError :message="salesForm.errors.join_date" class="mt-2" />
                                </div>
                                <div></div>
                            </div>

                            <div class="mt-8 pt-5 border-t border-slate-100 flex justify-end gap-3">
                                <SecondaryButton @click="closeSalesModal" class="rounded-xl px-5">Batal</SecondaryButton>
                                <PrimaryButton :disabled="salesForm.processing" class="rounded-xl px-6 bg-indigo-600 hover:bg-indigo-700">
                                    {{ isSalesEdit ? 'Simpan Perubahan' : 'Tambahkan' }}
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </Modal>

                <!-- View Tree Modal -->
                <Modal :show="isViewTreeOpen" @close="closeTreeModal" maxWidth="lg">
                    <div class="p-6">
                        <div class="flex justify-between items-start mb-6 border-b border-slate-100 pb-4">
                            <div>
                                <h2 class="text-lg font-bold text-slate-800">Hierarki Downline</h2>
                                <p class="text-xs sm:text-sm text-slate-500">Upline: <span class="font-bold text-indigo-600">{{ currentAffiliate?.name }}</span></p>
                            </div>
                            <button @click="closeTreeModal" class="text-slate-400 hover:text-slate-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                        
                        <!-- Simple Tree Visualization -->
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 min-h-[200px] max-h-[60vh] overflow-y-auto">
                            <div class="font-bold text-indigo-700 flex items-center gap-2 mb-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                {{ currentAffiliate?.name }}
                            </div>
                            
                            <div class="pl-4 border-l-2 border-indigo-200 ml-2">
                                <template v-for="level1 in getActiveDownlines(currentAffiliate?.id)" :key="level1.id">
                                    <div class="py-1">
                                        <div class="flex items-center gap-2">
                                            <div class="w-4 border-b-2 border-indigo-200"></div>
                                            <span class="font-semibold text-slate-700">{{ level1.name }}</span>
                                            <span v-if="treeType === 'affiliate'" class="text-xs px-1.5 py-0.5 rounded-full bg-slate-200 text-slate-600">{{ level1.commission_rate }}%</span>
                                            <span v-if="treeType === 'sales'" class="text-xs px-1.5 py-0.5 rounded-full bg-slate-200 text-slate-600">{{ level1.area || 'Tanpa Area' }}</span>
                                        </div>
                                        
                                        <div class="pl-6 border-l-2 border-slate-200 ml-2" v-if="getActiveDownlines(level1.id).length > 0">
                                            <template v-for="level2 in getActiveDownlines(level1.id)" :key="level2.id">
                                                <div class="py-1 flex items-center gap-2">
                                                    <div class="w-4 border-b-2 border-slate-200"></div>
                                                    <span class="text-xs sm:text-sm text-slate-600">{{ level2.name }}</span>
                                                    <span v-if="treeType === 'affiliate'" class="text-xs px-1.5 py-0.5 rounded-full bg-slate-200 text-slate-600">{{ level2.commission_rate }}%</span>
                                                    <span v-if="treeType === 'sales'" class="text-xs px-1.5 py-0.5 rounded-full bg-slate-200 text-slate-600">{{ level2.area || 'Tanpa Area' }}</span>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                                <div v-if="getActiveDownlines(currentAffiliate?.id).length === 0" class="py-2 text-xs sm:text-sm text-slate-500 italic flex items-center gap-2">
                                    <div class="w-4 border-b-2 border-indigo-200"></div>
                                    Belum ada downline.
                                </div>
                            </div>
                        </div>

                    </div>
                </Modal>
        <!-- Account Creation Modal -->
        <Modal :show="isAccountModalOpen" @close="closeAccountModal" maxWidth="sm">
            <div class="p-6">
                <div class="flex justify-between items-center mb-5">
                    <h2 class="text-xl font-bold text-slate-800">Buat Akun Sales</h2>
                    <button @click="closeAccountModal" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                
                <form @submit.prevent="createAccount" class="space-y-5">
                    <div class="bg-indigo-50 text-indigo-800 p-4 rounded-xl text-xs sm:text-sm mb-4">
                        <span class="block font-semibold mb-1">Sales: {{ currentSaleForAccount?.name }}</span>
                        <span class="block">Email: {{ currentSaleForAccount?.email || 'TIDAK ADA EMAIL' }}</span>
                    </div>

                    <div v-if="!currentSaleForAccount?.email" class="bg-rose-50 text-rose-700 p-3 rounded-lg text-xs sm:text-sm">
                        Anda harus mengisi email sales terlebih dahulu sebelum bisa membuat akun.
                    </div>

                    <div v-if="currentSaleForAccount?.email">
                        <InputLabel for="account_password" value="Password Baru" />
                        <TextInput
                            id="account_password"
                            type="password"
                            class="mt-1 block w-full bg-slate-50 focus:bg-white transition-colors"
                            v-model="accountForm.password"
                            required
                        />
                        <InputError class="mt-2" :message="accountForm.errors.password" />
                        <p class="text-xs text-slate-500 mt-1">Minimal 8 karakter.</p>
                    </div>

                    <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-slate-100">
                        <SecondaryButton @click="closeAccountModal">Batal</SecondaryButton>
                        <PrimaryButton :class="{ 'opacity-25': accountForm.processing }" :disabled="accountForm.processing || !currentSaleForAccount?.email">
                            {{ accountForm.processing ? 'Menyimpan...' : 'Buat Akun' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
        </div>
        </div>
    </AuthenticatedLayout>
</template>
