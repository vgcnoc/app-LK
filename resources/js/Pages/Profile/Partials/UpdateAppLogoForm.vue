<script setup>
import { ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const logoPreview = ref(null);

const form = useForm({
    app_logo: null
});

const onLogoChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.app_logo = file;
        logoPreview.value = URL.createObjectURL(file);
    } else {
        form.app_logo = null;
        logoPreview.value = null;
    }
};

const updateLogo = () => {
    form.post(route('profile.app-logo.update'), {
        preserveScroll: true,
        onSuccess: () => {
            logoPreview.value = null; // Resets preview if successful
            form.reset();
        }
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">Logo Aplikasi</h2>
            <p class="mt-1 text-sm text-gray-600">
                Perbarui logo aplikasi yang akan ditampilkan pada halaman Login dan Navigasi.
            </p>
        </header>

        <form @submit.prevent="updateLogo" class="mt-6 space-y-6">
            <div>
                <div class="flex items-center gap-6">
                    <div class="w-24 h-24 rounded-xl border-2 border-dashed border-gray-300 overflow-hidden bg-gray-50 flex items-center justify-center p-2">
                        <img v-if="logoPreview" :src="logoPreview" class="w-full h-full object-contain" />
                        <img v-else-if="$page.props.app_logo" :src="$page.props.app_logo" class="w-full h-full object-contain" />
                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <input type="file" accept="image/*" @change="onLogoChange" class="text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition-colors" />
                        <p class="mt-2 text-xs text-gray-500">PNG, JPG, GIF atau SVG maksimal 2MB.</p>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Simpan Logo</PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="text-sm text-gray-600">Tersimpan.</p>
                </Transition>
            </div>
        </form>
    </section>
</template>
