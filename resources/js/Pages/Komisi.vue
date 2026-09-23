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
const showRules = ref(false);

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

const proofModal = ref(false);
const proofUrl = ref('');

const openProofModal = (url) => {
    proofUrl.value = url;
    proofModal.value = true;
};

const closeProofModal = () => {
    proofModal.value = false;
    proofUrl.value = '';
};

const isImage = (url) => {
    if (!url) return false;
    return url.match(/\.(jpeg|jpg|gif|png|webp)$/i) != null;
};

const isPdf = (url) => {
    if (!url) return false;
    return url.match(/\.(pdf)$/i) != null;
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

                <!-- Aturan & Ketentuan Komisi (Accordion) -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <button @click="showRules = !showRules" class="w-full px-6 py-4 flex items-center justify-between bg-slate-50 hover:bg-slate-100 transition-colors focus:outline-none">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-indigo-100 text-indigo-600 rounded-lg">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <span class="font-bold text-slate-800 text-sm sm:text-base">Panduan & Ketentuan Komisi</span>
                        </div>
                        <svg class="w-5 h-5 text-slate-400 transition-transform duration-300" :class="showRules ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    
                    <div v-show="showRules" class="p-6 border-t border-slate-200 text-sm text-slate-700 space-y-6">
                        
                        <div>
                            <h4 class="font-bold text-slate-800 text-base mb-2 flex items-center gap-2">
                                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                1. Skema Komisi Multi-Tier
                            </h4>
                            <ul class="list-disc pl-5 space-y-2">
                                <li><strong>Direct Sales:</strong> Sales yang melakukan penjualan langsung (Closing) berhak mendapatkan komisi awal sebesar <strong>Rp 35.000</strong> (dibayarkan satu kali), serta komisi berkelanjutan sebesar <strong>Rp 5.000/bulan</strong> selama pelanggan tersebut masih aktif berlangganan.</li>
                                <li><strong>Upline 1 (Sponsor Langsung):</strong> Mendapatkan komisi pasif sebesar <strong>Rp 2.000/bulan</strong> dari setiap pelanggan aktif yang berhasil di-closing oleh downline level 1.</li>
                                <li><strong>Upline 2 (Sponsor dari Sponsor):</strong> Mendapatkan komisi pasif sebesar <strong>Rp 1.000/bulan</strong> dari setiap pelanggan aktif yang berhasil di-closing oleh downline level 2.</li>
                            </ul>
                        </div>

                        <div>
                            <h4 class="font-bold text-slate-800 text-base mb-2 flex items-center gap-2">
                                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path></svg>
                                2. Syarat Pencairan & Pembayaran
                            </h4>
                            <ul class="list-disc pl-5 space-y-2">
                                <li>Status komisi akan otomatis tercatat sebagai <strong>Pending</strong> saat pelanggan baru berstatus <strong>Aktif</strong> (selesai pasang).</li>
                                <li>Komisi hanya dapat <strong>Dicairkan (Dibayar)</strong> oleh pihak Admin/Finance setelah divalidasi bahwa pelanggan yang bersangkutan <strong>telah melakukan pembayaran tagihan bulanannya</strong>.</li>
                                <li>Jadwal pencairan komisi secara rutin dilakukan setiap <strong>tanggal {{ $page.props.settings?.commission_payout_date || 13 }}</strong> setiap bulannya.</li>
                                <li>Admin/Finance diwajibkan untuk mengunggah <strong>Bukti Transfer/Pencairan</strong> sebagai bukti sah perubahan status komisi menjadi dibayar.</li>
                            </ul>
                        </div>

                        <div class="bg-red-50 border border-red-200 rounded-xl p-4">
                            <h4 class="font-bold text-red-800 text-base mb-2 flex items-center gap-2">
                                <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                3. Ketentuan Pembatalan & Penalti (Refund)
                            </h4>
                            <ul class="list-disc pl-5 space-y-2 text-red-700">
                                <li>Jika pelanggan gagal melakukan pemasangan (status Batal), komisi tidak akan dihitung atau diberikan.</li>
                                <li>Apabila ditemukan indikasi kecurangan, Admin memiliki hak penuh untuk membatalkan komisi secara sepihak.</li>
                                <li><strong>PENTING:</strong> Jika pelanggan memutuskan untuk berhenti berlangganan (cabut) dalam kurun waktu <strong>di bawah 3 bulan</strong>, maka seluruh dana komisi yang telah dibayarkan terkait pelanggan tersebut (Rp 35.000, Rp 5.000, Rp 2.000, maupun Rp 1.000) wajib dikembalikan. Sistem akan memotong (retur) dari hasil pencairan komisi penjualan Anda berikutnya.</li>
                            </ul>
                        </div>

                    </div>
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
                        <div class="text-xs sm:text-sm font-semibold text-slate-500 uppercase tracking-wider mb-2 relative z-10">Total Komisi Dicairkan</div>
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
                                            <button v-if="c.proof_of_payment" @click="openProofModal('/storage/' + c.proof_of_payment)" class="text-xs bg-emerald-50 text-emerald-600 hover:bg-emerald-100 font-semibold px-3 py-1.5 rounded-lg transition-colors border border-emerald-200 inline-flex items-center gap-1.5 w-full justify-center">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                                Lihat Bukti
                                            </button>
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

                <div v-if="can_pay" class="text-xs text-slate-500 bg-emerald-50 p-4 rounded-xl border border-emerald-200 mt-6 flex items-start gap-3">
                    <svg class="w-5 h-5 text-emerald-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <p><strong class="text-emerald-700">Info:</strong> Tombol "Tandai Dibayar" akan mengunggah bukti pencairan (TF) dan secara otomatis mencatat pengeluaran kas tersebut di <strong>Buku Kas / Keuangan</strong>.</p>
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

    <!-- Modal Lihat Bukti -->
    <Modal :show="proofModal" @close="closeProofModal" maxWidth="xl">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-medium text-slate-900">
                    Bukti Pencairan Komisi
                </h2>
                <button @click="closeProofModal" class="text-slate-400 hover:text-slate-500">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <div class="bg-slate-50 rounded-xl p-2 border border-slate-200 flex justify-center items-center min-h-[300px]">
                <img v-if="isImage(proofUrl)" :src="proofUrl" alt="Bukti Transfer" class="max-w-full max-h-[60vh] object-contain rounded-lg shadow-sm" />
                <iframe v-else-if="isPdf(proofUrl)" :src="proofUrl" class="w-full h-[60vh] rounded-lg"></iframe>
                <div v-else class="text-center text-slate-500 py-10">
                    <svg class="mx-auto h-12 w-12 text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <p>Format file tidak didukung untuk pratinjau.</p>
                    <a :href="proofUrl" target="_blank" class="text-indigo-600 hover:underline mt-2 inline-block">Buka di tab baru</a>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <a :href="proofUrl" target="_blank" class="px-4 py-2 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded-lg text-sm font-semibold transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                    Buka Penuh
                </a>
            </div>
        </div>
    </Modal>
</template>
