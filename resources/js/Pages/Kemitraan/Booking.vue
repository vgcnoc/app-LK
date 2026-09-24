<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import KemitraanLayout from '@/Layouts/KemitraanLayout.vue';

const props = defineProps({
    bookings: { type: Array, default: () => [] },
});

const showForm = ref(false);

const form = useForm({
    nama_pelanggan: '',
    alamat: '',
    no_hp: '',
    paket: '',
    catatan: '',
});

const paketOptions = [
    '10 Mbps', '20 Mbps', '30 Mbps', '50 Mbps', '100 Mbps',
];

const submit = () => {
    form.post(route('kemitraan.booking.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            showForm.value = false;
        },
    });
};

const statusColor = (status) => {
    const map = {
        'Pending': 'bg-amber-100 text-amber-700',
        'Diproses': 'bg-blue-100 text-blue-700',
        'Selesai': 'bg-emerald-100 text-emerald-700',
        'Ditolak': 'bg-red-100 text-red-700',
    };
    return map[status] || 'bg-slate-100 text-slate-700';
};

const formattedTodayDate = computed(() => {
    return new Intl.DateTimeFormat('id-ID', {
        weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
    }).format(new Date());
});
</script>

<template>
    <Head title="Booking - Kemitraan" />

    <KemitraanLayout>
        <template #header>
            <div class="hidden sm:flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex-1 min-w-0">
                    <h2 class="text-xl sm:text-3xl font-bold text-slate-800 tracking-tight">
                        Booking Pemasangan
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">
                        Ajukan permintaan pemasangan baru untuk pelanggan Anda.
                    </p>
                </div>
                <div class="flex items-center gap-2 bg-white border border-slate-200 px-4 py-2.5 rounded-xl shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span class="text-xs sm:text-sm font-medium text-slate-700">{{ formattedTodayDate }}</span>
                </div>
            </div>
        </template>

        <div class="py-4 sm:py-8 bg-slate-50 min-h-screen">
            <div class="max-w-full mx-auto space-y-6">

                <!-- Action Bar -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="bg-indigo-50 text-indigo-600 p-2.5 rounded-xl">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-800">Daftar Booking</h3>
                            <p class="text-xs text-slate-500">{{ bookings.length }} total booking</p>
                        </div>
                    </div>
                    <button @click="showForm = !showForm"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-sm transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Booking Baru
                    </button>
                </div>

                <!-- Form Booking Baru -->
                <transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 -translate-y-2">
                    <div v-if="showForm" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                        <h3 class="text-lg font-bold text-slate-800 mb-6">Form Booking Pemasangan Baru</h3>
                        <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">Nama Pelanggan</label>
                                <input v-model="form.nama_pelanggan" type="text" required
                                    class="w-full px-4 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 transition-all"
                                    placeholder="Masukkan nama pelanggan" />
                                <p v-if="form.errors.nama_pelanggan" class="text-xs text-red-500 mt-1">{{ form.errors.nama_pelanggan }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">No. HP / WhatsApp</label>
                                <input v-model="form.no_hp" type="text" required
                                    class="w-full px-4 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 transition-all"
                                    placeholder="08xxxxxxxxxx" />
                                <p v-if="form.errors.no_hp" class="text-xs text-red-500 mt-1">{{ form.errors.no_hp }}</p>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">Alamat Pemasangan</label>
                                <textarea v-model="form.alamat" rows="3" required
                                    class="w-full px-4 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 transition-all resize-none"
                                    placeholder="Alamat lengkap pemasangan"></textarea>
                                <p v-if="form.errors.alamat" class="text-xs text-red-500 mt-1">{{ form.errors.alamat }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">Paket Internet</label>
                                <select v-model="form.paket" required
                                    class="w-full px-4 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 transition-all bg-white">
                                    <option value="">Pilih paket</option>
                                    <option v-for="p in paketOptions" :key="p" :value="p">{{ p }}</option>
                                </select>
                                <p v-if="form.errors.paket" class="text-xs text-red-500 mt-1">{{ form.errors.paket }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">Catatan (Opsional)</label>
                                <input v-model="form.catatan" type="text"
                                    class="w-full px-4 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 transition-all"
                                    placeholder="Catatan tambahan" />
                            </div>
                            <div class="md:col-span-2 flex justify-end gap-3 pt-2">
                                <button type="button" @click="showForm = false"
                                    class="px-5 py-2.5 text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">
                                    Batal
                                </button>
                                <button type="submit" :disabled="form.processing"
                                    class="px-6 py-2.5 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-sm transition-all disabled:opacity-50">
                                    <span v-if="form.processing">Mengirim...</span>
                                    <span v-else>Kirim Booking</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </transition>

                <!-- Table Booking -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-slate-600">
                            <thead class="text-xs uppercase text-slate-400 border-b border-slate-100 bg-slate-50/50">
                                <tr>
                                    <th class="py-4 px-6 font-semibold">No</th>
                                    <th class="py-4 px-6 font-semibold">Pelanggan</th>
                                    <th class="py-4 px-6 font-semibold">Alamat</th>
                                    <th class="py-4 px-6 font-semibold">Paket</th>
                                    <th class="py-4 px-6 font-semibold">Tanggal</th>
                                    <th class="py-4 px-6 font-semibold text-right">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <template v-if="bookings.length > 0">
                                    <tr v-for="(booking, index) in bookings" :key="booking.id" class="hover:bg-slate-50 transition-colors">
                                        <td class="py-4 px-6 font-medium text-slate-700">{{ index + 1 }}</td>
                                        <td class="py-4 px-6">
                                            <div>
                                                <p class="font-medium text-slate-800">{{ booking.nama_pelanggan }}</p>
                                                <p class="text-xs text-slate-400 mt-0.5">{{ booking.no_hp }}</p>
                                            </div>
                                        </td>
                                        <td class="py-4 px-6 max-w-xs truncate">{{ booking.alamat }}</td>
                                        <td class="py-4 px-6">
                                            <span class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-indigo-50 text-indigo-700">{{ booking.paket }}</span>
                                        </td>
                                        <td class="py-4 px-6 text-slate-500">{{ booking.tanggal }}</td>
                                        <td class="py-4 px-6 text-right">
                                            <span :class="[statusColor(booking.status), 'px-2.5 py-1 rounded-full text-xs font-semibold']">
                                                {{ booking.status }}
                                            </span>
                                        </td>
                                    </tr>
                                </template>
                                <tr v-else>
                                    <td colspan="6" class="py-16 text-center">
                                        <div class="flex flex-col items-center gap-3">
                                            <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                            <p class="text-sm text-slate-500">Belum ada data booking</p>
                                            <p class="text-xs text-slate-400">Klik "Booking Baru" untuk menambah booking pertama</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </KemitraanLayout>
</template>
