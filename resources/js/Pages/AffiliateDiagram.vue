<template>
    <Head title="Diagram Affiliate" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-bold text-xl text-slate-800 leading-tight flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
                Diagram Multi-Tier Affiliate
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-full mx-auto">
                <!-- Main Container -->
                <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden min-h-[600px] flex flex-col">
                    <div class="p-6 md:p-8 bg-slate-50 border-b border-slate-200 flex justify-between items-center">
                        <div>
                            <h3 class="text-xl font-bold text-slate-800">Hierarki Jaringan Sales</h3>
                            <p class="text-slate-500 text-xs sm:text-sm mt-1">Struktur visual jaringan (Upline, Downline 1, Downline 2)</p>
                        </div>
                    </div>
                    
                    <div class="p-4 sm:p-6 md:p-12 flex-grow overflow-auto bg-slate-50/50">
                        <div v-if="treeData.length > 0" class="min-w-max flex justify-center py-6 sm:py-10 pb-16 sm:pb-20">
                            <div class="flex justify-center gap-6 sm:gap-12 md:gap-16">
                                <OrgNode v-for="root in treeData" :key="root.id" :node="root" :level="0" />
                            </div>
                        </div>
                        <div v-else class="flex flex-col items-center justify-center h-64 text-slate-400">
                            <svg class="w-16 h-16 mb-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <p class="text-lg font-medium">Belum ada data jaringan yang dapat ditampilkan.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import OrgNode from '@/Components/OrgNode.vue';

const props = defineProps({
    sales: {
        type: Array,
        default: () => []
    },
    is_sales: Boolean,
});

const buildTree = (data, parentId = null) => {
    return data
        .filter(item => item.parent_id === parentId)
        .map(item => ({
            ...item,
            children: buildTree(data, item.id)
        }));
};

const treeData = computed(() => {
    const allIds = new Set(props.sales.map(s => s.id));
    const roots = props.sales.filter(s => !s.parent_id || !allIds.has(s.parent_id));
    
    return roots.map(root => ({
        ...root,
        children: buildTree(props.sales, root.id)
    }));
});
</script>
