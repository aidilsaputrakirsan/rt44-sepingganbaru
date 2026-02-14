<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/Components/ui/dialog';
import { Plus, Trash2, Receipt } from 'lucide-vue-next';

defineProps({
 expenses: Array,
});

const isModalOpen = ref(false);
const displayAmount = ref('');

const form = useForm({
 title: '',
 amount: '',
 date: new Date().toISOString().substr(0, 10),
 category: 'operasional',
});

const formatNumber = (num) => {
 if (!num && num !== 0) return '';
 return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
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

const submitExpense = () => {
 form.post(route('admin.expenses.store'), {
 onSuccess: () => {
 isModalOpen.value = false;
 form.reset();
 displayAmount.value = '';
 },
 });
};

const deleteExpense = (id) => {
 if (confirm('Apakah Anda yakin ingin menghapus catatan pengeluaran ini?')) {
 form.delete(route('admin.expenses.destroy', id));
 }
};

const formatCurrency = (amount) => {
 return new Intl.NumberFormat('id-ID', {
 style: 'currency',
 currency: 'IDR',
 minimumFractionDigits: 0,
 }).format(amount);
};
</script>

<template>
 <Head title="Pengeluaran" />

 <AuthenticatedLayout>
 <template #header>
 <div class="flex justify-between items-center">
 <h2 class="text-xl font-semibold leading-tight text-gray-800">
 Catatan Pengeluaran Kas RT
 </h2>
 <Button @click="isModalOpen = true">
 <Plus class="w-4 h-4 mr-2" />
 Tambah Pengeluaran
 </Button>
 </div>
 </template>

 <div class="py-12">
 <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
 <Card>
 <CardHeader>
 <CardTitle class="flex items-center gap-2">
 <Receipt class="w-5 h-5 text-primary" />
 Riwayat Pengeluaran
 </CardTitle>
 </CardHeader>
 <CardContent>
 <Table>
 <TableHeader>
 <TableRow>
 <TableHead>Tanggal</TableHead>
 <TableHead>Keterangan / Kegiatan</TableHead>
 <TableHead>Kategori</TableHead>
 <TableHead class="text-right">Nominal</TableHead>
 <TableHead class="text-center w-24">Action</TableHead>
 </TableRow>
 </TableHeader>
 <TableBody>
 <TableRow v-for="expense in expenses" :key="expense.id">
 <TableCell>{{ new Date(expense.date).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' }) }}</TableCell>
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
 <Button variant="ghost" size="sm" @click="deleteExpense(expense.id)" class="text-destructive hover:text-destructive">
 <Trash2 class="w-4 h-4" />
 </Button>
 </TableCell>
 </TableRow>
 <TableRow v-if="expenses.length === 0">
 <TableCell colspan="5" class="text-center py-10 text-muted-foreground">
 Belum ada catatan pengeluaran.
 </TableCell>
 </TableRow>
 </TableBody>
 </Table>
 </CardContent>
 </Card>
 </div>
 </div>

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
 </AuthenticatedLayout>
</template>
