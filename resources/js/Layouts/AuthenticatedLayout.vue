<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';

const sidebarOpen = ref(false);
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
                'fixed lg:static inset-y-0 left-0 z-30 w-64 bg-gradient-to-b from-slate-800 to-slate-900 text-white flex flex-col flex-shrink-0 transition-transform duration-300 ease-in-out no-print shadow-xl lg:shadow-none print:hidden'
            ]"
        >
            <!-- Logo / Brand Area -->
            <div class="h-16 flex items-center justify-between px-6 border-b border-slate-700/60 flex-shrink-0">
                <Link :href="route('dashboard')" class="flex items-center gap-3 text-white group">
                    <div class="p-2 rounded-xl bg-indigo-500/20 text-indigo-400 group-hover:bg-indigo-500/30 group-hover:scale-105 transition-all">
                        <!-- Wallet SVG Icon -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <span class="text-base font-bold tracking-wider uppercase">
                        APP KEUANGAN
                    </span>
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
                    :href="route('dashboard')"
                    :class="[
                        route().current('dashboard')
                            ? 'bg-white/10 text-white font-semibold border-l-4 border-white pl-3'
                            : 'text-slate-300 hover:text-white hover:bg-white/5 border-l-4 border-transparent pl-3',
                        'flex items-center gap-3 px-3 py-2.5 rounded-r-xl text-sm font-medium transition-all duration-150 hover:translate-x-1'
                    ]"
                >
                    <!-- Dashboard Icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    <span>Dashboard</span>
                </Link>

                <!-- Billing Data -->
                <Link
                    :href="route('billing.index')"
                    :class="[
                        route().current('billing*')
                            ? 'bg-white/10 text-white font-semibold border-l-4 border-white pl-3'
                            : 'text-slate-300 hover:text-white hover:bg-white/5 border-l-4 border-transparent pl-3',
                        'flex items-center gap-3 px-3 py-2.5 rounded-r-xl text-sm font-medium transition-all duration-150 hover:translate-x-1'
                    ]"
                >
                    <!-- Receipt/Billing Icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Billing Data</span>
                </Link>

                <!-- Data Pelanggan -->
                <Link
                    :href="route('pelanggan.index')"
                    :class="[
                        route().current('pelanggan*') && !route().current('pelanggan.inaktif')
                            ? 'bg-white/10 text-white font-semibold border-l-4 border-white pl-3'
                            : 'text-slate-300 hover:text-white hover:bg-white/5 border-l-4 border-transparent pl-3',
                        'flex items-center gap-3 px-3 py-2.5 rounded-r-xl text-sm font-medium transition-all duration-150 hover:translate-x-1'
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
                    :href="route('pelanggan.inaktif')"
                    :class="[
                        route().current('pelanggan.inaktif')
                            ? 'bg-white/10 text-white font-semibold border-l-4 border-white pl-3'
                            : 'text-slate-300 hover:text-white hover:bg-white/5 border-l-4 border-transparent pl-3',
                        'flex items-center gap-3 px-3 py-2.5 rounded-r-xl text-sm font-medium transition-all duration-150 hover:translate-x-1'
                    ]"
                >
                    <!-- Archive/Pause Icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Data Non-Aktif</span>
                </Link>

                <!-- Laporan -->
                <Link
                    :href="route('laporan')"
                    :class="[
                        route().current('laporan*')
                            ? 'bg-white/10 text-white font-semibold border-l-4 border-white pl-3'
                            : 'text-slate-300 hover:text-white hover:bg-white/5 border-l-4 border-transparent pl-3',
                        'flex items-center gap-3 px-3 py-2.5 rounded-r-xl text-sm font-medium transition-all duration-150 hover:translate-x-1'
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
                    :href="route('master-data.index')"
                    :class="[
                        route().current('master-data*')
                            ? 'bg-white/10 text-white font-semibold border-l-4 border-white pl-3'
                            : 'text-slate-300 hover:text-white hover:bg-white/5 border-l-4 border-transparent pl-3',
                        'flex items-center gap-3 px-3 py-2.5 rounded-r-xl text-sm font-medium transition-all duration-150 hover:translate-x-1'
                    ]"
                >
                    <!-- Database / Master Data Icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                    </svg>
                    <span>Master Data</span>
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
                            ? 'bg-white/10 text-white font-semibold border-l-4 border-white pl-3'
                            : 'text-slate-300 hover:text-white hover:bg-white/5 border-l-4 border-transparent pl-3',
                        'flex items-center gap-3 px-3 py-2.5 rounded-r-xl text-sm font-medium transition-all duration-150 hover:translate-x-1'
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
            <header class="h-16 bg-white shadow-sm border-b border-slate-200/80 flex items-center justify-between px-6 z-10 no-print flex-shrink-0 print:hidden">
                <div class="flex items-center gap-3">
                    <!-- Mobile Hamburger Button -->
                    <button
                        @click="sidebarOpen = !sidebarOpen"
                        class="lg:hidden p-2 rounded-lg text-slate-500 hover:text-slate-700 hover:bg-slate-100 focus:outline-none"
                        aria-label="Toggle Navigation"
                    >
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <div>
                        <slot name="header" />
                    </div>
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
                        <!-- Red Logout SVG Icon -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </Link>
                </div>
            </header>

            <!-- Main Content -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-50 p-6 print:overflow-visible print:bg-white print:p-0 print:block">
                <slot />
            </main>
        </div>
    </div>
</template>
