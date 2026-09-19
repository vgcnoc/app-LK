<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    areas: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    name: '',
    email: '',
    phone: '',
    area: '',
    upline_code: '',
    password: '',
    password_confirmation: '',
});

const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

const submit = () => {
    form.post(route('register.affiliate'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Pendaftaran Affiliate / Sales" />

    <div class="min-h-screen flex w-full bg-slate-50 font-sans">
        
        <!-- Left Panel: Brand & Features -->
        <div class="hidden lg:flex lg:w-1/2 relative bg-gradient-to-br from-[#2f3cb3] via-[#321d8b] to-[#0f0729] overflow-hidden flex-col justify-between p-12 text-white">
            <!-- Same Abstract Glow Effects as Login -->
            <div class="absolute top-0 right-0 w-96 h-96 bg-indigo-500/20 rounded-full blur-3xl -mr-20 -mt-20"></div>
            <div class="absolute bottom-40 left-0 w-80 h-80 bg-purple-500/20 rounded-full blur-3xl -ml-20"></div>
            
            <!-- Header -->
            <div class="relative z-10 flex justify-between items-start w-full">
                <!-- Logo -->
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center text-[#321d8b] font-bold text-2xl shadow-lg">V</div>
                    <div>
                        <h1 class="text-xl font-bold tracking-tight leading-tight">VIRUZS</h1>
                        <p class="text-[10px] tracking-widest text-indigo-200 uppercase">Affiliate Program</p>
                    </div>
                </div>
            </div>

            <!-- Content Area -->
            <div class="relative z-10 mt-16 flex-grow max-w-xl">
                <h2 class="text-5xl font-bold leading-tight mb-6 tracking-tight drop-shadow-md">
                    Bergabung<br/>Bersama Kami,<br/>Raih Kesuksesan
                </h2>
                
                <p class="text-indigo-100 text-base leading-relaxed mb-10 max-w-md">
                    Program Mitra Viruzs memberikan peluang penghasilan tambahan tanpa batas dengan memasarkan layanan internet terbaik kami.
                </p>
                
                <div class="space-y-6">
                    <div class="flex items-start gap-4 group">
                        <div class="w-12 h-12 rounded-xl bg-purple-500/20 flex items-center justify-center flex-shrink-0 border border-purple-400/30 group-hover:bg-purple-500/40 transition-colors shadow-lg shadow-purple-900/50">
                            <svg class="w-6 h-6 text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div>
                            <h4 class="font-semibold text-white text-base">Komisi Berjenjang</h4>
                            <p class="text-indigo-200 text-sm mt-0.5">Dapatkan komisi dari penjualan langsung dan downline Anda</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4 group">
                        <div class="w-12 h-12 rounded-xl bg-emerald-500/20 flex items-center justify-center flex-shrink-0 border border-emerald-400/30 group-hover:bg-emerald-500/40 transition-colors shadow-lg shadow-emerald-900/50">
                            <svg class="w-6 h-6 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                        </div>
                        <div>
                            <h4 class="font-semibold text-white text-base">Penghasilan Pasif</h4>
                            <p class="text-indigo-200 text-sm mt-0.5">Komisi bulanan berulang selama pelanggan aktif berlangganan</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Background Elements -->
            <div class="absolute bottom-0 left-0 w-full h-64 overflow-hidden pointer-events-none opacity-80 mix-blend-screen">
                <svg viewBox="0 0 1440 320" class="absolute bottom-0 w-full h-auto text-indigo-900/50 fill-current" preserveAspectRatio="none"><path d="M0,256L48,229.3C96,203,192,149,288,154.7C384,160,480,224,576,218.7C672,213,768,139,864,128C960,117,1056,171,1152,197.3C1248,224,1344,224,1392,224L1440,224L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>
            </div>
            
            <div class="relative z-10 text-indigo-200 text-xs max-w-sm font-medium mt-12 italic">
                <p>" Tumbuh dan sukses bersama jaringan internet tercepat "</p>
                <p class="mt-2 text-indigo-300 not-italic">&mdash; PT Viruzs Global Connection</p>
            </div>
        </div>

        <!-- Right Panel: Registration Form -->
        <div class="w-full lg:w-1/2 flex flex-col justify-center items-center p-6 sm:p-12 relative overflow-y-auto">
            
            <div class="w-full max-w-[500px] bg-white rounded-[2rem] shadow-2xl shadow-indigo-100/50 p-8 sm:p-10 border border-slate-100 relative z-10 my-8">
                
                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-slate-800 tracking-tight">Pendaftaran Mitra Sales</h2>
                    <p class="mt-1.5 text-sm text-slate-500 font-medium">Lengkapi data diri Anda di bawah ini</p>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    
                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-xs font-bold text-slate-700 mb-1.5">Nama Lengkap *</label>
                        <input id="name" type="text" v-model="form.name" required class="block w-full py-3 px-4 sm:text-sm border border-slate-300 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 shadow-sm font-medium text-slate-800 transition-colors bg-white hover:bg-slate-50 focus:bg-white" placeholder="Nama lengkap sesuai KTP" />
                        <p v-if="form.errors.name" class="mt-1.5 text-xs font-medium text-rose-500">{{ form.errors.name }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-xs font-bold text-slate-700 mb-1.5">Email *</label>
                            <input id="email" type="email" v-model="form.email" required class="block w-full py-3 px-4 sm:text-sm border border-slate-300 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 shadow-sm font-medium text-slate-800 transition-colors bg-white hover:bg-slate-50 focus:bg-white" placeholder="nama@email.com" />
                            <p v-if="form.errors.email" class="mt-1.5 text-xs font-medium text-rose-500">{{ form.errors.email }}</p>
                        </div>
                        
                        <!-- Phone -->
                        <div>
                            <label for="phone" class="block text-xs font-bold text-slate-700 mb-1.5">No. WhatsApp</label>
                            <input id="phone" type="text" v-model="form.phone" class="block w-full py-3 px-4 sm:text-sm border border-slate-300 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 shadow-sm font-medium text-slate-800 transition-colors bg-white hover:bg-slate-50 focus:bg-white" placeholder="0812xxxx" />
                            <p v-if="form.errors.phone" class="mt-1.5 text-xs font-medium text-rose-500">{{ form.errors.phone }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <!-- Area -->
                        <div>
                            <label for="area" class="block text-xs font-bold text-slate-700 mb-1.5">Area (Opsional)</label>
                            <select id="area" v-model="form.area" class="block w-full py-3 px-4 sm:text-sm border border-slate-300 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 shadow-sm font-medium text-slate-800 transition-colors bg-white hover:bg-slate-50 focus:bg-white">
                                <option value="">-- Pilih Area --</option>
                                <option v-for="a in areas" :key="a" :value="a">{{ a }}</option>
                            </select>
                            <p v-if="form.errors.area" class="mt-1.5 text-xs font-medium text-rose-500">{{ form.errors.area }}</p>
                        </div>

                        <!-- Upline Code -->
                        <div>
                            <label for="upline_code" class="block text-xs font-bold text-slate-700 mb-1.5">Kode Referral (Opsional)</label>
                            <input id="upline_code" type="text" v-model="form.upline_code" class="block w-full py-3 px-4 sm:text-sm border border-slate-300 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 shadow-sm font-medium text-slate-800 transition-colors bg-white hover:bg-slate-50 focus:bg-white" placeholder="Contoh: SL-xxx-001" />
                            <p v-if="form.errors.upline_code" class="mt-1.5 text-xs font-medium text-rose-500">{{ form.errors.upline_code }}</p>
                        </div>
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-xs font-bold text-slate-700 mb-1.5">Kata Sandi (Minimal 8 karakter) *</label>
                        <div class="relative">
                            <input id="password" :type="showPassword ? 'text' : 'password'" v-model="form.password" required class="block w-full py-3 pl-4 pr-10 sm:text-sm border border-slate-300 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 shadow-sm font-medium text-slate-800 transition-colors bg-white hover:bg-slate-50 focus:bg-white" placeholder="••••••••" />
                            <div class="absolute inset-y-0 right-0 pr-4 flex items-center">
                                <button type="button" @click="showPassword = !showPassword" class="text-slate-400 hover:text-indigo-600 focus:outline-none">
                                    <svg v-if="!showPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                    <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                                </button>
                            </div>
                        </div>
                        <p v-if="form.errors.password" class="mt-1.5 text-xs font-medium text-rose-500">{{ form.errors.password }}</p>
                    </div>

                    <!-- Password Confirmation -->
                    <div>
                        <label for="password_confirmation" class="block text-xs font-bold text-slate-700 mb-1.5">Konfirmasi Kata Sandi *</label>
                        <div class="relative">
                            <input id="password_confirmation" :type="showPasswordConfirmation ? 'text' : 'password'" v-model="form.password_confirmation" required class="block w-full py-3 pl-4 pr-10 sm:text-sm border border-slate-300 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 shadow-sm font-medium text-slate-800 transition-colors bg-white hover:bg-slate-50 focus:bg-white" placeholder="••••••••" />
                            <div class="absolute inset-y-0 right-0 pr-4 flex items-center">
                                <button type="button" @click="showPasswordConfirmation = !showPasswordConfirmation" class="text-slate-400 hover:text-indigo-600 focus:outline-none">
                                    <svg v-if="!showPasswordConfirmation" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                    <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                                </button>
                            </div>
                        </div>
                        <p v-if="form.errors.password_confirmation" class="mt-1.5 text-xs font-medium text-rose-500">{{ form.errors.password_confirmation }}</p>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-4">
                        <button 
                            type="submit" 
                            class="w-full flex justify-center items-center py-4 px-4 border border-transparent rounded-xl shadow-lg shadow-indigo-500/30 text-sm font-bold text-white bg-gradient-to-r from-indigo-500 via-indigo-600 to-blue-500 hover:from-indigo-600 hover:via-indigo-700 hover:to-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all transform hover:-translate-y-0.5"
                            :class="{ 'opacity-70 cursor-not-allowed': form.processing }"
                            :disabled="form.processing"
                        >
                            <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span>DAFTAR SEKARANG</span>
                        </button>
                    </div>
                </form>

                <!-- Registration Link -->
                <div class="mt-8 text-center">
                    <p class="text-sm text-slate-500 font-medium">
                        Sudah punya akun? 
                        <Link :href="route('login')" class="font-bold text-indigo-600 hover:text-indigo-800 transition-colors underline-offset-2 hover:underline">Masuk Disini</Link>
                    </p>
                </div>
            </div>
            
        </div>
    </div>
</template>
