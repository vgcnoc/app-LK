const fs = require('fs');

let content = fs.readFileSync('resources/js/Pages/Pelanggan.vue', 'utf8');

content = content.replace(/Master Data Pelanggan/g, 'Booking Pelanggan');
content = content.replace(/route\('pelanggan\.store'\)/g, "route('booking.store')");
content = content.replace(/route\('pelanggan\.update'/g, "route('booking.update'");
content = content.replace(/route\('pelanggan\.destroy'/g, "route('booking.destroy'");

content = content.replace(/import Chart from 'chart\.js\/auto';/g, '');
content = content.replace(/chart: \{[\s\S]*?\},/g, '');
content = content.replace(/const chartCanvas = ref\(null\);[\s\S]*?\}\)/g, '');
content = content.replace(/<div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">[\s\S]*?<\/div>\s*<\/div>\s*<\/div>/g, '');

fs.writeFileSync('resources/js/Pages/Booking.vue', content, 'utf8');
console.log('Done');
