<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';

defineProps({
    dues: Array,
});

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
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
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
                                <TableRow v-for="due in dues" :key="due.id">
                                    <TableCell class="font-bold">{{ due.house }}</TableCell>
                                    <TableCell>{{ due.owner }}</TableCell>
                                    <TableCell class="font-semibold text-slate-900 dark:text-slate-100">
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
