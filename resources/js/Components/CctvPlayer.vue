<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick } from 'vue';
import { AlertTriangle, Loader2 } from 'lucide-vue-next';

const props = defineProps({
    src: { type: String, required: true },
});

const videoEl = ref(null);
const isLoading = ref(true);
const errorMessage = ref(null);
let hls = null;
let startupTimer = null;

const teardown = () => {
    if (startupTimer) {
        clearTimeout(startupTimer);
        startupTimer = null;
    }
    if (hls) {
        hls.destroy();
        hls = null;
    }
};

// Parent me-render komponen ini inline (bukan di dalam Dialog) dan memberinya
// :key per kamera, jadi elemen <video> dijamin ada saat onMounted berjalan.
onMounted(async () => {
    const video = videoEl.value;

    // Beri satu frame agar tata letak benar-benar selesai sebelum MediaSource
    // dipasang; memindahkan/mereflow elemen <video> setelah attach bisa mereset
    // pipeline media-nya tanpa melempar error.
    await nextTick();
    await new Promise((resolve) => requestAnimationFrame(() => requestAnimationFrame(resolve)));

    // Panel bisa saja sudah ditutup selama penundaan singkat di atas.
    if (!videoEl.value) return;

    video.addEventListener('playing', () => { isLoading.value = false; }, { once: true });

    startupTimer = setTimeout(() => {
        if (!isLoading.value) return;
        isLoading.value = false;
        errorMessage.value = 'Stream tidak merespons dalam 20 detik. Kamera kemungkinan sedang mati.';
        teardown();
    }, 20000);

    // Safari/iOS memutar HLS secara native.
    if (video.canPlayType('application/vnd.apple.mpegurl')) {
        video.src = props.src;
        video.play().catch(() => {});
        return;
    }

    let Hls;
    try {
        ({ default: Hls } = await import('hls.js'));
    } catch (err) {
        console.error('[CctvPlayer] GAGAL memuat hls.js:', err);
        isLoading.value = false;
        errorMessage.value = 'Gagal memuat pemutar video (hls.js).';
        return;
    }

    if (!Hls.isSupported()) {
        isLoading.value = false;
        errorMessage.value = 'Browser ini tidak mendukung pemutaran live stream (HLS).';
        return;
    }

    // Pakai konfigurasi bawaan hls.js. Tuning kustom sebelumnya
    // (liveSyncDurationCount/maxBufferLength) membuat stream tidak pernah mulai.
    hls = new Hls();
    hls.loadSource(props.src);
    hls.attachMedia(video);

    hls.on(Hls.Events.MANIFEST_PARSED, () => {
        video.play().catch(() => {});
    });

    hls.on(Hls.Events.ERROR, (_event, data) => {
        if (!data.fatal) return;
        console.error('[CctvPlayer] fatal HLS error', data.type, data.details, data);
        isLoading.value = false;
        errorMessage.value = 'Stream CCTV tidak dapat dimuat. Kamera mungkin sedang mati atau server Pemkot sedang gangguan.';
        teardown();
    });
});

onBeforeUnmount(teardown);
</script>

<template>
    <div class="space-y-2">
        <div class="relative overflow-hidden rounded-lg bg-black">
            <video ref="videoEl" class="aspect-video w-full" controls autoplay muted playsinline></video>

            <div v-if="isLoading"
                 class="pointer-events-none absolute inset-0 flex items-center justify-center gap-2 bg-black/60 text-sm text-white">
                <Loader2 class="h-4 w-4 animate-spin" />
                Memuat live stream...
            </div>
        </div>

        <div v-if="errorMessage"
             class="flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
            <AlertTriangle class="mt-0.5 h-4 w-4 shrink-0" />
            <span>{{ errorMessage }}</span>
        </div>
    </div>
</template>
