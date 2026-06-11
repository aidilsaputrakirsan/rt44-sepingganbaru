<script setup>
import { ref, computed, watch, nextTick } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/Components/ui/select';
import { FileText, Plus, Trash2, AlertCircle, Loader2 } from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps({
    houses: Array,
});

// ── House & Person Selection ─────────────────────────────────
const selectedHouseId = ref('');
const selectedPersonId = ref(''); // id resident_id_card yang dipilih

const selectedHouse = computed(() =>
    props.houses.find(h => h.id === Number(selectedHouseId.value)) ?? null
);

const people = computed(() => selectedHouse.value?.people ?? []);

// ── Form ─────────────────────────────────────────────────────
const form = useForm({
    house_id:          '',
    person_id:         '', // id orang terpilih (cNN = kartu, uNN = akun) utk simpan-balik
    nama_lengkap:      '',
    jenis_kelamin:     '',
    tempat_lahir:      '',
    tanggal_lahir:     '',
    status_perkawinan: '',
    agama:             '',
    pekerjaan:         '',
    golongan_darah:    '',
    kewarganegaraan:   'WNI',
    alamat:            '',
    nik:               '',
    nomor_kk:          '',
    maksud_tujuan:     '',
    keperluan:         [],
    keperluan_lain:    '',
    alamat_dituju:     '',
    nomor_dituju:      '',
    rt_dituju:         '',
    kelurahan_dituju:  '',
    kecamatan_dituju:  '',
    kab_kota_dituju:   '',
    provinsi_dituju:   '',
    jumlah_pengikut:   0,
    pengikut:          [],
    nomor_surat:       '',
    tanggal_surat:     new Date().toISOString().slice(0, 10),
});

// Saat ganti rumah: set house_id + alamat, reset pilihan orang
watch(selectedHouseId, () => {
    const h = selectedHouse.value;
    selectedPersonId.value = '';
    if (!h) return;
    form.house_id = h.id;
    form.alamat = h.alamat ?? '';
});

// Saat pilih orang: auto-fill semua field identitas dari data tersimpan
watch(selectedPersonId, () => {
    form.person_id = selectedPersonId.value;
    const p = people.value.find(x => String(x.id) === selectedPersonId.value);
    if (!p) return;

    form.nama_lengkap      = p.nama ?? '';
    form.nik               = p.nomor_ktp ?? '';
    form.nomor_kk          = p.nomor_kk ?? '';
    form.jenis_kelamin     = p.jenis_kelamin ?? '';
    form.tempat_lahir      = p.tempat_lahir ?? '';
    form.tanggal_lahir     = p.tanggal_lahir ?? '';
    form.status_perkawinan = p.status_perkawinan ?? '';
    form.agama             = p.agama ?? '';
    form.pekerjaan         = p.pekerjaan ?? '';
    form.golongan_darah    = p.golongan_darah ?? '';
    form.kewarganegaraan   = p.kewarganegaraan || 'WNI';
});

// ── Keperluan checkboxes ──────────────────────────────────────
const keperluanOptions = [
    { key: 'kk_ktp',            label: 'KK / KTP' },
    { key: 'akte_kelahiran',    label: 'Pengantar Akte Kelahiran / Kenal Lahir' },
    { key: 'surat_kematian',    label: 'Pengantar Surat Kematian' },
    { key: 'nikah',             label: 'Pengantar Nikah' },
    { key: 'pindah',            label: 'Pengantar Permohonan Pindah' },
    { key: 'domisili_tinggal',  label: 'Surat Ket. Domisili Tempat Tinggal' },
    { key: 'bepergian',         label: 'Surat Ket. Bepergian / Jalan' },
    { key: 'domisili_usaha',    label: 'Surat Ket. Domisili Usaha' },
    { key: 'skck',              label: 'SKCK' },
    { key: 'lain_lain',         label: 'Lain-Lain' },
];

const toggleKeperluan = (key) => {
    const idx = form.keperluan.indexOf(key);
    if (idx === -1) form.keperluan.push(key);
    else form.keperluan.splice(idx, 1);
};

// ── Pengikut rows ─────────────────────────────────────────────
const addPengikut = () => {
    form.pengikut.push({ nama: '', hub_keluarga: '' });
    form.jumlah_pengikut = form.pengikut.length;
};

const removePengikut = (i) => {
    form.pengikut.splice(i, 1);
    form.jumlah_pengikut = form.pengikut.length;
};

// Because PDF must stream (not Inertia response), we submit directly via fetch
const generatePdf = async () => {
    form.jumlah_pengikut = form.pengikut.length;
    form.clearErrors();
    generalError.value = '';
    isLoading.value = true;

    const payload = { ...form.data() };

    try {
        const res = await axios.post(route('ketua.surat-pengantar.generate'), payload, {
            responseType: 'blob',
            headers: {
                // Accept json supaya error validasi balik sebagai 422 JSON (bukan redirect 302).
                // Response sukses tetap PDF karena controller set content-type sendiri.
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            validateStatus: () => true, // tangani semua status secara manual
        });

        if (res.status === 422) {
            // blob → text → JSON untuk baca validation errors
            const text = await res.data.text();
            const json = JSON.parse(text);
            form.setError(json.errors ?? {});
            generalError.value = 'Periksa kembali isian form. Ada data yang belum lengkap atau tidak valid.';
            await nextTick();
            errorBannerRef.value?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        if (res.status === 419) {
            generalError.value = 'Sesi telah berakhir. Silakan refresh halaman dan coba lagi.';
            await nextTick();
            errorBannerRef.value?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        if (res.status !== 200) {
            const text = await res.data.text().catch(() => '');
            let detail = '';
            try {
                const json = JSON.parse(text);
                detail = json.message ?? '';
            } catch {
                // response adalah HTML atau teks lain, jangan tampilkan ke user
            }
            generalError.value = detail
                ? `Gagal membuat surat: ${detail}`
                : `Gagal membuat surat. Terjadi kesalahan pada server (HTTP ${res.status}).`;
            await nextTick();
            errorBannerRef.value?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        const url = URL.createObjectURL(res.data);
        window.open(url, '_blank');
    } catch (e) {
        generalError.value = 'Terjadi kesalahan koneksi. Periksa jaringan dan coba lagi.';
        await nextTick();
        errorBannerRef.value?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    } finally {
        isLoading.value = false;
    }
};

const hasLainLain = computed(() => form.keperluan.includes('lain_lain'));

// ── Error handling ────────────────────────────────────────────
const isLoading   = ref(false);
const generalError = ref('');
const errorBannerRef = ref(null);

const fieldLabels = {
    house_id:          'Rumah',
    nama_lengkap:      'Nama Lengkap',
    jenis_kelamin:     'Jenis Kelamin',
    tempat_lahir:      'Tempat Lahir',
    tanggal_lahir:     'Tanggal Lahir',
    status_perkawinan: 'Status Perkawinan',
    agama:             'Agama',
    pekerjaan:         'Pekerjaan',
    kewarganegaraan:   'Kewarganegaraan',
    alamat:            'Alamat',
    nik:               'NIK',
    nomor_kk:          'Nomor KK',
    maksud_tujuan:     'Maksud / Tujuan Mengurus',
    tanggal_surat:     'Tanggal Surat',
};

const errorList = computed(() => {
    return Object.entries(form.errors).map(([field, msg]) => ({
        field,
        label: fieldLabels[field] ?? field,
        msg,
    }));
});
</script>

<template>
    <Head title="Surat Pengantar" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <FileText class="w-5 h-5 text-blue-600" />
                <h2 class="text-lg font-semibold text-slate-800">Surat Pengantar</h2>
            </div>
        </template>

        <div class="max-w-3xl mx-auto space-y-6">

            <!-- ── Pilih Rumah ── -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                <h3 class="font-semibold text-slate-700 mb-4">Pilih Rumah & Warga</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <Label>Nomor Rumah</Label>
                        <Select v-model="selectedHouseId">
                            <SelectTrigger>
                                <SelectValue placeholder="Pilih rumah..." />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="h in houses"
                                    :key="h.id"
                                    :value="String(h.id)"
                                >
                                    Blok {{ h.blok }}/{{ h.nomor }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="space-y-1.5">
                        <Label>Pilih Nama</Label>
                        <Select v-model="selectedPersonId" :disabled="!selectedHouse">
                            <SelectTrigger>
                                <SelectValue placeholder="Pilih nama..." />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="p in people"
                                    :key="p.id"
                                    :value="String(p.id)"
                                >
                                    {{ p.nama }} <span class="text-slate-400">({{ p.slot_label }})</span>
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="selectedHouse && people.length === 0" class="text-xs text-amber-600">
                            Belum ada data anggota keluarga untuk rumah ini. Tambahkan dulu lewat
                            <strong>Data Warga → Profil</strong>, atau isi manual di bawah.
                        </p>
                    </div>
                </div>
            </div>

            <!-- ── Data Surat ── -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-5">
                <h3 class="font-semibold text-slate-700">Nomor & Tanggal Surat</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <Label>Nomor Surat <span class="text-slate-400 text-xs">(opsional)</span></Label>
                        <Input v-model="form.nomor_surat" placeholder="Contoh: 001/RT.44/I/2025" />
                    </div>
                    <div class="space-y-1.5">
                        <Label>Tanggal Surat <span class="text-red-500">*</span></Label>
                        <Input type="date" v-model="form.tanggal_surat" />
                        <p v-if="form.errors.tanggal_surat" class="text-xs text-red-500">{{ form.errors.tanggal_surat }}</p>
                    </div>
                </div>
            </div>

            <!-- ── Identitas Pemohon ── -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
                <h3 class="font-semibold text-slate-700">Data Identitas Pemohon</h3>

                <div class="space-y-1.5">
                    <Label>Nama Lengkap <span class="text-red-500">*</span></Label>
                    <Input v-model="form.nama_lengkap" placeholder="Nama sesuai KTP" />
                    <p v-if="form.errors.nama_lengkap" class="text-xs text-red-500">{{ form.errors.nama_lengkap }}</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <Label>Jenis Kelamin <span class="text-red-500">*</span></Label>
                        <Select v-model="form.jenis_kelamin">
                            <SelectTrigger>
                                <SelectValue placeholder="Pilih..." />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="Laki-laki">Laki-laki</SelectItem>
                                <SelectItem value="Perempuan">Perempuan</SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="form.errors.jenis_kelamin" class="text-xs text-red-500">{{ form.errors.jenis_kelamin }}</p>
                    </div>

                    <div class="space-y-1.5">
                        <Label>Status Perkawinan <span class="text-red-500">*</span></Label>
                        <Select v-model="form.status_perkawinan">
                            <SelectTrigger>
                                <SelectValue placeholder="Pilih..." />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="Belum Kawin">Belum Kawin</SelectItem>
                                <SelectItem value="Kawin">Kawin</SelectItem>
                                <SelectItem value="Cerai Hidup">Cerai Hidup</SelectItem>
                                <SelectItem value="Cerai Mati">Cerai Mati</SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="form.errors.status_perkawinan" class="text-xs text-red-500">{{ form.errors.status_perkawinan }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <Label>Tempat Lahir <span class="text-red-500">*</span></Label>
                        <Input v-model="form.tempat_lahir" placeholder="Kota lahir" />
                        <p v-if="form.errors.tempat_lahir" class="text-xs text-red-500">{{ form.errors.tempat_lahir }}</p>
                    </div>
                    <div class="space-y-1.5">
                        <Label>Tanggal Lahir <span class="text-red-500">*</span></Label>
                        <Input type="date" v-model="form.tanggal_lahir" />
                        <p v-if="form.errors.tanggal_lahir" class="text-xs text-red-500">{{ form.errors.tanggal_lahir }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <Label>Agama <span class="text-red-500">*</span></Label>
                        <Select v-model="form.agama">
                            <SelectTrigger>
                                <SelectValue placeholder="Pilih..." />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="Islam">Islam</SelectItem>
                                <SelectItem value="Kristen">Kristen</SelectItem>
                                <SelectItem value="Katolik">Katolik</SelectItem>
                                <SelectItem value="Hindu">Hindu</SelectItem>
                                <SelectItem value="Buddha">Buddha</SelectItem>
                                <SelectItem value="Konghucu">Konghucu</SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="form.errors.agama" class="text-xs text-red-500">{{ form.errors.agama }}</p>
                    </div>
                    <div class="space-y-1.5">
                        <Label>Golongan Darah</Label>
                        <Select v-model="form.golongan_darah">
                            <SelectTrigger>
                                <SelectValue placeholder="Pilih..." />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="">Tidak diketahui</SelectItem>
                                <SelectItem value="A">A</SelectItem>
                                <SelectItem value="B">B</SelectItem>
                                <SelectItem value="AB">AB</SelectItem>
                                <SelectItem value="O">O</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <Label>Pekerjaan <span class="text-red-500">*</span></Label>
                        <Input v-model="form.pekerjaan" placeholder="Contoh: Karyawan Swasta" />
                        <p v-if="form.errors.pekerjaan" class="text-xs text-red-500">{{ form.errors.pekerjaan }}</p>
                    </div>
                    <div class="space-y-1.5">
                        <Label>Kewarganegaraan <span class="text-red-500">*</span></Label>
                        <Input v-model="form.kewarganegaraan" />
                        <p v-if="form.errors.kewarganegaraan" class="text-xs text-red-500">{{ form.errors.kewarganegaraan }}</p>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <Label>Alamat <span class="text-red-500">*</span></Label>
                    <Input v-model="form.alamat" placeholder="Alamat lengkap" />
                    <p v-if="form.errors.alamat" class="text-xs text-red-500">{{ form.errors.alamat }}</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <Label>NIK <span class="text-red-500">*</span></Label>
                        <Input v-model="form.nik" placeholder="16 digit NIK" maxlength="16" />
                        <p v-if="form.errors.nik" class="text-xs text-red-500">{{ form.errors.nik }}</p>
                    </div>
                    <div class="space-y-1.5">
                        <Label>Nomor KK <span class="text-red-500">*</span></Label>
                        <Input v-model="form.nomor_kk" placeholder="Nomor Kartu Keluarga" maxlength="16" />
                        <p v-if="form.errors.nomor_kk" class="text-xs text-red-500">{{ form.errors.nomor_kk }}</p>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <Label>Maksud / Tujuan Mengurus <span class="text-red-500">*</span></Label>
                    <Input v-model="form.maksud_tujuan" placeholder="Contoh: Mengurus administrasi kependudukan" />
                    <p v-if="form.errors.maksud_tujuan" class="text-xs text-red-500">{{ form.errors.maksud_tujuan }}</p>
                </div>
            </div>

            <!-- ── Keperluan ── -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-3">
                <h3 class="font-semibold text-slate-700">Keperluan</h3>
                <div class="grid grid-cols-2 gap-x-6 gap-y-2">
                    <label
                        v-for="opt in keperluanOptions"
                        :key="opt.key"
                        class="flex items-center gap-2 text-sm cursor-pointer"
                    >
                        <input
                            type="checkbox"
                            :checked="form.keperluan.includes(opt.key)"
                            @change="toggleKeperluan(opt.key)"
                            class="w-4 h-4 rounded border-slate-300 text-blue-600"
                        />
                        {{ opt.label }}
                    </label>
                </div>
                <div v-if="hasLainLain" class="space-y-1.5 mt-2">
                    <Label>Keterangan Lain-Lain</Label>
                    <Input v-model="form.keperluan_lain" placeholder="Sebutkan..." />
                </div>
            </div>

            <!-- ── Alamat Dituju ── -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
                <h3 class="font-semibold text-slate-700">Alamat yang Dituju</h3>
                <div class="grid grid-cols-3 gap-4">
                    <div class="col-span-2 space-y-1.5">
                        <Label>Alamat</Label>
                        <Input v-model="form.alamat_dituju" placeholder="Nama jalan / instansi" />
                    </div>
                    <div class="space-y-1.5">
                        <Label>No.</Label>
                        <Input v-model="form.nomor_dituju" placeholder="–" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <Label>RT</Label>
                        <Input v-model="form.rt_dituju" placeholder="–" />
                    </div>
                    <div class="space-y-1.5">
                        <Label>Kelurahan</Label>
                        <Input v-model="form.kelurahan_dituju" />
                    </div>
                    <div class="space-y-1.5">
                        <Label>Kecamatan</Label>
                        <Input v-model="form.kecamatan_dituju" />
                    </div>
                    <div class="space-y-1.5">
                        <Label>Kab / Kota</Label>
                        <Input v-model="form.kab_kota_dituju" />
                    </div>
                    <div class="space-y-1.5 col-span-2">
                        <Label>Provinsi</Label>
                        <Input v-model="form.provinsi_dituju" />
                    </div>
                </div>
            </div>

            <!-- ── Pengikut ── -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-slate-700">Daftar Pengikut</h3>
                    <Button variant="outline" size="sm" @click="addPengikut">
                        <Plus class="w-4 h-4 mr-1" /> Tambah
                    </Button>
                </div>

                <div v-if="form.pengikut.length === 0" class="text-sm text-slate-400 text-center py-4 border border-dashed border-slate-200 rounded-lg">
                    Tidak ada pengikut
                </div>

                <div v-else class="space-y-2">
                    <div
                        v-for="(p, i) in form.pengikut"
                        :key="i"
                        class="flex items-center gap-3"
                    >
                        <span class="text-sm text-slate-400 w-5 text-right shrink-0">{{ i + 1 }}</span>
                        <Input v-model="p.nama" placeholder="Nama" class="flex-1" />
                        <Input v-model="p.hub_keluarga" placeholder="Hub. Keluarga" class="w-36" />
                        <Button variant="ghost" size="icon" @click="removePengikut(i)" class="text-red-400 hover:text-red-600 shrink-0">
                            <Trash2 class="w-4 h-4" />
                        </Button>
                    </div>
                </div>
            </div>

            <!-- ── Error Banner ── -->
            <div
                v-if="generalError || errorList.length > 0"
                ref="errorBannerRef"
                class="bg-red-50 border border-red-200 rounded-xl p-4 space-y-2"
            >
                <div class="flex items-center gap-2 text-red-700 font-medium">
                    <AlertCircle class="w-4 h-4 shrink-0" />
                    <span>{{ generalError || 'Terdapat kesalahan pada form' }}</span>
                </div>
                <ul v-if="errorList.length > 0" class="text-sm text-red-600 space-y-1 pl-6 list-disc">
                    <li v-for="e in errorList" :key="e.field">
                        <span class="font-medium">{{ e.label }}</span>: {{ e.msg }}
                    </li>
                </ul>
            </div>

            <!-- ── Tombol Generate ── -->
            <div class="flex justify-end pb-8">
                <Button
                    size="lg"
                    class="bg-blue-600 hover:bg-blue-700 text-white gap-2"
                    :disabled="!selectedHouseId || isLoading"
                    @click="generatePdf"
                >
                    <Loader2 v-if="isLoading" class="w-4 h-4 animate-spin" />
                    <FileText v-else class="w-4 h-4" />
                    {{ isLoading ? 'Membuat PDF...' : 'Buat & Unduh PDF' }}
                </Button>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
