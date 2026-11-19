<script setup>
/**
 * Periksa hasil baca Kartu Keluarga sebelum disimpan (Profil Data Warga).
 * Foto asli tampil di samping; tiap anggota bisa dikoreksi / tidak ikut disimpan.
 * Anggota yang NIK-nya sudah terdaftar ditandai "diperbarui" (tidak membuat data dobel).
 * Kolom hasil baca otomatis berbingkai ungu.
 */
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Dialog, DialogScrollContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { AlertTriangle, Loader2, Save, UserCheck, UserPlus } from 'lucide-vue-next';

const open = defineModel('open', { type: Boolean, default: false });
const props = defineProps({
    hasil: { type: Object, default: null },       // { nomor_kk, alamat, anggota: [{ data, peringatan, id }], peringatan }
    foto: { type: [Blob, File], default: null },
    houseId: { type: Number, required: true },
    slotRumah: { type: String, default: 'owner' }, // 'owner' | 'tenant'
    adaLampiranKk: { type: Boolean, default: false },
});

const JK = ['Laki-laki', 'Perempuan'];
const AGAMA = ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'];
const KAWIN = ['Belum Kawin', 'Kawin', 'Cerai Hidup', 'Cerai Mati'];
const GOLDAR = ['A', 'B', 'AB', 'O'];

const fotoUrl = ref('');
const baris = ref([]);   // [{ ikut, id, peringatan, data }]
const form = useForm({ slot: 'owner', nomor_kk: '', simpan_foto: true, foto: null, anggota: [] });

watch(() => [open.value, props.hasil], () => {
    if (!open.value || !props.hasil) return;
    if (fotoUrl.value) URL.revokeObjectURL(fotoUrl.value);
    fotoUrl.value = props.foto ? URL.createObjectURL(props.foto) : '';
    baris.value = (props.hasil.anggota || []).map(a => ({
        ikut: true,
        id: a.id || null,
        peringatan: a.peringatan || [],
        data: { ...a.data },
    }));
    form.clearErrors();
    form.nomor_kk = props.hasil.nomor_kk || '';
    form.simpan_foto = !!props.foto;
}, { immediate: true });

const jumlahIkut = computed(() => baris.value.filter(b => b.ikut).length);
const kelasIsian = (b, kunci) => (b.data[kunci] ? 'border-violet-300 bg-violet-50/40' : '');
const galatBaris = (i, kunci) => form.errors[`anggota.${i}.${kunci}`];

const simpan = () => {
    const ikut = baris.value.filter(b => b.ikut);
    form.slot = props.slotRumah;
    form.foto = form.simpan_foto ? props.foto : null;
    form.anggota = ikut.map(b => ({ id: b.id, ...b.data }));
    form.post(route('admin.warga.profil.kk-anggota', props.houseId), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => { open.value = false; },
    });
};
</script>

<template>
    <Dialog v-model:open="open">
        <DialogScrollContent class="sm:max-w-[1100px]">
            <DialogHeader>
                <DialogTitle class="text-lg">Periksa Hasil Baca Kartu Keluarga</DialogTitle>
                <DialogDescription>
                    Cocokkan dengan foto. Kolom <span class="rounded border border-violet-300 bg-violet-50 px-1">ungu</span> terisi otomatis —
                    perbaiki bila ada yang salah, lalu tekan <strong>Simpan</strong>. Belum ada yang tersimpan sebelum tombol itu ditekan.
                </DialogDescription>
            </DialogHeader>

            <div v-if="hasil" class="grid lg:grid-cols-[minmax(0,2fr)_minmax(0,3fr)] gap-5">
                <!-- Foto asli -->
                <div class="lg:sticky lg:top-0 self-start space-y-2">
                    <a v-if="fotoUrl" :href="fotoUrl" target="_blank" title="Buka foto ukuran penuh">
                        <img :src="fotoUrl" alt="Foto Kartu Keluarga" class="w-full rounded-xl border border-slate-200 bg-slate-50" />
                    </a>
                    <p class="text-[11px] text-slate-500">Klik foto untuk memperbesar.</p>
                    <div>
                        <Label>Nomor KK</Label>
                        <Input v-model="form.nomor_kk" inputmode="numeric" maxlength="16" class="mt-1 font-mono" :class="form.nomor_kk ? 'border-violet-300 bg-violet-50/40' : ''" />
                        <p v-if="form.errors.nomor_kk" class="text-xs text-red-600 mt-0.5">{{ form.errors.nomor_kk }}</p>
                    </div>
                    <p v-if="hasil.alamat" class="text-xs text-slate-500">Alamat di KK: {{ hasil.alamat }}</p>
                    <div v-for="p in hasil.peringatan" :key="p" class="flex gap-1.5 text-xs text-red-700"><AlertTriangle class="w-3.5 h-3.5 shrink-0 mt-0.5" />{{ p }}</div>
                </div>

                <!-- Anggota -->
                <div class="space-y-3">
                    <div v-for="(b, i) in baris" :key="i" class="rounded-xl border p-4 transition-colors"
                         :class="b.ikut ? (b.peringatan.length ? 'border-red-200 bg-red-50/30' : 'border-slate-200') : 'border-slate-200 bg-slate-50 opacity-60'">
                        <div class="flex items-start justify-between gap-3 mb-3">
                            <label class="flex items-center gap-2.5 cursor-pointer">
                                <input v-model="b.ikut" type="checkbox" class="h-5 w-5 rounded border-slate-300 text-indigo-600" />
                                <span class="font-semibold text-slate-900">{{ i + 1 }}. {{ b.data.nama || '(tanpa nama)' }}</span>
                            </label>
                            <span v-if="b.id" class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-800 shrink-0">
                                <UserCheck class="w-3.5 h-3.5" /> sudah terdaftar · diperbarui
                            </span>
                            <span v-else class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold text-emerald-800 shrink-0">
                                <UserPlus class="w-3.5 h-3.5" /> anggota baru
                            </span>
                        </div>

                        <div v-for="p in b.peringatan" :key="p" class="mb-2 flex gap-1.5 text-xs text-red-700"><AlertTriangle class="w-3.5 h-3.5 shrink-0 mt-0.5" />{{ p }}</div>

                        <div v-if="b.ikut" class="grid grid-cols-2 md:grid-cols-3 gap-x-3 gap-y-2 text-sm">
                            <div class="col-span-2 md:col-span-2">
                                <Label class="text-xs">Nama Lengkap</Label>
                                <Input v-model="b.data.nama" class="mt-0.5 h-9" :class="kelasIsian(b, 'nama')" />
                                <p v-if="galatBaris(i, 'nama')" class="text-xs text-red-600">{{ galatBaris(i, 'nama') }}</p>
                            </div>
                            <div>
                                <Label class="text-xs">Hubungan</Label>
                                <Input v-model="b.data.label" class="mt-0.5 h-9" :class="kelasIsian(b, 'label')" placeholder="Kepala Keluarga" />
                            </div>
                            <div class="col-span-2 md:col-span-1">
                                <Label class="text-xs">NIK</Label>
                                <Input v-model="b.data.nomor_ktp" inputmode="numeric" maxlength="16" class="mt-0.5 h-9 font-mono" :class="kelasIsian(b, 'nomor_ktp')" />
                            </div>
                            <div>
                                <Label class="text-xs">Jenis Kelamin</Label>
                                <select v-model="b.data.jenis_kelamin" class="mt-0.5 h-9 w-full rounded-md border border-input bg-background px-2" :class="kelasIsian(b, 'jenis_kelamin')">
                                    <option value="">-</option><option v-for="o in JK" :key="o">{{ o }}</option>
                                </select>
                            </div>
                            <div>
                                <Label class="text-xs">Tanggal Lahir</Label>
                                <Input v-model="b.data.tanggal_lahir" type="date" class="mt-0.5 h-9" :class="kelasIsian(b, 'tanggal_lahir')" />
                            </div>
                            <div>
                                <Label class="text-xs">Tempat Lahir</Label>
                                <Input v-model="b.data.tempat_lahir" class="mt-0.5 h-9" :class="kelasIsian(b, 'tempat_lahir')" />
                            </div>
                            <div>
                                <Label class="text-xs">Agama</Label>
                                <select v-model="b.data.agama" class="mt-0.5 h-9 w-full rounded-md border border-input bg-background px-2" :class="kelasIsian(b, 'agama')">
                                    <option value="">-</option><option v-for="o in AGAMA" :key="o">{{ o }}</option>
                                </select>
                            </div>
                            <div>
                                <Label class="text-xs">Status Kawin</Label>
                                <select v-model="b.data.status_perkawinan" class="mt-0.5 h-9 w-full rounded-md border border-input bg-background px-2" :class="kelasIsian(b, 'status_perkawinan')">
                                    <option value="">-</option><option v-for="o in KAWIN" :key="o">{{ o }}</option>
                                </select>
                            </div>
                            <div class="col-span-2 md:col-span-1">
                                <Label class="text-xs">Pekerjaan</Label>
                                <Input v-model="b.data.pekerjaan" class="mt-0.5 h-9" :class="kelasIsian(b, 'pekerjaan')" />
                            </div>
                            <div>
                                <Label class="text-xs">Gol. Darah</Label>
                                <select v-model="b.data.golongan_darah" class="mt-0.5 h-9 w-full rounded-md border border-input bg-background px-2" :class="kelasIsian(b, 'golongan_darah')">
                                    <option value="">-</option><option v-for="o in GOLDAR" :key="o">{{ o }}</option>
                                </select>
                            </div>
                            <div>
                                <Label class="text-xs">Kewarganegaraan</Label>
                                <Input v-model="b.data.kewarganegaraan" class="mt-0.5 h-9" :class="kelasIsian(b, 'kewarganegaraan')" />
                            </div>
                        </div>
                    </div>
                    <p v-if="form.errors.anggota" class="text-sm text-red-600">{{ form.errors.anggota }}</p>
                </div>
            </div>

            <DialogFooter class="flex-col sm:flex-row sm:items-center gap-3 border-t pt-4">
                <label v-if="foto" class="flex items-center gap-2 text-sm text-slate-700 sm:mr-auto cursor-pointer">
                    <input v-model="form.simpan_foto" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-indigo-600" />
                    Simpan foto ini sebagai lampiran KK<span v-if="adaLampiranKk" class="text-slate-400">(menggantikan lampiran lama)</span>
                </label>
                <Button variant="outline" :disabled="form.processing" @click="open = false">Batal</Button>
                <Button class="bg-indigo-600 hover:bg-indigo-700 gap-2 h-11 px-6 text-base" :disabled="form.processing || !jumlahIkut" @click="simpan">
                    <Loader2 v-if="form.processing" class="w-5 h-5 animate-spin" />
                    <Save v-else class="w-5 h-5" />
                    Simpan {{ jumlahIkut }} Anggota
                </Button>
            </DialogFooter>
        </DialogScrollContent>
    </Dialog>
</template>
