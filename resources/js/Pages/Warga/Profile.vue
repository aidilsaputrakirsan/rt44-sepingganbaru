<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Paperclip, FileText, Trash2, Upload, IdCard, Home, User, Mail, Phone, Users } from 'lucide-vue-next';

const props = defineProps({
    profile: Object,
    readonly_info: Object,
});

const form = useForm({
    jumlah_anggota_keluarga: props.profile.jumlah_anggota_keluarga ?? '',
});

const kkForm = useForm({ kk_file: null });
const ktpForm = useForm({ label: '', ktp_file: null });

const kkInputRef = ref(null);
const ktpInputRef = ref(null);

const submitProfile = () => {
    form.post(route('profil.update'), { preserveScroll: true });
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
    kkForm.post(route('profil.kk.upload'), {
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
    router.delete(route('profil.kk.delete'), { preserveScroll: true });
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
    if (!ktpForm.ktp_file) {
        alert('Pilih file KTP terlebih dahulu.');
        return;
    }
    ktpForm.post(route('profil.ktp.upload'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            ktpForm.reset();
            if (ktpInputRef.value) ktpInputRef.value.value = '';
        },
    });
};

const deleteKtp = (id) => {
    if (!confirm('Hapus KTP ini?')) return;
    router.delete(route('profil.ktp.delete', { idCard: id }), { preserveScroll: true });
};

const statusHuniLabel = computed(() => {
    if (!props.readonly_info.status_huni) return '-';
    return props.readonly_info.status_huni === 'berpenghuni' ? 'Berpenghuni' : 'Kosong';
});

const fileExt = (path) => (path?.split('.').pop() || '').toUpperCase();
</script>

<template>
    <Head title="Profil Warga" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Profil Warga</h2>
        </template>

        <div class="py-6 max-w-5xl mx-auto space-y-6">
            <!-- Info Dasar (readonly) -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-base">
                        <User class="w-5 h-5 text-amber-500" />
                        Data Dasar
                    </CardTitle>
                    <CardDescription>
                        Data ini diambil dari sistem. Untuk mengubah, silakan hubungi admin RT.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <Label class="text-xs uppercase tracking-wide text-muted-foreground flex items-center gap-1.5">
                                <Home class="w-3.5 h-3.5" /> Nomor Rumah
                            </Label>
                            <div class="mt-1 px-3 py-2 rounded-md bg-slate-50 border border-slate-200 text-sm">
                                {{ readonly_info.no_rumah }}
                            </div>
                        </div>
                        <div>
                            <Label class="text-xs uppercase tracking-wide text-muted-foreground flex items-center gap-1.5">
                                <User class="w-3.5 h-3.5" /> Nama Kepala Keluarga
                            </Label>
                            <div class="mt-1 px-3 py-2 rounded-md bg-slate-50 border border-slate-200 text-sm">
                                {{ readonly_info.name || '-' }}
                            </div>
                        </div>
                        <div>
                            <Label class="text-xs uppercase tracking-wide text-muted-foreground flex items-center gap-1.5">
                                <Phone class="w-3.5 h-3.5" /> Nomor HP/WhatsApp
                            </Label>
                            <div class="mt-1 px-3 py-2 rounded-md bg-slate-50 border border-slate-200 text-sm">
                                {{ readonly_info.phone_number || '-' }}
                            </div>
                        </div>
                        <div>
                            <Label class="text-xs uppercase tracking-wide text-muted-foreground flex items-center gap-1.5">
                                <Mail class="w-3.5 h-3.5" /> Email
                            </Label>
                            <div class="mt-1 px-3 py-2 rounded-md bg-slate-50 border border-slate-200 text-sm">
                                {{ readonly_info.email || '-' }}
                            </div>
                        </div>
                        <div>
                            <Label class="text-xs uppercase tracking-wide text-muted-foreground flex items-center gap-1.5">
                                <Home class="w-3.5 h-3.5" /> Status Rumah
                            </Label>
                            <div class="mt-1 px-3 py-2 rounded-md bg-slate-50 border border-slate-200 text-sm">
                                {{ statusHuniLabel }}
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Data tambahan -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-base">
                        <Users class="w-5 h-5 text-amber-500" />
                        Data Tambahan
                    </CardTitle>
                    <CardDescription>Lengkapi data berikut untuk kebutuhan administrasi RT.</CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submitProfile" class="space-y-4">
                        <div>
                            <Label for="jumlah_anggota_keluarga">Jumlah Anggota Keluarga</Label>
                            <Input
                                id="jumlah_anggota_keluarga"
                                v-model="form.jumlah_anggota_keluarga"
                                type="number"
                                min="0"
                                max="50"
                                placeholder="0"
                                class="mt-1 max-w-xs"
                            />
                            <p v-if="form.errors.jumlah_anggota_keluarga" class="text-xs text-red-600 mt-1">
                                {{ form.errors.jumlah_anggota_keluarga }}
                            </p>
                        </div>
                        <div class="flex justify-end">
                            <Button type="submit" :disabled="form.processing">Simpan</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <!-- Kartu Keluarga -->
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
                    <!-- Form tambah KTP -->
                    <div class="grid grid-cols-1 md:grid-cols-[1fr_1fr_auto] gap-3 items-end p-4 rounded-lg bg-slate-50 border border-slate-200">
                        <div>
                            <Label for="ktp_label">Label (opsional)</Label>
                            <Input
                                id="ktp_label"
                                v-model="ktpForm.label"
                                placeholder="mis. Kepala Keluarga, Istri, Anak 1"
                                class="mt-1"
                            />
                        </div>
                        <div>
                            <Label for="ktp_file">File KTP</Label>
                            <input
                                id="ktp_file"
                                ref="ktpInputRef"
                                type="file"
                                accept=".jpg,.jpeg,.png,.pdf"
                                class="mt-1 block w-full text-sm text-slate-700 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:bg-slate-200 file:text-slate-700 hover:file:bg-slate-300 file:cursor-pointer"
                                @change="onKtpChange"
                            />
                        </div>
                        <Button type="button" @click="submitKtp" :disabled="ktpForm.processing">
                            <Upload class="w-4 h-4 mr-1.5" /> Upload
                        </Button>
                    </div>
                    <p v-if="ktpForm.errors.ktp_file" class="text-xs text-red-600">{{ ktpForm.errors.ktp_file }}</p>
                    <p v-if="ktpForm.errors.label" class="text-xs text-red-600">{{ ktpForm.errors.label }}</p>
                    <p v-if="ktpForm.progress" class="text-xs text-muted-foreground">
                        Mengunggah... {{ ktpForm.progress.percentage }}%
                    </p>

                    <!-- Daftar KTP -->
                    <div v-if="profile.id_cards.length > 0" class="space-y-2">
                        <div
                            v-for="card in profile.id_cards"
                            :key="card.id"
                            class="flex items-center justify-between p-3 rounded-md bg-white border border-slate-200"
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <IdCard class="w-5 h-5 text-amber-600 shrink-0" />
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-slate-900 truncate">
                                        {{ card.label || 'KTP tanpa label' }}
                                    </p>
                                    <a :href="card.file_url" target="_blank" class="text-xs text-amber-700 hover:underline">
                                        Lihat / Download ({{ fileExt(card.file_path) }})
                                    </a>
                                </div>
                            </div>
                            <Button variant="ghost" size="sm" class="text-red-600 hover:bg-red-50" @click="deleteKtp(card.id)">
                                <Trash2 class="w-4 h-4" />
                            </Button>
                        </div>
                    </div>
                    <div v-else class="text-center py-6 text-sm text-muted-foreground">
                        Belum ada KTP terunggah.
                    </div>
                </CardContent>
            </Card>
        </div>
    </AuthenticatedLayout>
</template>
