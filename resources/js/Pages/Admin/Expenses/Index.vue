<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import DemoToast from '@/Components/DemoToast.vue';
import { useDemoGuard } from '@/composables/useDemoGuard';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogDescription } from '@/Components/ui/dialog';
import { Plus, Trash2, Receipt, Edit2, AlertCircle, Copy, CalendarDays } from 'lucide-vue-next';

defineProps({
 expenses: Array,
 groupedExpenses: Object,
});

const { isDemo, demoGuard } = useDemoGuard();

const isModalOpen = ref(false);
const isEditModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const expenseToEdit = ref(null);
const expenseToDelete = ref(null);

const displayAmount = ref('');
const editDisplayAmount = ref('');

const form = useForm({
 title: '',
 amount: '',
 date: new Date().toISOString().substr(0, 10),
 category: 'operasional',
});

const editForm = useForm({
 title: '',
 amount: '',
 date: '',
 category: 'operasional',
});

const isCloneModalOpen = ref(false);
const cloneForm = useForm({
 source_month: '',
 target_month: '',
});

const formatNumber = (num) => {
 if (!num && num !== 0) return '';
 // Remove any trailing decimals if it somehow comes from DB as X.00
 const integerPart = Math.floor(Number(num));
 return integerPart.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
};

const handleAmountInput = (e) => {
 let value = e.target.value.replace(/\D/g, '');
 if (value === '') {
 form.amount = '';
 displayAmount.value = '';
 return;
 }
 const numericValue = parseInt(value, 10);
 form.amount = numericValue;
 displayAmount.value = formatNumber(numericValue);
};

const handleEditAmountInput = (e) => {
 let value = e.target.value.replace(/\D/g, '');
 if (value === '') {
 editForm.amount = '';
 editDisplayAmount.value = '';
 return;
 }
 const numericValue = parseInt(value, 10);
 editForm.amount = numericValue;
 editDisplayAmount.value = formatNumber(numericValue);
};

const submitExpense = () => {
 form.post(route('admin.expenses.store'), {
 onSuccess: () => {
 isModalOpen.value = false;
 form.reset();
 displayAmount.value = '';
 },
 });
};

const openEditModal = (expense) => {
 expenseToEdit.value = expense;
 editForm.title = expense.title;
 editForm.amount = Math.floor(Number(expense.amount));
 // Ensure the date is in YYYY-MM-DD format for the input[type=date]
 editForm.date = expense.date ? expense.date.substr(0, 10) : '';
 editForm.category = expense.category;
 editDisplayAmount.value = formatNumber(expense.amount);
 isEditModalOpen.value = true;
};

const submitUpdateExpense = () => {
 editForm.put(route('admin.expenses.update', expenseToEdit.value.id), {
 onSuccess: () => {
 isEditModalOpen.value = false;
 expenseToEdit.value = null;
 },
 });
};

const openDeleteModal = (expense) => {
 expenseToDelete.value = expense;
 isDeleteModalOpen.value = true;
};

const confirmDelete = () => {
 if (!expenseToDelete.value) return;
 form.delete(route('admin.expenses.destroy', expenseToDelete.value.id), {
 onSuccess: () => {
 isDeleteModalOpen.value = false;
 expenseToDelete.value = null;
 }
 });
};

const formatCurrency = (amount) => {
 return new Intl.NumberFormat('id-ID', {
 style: 'currency',
 currency: 'IDR',
 minimumFractionDigits: 0,
 }).format(amount);
};

const formatMonthYearLabel = (yearMonthStr) => {
 // yearMonthStr is '2026-02'
 const [year, month] = yearMonthStr.split('-');
 const date = new Date(parseInt(year), parseInt(month) - 1, 1);
 return date.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' });
};

const calculateTotal = (monthlyExpenses) => {
 return monthlyExpenses.reduce((sum, item) => sum + Number(item.amount), 0);
};

const submitCloneExpense = () => {
 cloneForm.post(route('admin.expenses.clone'), {
 onSuccess: () => {
 isCloneModalOpen.value = false;
 cloneForm.reset();
 }
 });
};
</script>

<template>
 <Head title="Pengeluaran" />

 <AuthenticatedLayout>
 <template #header>
 <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
 <div>
 <h2 class="text-2xl font-bold text-slate-900 flex items-center gap-2">
 <Receipt class="w-6 h-6 text-indigo-600" />
 Pengeluaran
 </h2>
 <p class="text-slate-500 mt-1 uppercase text-sm tracking-wider font-medium">
 Catatan Pengeluaran Kas RT
 </p>
 </div>
  <div class="flex gap-2" :class="isDemo ? 'opacity-50' : ''">
  <Button variant="outline" @click="demoGuard() && (isCloneModalOpen = true)" class="flex items-center gap-2" :class="isDemo ? 'cursor-not-allowed' : ''">
  <Copy class="w-4 h-4" />
  Salin Data Bulanan
  </Button>
  <Button @click="demoGuard() && (isModalOpen = true)" class="bg-indigo-600 hover:bg-indigo-700 shadow-md flex items-center gap-2" :class="isDemo ? 'cursor-not-allowed' : ''">
  <Plus class="w-4 h-4" />
  Tambah Pengeluaran
  </Button>
  </div>
 </div>
 </template>

 <div class="py-12">
 <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-8">
 
 <div v-for="(monthlyExpenses, monthYear) in groupedExpenses" :key="monthYear">
 <div class="mb-4">
 <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
 <CalendarDays class="w-5 h-5 text-indigo-500" />
 {{ formatMonthYearLabel(monthYear) }}
 </h3>
 </div>
 <Card>
 <CardContent class="p-0">
 <Table>
 <TableHeader>
 <TableRow>
 <TableHead class="w-32">Tanggal</TableHead>
 <TableHead>Keterangan / Kegiatan</TableHead>
 <TableHead>Kategori</TableHead>
 <TableHead class="text-right">Nominal</TableHead>
  <TableHead class="text-center w-24">Action</TableHead>
 </TableRow>
 </TableHeader>
 <TableBody>
 <TableRow v-for="expense in monthlyExpenses" :key="expense.id">
 <TableCell>{{ new Date(expense.date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) }}</TableCell>
 <TableCell class="font-medium text-slate-900">
 {{ expense.title }}
 </TableCell>
 <TableCell>
 <span class="px-2 py-1 text-[10px] font-bold uppercase tracking-wider bg-slate-100 rounded">
 {{ expense.category }}
 </span>
 </TableCell>
 <TableCell class="text-right font-bold text-red-600">
 {{ formatCurrency(expense.amount) }}
 </TableCell>
  <TableCell class="text-center">
  <div class="flex items-center justify-center gap-1" :class="isDemo ? 'opacity-40' : ''">
  <Button variant="ghost" size="icon" @click="demoGuard() && openEditModal(expense)" class="h-8 w-8 text-slate-500 hover:text-indigo-600" :class="isDemo ? 'cursor-not-allowed' : ''">
  <Edit2 class="w-4 h-4" />
  </Button>
  <Button variant="ghost" size="icon" @click="demoGuard() && openDeleteModal(expense)" class="h-8 w-8 text-slate-500 hover:text-destructive" :class="isDemo ? 'cursor-not-allowed' : ''">
  <Trash2 class="w-4 h-4" />
  </Button>
  </div>
  </TableCell>
 </TableRow>
 </TableBody>
 </Table>
 </CardContent>
 <div class="bg-slate-50 border-t border-slate-100 p-4 text-right">
 <p class="text-sm text-slate-500 font-medium">
 Total: <span class="text-red-600 font-bold ml-2">{{ formatCurrency(calculateTotal(monthlyExpenses)) }}</span>
 </p>
 </div>
 </Card>
 </div>

 <div v-if="Object.keys(groupedExpenses || {}).length === 0" class="text-center py-20 bg-white rounded-xl shadow-sm border border-slate-200">
 <Receipt class="w-12 h-12 text-slate-300 mx-auto mb-3" />
 <p class="text-slate-500 font-medium">Belum ada catatan pengeluaran apapun.</p>
 </div>
 </div>
 </div>

 <!-- Bulk Clone Modal -->
 <Dialog :open="isCloneModalOpen" @update:open="isCloneModalOpen = $event">
 <DialogContent class="sm:max-w-md">
 <DialogHeader>
 <DialogTitle class="flex items-center gap-2 text-indigo-700">
 <Copy class="w-5 h-5" />
 Salin Pengeluaran Bulanan
 </DialogTitle>
 <DialogDescription class="pt-2 text-slate-600">
 Salin seluruh data pengeluaran (nama pembayaran, nominal & kategori) dari bulan sebelumnya ke bulan yang baru. Anda bisa <strong class="text-indigo-600">mengedit atau menghapus</strong> item yang tidak diperlukan setelah disalin.
 </DialogDescription>
 </DialogHeader>
 
 <form @submit.prevent="submitCloneExpense" class="space-y-4 py-4">
 <div class="space-y-2">
 <Label>Sumber Data (Salin Dari)</Label>
 <Input v-model="cloneForm.source_month" type="month" required />
 <p class="text-xs text-slate-500">Pilih bulan yang datanya ingin disalin.</p>
 </div>
 <div class="space-y-2 mt-4">
 <Label>Target (Tempel Ke)</Label>
 <Input v-model="cloneForm.target_month" type="month" required />
 <p class="text-xs text-slate-500">Pilih bulan tujuan. Tanggal pengeluaran akan otomatis disesuaikan.</p>
 </div>

 <DialogFooter class="pt-4 gap-2 sm:gap-0">
 <Button type="button" variant="outline" @click="isCloneModalOpen = false">Batal</Button>
 <Button type="submit" :disabled="cloneForm.processing" class="bg-indigo-600 hover:bg-indigo-700 shadow-md flex items-center gap-2 text-white">
 <Copy class="w-4 h-4" />
 Salin Sekarang
 </Button>
 </DialogFooter>
 </form>
 </DialogContent>
 </Dialog>

 <!-- Add Expense Modal -->
 <Dialog :open="isModalOpen" @update:open="isModalOpen = $event">
 <DialogContent class="sm:max-w-md">
 <DialogHeader>
 <DialogTitle>Tambah Catatan Pengeluaran</DialogTitle>
 </DialogHeader>
 
 <form @submit.prevent="submitExpense" class="space-y-4 py-4">
 <div class="space-y-2">
 <Label>Judul / Kegiatan</Label>
 <Input v-model="form.title" placeholder="Contoh: Gaji Security, Biaya Sampah, dll" required />
 </div>

 <div class="grid grid-cols-2 gap-4">
 <div class="space-y-2">
 <Label>Tanggal</Label>
 <Input v-model="form.date" type="date" required />
 </div>
 <div class="space-y-2">
 <Label>Kategori</Label>
 <select v-model="form.category" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
 <option value="operasional">Operasional</option>
 <option value="kegiatan">Kegiatan</option>
 <option value="perbaikan">Perbaikan</option>
 <option value="lainnya">Lainnya</option>
 </select>
 </div>
 </div>

 <div class="space-y-2">
 <Label>Nominal (Rp)</Label>
 <div class="relative">
 <span class="absolute left-3 top-1/2 -translate-y-1/2 font-bold text-slate-400">Rp</span>
 <Input
 :modelValue="displayAmount"
 @input="handleAmountInput"
 type="text"
 class="pl-10 h-11 text-lg font-bold"
 required
 />
 </div>
 </div>

 <DialogFooter class="pt-4">
 <Button type="button" variant="ghost" @click="isModalOpen = false">Batal</Button>
 <Button type="submit" :disabled="form.processing">Simpan Catatan</Button>
 </DialogFooter>
 </form>
 </DialogContent>
 </Dialog>

 <!-- Edit Expense Modal -->
 <Dialog :open="isEditModalOpen" @update:open="isEditModalOpen = $event">
 <DialogContent class="sm:max-w-md">
 <DialogHeader>
 <DialogTitle>Ubah Catatan Pengeluaran</DialogTitle>
 <DialogDescription>
 Perbarui detail catatan pengeluaran ini.
 </DialogDescription>
 </DialogHeader>
 
 <form @submit.prevent="submitUpdateExpense" class="space-y-4 py-4">
 <div class="space-y-2">
 <Label>Judul / Kegiatan</Label>
 <Input v-model="editForm.title" placeholder="Contoh: Gaji Security, Biaya Sampah, dll" required />
 </div>

 <div class="grid grid-cols-2 gap-4">
 <div class="space-y-2">
 <Label>Tanggal</Label>
 <Input v-model="editForm.date" type="date" required />
 </div>
 <div class="space-y-2">
 <Label>Kategori</Label>
 <select v-model="editForm.category" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
 <option value="operasional">Operasional</option>
 <option value="kegiatan">Kegiatan</option>
 <option value="perbaikan">Perbaikan</option>
 <option value="lainnya">Lainnya</option>
 </select>
 </div>
 </div>

 <div class="space-y-2">
 <Label>Nominal (Rp)</Label>
 <div class="relative">
 <span class="absolute left-3 top-1/2 -translate-y-1/2 font-bold text-slate-400">Rp</span>
 <Input
 :modelValue="editDisplayAmount"
 @input="handleEditAmountInput"
 type="text"
 class="pl-10 h-11 text-lg font-bold"
 required
 />
 </div>
 </div>

 <DialogFooter class="pt-4">
 <Button type="button" variant="ghost" @click="isEditModalOpen = false">Batal</Button>
 <Button type="submit" :disabled="editForm.processing" class="bg-indigo-600 hover:bg-indigo-700">Update Catatan</Button>
 </DialogFooter>
 </form>
 </DialogContent>
 </Dialog>

 <!-- Delete Confirmation Modal -->
 <Dialog :open="isDeleteModalOpen" @update:open="isDeleteModalOpen = $event">
 <DialogContent class="sm:max-w-[400px]">
 <DialogHeader>
 <DialogTitle class="flex items-center gap-2 text-red-600">
 <AlertCircle class="w-5 h-5" />
 Konfirmasi Hapus
 </DialogTitle>
 <DialogDescription class="pt-2 text-slate-600">
 Apakah Anda yakin ingin menghapus catatan pengeluaran <strong>{{ expenseToDelete?.title }}</strong> sebesar <strong>{{ formatCurrency(expenseToDelete?.amount || 0) }}</strong>?
 <p class="mt-2 text-xs text-red-500 font-medium">Tindakan ini tidak dapat dibatalkan.</p>
 </DialogDescription>
 </DialogHeader>
 <DialogFooter class="gap-2 sm:gap-0">
 <Button variant="outline" @click="isDeleteModalOpen = false" class="border-slate-200">Batal</Button>
 <Button @click="confirmDelete" :disabled="form.processing" class="bg-red-600 hover:bg-red-700 text-white shadow-md">
 Ya, Hapus Data
 </Button>
 </DialogFooter>
 </DialogContent>
  </Dialog>
  <DemoToast />
  </AuthenticatedLayout>
</template>
