<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    customers: {
        type: Array,
        default: () => [],
    }
});

const activeTab = ref('Berhenti sementara');

const filteredCustomers = computed(() => {
    return props.customers.filter(c => c.status_pelanggan === activeTab.value);
});

// Format Currency
const formatRupiah = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value);
};

// Format Date
const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    }).format(date);
};

// Edit Customer Modal
const isEditModalOpen = ref(false);
const editingCustomer = ref(null);

const editForm = useForm({
    name: '',
    area: '',
    alamat: '',
    paket: '',
    register_date: '',
    status_pelanggan: '',
    suspend_start_date: '',
    suspend_end_date: '',
    stop_date: '',
    amount: 0,
});

const openEditModal = (customer) => {
    editingCustomer.value = customer;
    editForm.name = customer.name || '';
    editForm.area = customer.area || '';
    editForm.alamat = customer.alamat || '';
    editForm.paket = customer.paket || '';
    editForm.register_date = customer.register_date || '';
    editForm.status_pelanggan = customer.status_pelanggan || 'Aktif';
    editForm.suspend_start_date = customer.suspend_start_date || '';
    editForm.suspend_end_date = customer.suspend_end_date || '';
    editForm.stop_date = customer.stop_date || '';
    editForm.amount = customer.amount || 0;
    editForm.clearErrors();
    isEditModalOpen.value = true;
};

const closeEditModal = () => {
    isEditModalOpen.value = false;
    setTimeout(() => {
        editingCustomer.value = null;
        editForm.reset();
    }, 200);
};

const submitEdit = () => {
    editForm.put(route('pelanggan.update', editingCustomer.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeEditModal();
        },
    });
};

const confirmDelete = (id) => {
    if (confirm('Apakah Anda yakin ingin menghapus pelanggan ini? Data transaksi yang terkait mungkin akan terpengaruh.')) {
        router.delete(route('pelanggan.destroy', id), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Pelanggan Non-Aktif" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-slate-800">
                        Pelanggan Non-Aktif
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Kelola data pelanggan yang berhenti sementara atau stop permanen.
                    </p>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                
                <!-- Tabs -->
                <div class="mb-6 flex space-x-1 rounded-xl bg-slate-200/50 p-1 w-full max-w-lg">
                    <button
                        @click="activeTab = 'Berhenti sementara'"
                        :class="[
                            'w-full rounded-lg py-2.5 text-sm font-medium leading-5 transition-all',
                            activeTab === 'Berhenti sementara'
                                ? 'bg-white text-indigo-700 shadow ring-1 ring-black/5'
                                : 'text-slate-600 hover:bg-white/50 hover:text-slate-900'
                        ]"
                    >
                        Berhenti Sementara
                    </button>
                    <button
                        @click="activeTab = 'Stop Permanen'"
                        :class="[
                            'w-full rounded-lg py-2.5 text-sm font-medium leading-5 transition-all',
                            activeTab === 'Stop Permanen'
                                ? 'bg-white text-indigo-700 shadow ring-1 ring-black/5'
                                : 'text-slate-600 hover:bg-white/50 hover:text-slate-900'
                        ]"
                    >
                        Stop Permanen
                    </button>
                    <button
                        @click="activeTab = 'Gratis'"
                        :class="[
                            'w-full rounded-lg py-2.5 text-sm font-medium leading-5 transition-all',
                            activeTab === 'Gratis'
                                ? 'bg-white text-indigo-700 shadow ring-1 ring-black/5'
                                : 'text-slate-600 hover:bg-white/50 hover:text-slate-900'
                        ]"
                    >
                        Gratis
                    </button>
                </div>

                <!-- Main Table Card -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50/50">
                                <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    <th scope="col" class="w-16 px-6 py-3.5 text-center">No</th>
                                    <th scope="col" class="px-6 py-3.5">Nama Pelanggan</th>
                                    <th scope="col" class="px-6 py-3.5">Area / Wilayah</th>
                                    <th scope="col" class="px-6 py-3.5">Alamat</th>
                                    <th scope="col" class="px-6 py-3.5">Nama Paket</th>
                                    
                                    <!-- Conditional Headers based on Tab -->
                                    <th v-if="activeTab === 'Berhenti sementara'" scope="col" class="px-6 py-3.5 text-center">Tanggal Mulai</th>
                                    <th v-if="activeTab === 'Berhenti sementara'" scope="col" class="px-6 py-3.5 text-center">Tanggal Selesai</th>
                                    <th v-if="activeTab === 'Stop Permanen'" scope="col" class="px-6 py-3.5 text-center">Tanggal Berhenti</th>
                                    <th v-if="activeTab === 'Gratis'" scope="col" class="px-6 py-3.5 text-center">Tanggal Register</th>
                                    
                                    <th scope="col" class="w-36 px-6 py-3.5 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <tr
                                    v-for="(customer, index) in filteredCustomers"
                                    :key="customer.id || index"
                                    class="transition-colors duration-150 hover:bg-slate-50/80"
                                >
                                    <!-- Row Number -->
                                    <td class="whitespace-nowrap px-4 py-4 text-center text-xs font-medium text-slate-400">
                                        {{ index + 1 }}
                                    </td>

                                    <!-- Customer Name -->
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-600 ring-1 ring-slate-200">
                                                {{ (customer.name || '?').charAt(0).toUpperCase() }}
                                            </div>
                                            <div>
                                                <div class="font-semibold text-slate-800">
                                                    {{ customer.name }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Area -->
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-600">
                                        {{ customer.area || '-' }}
                                    </td>

                                    <!-- Alamat -->
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-600">
                                        {{ customer.alamat || '-' }}
                                    </td>

                                    <!-- Paket -->
                                    <td class="whitespace-nowrap px-6 py-4 text-xs text-slate-600">
                                        {{ customer.paket || '-' }}
                                    </td>

                                    <!-- Conditional Dates -->
                                    <td v-if="activeTab === 'Berhenti sementara'" class="whitespace-nowrap px-6 py-4 text-center text-xs font-medium text-amber-700">
                                        {{ formatDate(customer.suspend_start_date) }}
                                    </td>
                                    <td v-if="activeTab === 'Berhenti sementara'" class="whitespace-nowrap px-6 py-4 text-center text-xs font-medium text-amber-700">
                                        {{ formatDate(customer.suspend_end_date) }}
                                    </td>
                                    
                                    <td v-if="activeTab === 'Stop Permanen'" class="whitespace-nowrap px-6 py-4 text-center text-xs font-medium text-rose-700">
                                        {{ formatDate(customer.stop_date) }}
                                    </td>

                                    <td v-if="activeTab === 'Gratis'" class="whitespace-nowrap px-6 py-4 text-center text-xs font-medium text-emerald-700">
                                        {{ formatDate(customer.register_date) }}
                                    </td>

                                    <!-- Actions -->
                                    <td class="whitespace-nowrap px-6 py-4 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <button
                                                @click="openEditModal(customer)"
                                                class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-indigo-600 focus:outline-none"
                                                title="Edit Data"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                                </svg>
                                            </button>
                                            <button
                                                @click="confirmDelete(customer.id)"
                                                class="rounded-lg p-1.5 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600 focus:outline-none"
                                                title="Hapus"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Empty State -->
                                <tr v-if="filteredCustomers.length === 0">
                                    <td colspan="8" class="px-6 py-12 text-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <h3 class="mt-2 text-sm font-medium text-slate-900">Belum Ada Data</h3>
                                        <p class="mt-1 text-sm text-slate-500">
                                            Tidak ada pelanggan dengan status ini.
                                        </p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

        <!-- Edit Customer Modal -->
        <div
            v-if="isEditModalOpen"
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title"
            role="dialog"
            aria-modal="true"
        >
            <div class="flex min-h-screen items-center justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" @click="closeEditModal" aria-hidden="true"></div>

                <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>

                <div
                    class="inline-block transform overflow-hidden rounded-2xl bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle"
                >
                    <div class="border-b border-slate-100 bg-white px-6 py-5 sm:px-6">
                        <h3 class="text-lg font-bold text-slate-800" id="modal-title">
                            Edit Pelanggan
                        </h3>
                        <p class="mt-1 text-xs text-slate-500">
                            Perbarui informasi data pelanggan.
                        </p>
                    </div>

                    <div class="p-6">
                        <form @submit.prevent="submitEdit">
                            <div class="space-y-4">
                                <!-- Name -->
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-600">
                                        Nama Pelanggan <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="editForm.name"
                                        type="text"
                                        class="w-full rounded-xl border border-slate-300 py-2.5 px-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                        required
                                    />
                                    <p v-if="editForm.errors.name" class="mt-1 text-xs text-rose-600">{{ editForm.errors.name }}</p>
                                </div>

                                <!-- Area -->
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-600">
                                        Area / Wilayah
                                    </label>
                                    <input
                                        v-model="editForm.area"
                                        type="text"
                                        class="w-full rounded-xl border border-slate-300 py-2.5 px-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                    />
                                    <p v-if="editForm.errors.area" class="mt-1 text-xs text-rose-600">{{ editForm.errors.area }}</p>
                                </div>

                                <!-- Alamat -->
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-600">
                                        Alamat Lengkap
                                    </label>
                                    <textarea
                                        v-model="editForm.alamat"
                                        rows="2"
                                        class="w-full rounded-xl border border-slate-300 py-2.5 px-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                    ></textarea>
                                    <p v-if="editForm.errors.alamat" class="mt-1 text-xs text-rose-600">{{ editForm.errors.alamat }}</p>
                                </div>

                                <!-- Paket -->
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-600">
                                        Nama Paket
                                    </label>
                                    <input
                                        v-model="editForm.paket"
                                        type="text"
                                        class="w-full rounded-xl border border-slate-300 py-2.5 px-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                    />
                                    <p v-if="editForm.errors.paket" class="mt-1 text-xs text-rose-600">{{ editForm.errors.paket }}</p>
                                </div>

                                <!-- Register Date -->
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-600">
                                        Tanggal Register
                                    </label>
                                    <input
                                        v-model="editForm.register_date"
                                        type="date"
                                        class="w-full rounded-xl border border-slate-300 py-2.5 px-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                    />
                                    <p v-if="editForm.errors.register_date" class="mt-1 text-xs text-rose-600">{{ editForm.errors.register_date }}</p>
                                </div>

                                <!-- Status Pelanggan -->
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-600">
                                        Status Pelanggan
                                    </label>
                                    <select
                                        v-model="editForm.status_pelanggan"
                                        class="w-full rounded-xl border border-slate-300 py-2.5 px-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                    >
                                        <option value="Aktif">Aktif</option>
                                        <option value="Berhenti sementara">Berhenti sementara</option>
                                        <option value="Stop Permanen">Stop Permanen</option>
                                        <option value="Gratis">Gratis</option>
                                    </select>
                                    <p v-if="editForm.errors.status_pelanggan" class="mt-1 text-xs text-rose-600">{{ editForm.errors.status_pelanggan }}</p>
                                </div>

                                <!-- Conditional Fields for Berhenti Sementara -->
                                <div v-if="editForm.status_pelanggan === 'Berhenti sementara'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-600">
                                            Tanggal Mulai
                                        </label>
                                        <input
                                            v-model="editForm.suspend_start_date"
                                            type="date"
                                            class="w-full rounded-xl border border-slate-300 py-2.5 px-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                        />
                                        <p v-if="editForm.errors.suspend_start_date" class="mt-1 text-xs text-rose-600">{{ editForm.errors.suspend_start_date }}</p>
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-600">
                                            Tanggal Selesai
                                        </label>
                                        <input
                                            v-model="editForm.suspend_end_date"
                                            type="date"
                                            class="w-full rounded-xl border border-slate-300 py-2.5 px-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                        />
                                        <p v-if="editForm.errors.suspend_end_date" class="mt-1 text-xs text-rose-600">{{ editForm.errors.suspend_end_date }}</p>
                                    </div>
                                </div>

                                <!-- Conditional Field for Stop Permanen -->
                                <div v-if="editForm.status_pelanggan === 'Stop Permanen'">
                                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-600">
                                        Tanggal Berhenti
                                    </label>
                                    <input
                                        v-model="editForm.stop_date"
                                        type="date"
                                        class="w-full rounded-xl border border-slate-300 py-2.5 px-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                    />
                                    <p v-if="editForm.errors.stop_date" class="mt-1 text-xs text-rose-600">{{ editForm.errors.stop_date }}</p>
                                </div>

                                <!-- Tagihan -->
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-600">
                                        Tagihan (Rp) <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="editForm.amount"
                                        type="number"
                                        min="0"
                                        class="w-full rounded-xl border border-slate-300 py-2.5 px-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                        required
                                    />
                                    <p v-if="editForm.errors.amount" class="mt-1 text-xs text-rose-600">{{ editForm.errors.amount }}</p>
                                </div>
                            </div>

                            <!-- Modal Actions -->
                            <div class="mt-6 flex items-center justify-end gap-3 pt-2">
                                <button
                                    type="button"
                                    @click="closeEditModal"
                                    class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    :disabled="editForm.processing"
                                    class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-indigo-700 hover:shadow focus:outline-none focus:ring-2 focus:ring-indigo-500/30 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <svg
                                        v-if="editForm.processing"
                                        class="h-3.5 w-3.5 animate-spin text-white"
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                    >
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                    </svg>
                                    <span>{{ editForm.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
