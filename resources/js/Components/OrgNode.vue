<template>
    <div class="flex flex-col items-center">
        <!-- Node Card -->
        <div class="relative bg-white rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] w-[260px] sm:w-[280px] border border-slate-100 z-10 mx-2 mt-4" 
             :class="[`border-t-4`, levelColors.borderTop]">
            
            <!-- Badge Level -->
            <div class="absolute -top-3.5 left-1/2 -translate-x-1/2 z-20">
                <div :class="`px-5 py-1 rounded-full text-[10px] sm:text-xs font-bold uppercase tracking-wider text-white shadow-sm whitespace-nowrap ${levelColors.bg}`">
                    {{ levelName }}
                </div>
            </div>
            
            <div class="p-5 pt-6 flex gap-4">
                <!-- Avatar -->
                <div :class="`w-12 h-12 rounded-full flex items-center justify-center shrink-0 ${levelColors.avatarBg} ${levelColors.text}`">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
                    </svg>
                </div>
                
                <!-- Info -->
                <div class="flex flex-col text-left overflow-hidden w-full relative">
                    <!-- Menu Icon -->
                    <button class="absolute top-0 right-0 text-slate-400 hover:text-slate-600 transition-colors">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
                        </svg>
                    </button>
                    
                    <h4 class="font-bold text-slate-800 text-sm truncate pr-4" :title="node.name">{{ node.name }}</h4>
                    <p class="text-[10px] text-slate-500 truncate mb-1.5">ID: {{ node.member_number || node.id }}</p>
                    <div>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold" 
                              :class="node.status === 'Aktif' ? 'bg-emerald-100 text-emerald-600' : (node.status === 'Nonaktif' || node.status === 'Pending' ? 'bg-amber-100 text-amber-600' : 'bg-rose-100 text-rose-600')">
                            {{ node.status }}
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="px-5 pb-5">
                <div class="border-t border-slate-100 pt-3">
                     <div class="flex items-center gap-2 text-xs text-slate-600 mb-2.5">
                          <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                          </svg>
                          <span>Komisi : <strong class="text-slate-800">{{ node.commission_rate || 0 }}%</strong></span>
                     </div>
                     
                     <div class="flex items-center justify-between">
                         <div class="flex items-center gap-2 text-xs text-slate-600">
                              <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                              </svg>
                              <span class="text-indigo-600 font-semibold underline underline-offset-2">{{ node.children ? node.children.length : 0 }} Downline{{ level === 0 ? '' : ' ' + (level + 1) }}</span>
                         </div>
                         
                         <!-- For downlines (level > 0), mock Total Member as seen on screenshot -->
                         <div v-if="level > 0" class="flex items-center gap-2 text-xs text-slate-600 border-l border-slate-100 pl-3">
                              <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                              </svg>
                              <div class="flex flex-col text-[9px] leading-tight font-medium">
                                  <span class="text-slate-800 text-[11px] font-bold">0</span>
                                  <span>Total Member</span>
                              </div>
                         </div>
                     </div>
                </div>
            </div>
        </div>

        <!-- Children Structure -->
        <div v-if="node.children && node.children.length > 0" class="flex flex-col items-center w-full">
            <!-- Vertical line going down from parent -->
            <div class="h-6 sm:h-8 relative" :class="levelColors.line" style="width: 2px; z-index: 0;">
                <!-- Parent Check Circle -->
                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-5 h-5 rounded-full border-2 border-white flex items-center justify-center text-white z-10" :class="levelColors.bg">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
            </div>
            
            <!-- Horizontal connector + Children row -->
            <div class="flex justify-center relative w-full z-0">
                <div v-for="(child, index) in node.children" :key="child.id" class="flex flex-col items-center relative">
                    <!-- Horizontal connectors for this child -->
                    <div class="absolute top-0 w-full flex" style="height: 2px;" v-if="node.children.length > 1">
                        <div class="h-full w-1/2" :class="index === 0 ? 'bg-transparent' : levelColors.line"></div>
                        <div class="h-full w-1/2" :class="index === node.children.length - 1 ? 'bg-transparent' : levelColors.line"></div>
                    </div>
                    
                    <!-- Vertical line going down to child -->
                    <div class="h-6 sm:h-8 relative" :class="levelColors.line" style="width: 2px;">
                        <!-- Child Check Circle (only on the bottom before child card) -->
                        <div class="absolute -bottom-2 left-1/2 -translate-x-1/2 w-4 h-4 rounded-full border-[1.5px] border-white flex items-center justify-center text-white z-10" :class="getChildLevelColor(level + 1)">
                            <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                    </div>
                    
                    <!-- The Child -->
                    <div class="px-2 sm:px-4 md:px-6">
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

const levelColors = computed(() => {
    return getLevelColors(props.level);
});

function getChildLevelColor(childLvl) {
    return getLevelColors(childLvl).bg;
}

function getLevelColors(lvl) {
    if (lvl === 0) {
        return {
            bg: 'bg-indigo-500',
            text: 'text-indigo-500',
            borderTop: 'border-indigo-500',
            avatarBg: 'bg-indigo-100',
            line: 'bg-indigo-300'
        };
    } else if (lvl === 1) {
        return {
            bg: 'bg-emerald-500',
            text: 'text-emerald-500',
            borderTop: 'border-emerald-500',
            avatarBg: 'bg-emerald-100',
            line: 'bg-emerald-300'
        };
    } else {
        return {
            bg: 'bg-amber-500',
            text: 'text-amber-500',
            borderTop: 'border-amber-500',
            avatarBg: 'bg-amber-100',
            line: 'bg-amber-300'
        };
    }
}
</script>
