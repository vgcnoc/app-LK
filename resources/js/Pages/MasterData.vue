<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref } from 'vue';

const props = defineProps({
    expenseCategories: {
        type: Array,
        default: () => [],
    },
    paymentMethods: {
        type: Array,
        default: () => [],
    }
});

// Form Kategori Pengeluaran
const form = useForm({
    name: ''
});

const submit = () => {
    form.post(route('expense-categories.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};

const deleteCategory = (category) => {
    if (confirm(`Apakah Anda yakin ingin menghapus kategori "${category.name}"?`)) {
        router.delete(route('expense-categories.destroy', category.id), {
            preserveScroll: true,
        });
    }
};

// Form Metode Pembayaran
const pmForm = useForm({
    name: ''
});

const submitPm = () => {
    pmForm.post(route('payment-methods.store'), {
        preserveScroll: true,
        onSuccess: () => pmForm.reset(),
    });
};

const deletePaymentMethod = (method) => {
    if (confirm(`Apakah Anda yakin ingin menghapus metode pembayaran "${method.name}"?`)) {
        router.delete(route('payment-methods.destroy', method.id), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Master Data" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-2xl font-bold tracking-tight text-slate-800">
                    Master Data
                </h2>
                <p class="text-sm text-slate-500">
                    Kelola data master seperti Kategori Pengeluaran.
                </p>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Kategori Pengeluaran Section -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 overflow-hidden">
                        <div class="p-6 border-b border-slate-100">
                            <h3 class="text-lg font-bold text-slate-800">Kategori Pengeluaran</h3>
                            <p class="text-xs text-slate-500 mt-1">Tambahkan kategori untuk mengelompokkan pengeluaran Anda.</p>
                        </div>
                        
                        <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                            <form @submit.prevent="submit" class="flex gap-3 items-start">
                                <div class="flex-1">
                                    <input 
                                        type="text" 
                                        v-model="form.name" 
                                        placeholder="Nama Kategori (contoh: Operasional, Gaji, dll)"
                                        required
                                        class="w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                                    />
                                    <p v-if="form.errors.name" class="mt-1 text-xs text-rose-600">{{ form.errors.name }}</p>
                                </div>
                                <button 
                                    type="submit" 
                                    :disabled="form.processing"
                                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-sm font-semibold shadow-sm transition-colors disabled:opacity-50"
                                >
                                    Tambah
                                </button>
                            </form>
                        </div>

                        <div class="p-0">
                            <table class="w-full text-left text-sm">
                                <thead>
                                    <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold uppercase text-slate-500">
                                        <th class="px-6 py-3">Nama Kategori</th>
                                        <th class="px-6 py-3 w-24 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="cat in expenseCategories" :key="cat.id" class="hover:bg-slate-50/80">
                                        <td class="px-6 py-3 font-medium text-slate-700">
                                            {{ cat.name }}
                                        </td>
                                        <td class="px-6 py-3 text-right">
                                            <button 
                                                @click="deleteCategory(cat)"
                                                class="text-rose-500 hover:text-rose-700 hover:bg-rose-50 p-1.5 rounded-lg transition-colors"
                                                title="Hapus Kategori"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="expenseCategories.length === 0">
                                        <td colspan="2" class="px-6 py-8 text-center text-slate-400">
                                            Belum ada kategori pengeluaran.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Master Metode Pembayaran Section -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 overflow-hidden">
                        <div class="p-6 border-b border-slate-100">
                            <h3 class="text-lg font-bold text-slate-800">Metode Pembayaran</h3>
                            <p class="text-xs text-slate-500 mt-1">Kelola daftar metode pembayaran (contoh: Tunai, Transfer BCA, OVO, dll).</p>
                        </div>
                        
                        <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                            <form @submit.prevent="submitPm" class="flex gap-3 items-start">
                                <div class="flex-1">
                                    <input 
                                        type="text" 
                                        v-model="pmForm.name" 
                                        placeholder="Nama Metode Pembayaran"
                                        required
                                        class="w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                                    />
                                    <p v-if="pmForm.errors.name" class="mt-1 text-xs text-rose-600">{{ pmForm.errors.name }}</p>
                                </div>
                                <button 
                                    type="submit" 
                                    :disabled="pmForm.processing"
                                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-sm font-semibold shadow-sm transition-colors disabled:opacity-50"
                                >
                                    Tambah
                                </button>
                            </form>
                        </div>

                        <div class="p-0">
                            <table class="w-full text-left text-sm">
                                <thead>
                                    <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold uppercase text-slate-500">
                                        <th class="px-6 py-3">Nama Metode</th>
                                        <th class="px-6 py-3 w-24 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="pm in paymentMethods" :key="pm.id" class="hover:bg-slate-50/80">
                                        <td class="px-6 py-3 font-medium text-slate-700">
                                            {{ pm.name }}
                                        </td>
                                        <td class="px-6 py-3 text-right">
                                            <button 
                                                @click="deletePaymentMethod(pm)"
                                                class="text-rose-500 hover:text-rose-700 hover:bg-rose-50 p-1.5 rounded-lg transition-colors"
                                                title="Hapus Metode"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="paymentMethods.length === 0">
                                        <td colspan="2" class="px-6 py-8 text-center text-slate-400">
                                            Belum ada metode pembayaran.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
