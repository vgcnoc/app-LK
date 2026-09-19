const fs = require('fs');

let content = fs.readFileSync('resources/js/Pages/Booking.vue', 'utf8');

// Replace Text
content = content.replace(/Data Master Pelanggan/g, 'Booking Pelanggan');
content = content.replace(/Kelola data pelanggan, wilayah area, paket langganan, dan tarif dasar\./g, 'Kelola data booking pelanggan.');
content = content.replace(/Tambah Pelanggan Baru/g, 'Tambah Booking Baru');
content = content.replace(/Tambah Pelanggan/g, 'Tambah Booking');
content = content.replace(/Edit Data Pelanggan/g, 'Edit Data Booking');
content = content.replace(/Hapus Data Pelanggan/g, 'Hapus Data Booking');

// Remove Status Pelanggan input from create form
content = content.replace(/<!-- Status Pelanggan -->[\s\S]*?<\/select>\s*<\/div>\s*<\/div>\s*<p v-if="createForm\.errors\.status_pelanggan"[\s\S]*?<\/p>\s*<\/div>/g, '');

// Remove Status Pelanggan input from edit form
content = content.replace(/<!-- Status Pelanggan -->[\s\S]*?<\/select>\s*<\/div>\s*<\/div>\s*<p v-if="editForm\.errors\.status_pelanggan"[\s\S]*?<\/p>\s*<\/div>/g, '');

// Remove Status Column from Table
content = content.replace(/<th scope="col" class="py-4 px-4 text-left font-semibold text-slate-600[\s\S]*?Status Pelanggan[\s\S]*?<\/th>/g, '');
content = content.replace(/<!-- Status Pelanggan -->[\s\S]*?{{ getStatusBadge\(customer\.status_pelanggan\)\.label }}[\s\S]*?<\/td>/g, '');

// Add "Aktivasi" button to Table Actions
let aktivasiButton = `
                                            <button 
                                                @click="activateBooking(customer)"
                                                class="p-2 text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-xl transition-colors"
                                                title="Aktivasi Booking"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </button>
`;
content = content.replace(/<!-- Table Actions -->\s*<td class="py-4 px-4 align-middle">/, `<!-- Table Actions -->\n                                        <td class="py-4 px-4 align-middle">\n${aktivasiButton}`);

// Remove Overview Stats
content = content.replace(/<!-- Master Stats Overview \(Pure Customer Counters\) -->[\s\S]*?<\/div>\s*<\/div>\s*<\/div>/, '');

fs.writeFileSync('resources/js/Pages/Booking.vue', content, 'utf8');
console.log('Done');
