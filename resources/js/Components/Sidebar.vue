<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { LayoutDashboard, Users, Receipt, Calendar, Home, LogOut, UserCircle, PieChart, IdCard } from 'lucide-vue-next';

const props = defineProps({
    mobile: {
        type: Boolean,
        default: false,
    },
});

const user = usePage().props.auth.user;

const isAdmin = computed(() => user.role === 'admin' || user.role === 'demo');
const isKetua = computed(() => user.role === 'ketua');
const isDemo = computed(() => user.role === 'demo');

const roleLabel = computed(() => {
    if (user.role === 'admin') return 'Bendahara';
    if (user.role === 'ketua') return 'Ketua';
    if (user.role === 'demo') return 'Demo';
    return null;
});

const menuItems = computed(() => {
    if (isAdmin.value) {
        return [
            { name: 'Dashboard', icon: LayoutDashboard, route: 'admin.dashboard' },
            { name: 'Data Warga', icon: UserCircle, route: 'admin.warga.index' },
            { name: 'Tagihan Per Rumah', icon: Receipt, route: 'admin.tagihan' },
            { name: 'Kalender Iuran', icon: Calendar, route: 'admin.calendar' },
            { name: 'Pengeluaran', icon: Receipt, route: 'admin.expenses.index' },
            { name: 'Laporan Keuangan', icon: PieChart, route: 'admin.report.index' },
        ];
    } else if (isKetua.value) {
        return [
            { name: 'Dashboard', icon: LayoutDashboard, route: 'admin.dashboard' },
            { name: 'Data Warga', icon: UserCircle, route: 'admin.warga.index' },
        ];
    } else {
        return [
            { name: 'Dashboard', icon: Home, route: 'dashboard' },
            { name: 'Kalender Iuran', icon: Calendar, route: 'dashboard.calendar' },
            // Profil Warga di-hide dari sidebar warga — admin yang manage CRUD via Data Warga.
            // Route tetap aktif (profil.show) untuk akses langsung jika dibutuhkan.
        ];
    }
});
</script>

<template>
    <div
        class="bg-[hsl(var(--sidebar-bg))] flex flex-col h-full"
        :class="{
            'h-screen w-64 border-r border-[hsl(var(--sidebar-border))]': !mobile,
            'w-full': mobile
        }"
    >
        <!-- Logo + Brand -->
        <div class="p-5 flex items-center gap-3 border-b border-[hsl(var(--sidebar-border))]">
            <img src="/logort.png" alt="Logo RT-44" class="w-10 h-10 rounded-lg" />
            <div>
                <h1 class="text-base font-bold text-white leading-tight">RT-44</h1>
                <p class="text-[10px] text-slate-400 leading-tight">Sepinggan Baru</p>
            </div>
        </div>

        <nav class="flex-1 px-3 py-5 space-y-1">
            <Link
                v-for="item in menuItems"
                :key="item.name"
                :href="route(item.route)"
                class="flex items-center px-3 py-2.5 text-sm rounded-lg transition-colors"
                :class="route().current(item.route)
                    ? 'bg-amber-500 text-white font-semibold shadow-md shadow-amber-500/20'
                    : 'text-slate-300 hover:bg-white/5 hover:text-white'"
            >
                <component :is="item.icon" class="w-5 h-5 mr-3 shrink-0" />
                {{ item.name }}
            </Link>
        </nav>

        <div class="p-3 border-t border-[hsl(var(--sidebar-border))]">
            <div v-if="isDemo" class="mb-3 mx-1 px-3 py-2 rounded-lg bg-amber-500/20 border border-amber-500/30 text-center">
                <p class="text-xs font-bold text-amber-400 uppercase tracking-wider">Mode Demo</p>
                <p class="text-[10px] text-amber-300/80 mt-0.5">Semua fitur terlihat, data tidak bisa diubah</p>
            </div>
            <div class="flex items-center mb-3 px-3">
                <div class="w-8 h-8 rounded-full bg-amber-500/20 flex items-center justify-center shrink-0">
                    <UserCircle class="w-5 h-5 text-amber-400" />
                </div>
                <div class="ml-2 min-w-0">
                    <div class="flex items-center gap-1.5">
                        <p class="text-sm font-medium text-white truncate">{{ user.name }}</p>
                        <span v-if="roleLabel" class="text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded"
                              :class="user.role === 'admin' ? 'bg-emerald-500/20 text-emerald-300' : (user.role === 'ketua' ? 'bg-blue-500/20 text-blue-300' : 'bg-amber-500/20 text-amber-300')">
                            {{ roleLabel }}
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-400 truncate">{{ user.no_rumah || user.email }}</p>
                </div>
            </div>
            <Link
                :href="route('logout')"
                method="post"
                as="button"
                class="w-full flex items-center px-3 py-2 text-sm text-red-400 hover:bg-red-500/10 rounded-lg transition-colors"
            >
                <LogOut class="w-4 h-4 mr-3" />
                Logout
            </Link>
        </div>
    </div>
</template>
