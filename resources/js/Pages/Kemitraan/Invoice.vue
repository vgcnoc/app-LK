<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import KemitraanLayout from '@/Layouts/KemitraanLayout.vue';

const props = defineProps({
    invoices: { type: Array, default: () => [] },
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
    nomor_invoice: '',
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
    form.nomor_invoice = inv.nomor_invoice;
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

                <!-- Table -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
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
                                            <div class="text-xs text-slate-400 font-normal mt-0.5 max-w-xs truncate">{{ inv.keterangan }}</div>
                                        </td>
                                        <td class="py-4 px-6 font-semibold text-slate-800">{{ formatRupiah(inv.nominal) }}</td>
                                        <td class="py-4 px-6 text-slate-500">{{ inv.tanggal_tagihan }}</td>
                                        <td class="py-4 px-6 text-rose-500 font-medium">{{ inv.jatuh_tempo }}</td>
                                        <td class="py-4 px-6 text-right">
                                            <span :class="[statusColor(inv.status), 'px-2.5 py-1 rounded-full text-xs font-semibold']">
                                                {{ inv.status }}
                                            </span>
                                        </td>
                                        <td class="py-4 px-6 text-center space-x-2">
                                            <button v-if="isAdmin" @click="openEditModal(inv)" class="px-3 py-1.5 text-xs font-medium text-indigo-600 bg-indigo-50 hover:bg-indigo-100 rounded-lg transition-colors">
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
                                    <label class="block text-sm font-medium text-slate-700">Nomor Invoice</label>
                                    <input type="text" v-model="form.nomor_invoice" required class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">Tgl Tagihan</label>
                                        <input type="date" v-model="form.tanggal_tagihan" required class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">Jatuh Tempo</label>
                                        <input type="date" v-model="form.jatuh_tempo" required class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
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
    </KemitraanLayout>
</template>
