<script setup>
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { IdCard, LayoutDashboard, ChevronDown, Search, Users, UserCircle } from 'lucide-vue-next';

const props = defineProps({
    profileStatuses: { type: Array, default: () => [] },
});

const profileFilter = ref('belum');
const profileSearch = ref('');
const profileOpen = ref(true);

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
    if (status === 'lengkap') return {
        label: 'Lengkap',
        borderCls: 'border-emerald-200',
        headerCls: 'bg-emerald-50 border-emerald-200',
        chipCls: 'bg-emerald-600 text-white',
    };
    if (status === 'sebagian') return {
        label: 'Sebagian',
        borderCls: 'border-amber-200',
        headerCls: 'bg-amber-50 border-amber-200',
        chipCls: 'bg-amber-600 text-white',
    };
    return {
        label: 'Belum',
        borderCls: 'border-red-200',
        headerCls: 'bg-red-50 border-red-200',
        chipCls: 'bg-red-600 text-white',
    };
};
</script>

<template>
    <Head title="Dashboard Ketua" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 flex items-center gap-2">
                        <LayoutDashboard class="w-6 h-6 text-blue-600" />
                        Dashboard Ketua RT
                    </h2>
                    <p class="text-slate-500 mt-1 uppercase text-sm tracking-wider font-medium">
                        Status Kelengkapan Profil Warga
                    </p>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">

                <!-- Summary stat cards -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <Card class="border-l-4 border-l-slate-400">
                        <CardHeader class="pb-2">
                            <CardDescription class="flex items-center gap-1.5">
                                <Users class="w-3.5 h-3.5" />
                                Total Rumah
                            </CardDescription>
                            <CardTitle class="text-2xl font-black text-slate-700">
                                {{ profileStats.total }} <span class="text-base font-medium text-slate-400">rumah</span>
                            </CardTitle>
                        </CardHeader>
                    </Card>
                    <Card class="border-l-4 border-l-emerald-500">
                        <CardHeader class="pb-2">
                            <CardDescription>Lengkap (KK + KTP)</CardDescription>
                            <CardTitle class="text-2xl font-black text-emerald-600">
                                {{ profileStats.lengkap }}
                            </CardTitle>
                            <p class="text-xs text-slate-500 mt-1">{{ profileStats.percent }}% dari total</p>
                        </CardHeader>
                    </Card>
                    <Card class="border-l-4 border-l-amber-500">
                        <CardHeader class="pb-2">
                            <CardDescription>Sebagian</CardDescription>
                            <CardTitle class="text-2xl font-black text-amber-600">
                                {{ profileStats.sebagian }}
                            </CardTitle>
                            <p class="text-xs text-slate-500 mt-1">Hanya KK atau KTP</p>
                        </CardHeader>
                    </Card>
                    <Card class="border-l-4 border-l-red-500">
                        <CardHeader class="pb-2">
                            <CardDescription>Belum Mengisi</CardDescription>
                            <CardTitle class="text-2xl font-black text-red-600">
                                {{ profileStats.belum }}
                            </CardTitle>
                            <p class="text-xs text-slate-500 mt-1">Belum upload apa pun</p>
                        </CardHeader>
                    </Card>
                </div>

                <!-- Kelengkapan Profil Warga -->
                <Card v-if="profileStatuses.length > 0">
                    <CardHeader class="cursor-pointer select-none hover:bg-slate-50/60 rounded-t-xl transition-colors" @click="profileOpen = !profileOpen">
                        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                            <div class="flex items-start gap-2">
                                <ChevronDown class="w-5 h-5 text-slate-400 mt-0.5 transition-transform shrink-0" :class="{ '-rotate-90': !profileOpen }" />
                                <div>
                                    <CardTitle class="flex items-center gap-2">
                                        <IdCard class="w-5 h-5 text-blue-500" />
                                        Detail Status per Rumah
                                    </CardTitle>
                                    <CardDescription class="mt-1">
                                        Filter rumah berdasarkan status kelengkapan. Klik rumah → buka Data Warga.
                                    </CardDescription>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 shrink-0">
                                <div class="text-right">
                                    <div class="text-2xl font-black text-blue-600 leading-none">{{ profileStats.percent }}%</div>
                                    <div class="text-[10px] uppercase tracking-wider text-slate-500 mt-0.5">Lengkap</div>
                                </div>
                                <div class="relative w-14 h-14">
                                    <svg class="w-full h-full -rotate-90" viewBox="0 0 36 36">
                                        <circle cx="18" cy="18" r="16" fill="none" stroke="#e2e8f0" stroke-width="3" />
                                        <circle cx="18" cy="18" r="16" fill="none" stroke="#3b82f6" stroke-width="3" stroke-linecap="round"
                                            :stroke-dasharray="`${profileStats.percent} 100`" pathLength="100" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent v-show="profileOpen">
                        <!-- Filter & search -->
                        <div class="flex flex-col sm:flex-row gap-3 mb-4">
                            <div class="flex gap-1.5 flex-wrap">
                                <button v-for="f in ['semua', 'belum', 'sebagian', 'lengkap']" :key="f"
                                    type="button"
                                    class="px-3 py-1.5 text-xs font-semibold rounded-full uppercase tracking-wider border transition-colors"
                                    :class="profileFilter === f ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'"
                                    @click="profileFilter = f"
                                >
                                    {{ f }}
                                </button>
                            </div>
                            <div class="relative flex-1 max-w-xs">
                                <Search class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400" />
                                <Input v-model="profileSearch" placeholder="Cari nomor rumah..." class="pl-8 h-9 text-sm" />
                            </div>
                        </div>

                        <!-- Grid status — 2 slot per rumah -->
                        <div v-if="filteredProfiles.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
                            <div v-for="p in filteredProfiles" :key="p.name"
                                class="rounded-lg border-2 overflow-hidden bg-white transition-colors"
                                :class="profileStatusBadge(p.status).borderCls"
                            >
                                <!-- Header rumah + overall badge -->
                                <div class="px-3 py-2 flex items-center justify-between border-b" :class="profileStatusBadge(p.status).headerCls">
                                    <span class="font-bold text-sm">{{ p.name }}</span>
                                    <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full" :class="profileStatusBadge(p.status).chipCls">
                                        {{ profileStatusBadge(p.status).label }}
                                    </span>
                                </div>
                                <!-- Body: 2 slots -->
                                <div class="divide-y divide-slate-100">
                                    <!-- Pemilik -->
                                    <div v-if="p.owner" class="px-3 py-2 flex items-center justify-between gap-2">
                                        <div class="flex items-center gap-1.5 min-w-0">
                                            <UserCircle class="w-4 h-4 text-emerald-600 shrink-0" />
                                            <span class="text-[10px] uppercase tracking-wider font-bold text-emerald-700">Pemilik</span>
                                        </div>
                                        <div class="flex gap-1.5">
                                            <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded" :class="p.owner.has_kk ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-400'">
                                                KK {{ p.owner.has_kk ? '✓' : '−' }}
                                            </span>
                                            <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded" :class="p.owner.has_ktp ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-400'">
                                                KTP {{ p.owner.ktp_count || '−' }}
                                            </span>
                                        </div>
                                    </div>
                                    <!-- Kontrak (kalau ada) -->
                                    <div v-if="p.tenant" class="px-3 py-2 flex items-center justify-between gap-2">
                                        <div class="flex items-center gap-1.5 min-w-0">
                                            <UserCircle class="w-4 h-4 text-amber-600 shrink-0" />
                                            <span class="text-[10px] uppercase tracking-wider font-bold text-amber-700">Kontrak</span>
                                        </div>
                                        <div class="flex gap-1.5">
                                            <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded" :class="p.tenant.has_kk ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-400'">
                                                KK {{ p.tenant.has_kk ? '✓' : '−' }}
                                            </span>
                                            <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded" :class="p.tenant.has_ktp ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-400'">
                                                KTP {{ p.tenant.ktp_count || '−' }}
                                            </span>
                                        </div>
                                    </div>
                                    <!-- No kontrak placeholder -->
                                    <div v-else class="px-3 py-1.5 text-center">
                                        <span class="text-[10px] text-slate-400 italic">Tidak ada kontrak</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-12 text-sm text-slate-400">
                            Tidak ada rumah yang cocok filter.
                        </div>
                    </CardContent>
                </Card>

                <div v-else class="text-center py-16">
                    <IdCard class="w-12 h-12 text-slate-300 mx-auto mb-3" />
                    <p class="text-sm text-slate-500">Belum ada data rumah dengan pemilik terdaftar.</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
