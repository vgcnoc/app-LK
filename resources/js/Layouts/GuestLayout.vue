<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen flex flex-col justify-center items-center bg-gradient-to-br from-indigo-600 via-indigo-700 to-purple-700 p-4 sm:p-6 relative overflow-hidden">
        <!-- Ambient Background Glow Accents -->
        <div class="absolute -top-32 -left-32 w-80 h-80 bg-purple-500/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-32 -right-32 w-80 h-80 bg-indigo-400/20 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Header: Logo & App Title -->
        <div class="relative z-10 mb-6 text-center">
            <Link href="/" class="inline-flex flex-col items-center group transition-transform duration-200 hover:scale-105">
                <div class="flex items-center justify-center w-16 h-16 rounded-2xl bg-white/15 backdrop-blur-md border border-white/25 shadow-lg mb-3 group-hover:bg-white/20 transition-all duration-200">
                    <!-- Wallet SVG Icon -->
                    <svg class="w-8 h-8 text-white drop-shadow" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                </div>
                <h1 class="text-2xl font-bold tracking-wider text-white uppercase drop-shadow-sm">
                    APP KEUANGAN
                </h1>
            </Link>
        </div>

        <!-- Glass-like Frosted Form Card -->
        <div class="relative z-10 w-full sm:max-w-md bg-white/90 backdrop-blur-xl border border-white/30 shadow-2xl rounded-2xl px-6 py-8 sm:px-8 sm:py-8 transition-all">
            <slot />
        </div>
    </div>
</template>
