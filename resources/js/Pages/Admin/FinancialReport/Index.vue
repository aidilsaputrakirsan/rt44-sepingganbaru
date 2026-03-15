<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import DemoToast from '@/Components/DemoToast.vue';
import { useDemoGuard } from '@/composables/useDemoGuard';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/Components/ui/dialog';
import { TrendingUp, TrendingDown, Wallet, Calendar, Calculator, ArrowRight, Settings2, FileDown, Link, Pencil, RotateCcw, PieChart } from 'lucide-vue-next';

const props = defineProps({
 report: Object,
 filters: Object,
});

const { isDemo, demoGuard } = useDemoGuard();

const isModalOpen = ref(false);
const isBreakdownOpen = ref(false);
const displayAmount = ref('');

const balanceForm = useForm({
 period: props.report.period,
 amount: props.report.saldo_awal,
});

const deleteForm = useForm({
 period: props.report.period,
});

const formatNumber = (num) => {
 if (!num && num !== 0) return '';
 return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
};

const handleAmountInput = (e) => {
 let value = e.target.value.replace(/\D/g, '');
 if (value === '') {
 balanceForm.amount = 0;
 displayAmount.value = '';
 return;
 }
 const numericValue = parseInt(value, 10);
 balanceForm.amount = numericValue;
 displayAmount.value = formatNumber(numericValue);
};

const openModal = () => {
 balanceForm.period = props.report.period;
 balanceForm.amount = props.report.saldo_awal;
 displayAmount.value = formatNumber(props.report.saldo_awal);
 isModalOpen.value = true;
};

const submitBalance = () => {
 balanceForm.post(route('admin.report.initial-balance'), {
 onSuccess: () => {
 isModalOpen.value = false;
 },
 });
};

const resetToAuto = () => {
 if (!confirm('Yakin ingin mengembalikan saldo awal ke mode otomatis?\nSaldo awal akan dihitung dari saldo akhir bulan sebelumnya.')) return;
 deleteForm.period = props.report.period;
 deleteForm.delete(route('admin.report.delete-initial-balance'), {
 onSuccess: () => {
 isModalOpen.value = false;
 },
 });
};

const formatCurrency = (amount) => {
 return new Intl.NumberFormat('id-ID', {
 style: 'currency',
 currency: 'IDR',
 minimumFractionDigits: 0,
 }).format(amount);
};

const changePeriod = (e) => {
 const [year, month] = e.target.value.split('-');
 router.get(route('admin.report.index'), { month: parseInt(month), year: parseInt(year) }, { preserveState: true });
};

const exportPdf = () => {
 const period = props.report.period.split('-');
 window.open(route('admin.report.export-pdf', {
 month: parseInt(period[1]),
 year: parseInt(period[0])
 }), '_blank');
};
</script>

<template>
 <Head title="Laporan Keuangan" />

 <AuthenticatedLayout>
 <template #header>
 <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
 <div>
 <h2 class="text-2xl font-bold text-slate-900 flex items-center gap-2">
 <PieChart class="w-6 h-6 text-indigo-600" />
 Laporan Keuangan
 </h2>
 <p class="text-slate-500 mt-1 uppercase text-sm tracking-wider font-medium">
 Laporan Keuangan RT-44
 </p>
 </div>
 <div class="flex items-center gap-3 flex-wrap">
 <div class="flex items-center gap-2 bg-white border rounded-lg px-3 py-1.5 shadow-sm">
 <Calendar class="w-4 h-4 text-muted-foreground" />
 <input
 type="month"
 :value="report.period"
 @change="changePeriod"
 class="border-none bg-transparent p-0 text-sm font-semibold focus:ring-0"
 />
 </div>
 <Button variant="outline" size="sm" @click="exportPdf" class="bg-white hover:bg-slate-50 border-indigo-200 text-indigo-700">
 <FileDown class="w-4 h-4 mr-2" />
 Ekspor PDF
 </Button>
 <Button variant="outline" size="sm" @click="demoGuard() && openModal()" :class="isDemo ? 'opacity-50 cursor-not-allowed' : ''">
 <Settings2 class="w-4 h-4 mr-2" />
 Saldo Awal
 </Button>
 </div>
 </div>
 </template>

 <div class="py-12">
 <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
 <!-- Summary Stats -->
 <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
 <Card class="border-l-4 border-l-blue-500">
 <CardHeader class="pb-2">
 <CardDescription class="flex items-center gap-1.5">
 Saldo Awal
 <span v-if="report.is_manual_saldo" class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-700 uppercase">
 <Pencil class="w-2.5 h-2.5" /> Manual
 </span>
 <span v-else class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-700 uppercase">
 <Link class="w-2.5 h-2.5" /> Otomatis
 </span>
 </CardDescription>
 <CardTitle class="text-xl font-bold">{{ formatCurrency(report.saldo_awal) }}</CardTitle>
 </CardHeader>
 </Card>
 <Card
  class="border-l-4 border-l-green-500 cursor-pointer hover:shadow-md hover:border-l-green-600 transition-all"
  @click="isBreakdownOpen = true"
  title="Klik untuk lihat rincian per rumah"
 >
 <CardHeader class="pb-2">
  <CardDescription class="flex items-center gap-1">
   Total Pemasukan
   <span class="text-[10px] text-green-500 font-medium">(klik untuk rincian)</span>
  </CardDescription>
  <CardTitle class="text-xl font-bold text-green-600">+ {{ formatCurrency(report.total_income) }}</CardTitle>
 </CardHeader>
 </Card>
 <Card class="border-l-4 border-l-red-500">
 <CardHeader class="pb-2">
 <CardDescription>Total Pengeluaran</CardDescription>
 <CardTitle class="text-xl font-bold text-red-600">- {{ formatCurrency(report.total_expenses) }}</CardTitle>
 </CardHeader>
 </Card>
 <Card class="border-l-4 border-l-emerald-600 bg-emerald-50/50">
 <CardHeader class="pb-2">
 <CardDescription>Saldo Akhir ({{ report.period_label }})</CardDescription>
 <CardTitle class="text-2xl font-black text-emerald-700">
 {{ formatCurrency(report.saldo_akhir) }}
 </CardTitle>
 </CardHeader>
 </Card>
 </div>

 <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
 <!-- Income Details -->
 <Card>
 <CardHeader>
 <CardTitle class="flex items-center gap-2">
 <TrendingUp class="w-5 h-5 text-green-500" />
 Rincian Pemasukan
 </CardTitle>
 </CardHeader>
 <CardContent>
 <div class="space-y-4">
 <div class="flex justify-between items-center p-4 rounded-lg bg-slate-50 border border-dashed text-sm">
 <div class="flex flex-col">
 <span class="font-bold">Iuran Wajib</span>
 </div>
 <span class="text-lg font-bold text-slate-900">{{ formatCurrency(report.income_wajib) }}</span>
 </div>
 <div class="flex justify-between items-center p-4 rounded-lg bg-slate-50 border border-dashed text-sm">
 <div class="flex flex-col">
 <span class="font-bold">Iuran Sukarela</span>
 </div>
 <span class="text-lg font-bold text-slate-900">{{ formatCurrency(report.income_sukarela) }}</span>
 </div>
 <hr class="border-dashed" />
 <div class="flex justify-between items-center px-4">
 <span class="font-black text-lg">Total Pemasukan</span>
 <span class="text-xl font-black text-green-600">{{ formatCurrency(report.total_income) }}</span>
 </div>
 </div>
 </CardContent>
 </Card>

 <!-- Expense Details -->
 <Card>
 <CardHeader>
 <CardTitle class="flex items-center gap-2">
 <TrendingDown class="w-5 h-5 text-red-500" />
 Rincian Pengeluaran
 </CardTitle>
 </CardHeader>
 <CardContent>
 <Table>
 <TableHeader>
 <TableRow>
 <TableHead>Kegiatan</TableHead>
 <TableHead class="text-right">Nominal</TableHead>
 </TableRow>
 </TableHeader>
 <TableBody>
 <TableRow v-for="expense in report.expenses" :key="expense.id">
 <TableCell class="text-sm">{{ expense.title }}</TableCell>
 <TableCell class="text-right text-sm font-semibold text-red-600">{{ formatCurrency(expense.amount) }}</TableCell>
 </TableRow>
 <TableRow v-if="report.expenses.length === 0">
 <TableCell colspan="2" class="text-center py-6 text-muted-foreground italic">
 Tidak ada catatan pengeluaran di periode ini.
 </TableCell>
 </TableRow>
 </TableBody>
 </Table>
 <div class="mt-4 pt-4 border-t border-dashed flex justify-between items-center px-4">
 <span class="font-black text-lg">Total Pengeluaran</span>
 <span class="text-xl font-black text-red-600">{{ formatCurrency(report.total_expenses) }}</span>
 </div>
 </CardContent>
 </Card>
 </div>

 <!-- Final Calculation Summary -->
 <Card class="bg-slate-900 text-white overflow-hidden relative">
 <div class="absolute right-0 top-0 p-8 opacity-10">
 <Calculator class="w-32 h-32" />
 </div>
 <CardHeader>
 <CardTitle>Ringkasan Akhir Periode</CardTitle>
 <CardDescription class="text-slate-400">Kalkulasi otomatis berdasarkan data masuk dan keluar</CardDescription>
 </CardHeader>
 <CardContent class="grid grid-cols-1 md:grid-cols-5 gap-6 items-center text-center md:text-left">
 <div class="space-y-1">
 <p class="text-xs text-slate-400 uppercase font-bold tracking-widest">Saldo Awal</p>
 <p class="text-xl font-bold">{{ formatCurrency(report.saldo_awal) }}</p>
 </div>
 <p class="text-slate-500 text-center font-bold text-lg">+</p>
 <div class="space-y-1">
 <p class="text-xs text-slate-400 uppercase font-bold tracking-widest">Total Pemasukan</p>
 <p class="text-xl font-bold">{{ formatCurrency(report.total_income) }}</p>
 </div>
 <p class="text-slate-500 text-center font-bold text-lg">&minus;</p>
 <div class="space-y-1">
 <p class="text-xs text-slate-400 uppercase font-bold tracking-widest">Total Pengeluaran</p>
 <p class="text-xl font-bold text-red-400">{{ formatCurrency(report.total_expenses) }}</p>
 </div>
 <div class="md:col-start-1 md:col-end-6 pt-4 mt-4 border-t border-slate-800 flex flex-col md:flex-row justify-between items-center gap-4">
 <p class="text-sm font-medium italic text-slate-400">
 * Saldo akhir otomatis menjadi saldo awal bulan berikutnya, kecuali di-override manual.
 </p>
 <div class="text-right">
 <p class="text-xs text-slate-400 uppercase font-bold tracking-widest">Sisa Saldo Kas RT</p>
 <p class="text-3xl font-black text-emerald-400">{{ formatCurrency(report.saldo_akhir) }}</p>
 </div>
 </div>
 </CardContent>
 </Card>
 </div>
 </div>

 <!-- Set Saldo Awal Modal -->
 <Dialog :open="isModalOpen" @update:open="isModalOpen = $event">
 <DialogContent class="sm:max-w-md">
 <DialogHeader>
 <DialogTitle>Atur Saldo Awal Periode</DialogTitle>
 <DialogDescription>{{ report.period_label }}</DialogDescription>
 </DialogHeader>

 <div class="space-y-4 py-2">
 <!-- Current Status Info -->
 <div class="rounded-lg border p-3 space-y-1">
 <div class="flex items-center justify-between">
 <span class="text-sm text-muted-foreground">Status saat ini</span>
 <span v-if="report.is_manual_saldo" class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-bold bg-amber-100 text-amber-700">
 <Pencil class="w-3 h-3" /> Manual
 </span>
 <span v-else class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-bold bg-blue-100 text-blue-700">
 <Link class="w-3 h-3" /> Otomatis
 </span>
 </div>
 <p class="text-xs text-muted-foreground">
 <template v-if="report.is_manual_saldo">
 Saldo awal diisi manual oleh admin.
 </template>
 <template v-else>
 Saldo awal dihitung otomatis dari saldo akhir bulan sebelumnya.
 </template>
 </p>
 </div>

 <!-- Override Form -->
 <form @submit.prevent="submitBalance" class="space-y-3">
 <div class="space-y-2">
 <Label class="text-sm font-semibold">Override Saldo Awal (Rp)</Label>
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
 <p class="text-[11px] text-muted-foreground italic">
 Simpan untuk menggunakan nilai manual pada bulan ini.
 </p>
 </div>

 <div class="flex items-center gap-2">
 <Button type="submit" :disabled="balanceForm.processing" class="flex-1">
 Simpan Manual
 </Button>
 <Button
 v-if="report.is_manual_saldo"
 type="button"
 variant="outline"
 @click="resetToAuto"
 :disabled="deleteForm.processing"
 class="flex-1"
 >
 <RotateCcw class="w-4 h-4 mr-1.5" />
 Kembalikan Otomatis
 </Button>
 </div>
 </form>
 </div>
 </DialogContent>
  </Dialog>
  <!-- Modal Rincian Pemasukan Per Rumah -->
  <Dialog v-model:open="isBreakdownOpen">
   <DialogContent class="sm:max-w-[680px] max-h-[85vh] flex flex-col">
    <DialogHeader>
     <DialogTitle class="flex items-center gap-2 text-green-700">
      <TrendingUp class="w-5 h-5" />
      Rincian Pemasukan — {{ report.period_label }}
     </DialogTitle>
     <DialogDescription>
      Daftar pembayaran terverifikasi berdasarkan tanggal pembayaran diterima.
      Total: <strong class="text-green-700">{{ formatCurrency(report.total_income) }}</strong>
      dari <strong>{{ report.income_breakdown.length }}</strong> transaksi.
     </DialogDescription>
    </DialogHeader>

    <div class="overflow-auto flex-1 -mx-1 px-1">
     <div v-if="report.income_breakdown.length === 0" class="text-center py-10 text-slate-400 text-sm">
      Tidak ada pemasukan di bulan ini.
     </div>
     <table v-else class="w-full text-sm border-collapse">
      <thead class="sticky top-0 bg-white z-10">
       <tr class="border-b-2 border-slate-200 text-slate-600 text-xs uppercase tracking-wide">
        <th class="p-2 text-left">Rumah</th>
        <th class="p-2 text-left">Tagihan Bulan</th>
        <th class="p-2 text-left">Tgl Bayar</th>
        <th class="p-2 text-right">Wajib</th>
        <th class="p-2 text-right">Sukarela</th>
        <th class="p-2 text-right font-bold">Total</th>
       </tr>
      </thead>
      <tbody>
       <tr
        v-for="(item, i) in report.income_breakdown"
        :key="i"
        class="border-b border-slate-100 hover:bg-green-50/40"
       >
        <td class="p-2 font-bold text-indigo-600">{{ item.rumah }}</td>
        <td class="p-2 text-slate-500">{{ item.period }}</td>
        <td class="p-2 text-slate-500">{{ item.payment_date }}</td>
        <td class="p-2 text-right">{{ formatCurrency(item.amount_wajib) }}</td>
        <td class="p-2 text-right text-slate-400">
         <span v-if="item.amount_sukarela > 0">{{ formatCurrency(item.amount_sukarela) }}</span>
         <span v-else class="text-slate-300">—</span>
        </td>
        <td class="p-2 text-right font-semibold text-green-700">{{ formatCurrency(item.total) }}</td>
       </tr>
      </tbody>
      <tfoot>
       <tr class="border-t-2 border-slate-300 bg-slate-50 font-bold">
        <td class="p-2" colspan="3">Total</td>
        <td class="p-2 text-right">{{ formatCurrency(report.income_wajib) }}</td>
        <td class="p-2 text-right">{{ formatCurrency(report.income_sukarela) }}</td>
        <td class="p-2 text-right text-green-700">{{ formatCurrency(report.total_income) }}</td>
       </tr>
      </tfoot>
     </table>

     <div v-if="report.income_breakdown.some(i => i.notes)" class="mt-4 space-y-1">
      <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Keterangan</p>
      <div
       v-for="(item, i) in report.income_breakdown.filter(i => i.notes)"
       :key="'note-' + i"
       class="text-xs text-slate-500 italic"
      >
       <span class="font-semibold not-italic text-slate-700">{{ item.rumah }}</span> — {{ item.notes }}
      </div>
     </div>
    </div>

    <DialogFooter class="pt-3 border-t">
     <Button variant="outline" @click="isBreakdownOpen = false">Tutup</Button>
    </DialogFooter>
   </DialogContent>
  </Dialog>

  <DemoToast />
  </AuthenticatedLayout>
</template>
