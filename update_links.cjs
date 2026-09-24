const fs = require('fs');
const path = require('path');
const file = path.join(__dirname, 'resources/js/Layouts/AuthenticatedLayout.vue');
let content = fs.readFileSync(file, 'utf8');

const activeClassOld = 'bg-fuchsia-500/15 text-white font-semibold border-l-4 border-fuchsia-400 pl-3 shadow-[inset_0_0_12px_rgba(232,121,249,0.1)]';
const activeClassNew = 'bg-[#432386] text-white font-semibold shadow-md';
content = content.split(activeClassOld).join(activeClassNew);

const inactiveClassOld = 'text-purple-200/70 lg:hover:text-white lg:hover:bg-fuchsia-500/10 border-l-4 border-transparent pl-3';
const inactiveClassNew = 'text-slate-300 lg:hover:text-white lg:hover:bg-white/5';
content = content.split(inactiveClassOld).join(inactiveClassNew);

const commonClassOld = 'flex items-center gap-3 px-3 py-2.5 rounded-r-xl text-sm font-medium transition-all duration-150';
const commonClassNew = 'flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-150';
content = content.split(commonClassOld).join(commonClassNew);

fs.writeFileSync(file, content, 'utf8');
console.log('Links updated');
