<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Label } from '@/Components/ui/label';
import { ArrowLeft, FileText, IdCard, Home, User, Mail, Phone, Users } from 'lucide-vue-next';

const props = defineProps({
    house: Object,
    owner: Object,
    profile: Object,
});

const statusHuniLabel = (s) => s === 'berpenghuni' ? 'Berpenghuni' : (s === 'kosong' ? 'Kosong' : '-');
const residentStatusLabel = (s) => {
    if (s === 'pemilik') return 'Pemilik';
    if (s === 'kontrak') return 'Kontrak';
    return 'Belum Diketahui';
};
const fileExt = (path) => (path?.split('.').pop() || '').toUpperCase();
</script>

<template>
    <Head :title="`Profil Warga - ${house.blok}/${house.nomor}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Profil Warga — {{ house.blok }}/{{ house.nomor }}
                </h2>
                <Button variant="outline" as-child>
                    <Link :href="route('admin.warga.index')">
                        <ArrowLeft class="w-4 h-4 mr-1.5" />
                        Kembali ke Data Warga
                    </Link>
                </Button>
            </div>
        </template>

        <div class="py-6 max-w-5xl mx-auto space-y-6">
            <!-- Data dasar -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-base">
                        <User class="w-5 h-5 text-amber-500" />
                        Data Dasar
                    </CardTitle>
                    <CardDescription>Untuk mengubah, gunakan menu Data Warga.</CardDescription>
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
                                <User class="w-3.5 h-3.5" /> Nama Kepala Keluarga
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
                        <div>
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

            <!-- Data tambahan -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-base">
                        <Users class="w-5 h-5 text-amber-500" />
                        Data Tambahan
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div>
                        <Label class="text-xs uppercase tracking-wide text-muted-foreground">Jumlah Anggota Keluarga</Label>
                        <div class="mt-1 px-3 py-2 rounded-md bg-slate-50 border border-slate-200 text-sm max-w-xs">
                            {{ profile.jumlah_anggota_keluarga ?? '-' }} {{ profile.jumlah_anggota_keluarga ? 'orang' : '' }}
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- KK -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-base">
                        <FileText class="w-5 h-5 text-amber-500" />
                        Kartu Keluarga (KK)
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="profile.kk_url" class="flex items-center justify-between p-3 rounded-md bg-emerald-50 border border-emerald-200">
                        <div class="flex items-center gap-3 min-w-0">
                            <FileText class="w-5 h-5 text-emerald-600 shrink-0" />
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-emerald-900">Kartu Keluarga terunggah</p>
                                <a :href="profile.kk_url" target="_blank" class="text-xs text-emerald-700 hover:underline">
                                    Lihat / Download ({{ fileExt(profile.kk_path) }})
                                </a>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-6 text-sm text-muted-foreground border border-dashed border-slate-200 rounded-md">
                        Warga belum mengunggah Kartu Keluarga.
                    </div>
                </CardContent>
            </Card>

            <!-- KTP -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-base">
                        <IdCard class="w-5 h-5 text-amber-500" />
                        KTP Anggota Keluarga
                    </CardTitle>
                    <CardDescription>{{ profile.id_cards.length }} dokumen terunggah.</CardDescription>
                </CardHeader>
                <CardContent>
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
                                        Lihat / Download
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-6 text-sm text-muted-foreground border border-dashed border-slate-200 rounded-md">
                        Warga belum mengunggah KTP.
                    </div>
                </CardContent>
            </Card>
        </div>
    </AuthenticatedLayout>
</template>
