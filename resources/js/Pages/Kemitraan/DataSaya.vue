<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import KemitraanLayout from '@/Layouts/KemitraanLayout.vue';

const page = usePage();
const user = computed(() => page.props.auth?.user || {});

const props = defineProps({
    profile: { type: Object, default: () => ({}) },
});

const editing = ref(false);

const form = useForm({
    name: user.value.name || '',
    email: user.value.email || '',
    phone: props.profile.phone || '',
    company_name: props.profile.company_name || '',
    address: props.profile.address || '',
    area: props.profile.area || '',
});

const submit = () => {
    form.put(route('kemitraan.datasaya.update'), {
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
</script>

<template>
    <Head title="Data Saya - Kemitraan" />

    <KemitraanLayout>
        <template #header>
            <div class="hidden sm:flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex-1 min-w-0">
                    <h2 class="text-xl sm:text-3xl font-bold text-slate-800 tracking-tight">
                        Data Saya
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">
                        Informasi profil dan data kemitraan Anda.
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

                <!-- Profile Card -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <!-- Header Banner -->
                    <div class="h-32 bg-gradient-to-br from-indigo-600 via-purple-600 to-indigo-800 relative">
                        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiNmZmYiIGZpbGwtb3BhY2l0eT0iMC4wNSI+PHBhdGggZD0iTTM2IDM0djItSDJ2LTJoMzR6bTAtMzBWMkgydjJoMzR6TTE4IDE3djJIMnYtMmgxNnptMC0xNXYySDB2LTJoMTh6Ii8+PC9nPjwvZz48L3N2Zz4=')] opacity-30"></div>
                    </div>

                    <div class="px-6 pb-6 -mt-12 relative">
                        <!-- Avatar -->
                        <div class="flex items-end gap-4 mb-6">
                            <div class="w-24 h-24 rounded-2xl bg-white shadow-lg border-4 border-white flex items-center justify-center text-3xl font-black text-indigo-600">
                                {{ (user.name || 'U').substring(0, 2).toUpperCase() }}
                            </div>
                            <div class="pb-1">
                                <h3 class="text-xl font-bold text-slate-800">{{ user.name }}</h3>
                                <p class="text-sm text-slate-500">Mitra Reseller</p>
                            </div>
                        </div>

                        <!-- Profile Info / Edit Form -->
                        <div v-if="!editing" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <div>
                                    <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Nama Lengkap</label>
                                    <p class="text-sm font-medium text-slate-800 mt-1">{{ user.name || '-' }}</p>
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Email</label>
                                    <p class="text-sm font-medium text-slate-800 mt-1">{{ user.email || '-' }}</p>
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">No. Telepon</label>
                                    <p class="text-sm font-medium text-slate-800 mt-1">{{ profile.phone || '-' }}</p>
                                </div>
                            </div>
                            <div class="space-y-4">
                                <div>
                                    <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Nama Usaha</label>
                                    <p class="text-sm font-medium text-slate-800 mt-1">{{ profile.company_name || '-' }}</p>
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Area</label>
                                    <p class="text-sm font-medium text-slate-800 mt-1">{{ profile.area || '-' }}</p>
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Alamat</label>
                                    <p class="text-sm font-medium text-slate-800 mt-1">{{ profile.address || '-' }}</p>
                                </div>
                            </div>

                            <div class="md:col-span-2 pt-2">
                                <button @click="editing = true"
                                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-sm transition-all">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Edit Profil
                                </button>
                            </div>
                        </div>

                        <!-- Edit Form -->
                        <form v-else @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">Nama Lengkap</label>
                                <input v-model="form.name" type="text" required
                                    class="w-full px-4 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 transition-all" />
                                <p v-if="form.errors.name" class="text-xs text-red-500 mt-1">{{ form.errors.name }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
                                <input v-model="form.email" type="email" required
                                    class="w-full px-4 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 transition-all" />
                                <p v-if="form.errors.email" class="text-xs text-red-500 mt-1">{{ form.errors.email }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">No. Telepon</label>
                                <input v-model="form.phone" type="text"
                                    class="w-full px-4 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 transition-all"
                                    placeholder="08xxxxxxxxxx" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">Nama Usaha</label>
                                <input v-model="form.company_name" type="text"
                                    class="w-full px-4 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 transition-all"
                                    placeholder="Nama usaha / toko" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">Area</label>
                                <input v-model="form.area" type="text"
                                    class="w-full px-4 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 transition-all"
                                    placeholder="Area operasional" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">Alamat</label>
                                <input v-model="form.address" type="text"
                                    class="w-full px-4 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 transition-all"
                                    placeholder="Alamat lengkap" />
                            </div>
                            <div class="md:col-span-2 flex justify-end gap-3 pt-2">
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

                <!-- Quick Info Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="bg-emerald-50 text-emerald-600 p-2 rounded-xl">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h4 class="font-semibold text-slate-800">Status Akun</h4>
                        </div>
                        <span class="px-3 py-1.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">Aktif</span>
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
                        <p class="text-sm text-slate-600">{{ user.created_at ? new Date(user.created_at).toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' }) : '-' }}</p>
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
                        <p class="text-sm text-slate-600">Reseller ISP</p>
                    </div>
                </div>

            </div>
        </div>
    </KemitraanLayout>
</template>
