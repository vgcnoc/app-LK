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
        default: () => ({})
    }
});
</script>

<template>
    <Head title="Dashboard Kemitraan" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <div class="p-2 bg-indigo-500/20 rounded-xl text-indigo-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-slate-800 leading-tight">Dashboard Kemitraan</h2>
                    <p class="text-xs text-slate-500">Ringkasan performa mitra dan komisi Anda.</p>
                </div>
            </div>
        </template>

        <div class="py-6 space-y-6">
            <!-- Welcome Header -->
            <div class="bg-gradient-to-r from-[#2f3cb3] to-[#321d8b] rounded-2xl p-6 sm:p-8 text-white relative overflow-hidden shadow-xl shadow-indigo-900/10">
                <div class="absolute right-0 top-0 w-64 h-64 bg-white/10 rounded-full blur-3xl -mr-20 -mt-20 pointer-events-none"></div>
                <div class="relative z-10">
                    <h3 class="text-2xl font-bold mb-2 tracking-tight">Selamat Datang di Portal Kemitraan</h3>
                    <p class="text-indigo-200 text-sm max-w-xl leading-relaxed">
                        Pantau aktivitas mitra, komisi, dan performa jaringan Anda secara real-time.
                    </p>
                </div>
            </div>

            <!-- Dashboard Content Placeholder -->
            <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-slate-200 text-center text-slate-500">
                <p>Dashboard Kemitraan sedang dalam pengembangan.</p>
                <p class="text-xs mt-2">Data analitik kemitraan akan segera tersedia di sini.</p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
