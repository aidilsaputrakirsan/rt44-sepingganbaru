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

const getStatusColor = (status, isSubsidized) => {
 if (status === 'none' && isSubsidized) return 'text-slate-400';
 switch (status) {
 case 'paid': return 'text-green-600';
 case 'unpaid': return 'text-red-600';
 case 'overdue': return 'text-yellow-600';
 default: return 'text-gray-400';
 }
};

const getBorderColor = (status, isSubsidized) => {
 if (status === 'none' && isSubsidized) return 'border-slate-200 bg-slate-50 opacity-80';
 switch (status) {
 case 'paid': return 'border-green-200 bg-green-50';
 case 'unpaid': return 'border-red-200 bg-red-50';
 default: return 'border-gray-200 bg-white';
 }
};

const getStatusText = (status, isSubsidized) => {
 if (status === 'none' && isSubsidized) return 'SUBSIDI';
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
 <h2 class="text-xl font-semibold leading-tight text-gray-800">
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
