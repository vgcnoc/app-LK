const fs = require('fs');
const path = require('path');
const filesToFix = ['MasterData.vue', 'Transaksi.vue', 'Billing.vue', 'Booking.vue', 'Komisi.vue', 'Laporan.vue', 'VoucherSaldo.vue', 'Affiliate.vue', 'SalesDashboard.vue', 'Dashboard.vue', 'Pelanggan.vue'];
for (const file of filesToFix) {
    const filePath = path.join('C:\\Users\\v\\Documents\\XAMPP\\htdocs\\APP-KEUANGAN-LARAVEL\\resources\\js\\Pages', file);
    if (fs.existsSync(filePath)) {
        let content = fs.readFileSync(filePath, 'utf8');
        content = content.replace(/<div class="[^"]*overflow-x-auto[^"]*">\s*(<table[\s\S]*?<\/table>)\s*<\/div>/g, '$1');
        content = content.replace(/(<table[\s\S]*?<\/table>)/g, '<div class="overflow-x-auto w-full pb-4">\n$1\n</div>');
        fs.writeFileSync(filePath, content);
        console.log('Fixed tables in ' + file);
    }
}
