<script setup>
import { ref, computed } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    commissions: Array,
    paymentMethods: Array,
    is_sales: Boolean,
    can_pay: Boolean,
});

const search = ref('');
const statusFilter = ref('');

const formatCurrency = (value) => {
    if (!value) return '0';
    return Number(value).toLocaleString('id-ID');
};

const filteredCommissions = computed(() => {
    return props.commissions.filter(c => {
        const matchesSearch = 
            (c.sales?.name?.toLowerCase().includes(search.value.toLowerCase()) || '') ||
            (c.customer?.name?.toLowerCase().includes(search.value.toLowerCase()) || '') ||
            (c.description?.toLowerCase().includes(search.value.toLowerCase()) || '');
        const matchesStatus = statusFilter.value ? c.status === statusFilter.value : true;
        return matchesSearch && matchesStatus;
    });
});

const totalPending = computed(() => {
    return filteredCommissions.value
        .filter(c => c.status === 'pending')
        .reduce((sum, c) => sum + Number(c.amount), 0);
});

const totalPaid = computed(() => {
    return filteredCommissions.value
        .filter(c => c.status === 'paid')
        .reduce((sum, c) => sum + Number(c.amount), 0);
});

const payModal = ref(false);
const payForm = useForm({
    commission_id: null,
    date: new Date().toISOString().split('T')[0],
    payment_method: 'Tunai',
    proof: null
});

const openPayModal = (commission) => {
    payForm.reset();
    payForm.clearErrors();
    payForm.commission_id = commission.id;
    payModal.value = true;
};

const closePayModal = () => {
    payModal.value = false;
    payForm.reset();
    payForm.clearErrors();
};

const cancelPay = (commission) => {
    if (confirm('Yakin ingin membatalkan pembayaran komisi ini? Transaksi pengeluaran di laporan keuangan akan dihapus.')) {
        router.post(`/komisi/${commission.id}/cancel-pay`, {}, {
            preserveScroll: true,
            preserveState: true,
        });
    }
};

const submitPay = () => {
    payForm.post(route('komisi.pay', payForm.commission_id), {
        onSuccess: () => closePayModal(),
    });
};

const getBadgeColor = (status) => {
    if (status === 'paid') return 'bg-emerald-100 text-emerald-800';
    if (status === 'hold') return 'bg-slate-100 text-slate-800';
    if (status === 'void') return 'bg-red-100 text-red-800';
    return 'bg-amber-100 text-amber-800'; // pending
};
const getBadgeText = (status) => {
    if (status === 'paid') return 'Sudah Dibayar';
    if (status === 'hold') return 'Ditangguhkan';
    if (status === 'void') return 'Dibatalkan';
    return 'Pending';
};
const getTypeBadgeColor = (type) => {
    if (type === 'booking') return 'bg-indigo-100 text-indigo-800';
    if (type === 'clawback') return 'bg-rose-100 text-rose-800';
    if (type.includes('monthly')) return 'bg-blue-100 text-blue-800';
    return 'bg-slate-100 text-slate-800';
};
const getTypeText = (type) => {
    if (type === 'booking') return 'Booking';
    if (type === 'clawback') return 'Potongan/Denda';
    if (type === 'monthly_direct') return 'Bulanan (Langsung)';
    if (type === 'monthly_upline_1') return 'Bulanan (Downline 1)';
    if (type === 'monthly_upline_2') return 'Bulanan (Downline 2)';
    return type;
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Data Komisi" />
        <template #header>
            <h2 class="font-bold text-xl text-slate-800 leading-tight">Data Komisi</h2>
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

                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col relative overflow-hidden group">
                        <div class="absolute -right-6 -top-6 w-24 h-24 bg-amber-50 rounded-full group-hover:scale-110 transition-transform duration-500"></div>
                        <div class="text-xs sm:text-sm font-semibold text-slate-500 uppercase tracking-wider mb-2 relative z-10">Total Pending</div>
                        <div class="text-3xl font-black text-amber-600 relative z-10">Rp {{ formatCurrency(totalPending) }}</div>
                    </div>
                    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col relative overflow-hidden group">
                        <div class="absolute -right-6 -top-6 w-24 h-24 bg-emerald-50 rounded-full group-hover:scale-110 transition-transform duration-500"></div>
                        <div class="text-xs sm:text-sm font-semibold text-slate-500 uppercase tracking-wider mb-2 relative z-10">Total Sudah Dibayar</div>
                        <div class="text-3xl font-black text-emerald-600 relative z-10">Rp {{ formatCurrency(totalPaid) }}</div>
                    </div>
                </div>

                <!-- Table Container -->
                <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-6 border-b border-slate-100 flex flex-col sm:flex-row justify-between items-center gap-4">
                        <h3 class="text-lg font-bold text-slate-800">Daftar Komisi</h3>
                        
                        <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                            <div class="relative w-full sm:w-64">
                                <input
                                    v-model="search"
                                    type="text"
                                    placeholder="Cari sales, pelanggan..."
                                    class="w-full pl-10 pr-4 py-2 border-slate-200 rounded-xl text-xs sm:text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                            </div>
                            <select 
                                v-model="statusFilter"
                                class="w-full sm:w-auto border-slate-200 rounded-xl text-xs sm:text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">Semua Status</option>
                                <option value="pending">Pending</option>
                                <option value="paid">Sudah Dibayar</option>
                                <option value="hold">Ditangguhkan</option>
                                <option value="void">Dibatalkan</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto w-full pb-4">
<table class="w-full text-left text-xs sm:text-sm whitespace-nowrap">
                            <thead class="bg-slate-50 border-b border-slate-100 text-slate-600 font-semibold">
                                <tr>
                                    <th class="px-3 py-3 sm:px-6 sm:py-4">Tanggal</th>
                                    <th v-if="!is_sales" class="px-3 py-3 sm:px-6 sm:py-4">Sales / Afiliator</th>
                                    <th class="px-3 py-3 sm:px-6 sm:py-4">Pelanggan (Sumber)</th>
                                    <th class="px-3 py-3 sm:px-6 sm:py-4">Jenis Komisi</th>
                                    <th class="px-3 py-3 sm:px-6 sm:py-4">Nominal</th>
                                    <th class="px-3 py-3 sm:px-6 sm:py-4 text-center">Status</th>
                                    <th class="px-3 py-3 sm:px-6 sm:py-4 text-center">Aksi / Bukti</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="c in filteredCommissions" :key="c.id" class="hover:bg-slate-50/70 transition-colors">
                                    <td class="px-3 py-3 sm:px-6 sm:py-4">
                                        <div class="text-slate-700">{{ new Date(c.created_at).toLocaleDateString('id-ID', {day: 'numeric', month: 'short', year: 'numeric'}) }}</div>
                                    </td>
                                    <td v-if="!is_sales" class="px-3 py-3 sm:px-6 sm:py-4">
                                        <div class="font-bold text-slate-800">{{ c.sales?.name || '-' }}</div>
                                    </td>
                                    <td class="px-3 py-3 sm:px-6 sm:py-4">
                                        <div class="font-medium text-slate-700">{{ c.customer?.name || '-' }}</div>
                                        <div v-if="c.customer?.sales?.id && c.customer.sales.id !== c.sales_id" class="text-[10px] text-slate-500 mt-0.5">
                                            dari downline: <span class="font-semibold">{{ c.customer.sales.name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-3 py-3 sm:px-6 sm:py-4">
                                        <div class="flex flex-col gap-1">
                                            <span :class="['w-fit px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider', getTypeBadgeColor(c.type)]">
                                                {{ getTypeText(c.type) }}
                                            </span>
                                            <span class="text-xs text-slate-500 truncate max-w-xs" :title="c.description">{{ c.description }}</span>
                                        </div>
                                    </td>
                                    <td class="px-3 py-3 sm:px-6 sm:py-4">
                                        <div class="font-bold text-slate-800">Rp {{ formatCurrency(c.amount) }}</div>
                                    </td>
                                    <td class="px-3 py-3 sm:px-6 sm:py-4 text-center">
                                        <span :class="['px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full', getBadgeColor(c.status)]">
                                            {{ getBadgeText(c.status) }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-3 sm:px-6 sm:py-4 whitespace-nowrap text-right text-xs sm:text-sm font-medium">
                                        <button v-if="can_pay && c.status === 'pending'" @click="openPayModal(c)" class="text-xs bg-indigo-50 text-indigo-600 hover:bg-indigo-100 font-semibold px-3 py-1.5 rounded-lg transition-colors border border-indigo-200">
                                            Tandai Dibayar
                                        </button>
                                        <div v-else-if="c.status === 'paid'" class="flex flex-col items-end gap-1">
                                            <a v-if="c.proof_of_payment" :href="'/storage/' + c.proof_of_payment" target="_blank" class="text-xs text-blue-500 hover:underline">
                                                Lihat Bukti
                                            </a>
                                            <button v-if="can_pay" @click="cancelPay(c)" class="text-[10px] text-red-500 hover:text-red-700 underline mt-1">
                                                Batal Bayar
                                            </button>
                                        </div>
                                        <span v-else-if="can_pay" class="text-xs text-slate-400 italic">-</span>
                                    </td>
                                </tr>
                                <tr v-if="filteredCommissions.length === 0">
                                    <td :colspan="is_sales ? 6 : 7" class="px-6 py-12 text-center text-slate-500">
                                        Belum ada data komisi.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
</div>
                </div>

                <div v-if="can_pay" class="text-xs text-slate-500 bg-slate-50 p-4 rounded-xl border border-slate-200 mt-6 flex items-start gap-3">
                    <svg class="w-5 h-5 text-slate-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <p><strong class="text-slate-700">Info:</strong> Tombol "Tandai Dibayar" hanya mengubah status komisi di halaman ini. Proses transfer dana dan pencatatan kas keluar di "Buku Kas" harus Anda lakukan secara manual bila diperlukan.</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>

    <Modal :show="payModal" @close="closePayModal">
        <div class="p-6">
            <h2 class="text-lg font-medium text-slate-900 mb-4">
                Tandai Komisi Dibayar
            </h2>
            <p class="text-xs sm:text-sm text-slate-600 mb-6">
                Silakan unggah foto atau dokumen bukti pencairan komisi.
            </p>

            <form @submit.prevent="submitPay">
                <div class="mb-4">
                    <label class="block text-xs sm:text-sm font-medium text-slate-700 mb-1">Tanggal Pembayaran</label>
                    <input type="date" v-model="payForm.date" class="block w-full text-xs sm:text-sm border border-slate-300 rounded-md p-2 focus:border-indigo-500 focus:ring-indigo-500" required />
                    <div v-if="payForm.errors.date" class="text-xs sm:text-sm text-red-600 mt-1">{{ payForm.errors.date }}</div>
                </div>

                <div class="mb-4">
                    <label class="block text-xs sm:text-sm font-medium text-slate-700 mb-1">Metode Bayar</label>
                    <select v-model="payForm.payment_method" class="block w-full text-xs sm:text-sm border border-slate-300 rounded-md p-2 focus:border-indigo-500 focus:ring-indigo-500" required>
                        <option v-for="method in props.paymentMethods" :key="method.id" :value="method.name">{{ method.name }}</option>
                    </select>
                    <div v-if="payForm.errors.payment_method" class="text-xs sm:text-sm text-red-600 mt-1">{{ payForm.errors.payment_method }}</div>
                </div>

                <div class="mb-4">
                    <label class="block text-xs sm:text-sm font-medium text-slate-700 mb-1">Bukti Pencairan</label>
                    <input type="file" @input="payForm.proof = $event.target.files[0]" class="block w-full text-xs sm:text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs sm:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border border-slate-300 rounded-md p-2" required accept="image/jpeg,image/png,image/jpg,application/pdf" />
                    <div v-if="payForm.errors.proof" class="text-xs sm:text-sm text-red-600 mt-1">{{ payForm.errors.proof }}</div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="button" @click="closePayModal" class="px-4 py-2 text-xs sm:text-sm text-slate-600 hover:text-slate-800 mr-2">Batal</button>
                    <PrimaryButton :class="{ 'opacity-25': payForm.processing }" :disabled="payForm.processing">
                        Simpan
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>
