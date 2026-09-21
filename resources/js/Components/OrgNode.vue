<template>
    <div class="flex flex-col items-center">
        <!-- Node Card -->
        <!-- Menggunakan px-4 pb-4 pt-8 untuk memastikan ada ruang kosong di bagian atas agar tidak tertumpuk badge -->
        <div class="relative bg-slate-800 text-white rounded-2xl px-4 pb-4 pt-8 shadow-xl w-[170px] sm:w-[200px] border-b-4 z-10 mx-1 sm:mx-2"
             :style="{ borderBottomColor: level === 0 ? '#6366f1' : (level === 1 ? '#10b981' : '#f59e0b') }">
            
            <!-- Badge Level -->
            <!-- Diberi warna latar eksplisit agar tidak ter-purge oleh Tailwind -->
            <div class="absolute -top-3 left-1/2 -translate-x-1/2 z-20">
                <div class="px-3 py-1 rounded-full text-[10px] sm:text-xs font-bold uppercase tracking-wider shadow-md whitespace-nowrap text-white"
                     :style="{ backgroundColor: level === 0 ? '#6366f1' : (level === 1 ? '#10b981' : '#f59e0b') }">
                    {{ levelName }}
                </div>
            </div>
            
            <h4 class="font-bold text-sm sm:text-base text-center mt-1 mb-0.5 truncate" :title="node.name">{{ node.name }}</h4>
            <div class="text-[10px] sm:text-[11px] text-slate-300 text-center mb-2">ID: {{ node.member_number || node.id }}</div>
            
            <div class="flex justify-between items-center text-[10px] sm:text-[11px] border-t border-slate-700 pt-2.5">
                <span class="text-slate-400">Komisi: <span class="text-white font-semibold">{{ node.commission_rate }}%</span></span>
                <span :class="[
                    'px-2 py-0.5 rounded text-[8px] sm:text-[9px] font-bold',
                    node.status === 'Aktif' ? 'bg-emerald-900 text-emerald-300' : 'bg-rose-900 text-rose-300'
                ]">
                    {{ node.status }}
                </span>
            </div>
            <div class="text-center mt-2.5" v-if="node.children && node.children.length > 0">
                <span class="inline-flex items-center gap-1 text-[9px] sm:text-[10px] bg-slate-700 text-slate-300 px-2 py-0.5 rounded-full">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                    {{ node.children.length }} Downline
                </span>
            </div>
        </div>

        <!-- Children Structure -->
        <div v-if="node.children && node.children.length > 0" class="flex flex-col items-center w-full">
            <!-- Vertical line going down from parent -->
            <div class="h-6 sm:h-8" style="width: 2px; background-color: #cbd5e1; position: relative; z-index: 0;"></div>
            
            <!-- Horizontal connector + Children row -->
            <div class="flex justify-center relative w-full z-0">
                <div v-for="(child, index) in node.children" :key="child.id" class="flex flex-col items-center relative">
                    <!-- Horizontal connectors for this child -->
                    <div class="absolute top-0 w-full flex" style="height: 2px;" v-if="node.children.length > 1">
                        <div class="h-full w-1/2" :style="{ backgroundColor: index === 0 ? 'transparent' : '#cbd5e1' }"></div>
                        <div class="h-full w-1/2" :style="{ backgroundColor: index === node.children.length - 1 ? 'transparent' : '#cbd5e1' }"></div>
                    </div>
                    
                    <!-- Vertical line going down to child -->
                    <div class="h-6 sm:h-8" style="width: 2px; background-color: #cbd5e1;"></div>
                    
                    <!-- The Child -->
                    <div class="px-1 sm:px-2 md:px-4">
                        <OrgNode :node="child" :level="level + 1" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    node: {
        type: Object,
        required: true
    },
    level: {
        type: Number,
        default: 0
    }
});

const levelName = computed(() => {
    if (props.level === 0) return 'Upline';
    return `Downline ${props.level}`;
});
</script>
