<script setup>
import { ref, computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    roles: {
        type: Array,
        required: true,
    },
    allPermissions: {
        type: Array,
        default: () => [],
    },
});

const isModalOpen = ref(false);
const editingRole = ref(null);

const form = useForm({
    permissions: [],
});

// Permission groups for organized display
const permissionGroups = computed(() => [
    {
        label: 'Dashboard',
        icon: '🏠',
        permissions: props.allPermissions.filter(p => p.includes('dashboard')),
    },
    {
        label: 'Booking Pelanggan',
        icon: '📅',
        permissions: props.allPermissions.filter(p => p.includes('booking')),
    },
    {
        label: 'Data Pelanggan Aktif',
        icon: '👥',
        permissions: props.allPermissions.filter(p => p.includes('pelanggan')),
    },
    {
        label: 'Data Non-Aktif',
        icon: '⏸️',
        permissions: props.allPermissions.filter(p => p.includes('nonaktif')),
    },
    {
        label: 'Transaksi',
        icon: '💰',
        permissions: props.allPermissions.filter(p => p.includes('transaksi')),
    },
    {
        label: 'Billing',
        icon: '📄',
        permissions: props.allPermissions.filter(p => p.includes('billing')),
    },
    {
        label: 'Voucher & Saldo',
        icon: '🎟️',
        permissions: props.allPermissions.filter(p => p.includes('voucher_saldo')),
    },
    {
        label: 'Multi-Tier Affiliate & Sales',
        icon: '🔗',
        permissions: props.allPermissions.filter(p => p.includes('affiliate') || p.includes('akun_sales')),
    },
    {
        label: 'Laporan',
        icon: '📊',
        permissions: props.allPermissions.filter(p => p.includes('laporan')),
    },
    {
        label: 'Master Data',
        icon: '🗄️',
        permissions: props.allPermissions.filter(p => p.includes('master_data')),
    },
    {
        label: 'Pengaturan & Manajemen',
        icon: '⚙️',
        permissions: props.allPermissions.filter(p => p.includes('pengaturan') || p.includes('manajemen')),
    },
]);

const permissionLabels = {
    // Dashboard
    'akses_dashboard': 'Lihat Dashboard',
    
    // Booking Pelanggan
    'akses_booking': 'Lihat Daftar Booking',
    'tambah_booking': 'Tambah Booking Baru',
    'edit_booking': 'Edit Data Booking',
    'hapus_booking': 'Hapus Data Booking',
    'setujui_booking': 'Aktivasi Booking ke Pelanggan Aktif',

    // Pelanggan Aktif
    'akses_data_pelanggan': 'Lihat Daftar Pelanggan Aktif',
    'tambah_pelanggan': 'Tambah Pelanggan Baru',
    'edit_pelanggan': 'Edit Data Pelanggan',
    'hapus_pelanggan': 'Hapus Data Pelanggan',
    
    // Pelanggan Non-Aktif
    'akses_data_nonaktif': 'Lihat Data Non-Aktif',

    // Transaksi / Buku Kas
    'akses_transaksi': 'Lihat Transaksi',
    'tambah_transaksi': 'Tambah Transaksi',
    'edit_transaksi': 'Edit Transaksi',
    'hapus_transaksi': 'Hapus Transaksi',
    
    // Billing
    'akses_billing': 'Lihat Transaksi Billing',
    'tambah_billing': 'Proses Billing Lunas/Janji Bayar',
    'edit_billing': 'Edit/Import Data Billing',
    'hapus_billing': 'Rollback/Batalkan Billing',
    
    // Voucher & Saldo
    'akses_voucher_saldo': 'Lihat Voucher & Saldo',
    'tambah_voucher_saldo': 'Tambah Transaksi Voucher/Saldo',
    'edit_voucher_saldo': 'Edit Status (Lunas/Batal)',
    'hapus_voucher_saldo': 'Hapus Transaksi Voucher/Saldo',
    
    // Multi-Tier Affiliate & Sales
    'akses_affiliate': 'Lihat Pendaftaran Sales & Affiliate',
    'tambah_affiliate': 'Tambah Data Sales/Affiliate',
    'edit_affiliate': 'Edit Data Sales/Affiliate',
    'hapus_affiliate': 'Hapus Data Sales/Affiliate',
    'kelola_akun_sales': 'Kelola Akun Login Sales (Buat/Hapus Akun)',

    // Laporan
    'akses_laporan': 'Lihat Laporan Keuangan',
    'ekspor_laporan': 'Ekspor Laporan (Excel/PDF)',
    
    // Master Data
    'akses_master_data': 'Lihat Master Data',
    'tambah_master_data': 'Tambah Master Data Baru',
    'edit_master_data': 'Edit Master Data',
    'hapus_master_data': 'Hapus Master Data',
    
    // Pengaturan
    'manajemen_pengguna': 'Kelola Akun Pengguna (Staff/Admin)',
    'manajemen_role': 'Kelola Role & Hak Akses',
    'pengaturan_aplikasi': 'Pengaturan Aplikasi (Logo, dll)',
};

const openEditModal = (role) => {
    editingRole.value = role;
    form.permissions = [...(role.permissions || [])];
    form.clearErrors();
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
};

const toggleAllPermissions = () => {
    if (form.permissions.length === props.allPermissions.length) {
        form.permissions = [];
    } else {
        form.permissions = [...props.allPermissions];
    }
};

const toggleGroupPermissions = (group) => {
    const allChecked = group.permissions.every(p => form.permissions.includes(p));
    if (allChecked) {
        form.permissions = form.permissions.filter(p => !group.permissions.includes(p));
    } else {
        const newPerms = [...form.permissions];
        group.permissions.forEach(p => {
            if (!newPerms.includes(p)) newPerms.push(p);
        });
        form.permissions = newPerms;
    }
};

const submit = () => {
    if (editingRole.value) {
        form.put(route('roles.update', editingRole.value.id), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
};

const authPermissions = usePage().props.auth.permissions || [];
const canManage = authPermissions.includes('manajemen_pengguna');
</script>

<template>
    <section v-if="canManage" class="mt-8">
        <header class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-800 flex items-center gap-2">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-100 text-emerald-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    </span>
                    Manajemen Role
                </h2>
                <p class="mt-1 text-xs sm:text-sm text-slate-500">
                    Atur hak akses default untuk masing-masing tipe Role.
                </p>
            </div>
        </header>

        <div class="mt-6 grid gap-4 grid-cols-1 sm:grid-cols-2 md:grid-cols-3">
            <div v-for="role in roles" :key="role.id" class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 hover:shadow-md transition-shadow flex flex-col justify-between">
                <div>
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="font-bold text-lg text-slate-800 uppercase tracking-wide">{{ role.name }}</h3>
                            <p class="text-xs text-slate-500 mt-1">
                                {{ role.permissions.length === allPermissions.length ? 'Akses Penuh' : (role.permissions.length + ' hak akses') }}
                            </p>
                        </div>
                        <button @click="openEditModal(role)" class="p-2 text-indigo-600 bg-indigo-50 hover:bg-indigo-100 rounded-lg transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        </button>
                    </div>
                    <div class="flex flex-wrap gap-1">
                        <span v-for="perm in role.permissions.slice(0, 3)" :key="perm" class="px-2 py-0.5 text-[10px] bg-slate-100 text-slate-600 rounded-full border border-slate-200 font-medium">
                            {{ permissionLabels[perm] || perm }}
                        </span>
                        <span v-if="role.permissions.length > 3" class="px-2 py-0.5 text-[10px] bg-slate-100 text-slate-500 rounded-full border border-slate-200 font-medium">
                            +{{ role.permissions.length - 3 }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Role Modal -->
        <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="closeModal"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col">
                <!-- Modal Header -->
                <div class="px-3 py-3 sm:px-6 sm:py-4 border-b border-slate-200 flex-shrink-0">
                    <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-amber-100 text-amber-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        </span>
                        Edit Hak Akses Role: <span class="uppercase">{{ editingRole.name }}</span>
                    </h3>
                </div>
                
                <!-- Modal Body (scrollable) -->
                <div class="overflow-y-auto flex-1 px-6 py-5">
                    <form @submit.prevent="submit" class="space-y-5">
                        <!-- Permissions Section -->
                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-xs sm:text-sm font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2">
                                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                                    Hak Akses (Permissions)
                                </h4>
                                <button type="button" @click="toggleAllPermissions" class="text-xs font-bold px-3 py-1.5 rounded-lg transition-all"
                                    :class="form.permissions.length === allPermissions.length ? 'bg-red-100 text-red-700 hover:bg-red-200' : 'bg-indigo-100 text-indigo-700 hover:bg-indigo-200'">
                                    {{ form.permissions.length === allPermissions.length ? 'Hapus Semua' : 'Pilih Semua' }}
                                </button>
                            </div>

                            <div class="space-y-3">
                                <div v-for="group in permissionGroups" :key="group.label" class="rounded-xl border border-slate-200 overflow-hidden">
                                    <!-- Group Header -->
                                    <div class="flex items-center justify-between px-4 py-2.5 bg-slate-50 cursor-pointer hover:bg-slate-100 transition-colors" @click="toggleGroupPermissions(group)">
                                        <div class="flex items-center gap-2">
                                            <span class="text-base">{{ group.icon }}</span>
                                            <span class="text-xs sm:text-sm font-semibold text-slate-700">{{ group.label }}</span>
                                            <span class="text-xs text-slate-400 font-medium">({{ group.permissions.filter(p => form.permissions.includes(p)).length }}/{{ group.permissions.length }})</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span v-if="group.permissions.every(p => form.permissions.includes(p))" class="text-xs font-bold text-green-600">✓ Semua</span>
                                        </div>
                                    </div>
                                    <!-- Group Permissions -->
                                    <div class="px-2 py-2 sm:px-4 sm:py-3 bg-white grid grid-cols-1 sm:grid-cols-2 gap-2">
                                        <label v-for="perm in group.permissions" :key="perm" class="flex items-center gap-2.5 cursor-pointer group py-1 px-2 rounded-lg hover:bg-slate-50 transition-colors">
                                            <input type="checkbox" :value="perm" v-model="form.permissions" class="rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500 focus:ring-offset-0 w-4 h-4 group-hover:border-indigo-400 transition-colors" />
                                            <span class="text-xs sm:text-sm text-slate-700 group-hover:text-slate-900 font-medium transition-colors">{{ permissionLabels[perm] || perm }}</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Modal Footer -->
                <div class="px-3 py-3 sm:px-6 sm:py-4 border-t border-slate-200 flex items-center justify-end gap-3 flex-shrink-0 bg-slate-50">
                    <button type="button" @click="closeModal" class="px-4 py-2.5 bg-white border border-slate-300 rounded-xl font-semibold text-xs text-slate-700 uppercase tracking-widest shadow-sm hover:bg-slate-50 transition">
                        Batal
                    </button>
                    <button @click="submit" :disabled="form.processing" class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 border border-transparent rounded-xl font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 transition-all shadow-md">
                        <svg v-if="form.processing" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                    </button>
                </div>
            </div>
        </div>
    </section>
</template>
