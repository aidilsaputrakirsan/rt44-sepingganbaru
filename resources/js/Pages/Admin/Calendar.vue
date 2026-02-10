<script setup>
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Table, TableBody, TableCaption, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogDescription } from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { MessageCircle, Loader2 } from 'lucide-vue-next';
import { ref, computed } from 'vue';

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

const openReminderModal = (row) => {
    reminderTarget.value = row;
    isReminderModalOpen.value = true;
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
                        <!-- Flash Message -->
                        <div v-if="showFlash && (flash.success || flash.error)" class="mb-4 rounded-lg px-4 py-3 text-sm flex items-center justify-between" :class="flash.success ? 'bg-green-50 text-green-700 border border-green-200 dark:bg-green-900/30 dark:text-green-300 dark:border-green-800' : 'bg-red-50 text-red-700 border border-red-200 dark:bg-red-900/30 dark:text-red-300 dark:border-red-800'">
                            <span>{{ flash.success || flash.error }}</span>
                            <button @click="showFlash = false" class="ml-2 text-current opacity-60 hover:opacity-100">&times;</button>
                        </div>

                        <Table class="w-full text-center text-xs md:text-sm">
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="text-left sticky left-0 bg-white dark:bg-slate-950 z-10 w-40 border-r shadow-sm">Rumah</TableHead>
                                    <TableHead v-for="month in months" :key="month" class="text-center min-w-[100px]">{{ month }}</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="row in calendar" :key="row.id">
                                    <TableCell class="text-left font-bold sticky left-0 bg-white dark:bg-slate-950 z-10 border-r shadow-sm">
                                        <div class="flex items-center justify-between gap-1">
                                            <div>
                                                {{ row.name }}
                                                <div class="text-[10px] text-muted-foreground font-normal truncate max-w-[100px]">{{ row.owner }}</div>
                                            </div>
                                            <button
                                                v-if="hasUnpaid(row) && row.phone"
                                                @click.stop="openReminderModal(row)"
                                                class="shrink-0 w-7 h-7 rounded-full flex items-center justify-center bg-green-500 hover:bg-green-600 text-white transition-colors shadow-sm"
                                                title="Kirim Reminder WA"
                                            >
                                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor">
                                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.0740-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                                </svg>
                                            </button>
                                        </div>
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
                    <p class="text-xs text-muted-foreground">
                        Pesan reminder berisi daftar tagihan yang belum lunas tahun {{ year }} akan dikirim via WhatsApp.
                    </p>
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
    </AuthenticatedLayout>
</template>
