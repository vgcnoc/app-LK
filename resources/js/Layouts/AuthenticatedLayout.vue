<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const sidebarOpen = ref(false);

const permissions = computed(() => usePage().props.auth?.permissions || []);
const can = (perm) => permissions.value.includes(perm);
</script>

<template>
    <div class="flex h-screen bg-slate-50 overflow-hidden print:h-auto print:overflow-visible print:block">
        <!-- Mobile Sidebar Backdrop -->
        <div
            v-if="sidebarOpen"
            @click="sidebarOpen = false"
            class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-20 lg:hidden transition-opacity print:hidden"
        ></div>

        <!-- Sidebar -->
        <aside
            :class="[
                sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
                'fixed lg:static inset-y-0 left-0 z-30 w-64 bg-gradient-to-b from-indigo-950 via-purple-950 to-slate-950 border-r border-purple-800/30 text-white flex flex-col flex-shrink-0 transition-transform duration-300 ease-in-out no-print shadow-2xl lg:shadow-none print:hidden'
            ]"
        >
            <!-- Logo / Brand Area -->
            <div class="h-16 flex items-center justify-between px-6 border-b border-purple-800/40 flex-shrink-0">
                <Link :href="route('dashboard')" class="flex items-center gap-3 text-white group">
                    <template v-if="$page.props.app_logo">
                        <img :src="$page.props.app_logo" class="h-8 max-w-full object-contain" alt="Logo" />
                    </template>
                    <template v-else>
                        <div class="p-2.5 rounded-xl bg-indigo-500/20 text-indigo-400 group-hover:bg-indigo-500/30 group-hover:scale-105 transition-all flex-shrink-0 flex items-center justify-center overflow-hidden">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <span class="text-lg font-bold tracking-wide text-white">
                            V-Billing
                        </span>
                    </template>
                </Link>

                <!-- Mobile close button -->
                <button
                    @click="sidebarOpen = false"
                    class="lg:hidden text-slate-400 hover:text-white p-1 rounded-lg focus:outline-none"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Navigation Links -->
            <nav class="flex-1 px-3 py-4 space-y-1.5 overflow-y-auto">
                <!-- Dashboard -->
                <Link
                    v-if="can('akses_dashboard')"
                    :href="route('dashboard')"
                    :class="[
                        route().current('dashboard')
                            ? 'bg-fuchsia-500/15 text-white font-semibold border-l-4 border-fuchsia-400 pl-3 shadow-[inset_0_0_12px_rgba(232,121,249,0.1)]'
                            : 'text-purple-200/70 lg:hover:text-white lg:hover:bg-fuchsia-500/10 border-l-4 border-transparent pl-3',
                        'flex items-center gap-3 px-3 py-2.5 rounded-r-xl text-sm font-medium transition-all duration-150'
                    ]"
                >
                    <!-- Dashboard Icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    <span>Dashboard</span>
                </Link>

                <!-- Transaksi -->
                <Link
                    v-if="can('akses_transaksi')"
                    :href="route('transaksi')"
                    :class="[
                        route().current('transaksi*')
                            ? 'bg-fuchsia-500/15 text-white font-semibold border-l-4 border-fuchsia-400 pl-3 shadow-[inset_0_0_12px_rgba(232,121,249,0.1)]'
                            : 'text-purple-200/70 lg:hover:text-white lg:hover:bg-fuchsia-500/10 border-l-4 border-transparent pl-3',
                        'flex items-center gap-3 px-3 py-2.5 rounded-r-xl text-sm font-medium transition-all duration-150'
                    ]"
                >
                    <!-- Transactions Icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Transaksi</span>
                </Link>


                <!-- Billing Data -->
                <Link
                    v-if="can('akses_billing')"
                    :href="route('billing.index')"
                    :class="[
                        route().current('billing*')
                            ? 'bg-fuchsia-500/15 text-white font-semibold border-l-4 border-fuchsia-400 pl-3 shadow-[inset_0_0_12px_rgba(232,121,249,0.1)]'
                            : 'text-purple-200/70 lg:hover:text-white lg:hover:bg-fuchsia-500/10 border-l-4 border-transparent pl-3',
                        'flex items-center gap-3 px-3 py-2.5 rounded-r-xl text-sm font-medium transition-all duration-150'
                    ]"
                >
                    <!-- Receipt/Billing Icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Billing Data</span>
                </Link>

                <!-- Voucher & Saldo -->
                <Link
                    v-if="can('akses_voucher_saldo')"
                    :href="route('voucher-saldo.index')"
                    :class="[
                        route().current('voucher-saldo*')
                            ? 'bg-fuchsia-500/15 text-white font-semibold border-l-4 border-fuchsia-400 pl-3 shadow-[inset_0_0_12px_rgba(232,121,249,0.1)]'
                            : 'text-purple-200/70 lg:hover:text-white lg:hover:bg-fuchsia-500/10 border-l-4 border-transparent pl-3',
                        'flex items-center gap-3 px-3 py-2.5 rounded-r-xl text-sm font-medium transition-all duration-150'
                    ]"
                >
                    <!-- Ticket/Voucher Icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                    </svg>
                    <span>Voucher & Saldo</span>
                </Link>
                <!-- Booking Pelanggan -->
                <Link
                    v-if="can('akses_booking')"
                    :href="route('booking.index')"
                    :class="[
                        route().current('booking*')
                            ? 'bg-fuchsia-500/15 text-white font-semibold border-l-4 border-fuchsia-400 pl-3 shadow-[inset_0_0_12px_rgba(232,121,249,0.1)]'
                            : 'text-purple-200/70 lg:hover:text-white lg:hover:bg-fuchsia-500/10 border-l-4 border-transparent pl-3',
                        'flex items-center gap-3 px-3 py-2.5 rounded-r-xl text-sm font-medium transition-all duration-150'
                    ]"
                >
                    <!-- Clipboard Document Icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <span>Booking Pelanggan</span>
                </Link>


                <!-- Data Pelanggan -->
                <Link
                    v-if="can('akses_data_pelanggan')"
                    :href="route('pelanggan.index')"
                    :class="[
                        route().current('pelanggan*') && !route().current('pelanggan.inaktif')
                            ? 'bg-fuchsia-500/15 text-white font-semibold border-l-4 border-fuchsia-400 pl-3 shadow-[inset_0_0_12px_rgba(232,121,249,0.1)]'
                            : 'text-purple-200/70 lg:hover:text-white lg:hover:bg-fuchsia-500/10 border-l-4 border-transparent pl-3',
                        'flex items-center gap-3 px-3 py-2.5 rounded-r-xl text-sm font-medium transition-all duration-150'
                    ]"
                >
                    <!-- Users Icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <span>Data Pelanggan</span>
                </Link>

                <!-- Pelanggan Inaktif -->
                <Link
                    v-if="can('akses_data_nonaktif')"
                    :href="route('pelanggan.inaktif')"
                    :class="[
                        route().current('pelanggan.inaktif')
                            ? 'bg-fuchsia-500/15 text-white font-semibold border-l-4 border-fuchsia-400 pl-3 shadow-[inset_0_0_12px_rgba(232,121,249,0.1)]'
                            : 'text-purple-200/70 lg:hover:text-white lg:hover:bg-fuchsia-500/10 border-l-4 border-transparent pl-3',
                        'flex items-center gap-3 px-3 py-2.5 rounded-r-xl text-sm font-medium transition-all duration-150'
                    ]"
                >
                    <!-- Archive/Pause Icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Pelanggan Inaktif</span>
                </Link>

                <!-- Pelanggan Pantauan -->
                <Link
                    v-if="can('akses_data_pelanggan')"
                    :href="route('pelanggan.pantauan')"
                    :class="[
                        route().current('pelanggan.pantauan')
                            ? 'bg-fuchsia-500/15 text-white font-semibold border-l-4 border-fuchsia-400 pl-3 shadow-[inset_0_0_12px_rgba(232,121,249,0.1)]'
                            : 'text-purple-200/70 lg:hover:text-white lg:hover:bg-fuchsia-500/10 border-l-4 border-transparent pl-3',
                        'flex items-center gap-3 px-3 py-2.5 rounded-r-xl text-sm font-medium transition-all duration-150'
                    ]"
                >
                    <!-- Exclamation Icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span>Pelanggan Pantauan</span>
                </Link>

                <!-- Laporan -->
                <Link
                    v-if="can('akses_laporan')"
                    :href="route('laporan')"
                    :class="[
                        route().current('laporan*')
                            ? 'bg-fuchsia-500/15 text-white font-semibold border-l-4 border-fuchsia-400 pl-3 shadow-[inset_0_0_12px_rgba(232,121,249,0.1)]'
                            : 'text-purple-200/70 lg:hover:text-white lg:hover:bg-fuchsia-500/10 border-l-4 border-transparent pl-3',
                        'flex items-center gap-3 px-3 py-2.5 rounded-r-xl text-sm font-medium transition-all duration-150'
                    ]"
                >
                    <!-- Report / Chart Icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Laporan</span>
                </Link>

                <!-- Master Data -->
                <Link
                    v-if="can('akses_master_data')"
                    :href="route('master-data.index')"
                    :class="[
                        route().current('master-data*')
                            ? 'bg-fuchsia-500/15 text-white font-semibold border-l-4 border-fuchsia-400 pl-3 shadow-[inset_0_0_12px_rgba(232,121,249,0.1)]'
                            : 'text-purple-200/70 lg:hover:text-white lg:hover:bg-fuchsia-500/10 border-l-4 border-transparent pl-3',
                        'flex items-center gap-3 px-3 py-2.5 rounded-r-xl text-sm font-medium transition-all duration-150'
                    ]"
                >
                    <!-- Database / Master Data Icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                    </svg>
                    <span>Master Data</span>
                </Link>

                <!-- Affiliates -->
                <Link
                    v-if="can('akses_affiliate')"
                    :href="route('affiliates.index')"
                    :class="[
                        route().current('affiliates*')
                            ? 'bg-fuchsia-500/15 text-white font-semibold border-l-4 border-fuchsia-400 pl-3 shadow-[inset_0_0_12px_rgba(232,121,249,0.1)]'
                            : 'text-purple-200/70 lg:hover:text-white lg:hover:bg-fuchsia-500/10 border-l-4 border-transparent pl-3',
                        'flex items-center gap-3 px-3 py-2.5 rounded-r-xl text-sm font-medium transition-all duration-150'
                    ]"
                >
                    <!-- Affiliate / Network Icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <span>Multi-Tier Affiliate</span>
                </Link>
                
                <!-- Diagram Affiliate -->
                <Link
                    v-if="can('akses_affiliate')"
                    :href="route('affiliates.diagram')"
                    :class="[
                        route().current('affiliates.diagram')
                            ? 'bg-fuchsia-500/15 text-white font-semibold border-l-4 border-fuchsia-400 pl-3 shadow-[inset_0_0_12px_rgba(232,121,249,0.1)]'
                            : 'text-purple-200/70 lg:hover:text-white lg:hover:bg-fuchsia-500/10 border-l-4 border-transparent pl-3',
                        'flex items-center gap-3 px-3 py-2.5 rounded-r-xl text-sm font-medium transition-all duration-150'
                    ]"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                    <span>Diagram Affiliate</span>
                </Link>

                <!-- Data Komisi -->
                <Link
                    v-if="can('akses_affiliate')"
                    :href="route('komisi.index')"
                    :class="[
                        route().current('komisi*')
                            ? 'bg-fuchsia-500/15 text-white font-semibold border-l-4 border-fuchsia-400 pl-3 shadow-[inset_0_0_12px_rgba(232,121,249,0.1)]'
                            : 'text-purple-200/70 lg:hover:text-white lg:hover:bg-fuchsia-500/10 border-l-4 border-transparent pl-3',
                        'flex items-center gap-3 px-3 py-2.5 rounded-r-xl text-sm font-medium transition-all duration-150'
                    ]"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Data Komisi</span>
                </Link>

                <!-- Integrasi API Billing -->
                <Link
                    v-if="can('akses_integrasi')"
                    :href="route('integrasi-billing.index')"
                    :class="[
                        route().current('integrasi-billing*')
                            ? 'bg-fuchsia-500/15 text-white font-semibold border-l-4 border-fuchsia-400 pl-3 shadow-[inset_0_0_12px_rgba(232,121,249,0.1)]'
                            : 'text-purple-200/70 lg:hover:text-white lg:hover:bg-fuchsia-500/10 border-l-4 border-transparent pl-3',
                        'flex items-center gap-3 px-3 py-2.5 rounded-r-xl text-sm font-medium transition-all duration-150'
                    ]"
                >
                    <!-- Receipt / Billing Icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Integrasi API Billing</span>
                </Link>

                <!-- Divider -->
                <div class="my-3 px-3">
                    <div class="border-t border-slate-700/60"></div>
                </div>

                <!-- Pengaturan -->
                <Link
                    :href="route().has('profile.edit') ? route('profile.edit') : (route().has('settings') ? route('settings') : '#')"
                    :class="[
                        route().current('profile*') || route().current('settings*')
                            ? 'bg-fuchsia-500/15 text-white font-semibold border-l-4 border-fuchsia-400 pl-3 shadow-[inset_0_0_12px_rgba(232,121,249,0.1)]'
                            : 'text-purple-200/70 lg:hover:text-white lg:hover:bg-fuchsia-500/10 border-l-4 border-transparent pl-3',
                        'flex items-center gap-3 px-3 py-2.5 rounded-r-xl text-sm font-medium transition-all duration-150'
                    ]"
                >
                    <!-- Settings / Cog Icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span>Pengaturan</span>
                </Link>
            </nav>
        </aside>

        <!-- Content Wrapper -->
        <div class="flex-1 flex flex-col overflow-hidden min-w-0 print:overflow-visible print:block">
            <!-- Topbar -->
            <header class="h-16 bg-white shadow-sm border-b border-slate-200/80 flex items-center justify-between px-4 sm:px-6 z-10 no-print flex-shrink-0 print:hidden">
                <!-- Mobile Topbar -->
                <div class="flex items-center justify-between w-full lg:hidden">
                    <div class="flex items-center gap-3">
                        <button @click="sidebarOpen = !sidebarOpen" class="p-2 -ml-2 rounded-lg text-slate-700 hover:bg-slate-100 focus:outline-none">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                        <Link :href="route('dashboard')" class="flex items-center">
                            <template v-if="$page.props.app_logo">
                                <img :src="$page.props.app_logo" class="h-6 max-w-full object-contain" alt="Logo" />
                            </template>
                            <template v-else>
                                <span class="text-lg font-bold text-indigo-700">V-Billing</span>
                            </template>
                        </Link>
                    </div>
                    <div class="flex items-center gap-3">
                        <button class="relative p-2 text-slate-600 hover:bg-slate-100 rounded-full focus:outline-none">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                            <span class="absolute top-1.5 right-2 block h-2.5 w-2.5 rounded-full bg-red-500 border-2 border-white"></span>
                        </button>
                        <div class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center text-sm font-bold shadow-sm">
                            {{ $page.props.auth.user.name.substring(0,2).toUpperCase() }}
                        </div>
                    </div>
                </div>

                <!-- Desktop Topbar -->
                <div class="hidden lg:flex items-center justify-between w-full">
                    <div class="flex items-center gap-3 truncate">
                        <slot name="header" />
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="text-sm font-medium text-slate-600">
                            Halo, <span class="font-semibold text-slate-800">{{ $page.props.auth.user.name }}</span>
                        </span>
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            title="Keluar"
                            class="text-red-600 hover:text-red-700 bg-red-50 hover:bg-red-100 p-2 rounded-xl transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-red-500/20 shadow-sm"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                        </Link>
                    </div>
                </div>
            </header>

            <!-- Main Content -->
            <main class="flex-1 overflow-x-auto overflow-y-auto bg-slate-50 p-4 sm:p-6 pb-20 lg:pb-6 print:overflow-visible print:bg-white print:p-0 print:block">
                <slot />
                
                <!-- Footer -->
                <footer class="mt-8 pt-4 border-t border-slate-200/80 text-center text-sm text-slate-500 print:hidden mb-16 lg:mb-0">
                    &copy; {{ new Date().getFullYear() }} viruzs global connection.
                </footer>
            </main>

            <!-- Mobile Bottom Navigation -->
            <nav class="lg:hidden fixed bottom-0 w-full bg-white border-t border-slate-200 flex justify-around items-center pb-safe z-40 h-16 shadow-[0_-2px_10px_rgba(0,0,0,0.02)]">
                <Link :href="route('dashboard')" :class="[route().current('dashboard') ? 'text-blue-600' : 'text-slate-500', 'flex flex-col items-center justify-center w-full h-full space-y-1']">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    <span class="text-[10px] font-medium">Dashboard</span>
                </Link>
                <Link v-if="can('akses_data_pelanggan')" :href="route('pelanggan.index')" :class="[route().current('pelanggan.index') ? 'text-blue-600' : 'text-slate-500', 'flex flex-col items-center justify-center w-full h-full space-y-1']">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    <span class="text-[10px] font-medium">Pelanggan</span>
                </Link>
                <Link v-if="can('akses_billing')" :href="route('billing.index')" :class="[route().current('billing*') ? 'text-blue-600' : 'text-slate-500', 'flex flex-col items-center justify-center w-full h-full space-y-1']">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span class="text-[10px] font-medium">Tagihan</span>
                </Link>
                <button @click="sidebarOpen = true" class="text-slate-500 flex flex-col items-center justify-center w-full h-full space-y-1 focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z"/></svg>
                    <span class="text-[10px] font-medium">Lainnya</span>
                </button>
            </nav>
        </div>
    </div>
</template>
