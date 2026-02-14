<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Search, X, Settings2 } from 'lucide-vue-next';

const props = defineProps({
 dues: Array,
});

const searchQuery = ref('');
const filteredDues = computed(() => {
 const q = searchQuery.value.toLowerCase().trim();
 if (!q) return props.dues;
 return props.dues.filter(d =>
 d.house.toLowerCase().includes(q) || d.owner.toLowerCase().includes(q)
 );
});

// --- Flash ---
const page = usePage();
const flash = computed(() => page.props.flash || {});
const showFlash = ref(true);

// --- Bulk Update ---
const bulkForm = useForm({
 amount_berpenghuni: null,
 amount_kosong: null,
});
const displayBerpenghuni = ref('');
const displayKosong = ref('');

const handleBulkInput = (e, field) => {
 let value = e.target.value.replace(/\D/g, '');
 if (value === '') {
 bulkForm[field] = null;
 if (field === 'amount_berpenghuni') displayBerpenghuni.value = '';
 else displayKosong.value = '';
 return;
 }
 const num = parseInt(value, 10);
 bulkForm[field] = num;
 if (field === 'amount_berpenghuni') displayBerpenghuni.value = formatNumber(num);
 else displayKosong.value = formatNumber(num);
};

const submitBulkUpdate = () => {
 if (bulkForm.amount_berpenghuni === null && bulkForm.amount_kosong === null) return;
 bulkForm.post(route('admin.tagihan.bulk-update'), {
 preserveScroll: true,
 onSuccess: () => {
 bulkForm.reset();
 displayBerpenghuni.value = '';
 displayKosong.value = '';
 showFlash.value = true;
 },
 });
};

const isModalOpen = ref(false);
const selectedDue = ref(null);
const displayAmount = ref('');

const form = useForm({
 amount: '',
});

const formatNumber = (num) => {
 if (!num && num !== 0) return '';
 return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
};

const openEditModal = (due) => {
 selectedDue.value = due;
 form.amount = due.amount;
 displayAmount.value = formatNumber(due.amount);
 isModalOpen.value = true;
};

const handleInput = (e) => {
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

const submitUpdate = () => {
 if (!selectedDue.value || form.amount === '') return;

 form.patch(route('admin.due.update', selectedDue.value.id), {
 preserveScroll: true,
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
 maximumFractionDigits: 0,
 }).format(amount);
};
</script>

<template>
 <Head title="Tagihan Data Warga" />

 <AuthenticatedLayout>
 <template #header>
 <h2 class="text-xl font-semibold leading-tight text-gray-800">
 Kelola Tagihan Iuran Warga
 </h2>
 </template>

 <div class="py-12">
 <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
 <Card>
 <CardHeader>
 <CardTitle>Daftar Tagihan (Bulan Ini)</CardTitle>
 <p class="text-sm text-muted-foreground">
 Halaman ini khusus untuk mengatur besaran tagihan per rumah. Untuk input pembayaran, silakan gunakan menu <b>Kalender Iuran</b>.
 </p>
 </CardHeader>
 <CardContent>
 <!-- Flash Message -->
 <div v-if="showFlash && (flash.success || flash.error)" class="mb-4 rounded-lg px-4 py-3 text-sm flex items-center justify-between" :class="flash.success ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'">
 <span>{{ flash.success || flash.error }}</span>
 <button @click="showFlash = false" class="ml-2 text-current opacity-60 hover:opacity-100">&times;</button>
 </div>

 <!-- Bulk Update -->
 <div class="mb-5 rounded-lg border bg-slate-50 p-4">
 <div class="flex items-center gap-2 mb-3">
 <Settings2 class="w-4 h-4 text-muted-foreground" />
 <span class="text-sm font-semibold">Atur Nominal Massal</span>
 </div>
 <div class="flex flex-col sm:flex-row items-end gap-3">
 <div class="flex-1 w-full">
 <Label class="text-xs text-muted-foreground mb-1 block">Berpenghuni (Rp)</Label>
 <Input
 :modelValue="displayBerpenghuni"
 @input="handleBulkInput($event, 'amount_berpenghuni')"
 type="text"
 placeholder="160.000"
 class="h-9 text-sm font-semibold"
 />
 </div>
 <div class="flex-1 w-full">
 <Label class="text-xs text-muted-foreground mb-1 block">Kosong (Rp)</Label>
 <Input
 :modelValue="displayKosong"
 @input="handleBulkInput($event, 'amount_kosong')"
 type="text"
 placeholder="110.000"
 class="h-9 text-sm font-semibold"
 />
 </div>
 <Button
 @click="submitBulkUpdate"
 :disabled="bulkForm.processing || (bulkForm.amount_berpenghuni === null && bulkForm.amount_kosong === null)"
 size="sm"
 class="shrink-0"
 >
 Terapkan Semua
 </Button>
 </div>
 <p class="text-[11px] text-muted-foreground mt-2 italic">
 *Isi nominal yang ingin diubah, kosongkan jika tidak ingin mengubah. Perubahan berlaku untuk tagihan bulan ini.
 </p>
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

 <Table>
 <TableHeader>
 <TableRow>
 <TableHead>Rumah</TableHead>
 <TableHead>Pemilik</TableHead>
 <TableHead>Nominal Tagihan</TableHead>
 <TableHead class="text-center w-32">Action</TableHead>
 </TableRow>
 </TableHeader>
 <TableBody>
 <TableRow v-for="due in filteredDues" :key="due.id">
 <TableCell class="font-bold">{{ due.house }}</TableCell>
 <TableCell>{{ due.owner }}</TableCell>
 <TableCell class="font-semibold text-slate-900">
 {{ formatCurrency(due.amount) }}
 </TableCell>
 <TableCell class="text-center">
 <Button 
 variant="outline"
 size="sm"
 @click="openEditModal(due)"
 >
 Ubah Tagihan
 </Button>
 </TableCell>
 </TableRow>
 </TableBody>
 </Table>
 </CardContent>
 </Card>
 </div>
 </div>

 <!-- Edit Bill Modal -->
 <Dialog :open="isModalOpen" @update:open="isModalOpen = $event">
 <DialogContent class="sm:max-w-md">
 <DialogHeader>
 <DialogTitle>Ubah Nominal Tagihan</DialogTitle>
 </DialogHeader>
 
 <div v-if="selectedDue" class="space-y-4 py-4">
 <div class="rounded-lg border bg-muted/30 p-4 text-sm space-y-2">
 <div class="flex justify-between">
 <span class="text-muted-foreground">Rumah:</span>
 <span class="font-bold">{{ selectedDue.house }}</span>
 </div>
 <div class="flex justify-between">
 <span class="text-muted-foreground">Pemilik:</span>
 <span>{{ selectedDue.owner }}</span>
 </div>
 </div>

 <div class="space-y-3">
 <Label class="text-sm font-bold">Nominal Tagihan Baru (Rp)</Label>
 <div class="relative">
 <span class="absolute left-3 top-1/2 -translate-y-1/2 font-bold text-slate-400">Rp</span>
 <Input
 :modelValue="displayAmount"
 @input="handleInput"
 type="text"
 class="pl-10 h-10 text-lg font-bold"
 autofocus
 />
 </div>
 <p class="text-[11px] text-muted-foreground italic">
 *Perubahan ini akan memperbarui nilai tagihan dasar rumah ini untuk bulan berjalan.
 </p>
 </div>
 </div>

 <DialogFooter>
 <div class="flex justify-end gap-2 w-full">
 <Button type="button" variant="ghost" @click="isModalOpen = false">
 Batal
 </Button>
 <Button type="submit" @click="submitUpdate" :disabled="form.processing">
 Simpan Perubahan
 </Button>
 </div>
 </DialogFooter>
 </DialogContent>
 </Dialog>
 </AuthenticatedLayout>
</template>
