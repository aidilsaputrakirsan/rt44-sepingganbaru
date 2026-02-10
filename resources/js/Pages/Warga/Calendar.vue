<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';

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
        case 'paid': return 'text-green-600 dark:text-green-400';
        case 'unpaid': return 'text-red-600 dark:text-red-400';
        case 'overdue': return 'text-yellow-600 dark:text-yellow-400';
        default: return 'text-gray-400';
    }
};

const getBorderColor = (status) => {
     switch (status) {
        case 'paid': return 'border-green-200 dark:border-green-900 bg-green-50 dark:bg-green-900/10';
        case 'unpaid': return 'border-red-200 dark:border-red-900 bg-red-50 dark:bg-red-900/10';
        default: return 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800';
    }
};

const getStatusText = (status) => {
    switch (status) {
        case 'paid': return 'LUNAS';
        case 'unpaid': return 'BELUM';
        case 'overdue': return 'TELAT';
        default: return '-';
    }
};
</script>

<template>
    <Head title="Kalender Iuran Saya" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Kalender Iuran Saya ({{ year }})
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                <Card v-for="house in calendar" :key="house.id">
                    <CardHeader>
                        <CardTitle>Rumah Blok {{ house.name }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                            <div v-for="(data, m) in house.months" :key="m" 
                                 class="flex flex-col items-center justify-center p-4 rounded-lg border transition-colors"
                                 :class="getBorderColor(data.status)"
                            >
                                <span class="text-muted-foreground text-sm mb-1 uppercase tracking-wider font-semibold">{{ months[m-1] }}</span>
                                <span class="font-bold text-lg" :class="getStatusColor(data.status)">{{ getStatusText(data.status) }}</span>
                                <span v-if="data.amount > 0" class="text-xs text-muted-foreground mt-1">Rp {{ Number(data.amount).toLocaleString('id-ID') }}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
