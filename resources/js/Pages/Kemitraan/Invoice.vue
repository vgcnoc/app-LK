<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import KemitraanLayout from '@/Layouts/KemitraanLayout.vue';

const props = defineProps({
    invoices: { type: Array, default: () => [] },
    payments: { type: Array, default: () => [] },
    mitras: { type: Array, default: () => [] },
    isAdmin: { type: Boolean, default: false }
});

const formattedTodayDate = computed(() => {
    return new Intl.DateTimeFormat('id-ID', {
        weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
    }).format(new Date());
});

const statusColor = (status) => {
    const map = {
        'Lunas': 'bg-emerald-100 text-emerald-700',
        'Belum Bayar': 'bg-rose-100 text-rose-700',
    };
    return map[status] || 'bg-slate-100 text-slate-700';
};

const formatRupiah = (angka) => {
    if (!angka) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(angka);
};

const showModal = ref(false);
const isEditing = ref(false);

const form = useForm({
    id: null,
    user_id: '',
    kategori: 'Bandwidth',
    tipe_pembayaran: '1 Kali',
    tanggal_tagihan: '',
    jatuh_tempo: '',
    nominal: '',
    judul: '',
    keterangan: '',
    status: 'Belum Bayar'
});

const openAddModal = () => {
    isEditing.value = false;
    form.reset();
    showModal.value = true;
};

const openEditModal = (inv) => {
    isEditing.value = true;
    form.id = inv.id;
    form.user_id = inv.user_id;
    form.kategori = inv.kategori || 'Bandwidth';
    form.tipe_pembayaran = inv.tipe_pembayaran || '1 Kali';
    form.tanggal_tagihan = inv.tanggal_tagihan;
    form.jatuh_tempo = inv.jatuh_tempo;
    form.nominal = inv.nominal;
    form.judul = inv.judul;
    form.keterangan = inv.keterangan;
    form.status = inv.status;
    showModal.value = true;
};

const submitForm = () => {
    form.post(route('kemitraan.invoice.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showModal.value = false;
            form.reset();
        }
    });
};

const activeTab = ref('tagihan');
const showPaymentModal = ref(false);
const currentInvoiceForPayment = ref(null);

const paymentForm = useForm({
    invoice_id: null,
    nominal_bayar: '',
    tanggal_bayar: '',
    metode_pembayaran: 'Transfer',
    keterangan: ''
});

const openPaymentModal = (inv) => {
    currentInvoiceForPayment.value = inv;
    paymentForm.reset();
    paymentForm.invoice_id = inv.id;
    paymentForm.nominal_bayar = inv.nominal - (inv.terbayar || 0);
    paymentForm.tanggal_bayar = new Date().toISOString().split('T')[0];
    showPaymentModal.value = true;
};

const submitPayment = () => {
    paymentForm.post(route('kemitraan.invoice.pay'), {
        preserveScroll: true,
        onSuccess: () => {
            showPaymentModal.value = false;
            paymentForm.reset();
        }
    });
};

const showDetailModal = ref(false);
const currentInvoiceForDetail = ref(null);

const openDetailModal = (inv) => {
    currentInvoiceForDetail.value = inv;
    showDetailModal.value = true;
};
const printInvoice = () => {
    window.print();
};
</script>

<style>
@media print {
    body * {
        visibility: hidden !important;
    }
    .no-print, .no-print * {
        display: none !important;
    }
    #printable-invoice, #printable-invoice * {
        visibility: visible !important;
    }
    #printable-invoice {
        position: fixed !important;
        left: 0 !important;
        top: 0 !important;
        width: 100% !important;
        height: 100vh !important;
        margin: 0 !important;
        padding: 40px !important;
        background: white !important;
        box-shadow: none !important;
        border: none !important;
        z-index: 9999 !important;
    }
}
</style>

<template>
    <Head title="Invoice - Kemitraan" />

    <KemitraanLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex-1 min-w-0">
                    <h2 class="text-xl sm:text-3xl font-bold text-slate-800 tracking-tight">
                        Tagihan / Invoice
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">
                        Daftar tagihan kemitraan.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <button v-if="isAdmin" @click="openAddModal" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-xl shadow-sm text-sm font-semibold transition-colors">
                        + Tambah Tagihan
                    </button>
                    <div class="hidden sm:flex items-center gap-2 bg-white border border-slate-200 px-4 py-2.5 rounded-xl shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span class="text-xs sm:text-sm font-medium text-slate-700">{{ formattedTodayDate }}</span>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-4 sm:py-8 bg-slate-50 min-h-screen">
            <div class="max-w-full mx-auto space-y-6">

                <!-- Tabs -->
                <div class="flex space-x-1 bg-slate-200/50 p-1 rounded-xl w-fit">
                    <button @click="activeTab = 'tagihan'" :class="[activeTab === 'tagihan' ? 'bg-white shadow-sm text-indigo-700 font-semibold' : 'text-slate-600 hover:text-slate-900 font-medium', 'px-4 py-2 rounded-lg text-sm transition-all']">
                        Daftar Tagihan
                    </button>
                    <button @click="activeTab = 'riwayat'" :class="[activeTab === 'riwayat' ? 'bg-white shadow-sm text-indigo-700 font-semibold' : 'text-slate-600 hover:text-slate-900 font-medium', 'px-4 py-2 rounded-lg text-sm transition-all']">
                        Riwayat Pembayaran
                    </button>
                </div>

                <!-- Table -->
                <div v-if="activeTab === 'tagihan'" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-slate-600">
                            <thead class="text-xs uppercase text-slate-400 border-b border-slate-100 bg-slate-50/50">
                                <tr>
                                    <th class="py-4 px-6 font-semibold">No. Invoice</th>
                                    <th v-if="isAdmin" class="py-4 px-6 font-semibold">Mitra</th>
                                    <th class="py-4 px-6 font-semibold">Judul</th>
                                    <th class="py-4 px-6 font-semibold">Nominal</th>
                                    <th class="py-4 px-6 font-semibold">Tgl Tagihan</th>
                                    <th class="py-4 px-6 font-semibold">Jatuh Tempo</th>
                                    <th class="py-4 px-6 font-semibold text-right">Status</th>
                                    <th class="py-4 px-6 font-semibold text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <template v-if="invoices.length > 0">
                                    <tr v-for="inv in invoices" :key="inv.id" class="hover:bg-slate-50 transition-colors">
                                        <td class="py-4 px-6 font-mono font-medium text-indigo-600 text-sm">{{ inv.nomor_invoice }}</td>
                                        <td v-if="isAdmin" class="py-4 px-6 font-medium text-slate-800">{{ inv.mitra_name }}</td>
                                        <td class="py-4 px-6 font-medium text-slate-800">
                                            {{ inv.judul }}
                                            <div class="flex gap-2 mt-1">
                                                <span v-if="inv.kategori" class="px-2 py-0.5 rounded text-[10px] font-semibold bg-indigo-50 text-indigo-700">{{ inv.kategori }}</span>
                                                <span v-if="inv.tipe_pembayaran" class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-700">{{ inv.tipe_pembayaran }}</span>
                                            </div>
                                            <div class="text-xs text-slate-400 font-normal mt-0.5 max-w-xs truncate">{{ inv.keterangan }}</div>
                                        </td>
                                        <td class="py-4 px-6">
                                            <div class="font-semibold text-slate-800">{{ formatRupiah(inv.nominal) }}</div>
                                            <div v-if="inv.terbayar > 0" class="text-[10px] text-emerald-600 font-medium">Terbayar: {{ formatRupiah(inv.terbayar) }}</div>
                                            <div v-if="inv.terbayar > 0 && inv.nominal - inv.terbayar > 0" class="text-[10px] text-rose-500 font-medium">Sisa: {{ formatRupiah(inv.nominal - inv.terbayar) }}</div>
                                        </td>
                                        <td class="py-4 px-6 text-slate-500">{{ inv.tanggal_tagihan }}</td>
                                        <td class="py-4 px-6 text-rose-500 font-medium">{{ inv.jatuh_tempo }}</td>
                                        <td class="py-4 px-6 text-right">
                                            <span :class="[statusColor(inv.status), 'px-2.5 py-1 rounded-full text-xs font-semibold']">
                                                {{ inv.status }}
                                            </span>
                                        </td>
                                        <td class="py-4 px-6 text-center space-x-2">
                                            <button @click="openDetailModal(inv)" class="px-3 py-1.5 text-xs font-medium text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors">
                                                Detail
                                            </button>
                                            <template v-if="isAdmin">
                                                <button @click="openPaymentModal(inv)" v-if="inv.status !== 'Lunas'" class="px-3 py-1.5 text-xs font-medium text-emerald-600 bg-emerald-50 hover:bg-emerald-100 rounded-lg transition-colors">
                                                    Bayar
                                                </button>
                                                <button @click="openEditModal(inv)" class="px-3 py-1.5 text-xs font-medium text-indigo-600 bg-indigo-50 hover:bg-indigo-100 rounded-lg transition-colors">
                                                    Edit
                                                </button>
                                            </template>
                                        </td>
                                    </tr>
                                </template>
                                <tr v-else>
                                    <td :colspan="isAdmin ? 8 : 7" class="py-16 text-center">
                                        <div class="flex flex-col items-center gap-3">
                                            <p class="text-sm text-slate-500">Belum ada tagihan / invoice.</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div v-if="activeTab === 'riwayat'" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-slate-600">
                            <thead class="text-xs uppercase text-slate-400 border-b border-slate-100 bg-slate-50/50">
                                <tr>
                                    <th class="py-4 px-6 font-semibold">Tgl Bayar</th>
                                    <th class="py-4 px-6 font-semibold">No. Invoice</th>
                                    <th v-if="isAdmin" class="py-4 px-6 font-semibold">Mitra</th>
                                    <th class="py-4 px-6 font-semibold">Nominal Bayar</th>
                                    <th class="py-4 px-6 font-semibold">Metode</th>
                                    <th class="py-4 px-6 font-semibold">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <template v-if="payments.length > 0">
                                    <tr v-for="pay in payments" :key="pay.id" class="hover:bg-slate-50 transition-colors">
                                        <td class="py-4 px-6 text-slate-800">{{ pay.tanggal_bayar }}</td>
                                        <td class="py-4 px-6 font-mono text-indigo-600 text-sm">
                                            {{ pay.nomor_invoice }}
                                            <div class="text-[10px] text-slate-400 truncate max-w-[150px]">{{ pay.judul }}</div>
                                        </td>
                                        <td v-if="isAdmin" class="py-4 px-6 font-medium text-slate-800">{{ pay.mitra_name }}</td>
                                        <td class="py-4 px-6 font-semibold text-emerald-600">{{ formatRupiah(pay.nominal_bayar) }}</td>
                                        <td class="py-4 px-6 text-slate-600">{{ pay.metode_pembayaran }}</td>
                                        <td class="py-4 px-6 text-slate-500 text-xs">{{ pay.keterangan || '-' }}</td>
                                    </tr>
                                </template>
                                <tr v-else>
                                    <td :colspan="isAdmin ? 6 : 5" class="py-16 text-center text-sm text-slate-500">
                                        Belum ada riwayat pembayaran.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

        <!-- Form Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-slate-900 bg-opacity-75 transition-opacity" @click="showModal = false"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                    <form @submit.prevent="submitForm">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg leading-6 font-bold text-slate-900 mb-4" id="modal-title">
                                {{ isEditing ? 'Edit Tagihan' : 'Tambah Tagihan Baru' }}
                            </h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Pilih Mitra</label>
                                    <select v-model="form.user_id" required class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        <option value="" disabled>-- Pilih Mitra --</option>
                                        <option v-for="m in mitras" :key="m.id" :value="m.id">{{ m.name }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Kategori Tagihan</label>
                                    <select v-model="form.kategori" required class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        <option value="Registrasi">Registrasi</option>
                                        <option value="Bandwidth & Metro">Bandwidth & Metro</option>
                                        <option value="Bandwidth">Bandwidth</option>
                                        <option value="PPN">PPN</option>
                                        <option value="BHP+USO">BHP+USO</option>
                                        <option value="Lainnya">Lainnya</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Tipe Pembayaran</label>
                                    <select v-model="form.tipe_pembayaran" required class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        <option value="1 Kali">1 Kali</option>
                                        <option value="Bulanan">Bulanan</option>
                                    </select>
                                </div>
                                <div class="grid grid-cols-2 gap-4" v-if="form.kategori !== 'Registrasi'">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">Tgl Tagihan</label>
                                        <input type="date" v-model="form.tanggal_tagihan" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">Jatuh Tempo</label>
                                        <input type="date" v-model="form.jatuh_tempo" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Nominal (Rp)</label>
                                    <input type="number" v-model="form.nominal" required class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Judul Tagihan</label>
                                    <input type="text" v-model="form.judul" required class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Keterangan</label>
                                    <textarea v-model="form.keterangan" rows="2" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Status</label>
                                    <select v-model="form.status" required class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        <option value="Belum Bayar">Belum Bayar</option>
                                        <option value="Lunas">Lunas</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="bg-slate-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse rounded-b-2xl border-t border-slate-200">
                            <button type="submit" :disabled="form.processing" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50">
                                {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                            </button>
                            <button type="button" @click="showModal = false" class="mt-3 w-full inline-flex justify-center rounded-xl border border-slate-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Batal
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- Payment Modal -->
        <div v-if="showPaymentModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-slate-900 bg-opacity-75 transition-opacity" @click="showPaymentModal = false"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md w-full">
                    <form @submit.prevent="submitPayment">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg leading-6 font-bold text-slate-900 mb-4">
                                Pembayaran Tagihan
                            </h3>
                            <div v-if="currentInvoiceForPayment" class="mb-4 p-3 bg-slate-50 rounded-lg border border-slate-200">
                                <div class="text-xs text-slate-500 font-mono">{{ currentInvoiceForPayment.nomor_invoice }}</div>
                                <div class="text-sm font-semibold text-slate-800">{{ currentInvoiceForPayment.judul }}</div>
                                <div class="text-sm mt-1 text-slate-600">Total Tagihan: <span class="font-semibold text-slate-800">{{ formatRupiah(currentInvoiceForPayment.nominal) }}</span></div>
                                <div v-if="currentInvoiceForPayment.terbayar > 0" class="text-sm text-emerald-600">Sudah Dibayar: <span class="font-semibold">{{ formatRupiah(currentInvoiceForPayment.terbayar) }}</span></div>
                                <div class="text-sm mt-1 text-rose-600">Sisa Tagihan: <span class="font-semibold">{{ formatRupiah(currentInvoiceForPayment.nominal - currentInvoiceForPayment.terbayar) }}</span></div>
                            </div>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Nominal Bayar (Rp)</label>
                                    <input type="number" v-model="paymentForm.nominal_bayar" required min="1" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Tanggal Bayar</label>
                                    <input type="date" v-model="paymentForm.tanggal_bayar" required class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Metode Pembayaran</label>
                                    <select v-model="paymentForm.metode_pembayaran" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        <option value="Transfer">Transfer</option>
                                        <option value="Cash">Cash</option>
                                        <option value="Lainnya">Lainnya</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Keterangan Tambahan</label>
                                    <textarea v-model="paymentForm.keterangan" rows="2" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Misal: Pembayaran tahap 1"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="bg-slate-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse rounded-b-2xl border-t border-slate-200">
                            <button type="submit" :disabled="paymentForm.processing" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-emerald-600 text-base font-medium text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50">
                                {{ paymentForm.processing ? 'Menyimpan...' : 'Simpan Pembayaran' }}
                            </button>
                            <button type="button" @click="showPaymentModal = false" class="mt-3 w-full inline-flex justify-center rounded-xl border border-slate-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Batal
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Detail Modal -->
        <div v-if="showDetailModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-slate-900 bg-opacity-75 transition-opacity no-print" @click="showDetailModal = false"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen no-print" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl w-full">
                    
                    <!-- Action Bar (Hidden on Print) -->
                    <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex justify-between items-center no-print">
                        <h3 class="text-lg font-bold text-slate-800">Detail Invoice</h3>
                        <div class="flex gap-2">
                            <button @click="printInvoice" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                </svg>
                                Print / Download
                            </button>
                            <button @click="showDetailModal = false" class="text-slate-400 hover:text-slate-600 p-2 rounded-lg bg-white border border-slate-200 shadow-sm transition-colors">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Printable Content -->
                    <div id="printable-invoice" class="bg-white p-8 sm:p-12" v-if="currentInvoiceForDetail">
                        <!-- Header -->
                        <div class="mb-10">
                            <div class="flex flex-col sm:flex-row border border-dashed border-slate-300 p-2 sm:p-4 bg-white">
                                <!-- Logo -->
                                <div class="w-full sm:w-2/5 flex items-center justify-center p-4 border-b sm:border-b-0 sm:border-r border-dashed border-slate-300">
                                    <template v-if="$page.props.app_logo">
                                        <img :src="$page.props.app_logo" class="h-20 sm:h-24 object-contain" alt="Logo" />
                                    </template>
                                    <template v-else>
                                        <div class="text-3xl sm:text-4xl font-extrabold text-[#4c1d95] tracking-widest uppercase">VIRUZS</div>
                                    </template>
                                </div>
                                <!-- Company Info -->
                                <div class="w-full sm:w-3/5 p-4 sm:pl-8 flex flex-col justify-center">
                                    <h2 class="text-xl sm:text-2xl font-extrabold text-[#4c1d95] uppercase tracking-wide mb-3">PT VIRUZS GLOBAL CONNECTION</h2>
                                    <div class="space-y-1.5 text-xs sm:text-sm text-slate-800">
                                        <div class="flex items-start gap-2">
                                            <span class="mt-0.5 text-[8px] sm:text-[10px]">⚫</span>
                                            <span class="leading-tight">Jl Cilandak RT 002 RW 002,Desa<br>Sirnajaya,Kec Warungkiara Kab Sukabumi Jawa Barat 43362</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"></path></svg>
                                            <span>+62 889 7639 7034</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path><path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path></svg>
                                            <span>info@viruzs.co.id , info@viruzs.net</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM4.332 8.027a6.012 6.012 0 011.912-2.706C6.512 5.73 6.974 6 7.5 6A1.5 1.5 0 019 7.5V8a2 2 0 004 0 2 2 0 011.523-1.943A5.977 5.977 0 0116 10c0 .34-.028.675-.083 1H15a2 2 0 00-2 2v2.197A5.973 5.973 0 0110 16v-2a2 2 0 00-2-2 2 2 0 01-2-2 2 2 0 00-1.668-1.973z" clip-rule="evenodd"></path></svg>
                                            <span>www.viruzs.co.id</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Decorative Bar -->
                            <div class="flex items-center w-full h-4 sm:h-6 mt-1 overflow-visible pl-1 sm:pl-3">
                                <div class="h-full bg-[#4c1d95] w-[30%] min-w-[120px] sm:min-w-[180px]" style="transform: skewX(-45deg); transform-origin: left bottom; margin-left: -20px;"></div>
                                <div class="h-full bg-[#4c1d95] w-6 sm:w-8" style="transform: skewX(-45deg); margin-left: 6px;"></div>
                                <div class="h-full bg-[#f97316] w-12 sm:w-16" style="transform: skewX(-45deg); margin-left: 6px;"></div>
                                <div class="flex-grow h-[2px] bg-[#4c1d95]" style="margin-left: -2px;"></div>
                                <div class="w-3 h-3 rounded-full border-[2px] border-[#4c1d95] bg-white relative z-10" style="margin-left: -2px;"></div>
                            </div>
                        </div>

                        <div class="mb-8">
                            <h2 class="text-3xl font-bold text-[#4c1d95] tracking-widest text-center border-b-2 border-slate-100 pb-4">INVOICE</h2>
                        </div>

                        <!-- Billed To & Meta -->
                        <div class="flex flex-col sm:flex-row justify-between gap-8 mb-12">
                            <!-- Client Info -->
                            <div>
                                <p class="text-xs text-slate-400 font-bold uppercase tracking-wider mb-2">Ditagihkan Kepada:</p>
                                <p class="text-lg font-bold text-slate-800">{{ currentInvoiceForDetail.mitra_name || 'Mitra' }}</p>
                            </div>
                            <!-- Invoice Meta -->
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-start sm:justify-end gap-4">
                                    <span class="text-slate-500 w-24">No. Invoice:</span>
                                    <span class="font-bold text-slate-800 w-32 sm:text-right">{{ currentInvoiceForDetail.nomor_invoice }}</span>
                                </div>
                                <div class="flex justify-start sm:justify-end gap-4">
                                    <span class="text-slate-500 w-24">Tgl Tagihan:</span>
                                    <span class="font-bold text-slate-800 w-32 sm:text-right">{{ currentInvoiceForDetail.tanggal_tagihan || '-' }}</span>
                                </div>
                                <div class="flex justify-start sm:justify-end gap-4">
                                    <span class="text-slate-500 w-24">Jatuh Tempo:</span>
                                    <span class="font-bold text-slate-800 w-32 sm:text-right">{{ currentInvoiceForDetail.jatuh_tempo || '-' }}</span>
                                </div>
                                <div class="flex justify-start sm:justify-end gap-4">
                                    <span class="text-slate-500 w-24">Status:</span>
                                    <span class="font-bold text-slate-800 w-32 sm:text-right uppercase">{{ currentInvoiceForDetail.status }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="mb-12">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-slate-100/70 border-y border-slate-200">
                                        <th class="py-3 px-4 text-xs font-bold text-slate-600 uppercase tracking-wider">Deskripsi</th>
                                        <th class="py-3 px-4 text-xs font-bold text-slate-600 uppercase tracking-wider text-center hidden sm:table-cell">Kategori</th>
                                        <th class="py-3 px-4 text-xs font-bold text-slate-600 uppercase tracking-wider text-right">Jumlah</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="border-b border-slate-100">
                                        <td class="py-4 px-4">
                                            <div class="font-bold text-slate-800">{{ currentInvoiceForDetail.judul }}</div>
                                            <div class="text-sm text-slate-500 mt-1 max-w-sm">{{ currentInvoiceForDetail.keterangan || '-' }}</div>
                                        </td>
                                        <td class="py-4 px-4 text-center text-sm text-slate-700 hidden sm:table-cell">
                                            {{ currentInvoiceForDetail.kategori || '-' }}<br>
                                            <span class="text-xs text-slate-400">{{ currentInvoiceForDetail.tipe_pembayaran || '-' }}</span>
                                        </td>
                                        <td class="py-4 px-4 text-right font-bold text-slate-800">
                                            {{ formatRupiah(currentInvoiceForDetail.nominal) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Totals -->
                        <div class="flex justify-end">
                            <div class="w-full sm:w-1/2 md:w-1/3 space-y-3">
                                <div class="flex justify-between items-center text-sm px-4">
                                    <span class="text-slate-600 font-medium">Total Tagihan</span>
                                    <span class="font-bold text-slate-800">{{ formatRupiah(currentInvoiceForDetail.nominal) }}</span>
                                </div>
                                <div class="flex justify-between items-center text-sm px-4">
                                    <span class="text-slate-600 font-medium">Sudah Dibayar</span>
                                    <span class="font-bold text-emerald-600">{{ formatRupiah(currentInvoiceForDetail.terbayar || 0) }}</span>
                                </div>
                                <div class="border-t-2 border-slate-800 pt-3 flex justify-between items-center px-4">
                                    <span class="text-sm font-bold text-slate-800">SISA TAGIHAN</span>
                                    <span class="text-lg font-bold text-rose-600">{{ formatRupiah(currentInvoiceForDetail.nominal - (currentInvoiceForDetail.terbayar || 0)) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </KemitraanLayout>
</template>
