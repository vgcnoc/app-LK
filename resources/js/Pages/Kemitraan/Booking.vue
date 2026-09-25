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
const draftBastData = ref({});

const formatIndoDate = (dateStr) => {
    if (!dateStr) return '-';
    if (dateStr.includes(' ')) return dateStr;
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr;
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`;
};

const openBastModal = (item) => {
    selectedBooking.value = item;
    
    let ymdDate = '';
    if (item.created_at) {
        ymdDate = item.created_at.split(' ')[0];
    } else {
        const today = new Date();
        ymdDate = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
    }

    let isPop = false;
    if (item.paket && item.paket.toUpperCase().includes('POP')) {
        isPop = true;
    }

    if (item.bast && item.bast.data) {
        draftBastData.value = { ...item.bast.data, nomor: item.bast.nomor };
        // Fix for previously saved data
        if (!draftBastData.value.sebutan_pihak2) {
            draftBastData.value.sebutan_pihak2 = isPop ? 'POP' : 'PELANGGAN/RESELLER';
        }
        if (isPop && draftBastData.value.pihak2_jabatan === 'DIREKTUR') {
            draftBastData.value.pihak2_jabatan = 'KOORDINATOR POP';
        }
    } else {
        draftBastData.value = {
            nomor: '00002/BAST/VGC/VII/2025',
            hari_tanggal: formattedTodayDate.value,
            lokasi: 'Jl Perintis Kemerdekaan No. 12 A Desa Sukamulya Kecamatan Cikembar Sukabumi Jawa Barat 43157',
            pihak1_nama: 'DERI GANTINAYASA',
            pihak1_jabatan: 'DIREKTUR',
            pihak1_perusahaan: 'PT VIRUZS GLOBAL CONNECTION',
            pihak1_alamat: 'Kp Cilandak RT 002 RW 002 Sirnajaya, Warungkiara, Sukabumi, Jawa Barat, Indonesia.',
            pihak2_nama: item.nama_pelanggan || '',
            pihak2_jabatan: isPop ? 'KOORDINATOR POP' : 'DIREKTUR',
            pihak2_perusahaan: item.nama_pelanggan || '',
            pihak2_alamat: item.alamat || '',
            sebutan_pihak2: isPop ? 'POP' : 'PELANGGAN/RESELLER',
            layanan_atas_nama: item.nama_pelanggan || '',
            layanan_jenis_pekerjaan: 'Instalasi & Aktivasi',
            layanan_jenis: item.paket ? (item.paket.includes('~') ? item.paket.split('~')[0].trim() : item.paket) : 'Internet Dedicated',
            layanan_kapasitas: item.paket || 'Internet Dedicated ~ 50 Mbps',
            layanan_lokasi_asal: '-',
            layanan_lokasi_tujuan: item.alamat || '',
            layanan_tanggal_booking: item.tanggal || '',
            layanan_tanggal_instalasi: ymdDate,
            layanan_tanggal_aktivasi: ymdDate,
            layanan_tanggal_aktif: ymdDate,
            penandatangan_nama_pihak2: item.nama_pelanggan || '..............................'
        };
    }
    showBastModal.value = true;
};

const isSaving = ref(false);
const saveAndPrintBast = () => {
    isSaving.value = true;
    router.post(route('kemitraan.booking.bast.store', selectedBooking.value.id), {
        data: draftBastData.value
    }, {
        preserveScroll: true,
        onSuccess: () => {
            isSaving.value = false;
            setTimeout(() => {
                window.print();
            }, 300);
        },
        onError: () => {
            isSaving.value = false;
            alert('Gagal menyimpan BAST');
        }
    });
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
                        <button @click="saveAndPrintBast" :disabled="isSaving" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors shadow-sm disabled:opacity-50">
                            <svg v-if="!isSaving" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            <svg v-else class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ isSaving ? 'Menyimpan...' : 'Simpan & Print' }}
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
                        <!-- Kop Surat (Letterhead) -->
                        <div class="flex items-center justify-between border-b-4 border-black pb-4 mb-8">
                            <div class="flex-shrink-0 w-24 h-24 bg-indigo-100 rounded-full flex items-center justify-center border-2 border-indigo-600 overflow-hidden print:border-black">
                                <span class="text-3xl font-black text-indigo-700 tracking-tighter italic print:text-black">VGC</span>
                            </div>
                            <div class="flex-1 text-center px-4">
                                <h1 class="text-2xl font-black uppercase text-indigo-900 tracking-wide print:text-black mb-1">PT VIRUZS GLOBAL CONNECTION</h1>
                                <p class="text-sm font-bold text-slate-800 print:text-black">Layanan Internet & Jaringan Telekomunikasi</p>
                                <p class="text-xs text-slate-600 mt-1 print:text-black">
                                    Jl Perintis Kemerdekaan No. 12 A Desa Sukamulya Kecamatan Cikembar<br>
                                    Sukabumi, Jawa Barat 43157
                                </p>
                                <p class="text-xs text-slate-600 print:text-black">
                                    Email: info@viruzs.my.id | Telp: (0266) 123456
                                </p>
                            </div>
                        </div>

                        <div class="text-center mb-6 font-bold flex flex-col items-center">
                            <p class="text-lg">BERITA ACARA SERAH TERIMA</p>
                            <input type="text" v-model="draftBastData.nomor" class="text-sm font-bold border-0 bg-transparent p-0 focus:ring-0 text-center w-full max-w-xs">
                        </div>
                        
                        <p class="mb-6 text-justify leading-relaxed">
                            Pada hari ini : <input type="text" v-model="draftBastData.hari_tanggal" class="border-b border-dashed border-slate-300 bg-transparent p-0 focus:border-indigo-500 focus:ring-0 text-sm font-medium w-64">, Bertempat di: <textarea v-model="draftBastData.lokasi" rows="1" class="border-b border-dashed border-slate-300 bg-transparent p-0 focus:border-indigo-500 focus:ring-0 text-sm font-medium w-full resize-none inline-block align-bottom"></textarea>telah diterbitkan Berita acara Serah Terima antara :
                        </p>

                        <p class="mb-2">Kami yang bertandatangan di bawah ini :</p>

                        <!-- Pihak 1 -->
                        <table class="w-full mb-2 border-collapse border border-black text-sm">
                            <tbody>
                                <tr>
                                    <td class="border border-black px-2 py-1 w-1/4">Nama</td>
                                    <td class="border border-black px-2 py-1 flex items-center">
                                        <span class="mr-1">:</span>
                                        <input type="text" v-model="draftBastData.pihak1_nama" class="border-0 bg-transparent p-0 focus:ring-0 text-sm uppercase flex-1">
                                    </td>
                                </tr>
                                <tr>
                                    <td class="border border-black px-2 py-1">Jabatan</td>
                                    <td class="border border-black px-2 py-1 flex items-center">
                                        <span class="mr-1">:</span>
                                        <input type="text" v-model="draftBastData.pihak1_jabatan" class="border-0 bg-transparent p-0 focus:ring-0 text-sm uppercase flex-1">
                                    </td>
                                </tr>
                                <tr>
                                    <td class="border border-black px-2 py-1">Perusahaan</td>
                                    <td class="border border-black px-2 py-1 flex items-center">
                                        <span class="mr-1">:</span>
                                        <input type="text" v-model="draftBastData.pihak1_perusahaan" class="border-0 bg-transparent p-0 focus:ring-0 text-sm uppercase flex-1">
                                    </td>
                                </tr>
                                <tr>
                                    <td class="border border-black px-2 py-1">Alamat</td>
                                    <td class="border border-black px-2 py-1 flex items-start">
                                        <span class="mr-1 mt-0.5">:</span>
                                        <textarea v-model="draftBastData.pihak1_alamat" rows="2" class="border-0 bg-transparent p-0 focus:ring-0 text-sm w-full resize-none"></textarea>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <p class="mb-6 font-bold">Selanjutnya disebut Penyedia Jasa Internet</p>

                        <!-- Pihak 2 -->
                        <table class="w-full mb-2 border-collapse border border-black text-sm">
                            <tbody>
                                <tr>
                                    <td class="border border-black px-2 py-1 w-1/4">Nama</td>
                                    <td class="border border-black px-2 py-1 flex items-center">
                                        <span class="mr-1">:</span>
                                        <input type="text" v-model="draftBastData.pihak2_nama" class="border-0 bg-transparent p-0 focus:ring-0 text-sm uppercase flex-1 font-bold">
                                    </td>
                                </tr>
                                <tr>
                                    <td class="border border-black px-2 py-1">Jabatan</td>
                                    <td class="border border-black px-2 py-1 flex items-center">
                                        <span class="mr-1">:</span>
                                        <input type="text" v-model="draftBastData.pihak2_jabatan" class="border-0 bg-transparent p-0 focus:ring-0 text-sm uppercase flex-1">
                                    </td>
                                </tr>
                                <tr>
                                    <td class="border border-black px-2 py-1">Perusahaan</td>
                                    <td class="border border-black px-2 py-1 flex items-center">
                                        <span class="mr-1">:</span>
                                        <input type="text" v-model="draftBastData.pihak2_perusahaan" class="border-0 bg-transparent p-0 focus:ring-0 text-sm uppercase flex-1 font-bold">
                                    </td>
                                </tr>
                                <tr>
                                    <td class="border border-black px-2 py-1">Alamat</td>
                                    <td class="border border-black px-2 py-1 flex items-start">
                                        <span class="mr-1 mt-0.5">:</span>
                                        <textarea v-model="draftBastData.pihak2_alamat" rows="2" class="border-0 bg-transparent p-0 focus:ring-0 text-sm uppercase w-full resize-none font-bold"></textarea>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <p class="mb-6 font-bold flex items-center">
                            Selanjutnya disebut <input type="text" v-model="draftBastData.sebutan_pihak2" class="ml-1 border-0 bg-transparent p-0 focus:ring-0 text-sm font-bold uppercase w-48">
                        </p>

                        <p class="mb-2">Menyatakan bahwa sebagai berikut :</p>

                        <!-- Detail Layanan -->
                        <table class="w-full mb-6 border-collapse border border-black text-sm">
                            <tbody>
                                <tr>
                                    <td class="border border-black px-2 py-1 w-1/3">Atas Nama Perusahaan</td>
                                    <td class="border border-black px-2 py-1 flex items-center">
                                        <span class="mr-1">:</span>
                                        <input type="text" v-model="draftBastData.layanan_atas_nama" class="border-0 bg-transparent p-0 focus:ring-0 text-sm uppercase flex-1 font-bold">
                                    </td>
                                </tr>
                                <tr>
                                    <td class="border border-black px-2 py-1">Jenis Pekerjaan</td>
                                    <td class="border border-black px-2 py-1 flex items-center">
                                        <span class="mr-1">:</span>
                                        <input type="text" v-model="draftBastData.layanan_jenis_pekerjaan" class="border-0 bg-transparent p-0 focus:ring-0 text-sm flex-1">
                                    </td>
                                </tr>
                                <tr>
                                    <td class="border border-black px-2 py-1">Jenis Layanan</td>
                                    <td class="border border-black px-2 py-1 flex items-center">
                                        <span class="mr-1">:</span>
                                        <input type="text" v-model="draftBastData.layanan_jenis" class="border-0 bg-transparent p-0 focus:ring-0 text-sm flex-1">
                                    </td>
                                </tr>
                                <tr>
                                    <td class="border border-black px-2 py-1">Kapasitas</td>
                                    <td class="border border-black px-2 py-1 flex items-center">
                                        <span class="mr-1">:</span>
                                        <input type="text" v-model="draftBastData.layanan_kapasitas" class="border-0 bg-transparent p-0 focus:ring-0 text-sm flex-1">
                                    </td>
                                </tr>
                                <tr>
                                    <td class="border border-black px-2 py-1">Lokasi Asal</td>
                                    <td class="border border-black px-2 py-1 flex items-center">
                                        <span class="mr-1">:</span>
                                        <input type="text" v-model="draftBastData.layanan_lokasi_asal" class="border-0 bg-transparent p-0 focus:ring-0 text-sm flex-1">
                                    </td>
                                </tr>
                                <tr>
                                    <td class="border border-black px-2 py-1">Lokasi Tujuan</td>
                                    <td class="border border-black px-2 py-1 flex items-center">
                                        <span class="mr-1">:</span>
                                        <input type="text" v-model="draftBastData.layanan_lokasi_tujuan" class="border-0 bg-transparent p-0 focus:ring-0 text-sm uppercase flex-1 font-bold">
                                    </td>
                                </tr>
                                <tr>
                                    <td class="border border-black px-2 py-1">Tanggal Booking</td>
                                    <td class="border border-black px-2 py-1 flex items-center bg-yellow-50/50 print:bg-transparent">
                                        <span class="mr-1">:</span>
                                        <input type="text" v-model="draftBastData.layanan_tanggal_booking" readonly class="border-0 bg-transparent p-0 focus:ring-0 text-sm flex-1 cursor-default">
                                    </td>
                                </tr>
                                <tr>
                                    <td class="border border-black px-2 py-1">Tanggal Instalasi</td>
                                    <td class="border border-black px-2 py-1 flex items-center">
                                        <span class="mr-1">:</span>
                                        <input type="date" v-model="draftBastData.layanan_tanggal_instalasi" class="print:hidden border-0 bg-transparent p-0 focus:ring-0 text-sm flex-1 cursor-pointer">
                                        <span class="hidden print:inline flex-1 text-sm">{{ formatIndoDate(draftBastData.layanan_tanggal_instalasi) }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="border border-black px-2 py-1">Tanggal Aktivasi</td>
                                    <td class="border border-black px-2 py-1 flex items-center">
                                        <span class="mr-1">:</span>
                                        <input type="date" v-model="draftBastData.layanan_tanggal_aktivasi" class="print:hidden border-0 bg-transparent p-0 focus:ring-0 text-sm flex-1 cursor-pointer">
                                        <span class="hidden print:inline flex-1 text-sm">{{ formatIndoDate(draftBastData.layanan_tanggal_aktivasi) }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="border border-black px-2 py-1">Tanggal Aktif Berlangganan</td>
                                    <td class="border border-black px-2 py-1 flex items-center">
                                        <span class="mr-1">:</span>
                                        <input type="date" v-model="draftBastData.layanan_tanggal_aktif" class="print:hidden border-0 bg-transparent p-0 focus:ring-0 text-sm flex-1 cursor-pointer">
                                        <span class="hidden print:inline flex-1 text-sm">{{ formatIndoDate(draftBastData.layanan_tanggal_aktif) }}</span>
                                    </td>
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
                                <p class="underline font-bold"><input type="text" v-model="draftBastData.pihak1_nama" class="border-0 bg-transparent text-center p-0 focus:ring-0 font-bold w-full"></p>
                                <p><input type="text" v-model="draftBastData.pihak1_jabatan" class="border-0 bg-transparent text-center p-0 focus:ring-0 w-full"></p>
                                <p><input type="text" v-model="draftBastData.pihak1_perusahaan" class="border-0 bg-transparent text-center p-0 focus:ring-0 w-full"></p>
                            </div>
                            <div class="w-1/2">
                                <p class="mb-24">Menyetujui</p>
                                <p class="underline font-bold uppercase"><input type="text" v-model="draftBastData.penandatangan_nama_pihak2" class="border-0 bg-transparent text-center p-0 focus:ring-0 font-bold uppercase w-full"></p>
                                <p><input type="text" v-model="draftBastData.pihak2_jabatan" class="border-0 bg-transparent text-center p-0 focus:ring-0 w-full"></p>
                                <p class="uppercase"><input type="text" v-model="draftBastData.pihak2_perusahaan" class="border-0 bg-transparent text-center p-0 focus:ring-0 uppercase w-full"></p>
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
