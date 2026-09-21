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
                <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden min-h-[600px] flex flex-col relative">
                    
                    <!-- Stats Bar -->
                    <div class="p-4 md:p-6 bg-white border-b border-slate-100 flex flex-col md:flex-row justify-between items-center gap-4 relative z-20">
                        <div class="flex flex-wrap items-center gap-6 md:gap-10">
                            <!-- Stat Item: Upline -->
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-lg font-bold text-slate-800 leading-none">{{ uplineCount }}</span>
                                    <span class="text-xs text-slate-500 mt-1">Upline Aktif</span>
                                </div>
                            </div>
                            
                            <div class="hidden md:block w-px h-8 bg-slate-200"></div>
                            
                            <!-- Stat Item: Downline 1 -->
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-lg font-bold text-slate-800 leading-none">{{ downline1Count }}</span>
                                    <span class="text-xs text-slate-500 mt-1">Downline 1</span>
                                </div>
                            </div>
                            
                            <div class="hidden md:block w-px h-8 bg-slate-200"></div>
                            
                            <!-- Stat Item: Downline 2 -->
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-lg font-bold text-slate-800 leading-none">{{ downline2Count }}</span>
                                    <span class="text-xs text-slate-500 mt-1">Downline 2</span>
                                </div>
                            </div>
                            
                            <div class="hidden md:block w-px h-8 bg-slate-200"></div>
                            
                            <!-- Stat Item: Total Member -->
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" /></svg>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-lg font-bold text-slate-800 leading-none">{{ totalMemberCount }}</span>
                                    <span class="text-xs text-slate-500 mt-1">Total Member</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Toggle View -->
                        <div class="flex items-center gap-2 bg-slate-50 p-1 rounded-xl border border-slate-200">
                            <button class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-xs font-bold flex items-center gap-2 shadow-sm pointer-events-none">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>
                                Tampilan Diagram
                            </button>
                            <!-- If it's for Sales, link to /manage-sales. If for Affiliate, /affiliate. Assuming we use inertia link if needed, but let's just make it a generic button back for now, or just history.back() -->
                            <button @click="goBack" class="px-4 py-2 rounded-lg text-slate-600 hover:bg-white hover:text-indigo-600 text-xs font-bold flex items-center gap-2 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" /></svg>
                                Tampilan List
                            </button>
                        </div>
                    </div>
                    
                    <div class="relative p-4 sm:p-6 md:p-12 flex-grow overflow-auto bg-[#F8F9FE]">
                        <!-- SVG Background Wave -->
                        <div class="absolute bottom-0 left-0 right-0 pointer-events-none z-0 opacity-[0.15]">
                             <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="#6366f1" fill-opacity="1" d="M0,160L48,170.7C96,181,192,203,288,208C384,213,480,203,576,170.7C672,139,768,85,864,80C960,75,1056,117,1152,144C1248,171,1344,181,1392,186.7L1440,192L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>
                        </div>
                        
                        <div v-if="treeData.length > 0" class="relative z-10 min-w-max flex justify-center py-6 sm:py-10 pb-16 sm:pb-20">
                            <div class="flex justify-center gap-6 sm:gap-12 md:gap-16">
                                <OrgNode v-for="root in treeData" :key="root.id" :node="root" :level="0" />
                            </div>
                        </div>
                        <div v-else class="relative z-10 flex flex-col items-center justify-center h-64 text-slate-400">
                            <svg class="w-16 h-16 mb-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <p class="text-lg font-medium">Belum ada data jaringan yang dapat ditampilkan.</p>
                        </div>
                        
                        <!-- Info Card -->
                        <div class="absolute bottom-6 left-6 right-6 md:left-12 md:right-12 z-20 flex justify-between items-end pointer-events-none">
                            <div class="bg-white/80 backdrop-blur border border-indigo-100 rounded-2xl p-4 flex gap-4 items-center shadow-lg pointer-events-auto max-w-md">
                                <div class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-600 flex justify-center items-center shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </div>
                                <div>
                                    <h5 class="text-indigo-700 font-bold text-sm">Informasi</h5>
                                    <p class="text-xs text-slate-500">Maksimal 2 downline per member sesuai ketentuan program affiliate.</p>
                                </div>
                            </div>
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

// Computed Stats
const uplineCount = computed(() => treeData.value.length);

const downline1Count = computed(() => {
    let count = 0;
    treeData.value.forEach(n => {
        count += n.children ? n.children.length : 0;
    });
    return count;
});

const downline2Count = computed(() => {
    let count = 0;
    treeData.value.forEach(upline => {
        if (upline.children) {
            upline.children.forEach(d1 => {
                 count += d1.children ? d1.children.length : 0;
            });
        }
    });
    return count;
});

const totalMemberCount = computed(() => uplineCount.value + downline1Count.value + downline2Count.value);

const goBack = () => {
    window.history.back();
};
</script>
