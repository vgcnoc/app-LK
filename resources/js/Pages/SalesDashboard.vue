<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    profile: {
        type: Object,
        default: () => ({})
    },
    metrics: {
        type: Object,
        default: () => ({
            totalDownlines: 0,
            totalBooking: 0,
            activeBooking: 0,
            pendingBooking: 0,
            pasangBerbayar: 0,
            pasangGratis: 0,
            totalCommission: 0
        })
    },
    globalInstallationFee: {
        type: [Number, String],
        default: 0
    },
    internetPackages: {
        type: Array,
        default: () => []
    }
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value || 0);
};

const copied = ref(false);

const copyReferral = () => {
    if (props.profile?.member_number) {
        navigator.clipboard.writeText(props.profile.member_number)
            .then(() => {
                copied.value = true;
                setTimeout(() => { copied.value = false; }, 2000);
            })
            .catch(err => {
                console.error('Failed to copy text: ', err);
                // Fallback for older browsers
                try {
                    const el = document.createElement('textarea');
                    el.value = props.profile.member_number;
                    document.body.appendChild(el);
                    el.select();
                    document.execCommand('copy');
                    document.body.removeChild(el);
                    copied.value = true;
                    setTimeout(() => { copied.value = false; }, 2000);
                } catch (fallbackErr) {
                    alert('Gagal menyalin kode referral.');
                }
            });
    }
};
</script>

<template>
    <Head title="Sales Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-bold text-xl text-slate-800 leading-tight">
                Dashboard Affiliate
            </h2>
        </template>

        <!-- Welcome Banner -->
        <div class="mb-8 bg-gradient-to-r from-indigo-600 to-indigo-800 rounded-3xl p-8 text-white shadow-lg relative overflow-hidden">
            <!-- Decorative circles -->
            <div class="absolute bottom-0 right-32 -mb-16 w-48 h-48 rounded-full bg-indigo-400 opacity-20 blur-xl pointer-events-none"></div>
            
            <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div>
                    <h3 class="text-3xl font-bold mb-2">Selamat Datang, {{ profile?.name || $page.props.auth.user.name }}!</h3>
                    <p class="text-indigo-100 text-lg mb-4">
                        Tingkatkan terus jaringan Anda dan dapatkan komisi tanpa batas.
                    </p>
                    
                    <div class="flex items-center justify-between sm:justify-start gap-2 sm:gap-3 bg-indigo-900 bg-opacity-30 px-3 sm:px-4 py-2 rounded-xl border border-indigo-300 border-opacity-20 overflow-hidden">
                        <span class="text-indigo-100 text-xs sm:text-xs sm:text-sm whitespace-nowrap">Kode Referral:</span>
                        <span class="font-mono font-bold text-sm sm:text-lg tracking-wide text-white truncate">{{ profile?.member_number || '-' }}</span>
                        <button class="p-1 sm:p-1.5 hover:bg-indigo-900 hover:bg-opacity-50 rounded-lg transition-colors text-indigo-100 hover:text-white flex-shrink-0" :title="copied ? 'Tersalin!' : 'Salin'" @click="copyReferral">
                            <svg v-if="!copied" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                            <svg v-else class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        </button>
                    </div>
                </div>
                
                <div class="bg-indigo-900 bg-opacity-50 border border-indigo-500 border-opacity-30 p-6 rounded-2xl text-center min-w-[220px] shadow-inner">
                    <div class="text-indigo-200 text-xs sm:text-sm font-medium mb-1 uppercase tracking-wider">Total Estimasi Komisi</div>
                    <div class="text-3xl font-extrabold text-white">
                        {{ formatCurrency(metrics.totalCommission) }}
                    </div>
                </div>
            </div>
        </div>


        <!-- Metrics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Downlines -->
            <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100 hover:shadow-md transition-shadow relative overflow-hidden group">
                <div class="absolute -right-4 -top-4 w-24 h-24 bg-emerald-50 rounded-full group-hover:scale-150 transition-transform duration-500 ease-in-out"></div>
                <div class="relative z-10">
                    <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-2xl flex items-center justify-center mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                    </div>
                    <h4 class="text-slate-500 text-xs sm:text-sm font-medium mb-1">Anggota Jaringan</h4>
                    <div class="text-3xl font-bold text-slate-800">{{ metrics.totalDownlines }} <span class="text-xs sm:text-sm font-normal text-slate-400 ml-1">orang</span></div>
                </div>
            </div>

            <!-- Total Booking -->
            <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100 hover:shadow-md transition-shadow relative overflow-hidden group">
                <div class="absolute -right-4 -top-4 w-24 h-24 bg-blue-50 rounded-full group-hover:scale-150 transition-transform duration-500 ease-in-out"></div>
                <div class="relative z-10">
                    <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-2xl flex items-center justify-center mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                    </div>
                    <h4 class="text-slate-500 text-xs sm:text-sm font-medium mb-1">Total Prospek</h4>
                    <div class="text-3xl font-bold text-slate-800">{{ metrics.totalBooking }} <span class="text-xs sm:text-sm font-normal text-slate-400 ml-1">calon</span></div>
                </div>
            </div>

            <!-- Active Customers -->
            <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100 hover:shadow-md transition-shadow relative overflow-hidden group">
                <div class="absolute -right-4 -top-4 w-24 h-24 bg-indigo-50 rounded-full group-hover:scale-150 transition-transform duration-500 ease-in-out"></div>
                <div class="relative z-10">
                    <div class="w-12 h-12 bg-indigo-100 text-indigo-600 rounded-2xl flex items-center justify-center mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <h4 class="text-slate-500 text-xs sm:text-sm font-medium mb-1">Pelanggan Aktif</h4>
                    <div class="text-3xl font-bold text-slate-800">{{ metrics.activeBooking }} <span class="text-xs sm:text-sm font-normal text-slate-400 ml-1">pelanggan</span></div>
                </div>
            </div>

            <!-- Pending Booking -->
            <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100 hover:shadow-md transition-shadow relative overflow-hidden group">
                <div class="absolute -right-4 -top-4 w-24 h-24 bg-amber-50 rounded-full group-hover:scale-150 transition-transform duration-500 ease-in-out"></div>
                <div class="relative z-10">
                    <div class="w-12 h-12 bg-amber-100 text-amber-600 rounded-2xl flex items-center justify-center mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <h4 class="text-slate-500 text-xs sm:text-sm font-medium mb-1">Menunggu Persetujuan</h4>
                    <div class="text-3xl font-bold text-slate-800">{{ metrics.pendingBooking }} <span class="text-xs sm:text-sm font-normal text-slate-400 ml-1">calon</span></div>
                </div>
            </div>

        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Mulai Rekrutmen</h3>
                <p class="text-slate-500 mb-6">
                    Ajak rekan dan kenalan Anda untuk bergabung sebagai Sales. Dapatkan tambahan komisi dari setiap transaksi jaringan Anda.
                </p>
                <Link :href="route('affiliates.index')" class="inline-flex items-center justify-center px-3 py-2 sm:px-6 sm:py-3 border border-transparent text-base font-medium rounded-xl text-white bg-indigo-600 hover:bg-indigo-700 transition-colors w-full md:w-auto">
                    Lihat Jaringan Saya
                </Link>
            </div>

            <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Daftarkan Prospek</h3>
                <p class="text-slate-500 mb-6">
                    Masukkan data calon pelanggan yang tertarik. Tim kami akan segera menindaklanjuti proses pemasangan.
                </p>
                <Link :href="route('booking.index')" class="inline-flex items-center justify-center px-3 py-2 sm:px-6 sm:py-3 border border-transparent text-base font-medium rounded-xl text-white bg-emerald-600 hover:bg-emerald-700 transition-colors w-full md:w-auto">
                    Buat Booking Baru
                </Link>
            </div>
        </div>

        <!-- Pricing List Section -->
        <div class="mt-8">
            <h3 class="text-xl font-bold text-slate-800 mb-6 flex items-center gap-2">
                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                Daftar Harga Paket Layanan Internet Hebat
            </h3>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <div v-for="(pkg, index) in internetPackages" :key="pkg.id" class="relative bg-white rounded-3xl border border-slate-200 shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden group">
                    <div class="absolute top-0 left-0 w-full h-1.5 bg-gradient-to-r from-indigo-500 to-purple-500 transform origin-left scale-x-0 group-hover:scale-x-100 transition-transform duration-500"></div>
                    <div class="p-6">
                        <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0" /></svg>
                        </div>
                        <h4 class="text-lg font-bold text-slate-800 mb-1 line-clamp-2">{{ pkg.name }}</h4>
                        <div class="mt-4 flex items-end gap-1">
                            <span class="text-2xl font-black text-indigo-600">{{ formatCurrency(pkg.price) }}</span>
                            <span class="text-sm text-slate-500 font-medium mb-1">/bln</span>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 text-sm text-slate-600 flex justify-between items-center">
                        <span class="font-medium text-slate-700">Pemasangan:</span>
                        <span class="font-bold text-emerald-600">{{ globalInstallationFee > 0 ? formatCurrency(globalInstallationFee) : 'Gratis!' }}</span>
                    </div>
                </div>
            </div>

            <!-- Empty State if no packages -->
            <div v-if="!internetPackages || internetPackages.length === 0" class="text-center py-10 bg-slate-50 rounded-3xl border border-slate-200 border-dashed">
                <svg class="w-12 h-12 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                <p class="text-slate-500 text-sm">Belum ada paket internet yang tersedia saat ini.</p>
            </div>
        </div>

    </AuthenticatedLayout>
</template>
