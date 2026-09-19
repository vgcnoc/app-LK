<script setup>
import { ref } from 'vue';
import { useForm, router, usePage } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    users: {
        type: Array,
        required: true,
    },
    roles: {
        type: Array,
        required: true,
    }
});

const isModalOpen = ref(false);
const editingUser = ref(null);

const form = useForm({
    name: '',
    email: '',
    password: '',
    role: 'staff',
});

const openCreateModal = () => {
    editingUser.value = null;
    form.reset();
    form.clearErrors();
    isModalOpen.value = true;
};

const openEditModal = (user) => {
    editingUser.value = user;
    form.name = user.name;
    form.email = user.email;
    form.password = '';
    form.role = user.user_role || user.role || 'staff';
    form.clearErrors();
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
};

const submit = () => {
    if (editingUser.value) {
        form.put(route('users.update', editingUser.value.id), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('users.store'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
};

const deleteUser = (user) => {
    if (confirm(`Yakin ingin menghapus pengguna "${user.name}"?`)) {
        router.delete(route('users.destroy', user.id), {
            preserveScroll: true,
        });
    }
};

const authUser = usePage().props.auth.user;
const authPermissions = usePage().props.auth.permissions || [];
const canManage = authPermissions.includes('manajemen_pengguna');
</script>

<template>
    <section v-if="canManage">
        <header class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-800 flex items-center gap-2">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-indigo-100 text-indigo-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    </span>
                    Manajemen Pengguna
                </h2>
                <p class="mt-1 text-sm text-slate-500">
                    Kelola akun dan tetapkan role untuk setiap pengguna.
                </p>
            </div>
            <button
                @click="openCreateModal"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 border border-transparent rounded-xl font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all duration-200 shadow-md hover:shadow-lg"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                Tambah Akun
            </button>
        </header>

        <div class="mt-6 overflow-x-auto rounded-xl border border-slate-200 shadow-sm">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3.5 text-left font-semibold text-slate-600 uppercase tracking-wider text-xs">Nama</th>
                        <th class="px-6 py-3.5 text-left font-semibold text-slate-600 uppercase tracking-wider text-xs">Email</th>
                        <th class="px-6 py-3.5 text-left font-semibold text-slate-600 uppercase tracking-wider text-xs">Role</th>
                        <th class="px-6 py-3.5 text-right font-semibold text-slate-600 uppercase tracking-wider text-xs">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-100">
                    <tr v-for="user in users" :key="user.id" class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap font-medium text-slate-800">{{ user.name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-slate-600">{{ user.email }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span :class="{
                                'bg-indigo-100 text-indigo-700 ring-1 ring-indigo-600/20': user.user_role === 'admin' || user.role === 'admin',
                                'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-600/20': user.user_role !== 'admin' && user.role !== 'admin'
                            }" class="px-2.5 py-1 inline-flex text-xs leading-5 font-bold rounded-full uppercase">
                                {{ user.user_role || user.role || 'staff' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right font-medium text-sm space-x-2">
                            <button @click="openEditModal(user)" class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-800 font-semibold transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                Edit
                            </button>
                            <button @click="deleteUser(user)" class="inline-flex items-center gap-1 text-red-600 hover:text-red-800 font-semibold transition-colors" v-if="user.id !== authUser.id">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                Hapus
                            </button>
                        </td>
                    </tr>
                    <tr v-if="!users || users.length === 0">
                        <td colspan="4" class="px-6 py-8 text-center text-slate-400">
                            <svg class="w-10 h-10 mx-auto mb-2 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                            Belum ada akun lain
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modal -->
        <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="closeModal"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-slate-200 flex-shrink-0">
                    <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg" :class="editingUser ? 'bg-amber-100 text-amber-600' : 'bg-green-100 text-green-600'">
                            <svg v-if="editingUser" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                        </span>
                        {{ editingUser ? 'Edit Akun' : 'Tambah Akun Baru' }}
                    </h3>
                </div>
                
                <!-- Modal Body (scrollable) -->
                <div class="overflow-y-auto flex-1 px-6 py-5">
                    <form @submit.prevent="submit" class="space-y-5">
                        <!-- Basic Info -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block font-semibold text-sm text-slate-700 mb-1.5">Nama</label>
                                <input type="text" v-model="form.name" class="block w-full border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm bg-slate-50 focus:bg-white transition-all px-3.5 py-2.5" required placeholder="Nama lengkap" />
                                <InputError :message="form.errors.name" class="mt-1" />
                            </div>
                            <div>
                                <label class="block font-semibold text-sm text-slate-700 mb-1.5">Email</label>
                                <input type="email" v-model="form.email" class="block w-full border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm bg-slate-50 focus:bg-white transition-all px-3.5 py-2.5" required placeholder="email@domain.com" />
                                <InputError :message="form.errors.email" class="mt-1" />
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block font-semibold text-sm text-slate-700 mb-1.5">Role</label>
                                <select v-model="form.role" class="block w-full border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm bg-slate-50 focus:bg-white transition-all px-3.5 py-2.5">
                                    <option v-for="role in roles" :key="role.id" :value="role.name">{{ role.name.charAt(0).toUpperCase() + role.name.slice(1) }}</option>
                                </select>
                                <InputError :message="form.errors.role" class="mt-1" />
                            </div>
                            <div>
                                <label class="block font-semibold text-sm text-slate-700 mb-1.5">Password {{ editingUser ? '(Kosongkan jika tidak diubah)' : '' }}</label>
                                <input type="password" v-model="form.password" class="block w-full border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm bg-slate-50 focus:bg-white transition-all px-3.5 py-2.5" :required="!editingUser" placeholder="••••••••" />
                                <InputError :message="form.errors.password" class="mt-1" />
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 border-t border-slate-200 flex items-center justify-end gap-3 flex-shrink-0 bg-slate-50">
                    <button type="button" @click="closeModal" class="px-4 py-2.5 bg-white border border-slate-300 rounded-xl font-semibold text-xs text-slate-700 uppercase tracking-widest shadow-sm hover:bg-slate-50 transition">
                        Batal
                    </button>
                    <button @click="submit" :disabled="form.processing" class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 border border-transparent rounded-xl font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 transition-all shadow-md">
                        <svg v-if="form.processing" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                    </button>
                </div>
            </div>
        </div>
    </section>
</template>
