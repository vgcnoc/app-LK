<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import KemitraanLayout from '@/Layouts/KemitraanLayout.vue';

const props = defineProps({
    bookings: { type: Array, default: () => [] },
});

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

const showBastModal = ref(false);
const selectedBooking = ref(null);

const openBastModal = (item) => {
    selectedBooking.value = item;
    showBastModal.value = true;
};

const printBast = () => {
    window.print();
};

const deleteBooking = (id) => {
    if (confirm('Apakah Anda yakin ingin menghapus data booking ini?')) {
        router.delete(route('kemitraan.booking.destroy', id), {
            preserveScroll: true
        });
    }
};
</script>

<template>
    <Head title="Booking Mitra - Administrator" />

    <div class="print:hidden">
        <KemitraanLayout>
        <template #header>
            <div class="hidden sm:flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex-1 min-w-0">
                    <h2 class="text-xl sm:text-3xl font-bold text-slate-800 tracking-tight">
                        Booking Mitra (Kemitraan)
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">
                        Daftar calon mitra yang mendaftar melalui formulir kemitraan.
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
                            <h3 class="font-bold text-slate-800">Daftar Pendaftar Kemitraan</h3>
                            <p class="text-xs text-slate-500">{{ bookings.length }} mitra pendaftar</p>
                        </div>
                    </div>
                </div>

                <!-- Table/List Data -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-100 text-xs uppercase tracking-wider text-slate-500">
                                    <th class="px-6 py-4 font-semibold">Tanggal Daftar</th>
                                    <th class="px-6 py-4 font-semibold">Nama / Usaha</th>
                                    <th class="px-6 py-4 font-semibold">No. HP</th>
                                    <th class="px-6 py-4 font-semibold">Alamat</th>
                                    <th class="px-6 py-4 font-semibold text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="item in bookings" :key="item.id" class="hover:bg-slate-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2 text-sm text-slate-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            {{ item.tanggal }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <p class="text-sm font-bold text-slate-800">{{ item.nama_pelanggan }}</p>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <p class="text-sm text-slate-600">{{ item.no_hp || '-' }}</p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="text-sm text-slate-600 line-clamp-1 max-w-[200px]" :title="item.alamat">{{ item.alamat || '-' }}</p>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <button @click="openBastModal(item)" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-sky-50 text-sky-600 hover:bg-sky-100 rounded-lg text-xs font-semibold transition-colors">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                                Draft BAST
                                            </button>
                                            
                                            <!-- Tautan untuk melihat detail profil (Data Saya) Mitra ini -->
                                            <Link :href="route('kemitraan.booking.datasaya', { id: item.user_id })" 
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 text-indigo-600 hover:bg-indigo-100 rounded-lg text-xs font-semibold transition-colors">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                                Profil
                                            </Link>
                                            
                                            <button @click="deleteBooking(item.id)" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-50 text-red-600 hover:bg-red-100 rounded-lg text-xs font-semibold transition-colors">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                Hapus
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="bookings.length === 0">
                                    <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                                        Belum ada pendaftaran mitra kemitraan.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
        </KemitraanLayout>
    </div>

    <!-- Modal Draft BAST -->
    <div v-if="showBastModal" class="fixed inset-0 z-50 overflow-y-auto print:absolute print:inset-0 print:z-auto print:overflow-visible print:bg-white" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0 print:min-h-0 print:pt-0 print:px-0 print:pb-0">
            <div class="fixed inset-0 bg-slate-900 bg-opacity-75 transition-opacity print:hidden" @click="showBastModal = false"></div>
            
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen print:hidden" aria-hidden="true">&#8203;</span>
            
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle w-full max-w-4xl print:shadow-none print:w-full print:max-w-full print:rounded-none print:my-0">
                <!-- Header Actions (Hidden on print) -->
                <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex justify-between items-center print:hidden">
                    <h3 class="text-lg font-bold text-slate-800">Draft BAST</h3>
                    <div class="flex gap-2">
                        <button @click="printBast" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            Print
                        </button>
                        <button @click="showBastModal = false" class="text-slate-400 hover:text-slate-600 p-2 rounded-lg bg-white border border-slate-200 shadow-sm transition-colors">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Printable Area -->
                <div class="p-8 sm:p-12 print:p-0 bg-white" style="font-family: 'Times New Roman', Times, serif; color: black; line-height: 1.5; font-size: 14px;">
                    <!-- Watermark for Print -->
                    <div class="hidden print:flex absolute inset-0 z-0 items-center justify-center opacity-[0.05] pointer-events-none select-none overflow-hidden">
                        <div class="text-[120px] font-extrabold uppercase rotate-[-30deg] tracking-widest whitespace-nowrap">VIRUZS</div>
                    </div>

                    <div class="relative z-10">
                        <div class="text-center mb-6 font-bold">
                            <p class="text-lg">BERITA ACARA SERAH TERIMA</p>
                            <p class="text-sm">00002/BAST/VGC/VII/2025</p>
                        </div>
                        
                        <p class="mb-6 text-justify">
                            Pada hari ini : {{ formattedTodayDate }}, Bertempat di: Jl Perintis Kemerdekaan No. 12 A Desa Sukamulya Kecamatan Cikembar Sukabumi Jawa Barat 43157, telah diterbitkan Berita acara Serah Terima antara :
                        </p>

                        <p class="mb-2">Kami yang bertandatangan di bawah ini :</p>

                        <!-- Pihak 1 -->
                        <table class="w-full mb-2 border-collapse border border-black text-sm">
                            <tbody>
                                <tr>
                                    <td class="border border-black px-2 py-1 w-1/4">Nama</td>
                                    <td class="border border-black px-2 py-1">: DERI GANTINAYASA</td>
                                </tr>
                                <tr>
                                    <td class="border border-black px-2 py-1">Jabatan</td>
                                    <td class="border border-black px-2 py-1">: DIREKTUR</td>
                                </tr>
                                <tr>
                                    <td class="border border-black px-2 py-1">Perusahaan</td>
                                    <td class="border border-black px-2 py-1">: PT VIRUZS GLOBAL CONNECTION</td>
                                </tr>
                                <tr>
                                    <td class="border border-black px-2 py-1">Alamat</td>
                                    <td class="border border-black px-2 py-1">: Kp Cilandak RT 002 RW 002 Sirnajaya, Warungkiara, Sukabumi, Jawa Barat, Indonesia.</td>
                                </tr>
                            </tbody>
                        </table>
                        <p class="mb-6 font-bold">Selanjutnya disebut Penyedia Jasa Internet</p>

                        <!-- Pihak 2 -->
                        <table class="w-full mb-2 border-collapse border border-black text-sm">
                            <tbody>
                                <tr>
                                    <td class="border border-black px-2 py-1 w-1/4">Nama</td>
                                    <td class="border border-black px-2 py-1 uppercase">: {{ selectedBooking?.nama_pelanggan || '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="border border-black px-2 py-1">Jabatan</td>
                                    <td class="border border-black px-2 py-1">: DIREKTUR</td>
                                </tr>
                                <tr>
                                    <td class="border border-black px-2 py-1">Perusahaan</td>
                                    <td class="border border-black px-2 py-1 uppercase">: {{ selectedBooking?.nama_pelanggan || '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="border border-black px-2 py-1">Alamat</td>
                                    <td class="border border-black px-2 py-1 uppercase">: {{ selectedBooking?.alamat || '-' }}</td>
                                </tr>
                            </tbody>
                        </table>
                        <p class="mb-6 font-bold">Selanjutnya disebut PELANGGAN/RESELLER</p>

                        <p class="mb-2">Menyatakan bahwa sebagai berikut :</p>

                        <!-- Detail Layanan -->
                        <table class="w-full mb-6 border-collapse border border-black text-sm">
                            <tbody>
                                <tr>
                                    <td class="border border-black px-2 py-1 w-1/3">Atas Nama Perusahaan</td>
                                    <td class="border border-black px-2 py-1 uppercase">: {{ selectedBooking?.nama_pelanggan || '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="border border-black px-2 py-1">Jenis Pekerjaan</td>
                                    <td class="border border-black px-2 py-1">: Instalasi & Aktivasi</td>
                                </tr>
                                <tr>
                                    <td class="border border-black px-2 py-1">Jenis Layanan</td>
                                    <td class="border border-black px-2 py-1">: Internet Dedicated</td>
                                </tr>
                                <tr>
                                    <td class="border border-black px-2 py-1">Kapasitas</td>
                                    <td class="border border-black px-2 py-1">: Internet Dedicated ~ 50 Mbps</td>
                                </tr>
                                <tr>
                                    <td class="border border-black px-2 py-1">Lokasi Asal</td>
                                    <td class="border border-black px-2 py-1">: -</td>
                                </tr>
                                <tr>
                                    <td class="border border-black px-2 py-1">Lokasi Tujuan</td>
                                    <td class="border border-black px-2 py-1 uppercase">: {{ selectedBooking?.alamat || '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="border border-black px-2 py-1">Tanggal Instalasi</td>
                                    <td class="border border-black px-2 py-1">: {{ selectedBooking?.tanggal || '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="border border-black px-2 py-1">Tanggal Aktivasi</td>
                                    <td class="border border-black px-2 py-1">: {{ selectedBooking?.tanggal || '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="border border-black px-2 py-1">Tanggal Aktif Berlangganan</td>
                                    <td class="border border-black px-2 py-1">: {{ selectedBooking?.tanggal || '-' }}</td>
                                </tr>
                            </tbody>
                        </table>

                        <p class="mb-2 text-justify">
                            Telah selesai dilakukan instalasi dan aktivasi serta di uji dengan hasil baik, begitupun dinyatakan SIAP DIGUNAKAN DIOPERASIKAN, terhitung sejak tanggal aktif berlangganan.
                        </p>
                        <p class="mb-8 text-justify">
                            Demikian berita acara ini dibuat dengan sebenar-benarnya dan sesuai dengan keadaan di lapangan agar dapat digunakan sebagaimana mestinya.
                        </p>

                        <!-- Signatures -->
                        <div class="flex justify-between w-full mb-8 text-center text-sm">
                            <div class="w-1/2">
                                <p class="mb-24">Hormat Kami,</p>
                                <p class="underline font-bold">Deri Gantinayasa</p>
                                <p>Direktur</p>
                                <p>PT Viruzs Global Connection</p>
                            </div>
                            <div class="w-1/2">
                                <p class="mb-24">Menyetujui</p>
                                <p class="underline font-bold uppercase">{{ selectedBooking?.nama_pelanggan || '..............................' }}</p>
                                <p>Direktur</p>
                                <p class="uppercase">{{ selectedBooking?.nama_pelanggan || '..............................' }}</p>
                            </div>
                        </div>

                        <p class="text-xs font-bold">
                            *Disclaimer : Apabila selama 5 hari kerja BAST tidak di tandatangani maka kami anggap setuju.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
