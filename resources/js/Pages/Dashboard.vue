<script setup>
import { Head, usePage, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Table, TableBody, TableCaption, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';

defineProps({
    houses: Array,
});

const user = usePage().props.auth.user;

const getStatusVariant = (status) => {
    if (status === 'paid') return 'default'; // primary/green (if styled)
    if (status === 'unpaid') return 'destructive'; // red
    if (status === 'overdue') return 'warning';
    return 'secondary';
};
</script>

<template>
    <Head title="Dashboard Warga" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Dashboard Warga
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                <Card v-if="houses.length === 0" class="text-center p-6 bg-muted/50">
                    <CardContent class="pt-6">
                        <p class="text-muted-foreground">Belum ada data rumah yang terhubung dengan akun ini. Hubungi Bendahara.</p>
                    </CardContent>
                </Card>

                <Card v-for="house in houses" :key="house.id">
                    <CardHeader>
                        <div class="flex justify-between items-center">
                            <div>
                                <CardTitle>Rumah Blok {{ house.blok }} No. {{ house.nomor }}</CardTitle>
                                <CardDescription>Status: {{ house.status_huni.toUpperCase() }}</CardDescription>
                            </div>
                            <Button variant="outline" as-child>
                                <Link :href="route('dashboard.calendar')">Lihat Kalender</Link>
                            </Button>
                        </div>
                    </CardHeader>
                    
                    <CardContent>
                        <h4 class="font-semibold mb-4 text-sm uppercase tracking-wide text-muted-foreground">Riwayat Tagihan (Iuran)</h4>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Periode</TableHead>
                                    <TableHead>Jumlah</TableHead>
                                    <TableHead>Jatuh Tempo</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="due in house.dues" :key="due.id">
                                    <TableCell>{{ new Date(due.period).toLocaleDateString('id-ID', { month: 'long', year: 'numeric' }) }}</TableCell>
                                    <TableCell>Rp {{ Number(due.amount).toLocaleString('id-ID') }}</TableCell>
                                    <TableCell>{{ new Date(due.due_date).toLocaleDateString('id-ID') }}</TableCell>
                                    <TableCell>
                                        <Badge :variant="getStatusVariant(due.status)">
                                            {{ due.status.toUpperCase() }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <Button 
                                            v-if="due.status !== 'paid'" 
                                            variant="secondary" 
                                            size="sm"
                                        >
                                            Konfirmasi / Upload
                                        </Button>
                                        <Button 
                                            v-else 
                                            variant="ghost" 
                                            size="sm"
                                        >
                                            Download Kwitansi
                                        </Button>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="house.dues.length === 0">
                                    <TableCell colspan="5" class="text-center py-4 text-muted-foreground">Belum ada tagihan.</TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
