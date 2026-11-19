<script setup>
import { ref, watch, nextTick, onBeforeUnmount } from 'vue';
import { Dialog, DialogScrollContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { ScanLine, Camera, Upload, RotateCcw, Sparkles, Loader2, AlertCircle, ShieldCheck } from 'lucide-vue-next';
import axios from 'axios';

/**
 * Dialog foto dokumen → dibaca otomatis. Dipakai di Profil Data Warga untuk KTP dan
 * Kartu Keluarga. Foto dikecilkan di browser sebelum dikirim.
 * Emit `hasil` dengan (jawaban server, blob foto) — blob dipakai Profil warga sebagai lampiran.
 */
const props = defineProps({
    jenis: { type: String, default: 'ktp' },            // 'ktp' | 'kk'
    endpoint: { type: String, default: null },          // bawaan: Scan KTP Data Warga
    dataTambahan: { type: Object, default: () => ({}) }, // mis. { slot: 'owner' }
    catatanPrivasi: { type: String, default: 'Foto KTP hanya dipakai untuk mengisi formulir ini dan tidak disimpan.' },
});
const open = defineModel('open', { type: Boolean, default: false });
const emit = defineEmits(['hasil']);

const isKk = props.jenis === 'kk';
const namaDok = isKk ? 'Kartu Keluarga' : 'KTP';
// px — KTP cukup 1600; tulisan KK kecil & berupa tabel sehingga perlu lebih tajam
const SISI_MAKS = isKk ? 2400 : 1600;

const tahap = ref('pilih'); // 'pilih' | 'kamera' | 'pratinjau'
const fotoBlob = ref(null);
const fotoUrl = ref('');
const sedangMembaca = ref(false);
const galat = ref('');

const videoRef = ref(null);
const inputUnggahRef = ref(null);
const inputKameraRef = ref(null);
let stream = null;

const hentikanKamera = () => {
    stream?.getTracks().forEach(t => t.stop());
    stream = null;
};

const setel = () => {
    hentikanKamera();
    if (fotoUrl.value) URL.revokeObjectURL(fotoUrl.value);
    tahap.value = 'pilih';
    fotoBlob.value = null;
    fotoUrl.value = '';
    galat.value = '';
    sedangMembaca.value = false;
};

watch(open, (v) => { if (!v) setel(); });
onBeforeUnmount(setel);

// Kecilkan gambar (sisi terpanjang ≤ SISI_MAKS) dan ubah ke JPEG.
const kecilkan = (sumber, lebar, tinggi) => new Promise((resolve) => {
    const skala = Math.min(1, SISI_MAKS / Math.max(lebar, tinggi));
    const canvas = document.createElement('canvas');
    canvas.width = Math.round(lebar * skala);
    canvas.height = Math.round(tinggi * skala);
    canvas.getContext('2d').drawImage(sumber, 0, 0, canvas.width, canvas.height);
    canvas.toBlob(resolve, 'image/jpeg', 0.85);
});

const pakaiFoto = (blob) => {
    if (fotoUrl.value) URL.revokeObjectURL(fotoUrl.value);
    fotoBlob.value = blob;
    fotoUrl.value = URL.createObjectURL(blob);
    tahap.value = 'pratinjau';
    galat.value = '';
};

const bukaKamera = async () => {
    galat.value = '';
    if (!navigator.mediaDevices?.getUserMedia) {
        // Browser tanpa akses kamera langsung (mis. bukan HTTPS) → kamera bawaan HP lewat input file.
        inputKameraRef.value?.click();
        return;
    }
    try {
        stream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: { ideal: 'environment' }, width: { ideal: 1920 }, height: { ideal: 1080 } },
            audio: false,
        });
        tahap.value = 'kamera';
        await nextTick();
        videoRef.value.srcObject = stream;
    } catch {
        hentikanKamera();
        inputKameraRef.value?.click();
    }
};

const jepret = async () => {
    const v = videoRef.value;
    if (!v?.videoWidth) return;
    const blob = await kecilkan(v, v.videoWidth, v.videoHeight);
    hentikanKamera();
    pakaiFoto(blob);
};

const pilihFile = async (e) => {
    const file = e.target.files?.[0];
    e.target.value = '';
    if (!file) return;
    if (!file.type.startsWith('image/')) {
        galat.value = 'File harus berupa gambar (JPG/PNG).';
        return;
    }
    try {
        const bitmap = await createImageBitmap(file);
        pakaiFoto(await kecilkan(bitmap, bitmap.width, bitmap.height));
        bitmap.close();
    } catch {
        // Format yang tidak bisa dibaca browser — kirim apa adanya, server yang memvalidasi.
        pakaiFoto(file);
    }
};

const ulangi = () => {
    if (fotoUrl.value) URL.revokeObjectURL(fotoUrl.value);
    fotoBlob.value = null;
    fotoUrl.value = '';
    galat.value = '';
    tahap.value = 'pilih';
};

const bacaDenganAi = async () => {
    if (!fotoBlob.value) return;
    sedangMembaca.value = true;
    galat.value = '';
    const fd = new FormData();
    fd.append('foto', fotoBlob.value, isKk ? 'kk.jpg' : 'ktp.jpg');
    for (const [k, v] of Object.entries(props.dataTambahan)) fd.append(k, v);
    try {
        const { data } = await axios.post(props.endpoint || route('admin.warga.scan-ktp'), fd, {
            headers: { Accept: 'application/json' },
        });
        emit('hasil', data, fotoBlob.value);
        open.value = false;
    } catch (e) {
        const s = e.response?.status;
        galat.value = e.response?.data?.pesan
            || (s === 422 ? (e.response?.data?.errors?.foto?.[0] ?? 'Foto tidak valid.') : null)
            || (s === 429 ? 'Terlalu sering. Tunggu sebentar lalu coba lagi.' : null)
            || (s === 419 ? 'Sesi telah berakhir. Silakan refresh halaman.' : null)
            || `Gagal membaca ${namaDok}. Periksa koneksi dan coba lagi.`;
    } finally {
        sedangMembaca.value = false;
    }
};
</script>

<template>
    <Dialog v-model:open="open">
        <DialogScrollContent class="sm:max-w-[560px]">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <ScanLine class="w-5 h-5 text-violet-600" /> {{ isKk ? 'Foto Kartu Keluarga' : 'Scan KTP' }}
                </DialogTitle>
                <DialogDescription class="text-xs">
                    {{ isKk
                        ? 'Foto lembar KK, semua anggota keluarga akan terisi otomatis. Hasilnya diperiksa dulu sebelum disimpan.'
                        : 'Foto KTP, data identitas akan terisi otomatis. Periksa kembali hasilnya.' }}
                </DialogDescription>
            </DialogHeader>

            <!-- Pilih sumber foto -->
            <div v-if="tahap === 'pilih'" class="grid grid-cols-2 gap-3">
                <button
                    type="button"
                    class="flex flex-col items-center gap-2 p-6 rounded-xl border-2 border-dashed border-slate-300 hover:border-violet-400 hover:bg-violet-50 transition-colors"
                    @click="bukaKamera"
                >
                    <Camera class="w-8 h-8 text-violet-600" />
                    <span class="text-sm font-medium text-slate-700">Ambil dari Kamera</span>
                </button>
                <button
                    type="button"
                    class="flex flex-col items-center gap-2 p-6 rounded-xl border-2 border-dashed border-slate-300 hover:border-violet-400 hover:bg-violet-50 transition-colors"
                    @click="inputUnggahRef?.click()"
                >
                    <Upload class="w-8 h-8 text-violet-600" />
                    <span class="text-sm font-medium text-slate-700">Unggah Foto</span>
                </button>
                <p class="col-span-2 text-xs text-slate-500">
                    {{ isKk
                        ? 'Tips: letakkan KK mendatar di meja yang terang, seluruh lembar masuk foto, tidak miring, tidak terlipat, dan tidak buram.'
                        : 'Tips: letakkan KTP di permukaan gelap, seluruh kartu terlihat, tidak buram, dan tidak terkena pantulan lampu.' }}
                </p>
            </div>

            <!-- Kamera langsung -->
            <div v-else-if="tahap === 'kamera'" class="space-y-3">
                <div class="relative rounded-xl overflow-hidden bg-black aspect-[4/3]">
                    <video ref="videoRef" autoplay playsinline muted class="w-full h-full object-contain" />
                    <!-- Bingkai panduan: KTP 85,6 × 54 mm; KK ±33 × 21,5 cm (mendatar) -->
                    <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                        <div :class="isKk ? 'w-[94%] aspect-[33/21.5]' : 'w-[85%] aspect-[85.6/54]'" class="rounded-lg border-2 border-white/80 shadow-[0_0_0_9999px_rgba(0,0,0,0.35)]" />
                    </div>
                </div>
                <div class="flex justify-between gap-2">
                    <Button variant="outline" @click="ulangi">Batal</Button>
                    <Button class="bg-violet-600 hover:bg-violet-700 text-white gap-2" @click="jepret">
                        <Camera class="w-4 h-4" /> Jepret
                    </Button>
                </div>
            </div>

            <!-- Pratinjau -->
            <div v-else class="space-y-3">
                <img :src="fotoUrl" :alt="`Pratinjau foto ${namaDok}`" class="w-full max-h-80 object-contain rounded-xl border border-slate-200 bg-slate-50" />
            </div>

            <div v-if="galat" class="flex items-start gap-2 text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg p-3">
                <AlertCircle class="w-4 h-4 shrink-0 mt-0.5" /> <span>{{ galat }}</span>
            </div>

            <p class="flex items-start gap-1.5 text-[11px] text-slate-400">
                <ShieldCheck class="w-3.5 h-3.5 shrink-0 mt-px" />
                {{ catatanPrivasi }}
            </p>

            <DialogFooter v-if="tahap === 'pratinjau'" class="gap-2 sm:gap-0">
                <Button variant="outline" :disabled="sedangMembaca" class="gap-2" @click="ulangi">
                    <RotateCcw class="w-4 h-4" /> Foto Ulang
                </Button>
                <Button class="bg-violet-600 hover:bg-violet-700 text-white gap-2" :disabled="sedangMembaca" @click="bacaDenganAi">
                    <Loader2 v-if="sedangMembaca" class="w-4 h-4 animate-spin" />
                    <Sparkles v-else class="w-4 h-4" />
                    {{ sedangMembaca ? `Membaca ${namaDok}...` : `Baca ${namaDok}` }}
                </Button>
            </DialogFooter>

            <input ref="inputUnggahRef" type="file" accept="image/*" class="hidden" @change="pilihFile" />
            <input ref="inputKameraRef" type="file" accept="image/*" capture="environment" class="hidden" @change="pilihFile" />
        </DialogScrollContent>
    </Dialog>
</template>
