<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Table, TableBody, TableCaption, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { ref } from 'vue';

const props = defineProps({
    calendar: Array,
    year: Number,
});

const months = [
    'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
    'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
];

const selectedDue = ref(null);
const isModalOpen = ref(false);
const form = useForm({
    amount: '',
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
        remaining: dueData.bill_amount - dueData.paid_amount,
        is_edit: dueData.paid_amount > 0
    };
    
    // If already paid, show the current total amount for editing (instead of 0 or remaining)
    // This allows "overwriting" the value as requested
    const initialAmount = dueData.paid_amount > 0 ? dueData.paid_amount : (selectedDue.value.remaining > 0 ? selectedDue.value.remaining : 0);
    
    form.amount = initialAmount;
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
    if (!selectedDue.value || !form.amount) return;

    form.post(route('admin.payment.store', selectedDue.value.id), {
        onSuccess: () => {
            isModalOpen.value = false;
            form.reset();
            displayAmount.value = '';
        },
    });
};

const getStatusColor = (data) => {
    if (data.status === 'none') return 'bg-gray-100 dark:bg-gray-800 text-gray-300';
    
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
</script>

<template>
    <Head title="Kalender Iuran" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Kalender Iuran Tahunan ({{ year }})
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-[95%] sm:px-6 lg:px-8">
                <Card>
                    <CardHeader>
                        <CardTitle>Matrix Pembayaran</CardTitle>
                        <p class="text-sm text-muted-foreground">Klik pada kolom bulan untuk mencatat pembayaran manual.</p>
                    </CardHeader>
                    <CardContent class="overflow-x-auto">
                        <Table class="w-full text-center text-xs md:text-sm">
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="text-left sticky left-0 bg-white dark:bg-slate-950 z-10 w-32 border-r shadow-sm">Rumah</TableHead>
                                    <TableHead v-for="month in months" :key="month" class="text-center min-w-[100px]">{{ month }}</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="row in calendar" :key="row.id">
                                    <TableCell class="text-left font-bold sticky left-0 bg-white dark:bg-slate-950 z-10 border-r shadow-sm">
                                        {{ row.name }}
                                        <div class="text-[10px] text-muted-foreground font-normal truncate max-w-[120px]">{{ row.owner }}</div>
                                    </TableCell>
                                    <TableCell v-for="(data, m) in row.months" :key="m" class="p-1">
                                        <div 
                                            class="h-10 mx-auto flex flex-col items-center justify-center rounded-md cursor-pointer transition-all hover:brightness-95 text-[10px] sm:text-xs px-1"
                                            :class="getStatusColor(data)"
                                            @click="openPaymentModal(data, row.name, months[m-1])"
                                            :title="data.amount > 0 ? `Tagihan: ${formatCurrency(data.bill_amount)}` : 'No Bill'"
                                        >
                                            <template v-if="data.status !== 'none'">
                                                <span v-if="data.paid_amount > 0" class="font-bold">{{ formatCurrency(data.paid_amount) }}</span>
                                                <span v-else class="text-muted-foreground">-</span>
                                            </template>
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

        <!-- Manual Payment Modal -->
        <Dialog :open="isModalOpen" @update:open="isModalOpen = $event">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{{ selectedDue?.is_edit ? 'Ubah Pembayaran' : 'Input Pembayaran Manual' }}</DialogTitle>
                </DialogHeader>
                
                <div v-if="selectedDue" class="space-y-4 py-4">
                    <!-- Info Summary Section -->
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

                    <!-- Input Section -->
                    <div class="space-y-2">
                        <Label for="amount" class="text-sm font-semibold">
                            Jumlah Bayar (Rp)
                        </Label>
                        <div class="relative">
                            <Input
                                id="amount"
                                :modelValue="displayAmount"
                                @input="handleInput"
                                type="text"
                                class="pl-3 h-11 text-lg font-bold"
                                placeholder="0"
                                autofocus
                            />
                        </div>
                        <p class="text-[11px] text-muted-foreground">
                            {{ selectedDue?.is_edit ? '*Masukkan nominal baru untuk mengganti data yang lama.' : '*Masukkan nominal uang yang diterima saat ini.' }}
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
    </AuthenticatedLayout>
</template>
