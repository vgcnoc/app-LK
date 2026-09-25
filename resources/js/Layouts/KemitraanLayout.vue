<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import axios from 'axios';

const notifications = ref([]);
const showNotifications = ref(false);
let notifInterval;

const fetchNotifications = async () => {
    try {
        const response = await axios.get(route('api.notifications.index'));
        notifications.value = response.data.notifications;
    } catch (error) {
        console.error('Error fetching notifications:', error);
    }
};

const markAsRead = async (id) => {
    try {
        await axios.post(route('api.notifications.read', id));
        fetchNotifications();
    } catch (error) {
        console.error('Error marking as read:', error);
    }
};

onMounted(() => {
    fetchNotifications();
    notifInterval = setInterval(fetchNotifications, 30000);
});
onUnmounted(() => {
    clearInterval(notifInterval);
});

const sidebarOpen = ref(false);

const permissions = computed(() => usePage().props.auth?.permissions || []);
const can = (perm) => permissions.value.includes(perm);

const userRole = computed(() => usePage().props.auth?.user?.role || '');
const isAdmin = computed(() => userRole.value !== 'kemitraan');
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
                'fixed lg:static inset-y-0 left-0 z-30 w-64 bg-[#24134a] text-white flex flex-col flex-shrink-0 transition-transform duration-300 ease-in-out no-print shadow-2xl lg:shadow-none print:hidden'
            ]"
        >
            <!-- Logo / Brand Area -->
            <div class="h-16 flex items-center justify-between px-6 border-b border-white/5 flex-shrink-0">
                <Link :href="route('kemitraan.index')" class="flex items-center gap-3 text-white group">
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
                            Portal Kemitraan
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
            <nav class="flex-1 flex flex-col px-4 py-4 space-y-2 overflow-y-auto">
                <!-- Data Saya (paling atas untuk mitra) -->
                <Link
                    v-if="!isAdmin"
                    :href="route('kemitraan.datasaya')"
                    :class="[
                        route().current('kemitraan.datasaya*')
                            ? 'bg-[#432386] text-white font-semibold shadow-md'
                            : 'text-slate-300 lg:hover:text-white lg:hover:bg-white/5',
                        'flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-150'
                    ]"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <span>Data Saya</span>
                </Link>

                <!-- BAST -->
                <Link
                    :href="route('kemitraan.bast')"
                    :class="[
                        route().current('kemitraan.bast*')
                            ? 'bg-[#432386] text-white font-semibold shadow-md'
                            : 'text-slate-300 lg:hover:text-white lg:hover:bg-white/5',
                        'flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-150'
                    ]"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Berita Acara</span>
                </Link>

                <!-- Invoice -->
                <Link
                    :href="route('kemitraan.invoice')"
                    :class="[
                        route().current('kemitraan.invoice*')
                            ? 'bg-[#432386] text-white font-semibold shadow-md'
                            : 'text-slate-300 lg:hover:text-white lg:hover:bg-white/5',
                        'flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-150'
                    ]"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <span>Invoice</span>
                </Link>

                <!-- === Menu khusus Admin (ISP) === -->
                <template v-if="isAdmin">
                    <!-- Divider -->
                    <div class="my-3 px-3">
                        <div class="border-t border-slate-700/60"></div>
                    </div>

                    <p class="px-4 text-[10px] font-bold uppercase tracking-widest text-slate-500">Admin ISP</p>

                    <!-- Dashboard -->
                    <Link
                        :href="route('kemitraan.index')"
                        :class="[
                            route().current('kemitraan.index')
                                ? 'bg-[#432386] text-white font-semibold shadow-md'
                                : 'text-slate-300 lg:hover:text-white lg:hover:bg-white/5',
                            'flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-150'
                        ]"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span>Dashboard</span>
                    </Link>

                    <!-- Booking (Admin Only) -->
                    <Link
                        :href="route('kemitraan.booking')"
                        :class="[
                            route().current('kemitraan.booking*')
                                ? 'bg-[#432386] text-white font-semibold shadow-md'
                                : 'text-slate-300 lg:hover:text-white lg:hover:bg-white/5',
                            'flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-150'
                        ]"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span>Booking Mitra</span>
                    </Link>
                </template>

                <!-- Divider -->
                <div class="my-3 px-3">
                    <div class="border-t border-slate-700/60"></div>
                </div>

                <!-- Pengaturan -->
                <Link
                    :href="route().has('profile.edit') ? route('profile.edit') : (route().has('settings') ? route('settings') : '#')"
                    :class="[
                        route().current('profile*') || route().current('settings*')
                            ? 'bg-[#432386] text-white font-semibold shadow-md'
                            : 'text-slate-300 lg:hover:text-white lg:hover:bg-white/5',
                        'flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-150'
                    ]"
                >
                    <!-- Settings / Cog Icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span>Pengaturan</span>
                </Link>

                <!-- Logout (Khusus Mobile) -->
                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="lg:hidden flex items-center gap-3 px-4 py-2.5 mt-2 rounded-xl text-sm font-medium text-red-400 hover:bg-red-500/10 hover:text-red-300 transition-all duration-150 w-full text-left"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    <span>Keluar</span>
                </Link>
                <!-- Butuh Bantuan -->
                <div class="mt-auto pt-8 pb-4 lg:pb-0">
                    <div class="bg-[#1c0d38] p-4 rounded-2xl flex flex-col items-start shadow-inner border border-white/5">
                        <div class="p-2 bg-[#432386] rounded-xl mb-3">
                            <!-- Headset icon -->
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z" />
                            </svg>
                        </div>
                        <h4 class="text-sm font-bold text-white mb-1">Butuh bantuan?</h4>
                        <p class="text-xs text-slate-400 mb-3">Hubungi tim support kami</p>
                        <a :href="`https://wa.me/${$page.props.support_wa_number}?text=Halo%20Tim%20Support,%20saya%20butuh%20bantuan`" target="_blank" rel="noopener noreferrer" class="w-full py-2.5 bg-[#432386] hover:bg-[#5a31b5] text-white text-xs font-semibold rounded-lg transition-colors flex items-center justify-center gap-2">
                            <span>Hubungi Support</span>
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                        </a>
                    </div>
                </div>
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
                        <Link :href="route('kemitraan.index')" class="flex items-center">
                            <template v-if="$page.props.app_logo">
                                <img :src="$page.props.app_logo" class="h-6 max-w-full object-contain" alt="Logo" />
                            </template>
                            <template v-else>
                                <span class="text-lg font-bold text-indigo-700">Portal Kemitraan</span>
                            </template>
                        </Link>
                    </div>
                    <div class="flex items-center gap-2 sm:gap-3">
                        <button class="relative p-1.5 sm:p-2 text-slate-600 hover:bg-slate-100 rounded-full focus:outline-none">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                            <span class="absolute top-1 sm:top-1.5 right-1.5 sm:right-2 block h-2 w-2 sm:h-2.5 sm:w-2.5 rounded-full bg-red-500 border-2 border-white"></span>
                        </button>
                        <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-blue-600 text-white flex items-center justify-center text-xs sm:text-sm font-bold shadow-sm">
                            {{ $page.props.auth.user.name.substring(0,2).toUpperCase() }}
                        </div>
                        <Link :href="route('logout')" method="post" as="button" class="p-1.5 sm:p-2 text-red-500 hover:bg-red-50 rounded-full focus:outline-none">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 sm:h-6 sm:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                        </Link>
                    </div>
                </div>

                <!-- Desktop Topbar -->
                <div class="hidden lg:flex items-center justify-between w-full">
                    <div class="flex items-center gap-3 truncate">
                        <slot name="header" />
                    </div>

                    <!-- Middle: Search Box -->
                    <div class="flex-1 max-w-xl px-8 hidden xl:block">
                        <div class="relative w-full">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                            </div>
                            <input type="text" placeholder="Cari mitra atau area..." class="w-full pl-10 pr-16 py-2 bg-slate-100/70 border-none rounded-xl text-sm focus:ring-2 focus:ring-indigo-100 transition-all placeholder-slate-400">
                            <div class="absolute inset-y-0 right-0 pr-2 flex items-center pointer-events-none">
                                <span class="text-[10px] text-slate-400 font-medium px-2 py-0.5 bg-white rounded border border-slate-200">Ctrl + K</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-5">
                        <!-- Notification Dropdown -->
                        <div class="relative">
                            <button @click="showNotifications = !showNotifications" class="relative p-2 text-slate-500 hover:text-slate-700 transition-colors focus:outline-none">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                                <span v-if="notifications.length > 0" class="absolute top-1 right-1 flex h-4 w-4 items-center justify-center rounded-full bg-rose-500 text-[10px] font-bold text-white border-2 border-white">{{ notifications.length > 9 ? '9+' : notifications.length }}</span>
                            </button>
                            
                            <!-- Dropdown List -->
                            <div v-if="showNotifications" class="absolute right-0 mt-2 w-80 rounded-xl bg-white shadow-lg ring-1 ring-black ring-opacity-5 z-50 overflow-hidden">
                                <div class="px-4 py-3 border-b border-slate-100 bg-slate-50">
                                    <h3 class="text-sm font-semibold text-slate-700">Notifikasi</h3>
                                </div>
                                <div class="max-h-80 overflow-y-auto">
                                    <template v-if="notifications.length > 0">
                                        <div v-for="notif in notifications" :key="notif.id" @click="markAsRead(notif.id)" class="px-4 py-3 border-b border-slate-50 hover:bg-slate-50 cursor-pointer transition-colors">
                                            <div class="flex items-start">
                                                <div class="flex-shrink-0 mt-1">
                                                    <div class="h-2 w-2 rounded-full bg-rose-500"></div>
                                                </div>
                                                <div class="ml-3 w-0 flex-1">
                                                    <p class="text-sm font-medium text-slate-800">{{ notif.data.title }}</p>
                                                    <p class="mt-1 text-xs text-slate-500">{{ notif.data.message }}</p>
                                                    <p class="mt-1 text-[10px] text-slate-400">{{ new Date(notif.created_at).toLocaleString() }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                    <div v-else class="px-4 py-6 text-center text-sm text-slate-500">
                                        Tidak ada notifikasi baru.
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-if="showNotifications" @click="showNotifications = false" class="fixed inset-0 z-40"></div>

                        <div class="h-8 w-px bg-slate-200"></div>

                        <!-- Profile Dropdown Trigger (Dummy UI, using Logout directly to match functionality) -->
                        <div class="flex items-center gap-3 relative group">
                            <div class="w-9 h-9 rounded-full bg-blue-500 text-white flex items-center justify-center text-sm font-bold shadow-sm">
                                {{ $page.props.auth.user.name.substring(0,1).toUpperCase() }}
                            </div>
                            <div class="flex flex-col">
                                <span class="text-sm font-medium text-slate-700 group-hover:text-slate-900 transition-colors">
                                    Halo, <span class="font-bold">{{ $page.props.auth.user.name }}</span>
                                </span>
                                <span class="text-xs text-slate-500">Administrator</span>
                            </div>
                            
                            <Link
                                :href="route('logout')"
                                method="post"
                                as="button"
                                class="ml-2 text-slate-400 hover:text-red-500 transition-colors focus:outline-none p-1"
                                title="Keluar"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                            </Link>
                        </div>
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
            <nav class="lg:hidden print:hidden fixed bottom-0 w-full bg-white border-t border-slate-200 flex justify-around items-center pb-safe z-40 h-16 shadow-[0_-2px_10px_rgba(0,0,0,0.02)]">
                <Link :href="route('kemitraan.index')" :class="[route().current('kemitraan.index') ? 'text-blue-600' : 'text-slate-500', 'flex flex-col items-center justify-center w-full h-full space-y-1']">
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
