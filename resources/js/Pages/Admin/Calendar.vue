<script setup>
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Table, TableBody, TableCaption, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogDescription } from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Search, X, Loader2, ChevronLeft, ChevronRight, Download, Wallet } from 'lucide-vue-next';
import { ref, computed } from 'vue';

const props = defineProps({
 calendar: Array,
 year: Number,
});

const isDemo = computed(() => usePage().props.auth.is_demo);

const months = [
 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
];

const changeYear = (delta) => {
 router.get(route('admin.calendar'), { year: props.year + delta }, { preserveState: false });
};

const searchQuery = ref('');
const filteredCalendar = computed(() => {
 const q = searchQuery.value.toLowerCase().trim();
 if (!q) return props.calendar;
 return props.calendar.filter(row =>
 row.name.toLowerCase().includes(q) || row.owner.toLowerCase().includes(q)
 );
});

const selectedDue = ref(null);
const isModalOpen = ref(false);
const today = new Date().toISOString().slice(0, 10);
const form = useForm({
 amount: '',
 payment_date: today,
});

const displayAmount = ref('');

const openPaymentModal = (dueData, houseName, monthName) => {
 if (!dueData.due_id) return; // No bill exists

 selectedDue.value = {
 id: dueData.due_id,
 house: houseName,
 month: monthName,
 bill_amount: dueData.bill_amount,
 paid_amount: dueData.paid_amount,
 remaining: Math.max(0, dueData.bill_amount - dueData.paid_amount),
 is_edit: dueData.paid_amount > 0,
 };

 // If already paid, show current amount for editing; else show remaining
 const initialAmount = dueData.paid_amount > 0 ? dueData.paid_amount : (selectedDue.value.remaining > 0 ? selectedDue.value.remaining : 0);

 form.amount = initialAmount;
 form.payment_date = today;
 displayAmount.value = initialAmount > 0 ? formatNumber(initialAmount) : '';

 isModalOpen.value = true;
};

const formatNumber = (num) => {
 if (!num && num !== 0) return '';
 return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
};

const handleInput = (e) => {
 let value = e.target.value.replace(/\./g, ''); // Remove existing dots
 if (value === '') {
 form.amount = '';
 displayAmount.value = '';
 return;
 }
 
 // Only allow numbers
 if (!/^\d+$/.test(value)) {
 displayAmount.value = formatNumber(form.amount);
 return;
 }

 const numericValue = parseInt(value, 10);
 form.amount = numericValue;
 displayAmount.value = formatNumber(numericValue);
};

const submitPayment = () => {
 if (!selectedDue.value || form.amount === '' || form.amount === null) return;

 form.post(route('admin.payment.store', selectedDue.value.id), {
 onSuccess: () => {
 isModalOpen.value = false;
 form.amount = '';
 form.payment_date = today;
 displayAmount.value = '';
 },
 });
};

// --- Lump Sum Modal ---
const selectedHouse = ref(null);
const isLumpSumModalOpen = ref(false);
const lumpSumForm = useForm({
 amount: '',
 payment_date: today,
 year: props.year,
 due_ids: [],
});
const lumpSumDisplayAmount = ref('');
const selectedDueIds = ref([]);

// Build list of unpaid months for the selected house
const unpaidMonths = computed(() => {
 if (!selectedHouse.value) return [];
 const result = [];
 for (const [m, data] of Object.entries(selectedHouse.value.months)) {
 if (data.due_id && data.status !== 'paid') {
 const remaining = Math.max(0, data.bill_amount - data.paid_amount);
 if (remaining > 0) {
 result.push({
 month: parseInt(m),
 label: months[parseInt(m) - 1],
 due_id: data.due_id,
 bill_amount: data.bill_amount,
 paid_amount: data.paid_amount,
 remaining,
 });
 }
 }
 }
 return result;
});

const selectedTotal = computed(() => {
 return unpaidMonths.value
 .filter(m => selectedDueIds.value.includes(m.due_id))
 .reduce((sum, m) => sum + m.remaining, 0);
});

const toggleDue = (dueId) => {
 const idx = selectedDueIds.value.indexOf(dueId);
 if (idx >= 0) {
 selectedDueIds.value.splice(idx, 1);
 } else {
 selectedDueIds.value.push(dueId);
 }
 // Auto-update amount to match selected total
 const total = selectedTotal.value;
 lumpSumForm.amount = total > 0 ? total : '';
 lumpSumDisplayAmount.value = total > 0 ? formatNumber(total) : '';
};

const toggleAllDues = () => {
 if (selectedDueIds.value.length === unpaidMonths.value.length) {
 selectedDueIds.value = [];
 lumpSumForm.amount = '';
 lumpSumDisplayAmount.value = '';
 } else {
 selectedDueIds.value = unpaidMonths.value.map(m => m.due_id);
 const total = selectedTotal.value;
 lumpSumForm.amount = total > 0 ? total : '';
 lumpSumDisplayAmount.value = total > 0 ? formatNumber(total) : '';
 }
};

const openLumpSumModal = (houseRow) => {
 selectedHouse.value = houseRow;
 lumpSumForm.year = props.year;
 lumpSumForm.payment_date = today;

 // Auto-select all unpaid months
 const allUnpaidIds = [];
 for (const [, data] of Object.entries(houseRow.months)) {
 if (data.due_id && data.status !== 'paid') {
 const remaining = Math.max(0, data.bill_amount - data.paid_amount);
 if (remaining > 0) allUnpaidIds.push(data.due_id);
 }
 }
 selectedDueIds.value = allUnpaidIds;

 const totalUnpaid = houseRow.total_unpaid;
 lumpSumForm.amount = totalUnpaid > 0 ? totalUnpaid : '';
 lumpSumDisplayAmount.value = totalUnpaid > 0 ? formatNumber(totalUnpaid) : '';

 isLumpSumModalOpen.value = true;
};

const handleLumpSumInput = (e) => {
 let value = e.target.value.replace(/\./g, '');
 if (value === '') {
 lumpSumForm.amount = '';
 lumpSumDisplayAmount.value = '';
 return;
 }
 if (!/^\d+$/.test(value)) {
 lumpSumDisplayAmount.value = formatNumber(lumpSumForm.amount);
 return;
 }
 const numericValue = parseInt(value, 10);
 lumpSumForm.amount = numericValue;
 lumpSumDisplayAmount.value = formatNumber(numericValue);
};

const submitLumpSum = () => {
 if (!selectedHouse.value || lumpSumForm.amount === '' || lumpSumForm.amount === null) return;
 if (selectedDueIds.value.length === 0) return;

 lumpSumForm.due_ids = selectedDueIds.value;
 lumpSumForm.post(route('admin.payment.lump-sum', selectedHouse.value.id), {
 onSuccess: () => {
 isLumpSumModalOpen.value = false;
 lumpSumForm.amount = '';
 lumpSumForm.payment_date = today;
 lumpSumDisplayAmount.value = '';
 selectedDueIds.value = [];
 },
 });
};

const getStatusColor = (data) => {
 if (data.status === 'none') return 'bg-gray-100 text-gray-300';
 
 // Logic: Red if unpaid/0, Yellow if partial, Green if full/paid
 if (data.status === 'paid') return 'bg-green-100 text-green-700 border border-green-200';
 if (data.paid_amount > 0) return 'bg-yellow-100 text-yellow-700 border border-yellow-200'; // Partial
 if (data.status === 'overdue') return 'bg-red-100 text-red-700 border border-red-200';
 
 return 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50'; // Unpaid default
};

const formatCurrency = (amount) => {
 return new Intl.NumberFormat('id-ID', {
 style: 'currency',
 currency: 'IDR',
 minimumFractionDigits: 0,
 maximumFractionDigits: 0,
 }).format(amount);
};

// --- WhatsApp Reminder ---
const page = usePage();
const flash = computed(() => page.props.flash || {});
const showFlash = ref(true);
const reminderTarget = ref(null);
const isReminderModalOpen = ref(false);
const reminderLoading = ref(false);

const hasUnpaid = (row) => {
 return Object.values(row.months).some(m => m.status === 'unpaid' || m.status === 'overdue');
};

const reminderMessage = ref('');
const reminderPreviewLoading = ref(false);

const openReminderModal = async (row) => {
 reminderTarget.value = row;
 reminderMessage.value = '';
 reminderPreviewLoading.value = true;
 isReminderModalOpen.value = true;

 try {
 const response = await fetch(route('admin.reminder.preview', row.id) + '?year=' + props.year);
 const data = await response.json();
 reminderMessage.value = data.message || data.error || 'Tidak ada tagihan yang perlu diingatkan.';
 } catch (e) {
 reminderMessage.value = 'Gagal memuat preview pesan.';
 } finally {
 reminderPreviewLoading.value = false;
 }
};

const confirmSendReminder = () => {
 if (!reminderTarget.value) return;
 reminderLoading.value = true;

 router.post(route('admin.reminder.send', reminderTarget.value.id), {
 year: props.year,
 }, {
 preserveScroll: true,
 onFinish: () => {
 reminderLoading.value = false;
 isReminderModalOpen.value = false;
 showFlash.value = true;
 },
 });
};
</script>

<template>
 <Head title="Kalender Iuran" />

 <AuthenticatedLayout>
 <template #header>
 <div class="flex justify-between items-center">
 <h2 class="text-xl font-semibold leading-tight text-gray-800">
 Kalender Iuran Tahunan ({{ year }})
 </h2>
 </div>
 </template>

 <div class="py-12">
 <div class="mx-auto max-w-[95%] sm:px-6 lg:px-8">
 <Card>
 <CardHeader>
 <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
 <div>
 <CardTitle>Matrix Pembayaran</CardTitle>
 <p v-if="!isDemo" class="text-sm text-muted-foreground mt-1">Klik pada kolom bulan untuk mencatat pembayaran manual.</p>
 <p v-else class="text-sm text-amber-600 mt-1 font-medium">Mode Demo — hanya melihat data, tidak bisa mengubah.</p>
 </div>
 <div class="flex items-center gap-3 shrink-0">
 <div class="flex items-center gap-2">
 <Button variant="outline" size="icon" class="h-8 w-8" @click="changeYear(-1)">
 <ChevronLeft class="w-4 h-4" />
 </Button>
 <span class="text-lg font-bold min-w-[60px] text-center">{{ year }}</span>
 <Button variant="outline" size="icon" class="h-8 w-8" @click="changeYear(1)">
 <ChevronRight class="w-4 h-4" />
 </Button>
 </div>
 <a :href="route('admin.calendar.export-pdf', { year: year })" target="_blank" class="inline-flex">
 <Button variant="outline" size="sm" class="flex items-center gap-2">
 <Download class="w-4 h-4" />
 Export PDF
 </Button>
 </a>
 </div>
 </div>
 </CardHeader>
 <CardContent class="overflow-x-auto">
 <!-- Flash Message -->
 <div v-if="showFlash && (flash.success || flash.error)" class="mb-4 rounded-lg px-4 py-3 text-sm flex items-center justify-between" :class="flash.success ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'">
 <span>{{ flash.success || flash.error }}</span>
 <button @click="showFlash = false" class="ml-2 text-current opacity-60 hover:opacity-100">&times;</button>
 </div>

 <!-- Search -->
 <div class="mb-4 relative max-w-xs">
 <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
 <Input
 v-model="searchQuery"
 type="text"
 placeholder="Cari rumah atau pemilik..."
 class="pl-9 pr-8 h-9 text-sm"
 />
 <button v-if="searchQuery" @click="searchQuery = ''" class="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground">
 <X class="w-4 h-4" />
 </button>
 </div>

 <Table class="w-full text-center text-xs md:text-sm">
 <TableHeader>
 <TableRow>
 <TableHead class="text-left sticky left-0 bg-white z-10 w-40 border-r shadow-sm">Rumah</TableHead>
 <TableHead v-for="month in months" :key="month" class="text-center min-w-[100px]">{{ month }}</TableHead>
 </TableRow>
 </TableHeader>
 <TableBody>
 <TableRow v-for="row in filteredCalendar" :key="row.id">
 <TableCell class="text-left font-bold sticky left-0 bg-white z-10 border-r shadow-sm">
 <div class="flex items-center justify-between gap-1">
 <div>
 {{ row.name }}
 <div class="text-[10px] text-muted-foreground font-normal truncate max-w-[100px]">{{ row.owner }}</div>
 </div>
 <div class="flex items-center gap-1">
 <button
 v-if="row.total_unpaid > 0 && !isDemo"
 @click.stop="openLumpSumModal(row)"
 class="shrink-0 w-7 h-7 rounded-full flex items-center justify-center bg-blue-500 hover:bg-blue-600 text-white transition-colors shadow-sm"
 title="Bayar Sekaligus"
 >
 <Wallet class="w-3.5 h-3.5" />
 </button>
 <button
 v-if="hasUnpaid(row) && row.phone && !isDemo"
 @click.stop="openReminderModal(row)"
 class="shrink-0 w-7 h-7 rounded-full flex items-center justify-center bg-green-500 hover:bg-green-600 text-white transition-colors shadow-sm"
 title="Kirim Reminder WA"
 >
 <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor">
 <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.0740-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
 </svg>
 </button>
 </div>
 </div>
 </TableCell>
 <TableCell v-for="(data, m) in row.months" :key="m" class="p-1">
 <div 
 class="h-10 mx-auto flex flex-col items-center justify-center rounded-md transition-all text-[10px] sm:text-xs px-1"
 :class="[getStatusColor(data), isDemo ? '' : 'cursor-pointer hover:brightness-95']"
 @click="!isDemo && openPaymentModal(data, row.name, months[parseInt(m)-1])"
 :title="data.amount > 0 ? `Tagihan: ${formatCurrency(data.bill_amount)}` : 'No Bill'"
 >
 <template v-if="data.status !== 'none'">
 <span v-if="data.paid_amount > 0" class="font-bold">{{ formatCurrency(data.paid_amount) }}</span>
 <span v-else class="text-muted-foreground">-</span>
 </template>
 <span v-else-if="row.is_subsidized" class="text-[9px] font-bold text-slate-400">SUBSIDI</span>
 <span v-else>•</span>
 </div>
 </TableCell>
 </TableRow>
 </TableBody>
 </Table>
 </CardContent>
 </Card>
 </div>
 </div>

 <!-- Manual Payment Modal (per bulan) -->
 <Dialog :open="isModalOpen" @update:open="isModalOpen = $event">
 <DialogContent class="sm:max-w-md">
 <DialogHeader>
 <DialogTitle>{{ selectedDue?.is_edit ? 'Ubah Pembayaran' : 'Input Pembayaran Manual' }}</DialogTitle>
 </DialogHeader>

 <div v-if="selectedDue" class="space-y-4 py-4">
 <!-- Info Summary -->
 <div class="space-y-3 rounded-lg border bg-muted/50 p-4">
 <div class="flex justify-between items-center text-sm">
 <span class="text-muted-foreground font-medium">Rumah</span>
 <span class="font-bold text-base">{{ selectedDue.house }}</span>
 </div>
 <div class="flex justify-between items-center text-sm">
 <span class="text-muted-foreground font-medium">Bulan</span>
 <span class="font-semibold">{{ selectedDue.month }} {{ year }}</span>
 </div>
 <div class="flex justify-between items-center text-sm">
 <span class="text-muted-foreground font-medium">Tagihan Bulanan</span>
 <span class="font-semibold">{{ formatCurrency(selectedDue.bill_amount) }}</span>
 </div>
 <div class="flex justify-between items-center text-sm pt-2 border-t">
 <span class="text-muted-foreground font-medium">Sudah Dibayar</span>
 <span class="text-green-600 font-bold uppercase text-xs">{{ formatCurrency(selectedDue.paid_amount) }}</span>
 </div>
 <div v-if="selectedDue.remaining > 0" class="flex justify-between items-center text-sm">
 <span class="text-muted-foreground font-medium">Sisa Tagihan</span>
 <span class="text-red-500 font-bold">{{ formatCurrency(selectedDue.remaining) }}</span>
 </div>
 </div>

 <!-- Amount Input -->
 <div class="space-y-2">
 <Label for="amount" class="text-sm font-semibold">Jumlah Bayar (Rp)</Label>
 <Input
 id="amount"
 :modelValue="displayAmount"
 @input="handleInput"
 type="text"
 class="pl-3 h-11 text-lg font-bold"
 placeholder="0"
 autofocus
 />
 <p class="text-[11px] text-muted-foreground">
 {{ selectedDue?.is_edit ? '*Masukkan nominal baru untuk mengganti data yang lama.' : '*Masukkan nominal uang yang diterima saat ini. Masukkan 0 untuk menghapus.' }}
 </p>
 <p v-if="form.errors.amount" class="text-xs text-red-500 font-medium">{{ form.errors.amount }}</p>
 </div>

 <!-- Date Input -->
 <div class="space-y-2">
 <Label for="payment_date" class="text-sm font-semibold">Tanggal Pembayaran</Label>
 <Input
 id="payment_date"
 v-model="form.payment_date"
 type="date"
 class="h-11"
 :max="today"
 />
 <p class="text-[11px] text-muted-foreground">
 *Tanggal warga melakukan pembayaran (default: hari ini).
 </p>
 </div>
 </div>

 <DialogFooter>
 <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 w-full">
 <Button type="button" variant="ghost" @click="isModalOpen = false" class="sm:w-auto">
 Batal
 </Button>
 <Button type="submit" @click="submitPayment" :disabled="form.processing" class="sm:w-auto px-8">
 {{ selectedDue?.is_edit ? 'Update Data' : 'Simpan Pembayaran' }}
 </Button>
 </div>
 </DialogFooter>
 </DialogContent>
 </Dialog>

 <!-- WA Reminder Confirmation Modal -->
 <Dialog :open="isReminderModalOpen" @update:open="isReminderModalOpen = $event">
 <DialogContent class="sm:max-w-md">
 <DialogHeader>
 <DialogTitle>Kirim Reminder WhatsApp</DialogTitle>
 <DialogDescription>Konfirmasi pengiriman pesan</DialogDescription>
 </DialogHeader>

 <div v-if="reminderTarget" class="space-y-3 py-4">
 <div class="rounded-lg border bg-muted/50 p-4 space-y-2">
 <div class="flex justify-between text-sm">
 <span class="text-muted-foreground">Rumah</span>
 <span class="font-bold">{{ reminderTarget.name }}</span>
 </div>
 <div class="flex justify-between text-sm">
 <span class="text-muted-foreground">Pemilik</span>
 <span class="font-semibold">{{ reminderTarget.owner }}</span>
 </div>
 <div class="flex justify-between text-sm">
 <span class="text-muted-foreground">No. HP</span>
 <span class="font-semibold">{{ reminderTarget.phone || '-' }}</span>
 </div>
 </div>

 <!-- Message Preview -->
 <div class="space-y-1">
 <p class="text-xs font-semibold text-muted-foreground">Pesan yang akan dikirim:</p>
 <div class="rounded-lg border bg-green-50 p-3 max-h-60 overflow-y-auto">
 <div v-if="reminderPreviewLoading" class="flex items-center justify-center py-4">
 <Loader2 class="w-5 h-5 animate-spin text-muted-foreground" />
 <span class="ml-2 text-xs text-muted-foreground">Memuat preview...</span>
 </div>
 <pre v-else class="text-xs whitespace-pre-wrap font-sans leading-relaxed text-gray-700">{{ reminderMessage }}</pre>
 </div>
 </div>
 </div>

 <DialogFooter>
 <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 w-full">
 <Button type="button" variant="ghost" @click="isReminderModalOpen = false" :disabled="reminderLoading">
 Batal
 </Button>
 <Button @click="confirmSendReminder" :disabled="reminderLoading" class="bg-green-600 hover:bg-green-700">
 <Loader2 v-if="reminderLoading" class="w-4 h-4 mr-2 animate-spin" />
 <svg v-else class="mr-2 h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
 <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.0740-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
 </svg>
 {{ reminderLoading ? 'Mengirim...' : 'Kirim Reminder' }}
 </Button>
 </div>
 </DialogFooter>
 </DialogContent>
 </Dialog>
 <!-- Lump Sum Payment Modal -->
 <Dialog :open="isLumpSumModalOpen" @update:open="isLumpSumModalOpen = $event">
 <DialogContent class="sm:max-w-lg max-h-[90vh] flex flex-col">
 <DialogHeader class="shrink-0">
 <DialogTitle>Bayar Sekaligus — {{ selectedHouse?.name }}</DialogTitle>
 <DialogDescription>{{ selectedHouse?.owner }} · Pilih bulan yang dibayar</DialogDescription>
 </DialogHeader>

 <div v-if="selectedHouse" class="flex-1 overflow-y-auto space-y-4 py-2 min-h-0">
 <!-- Selectable Months -->
 <div class="rounded-lg border overflow-hidden">
 <table class="w-full text-xs">
 <thead class="bg-muted/50 sticky top-0 z-10">
 <tr>
 <th class="px-2 py-1.5 w-8">
 <input type="checkbox" class="rounded"
 :checked="selectedDueIds.length === unpaidMonths.length && unpaidMonths.length > 0"
 @change="toggleAllDues"
 />
 </th>
 <th class="text-left px-2 py-1.5 font-medium">Bulan</th>
 <th class="text-right px-2 py-1.5 font-medium">Tagihan</th>
 <th class="text-right px-2 py-1.5 font-medium">Sisa</th>
 </tr>
 </thead>
 <tbody>
 <tr v-for="m in unpaidMonths" :key="m.due_id"
 class="border-t cursor-pointer hover:bg-muted/30 transition-colors"
 :class="selectedDueIds.includes(m.due_id) ? 'bg-blue-50' : ''"
 @click="toggleDue(m.due_id)"
 >
 <td class="px-2 py-1">
 <input type="checkbox" class="rounded" :checked="selectedDueIds.includes(m.due_id)" @click.stop="toggleDue(m.due_id)" />
 </td>
 <td class="px-2 py-1">{{ m.label }} {{ year }}</td>
 <td class="text-right px-2 py-1">{{ formatCurrency(m.bill_amount) }}</td>
 <td class="text-right px-2 py-1 text-red-500 font-semibold">{{ formatCurrency(m.remaining) }}</td>
 </tr>
 </tbody>
 </table>
 </div>

 <!-- Selected Summary -->
 <div class="flex justify-between items-center text-sm px-1">
 <span class="text-muted-foreground">{{ selectedDueIds.length }} bulan dipilih</span>
 <span class="font-bold text-red-600">Total: {{ formatCurrency(selectedTotal) }}</span>
 </div>

 <!-- Amount + Date in compact row -->
 <div class="grid grid-cols-2 gap-3">
 <div class="space-y-1">
 <Label for="lump_amount" class="text-xs font-semibold">Jumlah Bayar (Rp)</Label>
 <Input
 id="lump_amount"
 :modelValue="lumpSumDisplayAmount"
 @input="handleLumpSumInput"
 type="text"
 class="pl-3 h-9 text-sm font-bold"
 placeholder="0"
 />
 </div>
 <div class="space-y-1">
 <Label for="lump_date" class="text-xs font-semibold">Tanggal Bayar</Label>
 <Input
 id="lump_date"
 v-model="lumpSumForm.payment_date"
 type="date"
 class="h-9 text-sm"
 :max="today"
 />
 </div>
 </div>
 <p class="text-[10px] text-muted-foreground -mt-2">
 Kelebihan dicatat sebagai iuran sukarela. Masukkan 0 untuk menghapus pembayaran manual.
 </p>
 <p v-if="lumpSumForm.errors.amount" class="text-xs text-red-500 font-medium -mt-1">{{ lumpSumForm.errors.amount }}</p>
 </div>

 <DialogFooter class="shrink-0">
 <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 w-full">
 <Button type="button" variant="ghost" @click="isLumpSumModalOpen = false" class="sm:w-auto">
 Batal
 </Button>
 <Button type="submit" @click="submitLumpSum" :disabled="lumpSumForm.processing || selectedDueIds.length === 0" class="sm:w-auto px-8">
 <Loader2 v-if="lumpSumForm.processing" class="w-4 h-4 mr-2 animate-spin" />
 Simpan Pembayaran
 </Button>
 </div>
 </DialogFooter>
 </DialogContent>
 </Dialog>
 </AuthenticatedLayout>
</template>
