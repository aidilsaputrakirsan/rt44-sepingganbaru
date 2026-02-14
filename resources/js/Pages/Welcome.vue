<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { LogIn, Shield, CalendarDays, Receipt } from 'lucide-vue-next';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});
</script>

<template>
    <Head title="Selamat Datang" />

    <div class="min-h-screen bg-gradient-to-br from-amber-50/60 via-white to-orange-50/40 flex flex-col">
        <!-- Header -->
        <header class="w-full px-6 py-4 flex items-center justify-between max-w-5xl mx-auto">
            <div class="flex items-center gap-3">
                <img src="/logort.png" alt="Logo RT-44" class="w-10 h-10 rounded-lg shadow-sm" />
                <span class="text-lg font-bold text-slate-800">RT-44 <span class="font-normal text-amber-600">Sepinggan Baru</span></span>
            </div>

            <nav v-if="canLogin" class="flex items-center gap-2">
                <Link
                    v-if="$page.props.auth.user"
                    :href="route('dashboard')"
                    class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold bg-amber-500 text-white hover:bg-amber-600 transition shadow-md shadow-amber-500/20"
                >
                    Dashboard
                </Link>

                <template v-else>
                    <Link
                        :href="route('login')"
                        class="inline-flex items-center gap-1.5 rounded-lg px-4 py-2 text-sm font-semibold bg-amber-500 text-white hover:bg-amber-600 transition shadow-md shadow-amber-500/20"
                    >
                        <LogIn class="w-4 h-4" />
                        Masuk
                    </Link>
                </template>
            </nav>
        </header>

        <!-- Hero Section -->
        <main class="flex-1 flex items-center justify-center px-6">
            <div class="max-w-2xl text-center space-y-8">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-amber-100 text-amber-700 text-xs font-semibold tracking-wide uppercase">
                    <Shield class="w-3.5 h-3.5" />
                    Sistem Informasi Warga
                </div>

                <h1 class="text-4xl sm:text-5xl font-extrabold text-slate-800 leading-tight">
                    Kelola Iuran RT
                    <span class="text-amber-500">Lebih Mudah</span>
                </h1>

                <p class="text-lg text-slate-500 leading-relaxed max-w-lg mx-auto">
                    Pantau pembayaran iuran bulanan, lihat riwayat transaksi, dan kelola keuangan RT-44 Sepinggan Baru secara digital.
                </p>

                <!-- Feature Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-4">
                    <div class="rounded-xl border border-amber-100 bg-white/80 backdrop-blur p-5 space-y-2 text-left shadow-sm">
                        <CalendarDays class="w-6 h-6 text-amber-500" />
                        <h3 class="font-semibold text-sm text-slate-800">Kalender Iuran</h3>
                        <p class="text-xs text-slate-500">Lihat status pembayaran per bulan dalam satu tampilan.</p>
                    </div>
                    <div class="rounded-xl border border-amber-100 bg-white/80 backdrop-blur p-5 space-y-2 text-left shadow-sm">
                        <Receipt class="w-6 h-6 text-amber-500" />
                        <h3 class="font-semibold text-sm text-slate-800">Laporan Keuangan</h3>
                        <p class="text-xs text-slate-500">Transparansi pemasukan & pengeluaran kas RT.</p>
                    </div>
                    <div class="rounded-xl border border-amber-100 bg-white/80 backdrop-blur p-5 space-y-2 text-left shadow-sm">
                        <Shield class="w-6 h-6 text-amber-500" />
                        <h3 class="font-semibold text-sm text-slate-800">Aman & Terpercaya</h3>
                        <p class="text-xs text-slate-500">Data tersimpan aman dengan sistem autentikasi.</p>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="py-6 text-center text-xs text-slate-400">
            &copy; {{ new Date().getFullYear() }} RT-44 Sepinggan Baru, Balikpapan
        </footer>
    </div>
</template>
