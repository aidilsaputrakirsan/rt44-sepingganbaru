<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Table, TableBody, TableCaption, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';

const props = defineProps({
    calendar: Array,
    year: Number,
});

const months = [
    'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
    'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
];

const getStatusColor = (status) => {
    switch (status) {
        case 'paid': return 'bg-green-500 text-white';
        case 'unpaid': return 'bg-red-500 text-white';
        case 'overdue': return 'bg-yellow-500 text-white';
        default: return 'bg-gray-100 dark:bg-gray-800 text-gray-300';
    }
};

const getStatusText = (status) => {
    switch (status) {
        case 'paid': return '✓';
        case 'unpaid': return '✗';
        case 'overdue': return '!';
        default: return '•';
    }
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
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <Card>
                    <CardHeader>
                        <CardTitle>Matrix Pembayaran</CardTitle>
                    </CardHeader>
                    <CardContent class="overflow-x-auto">
                        <Table class="w-full text-center text-xs md:text-sm">
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="text-left sticky left-0 bg-white dark:bg-slate-950 z-10 w-24">Rumah</TableHead>
                                    <TableHead v-for="month in months" :key="month" class="text-center w-16">{{ month }}</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="row in calendar" :key="row.id">
                                    <TableCell class="text-left font-bold sticky left-0 bg-white dark:bg-slate-950 z-10 border-r">
                                        {{ row.name }}
                                        <div class="text-[10px] text-muted-foreground font-normal truncate max-w-[100px]">{{ row.owner }}</div>
                                    </TableCell>
                                    <TableCell v-for="(data, m) in row.months" :key="m" class="p-1">
                                        <div 
                                            class="w-8 h-8 mx-auto flex items-center justify-center rounded-full cursor-help transition-colors font-bold text-xs"
                                            :class="getStatusColor(data.status)"
                                            :title="data.amount > 0 ? `Rp ${Number(data.amount).toLocaleString('id-ID')} (${data.status})` : 'No Bill'"
                                        >
                                            {{ getStatusText(data.status) }}
                                        </div>
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
