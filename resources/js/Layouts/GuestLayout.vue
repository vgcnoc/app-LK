<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-slate-50 relative overflow-hidden">
        <!-- Abstract Background Shapes -->
        <div class="absolute inset-0 z-0">
            <div class="absolute top-0 left-0 w-full h-96 bg-gradient-to-b from-indigo-600 to-indigo-900 rounded-b-[4rem] shadow-xl"></div>
            <!-- Decorative circles -->
            <div class="absolute top-10 left-10 w-32 h-32 bg-white/10 rounded-full blur-2xl"></div>
            <div class="absolute top-40 right-20 w-48 h-48 bg-indigo-400/20 rounded-full blur-3xl"></div>
        </div>

        <div class="relative z-10 w-full sm:max-w-md mt-10">
            <!-- Header: Logo & App Title -->
            <div class="mb-8 text-center">
                <Link href="/" class="inline-flex flex-col items-center group transition-transform duration-300 hover:-translate-y-1">
                    <div class="flex items-center justify-center w-16 h-16 rounded-2xl bg-white shadow-lg mb-4 text-indigo-600 group-hover:shadow-indigo-500/30 transition-all duration-300 overflow-hidden">
                        <!-- Custom Logo from Settings -->
                        <img v-if="$page.props.app_logo" :src="$page.props.app_logo" class="w-12 h-12 object-contain" alt="Logo" />
                        
                        <!-- Default SVG Icon -->
                        <svg v-else class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <h1 class="text-3xl font-extrabold tracking-tight text-white drop-shadow-md">
                        V-Billing
                    </h1>
                </Link>
            </div>

            <!-- Card -->
            <div class="w-full bg-white/90 backdrop-blur-lg shadow-2xl rounded-3xl px-8 py-10 border border-white/50 overflow-hidden relative">
                <!-- Inner glow/accent -->
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-indigo-500 via-purple-500 to-indigo-500"></div>
                <slot />
            </div>
            
            <p class="mt-8 text-center text-sm text-slate-500 font-medium">
                &copy; {{ new Date().getFullYear() }} V-Billing. All rights reserved.
            </p>
        </div>
    </div>
</template>
