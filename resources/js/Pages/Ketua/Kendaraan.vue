<script setup>
import { ref, computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Car, Bike, Search, X, Link2, Copy, Check, Home, CheckCircle2 } from 'lucide-vue-next';

const props = defineProps({
    houses: Array,
    ringkasan: Object,
});

const page = usePage();
const flash = computed(() => page.props.flash || {});
const showFlash = ref(true);

const publicLink = route('kendaraan.create');
const copied = ref(false);
const copyLink = async () => {
    try {
        await navigator.clipboard.writeText(publicLink);
        copied.value = true;
        setTimeout(() => { copied.value = false; }, 2000);
    } catch (e) {
        // Clipboard API mungkin tidak tersedia (mis. non-HTTPS); abaikan diam-diam.
    }
};

const searchQuery = ref('');
const filteredHouses = computed(() => {
    const q = searchQuery.value.toLowerCase().trim();
    if (!q) return props.houses;
    return props.houses.filter(h =>
        h.rumah.toLowerCase().includes(q) || h.nama.toLowerCase().includes(q)
    );
});
</script>

<template>
    <Head title="Data Kendaraan Warga" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-2xl font-bold text-slate-900 flex items-center gap-2">
                    <Car class="w-6 h-6 text-amber-600" />
                    Data Kendaraan Warga
                </h2>
                <p class="text-slate-500 mt-1 uppercase text-sm tracking-wider font-medium">
                    Rekap Pendataan Kendaraan untuk Stiker RT
                </p>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                <!-- Flash Message -->
                <div v-if="showFlash && (flash.success || flash.error)" class="rounded-lg px-4 py-3 text-sm flex items-center justify-between" :class="flash.success ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'">
                    <span>{{ flash.success || flash.error }}</span>
                    <button @click="showFlash = false" class="ml-2 text-current opacity-60 hover:opacity-100">&times;</button>
                </div>

                <!-- Share Link -->
                <Card>
                    <CardContent class="pt-6">
                        <div class="flex items-center gap-2 mb-2">
                            <Link2 class="w-4 h-4 text-amber-600" />
                            <span class="text-sm font-semibold">Bagikan Link Pendataan ke Warga</span>
                        </div>
                        <p class="text-xs text-muted-foreground mb-3">
                            Warga membuka link ini, memilih rumahnya, lalu mengisi jumlah kendaraan — tanpa perlu login.
                        </p>
                        <div class="flex flex-col sm:flex-row gap-2">
                            <Input :model-value="publicLink" readonly class="text-sm bg-slate-50" @focus="$event.target.select()" />
                            <Button @click="copyLink" class="shrink-0 sm:w-auto w-full" :class="copied ? 'bg-emerald-600 hover:bg-emerald-700' : ''">
                                <Check v-if="copied" class="w-4 h-4 mr-1.5" />
                                <Copy v-else class="w-4 h-4 mr-1.5" />
                                {{ copied ? 'Tersalin' : 'Salin Link' }}
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <!-- Summary Tiles -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <Card>
                        <CardContent class="pt-6">
                            <div class="flex items-center gap-2 text-muted-foreground text-xs font-medium uppercase tracking-wide mb-1">
                                <Home class="w-4 h-4" /> Total Rumah
                            </div>
                            <p class="text-2xl font-bold text-slate-900">{{ ringkasan.total_rumah }}</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent class="pt-6">
                            <div class="flex items-center gap-2 text-muted-foreground text-xs font-medium uppercase tracking-wide mb-1">
                                <CheckCircle2 class="w-4 h-4" /> Sudah Mengisi
                            </div>
                            <p class="text-2xl font-bold text-emerald-600">{{ ringkasan.sudah_isi }} <span class="text-sm font-normal text-muted-foreground">/ {{ ringkasan.total_rumah }}</span></p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent class="pt-6">
                            <div class="flex items-center gap-2 text-muted-foreground text-xs font-medium uppercase tracking-wide mb-1">
                                <Car class="w-4 h-4" /> Total Mobil
                            </div>
                            <p class="text-2xl font-bold text-slate-900">{{ ringkasan.total_mobil }}</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent class="pt-6">
                            <div class="flex items-center gap-2 text-muted-foreground text-xs font-medium uppercase tracking-wide mb-1">
                                <Bike class="w-4 h-4" /> Total Motor
                            </div>
                            <p class="text-2xl font-bold text-slate-900">{{ ringkasan.total_motor }}</p>
                        </CardContent>
                    </Card>
                </div>

                <!-- Table -->
                <Card>
                    <CardHeader>
                        <CardTitle>Daftar Rumah</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="mb-4 relative max-w-xs">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                            <Input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Cari rumah atau nama..."
                                class="pl-9 pr-8 h-9 text-sm"
                            />
                            <button v-if="searchQuery" @click="searchQuery = ''" class="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground">
                                <X class="w-4 h-4" />
                            </button>
                        </div>

                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Rumah</TableHead>
                                    <TableHead>Nama</TableHead>
                                    <TableHead class="text-center">Mobil</TableHead>
                                    <TableHead class="text-center">Motor</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Diisi Pada</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="h in filteredHouses" :key="h.rumah">
                                    <TableCell class="font-medium">{{ h.rumah }}</TableCell>
                                    <TableCell>{{ h.nama }}</TableCell>
                                    <TableCell class="text-center">{{ h.jumlah_mobil }}</TableCell>
                                    <TableCell class="text-center">{{ h.jumlah_motor }}</TableCell>
                                    <TableCell>
                                        <Badge :class="h.sudah_isi ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'">
                                            {{ h.sudah_isi ? 'Sudah Isi' : 'Belum Isi' }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="text-sm text-muted-foreground">{{ h.diisi_pada || '-' }}</TableCell>
                                </TableRow>
                                <TableRow v-if="filteredHouses.length === 0">
                                    <TableCell colspan="6" class="text-center text-muted-foreground py-6">
                                        Tidak ada data yang cocok.
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
