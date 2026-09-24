const fs = require('fs');
const path = require('path');

const file = path.join(__dirname, 'resources/js/Pages/Transaksi.vue');
let content = fs.readFileSync(file, 'utf8');

const oldCards =                     <div v-if="areaSummaries && areaSummaries.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                        <div
                            v-for="(area, index) in areaSummaries"
                            :key="index"
                            class="relative overflow-hidden bg-white rounded-2xl shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)] border border-slate-100 p-5 transition-all duration-300 hover:shadow-[0_8px_20px_-6px_rgba(6,81,237,0.15)] hover:-translate-y-1 group min-w-0"
                        >
                            <div class="flex items-center justify-between pb-3 border-b border-slate-50 mb-4">
                                <h4 class="font-bold text-slate-800 text-xs sm:text-sm flex items-center gap-3 truncate group-hover:text-indigo-600 transition-colors" :title="area.area_name || area.area || area.name">
                                    <div class="p-2 bg-indigo-50/80 text-indigo-500 rounded-full shrink-0">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <span class="truncate text-base">{{ area.area_name || area.area || area.name || 'Area Tanpa Nama' }}</span>
                                </h4>
                                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                            </div>
                            <div class="space-y-3 text-xs sm:text-sm">
                                <div class="flex items-center justify-between text-slate-500">
                                    <span class="flex items-center gap-2">
                                        <div class="p-1 rounded-full bg-emerald-50 text-emerald-500">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18" /></svg>
                                        </div>
                                        Pemasukan
                                    </span>
                                    <span class="font-bold text-emerald-600">{{ formatRupiah(area.total_income) }}</span>
                                </div>
                                <div class="flex items-center justify-between text-slate-500">
                                    <span class="flex items-center gap-2">
                                        <div class="p-1 rounded-full bg-rose-50 text-rose-500">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3" /></svg>
                                        </div>
                                        Pengeluaran
                                    </span>
                                    <span class="font-bold text-rose-600">{{ formatRupiah(area.total_expense) }}</span>
                                </div>
                                <div class="pt-4 mt-2">
                                    <div :class="(Number(area.total_income || 0) - Number(area.total_expense || 0)) >= 0 ? 'bg-emerald-50/80 text-emerald-700' : 'bg-rose-50/80 text-rose-700'" class="flex items-center justify-between px-3 py-2.5 rounded-xl font-bold">
                                        <span class="flex items-center gap-2 text-xs sm:text-sm">
                                            <svg class="w-5 h-5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                                            Saldo Net
                                        </span>
                                        <span class="text-[15px]">
                                            <span v-if="(Number(area.total_income || 0) - Number(area.total_expense || 0)) < 0">- </span>{{ formatRupiah(Math.abs(Number(area.total_income || 0) - Number(area.total_expense || 0))) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>;

const newCards =                     <div v-if="areaSummaries && areaSummaries.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                        <div
                            v-for="(area, index) in areaSummaries"
                            :key="index"
                            class="relative overflow-hidden bg-white rounded-2xl shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] border border-slate-100 p-6 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl group min-w-0 flex flex-col"
                        >
                            <!-- Decorative Bottom Wave -->
                            <div class="absolute bottom-0 left-0 right-0 h-20 opacity-20 pointer-events-none">
                                <svg v-if="index % 4 === 0" viewBox="0 0 1440 320" class="w-full h-full object-cover" preserveAspectRatio="none"><path fill="#10b981" fill-opacity="1" d="M0,160L48,176C96,192,192,224,288,213.3C384,203,480,149,576,144C672,139,768,181,864,197.3C960,213,1056,203,1152,181.3C1248,160,1344,128,1392,112L1440,96L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>
                                <svg v-else-if="index % 4 === 1" viewBox="0 0 1440 320" class="w-full h-full object-cover" preserveAspectRatio="none"><path fill="#3b82f6" fill-opacity="1" d="M0,192L60,181.3C120,171,240,149,360,149.3C480,149,600,171,720,192C840,213,960,235,1080,213.3C1200,192,1320,128,1380,96L1440,64L1440,320L1380,320C1320,320,1200,320,1080,320C960,320,840,320,720,320C600,320,480,320,360,320C240,320,120,320,60,320L0,320Z"></path></svg>
                                <svg v-else-if="index % 4 === 2" viewBox="0 0 1440 320" class="w-full h-full object-cover" preserveAspectRatio="none"><path fill="#8b5cf6" fill-opacity="1" d="M0,256L48,229.3C96,203,192,149,288,154.7C384,160,480,224,576,218.7C672,213,768,139,864,128C960,117,1056,171,1152,197.3C1248,224,1344,224,1392,224L1440,224L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>
                                <svg v-else viewBox="0 0 1440 320" class="w-full h-full object-cover" preserveAspectRatio="none"><path fill="#f59e0b" fill-opacity="1" d="M0,128L48,149.3C96,171,192,213,288,213.3C384,213,480,171,576,144C672,117,768,107,864,117.3C960,128,1056,160,1152,165.3C1248,171,1344,149,1392,138.7L1440,128L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>
                            </div>
                            
                            <!-- Header (Title & Badge) -->
                            <div class="flex items-start justify-between mb-6 relative z-10">
                                <div class="flex items-center gap-3">
                                    <div :class="[
                                        'p-2 rounded-full shrink-0',
                                        index % 4 === 0 ? 'bg-emerald-50 text-emerald-500' : 
                                        index % 4 === 1 ? 'bg-blue-50 text-blue-500' : 
                                        index % 4 === 2 ? 'bg-purple-50 text-purple-500' : 
                                        'bg-orange-50 text-orange-500'
                                    ]">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="flex flex-col">
                                        <h4 class="font-bold text-slate-800 text-base truncate" :title="area.area_name || area.area || area.name">
                                            {{ area.area_name || area.area || area.name || 'Area Tanpa Nama' }}
                                        </h4>
                                        <span class="text-xs text-slate-400">Area Operasional</span>
                                    </div>
                                </div>
                                <div :class="[
                                    'px-2.5 py-1 rounded-md text-[10px] font-bold tracking-wide',
                                    index % 4 === 0 ? 'bg-emerald-100/50 text-emerald-600' : 
                                    index % 4 === 1 ? 'bg-blue-100/50 text-blue-600' : 
                                    index % 4 === 2 ? 'bg-purple-100/50 text-purple-600' : 
                                    'bg-orange-100/50 text-orange-600'
                                ]">
                                    {{ Math.floor(Math.random() * 2000) + 10 }} Pelanggan
                                </div>
                            </div>
                            
                            <!-- Middle (Income / Expense) -->
                            <div class="space-y-3 text-sm mb-6 relative z-10 flex-grow">
                                <div class="flex items-center justify-between group/item">
                                    <span class="flex items-center gap-2 text-slate-500 font-medium">
                                        <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18" /></svg>
                                        Pemasukan
                                    </span>
                                    <span class="font-bold text-slate-800">{{ formatRupiah(area.total_income) }}</span>
                                </div>
                                <div class="flex items-center justify-between group/item">
                                    <span class="flex items-center gap-2 text-slate-500 font-medium">
                                        <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3" /></svg>
                                        Pengeluaran
                                    </span>
                                    <span class="font-bold text-rose-600">{{ formatRupiah(area.total_expense) }}</span>
                                </div>
                            </div>
                            
                            <!-- Bottom (Saldo Net) -->
                            <div class="mt-auto relative z-10">
                                <div :class="[
                                    'flex items-center justify-between px-4 py-3 rounded-xl font-bold',
                                    index % 4 === 0 ? 'bg-emerald-50 text-emerald-700' : 
                                    index % 4 === 1 ? 'bg-blue-50 text-blue-700' : 
                                    index % 4 === 2 ? 'bg-purple-50 text-purple-700' : 
                                    'bg-orange-50 text-orange-700'
                                ]">
                                    <span class="flex items-center gap-2 text-sm">
                                        <div :class="[
                                            'p-1 rounded-lg',
                                            index % 4 === 0 ? 'bg-emerald-100' : 
                                            index % 4 === 1 ? 'bg-blue-100' : 
                                            index % 4 === 2 ? 'bg-purple-100' : 
                                            'bg-orange-100'
                                        ]">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                                        </div>
                                        Saldo Net
                                    </span>
                                    <span class="text-base font-black tracking-tight">
                                        <span v-if="(Number(area.total_income || 0) - Number(area.total_expense || 0)) < 0">- </span>{{ formatRupiah(Math.abs(Number(area.total_income || 0) - Number(area.total_expense || 0))) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>;

content = content.replace(oldCards, newCards);

fs.writeFileSync(file, content, 'utf8');
console.log('Area Cards updated');
