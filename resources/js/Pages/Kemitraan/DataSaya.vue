<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import KemitraanLayout from '@/Layouts/KemitraanLayout.vue';

const page = usePage();
const user = computed(() => page.props.auth?.user || {});
const isAdmin = computed(() => user.value.role !== 'kemitraan');

const props = defineProps({
    profile: { type: Object, default: () => ({}) },
    targetUser: { type: Object, default: () => ({}) },
    isAdminViewingMitra: { type: Boolean, default: false },
});

const editing = ref(false);

const displayUser = computed(() => props.targetUser?.id ? props.targetUser : user.value);

const form = useForm({
    _method: 'post',
    name: displayUser.value.name || '',
    email: displayUser.value.email || '',
    
    // Admin Only Edit Fields
    status_akun: props.profile.status_akun || 'Pending',
    tipe_kemitraan: props.profile.tipe_kemitraan || 'Reseller ISP',
    metro: props.profile.metro || 'Belum ada metro',
    bandwidth: props.profile.bandwidth || '100 Mbps',
    
    // Data Mitra
    nik: props.profile.nik || '',
    no_wa: props.profile.no_wa || '',
    alamat: props.profile.alamat || '',
    kota: props.profile.kota || '',
    provinsi: props.profile.provinsi || '',

    // Data Usaha
    nama_usaha: props.profile.nama_usaha || '',
    jenis_usaha: props.profile.jenis_usaha || '',
    nib: props.profile.nib || '',
    npwp: props.profile.npwp || '',

    // Area Kemitraan
    area_dikelola: props.profile.area_dikelola || '',
    kecamatan: props.profile.kecamatan || '',
    pin_maps: props.profile.pin_maps || '',
    estimasi_pelanggan: props.profile.estimasi_pelanggan || '',

    // Data Teknis
    pic_teknisi: props.profile.pic_teknisi || '',
    wa_teknisi: props.profile.wa_teknisi || '',
    ukuran_seragam: props.profile.ukuran_seragam || '',
    pengalaman_infrastruktur: props.profile.pengalaman_infrastruktur || '',
    tim_teknisi: (() => {
        if (!props.profile.tim_teknisi) return [];
        try {
            return typeof props.profile.tim_teknisi === 'string' 
                ? JSON.parse(props.profile.tim_teknisi) 
                : props.profile.tim_teknisi;
        } catch(e) {
            return [];
        }
    })(),

    // File uploads
    file_ktp: null,
    file_nib: null,
    file_npwp: null,
    file_lokasi: null,
    foto: null,
    foto_tambahan: (() => {
        if (!props.profile.foto_tambahan) return [];
        try {
            return typeof props.profile.foto_tambahan === 'string' 
                ? JSON.parse(props.profile.foto_tambahan) 
                : props.profile.foto_tambahan;
        } catch(e) {
            return [];
        }
    })(),
});

const addTimTeknisi = () => {
    form.tim_teknisi.push({
        nama: '',
        wa: '',
        ukuran_seragam: ''
    });
};

const removeTimTeknisi = (index) => {
    form.tim_teknisi.splice(index, 1);
};

const handleFileUpload = (e, field) => {
    form[field] = e.target.files[0];
};

const addFotoTambahan = () => {
    form.foto_tambahan.push({
        nama: '',
        file: null,
        path: null
    });
};

const removeFotoTambahan = (index) => {
    form.foto_tambahan.splice(index, 1);
};

const handleFotoTambahanUpload = (e, index) => {
    form.foto_tambahan[index].file = e.target.files[0];
};

const submit = () => {
    const routeUrl = props.isAdminViewingMitra 
        ? route('kemitraan.datasaya.update', { id: props.targetUser.id }) 
        : route('kemitraan.datasaya.update');

    form.post(routeUrl, {
        preserveScroll: true,
        onSuccess: () => {
            editing.value = false;
        },
    });
};

const formattedTodayDate = computed(() => {
    return new Intl.DateTimeFormat('id-ID', {
        weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
    }).format(new Date());
});

const nomorSurat = computed(() => {
    const date = displayUser.value.created_at ? new Date(displayUser.value.created_at) : new Date();
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const year = date.getFullYear();
    const id = displayUser.value.id ? displayUser.value.id.toString().padStart(4, '0') : '0000';
    return `REG/VGC/${year}/${month}/${id}`;
});

const printPage = () => {
    window.print();
};

const statusHistory = computed(() => {
    try {
        return props.profile.status_history ? JSON.parse(props.profile.status_history) : {};
    } catch(e) {
        return {};
    }
});

const statusSteps = [
    'Pending',
    'Pembayaran Registrasi',
    'Survey Metro',
    'Instalasi',
    'Aktivasi',
    'Aktif'
];

const currentStepIndex = computed(() => {
    let status = props.profile.status_akun || 'Pending';
    if (status === 'Survey' || status === 'Metro') {
        status = 'Survey Metro';
    }
    return statusSteps.indexOf(status);
});

const getStatusDate = (stepName) => {
    let dateStr = statusHistory.value[stepName];
    if (!dateStr && stepName === 'Pending') {
        dateStr = props.profile.created_at;
    }
    if (!dateStr) return null;
    return new Intl.DateTimeFormat('id-ID', {
        year: 'numeric', month: 'short', day: 'numeric'
    }).format(new Date(dateStr));
};
</script>

<template>
    <Head title="Data Saya - Kemitraan" />

    <KemitraanLayout>
        <template #header>
            <div class="hidden sm:flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex-1 min-w-0 print:hidden">
                    <h2 class="text-xl sm:text-3xl font-bold text-slate-800 tracking-tight">
                        Data Saya
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">
                        Informasi profil dan data kemitraan Anda.
                    </p>
                </div>
                <!-- Printable Header Title (only visible on print) -->
                <div class="hidden print:block mb-4 text-center w-full border-b pb-4">
                    <h2 class="text-2xl font-bold text-slate-800 uppercase tracking-widest">
                        Data Booking / Kemitraan
                    </h2>
                    <p class="text-sm text-slate-500 mt-1">
                        Dicetak pada: {{ formattedTodayDate }}
                    </p>
                </div>
                <div class="flex items-center gap-2 bg-white border border-slate-200 px-4 py-2.5 rounded-xl shadow-sm print:hidden">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span class="text-xs sm:text-sm font-medium text-slate-700">{{ formattedTodayDate }}</span>
                </div>
                
                <button @click="printPage" class="print:hidden flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-xl shadow-sm transition-colors text-sm font-medium">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    Cetak Formulir
                </button>
            </div>
        </template>

        <div class="py-4 sm:py-8 bg-slate-50 min-h-screen">
            <div class="max-w-full mx-auto space-y-6 print:hidden">

                <!-- Visual Progress Status -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 print:hidden">
                    <h3 class="text-lg font-bold text-slate-800 mb-6">Status Pendaftaran Kemitraan</h3>
                    
                    <div v-if="profile.status_akun === 'Nonaktif'" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl flex items-start gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <div>
                            <p class="font-bold">Akun Nonaktif</p>
                            <p class="text-sm mt-1">Akun kemitraan Anda saat ini sedang dinonaktifkan. Silakan hubungi admin untuk informasi lebih lanjut.</p>
                        </div>
                    </div>

                    <div v-else class="relative mt-8">
                        <!-- Connecting Line -->
                        <div class="hidden md:block absolute top-[24px] left-[5%] w-[90%] h-1 bg-slate-200 -translate-y-1/2 rounded-full overflow-hidden">
                            <div class="absolute inset-0 unprocessed-line opacity-80"></div>
                            <div class="relative h-full bg-indigo-600 transition-all duration-700 ease-in-out z-10"
                                :style="{ width: (currentStepIndex / (statusSteps.length - 1)) * 100 + '%' }">
                            </div>
                        </div>

                        <!-- Steps -->
                        <div class="relative z-10 flex flex-col md:flex-row justify-between gap-6 md:gap-0">
                            
                            <!-- Dynamic Steps -->
                            <div v-for="(step, index) in statusSteps" :key="index" class="flex md:flex-col items-center md:items-center gap-4 md:gap-2 flex-1 md:text-center">
                                <div class="w-12 h-12 rounded-full flex items-center justify-center font-bold text-lg border-4 shadow-sm transition-all duration-500 z-10"
                                    :class="currentStepIndex > index ? 'bg-emerald-500 border-emerald-500 text-white shadow-emerald-200' : 
                                            currentStepIndex === index ? 'bg-indigo-600 border-indigo-600 text-white shadow-indigo-200 scale-110' : 
                                            'bg-white border-slate-200 text-slate-400'">
                                    <svg v-if="currentStepIndex > index" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span v-else>{{ index + 1 }}</span>
                                </div>
                                <div class="md:mt-2 text-left md:text-center">
                                    <p class="font-bold text-sm leading-tight" :class="currentStepIndex >= index ? 'text-slate-800' : 'text-slate-400'">{{ step }}</p>
                                    <p v-if="getStatusDate(step)" class="text-[10px] text-slate-500 mt-1 font-medium bg-slate-100 px-2 py-0.5 rounded-full inline-block">{{ getStatusDate(step) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Info Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="bg-emerald-50 text-emerald-600 p-2 rounded-xl">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h4 class="font-semibold text-slate-800">Status Akun</h4>
                        </div>
                        <span v-if="!isAdmin" class="px-3 py-1.5 rounded-full text-xs font-semibold" 
                            :class="{
                                'bg-emerald-100 text-emerald-700': profile.status_akun === 'Aktif',
                                'bg-red-100 text-red-700': profile.status_akun === 'Nonaktif',
                                'bg-amber-100 text-amber-700': profile.status_akun === 'Pending' || !profile.status_akun,
                                'bg-blue-100 text-blue-700': !['Aktif', 'Nonaktif', 'Pending'].includes(profile.status_akun)
                            }">{{ profile.status_akun || 'Pending' }}</span>
                        <div v-else>
                            <select v-model="form.status_akun" @change="submit" class="w-full px-3 py-1.5 text-sm border border-slate-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 font-semibold text-slate-700">
                                <option value="Pending">Pending</option>
                                <option value="Pembayaran Registrasi">Pembayaran Registrasi</option>
                                <option value="Survey Metro">Survey Metro</option>
                                <option value="Instalasi">Instalasi</option>
                                <option value="Aktivasi">Aktivasi</option>
                                <option value="Aktif">Aktif</option>
                                <option value="Nonaktif">Non-aktif</option>
                            </select>
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="bg-indigo-50 text-indigo-600 p-2 rounded-xl">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <h4 class="font-semibold text-slate-800">Terdaftar Sejak</h4>
                        </div>
                        <p class="text-sm text-slate-600">{{ displayUser.created_at ? new Date(displayUser.created_at).toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' }) : '-' }}</p>
                    </div>
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="bg-purple-50 text-purple-600 p-2 rounded-xl">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <h4 class="font-semibold text-slate-800">Tipe Kemitraan</h4>
                        </div>
                        <p v-if="!isAdmin" class="text-sm text-slate-600">{{ profile.tipe_kemitraan || 'Reseller ISP' }}</p>
                        <div v-else>
                            <input v-model="form.tipe_kemitraan" @blur="submit" @keyup.enter="submit" type="text" class="w-full px-3 py-1.5 text-sm border border-slate-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 font-semibold text-slate-700" />
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="bg-orange-50 text-orange-600 p-2 rounded-xl">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <h4 class="font-semibold text-slate-800">Metro</h4>
                        </div>
                        <p v-if="!isAdmin" class="text-sm font-semibold text-slate-700">{{ profile.metro || 'Belum ada metro' }}</p>
                        <div v-else>
                            <select v-model="form.metro" @change="submit" class="w-full px-3 py-1.5 text-sm border border-slate-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 font-semibold text-slate-700">
                                <option value="Belum ada metro">Belum ada metro</option>
                                <option value="Indosat">Indosat</option>
                                <option value="Iforte">Iforte</option>
                                <option value="Fiberstar">Fiberstar</option>
                                <option value="Asnet">Asnet</option>
                                <option value="Lintas Arta">Lintas Arta</option>
                                <option value="MAP">MAP</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Bandwidth Card -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 md:col-span-1">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="bg-blue-50 text-blue-600 p-2 rounded-xl">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                                </svg>
                            </div>
                            <h4 class="font-semibold text-slate-800">Bandwidth</h4>
                        </div>
                        <p v-if="!isAdmin" class="text-sm font-semibold text-slate-700">{{ profile.bandwidth || '100 Mbps' }}</p>
                        <div v-else>
                            <select v-model="form.bandwidth" @change="submit" class="w-full px-3 py-1.5 text-sm border border-slate-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 font-semibold text-slate-700">
                                <option value="100 Mbps">100 Mbps</option>
                                <option value="200 Mbps">200 Mbps</option>
                                <option value="300 Mbps">300 Mbps</option>
                                <option value="500 Mbps">500 Mbps</option>
                                <option value="1 Gbps">1 Gbps</option>
                                <option value="1,5 Gbps">1,5 Gbps</option>
                                <option value="2 Gbps">2 Gbps</option>
                                <option value="3 Gbps">3 Gbps</option>
                                <option value="4 Gbps">4 Gbps</option>
                                <option value="5 Gbps">5 Gbps</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Profile Header (Foto + Nama) -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-6">
                    <div class="px-6 py-8 flex flex-col md:flex-row items-center gap-6">
                        <div class="relative group">
                            <img v-if="profile.foto" :src="'/storage/' + profile.foto" class="h-24 w-24 rounded-full object-cover border-4 border-indigo-50 shadow-sm">
                            <div v-else class="h-24 w-24 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-3xl border-4 border-indigo-50 shadow-sm">
                                {{ displayUser.name.charAt(0).toUpperCase() }}
                            </div>
                            <label v-if="editing" class="absolute bottom-0 right-0 bg-indigo-600 text-white p-1.5 rounded-full cursor-pointer hover:bg-indigo-700 shadow-md transition-colors" title="Upload Foto">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <input type="file" class="hidden" @change="e => handleFileUpload(e, 'foto')" accept="image/*">
                            </label>
                        </div>
                        <div class="text-center md:text-left">
                            <h2 class="text-2xl font-bold text-slate-800 flex items-center gap-2 justify-center md:justify-start">
                                {{ displayUser.name }}
                                <svg v-if="profile.status_akun === 'Aktif'" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-500" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            </h2>
                            <p class="text-slate-500 font-medium">{{ profile.tipe_kemitraan }}</p>
                        </div>
                    </div>
                </div>

                <!-- Profile Card -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                        <h3 class="font-bold text-slate-800 text-lg">Detail Data Mitra</h3>
                        <div v-if="!editing" class="flex gap-2 print:hidden">
                            <button @click="printPage"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-semibold rounded-lg shadow-sm transition-all">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                </svg>
                                Cetak
                            </button>
                            <button @click="editing = true"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-all">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                Edit Profil
                            </button>
                        </div>
                    </div>

                    <div class="p-6">
                        <!-- View Mode -->
                        <div v-if="!editing" class="space-y-8">
                            <!-- Section: Data Mitra -->
                            <div>
                                <h4 class="text-sm font-bold text-indigo-700 uppercase tracking-wider mb-4 pb-2 border-b border-indigo-100">Data Mitra</h4>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div><label class="text-xs font-medium text-slate-400 block mb-1">Nama Lengkap / Nama Usaha</label><p class="text-sm font-medium text-slate-800">{{ displayUser.name || '-' }}</p></div>
                                    <div><label class="text-xs font-medium text-slate-400 block mb-1">NIK</label><p class="text-sm font-medium text-slate-800">{{ profile.nik || '-' }}</p></div>
                                    <div><label class="text-xs font-medium text-slate-400 block mb-1">No. WhatsApp</label><p class="text-sm font-medium text-slate-800">{{ profile.no_wa || '-' }}</p></div>
                                    <div><label class="text-xs font-medium text-slate-400 block mb-1">Email</label><p class="text-sm font-medium text-slate-800">{{ displayUser.email || '-' }}</p></div>
                                    <div><label class="text-xs font-medium text-slate-400 block mb-1">Kota/Kabupaten</label><p class="text-sm font-medium text-slate-800">{{ profile.kota || '-' }}</p></div>
                                    <div><label class="text-xs font-medium text-slate-400 block mb-1">Provinsi</label><p class="text-sm font-medium text-slate-800">{{ profile.provinsi || '-' }}</p></div>
                                    <div class="md:col-span-3"><label class="text-xs font-medium text-slate-400 block mb-1">Alamat Lengkap</label><p class="text-sm font-medium text-slate-800">{{ profile.alamat || '-' }}</p></div>
                                </div>
                            </div>

                            <!-- Section: Data Usaha -->
                            <div>
                                <h4 class="text-sm font-bold text-indigo-700 uppercase tracking-wider mb-4 pb-2 border-b border-indigo-100">Data Usaha</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div><label class="text-xs font-medium text-slate-400 block mb-1">Nama Usaha</label><p class="text-sm font-medium text-slate-800">{{ profile.nama_usaha || '-' }}</p></div>
                                    <div><label class="text-xs font-medium text-slate-400 block mb-1">Jenis Usaha</label><p class="text-sm font-medium text-slate-800">{{ profile.jenis_usaha || '-' }}</p></div>
                                    <div><label class="text-xs font-medium text-slate-400 block mb-1">NIB (Jika ada)</label><p class="text-sm font-medium text-slate-800">{{ profile.nib || '-' }}</p></div>
                                    <div><label class="text-xs font-medium text-slate-400 block mb-1">NPWP (Jika ada)</label><p class="text-sm font-medium text-slate-800">{{ profile.npwp || '-' }}</p></div>
                                </div>
                            </div>

                            <!-- Section: Area Kemitraan -->
                            <div>
                                <h4 class="text-sm font-bold text-indigo-700 uppercase tracking-wider mb-4 pb-2 border-b border-indigo-100">Area Kemitraan</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div><label class="text-xs font-medium text-slate-400 block mb-1">Area/Wilayah yang dikelola</label><p class="text-sm font-medium text-slate-800">{{ profile.area_dikelola || '-' }}</p></div>
                                    <div><label class="text-xs font-medium text-slate-400 block mb-1">Kecamatan/Kelurahan</label><p class="text-sm font-medium text-slate-800">{{ profile.kecamatan || '-' }}</p></div>
                                    <div><label class="text-xs font-medium text-slate-400 block mb-1">Lokasi/Pin Maps</label>
                                        <p class="text-sm font-medium text-slate-800">
                                            <a v-if="profile.pin_maps" :href="profile.pin_maps" target="_blank" class="text-indigo-600 hover:underline">Buka Maps</a>
                                            <span v-else>-</span>
                                        </p>
                                    </div>
                                    <div><label class="text-xs font-medium text-slate-400 block mb-1">Perkiraan Jumlah Calon Pelanggan</label><p class="text-sm font-medium text-slate-800">{{ profile.estimasi_pelanggan || '-' }}</p></div>
                                </div>
                            </div>

                            <!-- Section: Data Teknis -->
                            <div>
                                <h4 class="text-sm font-bold text-indigo-700 uppercase tracking-wider mb-4 pb-2 border-b border-indigo-100">Data Teknis</h4>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div><label class="text-xs font-medium text-slate-400 block mb-1">Nama PIC Teknisi</label><p class="text-sm font-medium text-slate-800">{{ profile.pic_teknisi || '-' }}</p></div>
                                    <div><label class="text-xs font-medium text-slate-400 block mb-1">No. WhatsApp Teknisi</label><p class="text-sm font-medium text-slate-800">{{ profile.wa_teknisi || '-' }}</p></div>
                                    <div><label class="text-xs font-medium text-slate-400 block mb-1">Ukuran Seragam</label><p class="text-sm font-medium text-slate-800">{{ profile.ukuran_seragam || '-' }}</p></div>
                                    <div class="md:col-span-3"><label class="text-xs font-medium text-slate-400 block mb-1">Pengalaman/Infrastruktur yang dimiliki</label><p class="text-sm font-medium text-slate-800">{{ profile.pengalaman_infrastruktur || '-' }}</p></div>
                                    
                                    <!-- Anggota Tim Teknisi List -->
                                    <div v-if="form.tim_teknisi && form.tim_teknisi.length > 0" class="md:col-span-3 mt-2">
                                        <h5 class="text-xs font-bold text-slate-500 uppercase mb-3">Anggota Tim Teknisi Lainnya</h5>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                                            <div v-for="(anggota, idx) in form.tim_teknisi" :key="idx" class="border border-slate-200 rounded-lg p-3 bg-slate-50 relative">
                                                <div class="flex justify-between items-start mb-1">
                                                    <span class="text-sm font-semibold text-slate-800">{{ anggota.nama || '-' }}</span>
                                                    <span class="text-xs px-2 py-0.5 bg-indigo-100 text-indigo-700 rounded font-medium">Size: {{ anggota.ukuran_seragam || '-' }}</span>
                                                </div>
                                                <p class="text-xs text-slate-500">WA: {{ anggota.wa || '-' }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Section: Dokumen -->
                            <div>
                                <h4 class="text-sm font-bold text-indigo-700 uppercase tracking-wider mb-4 pb-2 border-b border-indigo-100">Dokumen Pendukung</h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                                    <div class="border rounded-xl p-4 bg-slate-50 flex flex-col items-center justify-center text-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-indigo-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 21h7a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v11m0 5l4.879-4.879m0 0a3 3 0 104.243-4.242 3 3 0 00-4.243 4.242z" />
                                        </svg>
                                        <span class="text-sm font-medium text-slate-700 mb-1">KTP</span>
                                        <a v-if="profile.file_ktp" :href="`/storage/${profile.file_ktp}`" target="_blank" class="text-xs text-indigo-600 font-semibold hover:underline">Lihat File</a>
                                        <span v-else class="text-xs text-slate-400">Belum diunggah</span>
                                    </div>
                                    <div class="border rounded-xl p-4 bg-slate-50 flex flex-col items-center justify-center text-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-indigo-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <span class="text-sm font-medium text-slate-700 mb-1">NIB</span>
                                        <a v-if="profile.file_nib" :href="`/storage/${profile.file_nib}`" target="_blank" class="text-xs text-indigo-600 font-semibold hover:underline">Lihat File</a>
                                        <span v-else class="text-xs text-slate-400">Belum diunggah</span>
                                    </div>
                                    <div class="border rounded-xl p-4 bg-slate-50 flex flex-col items-center justify-center text-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-indigo-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                        <span class="text-sm font-medium text-slate-700 mb-1">NPWP</span>
                                        <a v-if="profile.file_npwp" :href="`/storage/${profile.file_npwp}`" target="_blank" class="text-xs text-indigo-600 font-semibold hover:underline">Lihat File</a>
                                        <span v-else class="text-xs text-slate-400">Belum diunggah</span>
                                    </div>
                                    <div class="border rounded-xl p-4 bg-slate-50 flex flex-col items-center justify-center text-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-indigo-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <span class="text-sm font-medium text-slate-700 mb-1">Foto Lokasi/Usaha</span>
                                        <a v-if="profile.file_lokasi" :href="`/storage/${profile.file_lokasi}`" target="_blank" class="text-xs text-indigo-600 font-semibold hover:underline">Lihat File</a>
                                        <span v-else class="text-xs text-slate-400">Belum diunggah</span>
                                    </div>
                                </div>
                                <div v-if="form.foto_tambahan.length > 0" class="mt-4 border-t border-slate-100 pt-4">
                                    <h5 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Foto/Dokumen Tambahan</h5>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                                        <div v-for="(foto, index) in form.foto_tambahan" :key="index" class="border rounded-xl p-4 bg-slate-50 flex flex-col items-center justify-center text-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-indigo-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            <span class="text-sm font-medium text-slate-700 mb-1">{{ foto.nama || 'Dokumen ' + (index+1) }}</span>
                                            <a v-if="foto.path" :href="`/storage/${foto.path}`" target="_blank" class="text-xs text-indigo-600 font-semibold hover:underline">Lihat File</a>
                                            <span v-else class="text-xs text-slate-400">Belum ada file</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Edit Mode -->
                        <form v-else @submit.prevent="submit" class="space-y-8">
                            <!-- Section: Data Mitra -->
                            <div>
                                <h4 class="text-sm font-bold text-indigo-700 uppercase tracking-wider mb-4 pb-2 border-b border-indigo-100">Data Mitra</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Nama Lengkap / Nama Usaha</label>
                                        <input v-model="form.name" type="text" class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1.5">NIK</label>
                                        <input v-model="form.nik" type="text" class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1.5">No. WhatsApp</label>
                                        <input v-model="form.no_wa" type="text" class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
                                        <input v-model="form.email" type="email" class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400" />
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Alamat Lengkap</label>
                                        <textarea v-model="form.alamat" rows="2" class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400"></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Kota/Kabupaten</label>
                                        <input v-model="form.kota" type="text" class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Provinsi</label>
                                        <input v-model="form.provinsi" type="text" class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400" />
                                    </div>
                                </div>
                            </div>

                            <!-- Section: Data Usaha -->
                            <div>
                                <h4 class="text-sm font-bold text-indigo-700 uppercase tracking-wider mb-4 pb-2 border-b border-indigo-100">Data Usaha</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Nama Usaha</label>
                                        <input v-model="form.nama_usaha" type="text" class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Jenis Usaha</label>
                                        <input v-model="form.jenis_usaha" type="text" class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1.5">NIB (Jika ada)</label>
                                        <input v-model="form.nib" type="text" class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1.5">NPWP (Jika ada)</label>
                                        <input v-model="form.npwp" type="text" class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400" />
                                    </div>
                                </div>
                            </div>

                            <!-- Section: Area Kemitraan -->
                            <div>
                                <h4 class="text-sm font-bold text-indigo-700 uppercase tracking-wider mb-4 pb-2 border-b border-indigo-100">Area Kemitraan</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Area/Wilayah yang dikelola</label>
                                        <input v-model="form.area_dikelola" type="text" class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Kecamatan/Kelurahan</label>
                                        <input v-model="form.kecamatan" type="text" class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Lokasi/Pin Maps (URL)</label>
                                        <input v-model="form.pin_maps" type="url" placeholder="https://goo.gl/maps/..." class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Perkiraan Jumlah Calon Pelanggan</label>
                                        <input v-model="form.estimasi_pelanggan" type="number" class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400" />
                                    </div>
                                </div>
                            </div>

                            <!-- Section: Data Teknis -->
                            <div>
                                <h4 class="text-sm font-bold text-indigo-700 uppercase tracking-wider mb-4 pb-2 border-b border-indigo-100">Data Teknis</h4>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Nama PIC Teknisi</label>
                                        <input v-model="form.pic_teknisi" type="text" class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1.5">No. WhatsApp Teknisi</label>
                                        <input v-model="form.wa_teknisi" type="text" class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Ukuran Seragam</label>
                                        <select v-model="form.ukuran_seragam" class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400">
                                            <option value="">- Pilih Ukuran -</option>
                                            <option value="M">M</option>
                                            <option value="L">L</option>
                                            <option value="XL">XL</option>
                                        </select>
                                    </div>
                                    <div class="md:col-span-3">
                                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Pengalaman/Infrastruktur yang dimiliki</label>
                                        <textarea v-model="form.pengalaman_infrastruktur" rows="2" class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400"></textarea>
                                    </div>
                                    
                                    <!-- Daftar Tim Teknisi Dynamic -->
                                    <div class="md:col-span-3 mt-2">
                                        <div class="flex items-center justify-between mb-3 border-b pb-2">
                                            <h5 class="text-sm font-bold text-slate-700">Anggota Tim Teknisi Lainnya</h5>
                                            <button type="button" @click="addTimTeknisi" class="inline-flex items-center px-2.5 py-1.5 bg-indigo-50 border border-indigo-200 rounded-md text-xs font-semibold text-indigo-700 hover:bg-indigo-100 transition shadow-sm">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                                </svg>
                                                Tambah Anggota
                                            </button>
                                        </div>
                                        <div v-if="form.tim_teknisi.length === 0" class="text-center py-6 bg-slate-50 border border-dashed border-slate-300 rounded-lg">
                                            <p class="text-sm text-slate-500">Belum ada data anggota tim tambahan.</p>
                                        </div>
                                        <div v-else class="space-y-4">
                                            <div v-for="(anggota, idx) in form.tim_teknisi" :key="idx" class="grid grid-cols-1 md:grid-cols-12 gap-4 p-4 border border-slate-200 rounded-lg bg-slate-50 relative group">
                                                <button type="button" @click="removeTimTeknisi(idx)" class="absolute -top-2 -right-2 bg-red-100 text-red-600 rounded-full p-1 shadow-sm opacity-0 group-hover:opacity-100 transition-opacity hover:bg-red-200" title="Hapus Anggota">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                                
                                                <div class="md:col-span-4">
                                                    <label class="block text-xs font-medium text-slate-500 mb-1">Nama Anggota</label>
                                                    <input v-model="anggota.nama" type="text" class="w-full px-3 py-1.5 border border-slate-300 rounded-md text-sm focus:ring-1 focus:ring-indigo-400 focus:border-indigo-400" placeholder="Nama..." />
                                                </div>
                                                <div class="md:col-span-4">
                                                    <label class="block text-xs font-medium text-slate-500 mb-1">No. WhatsApp</label>
                                                    <input v-model="anggota.wa" type="text" class="w-full px-3 py-1.5 border border-slate-300 rounded-md text-sm focus:ring-1 focus:ring-indigo-400 focus:border-indigo-400" placeholder="08..." />
                                                </div>
                                                <div class="md:col-span-4">
                                                    <label class="block text-xs font-medium text-slate-500 mb-1">Ukuran Seragam</label>
                                                    <select v-model="anggota.ukuran_seragam" class="w-full px-3 py-1.5 border border-slate-300 rounded-md text-sm focus:ring-1 focus:ring-indigo-400 focus:border-indigo-400">
                                                        <option value="">- Pilih Ukuran -</option>
                                                        <option value="M">M</option>
                                                        <option value="L">L</option>
                                                        <option value="XL">XL</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Section: Dokumen -->
                            <div>
                                <h4 class="text-sm font-bold text-indigo-700 uppercase tracking-wider mb-4 pb-2 border-b border-indigo-100">Upload Dokumen Pendukung (Abaikan jika tidak ingin mengubah)</h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1.5">KTP</label>
                                        <input type="file" @change="e => handleFileUpload(e, 'file_ktp')" class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1.5">NIB (Opsional)</label>
                                        <input type="file" @change="e => handleFileUpload(e, 'file_nib')" class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1.5">NPWP (Opsional)</label>
                                        <input type="file" @change="e => handleFileUpload(e, 'file_npwp')" class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Foto Lokasi/Usaha</label>
                                        <input type="file" @change="e => handleFileUpload(e, 'file_lokasi')" class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                                    </div>
                                </div>
                                <div class="mt-6 border-t border-slate-100 pt-6">
                                    <div class="flex items-center justify-between mb-4">
                                        <h5 class="text-sm font-bold text-slate-700">Foto/Dokumen Tambahan</h5>
                                        <button type="button" @click="addFotoTambahan" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 text-indigo-600 hover:bg-indigo-100 rounded-lg text-xs font-semibold transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                            Tambah Foto/Dokumen
                                        </button>
                                    </div>
                                    <div class="space-y-4">
                                        <div v-for="(foto, index) in form.foto_tambahan" :key="index" class="flex flex-col sm:flex-row gap-4 items-start sm:items-center bg-slate-50 p-4 rounded-xl border border-slate-200">
                                            <div class="flex-1 w-full">
                                                <label class="block text-xs font-medium text-slate-500 mb-1">Nama</label>
                                                <input v-model="foto.nama" type="text" placeholder="Contoh: Tim Instalasi, Teknisi, dll" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400" />
                                            </div>
                                            <div class="flex-1 w-full">
                                                <label class="block text-xs font-medium text-slate-500 mb-1">Upload File <span v-if="foto.path" class="text-indigo-500">(Sudah ada)</span></label>
                                                <input type="file" @change="e => handleFotoTambahanUpload(e, index)" class="w-full text-sm text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-white file:text-indigo-700 hover:file:bg-indigo-50 border border-slate-200 rounded-lg p-1" />
                                            </div>
                                            <button type="button" @click="removeFotoTambahan(index)" class="mt-4 sm:mt-0 p-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors self-end sm:self-auto" title="Hapus">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                        <div v-if="form.foto_tambahan.length === 0" class="text-center py-6 text-slate-400 text-sm">
                                            Belum ada foto/dokumen tambahan. Klik tombol "Tambah Foto/Dokumen" di atas.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
                                <button type="button" @click="editing = false"
                                    class="px-5 py-2.5 text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">
                                    Batal
                                </button>
                                <button type="submit" :disabled="form.processing"
                                    class="px-6 py-2.5 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-sm transition-all disabled:opacity-50">
                                    <span v-if="form.processing">Menyimpan...</span>
                                    <span v-else>Simpan Perubahan</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>

            <!-- Printable Area (Only visible on print) -->
            <div class="hidden print-only-area bg-white w-full h-auto" style="font-family: 'Times New Roman', Times, serif; color: black; line-height: 1.5; font-size: 14px;">
                <!-- Watermark for Print -->
                <div class="hidden print:flex fixed inset-0 z-0 items-center justify-center opacity-[0.05] pointer-events-none select-none overflow-hidden">
                    <div class="text-[120px] font-extrabold uppercase rotate-[-30deg] tracking-widest whitespace-nowrap">VIRUZS</div>
                </div>

                <div class="relative z-10 w-full bg-white h-auto">
                    <!-- Fixed Footer for Print (Sticks to absolute bottom of every printed page) -->
                    <div class="hidden print:block fixed bottom-0 left-0 right-0 w-full overflow-hidden z-[100]">
                        <img src="/images/kop-surat-bawah.jpg" alt="Footer Kop Surat" class="w-full h-auto object-contain block mx-auto print:max-w-full">
                    </div>
                    <table class="w-full border-collapse">
                        <thead class="print:table-header-group">
                            <tr>
                                <td class="p-0 border-none">
                                    <!-- Kop Surat (Letterhead) -->
                                    <div class="w-full text-center">
                                        <!-- Using the exact image without scaling to prevent blurriness -->
                                        <img src="/images/kop-surat-atas.jpg" alt="Kop Surat" class="max-w-full h-auto mx-auto print:max-w-full">
                                    </div>
                                </td>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="p-0 border-none">
                                    <!-- Content Area -->
                                    <div class="px-8 py-2 sm:px-12 sm:py-4 print:px-12 print:py-4 bg-white">
                        <div class="text-center mb-6 font-bold flex flex-col items-center">
                            <p class="text-xl underline mb-1 uppercase tracking-wide">Formulir PENDAFTARAN</p>
                            <p class="text-sm font-bold mt-1">Nomor: {{ nomorSurat }}</p>
                        </div>
                        
                        <p class="mb-4 text-justify leading-relaxed">
                            Berikut adalah data lengkap profil kemitraan yang terdaftar pada sistem kami:
                        </p>

                        <table class="w-full text-sm mb-6 border-collapse">
                            <tbody>
                                <tr>
                                    <td class="py-2 w-48 font-bold align-top">Nama Kemitraan</td>
                                    <td class="py-2 w-4 align-top">:</td>
                                    <td class="py-2 align-top">{{ displayUser.name }}</td>
                                </tr>
                                <tr>
                                    <td class="py-2 font-bold align-top">Email</td>
                                    <td class="py-2 align-top">:</td>
                                    <td class="py-2 align-top">{{ displayUser.email }}</td>
                                </tr>
                                <tr>
                                    <td class="py-2 font-bold align-top">Tipe Kemitraan</td>
                                    <td class="py-2 align-top">:</td>
                                    <td class="py-2 align-top">{{ profile.tipe_kemitraan || '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="py-2 font-bold align-top">Metro / Backbone</td>
                                    <td class="py-2 align-top">:</td>
                                    <td class="py-2 align-top">{{ profile.metro || '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="py-2 font-bold align-top">Bandwidth</td>
                                    <td class="py-2 align-top">:</td>
                                    <td class="py-2 align-top">{{ profile.bandwidth || '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="py-2 font-bold align-top">Nama Penanggung Jawab</td>
                                    <td class="py-2 align-top">:</td>
                                    <td class="py-2 align-top">{{ profile.nama_pic || displayUser.name }}</td>
                                </tr>
                                <tr>
                                    <td class="py-2 font-bold align-top">No WhatsApp</td>
                                    <td class="py-2 align-top">:</td>
                                    <td class="py-2 align-top">{{ profile.wa_pic || displayUser.phone || '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="py-2 font-bold align-top">Alamat Kemitraan</td>
                                    <td class="py-2 align-top">:</td>
                                    <td class="py-2 align-top">{{ profile.alamat || '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="py-2 font-bold align-top">Titik Koordinat</td>
                                    <td class="py-2 align-top">:</td>
                                    <td class="py-2 align-top">{{ profile.titik_koordinat || '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="py-2 font-bold align-top">Rekening Pembayaran</td>
                                    <td class="py-2 align-top">:</td>
                                    <td class="py-2 align-top">{{ profile.rekening || '-' }}</td>
                                </tr>
                            </tbody>
                        </table>

                        <div v-if="form.tim_teknisi && form.tim_teknisi.length > 0" class="mb-6">
                            <p class="font-bold mb-2">Tim Teknisi:</p>
                            <table class="w-full text-sm border-collapse border border-slate-800">
                                <thead>
                                    <tr>
                                        <th class="border border-slate-800 py-1 px-2 bg-slate-100 w-12 text-center">No</th>
                                        <th class="border border-slate-800 py-1 px-2 bg-slate-100 text-left">Nama Teknisi</th>
                                        <th class="border border-slate-800 py-1 px-2 bg-slate-100 text-left">No WhatsApp</th>
                                        <th class="border border-slate-800 py-1 px-2 bg-slate-100 text-center">Seragam</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(teknisi, index) in form.tim_teknisi" :key="index">
                                        <td class="border border-slate-800 py-1 px-2 text-center">{{ index + 1 }}</td>
                                        <td class="border border-slate-800 py-1 px-2">{{ teknisi.nama }}</td>
                                        <td class="border border-slate-800 py-1 px-2">{{ teknisi.wa }}</td>
                                        <td class="border border-slate-800 py-1 px-2 text-center">{{ teknisi.seragam || '-' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <p class="text-justify leading-relaxed mt-8">
                            Demikian profil kemitraan ini dicetak sebagai dokumen resmi yang sah sesuai dengan data yang terdaftar pada sistem kami.
                        </p>

                        <div class="flex justify-end mt-12 pr-12 pb-8">
                            <div class="text-center">
                                <p class="mb-20">Mengetahui,<br>Penanggung Jawab Kemitraan</p>
                                <p class="font-bold underline">{{ profile.nama_pic || displayUser.name }}</p>
                            </div>
                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="print:table-footer-group">
                            <tr>
                                <td class="p-0 border-none">
                                    <!-- Footer Kop Surat (Reserves space on every page) -->
                                    <div class="w-full mt-2 overflow-hidden print:invisible">
                                        <img src="/images/kop-surat-bawah.jpg" alt="Footer Kop Surat" class="w-full h-auto object-contain block transform scale-[1.08] print:scale-[1.1] origin-bottom">
                                    </div>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </KemitraanLayout>
</template>

<style scoped>
@media print {
    body, html {
        margin: 0 !important;
        padding: 0 !important;
        background-color: white !important;
        height: auto !important;
    }
    .print-only-area {
        display: block !important;
        width: 100% !important;
        height: auto !important;
        min-height: 0 !important;
    }
    .print\:hidden {
        display: none !important;
    }
}

.unprocessed-line {
    background-image: repeating-linear-gradient(
        -45deg,
        #ff6600 0,
        #ff6600 10px,
        transparent 10px,
        transparent 20px
    );
    background-size: 28px 100%;
    animation: move-dashed 1s linear infinite, blink-line 1.5s ease-in-out infinite;
}

@keyframes move-dashed {
    0% { background-position: 0 0; }
    100% { background-position: 28px 0; }
}

@keyframes blink-line {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}
</style>
