const fs = require('fs');
const path = require('path');

const file = path.join(__dirname, 'resources/js/Layouts/AuthenticatedLayout.vue');
let content = fs.readFileSync(file, 'utf8');

content = content.replace(
    'bg-gradient-to-b from-indigo-950 via-purple-950 to-slate-950 border-r border-purple-800/30',
    'bg-[#27144d]'
);

const activeClass = "'bg-fuchsia-500/15 text-white font-semibold border-l-4 border-fuchsia-400 pl-3 shadow-[inset_0_0_12px_rgba(232,121,249,0.1)]'";
const newActiveClass = "'bg-[#432386] text-white font-semibold shadow-md'";
content = content.split(activeClass).join(newActiveClass);

const inactiveClass = "'text-purple-200/70 lg:hover:text-white lg:hover:bg-fuchsia-500/10 border-l-4 border-transparent pl-3'";
const newInactiveClass = "'text-slate-300 lg:hover:text-white lg:hover:bg-white/5'";
content = content.split(inactiveClass).join(newInactiveClass);

const commonClass = "'flex items-center gap-3 px-3 py-2.5 rounded-r-xl text-sm font-medium transition-all duration-150'";
const newCommonClass = "'flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-150'";
content = content.split(commonClass).join(newCommonClass);

content = content.replace(
    '<nav class="flex-1 px-3 py-4 space-y-1.5 overflow-y-auto">',
    '<nav class="flex-1 px-4 py-4 space-y-1.5 overflow-y-auto">'
);

// Add Butuh bantuan
const butuhBantuan = 
                <!-- Butuh Bantuan -->
                <div class="px-4 pb-6 pt-4 mt-auto">
                    <div class="bg-[#1c0d38] p-4 rounded-2xl flex flex-col items-start shadow-inner border border-white/5">
                        <div class="p-2 bg-[#432386] rounded-xl mb-3">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 2.829a4.978 4.978 0 01-1.414-2.83m-1.414 5.658a9 9 0 01-2.167-9.238m7.824 2.167a1 1 0 111.414 1.414m-1.414-1.414L3 3m8.293 8.293l1.414 1.414" />
                            </svg>
                        </div>
                        <h4 class="text-sm font-bold text-white mb-1">Butuh bantuan?</h4>
                        <p class="text-xs text-slate-400 mb-3">Hubungi tim support kami</p>
                        <button class="w-full py-2 bg-[#432386] hover:bg-[#5a31b5] text-white text-xs font-semibold rounded-lg transition-colors flex items-center justify-center gap-2">
                            <span>Hubungi Support</span>
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                        </button>
                    </div>
                </div>
;

content = content.replace('</nav>\r\n        </aside>', '</nav>\n' + butuhBantuan + '\n        </aside>');

fs.writeFileSync(file, content, 'utf8');
console.log('Sidebar UI updated');
