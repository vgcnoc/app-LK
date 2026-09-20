<script setup>
import { ref, computed, watch } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import axios from 'axios';
import * as XLSX from 'xlsx';

const props = defineProps({
    customers: {
        type: Array,
        default: () => [],
    },
    paymentMethods: {
        type: Array,
        default: () => [],
    },
    internetPackages: {
        type: Array,
        default: () => [],
    },
    upgradeHistories: {
        type: Array,
        default: () => [],
    },
    settings: {
        type: Object,
        default: () => ({}),
    },
    areas: {
        type: Array,
        default: () => [],
    }
});

// Format Currency
const formatRupiah = (number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(number || 0);
};

// Format Date
const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    try {
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        return new Intl.DateTimeFormat('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric'
        }).format(d);
    } catch {
        return dateStr;
    }
};

const selectedCustomers = ref([]);
const toggleSelectAll = (event) => {
    if (event.target.checked) {
        selectedCustomers.value = paginatedCustomers.value.map(c => c.id);
    } else {
        selectedCustomers.value = [];
    }
};

const deleteSelected = () => {
    if (selectedCustomers.value.length === 0) return;
    if (confirm(`Yakin ingin menghapus ${selectedCustomers.value.length} pelanggan yang dipilih? Data yang dihapus tidak dapat dikembalikan.`)) {
        router.post(route('billing.mass-delete'), { ids: selectedCustomers.value }, {
            preserveScroll: true,
            onSuccess: () => {
                selectedCustomers.value = [];
            }
        });
    }
};

// Helper to check if customer is active
const isAktif = (c) => !c.status_pelanggan || String(c.status_pelanggan).toLowerCase() === 'aktif';

// Helper to check if customer is overdue
const isOverdue = (c) => {
    if (String(c.status).toLowerCase() === 'paid' || !isAktif(c)) return false;
    if (String(c.status).toLowerCase() === 'nunggak') return true;
    
    const todayDate = new Date();
    
    // Check Promise Date
    if (c.promise_date) {
        const pd = new Date(c.promise_date);
        pd.setHours(23, 59, 59, 999);
        if (todayDate > pd) return true;
    }
    
    // Check global due_date
    const globalDueDate = props.settings.global_due_date ? parseInt(props.settings.global_due_date) : null;
    const globalDueTime = props.settings.global_due_time || '23:59';

    if (globalDueDate) {
        // If customer registered this month, and the register date is after the global due date,
        // they shouldn't be marked as overdue for the current month.
        const registerDate = c.tgl_register ? new Date(c.tgl_register) : new Date(c.created_at);
        if (registerDate.getMonth() === todayDate.getMonth() && registerDate.getFullYear() === todayDate.getFullYear()) {
            if (registerDate.getDate() > globalDueDate) {
                return false; 
            }
        }

        // If today's date > globalDueDate, it is definitely overdue
        if (todayDate.getDate() > globalDueDate) {
            return true;
        }
        
        // If today's date === globalDueDate, check the time
        if (todayDate.getDate() === globalDueDate) {
            const [hours, minutes] = globalDueTime.split(':').map(Number);
            const dueDateTime = new Date();
            dueDateTime.setHours(hours, minutes, 0, 0);
            if (todayDate > dueDateTime) {
                return true;
            }
        }
    }
    
    return false;
};

// Helper to get duration of overdue
const getLamaNunggak = (c) => {
    if (!isOverdue(c)) return null;

    const todayDate = new Date();
    
    if (c.promise_date) {
        const pd = new Date(c.promise_date);
        pd.setHours(23, 59, 59, 999);
        if (todayDate > pd) {
            const diffTime = Math.abs(todayDate - pd);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            return `${diffDays} Hari`;
        }
    }
    
    const globalDueDate = props.settings.global_due_date ? parseInt(props.settings.global_due_date) : null;
    const globalDueTime = props.settings.global_due_time || '23:59';

    if (globalDueDate) {
        let dueDate = new Date();
        dueDate.setDate(globalDueDate);
        
        const [hours, minutes] = globalDueTime.split(':').map(Number);
        dueDate.setHours(hours, minutes, 0, 0);
        
        if (dueDate > todayDate) {
            dueDate.setMonth(dueDate.getMonth() - 1);
        }
        
        const diffTime = Math.abs(todayDate - dueDate);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        if (diffDays >= 30) {
            const months = Math.floor(diffDays / 30);
            const days = diffDays % 30;
            return `${months} Bln${days > 0 ? ` ${days} Hari` : ''}`;
        }
        return `${diffDays} Hari`;
    }
    
    return null;
};

// Summary Statistics
const totalCustomers = computed(() => props.customers.length);
const totalLunas = computed(() =>
    props.customers.filter((c) => String(c.status).toLowerCase() === 'paid').length
);
const totalBelumLunas = computed(() =>
    props.customers.filter((c) => String(c.status).toLowerCase() !== 'paid' && isAktif(c)).length
);
const totalTagihan = computed(() =>
    props.customers.reduce((sum, c) => sum + (isAktif(c) ? (Number(c.amount) || 0) : 0), 0)
);

const percentLunas = computed(() => {
    if (totalCustomers.value === 0) return 0;
    return Math.round((totalLunas.value / totalCustomers.value) * 100);
});

// Excel Import State
const fileInput = ref(null);
const selectedFileName = ref('');
const parsedCount = ref(0);
const parseError = ref('');
const isDragging = ref(false);
const importProgress = ref(0);
const importTotal = ref(0);
const isImporting = ref(false);

const importForm = useForm({
    customersData: [],
});

const handleFile = (file) => {
    if (!file) return;

    // Check extension
    const ext = file.name.split('.').pop().toLowerCase();
    if (!['xlsx', 'xls', 'csv'].includes(ext)) {
        alert('Format file tidak didukung. Harap unggah file .xlsx, .xls, atau .csv');
        return;
    }

    selectedFileName.value = file.name;
    parseError.value = '';

    // Helper: parse any Excel date value to YYYY-MM-DD string
    const parseExcelDate = (val) => {
        if (!val) return null;
        
        // If it's already a Date object (from cellDates:true)
        if (val instanceof Date && !isNaN(val.getTime())) {
            const y = val.getFullYear();
            const m = String(val.getMonth() + 1).padStart(2, '0');
            const d = String(val.getDate()).padStart(2, '0');
            return `${y}-${m}-${d}`;
        }
        
        const str = String(val).trim();
        if (!str) return null;
        
        // Already YYYY-MM-DD
        if (/^\d{4}-\d{2}-\d{2}$/.test(str)) return str;
        
        // DD/MM/YYYY or DD-MM-YYYY
        const dmy = str.match(/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/);
        if (dmy) {
            const day = dmy[1].padStart(2, '0');
            const mon = dmy[2].padStart(2, '0');
            return `${dmy[3]}-${mon}-${day}`;
        }
        
        // MM/DD/YYYY fallback (if month > 12 it's clearly DD/MM)
        // Already handled above
        
        // Excel serial number (e.g. 45555)
        const num = Number(str);
        if (!isNaN(num) && num > 30000 && num < 100000) {
            // Convert Excel serial to JS Date
            // Excel epoch: Jan 1, 1900 = serial 1 (with the 1900 leap year bug)
            const excelEpoch = new Date(1899, 11, 30);
            const jsDate = new Date(excelEpoch.getTime() + num * 86400000);
            if (!isNaN(jsDate.getTime())) {
                const y = jsDate.getFullYear();
                const m = String(jsDate.getMonth() + 1).padStart(2, '0');
                const d = String(jsDate.getDate()).padStart(2, '0');
                return `${y}-${m}-${d}`;
            }
        }
        
        // Last resort: try native Date parsing
        const parsed = new Date(str);
        if (!isNaN(parsed.getTime())) {
            const y = parsed.getFullYear();
            const m = String(parsed.getMonth() + 1).padStart(2, '0');
            const d = String(parsed.getDate()).padStart(2, '0');
            return `${y}-${m}-${d}`;
        }
        
        // Return raw string, let the backend try
        return str;
    };

    const reader = new FileReader();
    reader.onload = (e) => {
        try {
            const data = new Uint8Array(e.target.result);
            const workbook = XLSX.read(data, { type: 'array', cellDates: true });
            const sheetName = workbook.SheetNames[0];
            const worksheet = workbook.Sheets[sheetName];
            const jsonData = XLSX.utils.sheet_to_json(worksheet, { defval: '', raw: true });

            if (!jsonData || jsonData.length === 0) {
                parseError.value = 'File Excel tidak memiliki baris data.';
                importForm.customersData = [];
                parsedCount.value = 0;
                return;
            }

            // Map data and normalize properties while preserving original keys
            const mappedData = jsonData.map((row) => {
                const keys = Object.keys(row);
                // Auto detect name column
                const nameKey = keys.find((k) =>
                    /^(nama|name|pelanggan|customer)/i.test(k.trim())
                );

                // Auto detect amount column
                const amountKey = keys.find((k) =>
                    /^(amount|tagihan|tarif|nominal|harga|total|jumlah|biaya)/i.test(k.trim())
                );

                // Auto detect area
                const areaKey = keys.find((k) =>
                    /^(area|wilayah|cabang)/i.test(k.trim())
                );

                // Auto detect alamat
                const alamatKey = keys.find((k) =>
                    /^(alamat|address)/i.test(k.trim())
                );

                // Auto detect paket
                const paketKey = keys.find((k) =>
                    /^(paket|nama paket)/i.test(k.trim())
                );

                // Auto detect tanggal register
                const tglKey = keys.find((k) =>
                    /^(tanggal|tgl|register)/i.test(k.trim())
                );

                // Auto detect status pelanggan
                const statusKey = keys.find((k) =>
                    /^(status)/i.test(k.trim())
                );
                
                // Auto detect pembayaran terakhir
                const lastPaidKey = keys.find((k) =>
                    /^(pembayaran|terakhir|last|paid)/i.test(k.trim()) && !/^(tanggal|tgl|register)/i.test(k.trim())
                );

                const rawAmount = amountKey ? row[amountKey] : 0;
                let cleanAmount =
                    typeof rawAmount === 'number'
                        ? rawAmount
                        : Number(String(rawAmount).replace(/[^0-9.-]+/g, '')) || 0;

                const nameValue = nameKey ? String(row[nameKey]).trim() : '';
                const areaValue = areaKey ? String(row[areaKey]).trim() : '';
                const alamatValue = alamatKey ? String(row[alamatKey]).trim() : '';
                const paketValue = paketKey ? String(row[paketKey]).trim() : '';

                if (paketValue) {
                    const matchedPkg = props.internetPackages.find(p => p.name.toLowerCase() === paketValue.toLowerCase());
                    if (matchedPkg && matchedPkg.price) {
                        cleanAmount = Number(matchedPkg.price);
                    }
                }
                
                // Parse dates properly using our helper
                const tglValue = tglKey ? parseExcelDate(row[tglKey]) : null;
                const statusValue = statusKey ? String(row[statusKey]).trim() : 'Aktif';
                const lastPaidValue = lastPaidKey ? parseExcelDate(row[lastPaidKey]) : null;

                return {
                    name: nameValue,
                    amount: cleanAmount,
                    area: areaValue,
                    alamat: alamatValue,
                    paket: paketValue,
                    register_date: tglValue,
                    status_pelanggan: statusValue,
                    last_paid_date: lastPaidValue
                };
            }).filter((item) => item.name.length > 0);

            if (mappedData.length === 0) {
                parseError.value = 'Tidak ditemukan kolom nama pelanggan yang valid.';
                importForm.customersData = [];
                parsedCount.value = 0;
                return;
            }

            importForm.customersData = mappedData;
            parsedCount.value = mappedData.length;
        } catch (error) {
            console.error('Error parsing excel:', error);
            parseError.value = 'Gagal membaca file Excel. Pastikan struktur file valid.';
        }
    };
    reader.readAsArrayBuffer(file);
};

const onFileInputChange = (event) => {
    const file = event.target.files[0];
    if (file) handleFile(file);
};

const onDrop = (event) => {
    isDragging.value = false;
    const file = event.dataTransfer.files[0];
    if (file) handleFile(file);
};

const triggerFileInput = () => {
    if (fileInput.value) fileInput.value.click();
};

const removeSelectedFile = () => {
    selectedFileName.value = '';
    parsedCount.value = 0;
    parseError.value = '';
    importForm.customersData = [];
    if (fileInput.value) fileInput.value.value = '';
};

const CHUNK_SIZE = 50;

const submitImport = async () => {
    if (!importForm.customersData || importForm.customersData.length === 0) {
        alert('Mohon pilih file Excel yang memiliki Billing Data.');
        return;
    }

    const allData = importForm.customersData;
    const totalChunks = Math.ceil(allData.length / CHUNK_SIZE);
    importTotal.value = allData.length;
    importProgress.value = 0;
    isImporting.value = true;

    try {
        for (let i = 0; i < totalChunks; i++) {
            const chunk = allData.slice(i * CHUNK_SIZE, (i + 1) * CHUNK_SIZE);
            await axios.post(route('billing.import'), {
                customersData: chunk,
            });
            importProgress.value = Math.min((i + 1) * CHUNK_SIZE, allData.length);
        }

        removeSelectedFile();
        router.reload({ preserveScroll: true });
    } catch (error) {
        console.error('Import error:', error);
        const msg = error.response?.data?.message || error.message || 'Terjadi kesalahan saat import.';
        alert(`Gagal import data (batch ${Math.floor(importProgress.value / CHUNK_SIZE) + 1}/${totalChunks}): ${msg}`);
    } finally {
        isImporting.value = false;
        importProgress.value = 0;
        importTotal.value = 0;
    }
};

// Search and Filtering
const searchQuery = ref('');
const statusFilter = ref('all');
const areaFilter = ref('all');
const startDateFilter = ref('');
const endDateFilter = ref('');

const appliedSearchQuery = ref('');
const appliedStatusFilter = ref('all');
const appliedAreaFilter = ref('all');
const appliedStartDateFilter = ref('');
const appliedEndDateFilter = ref('');

const applyFilters = () => {
    appliedSearchQuery.value = searchQuery.value;
    appliedStatusFilter.value = statusFilter.value;
    appliedAreaFilter.value = areaFilter.value;
    appliedStartDateFilter.value = startDateFilter.value;
    appliedEndDateFilter.value = endDateFilter.value;
    currentPage.value = 1;
};

const uniqueAreas = computed(() => {
    return Array.isArray(props.areas) ? props.areas : [];
});

const activeTab = ref('semua'); // 'semua', 'piutang', 'janji_bayar', 'jatuh_tempo'

const checkSebagian = (c) => {
    const isLunas = String(c.status).toLowerCase() === 'paid';
    if (isLunas) return false;
    if (!c.base_amount || Number(c.base_amount) <= 0) return false;
    if (!c.amount || Number(c.amount) <= 0) return false;
    
    const effectiveProrata = c.prorata_amount ? Number(c.prorata_amount) : 0;
    const expectedBaseDiff = Number(c.amount) - effectiveProrata;
    
    if (effectiveProrata > 0) {
        return (expectedBaseDiff !== 0 && expectedBaseDiff % Number(c.base_amount) !== 0);
    } else {
        return (Number(c.amount) % Number(c.base_amount) !== 0);
    }
};

const filteredCustomers = computed(() => {
    const query = appliedSearchQuery.value.trim().toLowerCase();

    return props.customers.filter((customer) => {
        const matchesQuery =
            !query ||
            (customer.name && customer.name.toLowerCase().includes(query)) ||
            (customer.area && customer.area.toLowerCase().includes(query));

        const isLunas = String(customer.status).toLowerCase() === 'paid';
        const isPiutang = !isLunas && isAktif(customer);
        const hasJanjiBayar = !!customer.promise_date;
        const isSebagian = checkSebagian(customer);

        const matchesStatus =
            appliedStatusFilter.value === 'all' ||
            (appliedStatusFilter.value === 'Lunas' && isLunas) ||
            (appliedStatusFilter.value === 'Belum Lunas' && !isLunas);

        const matchesArea =
            appliedAreaFilter.value === 'all' || customer.area === appliedAreaFilter.value;
            
        const isJatuhTempo = isOverdue(customer);

        const matchesTab = 
            (activeTab.value === 'semua' && !hasJanjiBayar && !isSebagian && !isJatuhTempo) ||
            (activeTab.value === 'piutang' && isSebagian && isPiutang && !hasJanjiBayar) ||
            (activeTab.value === 'janji_bayar' && isPiutang && hasJanjiBayar) ||
            (activeTab.value === 'jatuh_tempo' && isJatuhTempo);

        let matchesDate = true;
        if (appliedStartDateFilter.value || appliedEndDateFilter.value) {
            const dateToCheck = activeTab.value === 'janji_bayar' 
                ? customer.promise_date 
                : (customer.tanggal_register || customer.created_at);
            
            if (!dateToCheck) {
                matchesDate = false;
            } else {
                const itemDate = new Date(dateToCheck).getTime();
                const start = appliedStartDateFilter.value ? new Date(appliedStartDateFilter.value).getTime() : 0;
                const end = appliedEndDateFilter.value ? new Date(appliedEndDateFilter.value + 'T23:59:59').getTime() : Infinity;
                matchesDate = itemDate >= start && itemDate <= end;
            }
        }

        return matchesQuery && matchesStatus && matchesArea && matchesTab && matchesDate;
    });
});

const totalTagihanFiltered = computed(() => {
    return filteredCustomers.value.reduce((sum, c) => sum + (Number(c.amount) || 0), 0);
});

const itemsPerPage = ref(10);
const currentPage = ref(1);

const totalPages = computed(() => Math.ceil(filteredCustomers.value.length / itemsPerPage.value) || 1);

const paginatedCustomers = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    return filteredCustomers.value.slice(start, start + itemsPerPage.value);
});

// Watch for filter changes to reset page
watch([appliedSearchQuery, appliedStatusFilter, appliedAreaFilter, activeTab, appliedStartDateFilter, appliedEndDateFilter, itemsPerPage], () => {
    currentPage.value = 1;
});

const resetFilters = () => {
    searchQuery.value = '';
    statusFilter.value = 'all';
    areaFilter.value = 'all';
    startDateFilter.value = '';
    endDateFilter.value = '';
    applyFilters();
};

const exportExcel = () => {
    const data = filteredCustomers.value.map((c, index) => ({
        'No': index + 1,
        'Nama Pelanggan': c.name,
        'Area': c.area || '-',
        'Alamat': c.alamat || '-',
        'Nama Paket': c.paket || '-',
        'Tanggal Register': c.tanggal_register ? new Date(c.tanggal_register).toLocaleDateString() : '-',
        'Janji Bayar': c.promise_date ? new Date(c.promise_date).toLocaleDateString() : '-',
        'Pembayaran Terakhir': c.last_paid_at ? new Date(c.last_paid_at).toLocaleDateString() : '-',
        'Tagihan': Number(c.amount) || 0,
        'Status Pelanggan': isAktif(c) ? 'Aktif' : 'Non-Aktif',
        'Status': String(c.status).toLowerCase() === 'paid' ? 'Lunas' : 'Belum Bayar',
    }));
    
    const ws = XLSX.utils.json_to_sheet(data);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "Data Billing");
    XLSX.writeFile(wb, `Data_Billing_${new Date().toISOString().split('T')[0]}.xlsx`);
};

const exportPDF = () => {
    window.print();
};

// Set Lunas Modal
const isLunasModalOpen = ref(false);
const activeCustomer = ref(null);

const lunasForm = useForm({
    payment_method_id: '',
    payment_method: '',
    payment_date: new Date().toISOString().slice(0, 10),
    payment_amount: 0,
    is_janji_bayar: false,
    promise_date: '',
});

const openLunasModal = (customer) => {
    activeCustomer.value = customer;
    lunasForm.reset();
    lunasForm.clearErrors();
    // Default to first payment method if available
    if (props.paymentMethods.length > 0) {
        lunasForm.payment_method_id = props.paymentMethods[0].id;
        lunasForm.payment_method = props.paymentMethods[0].name;
    }
    lunasForm.payment_date = new Date().toISOString().slice(0, 10);
    lunasForm.payment_amount = customer.amount || customer.base_amount || 0;
    lunasForm.is_janji_bayar = false;
    lunasForm.promise_date = '';
    isLunasModalOpen.value = true;
};

const closeLunasModal = () => {
    isLunasModalOpen.value = false;
    activeCustomer.value = null;
    lunasForm.reset();
};

const onPaymentMethodSelect = (event) => {
    const selectedId = event.target.value;
    const method = props.paymentMethods.find(
        (m) => String(m.id) === String(selectedId)
    );
    if (method) {
        lunasForm.payment_method_id = method.id;
        lunasForm.payment_method = method.name;
    }
};

const submitLunas = () => {
    if (!activeCustomer.value) return;

    lunasForm.post(route('billing.lunas', activeCustomer.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeLunasModal();
        },
    });
};

// Edit Customer Modal
const isEditModalOpen = ref(false);
const isProrata = ref(false);
const isUpgrade = ref(false);
const editingCustomer = ref(null);

const editForm = useForm({
    name: '',
    area: '',
    alamat: '',
    paket: '',
    register_date: '',
    status_pelanggan: '',
    suspend_start_date: '',
    suspend_end_date: '',
    stop_date: '',
    last_paid_date: '',
    base_amount: 0,
    amount: 0,
    prorata_amount: null,
    is_upgrade: false,
});

const onPaketChange = () => {
    if (editForm.paket !== 'Lainnya') {
        const pkg = props.internetPackages.find(p => p.name === editForm.paket);
        if (pkg && pkg.price) {
            editForm.base_amount = pkg.price;
        }
    }
};

const openEditModal = (customer) => {
    editingCustomer.value = customer;
    editForm.name = customer.name || '';
    editForm.area = customer.area || '';
    editForm.alamat = customer.alamat || '';
    editForm.paket = customer.paket || '';
    editForm.register_date = customer.register_date || '';
    editForm.status_pelanggan = customer.status_pelanggan || 'Aktif';
    editForm.suspend_start_date = customer.suspend_start_date || '';
    editForm.suspend_end_date = customer.suspend_end_date || '';
    editForm.stop_date = customer.stop_date || '';
    editForm.last_paid_date = customer.last_paid_date || '';
    editForm.base_amount = customer.base_amount || 0;
    
    // Inisialisasi status Prorata/Upgrade
    const hasProrata = customer.prorata_amount !== null && customer.prorata_amount !== undefined;
    
    // Jika belum pernah bayar, asumsikan itu Prorata awal. Jika sudah pernah bayar, asumsikan itu Upgrade.
    if (customer.last_paid_date === null) {
        isProrata.value = hasProrata;
        isUpgrade.value = false;
    } else {
        isProrata.value = false;
        isUpgrade.value = hasProrata;
    }
    
    if (hasProrata) {
        editForm.amount = customer.prorata_amount;
        editForm.prorata_amount = customer.prorata_amount;
    } else {
        editForm.amount = customer.amount || 0;
        editForm.prorata_amount = null;
    }
    
    editForm.clearErrors();
    isEditModalOpen.value = true;
};

const closeEditModal = () => {
    isEditModalOpen.value = false;
    editingCustomer.value = null;
    editForm.reset();
};

const submitEdit = () => {
    if (!editingCustomer.value) return;

    if (isProrata.value || isUpgrade.value) {
        editForm.prorata_amount = editForm.amount;
    } else {
        editForm.prorata_amount = null;
    }

    editForm.is_upgrade = isUpgrade.value;

    editForm.put(route('pelanggan.update', editingCustomer.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeEditModal();
        },
    });
};

const getUpgradeStatus = (oldPaketName, newPaketName) => {
    if (!oldPaketName || !newPaketName) return { label: 'UBAH PAKET', color: 'amber' };
    
    const oldPkg = props.internetPackages.find(p => p.name.toLowerCase() === oldPaketName.toLowerCase());
    const newPkg = props.internetPackages.find(p => p.name.toLowerCase() === newPaketName.toLowerCase());
    
    if (oldPkg && newPkg) {
        const oldPrice = Number(oldPkg.price || 0);
        const newPrice = Number(newPkg.price || 0);
        
        if (newPrice > oldPrice) {
            return { label: 'UPGRADE', color: 'emerald' };
        } else if (newPrice < oldPrice) {
            return { label: 'DOWNGRADE', color: 'rose' };
        }
    }
    
    return { label: 'UBAH PAKET', color: 'amber' };
};

// Set Janji Bayar Modal
const isJanjiModalOpen = ref(false);
const activeJanjiCustomer = ref(null);

const janjiForm = useForm({
    promise_date: new Date().toISOString().slice(0, 10),
});

const openJanjiModal = (customer) => {
    activeJanjiCustomer.value = customer;
    janjiForm.reset();
    janjiForm.clearErrors();
    janjiForm.promise_date = customer.promise_date || new Date().toISOString().slice(0, 10);
    isJanjiModalOpen.value = true;
};

const closeJanjiModal = () => {
    isJanjiModalOpen.value = false;
    setTimeout(() => {
        activeJanjiCustomer.value = null;
    }, 200);
};

const submitJanji = () => {
    if (!activeJanjiCustomer.value) return;
    janjiForm.post(route('billing.janji-bayar', activeJanjiCustomer.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeJanjiModal();
        },
    });
};

const cancelJanji = (customer) => {
    if (confirm(`Apakah Anda yakin ingin membatalkan janji bayar untuk "${customer.name}"?`)) {
        router.post(route('billing.batal-janji', customer.id), {}, {
            preserveScroll: true,
        });
    }
};

// Rollback Customer
const rollbackCustomer = (customer) => {
    if (
        confirm(
            `Apakah Anda yakin ingin membatalkan pelunasan untuk pelanggan "${customer.name}"? Transaksi pelunasan terakhir akan dihapus.`
        )
    ) {
        router.post(route('billing.rollback', customer.id), {}, {
            preserveScroll: true,
        });
    }
};

// Delete Customer
const deleteCustomer = (customer) => {
    if (
        confirm(
            `Apakah Anda yakin ingin menghapus pelanggan "${customer.name}"? Tindakan ini tidak dapat dibatalkan.`
        )
    ) {
        router.delete(route('pelanggan.destroy', customer.id), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Billing Data" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex-1 min-w-0">
                    <h2 class="text-2xl font-bold tracking-tight text-slate-800 break-words">
                        Billing Data
                    </h2>
                    <p class="text-sm text-slate-500 break-words">
                        Kelola Billing Data, pantau status tagihan, dan import data dari Excel.
                    </p>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <!-- 1. STATS CARDS -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 print:hidden">
                    <!-- Total Pelanggan -->
                    <div class="relative overflow-hidden rounded-xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-sm transition hover:shadow-md min-w-0">
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <p class="text-[10px] sm:text-xs font-semibold uppercase tracking-wider text-slate-500 truncate">
                                    Total Pelanggan
                                </p>
                                <p class="mt-1 sm:mt-2 text-xl sm:text-3xl font-bold tracking-tight text-slate-800 truncate">
                                    {{ totalCustomers }}
                                </p>
                                <p class="mt-1 text-[10px] sm:text-xs text-slate-400 truncate">
                                    Semua data terdaftar
                                </p>
                            </div>
                            <div class="flex h-10 w-10 sm:h-12 sm:w-12 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 ring-1 ring-indigo-100">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 sm:h-6 sm:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Sudah Lunas -->
                    <div class="relative overflow-hidden rounded-xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-sm transition hover:shadow-md min-w-0">
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <p class="text-[10px] sm:text-xs font-semibold uppercase tracking-wider text-slate-500 truncate">
                                    Sudah Lunas
                                </p>
                                <p class="mt-1 sm:mt-2 text-xl sm:text-3xl font-bold tracking-tight text-emerald-600 truncate">
                                    {{ totalLunas }}
                                </p>
                                <p class="mt-1 text-[10px] sm:text-xs text-slate-400 truncate">
                                    {{ percentLunas }}% dari total pelanggan
                                </p>
                            </div>
                            <div class="flex h-10 w-10 sm:h-12 sm:w-12 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 sm:h-6 sm:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Belum Lunas -->
                    <!-- Belum Lunas -->
                    <div class="relative overflow-hidden rounded-xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-sm transition hover:shadow-md min-w-0">
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <p class="text-[10px] sm:text-xs font-semibold uppercase tracking-wider text-slate-500 truncate">
                                    Belum Lunas
                                </p>
                                <p class="mt-1 sm:mt-2 text-xl sm:text-3xl font-bold tracking-tight text-amber-600 truncate">
                                    {{ totalBelumLunas }}
                                </p>
                                <p class="mt-1 text-[10px] sm:text-xs text-slate-400 truncate">
                                    Menunggu pembayaran
                                </p>
                            </div>
                            <div class="flex h-10 w-10 sm:h-12 sm:w-12 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600 ring-1 ring-amber-100">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 sm:h-6 sm:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Total Tagihan -->
                    <!-- Total Tagihan -->
                    <div class="relative overflow-hidden rounded-xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-sm transition hover:shadow-md min-w-0">
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <p class="text-[10px] sm:text-xs font-semibold uppercase tracking-wider text-slate-500 truncate">
                                    Total Tagihan
                                </p>
                                <p class="mt-1 sm:mt-2 truncate text-xl sm:text-2xl font-bold tracking-tight text-slate-800" :title="formatRupiah(totalTagihan)">
                                    {{ formatRupiah(totalTagihan) }}
                                </p>
                                <p class="mt-1 text-[10px] sm:text-xs text-slate-400 truncate">
                                    Akumulasi tagihan pelanggan
                                </p>
                            </div>
                            <div class="flex h-10 w-10 sm:h-12 sm:w-12 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-violet-600 ring-1 ring-violet-100">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 sm:h-6 sm:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. IMPORT SECTION -->
                <div class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm print:hidden">
                    <div class="border-b border-slate-100 px-6 py-4">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="text-base font-semibold text-slate-800 truncate">Import Billing Data via Excel</h3>
                                <p class="text-xs text-slate-500 truncate">Unggah berkas spreadsheet untuk menambahkan pelanggan secara massal.</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-6">
                        <form @submit.prevent="submitImport" class="flex flex-col gap-3">
                            <!-- Drag and Drop Upload Area -->
                            <div class="w-full">
                                <input
                                    ref="fileInput"
                                    type="file"
                                    accept=".xlsx, .xls, .csv"
                                    class="hidden"
                                    @change="onFileInputChange"
                                />

                                <div
                                    @dragover.prevent="isDragging = true"
                                    @dragenter.prevent="isDragging = true"
                                    @dragleave.prevent="isDragging = false"
                                    @drop.prevent="onDrop"
                                    @click="triggerFileInput"
                                    :class="[
                                        'group relative flex flex-col items-center justify-center rounded-xl border-2 border-dashed py-4 px-6 text-center cursor-pointer transition-all duration-200',
                                        isDragging
                                            ? 'border-indigo-500 bg-indigo-50/60 ring-2 ring-indigo-500/20'
                                            : 'border-slate-300 bg-slate-50/50 hover:border-indigo-400 hover:bg-slate-50'
                                    ]"
                                >
                                    <div class="flex items-center justify-center gap-3">
                                        <!-- Upload Cloud Icon -->
                                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white shadow-sm ring-1 ring-slate-200 transition group-hover:scale-105 group-hover:ring-indigo-200">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                            </svg>
                                        </div>

                                        <div class="text-sm text-left">
                                            <div class="text-slate-600">
                                                <span class="font-semibold text-indigo-600 group-hover:underline">Pilih file</span> atau seret & lepas file ke sini
                                            </div>
                                            <p class="text-xs text-slate-400">
                                                Format .xlsx, .xls, .csv
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Selected File Feedback -->
                                    <div
                                        v-if="selectedFileName"
                                        class="mt-3 inline-flex items-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50/80 px-3.5 py-1.5 text-xs text-indigo-900"
                                        @click.stop
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <span class="font-medium">{{ selectedFileName }}</span>
                                        <span v-if="parsedCount > 0" class="rounded bg-indigo-200/70 px-1.5 py-0.5 text-[11px] font-semibold text-indigo-800">
                                            {{ parsedCount }} baris
                                        </span>
                                        <button
                                            type="button"
                                            @click="removeSelectedFile"
                                            class="ml-1 text-slate-400 transition hover:text-rose-600"
                                            title="Hapus file"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <p v-if="parseError" class="mt-2 text-xs font-medium text-rose-600">
                                    {{ parseError }}
                                </p>
                                <p v-if="importForm.errors.customersData" class="mt-2 text-xs font-medium text-rose-600">
                                    {{ importForm.errors.customersData }}
                                </p>
                            </div>

                            <!-- Footer (Info & Submit) -->
                            <div class="flex w-full flex-col sm:flex-row sm:items-center justify-between gap-4 border-t border-slate-100 pt-3 mt-1">
                                <!-- Quick info alert box -->
                                <div class="flex-1 rounded-lg border border-blue-100 bg-blue-50/70 py-2 px-3 text-[11px] text-blue-800 min-w-0">
                                    <div class="flex items-start gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0 text-blue-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <p class="break-words flex-1 min-w-0">
                                            Header kolom Excel: <strong>Nama Pelanggan, Area, Alamat, Nama Paket, Tanggal Register, Status Pelanggan, Tagihan</strong>.
                                        </p>
                                    </div>
                                </div>

                                <button
                                    type="submit"
                                    :disabled="isImporting"
                                    class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 hover:shadow focus:outline-none focus:ring-2 focus:ring-indigo-500/30 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <svg
                                        v-if="isImporting"
                                        class="h-4 w-4 animate-spin text-white"
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                    >
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                    </svg>
                                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                    </svg>
                                    <span>{{ isImporting ? `Mengimpor... ${importProgress}/${importTotal}` : 'Mulai Import' }}</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- 3. CUSTOMER TABLE SECTION -->
                <!-- Print Header -->
                <div class="hidden print:block mb-6 text-center">
                    <h2 class="text-2xl font-bold text-slate-800">Laporan Billing Data</h2>
                    <p class="text-sm text-slate-600 mt-1">
                        Kategori: <span class="font-semibold">{{ activeTab.toUpperCase().replace('_', ' ') }}</span>
                    </p>
                    <p class="text-xs text-slate-500 mt-1">
                        Tanggal: {{ startDateFilter ? new Date(startDateFilter).toLocaleDateString('id-ID') : 'Awal' }} s/d {{ endDateFilter ? new Date(endDateFilter).toLocaleDateString('id-ID') : 'Sekarang' }}
                    </p>
                </div>
                
                <div class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm print:shadow-none print:border-none print:ring-0">
                                        <!-- Tab Navigation -->
                    <div class="border-b border-slate-100 bg-slate-50/50 p-4 sm:p-5 print:hidden">
                        <div class="flex flex-wrap items-center gap-2">
                            <button
                                @click="activeTab = 'semua'"
                                :class="[
                                    'rounded-lg px-4 py-2 text-xs font-semibold transition-all duration-200',
                                    activeTab === 'semua'
                                        ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-200/60'
                                        : 'text-slate-500 hover:text-slate-700 hover:bg-white/50'
                                ]"
                            >
                                📋 Daftar Tagihan
                                <span class="ml-1.5 rounded-md bg-slate-200/80 px-1.5 py-0.5 text-[10px] font-bold text-slate-600">{{ props.customers.filter(c => !c.promise_date && !checkSebagian(c) && !isOverdue(c)).length }}</span>
                            </button>
                            <button
                                @click="activeTab = 'jatuh_tempo'"
                                :class="[
                                    'rounded-lg px-4 py-2 text-xs font-semibold transition-all duration-200',
                                    activeTab === 'jatuh_tempo'
                                        ? 'bg-white text-rose-700 shadow-sm ring-1 ring-rose-200/60'
                                        : 'text-slate-500 hover:text-slate-700 hover:bg-white/50'
                                ]"
                            >
                                ⏳ Tagihan Jatuh Tempo
                                <span class="ml-1.5 rounded-md bg-rose-100 px-1.5 py-0.5 text-[10px] font-bold text-rose-700">{{ props.customers.filter(c => isOverdue(c)).length }}</span>
                            </button>
                            <button
                                @click="activeTab = 'piutang'"
                                :class="[
                                    'rounded-lg px-4 py-2 text-xs font-semibold transition-all duration-200',
                                    activeTab === 'piutang'
                                        ? 'bg-white text-amber-700 shadow-sm ring-1 ring-amber-200/60'
                                        : 'text-slate-500 hover:text-slate-700 hover:bg-white/50'
                                ]"
                            >
                                💰 Piutang (Bayar Sebagian)
                                <span class="ml-1.5 rounded-md bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold text-amber-700">{{ props.customers.filter(c => checkSebagian(c) && String(c.status).toLowerCase() !== 'paid' && isAktif(c) && !c.promise_date).length }}</span>
                            </button>
                            <button
                                @click="activeTab = 'janji_bayar'"
                                :class="[
                                    'rounded-lg px-4 py-2 text-xs font-semibold transition-all duration-200',
                                    activeTab === 'janji_bayar'
                                        ? 'bg-white text-indigo-700 shadow-sm ring-1 ring-indigo-200/60'
                                        : 'text-slate-500 hover:text-slate-700 hover:bg-white/50'
                                ]"
                            >
                                📅 Janji Bayar
                                <span class="ml-1.5 rounded-md bg-indigo-100 px-1.5 py-0.5 text-[10px] font-bold text-indigo-700">{{ props.customers.filter(c => !!c.promise_date && String(c.status).toLowerCase() !== 'paid' && isAktif(c)).length }}</span>
                            </button>
                            <button
                                @click="activeTab = 'riwayat_upgrade'"
                                :class="[
                                    'rounded-lg px-4 py-2 text-xs font-semibold transition-all duration-200',
                                    activeTab === 'riwayat_upgrade'
                                        ? 'bg-white text-purple-700 shadow-sm ring-1 ring-purple-200/60'
                                        : 'text-slate-500 hover:text-slate-700 hover:bg-white/50'
                                ]"
                            >
                                📈 Riwayat Upgrade
                                <span class="ml-1.5 rounded-md bg-purple-100 px-1.5 py-0.5 text-[10px] font-bold text-purple-700">{{ props.upgradeHistories?.length || 0 }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Filter & Actions Toolbar -->
                    <div class="border-b border-slate-100 p-4 sm:p-5 print:hidden">
                        <div class="flex flex-col gap-4">
                            <!-- Top Row: Filters -->
                            <div class="flex flex-wrap items-center gap-3">
                                <!-- Search Input -->
                                <div class="relative w-full sm:max-w-xs shrink-0">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                    </div>
                                    <input
                                        v-model="searchQuery"
                                        type="text"
                                        placeholder="Cari nama atau area..."
                                        class="w-full rounded-xl border border-slate-300 py-2 pl-9 pr-3 text-xs text-slate-800 shadow-sm transition placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                                    />
                                    <button
                                        v-if="searchQuery"
                                        @click="searchQuery = ''"
                                        class="absolute inset-y-0 right-0 flex items-center pr-2.5 text-slate-400 hover:text-slate-600"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </div>
                                
                                <!-- Tanggal -->
                                <div class="flex items-center gap-2 shrink-0">
                                    <label class="text-xs font-semibold text-slate-500 whitespace-nowrap">Tanggal</label>
                                    <input type="date" v-model="startDateFilter" class="w-full sm:w-auto rounded-xl border border-slate-300 py-2 px-3 text-xs text-slate-700 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                                    <span class="text-xs text-slate-500">s/d</span>
                                    <input type="date" v-model="endDateFilter" class="w-full sm:w-auto rounded-xl border border-slate-300 py-2 px-3 text-xs text-slate-700 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                                </div>

                                <!-- Status Filter -->
                                <select
                                    v-model="statusFilter"
                                    class="w-full rounded-xl border border-slate-300 py-2 pl-3 pr-8 text-xs text-slate-700 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 sm:w-auto"
                                >
                                    <option value="all">Semua Status</option>
                                    <option value="Lunas">Lunas</option>
                                    <option value="Belum Lunas">Belum Lunas</option>
                                </select>

                                <!-- Area Filter -->
                                <select
                                    v-model="areaFilter"
                                    class="w-full rounded-xl border border-slate-300 py-2 pl-3 pr-8 text-xs text-slate-700 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 sm:w-auto"
                                >
                                    <option value="all">Semua Area</option>
                                    <option v-for="area in uniqueAreas" :key="area" :value="area">
                                        {{ area }}
                                    </option>
                                </select>

                                <!-- Action Buttons (Filters) -->
                                <div class="flex items-center gap-2 shrink-0">
                                    <!-- Terapkan Filter -->
                                    <button
                                        @click="applyFilters"
                                        type="button"
                                        class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-medium text-white shadow-sm transition hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500/20"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                        Terapkan
                                    </button>

                                    <!-- Reset Filters -->
                                    <button
                                        v-if="searchQuery || statusFilter !== 'all' || areaFilter !== 'all' || startDateFilter || endDateFilter"
                                        @click="resetFilters"
                                        type="button"
                                        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-600 shadow-sm transition hover:bg-slate-50 focus:ring-2 focus:ring-slate-200"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                        Reset
                                    </button>
                                </div>
                            </div>

                            <!-- Bottom Row: Table Actions -->
                            <div class="flex flex-wrap items-center justify-between gap-4 border-t border-slate-100 pt-4">
                                <!-- Left side: Pagination & Info -->
                                <div class="flex items-center gap-3">
                                    <select
                                        v-model="itemsPerPage"
                                        class="rounded-xl border border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                                    >
                                        <option :value="10">10 baris</option>
                                        <option :value="50">50 baris</option>
                                        <option :value="100">100 baris</option>
                                        <option :value="500">500 baris</option>
                                    </select>
                                    <p class="text-xs font-medium text-slate-500">
                                        Menampilkan {{ filteredCustomers.length }} dari {{ totalCustomers }} pelanggan
                                    </p>
                                </div>
                                
                                <!-- Right side: Export & Delete -->
                                <div class="flex flex-wrap items-center gap-2">
                                    <button v-if="selectedCustomers.length > 0" @click="deleteSelected" type="button" class="inline-flex items-center gap-1.5 rounded-xl bg-rose-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-rose-700 transition-colors shadow-sm focus:ring-2 focus:ring-rose-500/20">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        Hapus Terpilih ({{ selectedCustomers.length }})
                                    </button>
                                    <button @click="exportExcel" type="button" class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-700 hover:bg-emerald-100 transition-colors border border-emerald-200">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                        </svg>
                                        Export Excel
                                    </button>
                                    <button @click="exportPDF" type="button" class="inline-flex items-center gap-1.5 rounded-xl bg-rose-50 px-3 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-100 transition-colors border border-rose-200">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                        </svg>
                                        Cetak PDF
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>


                    <!-- Table -->
                    <div v-if="activeTab !== 'riwayat_upgrade'" class="overflow-x-auto print:overflow-visible print:w-full">
                        <div class="overflow-x-auto w-full pb-4">
<table class="min-w-full divide-y divide-slate-200 text-left text-sm print:text-[11px]">
                            <thead class="bg-slate-50/80 text-xs font-semibold uppercase tracking-wider text-slate-500 print:text-[10px]">
                                <tr>
                                    <th scope="col" class="w-12 px-4 py-3.5 text-center print:hidden whitespace-nowrap">
                                        <input type="checkbox" class="rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500" @change="toggleSelectAll" :checked="selectedCustomers.length === paginatedCustomers.length && paginatedCustomers.length > 0" />
                                    </th>
                                    <th scope="col" class="w-16 px-4 py-3.5 text-center whitespace-nowrap">No</th>
                                    <th scope="col" class="px-6 py-3.5 whitespace-nowrap">Nama Pelanggan</th>
                                    <th scope="col" class="px-6 py-3.5 whitespace-nowrap">Area</th>
                                    <th scope="col" class="px-6 py-3.5 whitespace-nowrap">Alamat</th>
                                    <th scope="col" class="px-6 py-3.5 print:hidden whitespace-nowrap">Nama Paket</th>
                                    <th scope="col" class="px-6 py-3.5 print:hidden whitespace-nowrap">Tanggal Register</th>
                                    <th scope="col" class="px-6 py-3.5 print:hidden whitespace-nowrap">Pembayaran Terakhir</th>
                                    <th scope="col" class="px-6 py-3.5 text-right whitespace-nowrap">Tagihan</th>
                                    <th scope="col" class="px-6 py-3.5 text-center print:hidden whitespace-nowrap">Status Pelanggan</th>
                                    <th scope="col" class="px-6 py-3.5 text-center print:hidden whitespace-nowrap">Status</th>
                                    <th scope="col" class="px-6 py-3.5 text-center print:hidden whitespace-nowrap">Janji Bayar</th>
                                    <th scope="col" class="w-36 px-6 py-3.5 text-center print:hidden whitespace-nowrap">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <tr
                                    v-for="(customer, index) in paginatedCustomers"
                                    :key="customer.id || index"
                                    class="transition-colors duration-150 hover:bg-slate-50/80"
                                >
                                    <!-- Checkbox -->
                                    <td class="whitespace-nowrap px-4 py-4 text-center print:hidden">
                                        <input type="checkbox" class="rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500" v-model="selectedCustomers" :value="customer.id" />
                                    </td>
                                    
                                    <!-- Row Number -->
                                    <td class="whitespace-nowrap print:whitespace-normal px-4 py-4 text-center text-xs font-medium text-slate-400">
                                        {{ index + 1 }}
                                    </td>

                                    <!-- Customer Name -->
                                    <td class="whitespace-nowrap print:whitespace-normal px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-600 ring-1 ring-slate-200">
                                                {{ (customer.name || '?').charAt(0).toUpperCase() }}
                                            </div>
                                            <div>
                                                <div class="font-semibold text-slate-800">
                                                    {{ customer.name }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Area -->
                                    <td class="whitespace-nowrap print:whitespace-normal px-6 py-4 text-slate-600">
                                        <div class="flex items-center gap-1.5">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            {{ customer.area || '-' }}
                                        </div>
                                    </td>

                                    <!-- Address -->
                                    <td class="px-6 py-4 text-slate-600">
                                        <span class="line-clamp-2 max-w-[200px]" :title="customer.alamat">
                                            {{ customer.alamat || '-' }}
                                        </span>
                                    </td>

                                    <!-- Package -->
                                    <td class="whitespace-nowrap print:hidden px-6 py-4 font-medium text-slate-700">
                                        {{ customer.paket || '-' }}
                                    </td>

                                    <!-- Tanggal Register -->
                                    <td class="whitespace-nowrap print:hidden px-6 py-4 text-xs text-slate-600">
                                        {{ formatDate(customer.register_date) }}
                                    </td>

                                    <!-- Pembayaran Terakhir -->
                                    <td class="whitespace-nowrap print:hidden px-6 py-4 text-xs text-slate-600">
                                        {{ formatDate(customer.last_paid_date) }}
                                    </td>

                                    <!-- Amount -->
                                    <td class="whitespace-nowrap px-6 py-4 text-right font-medium text-slate-800">
                                        {{ isAktif(customer) ? formatRupiah(customer.amount) : formatRupiah(0) }}
                                    </td>

                                    <!-- Status Pelanggan -->
                                    <td class="whitespace-nowrap px-6 py-4 text-center">
                                        <span
                                            v-if="String(customer.status_pelanggan || 'Aktif').toLowerCase() === 'aktif'"
                                            class="inline-flex items-center gap-1.5 rounded-md bg-blue-100 px-2.5 py-1 text-xs font-medium text-blue-700"
                                        >
                                            Aktif
                                        </span>
                                        <span
                                            v-else-if="String(customer.status_pelanggan).toLowerCase() === 'berhenti sementara'"
                                            class="inline-flex items-center gap-1.5 rounded-md bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-700"
                                        >
                                            Berhenti sementara
                                        </span>
                                        <span
                                            v-else-if="String(customer.status_pelanggan).toLowerCase() === 'stop permanen'"
                                            class="inline-flex items-center gap-1.5 rounded-md bg-rose-100 px-2.5 py-1 text-xs font-medium text-rose-700"
                                        >
                                            Stop Permanen
                                        </span>
                                        <span
                                            v-else-if="String(customer.status_pelanggan).toLowerCase() === 'gratis'"
                                            class="inline-flex items-center gap-1.5 rounded-md bg-purple-100 px-2.5 py-1 text-xs font-medium text-purple-700"
                                        >
                                            Gratis
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center gap-1.5 rounded-md bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700"
                                        >
                                            {{ customer.status_pelanggan }}
                                        </span>
                                    </td>

                                    <!-- Status Badge -->
                                    <td class="whitespace-nowrap print:hidden px-6 py-4 text-center">
                                        <span
                                            v-if="String(customer.status_pelanggan || 'Aktif').toLowerCase() !== 'aktif'"
                                            class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 ring-1 ring-inset ring-slate-600/20"
                                        >
                                            <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                                            {{ customer.status_pelanggan }}
                                        </span>
                                        <div v-else-if="String(customer.status).toLowerCase() === 'paid'" class="flex flex-col items-center justify-center gap-1">
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-600"></span>
                                                Lunas
                                            </span>
                                            <span v-if="customer.last_paid_by" class="text-[10px] text-slate-500 font-medium">
                                                oleh {{ customer.last_paid_by }}
                                            </span>
                                        </div>
                                        <div v-else-if="String(customer.status).toLowerCase() === 'prorata'" class="flex flex-col items-center justify-center gap-1">
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-600/20">
                                                <span class="h-1.5 w-1.5 rounded-full bg-indigo-600"></span>
                                                Prorata
                                            </span>
                                        </div>
                                        <div v-else-if="String(customer.status).toLowerCase() === 'nunggak'" class="flex flex-col items-center justify-center gap-1">
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-100 px-3 py-1 text-xs font-semibold text-rose-700 ring-1 ring-inset ring-rose-600/20">
                                                <span class="h-1.5 w-1.5 rounded-full bg-rose-600"></span>
                                                Nunggak
                                            </span>
                                            <span v-if="activeTab === 'jatuh_tempo' && getLamaNunggak(customer)" class="text-[10px] text-rose-600 font-semibold mt-0.5">
                                                {{ getLamaNunggak(customer) }}
                                            </span>
                                        </div>
                                        <div v-else class="flex flex-col items-center justify-center gap-1">
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-600/20">
                                                <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                                Belum Bayar
                                            </span>
                                            <span v-if="activeTab === 'jatuh_tempo' && getLamaNunggak(customer)" class="text-[10px] text-rose-600 font-semibold mt-0.5">
                                                Nunggak {{ getLamaNunggak(customer) }}
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Janji Bayar -->
                                    <td class="whitespace-nowrap print:hidden px-6 py-4 text-center">
                                        <div v-if="customer.promise_date && String(customer.status).toLowerCase() !== 'paid'" class="flex flex-col items-center gap-1">
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-600/20">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                {{ formatDate(customer.promise_date) }}
                                            </span>
                                            <span v-if="new Date(customer.promise_date) < new Date()" class="text-[10px] font-semibold text-rose-500">Sudah lewat!</span>
                                        </div>
                                        <span v-else-if="String(customer.status).toLowerCase() === 'paid'" class="text-xs text-slate-400">—</span>
                                        <span v-else class="text-xs text-slate-400">Belum diatur</span>
                                    </td>

                                    <!-- Actions -->
                                    <td class="whitespace-nowrap px-6 py-4 text-center print:hidden">
                                        <div class="flex items-center justify-center gap-2">
                                            <!-- Set Lunas Button -->
                                            <button
                                                v-if="String(customer.status).toLowerCase() !== 'paid'"
                                                type="button"
                                                @click="openLunasModal(customer)"
                                                class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-2.5 py-1.5 text-xs font-medium text-white shadow-sm transition duration-150 hover:bg-emerald-700 hover:shadow focus:outline-none focus:ring-2 focus:ring-emerald-500/30"
                                                title="Tandai Sudah Lunas"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                </svg>
                                                <span>Set Lunas</span>
                                            </button>

                                            <!-- Rollback Lunas Button -->
                                            <button
                                                v-if="String(customer.status).toLowerCase() === 'paid'"
                                                type="button"
                                                @click="rollbackCustomer(customer)"
                                                class="inline-flex items-center gap-1 rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1.5 text-xs font-medium text-amber-600 shadow-sm transition duration-150 hover:bg-amber-100 hover:text-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500/30"
                                                title="Batalkan Pelunasan"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                                </svg>
                                                <span>Batal Lunas</span>
                                            </button>

                                            <!-- Set Janji Bayar Button -->
                                            <button
                                                v-if="String(customer.status).toLowerCase() !== 'paid' && isAktif(customer)"
                                                type="button"
                                                @click="openJanjiModal(customer)"
                                                class="inline-flex items-center gap-1 rounded-lg border border-indigo-200 bg-indigo-50 px-2.5 py-1.5 text-xs font-medium text-indigo-600 shadow-sm transition duration-150 hover:bg-indigo-100 hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/30"
                                                :title="customer.promise_date ? 'Ubah Janji Bayar' : 'Set Janji Bayar'"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                <span>{{ customer.promise_date ? 'Ubah' : 'Janji' }}</span>
                                            </button>

                                            <!-- Batalkan Janji Bayar Button -->
                                            <button
                                                v-if="customer.promise_date && String(customer.status).toLowerCase() !== 'paid'"
                                                type="button"
                                                @click="cancelJanji(customer)"
                                                class="inline-flex items-center gap-1 rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1.5 text-xs font-medium text-rose-600 shadow-sm transition duration-150 hover:bg-rose-100 hover:text-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500/30"
                                                title="Batalkan Janji Bayar"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                                <span>Batal Janji</span>
                                            </button>

                                            <!-- Edit Button -->
                                            <button
                                                type="button"
                                                @click="openEditModal(customer)"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition duration-150 hover:bg-indigo-50 hover:text-indigo-600 focus:outline-none"
                                                title="Edit Pelanggan"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>

                                            <!-- Delete Button -->
                                            <button
                                                type="button"
                                                @click="deleteCustomer(customer)"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition duration-150 hover:bg-rose-50 hover:text-rose-600 focus:outline-none"
                                                title="Hapus Pelanggan"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Empty State -->
                                <tr v-if="filteredCustomers.length === 0">
                                    <td colspan="12" class="px-6 py-12 text-center">
                                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                            </svg>
                                        </div>
                                        <h4 class="mt-3 text-sm font-semibold text-slate-800">
                                            Tidak Ada Billing Data
                                        </h4>
                                        <p class="mt-1 text-xs text-slate-500">
                                            <span v-if="searchQuery || statusFilter !== 'all' || areaFilter !== 'all'">
                                                Tidak ditemukan pelanggan yang sesuai dengan filter pencarian Anda.
                                            </span>
                                            <span v-else>
                                                Belum ada Billing Data yang ditambahkan. Gunakan formulir import Excel di atas untuk menambahkan pelanggan.
                                            </span>
                                        </p>
                                        <div v-if="searchQuery || statusFilter !== 'all' || areaFilter !== 'all'" class="mt-4">
                                            <button
                                                type="button"
                                                @click="resetFilters"
                                                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
                                            >
                                                Bersihkan Filter
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-slate-50 font-semibold text-slate-800 hidden print:table-footer-group border-t-2 border-slate-200">
                                <tr>
                                    <td colspan="4" class="px-6 py-4 text-right uppercase tracking-wider text-xs text-slate-500">Total Keseluruhan</td>
                                    <td class="px-6 py-4 text-right whitespace-nowrap">{{ formatRupiah(totalTagihanFiltered) }}</td>
                                </tr>
                            </tfoot>
                        </table>
</div>

                        <!-- Pagination -->
                        <div class="flex items-center justify-between border-t border-slate-100 bg-slate-50 px-5 py-4 print:hidden">
                            <div class="text-sm text-slate-500">
                                Menampilkan <span class="font-medium text-slate-900">{{ (currentPage - 1) * itemsPerPage + 1 }}</span> - 
                                <span class="font-medium text-slate-900">{{ Math.min(currentPage * itemsPerPage, filteredCustomers.length) }}</span> dari 
                                <span class="font-medium text-slate-900">{{ filteredCustomers.length }}</span> data
                            </div>
                            <div class="flex items-center gap-1">
                                <button
                                    @click="currentPage--" 
                                    :disabled="currentPage === 1"
                                    class="px-3 py-1.5 border border-slate-200 rounded-lg text-slate-500 bg-white hover:bg-slate-50 hover:text-slate-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium"
                                >
                                    &lt;
                                </button>
                                <button class="px-3 py-1.5 rounded-lg bg-indigo-600 text-white font-medium shadow-sm">{{ currentPage }}</button>
                                <button
                                    @click="currentPage++" 
                                    :disabled="currentPage === totalPages"
                                    class="px-3 py-1.5 border border-slate-200 rounded-lg text-slate-500 bg-white hover:bg-slate-50 hover:text-slate-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium"
                                >
                                    &gt;
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Riwayat Upgrade Table -->
                    <div v-else class="overflow-x-auto rounded-xl border border-slate-200">
                        <div class="overflow-x-auto w-full pb-4">
<table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th scope="col" class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Nama Pelanggan</th>
                                    <th scope="col" class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Paket Sebelumnya</th>
                                    <th scope="col" class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Paket Sekarang</th>
                                    <th scope="col" class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Tanggal Upgrade/Downgrade</th>
                                    <th scope="col" class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white">
                                <tr v-for="history in props.upgradeHistories" :key="history.id" class="hover:bg-slate-50 transition-colors">
                                    <td class="whitespace-nowrap px-4 py-4 text-sm font-bold text-slate-900">{{ history.customer?.name || 'Pelanggan Dihapus' }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-sm text-slate-600">
                                        <span class="inline-flex items-center rounded-md bg-slate-100 px-2.5 py-0.5 text-sm font-medium text-slate-800">
                                            {{ history.old_paket || '-' }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4 text-sm text-slate-600">
                                        <span class="inline-flex items-center rounded-md bg-indigo-100 px-2.5 py-0.5 text-sm font-medium text-indigo-800">
                                            {{ history.new_paket || '-' }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4 text-sm text-slate-600">
                                        <div class="flex items-center gap-1.5 text-slate-500">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            {{ new Date(history.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) }}
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4 text-sm text-slate-600">
                                        <span 
                                            class="inline-flex items-center rounded-md px-2.5 py-0.5 text-sm font-bold"
                                            :class="{
                                                'bg-emerald-100 text-emerald-800': getUpgradeStatus(history.old_paket, history.new_paket).color === 'emerald',
                                                'bg-rose-100 text-rose-800': getUpgradeStatus(history.old_paket, history.new_paket).color === 'rose',
                                                'bg-amber-100 text-amber-800': getUpgradeStatus(history.old_paket, history.new_paket).color === 'amber'
                                            }"
                                        >
                                            <svg v-if="getUpgradeStatus(history.old_paket, history.new_paket).color === 'emerald'" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                                            <svg v-else-if="getUpgradeStatus(history.old_paket, history.new_paket).color === 'rose'" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" /></svg>
                                            <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                                            {{ getUpgradeStatus(history.old_paket, history.new_paket).label }}
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="props.upgradeHistories.length === 0">
                                    <td colspan="5" class="px-4 py-12 text-center text-sm text-slate-500">
                                        Tidak ada riwayat perubahan paket.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. SET LUNAS MODAL -->
        <div
            v-if="isLunasModalOpen"
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title"
            role="dialog"
            aria-modal="true"
        >
            <!-- Overlay with Backdrop Blur -->
            <div
                class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity duration-300"
                @click="closeLunasModal"
            ></div>

            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div
                    class="relative w-full max-w-md transform overflow-hidden rounded-2xl border border-slate-100 bg-white p-6 text-left shadow-2xl transition-all duration-300"
                >
                    <!-- Close Button -->
                    <button
                        type="button"
                        @click="closeLunasModal"
                        class="absolute right-4 top-4 rounded-lg p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>

                    <!-- Modal Header -->
                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 id="modal-title" class="text-lg font-bold text-slate-800">
                                Konfirmasi Pelunasan
                            </h3>
                            <p class="text-xs text-slate-500">
                                Tandai tagihan pelanggan sebagai sudah lunas.
                            </p>
                        </div>
                    </div>

                    <!-- Customer Info Card -->
                    <div v-if="activeCustomer" class="mt-5 rounded-xl border border-slate-100 bg-slate-50/80 p-4">
                        <div class="flex items-start justify-between">
                            <div>
                                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                                    Nama Pelanggan
                                </span>
                                <p class="text-sm font-bold text-slate-800">
                                    {{ activeCustomer.name }}
                                </p>
                                <span class="mt-1 inline-flex items-center gap-1 rounded bg-white px-2 py-0.5 text-xs text-slate-600 ring-1 ring-slate-200">
                                    Area: {{ activeCustomer.area || '-' }}
                                </span>
                            </div>
                            <div class="text-right">
                                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                                    Total Tagihan
                                </span>
                                <p class="text-base font-bold text-emerald-600">
                                    {{ formatRupiah(activeCustomer.amount) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Form -->
                    <form @submit.prevent="submitLunas" class="mt-5 space-y-4">
                        <!-- Nominal Pembayaran -->
                        <div>
                            <label for="payment-amount" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">
                                Nominal Pembayaran <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative mt-1.5">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                                    <span class="text-xs font-semibold text-slate-400">Rp</span>
                                </div>
                                <input
                                    id="payment-amount"
                                    type="number"
                                    v-model.number="lunasForm.payment_amount"
                                    :max="activeCustomer?.amount"
                                    min="1"
                                    class="w-full rounded-xl border border-slate-300 py-2.5 pl-10 pr-3.5 text-sm text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                                    required
                                />
                            </div>
                            <!-- Sisa Piutang Info -->
                            <div v-if="activeCustomer && lunasForm.payment_amount > 0 && lunasForm.payment_amount < activeCustomer.amount" class="mt-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2">
                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                    </svg>
                                    <p class="text-xs font-semibold text-amber-800">
                                        Bayar sebagian — Sisa piutang: <span class="text-amber-900">{{ formatRupiah(activeCustomer.amount - lunasForm.payment_amount) }}</span>
                                    </p>
                                </div>
                            </div>
                            <div v-else-if="activeCustomer && lunasForm.payment_amount >= activeCustomer.amount" class="mt-2 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2">
                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <p class="text-xs font-semibold text-emerald-800">Lunas penuh — Tagihan akan terbayar seluruhnya.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Metode Pembayaran -->
                        <div>
                            <label for="payment-method" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">
                                Metode Pembayaran <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative mt-1.5">
                                <select
                                    id="payment-method"
                                    v-model="lunasForm.payment_method_id"
                                    @change="onPaymentMethodSelect"
                                    class="w-full rounded-xl border border-slate-300 py-2.5 pl-3.5 pr-10 text-sm text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                                    required
                                >
                                    <option value="" disabled>Pilih metode pembayaran...</option>
                                    <option
                                        v-for="method in paymentMethods"
                                        :key="method.id"
                                        :value="method.id"
                                    >
                                        {{ method.name }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Tanggal Pembayaran -->
                        <div>
                            <label for="payment-date" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">
                                Tanggal Pembayaran <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative mt-1.5">
                                <input
                                    id="payment-date"
                                    type="date"
                                    v-model="lunasForm.payment_date"
                                    class="w-full rounded-xl border border-slate-300 py-2.5 px-3.5 text-sm text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                                    required
                                />
                            </div>
                        </div>

                        <!-- Janji Bayar Toggle (only shows when partial) -->
                        <div v-if="activeCustomer && lunasForm.payment_amount > 0 && lunasForm.payment_amount < activeCustomer.amount" class="rounded-xl border border-indigo-100 bg-indigo-50/40 p-4 space-y-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <div>
                                        <p class="text-sm font-semibold text-slate-800">Janji Bayar Sisa</p>
                                        <p class="text-[11px] text-slate-500">Atur tanggal pelanggan berjanji bayar sisa tagihan</p>
                                    </div>
                                </div>
                                <label class="relative inline-flex cursor-pointer items-center">
                                    <input type="checkbox" v-model="lunasForm.is_janji_bayar" class="peer sr-only" />
                                    <div class="peer h-6 w-11 rounded-full bg-slate-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow after:transition-all after:content-[''] peer-checked:bg-indigo-600 peer-checked:after:translate-x-full peer-focus:ring-2 peer-focus:ring-indigo-500/30"></div>
                                </label>
                            </div>

                            <!-- Janji Bayar Fields (shown when toggle is ON) -->
                            <div v-if="lunasForm.is_janji_bayar" class="space-y-3 rounded-lg border border-indigo-100 bg-white/80 p-3">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600">Tanggal Janji Bayar <span class="text-rose-500">*</span></label>
                                    <input
                                        type="date"
                                        v-model="lunasForm.promise_date"
                                        class="mt-1 w-full rounded-lg border border-slate-300 py-2 px-3 text-sm text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                        :required="lunasForm.is_janji_bayar"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Modal Actions -->
                        <div class="mt-6 flex items-center justify-end gap-3 pt-2">
                            <button
                                type="button"
                                @click="closeLunasModal"
                                class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="lunasForm.processing"
                                class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-700 hover:shadow focus:outline-none focus:ring-2 focus:ring-emerald-500/30 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <svg
                                    v-if="lunasForm.processing"
                                    class="h-3.5 w-3.5 animate-spin text-white"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                <span>{{ lunasForm.processing ? 'Menyimpan...' : (activeCustomer && lunasForm.payment_amount < activeCustomer.amount ? 'Bayar Sebagian' : 'Tandai Lunas') }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit Customer Modal -->
        <div v-if="isEditModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <!-- Backdrop -->
            <div
                class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity"
                @click="closeEditModal"
            ></div>

            <!-- Modal Panel -->
            <div class="relative w-full max-w-md transform overflow-hidden rounded-2xl bg-white shadow-2xl transition-all">
                <div class="border-b border-slate-100 bg-slate-50/50 px-6 py-4">
                    <h3 class="text-lg font-bold text-slate-800">Edit Pelanggan</h3>
                    <p class="mt-1 text-xs text-slate-500">Perbarui informasi Billing Data.</p>
                </div>

                <div class="p-6">
                    <form @submit.prevent="submitEdit">
                        <div class="space-y-4">
                            <!-- Name -->
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-600">
                                    Nama Pelanggan <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="editForm.name"
                                    type="text"
                                    class="w-full rounded-xl border border-slate-300 py-2.5 px-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                    required
                                />
                                <p v-if="editForm.errors.name" class="mt-1 text-xs text-rose-600">{{ editForm.errors.name }}</p>
                            </div>

                            <!-- Area -->
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-600">
                                    Area / Wilayah
                                </label>
                                <select
                                    v-model="editForm.area"
                                    class="w-full rounded-xl border border-slate-300 py-2.5 px-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 bg-white"
                                >
                                    <option value="">-- Pilih Area --</option>
                                    <option v-for="a in uniqueAreas" :key="a" :value="a">{{ a }}</option>
                                </select>
                                <p v-if="editForm.errors.area" class="mt-1 text-xs text-rose-600">{{ editForm.errors.area }}</p>
                            </div>

                            <!-- Alamat -->
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-600">
                                    Alamat Lengkap
                                </label>
                                <textarea
                                    v-model="editForm.alamat"
                                    rows="2"
                                    class="w-full rounded-xl border border-slate-300 py-2.5 px-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                ></textarea>
                                <p v-if="editForm.errors.alamat" class="mt-1 text-xs text-rose-600">{{ editForm.errors.alamat }}</p>
                            </div>

                            <!-- Paket -->
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">
                                        Paket Pelanggan
                                    </label>
                                    <label class="flex items-center cursor-pointer group">
                                        <input type="checkbox" v-model="isUpgrade" class="sr-only peer">
                                        <div class="relative w-8 h-4 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:bg-indigo-600"></div>
                                        <span class="ml-2 text-xs font-bold text-slate-500 group-hover:text-indigo-600 peer-checked:text-indigo-600 transition-colors">UPGRADE PAKET</span>
                                    </label>
                                </div>
                                <select 
                                    v-model="editForm.paket" 
                                    @change="onPaketChange"
                                    :disabled="!isUpgrade"
                                    class="w-full rounded-xl border border-slate-300 py-2.5 px-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 disabled:bg-slate-100 disabled:text-slate-500"
                                >
                                    <option value="" disabled>Pilih Paket Internet</option>
                                    <option v-for="pkg in internetPackages" :key="pkg.id" :value="pkg.name">
                                        {{ pkg.name }}
                                    </option>
                                    <option value="Lainnya">Lainnya...</option>
                                </select>
                                <p v-if="editForm.errors.paket" class="mt-1 text-xs text-rose-600">{{ editForm.errors.paket }}</p>

                                <div v-if="isUpgrade" class="mt-3 bg-indigo-50/50 p-3 rounded-lg border border-indigo-100">
                                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-indigo-700">
                                        Tagihan Saat Ini (Rp) <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="editForm.amount"
                                        type="number"
                                        class="w-full rounded-lg border border-indigo-200 bg-white py-2 px-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                        placeholder="0"
                                    />
                                    <p class="text-[10px] text-indigo-600 mt-1">Atur nominal tagihan secara manual untuk satu bulan ini saja.</p>
                                </div>
                            </div>

                            <!-- Register Date -->
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-600">
                                    Tanggal Register
                                </label>
                                <input
                                    v-model="editForm.register_date"
                                    type="date"
                                    class="w-full rounded-xl border border-slate-300 py-2.5 px-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                />
                                <p v-if="editForm.errors.register_date" class="mt-1 text-xs text-rose-600">{{ editForm.errors.register_date }}</p>
                            </div>

                            <!-- Status Pelanggan -->
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-600">
                                    Status Pelanggan
                                </label>
                                <select
                                    v-model="editForm.status_pelanggan"
                                    class="w-full rounded-xl border border-slate-300 py-2.5 px-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                >
                                    <option value="Aktif">Aktif</option>
                                    <option value="Berhenti sementara">Berhenti sementara</option>
                                    <option value="Stop Permanen">Stop Permanen</option>
                                    <option value="Gratis">Gratis</option>
                                </select>
                                <p v-if="editForm.errors.status_pelanggan" class="mt-1 text-xs text-rose-600">{{ editForm.errors.status_pelanggan }}</p>
                            </div>

                            <!-- Conditional Fields for Berhenti Sementara -->
                            <div v-if="editForm.status_pelanggan === 'Berhenti sementara'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-600">
                                        Tanggal Mulai
                                    </label>
                                    <input
                                        v-model="editForm.suspend_start_date"
                                        type="date"
                                        class="w-full rounded-xl border border-slate-300 py-2.5 px-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                    />
                                    <p v-if="editForm.errors.suspend_start_date" class="mt-1 text-xs text-rose-600">{{ editForm.errors.suspend_start_date }}</p>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-600">
                                        Tanggal Selesai
                                    </label>
                                    <input
                                        v-model="editForm.suspend_end_date"
                                        type="date"
                                        class="w-full rounded-xl border border-slate-300 py-2.5 px-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                    />
                                    <p v-if="editForm.errors.suspend_end_date" class="mt-1 text-xs text-rose-600">{{ editForm.errors.suspend_end_date }}</p>
                                </div>
                            </div>

                            <!-- Conditional Field for Stop Permanen -->
                            <div v-if="editForm.status_pelanggan === 'Stop Permanen'">
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-600">
                                    Tanggal Berhenti
                                </label>
                                <input
                                    v-model="editForm.stop_date"
                                    type="date"
                                    class="w-full rounded-xl border border-slate-300 py-2.5 px-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                />
                                <p v-if="editForm.errors.stop_date" class="mt-1 text-xs text-rose-600">{{ editForm.errors.stop_date }}</p>
                            </div>

                            <!-- Pembayaran Terakhir -->
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-600">
                                    Pembayaran Terakhir
                                </label>
                                <input
                                    v-model="editForm.last_paid_date"
                                    type="date"
                                    class="w-full rounded-xl border border-slate-300 py-2.5 px-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                />
                                <p v-if="editForm.errors.last_paid_date" class="mt-1 text-xs text-rose-600">{{ editForm.errors.last_paid_date }}</p>
                            </div>

                            <!-- Biaya Bulanan (Base Amount) -->
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-600">
                                    Biaya Bulanan (Rp) <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="editForm.base_amount"
                                    type="number"
                                    min="0"
                                    class="w-full rounded-xl border border-slate-300 py-2.5 px-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                    required
                                />
                                <p v-if="editForm.errors.base_amount" class="mt-1 text-xs text-rose-600">{{ editForm.errors.base_amount }}</p>
                            </div>

                            <!-- Toggle Prorata (untuk pelanggan baru) -->
                            <div class="flex items-center justify-between bg-slate-50 p-3 rounded-xl border border-slate-200">
                                <div>
                                    <h4 class="text-sm font-semibold text-slate-800">Prorata Awal</h4>
                                    <p class="text-xs text-slate-500">Atur nominal tagihan secara manual untuk bulan pertama.</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="isProrata" class="sr-only peer">
                                    <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                    <span class="ml-3 text-sm font-medium text-slate-700">{{ isProrata ? 'ON' : 'OFF' }}</span>
                                </label>
                            </div>

                            <!-- Tagihan (Prorata) -->
                            <div v-if="isProrata">
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-600">
                                    Tagihan Bulan Pertama (Rp) <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="editForm.amount"
                                    type="number"
                                    min="0"
                                    class="w-full rounded-xl border border-slate-300 py-2.5 px-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                    required
                                />
                                <p v-if="editForm.errors.amount" class="mt-1 text-xs text-rose-600">{{ editForm.errors.amount }}</p>
                            </div>
                        </div>

                        <!-- Modal Actions -->
                        <div class="mt-6 flex items-center justify-end gap-3 pt-2">
                            <button
                                type="button"
                                @click="closeEditModal"
                                class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="editForm.processing"
                                class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-indigo-700 hover:shadow focus:outline-none focus:ring-2 focus:ring-indigo-500/30 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <svg
                                    v-if="editForm.processing"
                                    class="h-3.5 w-3.5 animate-spin text-white"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                <span>{{ editForm.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- Janji Bayar Modal -->
        <div v-if="isJanjiModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="closeJanjiModal"></div>
            <div class="relative w-full max-w-md rounded-2xl bg-white/95 backdrop-blur-xl shadow-2xl ring-1 ring-slate-200/60">
                <!-- Header -->
                <div class="border-b border-slate-100 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-slate-900">Set Janji Bayar</h3>
                            <p class="text-xs text-slate-500">{{ activeJanjiCustomer?.name }}</p>
                        </div>
                    </div>
                    <button @click="closeJanjiModal" class="absolute right-4 top-4 rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Body -->
                <form @submit.prevent="submitJanji" class="px-6 py-5">
                    <div class="space-y-4">
                        <!-- Info Box -->
                        <div class="rounded-xl border border-indigo-100 bg-indigo-50/50 p-3.5">
                            <div class="flex items-start gap-2.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 shrink-0 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div class="text-xs text-indigo-800">
                                    <p class="font-semibold">Tagihan: {{ formatRupiah(activeJanjiCustomer?.amount) }}</p>
                                    <p class="mt-0.5 text-indigo-600">Atur tanggal pelanggan berjanji untuk membayar.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Promise Date Input -->
                        <div>
                            <label for="promise_date_input" class="block text-sm font-medium text-slate-700">Tanggal Janji Bayar</label>
                            <input
                                id="promise_date_input"
                                type="date"
                                v-model="janjiForm.promise_date"
                                class="mt-1.5 block w-full rounded-xl border-0 px-3.5 py-2.5 text-sm text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600"
                            />
                            <p v-if="janjiForm.errors.promise_date" class="mt-1.5 text-xs text-rose-600">{{ janjiForm.errors.promise_date }}</p>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="mt-6 flex items-center justify-end gap-3">
                        <button
                            type="button"
                            @click="closeJanjiModal"
                            class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="janjiForm.processing"
                            class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500/30 disabled:opacity-50"
                        >
                            <svg
                                v-if="janjiForm.processing"
                                class="h-3.5 w-3.5 animate-spin text-white"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span>{{ janjiForm.processing ? 'Menyimpan...' : 'Simpan Janji Bayar' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
