<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { LogIn, Shield, CalendarDays, Receipt, Eye } from 'lucide-vue-next';
import { onMounted, onUnmounted } from 'vue';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});

const total = 350;
const orbSize = 130;
const orbSizeMobile = 70;
const duration = 14;
const baseHue = 180;

const seededRandom = (seed) => {
    const x = Math.sin(seed) * 10000;
    return x - Math.floor(x);
};

const particles = [];
for (let i = 0; i < total; i++) {
    particles.push({
        id: i,
        hue: (40 / total) * i + baseHue,
        delay: i * 0.01,
    });
}

const particleStyle = (p) => ({
    animationName: `orbit-${p.id}`,
    animationDuration: `${duration}s`,
    animationIterationCount: 'infinite',
    animationDelay: `${p.delay}s`,
    backgroundColor: `hsla(${p.hue}, 80%, 45%, 1)`,
});

onMounted(() => {
    if (document.getElementById('orb-keyframes')) return;

    const isMobile = window.innerWidth < 640;
    const size = isMobile ? orbSizeMobile : orbSize;

    let css = '';
    for (let i = 0; i < total; i++) {
        const z = seededRandom(i * 7 + 1) * 360;
        const y = seededRandom(i * 13 + 5) * 360;
        css += `
@keyframes orbit-${i} {
  20% { opacity: 1; }
  30% { transform: rotateZ(-${z}deg) rotateY(${y}deg) translateX(${size}px) rotateZ(${z}deg); }
  80% { transform: rotateZ(-${z}deg) rotateY(${y}deg) translateX(${size}px) rotateZ(${z}deg); opacity: 1; }
  100% { transform: rotateZ(-${z}deg) rotateY(${y}deg) translateX(${size * 3}px) rotateZ(${z}deg); }
}`;
    }

    const style = document.createElement('style');
    style.id = 'orb-keyframes';
    style.textContent = css;
    document.head.appendChild(style);
});

onUnmounted(() => {
    document.getElementById('orb-keyframes')?.remove();
});
</script>

<template>
    <Head title="Selamat Datang" />

    <div class="min-h-screen flex flex-col bg-gradient-to-br from-amber-50/60 via-white to-orange-50/40">
        <!-- Header -->
        <header class="relative z-20 w-full px-6 py-4 flex items-center justify-between max-w-5xl mx-auto">
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
                        :href="route('demo')"
                        class="inline-flex items-center gap-1.5 rounded-lg px-4 py-2 text-sm font-semibold bg-slate-700 text-white hover:bg-slate-800 transition shadow-md shadow-slate-500/20"
                    >
                        <Eye class="w-4 h-4" />
                        Lihat Demo
                    </Link>
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
        <main class="relative flex-1 flex items-center justify-center overflow-hidden px-6">
            <!-- Particle Orb -->
            <div class="orb-wrap">
                <div
                    v-for="p in particles"
                    :key="p.id"
                    class="orb-particle"
                    :style="particleStyle(p)"
                />
            </div>

            <!-- Content -->
            <div class="relative z-10 max-w-2xl text-center space-y-6 sm:space-y-8">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-amber-100 text-amber-700 text-xs font-semibold tracking-wide uppercase">
                    <Shield class="w-3.5 h-3.5" />
                    Sistem Informasi Warga
                </div>

                <h1 class="text-3xl sm:text-4xl md:text-5xl font-extrabold text-slate-800 leading-tight">
                    Kelola Iuran RT
                    <span class="text-amber-500">Lebih Mudah</span>
                </h1>

                <p class="text-base sm:text-lg text-slate-500 leading-relaxed max-w-lg mx-auto">
                    Pantau pembayaran iuran bulanan, lihat riwayat transaksi, dan kelola keuangan RT-44 Sepinggan Baru secara digital.
                </p>

                <!-- Feature Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4 pt-4">
                    <div class="rounded-xl border border-amber-100 bg-white/80 backdrop-blur p-4 sm:p-5 space-y-2 text-left shadow-sm">
                        <CalendarDays class="w-6 h-6 text-amber-500" />
                        <h3 class="font-semibold text-sm text-slate-800">Kalender Iuran</h3>
                        <p class="text-xs text-slate-500">Lihat status pembayaran per bulan dalam satu tampilan.</p>
                    </div>
                    <div class="rounded-xl border border-amber-100 bg-white/80 backdrop-blur p-4 sm:p-5 space-y-2 text-left shadow-sm">
                        <Receipt class="w-6 h-6 text-amber-500" />
                        <h3 class="font-semibold text-sm text-slate-800">Laporan Keuangan</h3>
                        <p class="text-xs text-slate-500">Transparansi pemasukan & pengeluaran kas RT.</p>
                    </div>
                    <div class="rounded-xl border border-amber-100 bg-white/80 backdrop-blur p-4 sm:p-5 space-y-2 text-left shadow-sm">
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

<style scoped>
.orb-wrap {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    transform-style: preserve-3d;
    perspective: 1000px;
    animation: orb-rotate 14s infinite linear;
}

@keyframes orb-rotate {
    100% {
        transform: rotateY(360deg) rotateX(360deg);
    }
}

.orb-particle {
    position: absolute;
    width: 3px;
    height: 3px;
    border-radius: 50%;
    opacity: 0;
}

@media (min-width: 640px) {
    .orb-particle {
        width: 3px;
        height: 3px;
    }
}
</style>
