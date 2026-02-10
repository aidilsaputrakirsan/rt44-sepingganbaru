<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { Home, LogIn, UserPlus, Shield, CalendarDays, Receipt } from 'lucide-vue-next';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});
</script>

<template>
    <Head title="Selamat Datang" />

    <div class="min-h-screen bg-gradient-to-br from-emerald-50 via-white to-teal-50 dark:from-gray-950 dark:via-gray-900 dark:to-emerald-950 flex flex-col">
        <!-- Header -->
        <header class="w-full px-6 py-4 flex items-center justify-between max-w-5xl mx-auto">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-600 flex items-center justify-center shadow-lg shadow-emerald-200 dark:shadow-emerald-900/30">
                    <Home class="w-5 h-5 text-white" />
                </div>
                <span class="text-lg font-bold text-gray-900 dark:text-white">RT 44 <span class="font-normal text-emerald-600">Sepingan Baru</span></span>
            </div>

            <nav v-if="canLogin" class="flex items-center gap-2">
                <Link
                    v-if="$page.props.auth.user"
                    :href="route('dashboard')"
                    class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold bg-emerald-600 text-white hover:bg-emerald-700 transition shadow-md shadow-emerald-200 dark:shadow-emerald-900/40"
                >
                    Dashboard
                </Link>

                <template v-else>
                    <Link
                        :href="route('login')"
                        class="inline-flex items-center gap-1.5 rounded-lg px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800 transition"
                    >
                        <LogIn class="w-4 h-4" />
                        Masuk
                    </Link>

                    <Link
                        v-if="canRegister"
                        :href="route('register')"
                        class="inline-flex items-center gap-1.5 rounded-lg px-4 py-2 text-sm font-semibold bg-emerald-600 text-white hover:bg-emerald-700 transition shadow-md shadow-emerald-200 dark:shadow-emerald-900/40"
                    >
                        <UserPlus class="w-4 h-4" />
                        Daftar
                    </Link>
                </template>
            </nav>
        </header>

        <!-- Hero Section -->
        <main class="flex-1 flex items-center justify-center px-6">
            <div class="max-w-2xl text-center space-y-8">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 text-xs font-semibold tracking-wide uppercase">
                    <Shield class="w-3.5 h-3.5" />
                    Sistem Informasi Warga
                </div>

                <h1 class="text-4xl sm:text-5xl font-extrabold text-gray-900 dark:text-white leading-tight">
                    Kelola Iuran RT
                    <span class="text-emerald-600 dark:text-emerald-400">Lebih Mudah</span>
                </h1>

                <p class="text-lg text-gray-500 dark:text-gray-400 leading-relaxed max-w-lg mx-auto">
                    Pantau pembayaran iuran bulanan, lihat riwayat transaksi, dan kelola keuangan RT 44 Sepingan Baru secara digital.
                </p>

                <!-- Feature Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-4">
                    <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white/70 dark:bg-gray-900/50 backdrop-blur p-5 space-y-2 text-left">
                        <CalendarDays class="w-6 h-6 text-emerald-600" />
                        <h3 class="font-semibold text-sm text-gray-900 dark:text-white">Kalender Iuran</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Lihat status pembayaran per bulan dalam satu tampilan.</p>
                    </div>
                    <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white/70 dark:bg-gray-900/50 backdrop-blur p-5 space-y-2 text-left">
                        <Receipt class="w-6 h-6 text-emerald-600" />
                        <h3 class="font-semibold text-sm text-gray-900 dark:text-white">Laporan Keuangan</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Transparansi pemasukan & pengeluaran kas RT.</p>
                    </div>
                    <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white/70 dark:bg-gray-900/50 backdrop-blur p-5 space-y-2 text-left">
                        <Shield class="w-6 h-6 text-emerald-600" />
                        <h3 class="font-semibold text-sm text-gray-900 dark:text-white">Aman & Terpercaya</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Data tersimpan aman dengan sistem autentikasi.</p>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="py-6 text-center text-xs text-gray-400 dark:text-gray-600">
            © {{ new Date().getFullYear() }} RT 44 Sepingan Baru. Hak cipta dilindungi.
        </footer>
    </div>
</template>
