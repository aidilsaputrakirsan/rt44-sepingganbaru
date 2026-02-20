<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Bar, Line } from 'vue-chartjs';
import {
 Chart as ChartJS,
 CategoryScale,
 LinearScale,
 BarElement,
 PointElement,
 LineElement,
 Title,
 Tooltip,
 Legend,
 Filler,
} from 'chart.js';
import { computed } from 'vue';
import { ChevronLeft, ChevronRight, AlertTriangle, Wallet } from 'lucide-vue-next';

ChartJS.register(
 CategoryScale, LinearScale, BarElement, PointElement, LineElement,
 Title, Tooltip, Legend, Filler
);

const props = defineProps({
 unpaidHouses: Array,
 saldoPerBulan: Array,
 year: Number,
 month: Number,
});

const monthNames = [
 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];
const monthNamesShort = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

const currentMonthLabel = computed(() => monthNames[props.month - 1] + ' ' + props.year);

const changeMonth = (delta) => {
 let newMonth = props.month + delta;
 let newYear = props.year;
 if (newMonth < 1) { newMonth = 12; newYear--; }
 if (newMonth > 12) { newMonth = 1; newYear++; }
 router.get(route('admin.dashboard'), { month: newMonth, year: newYear }, { preserveState: false });
};

const changeYear = (delta) => {
 router.get(route('admin.dashboard'), { month: props.month, year: props.year + delta }, { preserveState: false });
};

const formatCurrency = (amount) => {
 return new Intl.NumberFormat('id-ID', {
  style: 'currency',
  currency: 'IDR',
  minimumFractionDigits: 0,
  maximumFractionDigits: 0,
 }).format(amount);
};

const formatCompact = (amount) => {
 if (amount >= 1_000_000) return (amount / 1_000_000).toFixed(1).replace('.0', '') + ' Jt';
 if (amount >= 1_000) return (amount / 1_000).toFixed(0) + 'rb';
 return amount.toString();
};

// --- Chart 1: Tunggakan per rumah (Horizontal Bar) ---
const unpaidChartData = computed(() => ({
 labels: props.unpaidHouses.map(h => h.name),
 datasets: [{
  label: 'Sisa Tunggakan',
  data: props.unpaidHouses.map(h => h.remaining),
  backgroundColor: props.unpaidHouses.map((_, i) => {
   const colors = ['#ef4444', '#f97316', '#eab308', '#f43f5e', '#e11d48', '#dc2626', '#ea580c', '#d97706'];
   return colors[i % colors.length] + '90';
  }),
  borderColor: props.unpaidHouses.map((_, i) => {
   const colors = ['#ef4444', '#f97316', '#eab308', '#f43f5e', '#e11d48', '#dc2626', '#ea580c', '#d97706'];
   return colors[i % colors.length];
  }),
  borderWidth: 1,
  borderRadius: 4,
 }],
}));

const unpaidChartOptions = computed(() => ({
 indexAxis: 'y',
 responsive: true,
 maintainAspectRatio: false,
 plugins: {
  legend: { display: false },
  tooltip: {
   callbacks: {
    label: (ctx) => formatCurrency(ctx.raw),
   },
  },
 },
 scales: {
  x: {
   ticks: {
    callback: (value) => formatCompact(value),
    font: { size: 11 },
   },
   grid: { color: '#f1f5f9' },
  },
  y: {
   ticks: {
    font: { size: 11, weight: 'bold' },
   },
   grid: { display: false },
  },
 },
}));

// --- Chart 2: Saldo Kas per bulan (Area Line) ---
const saldoChartData = computed(() => ({
 labels: monthNamesShort,
 datasets: [{
  label: 'Saldo Akhir',
  data: props.saldoPerBulan,
  borderColor: '#10b981',
  backgroundColor: 'rgba(16, 185, 129, 0.1)',
  fill: true,
  tension: 0.4,
  pointBackgroundColor: '#10b981',
  pointBorderColor: '#fff',
  pointBorderWidth: 2,
  pointRadius: 5,
  pointHoverRadius: 7,
 }],
}));

const saldoChartOptions = {
 responsive: true,
 maintainAspectRatio: false,
 plugins: {
  legend: { display: false },
  tooltip: {
   callbacks: {
    label: (ctx) => formatCurrency(ctx.raw),
   },
  },
 },
 scales: {
  y: {
   ticks: {
    callback: (value) => formatCompact(value),
    font: { size: 11 },
   },
   grid: { color: '#f1f5f9' },
  },
  x: {
   ticks: { font: { size: 11 } },
   grid: { display: false },
  },
 },
};

// Total tunggakan
const totalTunggakan = computed(() => props.unpaidHouses.reduce((sum, h) => sum + h.remaining, 0));

// Saldo terakhir (bulan berjalan)
const latestSaldo = computed(() => {
 const currentIdx = props.month - 1;
 return props.saldoPerBulan[currentIdx] ?? props.saldoPerBulan[props.saldoPerBulan.length - 1] ?? 0;
});

// Dynamic chart height based on data count
const unpaidChartHeight = computed(() => {
 const count = props.unpaidHouses.length;
 if (count <= 5) return 220;
 if (count <= 15) return Math.max(300, count * 28);
 return Math.max(400, count * 24);
});
</script>

<template>
 <Head title="Dashboard Admin" />

 <AuthenticatedLayout>
  <template #header>
   <h2 class="text-xl font-semibold leading-tight text-gray-800">
    Dashboard Admin
   </h2>
  </template>

  <div class="py-12">
   <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">

    <!-- Chart 2: Saldo Kas -->
    <Card>
     <CardHeader>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
       <div>
        <CardTitle class="flex items-center gap-2">
         <Wallet class="w-5 h-5 text-emerald-500" />
         Saldo Kas RT per Bulan
        </CardTitle>
        <CardDescription class="mt-1">Tren saldo akhir kas RT sepanjang tahun</CardDescription>
       </div>
       <div class="flex items-center gap-2 shrink-0">
        <Button variant="outline" size="icon" class="h-8 w-8" @click="changeYear(-1)">
         <ChevronLeft class="w-4 h-4" />
        </Button>
        <span class="text-lg font-bold min-w-[60px] text-center">{{ year }}</span>
        <Button variant="outline" size="icon" class="h-8 w-8" @click="changeYear(1)">
         <ChevronRight class="w-4 h-4" />
        </Button>
       </div>
      </div>
     </CardHeader>
     <CardContent>
      <div style="height: 320px">
       <Line :data="saldoChartData" :options="saldoChartOptions" />
      </div>
     </CardContent>
    </Card>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
     <Card class="border-l-4 border-l-red-500">
      <CardHeader class="pb-2">
       <CardDescription class="flex items-center gap-1.5">
        <AlertTriangle class="w-3.5 h-3.5" />
        Warga Belum Bayar ({{ currentMonthLabel }})
       </CardDescription>
       <CardTitle class="text-2xl font-black text-red-600">
        {{ unpaidHouses.length }} <span class="text-base font-medium text-slate-400">rumah</span>
       </CardTitle>
       <p class="text-xs text-slate-500 mt-1">Total tunggakan: <span class="font-bold text-red-600">{{ formatCurrency(totalTunggakan) }}</span></p>
      </CardHeader>
     </Card>
     <Card class="border-l-4 border-l-emerald-500">
      <CardHeader class="pb-2">
       <CardDescription class="flex items-center gap-1.5">
        <Wallet class="w-3.5 h-3.5" />
        Saldo Kas RT ({{ monthNames[month - 1] }})
       </CardDescription>
       <CardTitle class="text-2xl font-black text-emerald-600">
        {{ formatCurrency(latestSaldo) }}
       </CardTitle>
       <p class="text-xs text-slate-500 mt-1">Saldo akhir bulan {{ monthNames[month - 1].toLowerCase() }} {{ year }}</p>
      </CardHeader>
     </Card>
    </div>

    <!-- Chart 1: Tunggakan -->
    <Card>
     <CardHeader>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
       <div>
        <CardTitle class="flex items-center gap-2">
         <AlertTriangle class="w-5 h-5 text-red-500" />
         Warga Belum Bayar
        </CardTitle>
        <CardDescription class="mt-1">Daftar rumah yang masih ada tunggakan iuran</CardDescription>
       </div>
       <div class="flex items-center gap-2 shrink-0">
        <Button variant="outline" size="icon" class="h-8 w-8" @click="changeMonth(-1)">
         <ChevronLeft class="w-4 h-4" />
        </Button>
        <span class="text-sm font-bold min-w-[130px] text-center">{{ currentMonthLabel }}</span>
        <Button variant="outline" size="icon" class="h-8 w-8" @click="changeMonth(1)">
         <ChevronRight class="w-4 h-4" />
        </Button>
       </div>
      </div>
     </CardHeader>
     <CardContent>
      <div v-if="unpaidHouses.length === 0" class="flex flex-col items-center justify-center py-12 text-center">
       <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mb-4">
        <svg class="w-8 h-8 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
         <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
        </svg>
       </div>
       <p class="text-lg font-bold text-green-600">Semua lunas!</p>
       <p class="text-sm text-slate-500 mt-1">Tidak ada tunggakan untuk {{ currentMonthLabel }}</p>
      </div>
      <div v-else :style="{ height: unpaidChartHeight + 'px' }">
       <Bar :data="unpaidChartData" :options="unpaidChartOptions" />
      </div>
     </CardContent>
    </Card>

    

   </div>
  </div>
 </AuthenticatedLayout>
</template>
