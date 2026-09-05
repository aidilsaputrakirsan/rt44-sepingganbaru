<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription
} from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';
import {
    Video, Wifi, Search, X, MapPin, AlertTriangle, Loader2, Navigation, ExternalLink
} from 'lucide-vue-next';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps({
    cctv: Array,
    wifi: Array,
    error: String,
    center: Object,
    radiusKm: Number,
});

// ── Tab & pencarian ─────────────────────────────────────
const activeTab = ref('cctv');
const searchQuery = ref('');

const activeList = computed(() => (activeTab.value === 'cctv' ? props.cctv : props.wifi));

const filteredList = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();
    if (!q) return activeList.value;
    return activeList.value.filter((p) =>
        p.nama.toLowerCase().includes(q) ||
        (p.kelurahan || '').toLowerCase().includes(q) ||
        (p.alamat || '').toLowerCase().includes(q)
    );
});

// ── Peta (Leaflet + OpenStreetMap, tanpa API key) ───────
let map = null;
let markerLayer = null;
const markerById = new Map();
const mapEl = ref(null);

// Marker pakai divIcon supaya tidak bergantung file gambar bawaan Leaflet
// (path-nya rusak kalau di-bundle Vite).
const makeIcon = (type) => {
    const color = type === 'cctv' ? '#dc2626' : '#0ea5e9';
    const glyph = type === 'cctv'
        ? '<path d="M2 6h11v6H2z" fill="#fff"/><path d="M13 8l4-2v6l-4-2z" fill="#fff"/>'
        : '<path d="M10 14.5a1.5 1.5 0 100 .01M6 11a6 6 0 018 0M3.5 8a10 10 0 0113 0" stroke="#fff" stroke-width="1.8" fill="none" stroke-linecap="round"/>';

    return L.divIcon({
        className: 'peta-marker',
        html: `<span style="background:${color}"><svg viewBox="0 0 20 20" width="15" height="15">${glyph}</svg></span>`,
        iconSize: [28, 28],
        iconAnchor: [14, 14],
        popupAnchor: [0, -14],
    });
};

const renderMarkers = () => {
    if (!map) return;
    markerLayer.clearLayers();
    markerById.clear();

    filteredList.value.forEach((p) => {
        const marker = L.marker([p.lat, p.lng], { icon: makeIcon(activeTab.value) })
            .bindTooltip(p.nama, { direction: 'top', offset: [0, -12] })
            .on('click', () => openDetail(p));
        markerLayer.addLayer(marker);
        markerById.set(p.id, marker);
    });

    const bounds = markerLayer.getBounds();
    if (bounds.isValid()) {
        map.fitBounds(bounds, { padding: [40, 40], maxZoom: 16 });
    }
};

onMounted(() => {
    map = L.map(mapEl.value, { scrollWheelZoom: true })
        .setView([props.center.lat, props.center.lng], 14);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap',
    }).addTo(map);

    // Penanda posisi RT-44 sebagai titik acuan jarak.
    L.circleMarker([props.center.lat, props.center.lng], {
        radius: 7, color: '#16a34a', fillColor: '#16a34a', fillOpacity: 0.9, weight: 2,
    }).addTo(map).bindTooltip('RT-44 Sepinggan Baru', { direction: 'top' });

    markerLayer = L.layerGroup().addTo(map);
    renderMarkers();
});

onBeforeUnmount(() => {
    stopStream();
    map?.remove();
    map = null;
});

watch([activeTab, filteredList], () => renderMarkers());

const focusPoint = (p) => {
    map?.setView([p.lat, p.lng], 17, { animate: true });
    markerById.get(p.id)?.openTooltip();
    openDetail(p);
};

// ── Detail & pemutar CCTV ───────────────────────────────
const isDetailOpen = ref(false);
const selected = ref(null);
const videoEl = ref(null);
const streamError = ref(null);
const isLoadingStream = ref(false);
let hls = null;
let startupTimer = null;

const openDetail = (p) => {
    selected.value = p;
    isDetailOpen.value = true;
    // Pemutaran dimulai lewat watcher videoEl di bawah — isi DialogContent baru
    // di-mount setelah dialog terbuka, jadi elemen <video> belum ada di sini.
    isLoadingStream.value = !!p.stream_url;
};

// Menunggu elemen <video> benar-benar ter-mount sebelum memasang stream.
// `selected` ikut dipantau agar ganti kamera saat dialog sudah terbuka tetap
// memuat ulang stream (elemen <video>-nya sendiri tidak berubah).
watch([videoEl, selected], ([el, point]) => {
    if (el && point?.stream_url) {
        playStream(point.stream_url);
    }
});

const stopStream = () => {
    if (startupTimer) {
        clearTimeout(startupTimer);
        startupTimer = null;
    }
    if (hls) {
        hls.destroy();
        hls = null;
    }
    if (videoEl.value) {
        videoEl.value.pause();
        videoEl.value.removeAttribute('src');
        videoEl.value.load();
    }
    isLoadingStream.value = false;
};

const playStream = async (url) => {
    stopStream();
    streamError.value = null;
    isLoadingStream.value = true;

    const video = videoEl.value;
    if (!video) return;

    // Jaring pengaman: apa pun penyebabnya, overlay "memuat" hilang begitu
    // frame pertama benar-benar tampil.
    video.addEventListener('playing', () => { isLoadingStream.value = false; }, { once: true });

    // Jangan biarkan spinner menggantung selamanya kalau kamera tidak merespons.
    startupTimer = setTimeout(() => {
        if (!isLoadingStream.value) return;
        isLoadingStream.value = false;
        streamError.value = 'Stream tidak merespons dalam 20 detik. Kamera kemungkinan sedang mati.';
        stopStream();
    }, 20000);

    // Safari/iOS memutar HLS secara native; browser lain butuh hls.js.
    if (video.canPlayType('application/vnd.apple.mpegurl')) {
        video.src = url;
        video.play().catch(() => {});
        return;
    }

    const { default: Hls } = await import('hls.js');

    if (!Hls.isSupported()) {
        streamError.value = 'Browser ini tidak mendukung pemutaran live stream (HLS).';
        isLoadingStream.value = false;
        return;
    }

    // Stream CCTV ini live dengan segmen 2 detik dan window pendek, jadi mulai
    // dari ujung terbaru dan jangan menumpuk buffer besar.
    hls = new Hls({
        liveSyncDurationCount: 2,
        backBufferLength: 30,
        maxBufferLength: 10,
    });
    hls.loadSource(url);
    hls.attachMedia(video);
    hls.on(Hls.Events.MANIFEST_PARSED, () => {
        video.play().catch(() => {});
    });
    hls.on(Hls.Events.ERROR, (_e, data) => {
        if (!data.fatal) return;
        isLoadingStream.value = false;
        streamError.value = 'Stream CCTV tidak dapat dimuat. Kamera mungkin sedang mati atau server Pemkot sedang gangguan.';
        stopStream();
    });
};

watch(isDetailOpen, (open) => {
    if (!open) {
        stopStream();
        streamError.value = null;
    }
});

const gmapsUrl = (p) => `https://www.google.com/maps/search/?api=1&query=${p.lat},${p.lng}`;
</script>

<template>
    <Head title="Peta Kota" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Peta Kota — CCTV & Wifi Gratis</h2>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
                <p class="text-sm text-gray-500">
                    Data langsung dari layanan publik
                    <a href="https://pwa-emanuntung.balikpapan.go.id" target="_blank" rel="noopener"
                       class="font-medium text-emerald-600 hover:underline">eManuntung</a>
                    Pemerintah Kota Balikpapan, dalam radius {{ radiusKm }} km dari RT-44.
                </p>

                <div v-if="error"
                     class="flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                    <AlertTriangle class="mt-0.5 h-4 w-4 shrink-0" />
                    <span>{{ error }}</span>
                </div>

                <!-- Tab kategori -->
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        @click="activeTab = 'cctv'"
                        class="inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-sm font-medium transition"
                        :class="activeTab === 'cctv'
                            ? 'border-red-200 bg-red-50 text-red-700'
                            : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50'">
                        <Video class="h-4 w-4" />
                        CCTV
                        <span class="rounded-full bg-white/70 px-1.5 text-xs">{{ cctv.length }}</span>
                    </button>
                    <button
                        type="button"
                        @click="activeTab = 'wifi'"
                        class="inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-sm font-medium transition"
                        :class="activeTab === 'wifi'
                            ? 'border-sky-200 bg-sky-50 text-sky-700'
                            : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50'">
                        <Wifi class="h-4 w-4" />
                        Wifi Gratis
                        <span class="rounded-full bg-white/70 px-1.5 text-xs">{{ wifi.length }}</span>
                    </button>

                    <div class="relative ml-auto w-full sm:w-64">
                        <Search class="absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                        <Input v-model="searchQuery" placeholder="Cari lokasi..." class="pl-8 pr-8" />
                        <button v-if="searchQuery" type="button" @click="searchQuery = ''"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <X class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <div class="grid gap-4 lg:grid-cols-3">
                    <!-- Peta -->
                    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm lg:col-span-2">
                        <div ref="mapEl" class="h-[420px] w-full lg:h-[560px]"></div>
                    </div>

                    <!-- Daftar lokasi -->
                    <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                        <div class="border-b border-gray-100 px-4 py-3 text-sm font-semibold text-gray-700">
                            {{ filteredList.length }} lokasi ditemukan
                        </div>
                        <div class="max-h-[420px] divide-y divide-gray-100 overflow-y-auto lg:max-h-[510px]">
                            <button
                                v-for="p in filteredList"
                                :key="p.id"
                                type="button"
                                @click="focusPoint(p)"
                                class="flex w-full items-start gap-3 px-4 py-3 text-left transition hover:bg-gray-50">
                                <span class="mt-0.5 rounded-md p-1.5"
                                      :class="activeTab === 'cctv' ? 'bg-red-50 text-red-600' : 'bg-sky-50 text-sky-600'">
                                    <Video v-if="activeTab === 'cctv'" class="h-4 w-4" />
                                    <Wifi v-else class="h-4 w-4" />
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-medium text-gray-800">{{ p.nama }}</span>
                                    <span class="block truncate text-xs text-gray-500">
                                        {{ p.kelurahan || p.kecamatan }} &middot; {{ p.distance }} km
                                    </span>
                                </span>
                            </button>

                            <div v-if="!filteredList.length" class="px-4 py-10 text-center text-sm text-gray-500">
                                Tidak ada lokasi yang cocok.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal detail / live stream -->
        <Dialog v-model:open="isDetailOpen">
            <DialogContent class="sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <Video v-if="selected?.stream_url" class="h-5 w-5 text-red-600" />
                        <Wifi v-else class="h-5 w-5 text-sky-600" />
                        {{ selected?.nama }}
                    </DialogTitle>
                    <DialogDescription>
                        {{ selected?.kelurahan }}<template v-if="selected?.kecamatan">, {{ selected.kecamatan }}</template>
                        &middot; {{ selected?.distance }} km dari RT-44
                    </DialogDescription>
                </DialogHeader>

                <!-- Live stream CCTV -->
                <div v-if="selected?.stream_url" class="space-y-2">
                    <div class="relative overflow-hidden rounded-lg bg-black">
                        <video ref="videoEl" class="aspect-video w-full" controls autoplay muted playsinline></video>

                        <div v-if="isLoadingStream"
                             class="absolute inset-0 flex items-center justify-center gap-2 bg-black/60 text-sm text-white">
                            <Loader2 class="h-4 w-4 animate-spin" />
                            Memuat live stream...
                        </div>
                    </div>

                    <div v-if="streamError"
                         class="flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                        <AlertTriangle class="mt-0.5 h-4 w-4 shrink-0" />
                        <span>{{ streamError }}</span>
                    </div>
                </div>

                <!-- Info titik wifi -->
                <div v-else class="rounded-lg border border-sky-100 bg-sky-50 p-4 text-sm text-sky-900">
                    <div class="flex items-start gap-2">
                        <MapPin class="mt-0.5 h-4 w-4 shrink-0" />
                        <div>
                            <p class="font-medium">Titik Wifi Gratis Pemkot Balikpapan</p>
                            <p class="mt-1 text-sky-800">{{ selected?.alamat || 'Alamat tidak tersedia.' }}</p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between gap-2 pt-1">
                    <span class="text-xs text-gray-400">
                        {{ selected?.lat?.toFixed(5) }}, {{ selected?.lng?.toFixed(5) }}
                    </span>
                    <Button as="a" :href="selected ? gmapsUrl(selected) : '#'" target="_blank" rel="noopener"
                            variant="outline" size="sm" class="gap-1.5">
                        <Navigation class="h-3.5 w-3.5" />
                        Buka di Google Maps
                        <ExternalLink class="h-3 w-3" />
                    </Button>
                </div>
            </DialogContent>
        </Dialog>
    </AuthenticatedLayout>
</template>

<style>
/* Marker bulat custom — dipakai lewat L.divIcon di atas. */
.peta-marker span {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border: 2px solid #fff;
    border-radius: 9999px;
    box-shadow: 0 1px 4px rgb(0 0 0 / 0.35);
}

/* Leaflet memasang z-index tinggi yang menutupi modal Radix. */
.leaflet-pane,
.leaflet-top,
.leaflet-bottom {
    z-index: 10;
}
</style>
