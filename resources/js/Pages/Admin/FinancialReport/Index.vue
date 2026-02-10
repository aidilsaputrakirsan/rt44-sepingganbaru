<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/Components/ui/dialog';
import { TrendingUp, TrendingDown, Wallet, Calendar, Calculator, ArrowRight, Settings2 } from 'lucide-vue-next';

const props = defineProps({
    report: Object,
    filters: Object,
});

const isModalOpen = ref(false);
const displayAmount = ref('');

const balanceForm = useForm({
    period: props.report.period,
    amount: props.report.saldo_awal,
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
</script>

<template>
    <Head title="Laporan Keuangan" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Laporan Keuangan RT-44
                </h2>
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2 bg-white dark:bg-slate-900 border rounded-lg px-3 py-1.5 shadow-sm">
                        <Calendar class="w-4 h-4 text-muted-foreground" />
                        <input 
                            type="month" 
                            :value="report.period" 
                            @change="changePeriod"
                            class="border-none bg-transparent p-0 text-sm font-semibold focus:ring-0"
                        />
                    </div>
                    <Button variant="outline" size="sm" @click="openModal">
                        <Settings2 class="w-4 h-4 mr-2" />
                        Set Saldo Awal
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
                            <CardDescription>Saldo Awal (Anchor)</CardDescription>
                            <CardTitle class="text-xl font-bold">{{ formatCurrency(report.saldo_awal) }}</CardTitle>
                        </CardHeader>
                    </Card>
                    <Card class="border-l-4 border-l-green-500">
                        <CardHeader class="pb-2">
                            <CardDescription>Total Pemasukan</CardDescription>
                            <CardTitle class="text-xl font-bold text-green-600">+ {{ formatCurrency(report.total_income) }}</CardTitle>
                        </CardHeader>
                    </Card>
                    <Card class="border-l-4 border-l-red-500">
                        <CardHeader class="pb-2">
                            <CardDescription>Total Pengeluaran</CardDescription>
                            <CardTitle class="text-xl font-bold text-red-600">- {{ formatCurrency(report.total_expenses) }}</CardTitle>
                        </CardHeader>
                    </Card>
                    <Card class="border-l-4 border-l-emerald-600 bg-emerald-50/50 dark:bg-emerald-950/20">
                        <CardHeader class="pb-2">
                            <CardDescription>Saldo Akhir ({{ report.period_label }})</CardDescription>
                            <CardTitle class="text-2xl font-black text-emerald-700 dark:text-emerald-400">
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
                                <div class="flex justify-between items-center p-4 rounded-lg bg-slate-50 dark:bg-slate-900 border border-dashed text-sm">
                                    <div class="flex flex-col">
                                        <span class="font-bold">Iuran Wajib</span>
                                        <span class="text-[10px] text-muted-foreground uppercase tracking-wider">Mandatory Fees</span>
                                    </div>
                                    <span class="text-lg font-bold text-slate-900 dark:text-slate-100">{{ formatCurrency(report.income_wajib) }}</span>
                                </div>
                                <div class="flex justify-between items-center p-4 rounded-lg bg-slate-50 dark:bg-slate-900 border border-dashed text-sm">
                                    <div class="flex flex-col">
                                        <span class="font-bold">Iuran Sukarela</span>
                                        <span class="text-[10px] text-muted-foreground uppercase tracking-wider">Voluntary / Excess</span>
                                    </div>
                                    <span class="text-lg font-bold text-slate-900 dark:text-slate-100">{{ formatCurrency(report.income_sukarela) }}</span>
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
                        <Plus class="w-4 h-4 mx-auto text-slate-500" />
                        <div class="space-y-1">
                            <p class="text-xs text-slate-400 uppercase font-bold tracking-widest">Total Pemasukan</p>
                            <p class="text-xl font-bold">{{ formatCurrency(report.total_income) }}</p>
                        </div>
                        <ArrowRight class="hidden md:block w-4 h-4 text-slate-500" />
                        <div class="space-y-1">
                            <p class="text-xs text-slate-400 uppercase font-bold tracking-widest">Total Pengeluaran</p>
                            <p class="text-xl font-bold text-red-400">{{ formatCurrency(report.total_expenses) }}</p>
                        </div>
                        <div class="md:col-start-1 md:col-end-6 pt-4 mt-4 border-t border-slate-800 flex flex-col md:flex-row justify-between items-center gap-4">
                            <p class="text-sm font-medium italic text-slate-400">
                                * Saldo akhir akan otomatis menjadi saldo awal untuk periode bulan berikutnya (Januari -> Februari).
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
                </DialogHeader>
                
                <form @submit.prevent="submitBalance" class="space-y-4 py-4">
                    <div class="space-y-2">
                        <Label>Periode Laporan</Label>
                        <Input :value="report.period_label" disabled class="bg-muted" />
                    </div>

                    <div class="space-y-2">
                        <Label>Saldo Awal (Rp)</Label>
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
                            * Saldo awal digunakan sebagai basis perhitungan kas untuk bulan ini.
                        </p>
                    </div>

                    <DialogFooter class="pt-4">
                        <Button type="button" variant="ghost" @click="isModalOpen = false">Batal</Button>
                        <Button type="submit" :disabled="balanceForm.processing">Simpan Saldo Awal</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AuthenticatedLayout>
</template>
