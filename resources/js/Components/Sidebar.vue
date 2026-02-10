<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { LayoutDashboard, Users, Receipt, Calendar, Home, LogOut, UserCircle, PieChart } from 'lucide-vue-next';

const props = defineProps({
    mobile: {
        type: Boolean,
        default: false,
    },
});

const user = usePage().props.auth.user;

const isAdmin = computed(() => user.role === 'admin');

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
    } else {
        return [
            { name: 'Dashboard', icon: Home, route: 'dashboard' },
            { name: 'Kalender Iuran', icon: Calendar, route: 'dashboard.calendar' },
        ];
    }
});
</script>

<template>
    <div 
        class="bg-white dark:bg-gray-800 flex flex-col h-full"
        :class="{
            'h-screen w-64 border-r border-gray-200 dark:border-gray-700': !mobile,
            'w-full': mobile
        }"
    >
        <div class="p-6 flex items-center justify-center border-b border-gray-200 dark:border-gray-700">
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">RT-44</h1>
        </div>

        <nav class="flex-1 px-4 py-6 space-y-2">
            <Link
                v-for="item in menuItems"
                :key="item.name"
                :href="route(item.route)"
                class="flex items-center px-4 py-3 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                :class="{ 'bg-gray-100 dark:bg-gray-700 font-bold': route().current(item.route) }"
            >
                <component :is="item.icon" class="w-5 h-5 mr-3" />
                {{ item.name }}
            </Link>
        </nav>

        <div class="p-4 border-t border-gray-200 dark:border-gray-700">
            <div class="flex items-center mb-4 px-4">
                <div class="ml-2">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ user.name }}</p>
                    <p class="text-xs text-gray-500">{{ user.no_rumah || user.email }}</p>
                </div>
            </div>
            <Link
                :href="route('logout')"
                method="post"
                as="button"
                class="w-full flex items-center px-4 py-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
            >
                <LogOut class="w-5 h-5 mr-3" />
                Logout
            </Link>
        </div>
    </div>
</template>
