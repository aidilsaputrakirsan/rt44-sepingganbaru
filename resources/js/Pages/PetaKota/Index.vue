<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch, nextTick } from 'vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CctvPlayer from '@/Components/CctvPlayer.vue';
import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';
import {
    Video, Wifi, Search, X, MapPin, AlertTriangle, Navigation, ExternalLink, MousePointerClick
} from 'lucide-vue-next';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps({
    cctv: Array,
    wifi: Array,
    error: String,
    center: Object,
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

// ── Detail terpilih ─────────────────────────────────────
const selected = ref(null);
const detailEl = ref(null);

const openDetail = (p) => {
    selected.value = p;
    nextTick(() => detailEl.value?.scrollIntoView({ behavior: 'smooth', block: 'nearest' }));
};

const gmapsUrl = (p) => `https://www.google.com/maps/search/?api=1&query=${p.lat},${p.lng}`;

// ── Peta (Leaflet + OpenStreetMap, tanpa API key) ───────
let map = null;
let markerLayer = null;
const markerById = new Map();
const mapEl = ref(null);

// Marker pakai divIcon supaya tidak bergantung file gambar bawaan Leaflet
// (path-nya rusak kalau di-bundle Vite).
const makeIcon = (type, isSelected = false) => {
    const color = type === 'cctv' ? '#dc2626' : '#0ea5e9';
    const glyph = type === 'cctv'
        ? '<path d="M2 6h11v6H2z" fill="#fff"/><path d="M13 8l4-2v6l-4-2z" fill="#fff"/>'
        : '<path d="M10 14.5a1.5 1.5 0 100 .01M6 11a6 6 0 018 0M3.5 8a10 10 0 0113 0" stroke="#fff" stroke-width="1.8" fill="none" stroke-linecap="round"/>';

    return L.divIcon({
        className: `peta-marker${isSelected ? ' is-selected' : ''}`,
        html: `<span style="background:${color}"><svg viewBox="0 0 20 20" width="15" height="15">${glyph}</svg></span>`,
        iconSize: [28, 28],
        iconAnchor: [14, 14],
        popupAnchor: [0, -14],
    });
};

// `fit` sengaja opsional: saat hanya menandai marker terpilih, peta tidak
// boleh ikut zoom-out ke seluruh sebaran.
const renderMarkers = (fit = true) => {
    if (!map) return;
    markerLayer.clearLayers();
    markerById.clear();

    filteredList.value.forEach((p) => {
        const marker = L.marker([p.lat, p.lng], {
            icon: makeIcon(activeTab.value, selected.value?.id === p.id),
        })
            .bindTooltip(p.nama, { direction: 'top', offset: [0, -12] })
            .on('click', () => openDetail(p));
        markerLayer.addLayer(marker);
        markerById.set(p.id, marker);
    });

    if (!fit) return;

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

    // featureGroup, bukan layerGroup: hanya featureGroup yang punya getBounds().
    markerLayer = L.featureGroup().addTo(map);
    renderMarkers();
});

onBeforeUnmount(() => {
    map?.remove();
    map = null;
});

// Ganti tab / hasil pencarian → susun ulang marker dan sesuaikan zoom.
watch([activeTab, filteredList], () => {
    selected.value = null;
    renderMarkers();
});

// Ganti pilihan → cukup perbarui tampilan marker, jangan ubah zoom.
watch(selected, () => renderMarkers(false));

const focusPoint = (p) => {
    map?.setView([p.lat, p.lng], 17, { animate: true });
    openDetail(p);
};
</script>

<template>
    <Head title="Peta Kota" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Peta Kota — CCTV &amp; Wifi Gratis</h2>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
                <div v-if="error"
                     class="flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                    <AlertTriangle class="mt-0.5 h-4 w-4 shrink-0" />
                    <span>{{ error }}</span>
                </div>

                <!-- Tab kategori + pencarian -->
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

                <div class="grid items-start gap-4 lg:grid-cols-3">
                    <!-- Kolom kiri: detail terpilih (kalau ada) lalu peta -->
                    <div class="space-y-4 lg:col-span-2">
                        <!-- Player sengaja INLINE, bukan di dalam Dialog: Dialog memakai
                             DialogPortal (Teleport ke <body>) yang memindahkan elemen
                             <video> di DOM setelah mount. -->
                        <div v-if="selected" ref="detailEl"
                             class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                            <div class="flex items-start gap-3 border-b border-gray-100 px-4 py-3">
                                <span class="mt-0.5 rounded-md p-1.5"
                                      :class="selected.stream_url ? 'bg-red-50 text-red-600' : 'bg-sky-50 text-sky-600'">
                                    <Video v-if="selected.stream_url" class="h-4 w-4" />
                                    <Wifi v-else class="h-4 w-4" />
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <h3 class="truncate text-base font-semibold text-gray-800">{{ selected.nama }}</h3>
                                        <span v-if="selected.stream_url"
                                              class="inline-flex shrink-0 items-center gap-1 rounded-full bg-red-50 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-red-600">
                                            <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-red-500"></span>
                                            Live
                                        </span>
                                    </div>
                                    <p class="truncate text-sm text-gray-500">
                                        {{ selected.kelurahan }}<template v-if="selected.kecamatan">, {{ selected.kecamatan }}</template>
                                        &middot; {{ selected.distance }} km dari RT-44
                                    </p>
                                </div>
                                <button type="button" @click="selected = null" title="Tutup"
                                        class="rounded-md p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600">
                                    <X class="h-5 w-5" />
                                </button>
                            </div>

                            <div class="p-4">
                                <CctvPlayer v-if="selected.stream_url" :key="selected.id" :src="selected.stream_url" />

                                <div v-else class="rounded-lg border border-sky-100 bg-sky-50 p-4 text-sm text-sky-900">
                                    <div class="flex items-start gap-2">
                                        <MapPin class="mt-0.5 h-4 w-4 shrink-0" />
                                        <div>
                                            <p class="font-medium">Titik Wifi Gratis Pemkot Balikpapan</p>
                                            <p class="mt-1 text-sky-800">{{ selected.alamat || 'Alamat tidak tersedia.' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center justify-between gap-2 border-t border-gray-100 bg-gray-50/60 px-4 py-2.5">
                                <span class="font-mono text-xs text-gray-400">
                                    {{ selected.lat?.toFixed(5) }}, {{ selected.lng?.toFixed(5) }}
                                </span>
                                <Button as="a" :href="gmapsUrl(selected)" target="_blank" rel="noopener"
                                        variant="outline" size="sm" class="gap-1.5">
                                    <Navigation class="h-3.5 w-3.5" />
                                    Buka di Google Maps
                                    <ExternalLink class="h-3 w-3" />
                                </Button>
                            </div>
                        </div>

                        <!-- Ajakan saat belum ada yang dipilih -->
                        <div v-else
                             class="flex items-center gap-2 rounded-xl border border-dashed border-gray-300 bg-white px-4 py-3 text-sm text-gray-500">
                            <MousePointerClick class="h-4 w-4 shrink-0 text-gray-400" />
                            <span v-if="activeTab === 'cctv'">Klik marker atau daftar di samping untuk menonton siaran CCTV.</span>
                            <span v-else>Klik marker atau daftar di samping untuk melihat detail titik wifi.</span>
                        </div>

                        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                            <div ref="mapEl" class="h-[380px] w-full lg:h-[480px]"></div>
                        </div>
                    </div>

                    <!-- Kolom kanan: daftar lokasi -->
                    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm lg:sticky lg:top-4">
                        <div class="border-b border-gray-100 px-4 py-3 text-sm font-semibold text-gray-700">
                            {{ filteredList.length }} lokasi ditemukan
                        </div>
                        <div class="max-h-[420px] divide-y divide-gray-100 overflow-y-auto lg:max-h-[640px]">
                            <button
                                v-for="p in filteredList"
                                :key="p.id"
                                type="button"
                                @click="focusPoint(p)"
                                class="flex w-full items-start gap-3 px-4 py-3 text-left transition"
                                :class="selected?.id === p.id
                                    ? (activeTab === 'cctv' ? 'bg-red-50/70' : 'bg-sky-50/70')
                                    : 'hover:bg-gray-50'">
                                <span class="mt-0.5 rounded-md p-1.5"
                                      :class="activeTab === 'cctv' ? 'bg-red-50 text-red-600' : 'bg-sky-50 text-sky-600'">
                                    <Video v-if="activeTab === 'cctv'" class="h-4 w-4" />
                                    <Wifi v-else class="h-4 w-4" />
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-medium"
                                          :class="selected?.id === p.id ? 'text-gray-900' : 'text-gray-800'">
                                        {{ p.nama }}
                                    </span>
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
    transition: transform 120ms ease;
}

/* Marker yang sedang dipilih dibuat menonjol. */
.peta-marker.is-selected span {
    transform: scale(1.3);
    border-color: #111827;
    box-shadow: 0 0 0 4px rgb(17 24 39 / 0.15), 0 2px 6px rgb(0 0 0 / 0.4);
}

/* Leaflet memasang z-index tinggi yang bisa menutupi elemen di atasnya. */
.leaflet-pane,
.leaflet-top,
.leaflet-bottom {
    z-index: 10;
}
</style>
