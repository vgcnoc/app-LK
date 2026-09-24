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
</script>

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
                                    <th v-if="isAdmin" class="py-4 px-6 font-semibold text-center">Aksi</th>
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
                                        <td v-if="isAdmin" class="py-4 px-6 text-center space-x-2">
                                            <button @click="openPaymentModal(inv)" v-if="inv.status !== 'Lunas'" class="px-3 py-1.5 text-xs font-medium text-emerald-600 bg-emerald-50 hover:bg-emerald-100 rounded-lg transition-colors">
                                                Bayar
                                            </button>
                                            <button @click="openEditModal(inv)" class="px-3 py-1.5 text-xs font-medium text-indigo-600 bg-indigo-50 hover:bg-indigo-100 rounded-lg transition-colors">
                                                Edit
                                            </button>
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

    </KemitraanLayout>
</template>
