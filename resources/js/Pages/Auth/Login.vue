<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Masuk" />

    <div class="min-h-screen flex w-full bg-slate-50 font-sans">
        
        <!-- Left Panel: Brand & Features -->
        <div class="hidden lg:flex lg:w-1/2 relative bg-gradient-to-br from-[#2f3cb3] via-[#321d8b] to-[#0f0729] overflow-hidden flex-col justify-between p-12 text-white">
            
            <!-- Abstract Glow Effects -->
            <div class="absolute top-0 right-0 w-96 h-96 bg-purple-500/20 rounded-full blur-3xl -mr-20 -mt-20"></div>
            <div class="absolute bottom-40 left-0 w-80 h-80 bg-purple-500/20 rounded-full blur-3xl -ml-20"></div>
            
            <!-- Header -->
            <div class="relative z-10 flex justify-between items-start w-full">
                <!-- Logo -->
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center text-[#321d8b] font-bold text-2xl shadow-lg">
                        V
                    </div>
                    <div>
                        <h1 class="text-xl font-bold tracking-tight leading-tight">VIRUZS</h1>
                        <p class="text-[10px] tracking-widest text-purple-200 uppercase">ISP Billing System</p>
                    </div>
                </div>
                
                <!-- Tagline -->
                <div class="text-right">
                    <p class="text-xs text-purple-200 font-medium tracking-wide">Reliable Connection</p>
                    <div class="flex items-center justify-end gap-2 mt-0.5">
                        <p class="text-xs text-white font-semibold">Brighter Tomorrow</p>
                        <div class="w-6 h-px bg-purple-400"></div>
                    </div>
                </div>
            </div>

            <!-- Content Area -->
            <div class="relative z-10 mt-16 flex-grow max-w-xl">
                <h2 class="text-5xl font-bold leading-tight mb-6 tracking-tight drop-shadow-md">
                    Kelola Bisnis<br/>Internet Anda<br/>Lebih Mudah
                </h2>
                
                <p class="text-purple-100 text-base leading-relaxed mb-10 max-w-md">
                    V-Billing membantu Anda mengelola pelanggan, tagihan, pembayaran, dan jaringan dalam satu sistem yang terintegrasi.
                </p>
                
                <!-- Features List -->
                <div class="space-y-6">
                    <!-- Feature 1 -->
                    <div class="flex items-start gap-4 group">
                        <div class="w-12 h-12 rounded-xl bg-purple-500/20 flex items-center justify-center flex-shrink-0 border border-purple-400/30 group-hover:bg-purple-500/40 transition-colors shadow-lg shadow-purple-900/50">
                            <svg class="w-6 h-6 text-purple-300" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                        </div>
                        <div>
                            <h4 class="font-semibold text-white text-base">Manajemen Pelanggan</h4>
                            <p class="text-purple-200 text-xs sm:text-sm mt-0.5">Data pelanggan terpusat dan aman</p>
                        </div>
                    </div>
                    
                    <!-- Feature 2 -->
                    <div class="flex items-start gap-4 group">
                        <div class="w-12 h-12 rounded-xl bg-emerald-500/20 flex items-center justify-center flex-shrink-0 border border-emerald-400/30 group-hover:bg-emerald-500/40 transition-colors shadow-lg shadow-emerald-900/50">
                            <svg class="w-6 h-6 text-emerald-300" fill="currentColor" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
                        </div>
                        <div>
                            <h4 class="font-semibold text-white text-base">Billing & Tagihan</h4>
                            <p class="text-purple-200 text-xs sm:text-sm mt-0.5">Pembuatan tagihan otomatis</p>
                        </div>
                    </div>
                    
                    <!-- Feature 3 -->
                    <div class="flex items-start gap-4 group">
                        <div class="w-12 h-12 rounded-xl bg-amber-500/20 flex items-center justify-center flex-shrink-0 border border-amber-400/30 group-hover:bg-amber-500/40 transition-colors shadow-lg shadow-amber-900/50">
                            <svg class="w-6 h-6 text-amber-300" fill="currentColor" viewBox="0 0 24 24"><path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/></svg>
                        </div>
                        <div>
                            <h4 class="font-semibold text-white text-base">Multi Metode Pembayaran</h4>
                            <p class="text-purple-200 text-xs sm:text-sm mt-0.5">Mendukung berbagai payment gateway</p>
                        </div>
                    </div>
                    
                    <!-- Feature 4 -->
                    <div class="flex items-start gap-4 group">
                        <div class="w-12 h-12 rounded-xl bg-purple-500/20 flex items-center justify-center flex-shrink-0 border border-purple-400/30 group-hover:bg-purple-500/40 transition-colors shadow-lg shadow-purple-900/50">
                            <svg class="w-6 h-6 text-purple-300" fill="currentColor" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/></svg>
                        </div>
                        <div>
                            <h4 class="font-semibold text-white text-base">Laporan Lengkap</h4>
                            <p class="text-purple-200 text-xs sm:text-sm mt-0.5">Pantau pertumbuhan bisnis secara real-time</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Cityscape / Abstract wave graphic at bottom -->
            <div class="absolute bottom-0 left-0 w-full h-64 overflow-hidden pointer-events-none opacity-80 mix-blend-screen">
                <svg viewBox="0 0 1440 320" class="absolute bottom-0 w-full h-auto text-purple-900/50 fill-current" preserveAspectRatio="none"><path d="M0,256L48,229.3C96,203,192,149,288,154.7C384,160,480,224,576,218.7C672,213,768,139,864,128C960,117,1056,171,1152,197.3C1248,224,1344,224,1392,224L1440,224L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>
                <svg viewBox="0 0 1440 320" class="absolute bottom-0 w-full h-auto text-purple-900/60 fill-current opacity-70" preserveAspectRatio="none" style="transform: scaleX(-1);"><path d="M0,192L48,202.7C96,213,192,235,288,218.7C384,203,480,149,576,144C672,139,768,181,864,202.7C960,224,1056,224,1152,197.3C1248,171,1344,117,1392,90.7L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>
                
                <!-- Fake buildings -->
                <div class="absolute bottom-0 left-32 w-16 h-24 bg-purple-950/80 rounded-t-sm shadow-[0_0_15px_rgba(79,70,229,0.3)]"></div>
                <div class="absolute bottom-0 left-[18%] w-12 h-32 bg-purple-950/90 rounded-t-sm"></div>
                <div class="absolute bottom-0 left-[35%] w-24 h-20 bg-purple-950/70 rounded-t-sm shadow-[0_0_15px_rgba(79,70,229,0.2)]">
                    <div class="flex gap-2 mt-4 ml-4"><div class="w-2 h-2 bg-cyan-200 shadow-[0_0_5px_#a5f3fc]"></div><div class="w-2 h-2 bg-cyan-200 shadow-[0_0_5px_#a5f3fc]"></div></div>
                </div>
                <div class="absolute bottom-0 left-[50%] w-32 h-16 bg-purple-950/80 rounded-t-sm">
                    <div class="flex gap-2 mt-4 justify-center"><div class="w-2 h-2 bg-purple-300"></div><div class="w-2 h-2 bg-purple-300"></div><div class="w-2 h-2 bg-purple-300"></div></div>
                </div>
                
                <!-- Cell tower abstraction -->
                <div class="absolute bottom-0 right-[25%] w-0.5 h-48 bg-purple-500/50"></div>
                <div class="absolute bottom-24 right-[25%] -mr-6 w-12 h-px bg-purple-500/50"></div>
                <div class="absolute bottom-12 right-[25%] -mr-10 w-20 h-px bg-purple-500/50"></div>
                <div class="absolute bottom-40 right-[25%] -mr-0.5 w-1.5 h-1.5 rounded-full bg-cyan-400 shadow-[0_0_10px_#22d3ee] animate-pulse"></div>
                
                <!-- Connection dots -->
                <div class="absolute bottom-32 left-[30%] w-1.5 h-1.5 rounded-full bg-purple-400 shadow-[0_0_10px_#818cf8]"></div>
                <div class="absolute bottom-48 left-[45%] w-1.5 h-1.5 rounded-full bg-cyan-300 shadow-[0_0_10px_#67e8f9]"></div>
            </div>

            <!-- Footer Text -->
            <div class="relative z-10 text-purple-200 text-xs max-w-sm font-medium mt-12 italic">
                <p>" Koneksi lebih dekat untuk masa depan yang lebih baik "</p>
                <p class="mt-2 text-purple-300 not-italic">&mdash; PT Viruzs Global Connection</p>
            </div>
            
        </div>

        <!-- Right Panel: Login Form -->
        <div class="w-full lg:w-1/2 flex flex-col justify-center items-center p-6 sm:p-12 relative">
            
            <!-- Theme Toggle (Visual Only) -->
            <div class="absolute top-6 right-6 bg-white border border-slate-200 rounded-full p-1 shadow-sm flex items-center">
                <button class="w-8 h-8 rounded-full flex items-center justify-center bg-purple-50 text-purple-600 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                </button>
                <button class="w-8 h-8 rounded-full flex items-center justify-center text-slate-400 hover:text-slate-600 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" /></svg>
                </button>
            </div>

            <!-- Main Card -->
            <div class="w-full max-w-[460px] bg-white rounded-[2rem] shadow-2xl shadow-purple-100/50 p-8 sm:p-10 border border-slate-100 relative z-10">
                
                <!-- Logo & Badge -->
                <div class="flex items-center justify-between mb-10">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-purple-700 rounded-xl flex items-center justify-center text-white font-bold text-2xl shadow-md">
                            V
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-slate-800 tracking-tight leading-none">VIRUZS</h1>
                            <p class="text-[9px] tracking-widest text-slate-500 uppercase mt-0.5">ISP Billing System</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-semibold text-slate-400">v1.0.0</span>
                        <span class="px-2 py-1 bg-emerald-100 text-emerald-700 text-[10px] font-bold rounded-md uppercase tracking-wider">Stable Release</span>
                    </div>
                </div>

                <!-- Form Header -->
                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-slate-800 tracking-tight">Selamat Datang Kembali</h2>
                    <p class="mt-1.5 text-xs sm:text-sm text-slate-500 font-medium">Masuk ke akun Anda untuk melanjutkan</p>
                </div>

                <div v-if="status" class="mb-6 rounded-xl bg-emerald-50 p-4 text-xs sm:text-sm font-medium text-emerald-700 border border-emerald-200 flex items-center gap-3">
                    <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    {{ status }}
                </div>

                <!-- Login Form -->
                <form @submit.prevent="submit" class="space-y-5">
                    
                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-xs font-bold text-slate-700 mb-1.5">Alamat Email</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            </div>
                            <input 
                                id="email" 
                                type="email" 
                                v-model="form.email" 
                                required 
                                autofocus 
                                autocomplete="username"
                                class="block w-full pl-11 pr-10 py-3.5 sm:text-xs sm:text-sm border border-slate-300 rounded-xl focus:ring-purple-500 focus:border-purple-500 shadow-sm font-medium text-slate-800 placeholder-slate-400 transition-colors bg-white hover:bg-slate-50 focus:bg-white" 
                                placeholder="nama@email.com"
                            />
                            <div v-if="form.email" class="absolute inset-y-0 right-0 pr-4 flex items-center">
                                <button type="button" @click="form.email = ''" class="text-slate-300 hover:text-slate-500 focus:outline-none">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>
                                </button>
                            </div>
                        </div>
                        <p v-if="form.errors.email" class="mt-1.5 text-xs font-medium text-rose-500">{{ form.errors.email }}</p>
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-xs font-bold text-slate-700 mb-1.5">Kata Sandi</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            </div>
                            <input 
                                id="password" 
                                :type="showPassword ? 'text' : 'password'" 
                                v-model="form.password" 
                                required 
                                autocomplete="current-password"
                                class="block w-full pl-11 pr-10 py-3.5 sm:text-xs sm:text-sm border border-slate-300 rounded-xl focus:ring-purple-500 focus:border-purple-500 shadow-sm font-medium text-slate-800 placeholder-slate-400 tracking-wider transition-colors bg-white hover:bg-slate-50 focus:bg-white" 
                                placeholder="••••••••"
                            />
                            <div class="absolute inset-y-0 right-0 pr-4 flex items-center">
                                <button type="button" @click="showPassword = !showPassword" class="text-slate-400 hover:text-purple-600 focus:outline-none transition-colors">
                                    <svg v-if="!showPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                    <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                                </button>
                            </div>
                        </div>
                        <p v-if="form.errors.password" class="mt-1.5 text-xs font-medium text-rose-500">{{ form.errors.password }}</p>
                    </div>

                    <!-- Remember & Forgot -->
                    <div class="flex items-center justify-between pt-1">
                        <label class="flex items-center group cursor-pointer">
                            <div class="relative flex items-center justify-center w-5 h-5 mr-2">
                                <input type="checkbox" v-model="form.remember" class="w-5 h-5 border-2 border-slate-300 rounded text-purple-600 focus:ring-purple-500 transition-all cursor-pointer peer" />
                            </div>
                            <span class="text-xs sm:text-sm font-semibold text-slate-700 group-hover:text-purple-600 transition-colors">Ingat Saya</span>
                        </label>

                        <Link v-if="canResetPassword" :href="route('password.request')" class="text-xs sm:text-sm font-bold text-purple-600 hover:text-purple-800 transition-colors underline-offset-2 hover:underline">
                            Lupa kata sandi?
                        </Link>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-4">
                        <button 
                            type="submit" 
                            class="w-full flex justify-center items-center py-4 px-4 border border-transparent rounded-xl shadow-lg shadow-purple-500/30 text-xs sm:text-sm font-bold text-white bg-gradient-to-r from-purple-500 via-purple-600 to-purple-500 hover:from-purple-600 hover:via-purple-700 hover:to-purple-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-all transform hover:-translate-y-0.5"
                            :class="{ 'opacity-70 cursor-not-allowed': form.processing }"
                            :disabled="form.processing"
                        >
                            <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span>MASUK</span>
                            <svg v-if="!form.processing" class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </button>
                    </div>
                </form>

                <!-- Divider -->
                <div class="mt-8 mb-6 relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-slate-200"></div>
                    </div>
                    <div class="relative flex justify-center text-xs sm:text-sm">
                        <span class="px-3 bg-white text-slate-400 font-medium text-xs uppercase tracking-wider">atau masuk dengan</span>
                    </div>
                </div>

                <!-- Social Login -->
                <div class="grid grid-cols-2 gap-4">
                    <button type="button" class="flex justify-center items-center py-2.5 px-4 border border-slate-200 rounded-xl shadow-sm bg-white text-xs sm:text-sm font-semibold text-slate-700 hover:bg-slate-50 hover:border-slate-300 transition-all focus:ring-2 focus:ring-slate-200">
                        <svg class="h-5 w-5 mr-2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
                        Google
                    </button>
                    <button type="button" class="flex justify-center items-center py-2.5 px-4 border border-slate-200 rounded-xl shadow-sm bg-white text-xs sm:text-sm font-semibold text-slate-700 hover:bg-slate-50 hover:border-slate-300 transition-all focus:ring-2 focus:ring-slate-200">
                        <svg class="h-5 w-5 mr-2" viewBox="0 0 21 21" xmlns="http://www.w3.org/2000/svg"><path fill="#f25022" d="M1 1h9v9H1z"/><path fill="#00a4ef" d="M1 11h9v9H1z"/><path fill="#7fba00" d="M11 1h9v9h-9z"/><path fill="#ffb900" d="M11 11h9v9h-9z"/></svg>
                        Microsoft
                    </button>
                </div>

                <!-- Registration Link -->
                <div class="mt-8 text-center space-y-2">
                    <p class="text-xs sm:text-sm text-slate-500 font-medium">
                        Ingin bergabung sebagai Mitra/Sales? 
                        <Link :href="route('register.affiliate')" class="font-bold text-purple-600 hover:text-purple-800 transition-colors underline-offset-2 hover:underline">Daftar Disini</Link>
                    </p>
                    <p class="text-xs sm:text-sm text-slate-500 font-medium">
                        Atau bergabung sebagai Partner Kemitraan? 
                        <Link :href="route('register.kemitraan')" class="font-bold text-purple-600 hover:text-purple-800 transition-colors underline-offset-2 hover:underline">Daftar Kemitraan</Link>
                    </p>
                </div>
            </div>
            
            <!-- Page Footer (Right Side) -->
            <div class="absolute bottom-6 w-full px-12 flex flex-col sm:flex-row justify-between items-center text-[10px] sm:text-xs text-slate-400 font-medium gap-2">
                <div class="text-center sm:text-left">
                    <p>&copy; 2026 V-Billing. All rights reserved.</p>
                    <p class="mt-0.5">PT Viruzs Global Connection</p>
                </div>
                <div class="flex gap-4">
                    <a href="#" class="hover:text-slate-600 transition-colors">Kebijakan Privasi</a>
                    <span>|</span>
                    <a href="#" class="hover:text-slate-600 transition-colors">Syarat & Ketentuan</a>
                    <span>|</span>
                    <a href="#" class="hover:text-slate-600 transition-colors">Bantuan</a>
                </div>
            </div>
        </div>
    </div>
</template>

