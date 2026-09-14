<script setup>
import { ref } from 'vue';
import { useForm, router, usePage } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    users: {
        type: Array,
        required: true,
    },
});

const isModalOpen = ref(false);
const editingUser = ref(null);

const form = useForm({
    name: '',
    email: '',
    password: '',
    role: 'admin',
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
    form.role = user.role || 'admin';
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
const isAdmin = authUser.role === 'admin';
</script>

<template>
    <section v-if="isAdmin">
        <header class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-medium text-gray-900">Manajemen Pengguna (Role Akses)</h2>
                <p class="mt-1 text-sm text-gray-600">
                    Kelola akun dan tentukan role (admin/staff) untuk membatasi akses.
                </p>
            </div>
            <button
                @click="openCreateModal"
                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150"
            >
                Tambah Akun
            </button>
        </header>

        <div class="mt-6 overflow-x-auto rounded-lg border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                        <th class="px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-right font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="user in users" :key="user.id">
                        <td class="px-6 py-4 whitespace-nowrap">{{ user.name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ user.email }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span :class="{'bg-indigo-100 text-indigo-800': user.role === 'admin', 'bg-emerald-100 text-emerald-800': user.role === 'staff'}" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full uppercase">
                                {{ user.role || 'admin' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right font-medium text-sm">
                            <button @click="openEditModal(user)" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</button>
                            <button @click="deleteUser(user)" class="text-red-600 hover:text-red-900" v-if="user.id !== authUser.id">Hapus</button>
                        </td>
                    </tr>
                    <tr v-if="!users || users.length === 0">
                        <td colspan="4" class="px-6 py-4 text-center text-gray-500">Belum ada akun lain</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modal -->
        <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="closeModal"></div>
            <div class="relative bg-white rounded-xl shadow-xl w-full max-w-md p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">{{ editingUser ? 'Edit Akun' : 'Tambah Akun' }}</h3>
                
                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="block font-medium text-sm text-gray-700">Nama</label>
                        <input type="text" v-model="form.name" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required />
                        <InputError :message="form.errors.name" class="mt-2" />
                    </div>
                    <div>
                        <label class="block font-medium text-sm text-gray-700">Email</label>
                        <input type="email" v-model="form.email" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required />
                        <InputError :message="form.errors.email" class="mt-2" />
                    </div>
                    <div>
                        <label class="block font-medium text-sm text-gray-700">Role Akses</label>
                        <select v-model="form.role" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="admin">Admin (Akses Penuh)</option>
                            <option value="staff">Staff (Terbatas)</option>
                        </select>
                        <InputError :message="form.errors.role" class="mt-2" />
                    </div>
                    <div>
                        <label class="block font-medium text-sm text-gray-700">Password {{ editingUser ? '(Kosongkan jika tidak diubah)' : '' }}</label>
                        <input type="password" v-model="form.password" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" :required="!editingUser" />
                        <InputError :message="form.errors.password" class="mt-2" />
                    </div>

                    <div class="flex items-center justify-end mt-6 gap-3">
                        <button type="button" @click="closeModal" class="px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 disabled:opacity-25 transition">
                            Batal
                        </button>
                        <button type="submit" :disabled="form.processing" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition">
                            {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</template>
