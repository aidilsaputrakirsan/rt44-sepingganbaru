<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Table, TableBody, TableCaption, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';

defineProps({
    stats: Object,
    dues: Array,
});

const form = useForm({});

const markAsPaid = (dueId) => {
    if (confirm('Mark this house as paid?')) {
        form.post(route('admin.payment.store', dueId), {
            preserveScroll: true,
            onSuccess: () => alert('Payment recorded!'),
        });
    }
};

const getStatusVariant = (status) => {
    if (status === 'paid') return 'default'; // primary/black
    if (status === 'unpaid') return 'destructive'; // red
    if (status === 'overdue') return 'warning'; // yellow (custom needed or use secondary)
    return 'outline';
};
</script>

<template>
    <Head title="Admin Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Admin Dashboard (Bendahara)
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-sm font-medium">Pending Verifications</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ stats.pending }}</div>
                            <p class="text-xs text-muted-foreground">Waiting for review</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle class="text-sm font-medium">Total Collected (This Month)</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold text-green-600 dark:text-green-400">Rp {{ Number(stats.collected).toLocaleString('id-ID') }}</div>
                            <p class="text-xs text-muted-foreground">Verified payments</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle class="text-sm font-medium">Unpaid Houses</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ stats.unpaid }}</div>
                            <p class="text-xs text-muted-foreground">Houses yet to pay</p>
                        </CardContent>
                    </Card>
                </div>

                <!-- Dues Checklist -->
                <Card>
                    <CardHeader>
                        <CardTitle>Checklist Iuran Warga (Bulan Ini)</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Rumah</TableHead>
                                    <TableHead>Pemilik</TableHead>
                                    <TableHead>Tagihan</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Action</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="due in dues" :key="due.id">
                                    <TableCell class="font-medium">{{ due.house }}</TableCell>
                                    <TableCell>{{ due.owner }}</TableCell>
                                    <TableCell>Rp {{ Number(due.amount).toLocaleString('id-ID') }}</TableCell>
                                    <TableCell>
                                        <Badge :variant="getStatusVariant(due.status)">
                                            {{ due.status.toUpperCase() }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <Button 
                                            v-if="!due.is_paid"
                                            size="sm"
                                            @click="markAsPaid(due.id)"
                                        >
                                            Check (Bayar)
                                        </Button>
                                        <span v-else class="text-green-600 font-bold text-sm">✓ Lunas</span>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
