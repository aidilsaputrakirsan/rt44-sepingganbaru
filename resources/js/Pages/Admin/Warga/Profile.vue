<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Label } from '@/Components/ui/label';
import {
    Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter
} from '@/Components/ui/dialog';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue
} from '@/Components/ui/select';
import { ArrowLeft, FileText, IdCard, Home, User, Mail, Phone, Users, Paperclip, Trash2, Upload, Pencil } from 'lucide-vue-next';

// Opsi dropdown identitas (dipakai form tambah & edit)
const GENDER_OPTS = ['Laki-laki', 'Perempuan'];
const STATUS_OPTS = ['Belum Kawin', 'Kawin', 'Cerai Hidup', 'Cerai Mati'];
const AGAMA_OPTS = ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'];
const GOLDAR_OPTS = ['A', 'B', 'AB', 'O'];

const props = defineProps({
    house: Object,
    owner: Object,
    profile: Object,
    slot: { type: String, default: 'owner' },
});

const isTenantSlot = props.slot === 'tenant';

const form = useForm({
    jumlah_anggota_keluarga: props.profile.jumlah_anggota_keluarga ?? '',
    nomor_kk: props.profile.nomor_kk ?? '',
});

const kkForm = useForm({ kk_file: null });

// Form tambah anggota keluarga (identitas + scan KTP opsional)
const emptyIdentity = () => ({
    label: '', nama: '', nomor_ktp: '', jenis_kelamin: '', tempat_lahir: '',
    tanggal_lahir: '', status_perkawinan: '', agama: '', pekerjaan: '',
    golongan_darah: '', kewarganegaraan: 'WNI', ktp_file: null,
});
const ktpForm = useForm(emptyIdentity());

const kkInputRef = ref(null);
const ktpInputRef = ref(null);

const slotQuery = { slot: props.slot };

const submitProfile = () => {
    form.transform((data) => ({ ...data, slot: props.slot }))
        .put(route('admin.warga.profil.update', props.house.id), { preserveScroll: true });
};

const onKkChange = (e) => {
    const f = e.target.files?.[0];
    if (!f) return;
    if (f.size > 5 * 1024 * 1024) {
        alert('Ukuran file maksimal 5MB.');
        e.target.value = '';
        return;
    }
    kkForm.kk_file = f;
    kkForm.transform((d) => ({ ...d, slot: props.slot }))
        .post(route('admin.warga.profil.kk.upload', props.house.id), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                kkForm.reset();
                if (kkInputRef.value) kkInputRef.value.value = '';
            },
        });
};

const deleteKk = () => {
    if (!confirm('Hapus file Kartu Keluarga?')) return;
    router.delete(route('admin.warga.profil.kk.delete', props.house.id), {
        data: slotQuery,
        preserveScroll: true,
    });
};

const onKtpChange = (e) => {
    const f = e.target.files?.[0];
    if (!f) return;
    if (f.size > 5 * 1024 * 1024) {
        alert('Ukuran file maksimal 5MB.');
        e.target.value = '';
        return;
    }
    ktpForm.ktp_file = f;
};

const submitKtp = () => {
    if (!ktpForm.nama && !ktpForm.label && !ktpForm.ktp_file) {
        alert('Isi minimal Nama, atau pilih file KTP.');
        return;
    }
    ktpForm.transform((d) => ({ ...d, slot: props.slot }))
        .post(route('admin.warga.profil.ktp.upload', props.house.id), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                ktpForm.reset();
                ktpForm.kewarganegaraan = 'WNI';
                if (ktpInputRef.value) ktpInputRef.value.value = '';
            },
        });
};

// ── Edit identitas anggota (boleh sekalian ganti file KTP) ────
const isEditOpen = ref(false);
const editKtpInputRef = ref(null);
const editCurrentFileUrl = ref(null);
const editCurrentFilePath = ref(null);
const editForm = useForm({ id: null, ...emptyIdentity() });

const openEdit = (card) => {
    editForm.id = card.id;
    editForm.label = card.label ?? '';
    editForm.nama = card.nama ?? '';
    editForm.nomor_ktp = card.nomor_ktp ?? '';
    editForm.jenis_kelamin = card.jenis_kelamin ?? '';
    editForm.tempat_lahir = card.tempat_lahir ?? '';
    editForm.tanggal_lahir = card.tanggal_lahir ?? '';
    editForm.status_perkawinan = card.status_perkawinan ?? '';
    editForm.agama = card.agama ?? '';
    editForm.pekerjaan = card.pekerjaan ?? '';
    editForm.golongan_darah = card.golongan_darah ?? '';
    editForm.kewarganegaraan = card.kewarganegaraan ?? 'WNI';
    editForm.ktp_file = null;
    editCurrentFileUrl.value = card.file_url ?? null;
    editCurrentFilePath.value = card.file_path ?? null;
    if (editKtpInputRef.value) editKtpInputRef.value.value = '';
    isEditOpen.value = true;
};

const onEditKtpChange = (e) => {
    const f = e.target.files?.[0];
    if (!f) return;
    if (f.size > 5 * 1024 * 1024) {
        alert('Ukuran file maksimal 5MB.');
        e.target.value = '';
        return;
    }
    editForm.ktp_file = f;
};

const submitEdit = () => {
    // _method=put + forceFormData supaya file ikut terkirim lewat route PUT
    editForm.transform((d) => ({ ...d, slot: props.slot, _method: 'put' }))
        .post(route('admin.warga.profil.ktp.update', { house: props.house.id, idCard: editForm.id }), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => { isEditOpen.value = false; },
        });
};

const deleteKtp = (id) => {
    if (!confirm('Hapus KTP ini?')) return;
    router.delete(route('admin.warga.profil.ktp.delete', { house: props.house.id, idCard: id }), {
        data: slotQuery,
        preserveScroll: true,
    });
};

const statusHuniLabel = (s) => s === 'berpenghuni' ? 'Berpenghuni' : (s === 'kosong' ? 'Kosong' : '-');
const residentStatusLabel = (s) => {
    if (s === 'pemilik') return 'Pemilik';
    if (s === 'kontrak') return 'Kontrak';
    return 'Belum Diketahui';
};
const fileExt = (path) => (path?.split('.').pop() || '').toUpperCase();
</script>

<template>
    <Head :title="`Profil ${slot === 'tenant' ? 'Kontrak' : 'Pemilik'} - ${house.blok}/${house.nomor}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">
                        Profil {{ slot === 'tenant' ? 'Kontrak' : 'Pemilik' }} — {{ house.blok }}/{{ house.nomor }}
                    </h2>
                    <Badge :class="slot === 'tenant' ? 'bg-blue-100 text-blue-700 border-blue-200' : 'bg-emerald-100 text-emerald-700 border-emerald-200'">
                        {{ slot === 'tenant' ? 'KONTRAK' : 'PEMILIK' }}
                    </Badge>
                </div>
                <Button variant="outline" as-child>
                    <Link :href="route('admin.warga.index')">
                        <ArrowLeft class="w-4 h-4 mr-1.5" />
                        Kembali ke Data Warga
                    </Link>
                </Button>
            </div>
        </template>

        <div class="py-6 max-w-5xl mx-auto space-y-6">
            <!-- Data Dasar (readonly — diatur via Edit di Data Warga) -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-base">
                        <User class="w-5 h-5 text-amber-500" />
                        Data Dasar
                    </CardTitle>
                    <CardDescription>Untuk mengubah nama, HP, atau email — kembali ke <strong>Data Warga</strong>.</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <Label class="text-xs uppercase tracking-wide text-muted-foreground flex items-center gap-1.5">
                                <Home class="w-3.5 h-3.5" /> Nomor Rumah
                            </Label>
                            <div class="mt-1 px-3 py-2 rounded-md bg-slate-50 border border-slate-200 text-sm">
                                {{ house.blok }}/{{ house.nomor }}
                            </div>
                        </div>
                        <div>
                            <Label class="text-xs uppercase tracking-wide text-muted-foreground flex items-center gap-1.5">
                                <User class="w-3.5 h-3.5" /> Nama
                            </Label>
                            <div class="mt-1 px-3 py-2 rounded-md bg-slate-50 border border-slate-200 text-sm">
                                {{ owner.name || '-' }}
                            </div>
                        </div>
                        <div>
                            <Label class="text-xs uppercase tracking-wide text-muted-foreground flex items-center gap-1.5">
                                <Phone class="w-3.5 h-3.5" /> Nomor HP/WhatsApp
                            </Label>
                            <div class="mt-1 px-3 py-2 rounded-md bg-slate-50 border border-slate-200 text-sm">
                                {{ owner.phone_number || '-' }}
                            </div>
                        </div>
                        <div v-if="!isTenantSlot">
                            <Label class="text-xs uppercase tracking-wide text-muted-foreground flex items-center gap-1.5">
                                <Mail class="w-3.5 h-3.5" /> Email
                            </Label>
                            <div class="mt-1 px-3 py-2 rounded-md bg-slate-50 border border-slate-200 text-sm">
                                {{ owner.email || '-' }}
                            </div>
                        </div>
                        <div>
                            <Label class="text-xs uppercase tracking-wide text-muted-foreground">Status Huni</Label>
                            <div class="mt-1">
                                <Badge>{{ statusHuniLabel(house.status_huni) }}</Badge>
                            </div>
                        </div>
                        <div>
                            <Label class="text-xs uppercase tracking-wide text-muted-foreground">Status Kepemilikan</Label>
                            <div class="mt-1">
                                <Badge variant="secondary">{{ residentStatusLabel(house.resident_status) }}</Badge>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Data Tambahan (editable) -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-base">
                        <Users class="w-5 h-5 text-amber-500" />
                        Data Tambahan
                    </CardTitle>
                    <CardDescription>Isi/edit data tambahan untuk slot {{ isTenantSlot ? 'KONTRAK' : 'PEMILIK' }}.</CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submitProfile" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <Label for="jumlah_anggota_keluarga">Jumlah Anggota Keluarga</Label>
                                <Input
                                    id="jumlah_anggota_keluarga"
                                    v-model="form.jumlah_anggota_keluarga"
                                    type="number"
                                    min="0"
                                    max="50"
                                    placeholder="0"
                                    class="mt-1"
                                />
                                <p v-if="form.errors.jumlah_anggota_keluarga" class="text-xs text-red-600 mt-1">
                                    {{ form.errors.jumlah_anggota_keluarga }}
                                </p>
                            </div>
                            <div>
                                <Label for="nomor_kk">Nomor KK <span class="text-xs text-muted-foreground font-normal">(opsional)</span></Label>
                                <Input
                                    id="nomor_kk"
                                    v-model="form.nomor_kk"
                                    type="text"
                                    inputmode="numeric"
                                    maxlength="32"
                                    placeholder="16 digit nomor KK"
                                    class="mt-1"
                                />
                                <p v-if="form.errors.nomor_kk" class="text-xs text-red-600 mt-1">
                                    {{ form.errors.nomor_kk }}
                                </p>
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <Button type="submit" :disabled="form.processing">Simpan</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <!-- Kartu Keluarga (KK) -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-base">
                        <FileText class="w-5 h-5 text-amber-500" />
                        Kartu Keluarga (KK)
                    </CardTitle>
                    <CardDescription>JPG, PNG, atau PDF — maksimal 5MB.</CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="profile.kk_url" class="flex items-center justify-between p-3 rounded-md bg-emerald-50 border border-emerald-200">
                        <div class="flex items-center gap-3 min-w-0">
                            <FileText class="w-5 h-5 text-emerald-600 shrink-0" />
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-emerald-900 truncate">Kartu Keluarga terunggah</p>
                                <a :href="profile.kk_url" target="_blank" class="text-xs text-emerald-700 hover:underline">
                                    Lihat / Download ({{ fileExt(profile.kk_path) }})
                                </a>
                            </div>
                        </div>
                        <Button variant="ghost" size="sm" class="text-red-600 hover:bg-red-50" @click="deleteKk">
                            <Trash2 class="w-4 h-4" />
                        </Button>
                    </div>
                    <label
                        v-else
                        class="flex flex-col items-center justify-center p-6 rounded-lg border-2 border-dashed border-slate-300 hover:border-amber-400 hover:bg-amber-50/40 cursor-pointer transition"
                    >
                        <Paperclip class="w-6 h-6 text-slate-400 mb-2" />
                        <span class="text-sm font-medium text-slate-700">Pilih File</span>
                        <span class="text-xs text-muted-foreground mt-1">JPG, PNG, PDF — maks 5MB</span>
                        <input
                            ref="kkInputRef"
                            type="file"
                            accept=".jpg,.jpeg,.png,.pdf"
                            class="hidden"
                            @change="onKkChange"
                        />
                    </label>
                    <p v-if="kkForm.errors.kk_file" class="text-xs text-red-600 mt-2">{{ kkForm.errors.kk_file }}</p>
                    <p v-if="kkForm.progress" class="text-xs text-muted-foreground mt-2">
                        Mengunggah... {{ kkForm.progress.percentage }}%
                    </p>
                </CardContent>
            </Card>

            <!-- KTP (multiple) -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-base">
                        <IdCard class="w-5 h-5 text-amber-500" />
                        KTP Anggota Keluarga
                    </CardTitle>
                    <CardDescription>
                        Bisa upload lebih dari satu (kepala keluarga, istri, anak). Beri label supaya mudah ditemukan.
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="p-4 rounded-lg bg-slate-50 border border-slate-200 space-y-3">
                        <p class="text-sm font-medium text-slate-700">Tambah Anggota Keluarga</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <Label>Nama Lengkap</Label>
                                <Input v-model="ktpForm.nama" placeholder="Nama sesuai KTP" class="mt-1" />
                            </div>
                            <div>
                                <Label>Label (hubungan)</Label>
                                <Input v-model="ktpForm.label" placeholder="mis. Kepala Keluarga, Istri, Anak 1" class="mt-1" />
                            </div>
                            <div>
                                <Label>NIK</Label>
                                <Input v-model="ktpForm.nomor_ktp" inputmode="numeric" maxlength="32" placeholder="16 digit NIK" class="mt-1" />
                            </div>
                            <div>
                                <Label>Jenis Kelamin</Label>
                                <Select v-model="ktpForm.jenis_kelamin">
                                    <SelectTrigger class="mt-1"><SelectValue placeholder="Pilih..." /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="o in GENDER_OPTS" :key="o" :value="o">{{ o }}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <Label>Tempat Lahir</Label>
                                <Input v-model="ktpForm.tempat_lahir" placeholder="Kota lahir" class="mt-1" />
                            </div>
                            <div>
                                <Label>Tanggal Lahir</Label>
                                <Input type="date" v-model="ktpForm.tanggal_lahir" class="mt-1" />
                            </div>
                            <div>
                                <Label>Status Perkawinan</Label>
                                <Select v-model="ktpForm.status_perkawinan">
                                    <SelectTrigger class="mt-1"><SelectValue placeholder="Pilih..." /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="o in STATUS_OPTS" :key="o" :value="o">{{ o }}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <Label>Agama</Label>
                                <Select v-model="ktpForm.agama">
                                    <SelectTrigger class="mt-1"><SelectValue placeholder="Pilih..." /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="o in AGAMA_OPTS" :key="o" :value="o">{{ o }}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <Label>Pekerjaan</Label>
                                <Input v-model="ktpForm.pekerjaan" placeholder="mis. Karyawan Swasta" class="mt-1" />
                            </div>
                            <div>
                                <Label>Golongan Darah</Label>
                                <Select v-model="ktpForm.golongan_darah">
                                    <SelectTrigger class="mt-1"><SelectValue placeholder="Pilih..." /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="o in GOLDAR_OPTS" :key="o" :value="o">{{ o }}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <Label>Kewarganegaraan</Label>
                                <Input v-model="ktpForm.kewarganegaraan" class="mt-1" />
                            </div>
                            <div>
                                <Label>File KTP <span class="text-xs text-muted-foreground font-normal">(opsional)</span></Label>
                                <input
                                    id="ktp_file"
                                    ref="ktpInputRef"
                                    type="file"
                                    accept=".jpg,.jpeg,.png,.pdf"
                                    class="mt-1 block w-full text-sm text-slate-700 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:bg-slate-200 file:text-slate-700 hover:file:bg-slate-300 file:cursor-pointer"
                                    @change="onKtpChange"
                                />
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <Button type="button" @click="submitKtp" :disabled="ktpForm.processing">
                                <Upload class="w-4 h-4 mr-1.5" /> Simpan Anggota
                            </Button>
                        </div>
                    </div>
                    <p v-if="ktpForm.errors.ktp_file" class="text-xs text-red-600">{{ ktpForm.errors.ktp_file }}</p>

                    <div v-if="profile.id_cards.length > 0" class="space-y-2">
                        <div
                            v-for="card in profile.id_cards"
                            :key="card.id"
                            class="flex items-start justify-between p-3 rounded-md bg-white border border-slate-200"
                        >
                            <div class="flex items-start gap-3 min-w-0">
                                <IdCard class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" />
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-slate-900 truncate">
                                        {{ card.nama || card.label || 'Tanpa nama' }}
                                        <span v-if="card.label && card.nama" class="text-xs text-slate-400 font-normal">({{ card.label }})</span>
                                    </p>
                                    <p v-if="card.nomor_ktp" class="text-xs text-slate-500 font-mono">NIK: {{ card.nomor_ktp }}</p>
                                    <p v-if="card.jenis_kelamin || card.tanggal_lahir" class="text-xs text-slate-500">
                                        {{ [card.jenis_kelamin, card.tempat_lahir && card.tanggal_lahir ? (card.tempat_lahir + ', ' + card.tanggal_lahir) : (card.tanggal_lahir || card.tempat_lahir)].filter(Boolean).join(' • ') }}
                                    </p>
                                    <a v-if="card.file_url" :href="card.file_url" target="_blank" class="text-xs text-amber-700 hover:underline">
                                        Lihat / Download KTP ({{ fileExt(card.file_path) }})
                                    </a>
                                    <span v-else class="text-xs text-slate-400">Tanpa scan KTP</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-1 shrink-0">
                                <Button variant="ghost" size="sm" class="text-slate-600 hover:bg-slate-100" @click="openEdit(card)">
                                    <Pencil class="w-4 h-4" />
                                </Button>
                                <Button variant="ghost" size="sm" class="text-red-600 hover:bg-red-50" @click="deleteKtp(card.id)">
                                    <Trash2 class="w-4 h-4" />
                                </Button>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-6 text-sm text-muted-foreground border border-dashed border-slate-200 rounded-md">
                        Belum ada anggota keluarga.
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- ── Dialog Edit Identitas Anggota ── -->
        <Dialog v-model:open="isEditOpen">
            <DialogContent class="max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Edit Data Anggota Keluarga</DialogTitle>
                    <DialogDescription>Data ini dipakai untuk auto-fill Surat Pengantar.</DialogDescription>
                </DialogHeader>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-[60vh] overflow-y-auto px-1">
                    <div>
                        <Label>Nama Lengkap</Label>
                        <Input v-model="editForm.nama" class="mt-1" />
                    </div>
                    <div>
                        <Label>Label (hubungan)</Label>
                        <Input v-model="editForm.label" class="mt-1" />
                    </div>
                    <div>
                        <Label>NIK</Label>
                        <Input v-model="editForm.nomor_ktp" inputmode="numeric" maxlength="32" class="mt-1" />
                    </div>
                    <div>
                        <Label>Jenis Kelamin</Label>
                        <Select v-model="editForm.jenis_kelamin">
                            <SelectTrigger class="mt-1"><SelectValue placeholder="Pilih..." /></SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="o in GENDER_OPTS" :key="o" :value="o">{{ o }}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <Label>Tempat Lahir</Label>
                        <Input v-model="editForm.tempat_lahir" class="mt-1" />
                    </div>
                    <div>
                        <Label>Tanggal Lahir</Label>
                        <Input type="date" v-model="editForm.tanggal_lahir" class="mt-1" />
                    </div>
                    <div>
                        <Label>Status Perkawinan</Label>
                        <Select v-model="editForm.status_perkawinan">
                            <SelectTrigger class="mt-1"><SelectValue placeholder="Pilih..." /></SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="o in STATUS_OPTS" :key="o" :value="o">{{ o }}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <Label>Agama</Label>
                        <Select v-model="editForm.agama">
                            <SelectTrigger class="mt-1"><SelectValue placeholder="Pilih..." /></SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="o in AGAMA_OPTS" :key="o" :value="o">{{ o }}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <Label>Pekerjaan</Label>
                        <Input v-model="editForm.pekerjaan" class="mt-1" />
                    </div>
                    <div>
                        <Label>Golongan Darah</Label>
                        <Select v-model="editForm.golongan_darah">
                            <SelectTrigger class="mt-1"><SelectValue placeholder="Pilih..." /></SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="o in GOLDAR_OPTS" :key="o" :value="o">{{ o }}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <Label>Kewarganegaraan</Label>
                        <Input v-model="editForm.kewarganegaraan" class="mt-1" />
                    </div>
                    <div class="md:col-span-2">
                        <Label>Ganti File KTP <span class="text-xs text-muted-foreground font-normal">(opsional)</span></Label>
                        <div class="mt-1 flex items-center gap-3 text-xs">
                            <a v-if="editCurrentFileUrl" :href="editCurrentFileUrl" target="_blank" class="text-amber-700 hover:underline">
                                File saat ini ({{ fileExt(editCurrentFilePath) }})
                            </a>
                            <span v-else class="text-slate-400">Belum ada file</span>
                        </div>
                        <input
                            ref="editKtpInputRef"
                            type="file"
                            accept=".jpg,.jpeg,.png,.pdf"
                            class="mt-1 block w-full text-sm text-slate-700 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:bg-slate-200 file:text-slate-700 hover:file:bg-slate-300 file:cursor-pointer"
                            @change="onEditKtpChange"
                        />
                        <p class="text-xs text-muted-foreground mt-1">Pilih file untuk mengganti. Kosongkan jika tidak ingin mengubah file.</p>
                        <p v-if="editForm.errors.ktp_file" class="text-xs text-red-600 mt-1">{{ editForm.errors.ktp_file }}</p>
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="isEditOpen = false">Batal</Button>
                    <Button @click="submitEdit" :disabled="editForm.processing">Simpan</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AuthenticatedLayout>
</template>
