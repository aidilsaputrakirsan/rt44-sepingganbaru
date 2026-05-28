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
 PointElement,
 LineElement,
 Title,
 Tooltip,
 Legend,
 Filler,
} from 'chart.js';
import { computed } from 'vue';
import { ChevronLeft, ChevronRight, ChevronDown, AlertTriangle, Wallet, LayoutDashboard, IdCard, CheckCircle2, XCircle, AlertCircle, Search } from 'lucide-vue-next';
import { ref } from 'vue';
import { Input } from '@/Components/ui/input';

ChartJS.register(
 CategoryScale, LinearScale, PointElement, LineElement,
 Title, Tooltip, Legend, Filler
);

const props = defineProps({
 unpaidHouses: Array,
 saldoPerBulan: Array,
 profileStatuses: { type: Array, default: () => [] },
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

// --- Chart 1: Tunggakan per rumah is removed in favor of a grid matrix UI ---

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

// Collapse state
const unpaidOpen = ref(true);
const profileOpen = ref(true);

// Profil Warga - stats & filter
const profileFilter = ref('belum');
const profileSearch = ref('');

const profileStats = computed(() => {
 const total = props.profileStatuses.length;
 const lengkap = props.profileStatuses.filter(p => p.status === 'lengkap').length;
 const sebagian = props.profileStatuses.filter(p => p.status === 'sebagian').length;
 const belum = props.profileStatuses.filter(p => p.status === 'belum').length;
 const percent = total > 0 ? Math.round((lengkap / total) * 100) : 0;
 return { total, lengkap, sebagian, belum, percent };
});

const filteredProfiles = computed(() => {
 const q = profileSearch.value.trim().toLowerCase();
 return props.profileStatuses.filter(p => {
  if (profileFilter.value !== 'semua' && p.status !== profileFilter.value) return false;
  if (q && !p.name.toLowerCase().includes(q)) return false;
  return true;
 });
});

const profileStatusBadge = (status) => {
 if (status === 'lengkap') return { label: 'Lengkap', cls: 'bg-emerald-50 border-emerald-200 text-emerald-700' };
 if (status === 'sebagian') return { label: 'Sebagian', cls: 'bg-amber-50 border-amber-200 text-amber-700' };
 return { label: 'Belum', cls: 'bg-red-50 border-red-200 text-red-700' };
};

// Total tunggakan
const totalTunggakan = computed(() => props.unpaidHouses.reduce((sum, h) => sum + h.remaining, 0));

// Saldo terakhir (bulan berjalan)
const latestSaldo = computed(() => {
 const currentIdx = props.month - 1;
 return props.saldoPerBulan[currentIdx] ?? props.saldoPerBulan[props.saldoPerBulan.length - 1] ?? 0;
});


</script>

<template>
 <Head title="Dashboard Admin" />

 <AuthenticatedLayout>
  <template #header>
   <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
     <h2 class="text-2xl font-bold text-slate-900 flex items-center gap-2">
      <LayoutDashboard class="w-6 h-6 text-indigo-600" />
      Dashboard Admin
     </h2>
     <p class="text-slate-500 mt-1 uppercase text-sm tracking-wider font-medium">
      Ringkasan Data RT-44
     </p>
    </div>
   </div>
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
     <CardHeader class="cursor-pointer select-none hover:bg-slate-50/60 rounded-t-xl transition-colors" @click="unpaidOpen = !unpaidOpen">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
       <div class="flex items-start gap-2">
        <ChevronDown class="w-5 h-5 text-slate-400 mt-0.5 transition-transform shrink-0" :class="{ '-rotate-90': !unpaidOpen }" />
        <div>
         <CardTitle class="flex items-center gap-2">
          <AlertTriangle class="w-5 h-5 text-red-500" />
          Warga Belum Bayar
         </CardTitle>
         <CardDescription class="mt-1">Daftar rumah yang masih ada tunggakan iuran</CardDescription>
        </div>
       </div>
       <div class="flex items-center gap-2 shrink-0" @click.stop>
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
     <CardContent v-show="unpaidOpen">
      <div v-if="unpaidHouses.length === 0" class="flex flex-col items-center justify-center py-12 text-center">
       <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mb-4">
        <svg class="w-8 h-8 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
         <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
        </svg>
       </div>
       <p class="text-lg font-bold text-green-600">Semua lunas!</p>
       <p class="text-sm text-slate-500 mt-1">Tidak ada tunggakan untuk {{ currentMonthLabel }}</p>
      </div>
      <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
       <div v-for="house in unpaidHouses" :key="house.name" class="flex flex-col p-3 rounded-lg border border-red-100 bg-red-50/50 hover:bg-red-50 transition-colors">
        <span class="text-sm font-bold text-slate-800">{{ house.name }}</span>
        <span class="text-xs font-semibold text-red-600 mt-1">{{ formatCurrency(house.remaining) }}</span>
       </div>
      </div>
     </CardContent>
    </Card>

    <!-- Kelengkapan Profil Warga -->
    <Card v-if="profileStatuses.length > 0">
     <CardHeader class="cursor-pointer select-none hover:bg-slate-50/60 rounded-t-xl transition-colors" @click="profileOpen = !profileOpen">
      <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
       <div class="flex items-start gap-2">
        <ChevronDown class="w-5 h-5 text-slate-400 mt-0.5 transition-transform shrink-0" :class="{ '-rotate-90': !profileOpen }" />
        <div>
         <CardTitle class="flex items-center gap-2">
          <IdCard class="w-5 h-5 text-indigo-500" />
          Kelengkapan Profil Warga
         </CardTitle>
         <CardDescription class="mt-1">
          Status upload Kartu Keluarga & KTP per rumah. Lengkap = KK + minimal 1 KTP.
         </CardDescription>
        </div>
       </div>
       <div class="flex items-center gap-3 shrink-0">
        <div class="text-right">
         <div class="text-2xl font-black text-indigo-600 leading-none">{{ profileStats.percent }}%</div>
         <div class="text-[10px] uppercase tracking-wider text-slate-500 mt-0.5">Lengkap</div>
        </div>
        <div class="relative w-14 h-14">
         <svg class="w-full h-full -rotate-90" viewBox="0 0 36 36">
          <circle cx="18" cy="18" r="16" fill="none" stroke="#e2e8f0" stroke-width="3" />
          <circle
           cx="18" cy="18" r="16" fill="none"
           stroke="#6366f1" stroke-width="3" stroke-linecap="round"
           :stroke-dasharray="`${profileStats.percent} 100`"
           pathLength="100"
          />
         </svg>
        </div>
       </div>
      </div>
     </CardHeader>
     <CardContent v-show="profileOpen">
      <!-- Summary Cards -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
       <button
        @click="profileFilter = 'semua'"
        class="text-left p-3 rounded-lg border-2 transition-all"
        :class="profileFilter === 'semua' ? 'border-slate-700 bg-slate-50 ring-2 ring-slate-200' : 'border-slate-200 bg-white hover:border-slate-300'"
       >
        <div class="flex items-center gap-1.5 text-xs uppercase tracking-wider text-slate-500 font-semibold mb-1">
         <IdCard class="w-3.5 h-3.5" />
         Total
        </div>
        <div class="text-2xl font-black text-slate-800">{{ profileStats.total }}</div>
       </button>
       <button
        @click="profileFilter = 'lengkap'"
        class="text-left p-3 rounded-lg border-2 transition-all"
        :class="profileFilter === 'lengkap' ? 'border-emerald-500 bg-emerald-50 ring-2 ring-emerald-200' : 'border-emerald-100 bg-emerald-50/40 hover:border-emerald-300'"
       >
        <div class="flex items-center gap-1.5 text-xs uppercase tracking-wider text-emerald-700 font-semibold mb-1">
         <CheckCircle2 class="w-3.5 h-3.5" />
         Lengkap
        </div>
        <div class="text-2xl font-black text-emerald-600">{{ profileStats.lengkap }}</div>
       </button>
       <button
        @click="profileFilter = 'sebagian'"
        class="text-left p-3 rounded-lg border-2 transition-all"
        :class="profileFilter === 'sebagian' ? 'border-amber-500 bg-amber-50 ring-2 ring-amber-200' : 'border-amber-100 bg-amber-50/40 hover:border-amber-300'"
       >
        <div class="flex items-center gap-1.5 text-xs uppercase tracking-wider text-amber-700 font-semibold mb-1">
         <AlertCircle class="w-3.5 h-3.5" />
         Sebagian
        </div>
        <div class="text-2xl font-black text-amber-600">{{ profileStats.sebagian }}</div>
       </button>
       <button
        @click="profileFilter = 'belum'"
        class="text-left p-3 rounded-lg border-2 transition-all"
        :class="profileFilter === 'belum' ? 'border-red-500 bg-red-50 ring-2 ring-red-200' : 'border-red-100 bg-red-50/40 hover:border-red-300'"
       >
        <div class="flex items-center gap-1.5 text-xs uppercase tracking-wider text-red-700 font-semibold mb-1">
         <XCircle class="w-3.5 h-3.5" />
         Belum
        </div>
        <div class="text-2xl font-black text-red-600">{{ profileStats.belum }}</div>
       </button>
      </div>

      <!-- Stacked progress bar -->
      <div class="mb-5">
       <div class="flex h-2.5 w-full rounded-full overflow-hidden bg-slate-100">
        <div class="bg-emerald-500" :style="{ width: profileStats.total ? (profileStats.lengkap / profileStats.total * 100) + '%' : '0%' }"></div>
        <div class="bg-amber-500" :style="{ width: profileStats.total ? (profileStats.sebagian / profileStats.total * 100) + '%' : '0%' }"></div>
        <div class="bg-red-500" :style="{ width: profileStats.total ? (profileStats.belum / profileStats.total * 100) + '%' : '0%' }"></div>
       </div>
      </div>

      <!-- Search -->
      <div class="relative mb-4 max-w-sm">
       <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
       <Input v-model="profileSearch" placeholder="Cari nomor rumah..." class="pl-9 h-9" />
      </div>

      <!-- Grid daftar rumah -->
      <div v-if="filteredProfiles.length === 0" class="text-center py-8 text-sm text-slate-500">
       Tidak ada data yang cocok dengan filter.
      </div>
      <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2.5">
       <div
        v-for="house in filteredProfiles"
        :key="house.name"
        class="flex flex-col p-2.5 rounded-lg border transition-colors"
        :class="profileStatusBadge(house.status).cls"
       >
        <span class="text-sm font-bold leading-tight">{{ house.name }}</span>
        <div class="flex items-center gap-1.5 mt-1.5 text-[10px] font-medium">
         <span class="flex items-center gap-0.5" :title="house.has_kk ? 'KK terunggah' : 'KK belum diunggah'">
          <CheckCircle2 v-if="house.has_kk" class="w-3 h-3 text-emerald-600" />
          <XCircle v-else class="w-3 h-3 text-slate-400" />
          KK
         </span>
         <span class="flex items-center gap-0.5" :title="`${house.ktp_count} KTP terunggah`">
          <CheckCircle2 v-if="house.has_ktp" class="w-3 h-3 text-emerald-600" />
          <XCircle v-else class="w-3 h-3 text-slate-400" />
          KTP{{ house.ktp_count > 0 ? ` (${house.ktp_count})` : '' }}
         </span>
        </div>
       </div>
      </div>
     </CardContent>
    </Card>

   </div>
  </div>
 </AuthenticatedLayout>
</template>
