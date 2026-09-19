<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import ManageUsers from './Partials/ManageUsers.vue';
import ManageRoles from './Partials/ManageRoles.vue';
import UpdateAppLogoForm from './Partials/UpdateAppLogoForm.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputError from '@/Components/InputError.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    mustVerifyEmail: { type: Boolean },
    status: { type: String },
    users: { type: Array, default: () => [] },
    allPermissions: { type: Array, default: () => [] },
    roles: { type: Array, default: () => [] },
});

const authPermissions = usePage().props.auth.permissions || [];
const canManageUsers = authPermissions.includes('manajemen_pengguna');
</script>

<template>
    <Head title="Profile" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Pengaturan & Profil</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Breeze Default Sections -->
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        class="max-w-xl"
                    />
                </div>

                <!-- App Logo Update Section (Admin Only) -->
                <div v-if="canManageUsers" class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <UpdateAppLogoForm class="max-w-xl" />
                </div>

                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <UpdatePasswordForm class="max-w-xl" />
                </div>
                <!-- User Management Section (Permission-based) -->
                <div v-if="canManageUsers" class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <ManageUsers :users="users" :roles="roles" class="w-full" />
                    <ManageRoles :roles="roles" :all-permissions="allPermissions" class="w-full mt-8 border-t border-slate-200 pt-8" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

