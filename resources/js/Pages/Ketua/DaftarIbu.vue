<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Card, CardContent } from '@/Components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Users, Search, X, FileDown, Home, CheckCircle2, AlertTriangle, HelpCircle, ChevronDown, ChevronRight } from 'lucide-vue-next';

const props = defineProps({
    rows: Array,
});

// Lingkup rumah yang ditampilkan. Default: berpenghuni (yang relevan untuk PKK).
const scope = ref('berpenghuni');
const scopeOptions = [
    { value: 'berpenghuni', label: 'Berpenghuni' },
    { value: 'kosong', label: 'Rumah Kosong' },
    { value: 'semua', label: 'Semua' },
];

const scopedRows = computed(() => {
    if (scope.value === 'semua') return props.rows;
    const mau = scope.value === 'berpenghuni';
    return props.rows.filter((r) => (r.status_huni === 'berpenghuni') === mau);
});

const summary = computed(() => {
    const s = { total: 0, ada_istri: 0, kk_perempuan: 0, perempuan_lain: 0, kk_saja: 0, belum_didata: 0 };
    for (const r of scopedRows.value) {
        s.total++;
        s[r.status]++;
    }
    return s;
});

const exportExcel = () => {
    window.location.href = route('ketua.daftar-ibu.export-excel', { scope: scope.value });
};

const searchQuery = ref('');
// null = tampilkan semua; selain itu filter by status baris.
const statusFilter = ref(null);

const toggleFilter = (status) => {
    statusFilter.value = statusFilter.value === status ? null : status;
};

// Kepala keluarga perempuan (janda/ibu tunggal) sudah dianggap lengkap —
// memang tidak ada data istri terpisah untuk rumah itu.
const isLengkap = (r) => r.status === 'ada_istri' || r.status === 'kk_perempuan';

const filteredRows = computed(() => {
    const q = searchQuery.value.toLowerCase().trim();
    return scopedRows.value.filter((r) => {
        if (statusFilter.value === 'lengkap' && !isLengkap(r)) return false;
        if (statusFilter.value === 'perlu_lengkapi' && isLengkap(r)) return false;
        if (!q) return true;
        return r.rumah.toLowerCase().includes(q)
            || r.nama.toLowerCase().includes(q)
            || (r.kepala_keluarga || '').toLowerCase().includes(q)
            || r.anggota.some((a) => a.nama.toLowerCase().includes(q));
    });
});

// Baris mana yang daftar anggotanya sedang dibuka.
const expanded = ref(new Set());
const toggleRow = (id) => {
    const next = new Set(expanded.value);
    next.has(id) ? next.delete(id) : next.add(id);
    expanded.value = next;
};

const statusBadge = (status) => {
    if (status === 'ada_istri') return { label: 'Istri', cls: 'bg-pink-50 text-pink-700 border-pink-200' };
    if (status === 'kk_perempuan') return { label: 'KK Perempuan', cls: 'bg-fuchsia-50 text-fuchsia-700 border-fuchsia-200' };
    if (status === 'perempuan_lain') return { label: 'Anggota perempuan', cls: 'bg-amber-50 text-amber-700 border-amber-200' };
    if (status === 'kk_saja') return { label: 'Kepala Keluarga', cls: 'bg-orange-50 text-orange-700 border-orange-200' };
    return { label: 'Akun warga', cls: 'bg-slate-50 text-slate-600 border-slate-200' };
};
</script>

<template>
    <Head title="Daftar Ibu per Rumah" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 flex items-center gap-2">
                        <Users class="w-6 h-6 text-pink-600" />
                        Daftar Ibu per Rumah
                    </h2>
                    <p class="text-slate-500 mt-1 uppercase text-sm tracking-wider font-medium">
                        Rekap Nama Ibu-Ibu RT-44 untuk Kegiatan PKK
                    </p>
                </div>
                <Button variant="outline" size="sm" @click="exportExcel" class="bg-white hover:bg-slate-50 border-pink-200 text-pink-700">
                    <FileDown class="w-4 h-4 mr-2" />
                    Download Excel
                </Button>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                <!-- Lingkup rumah -->
                <div class="inline-flex rounded-lg border border-slate-200 bg-white p-1">
                    <button
                        v-for="opt in scopeOptions"
                        :key="opt.value"
                        @click="scope = opt.value"
                        class="px-4 py-1.5 text-sm rounded-md transition-colors"
                        :class="scope === opt.value
                            ? 'bg-pink-600 text-white font-semibold'
                            : 'text-slate-600 hover:bg-slate-50'"
                    >
                        {{ opt.label }}
                    </button>
                </div>

                <!-- Ringkasan; kartu bisa diklik untuk memfilter tabel -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <Card>
                        <CardContent class="pt-6">
                            <div class="flex items-center gap-2 text-muted-foreground text-xs font-medium uppercase tracking-wide mb-1">
                                <Home class="w-4 h-4" />
                                {{ scope === 'kosong' ? 'Rumah Kosong' : (scope === 'semua' ? 'Total Rumah' : 'Rumah Berpenghuni') }}
                            </div>
                            <p class="text-2xl font-bold text-slate-900">{{ summary.total }}</p>
                        </CardContent>
                    </Card>
                    <Card
                        class="cursor-pointer transition-colors"
                        :class="statusFilter === 'lengkap' ? 'ring-2 ring-pink-400' : 'hover:bg-slate-50'"
                        @click="toggleFilter('lengkap')"
                    >
                        <CardContent class="pt-6">
                            <div class="flex items-center gap-2 text-muted-foreground text-xs font-medium uppercase tracking-wide mb-1">
                                <CheckCircle2 class="w-4 h-4" /> Nama Ibu Valid
                            </div>
                            <p class="text-2xl font-bold text-pink-600">{{ summary.ada_istri + summary.kk_perempuan }}</p>
                            <p class="text-[11px] text-muted-foreground mt-0.5">
                                {{ summary.ada_istri }} istri &middot; {{ summary.kk_perempuan }} KK perempuan
                            </p>
                        </CardContent>
                    </Card>
                    <Card
                        class="cursor-pointer transition-colors"
                        :class="statusFilter === 'perlu_lengkapi' ? 'ring-2 ring-amber-400' : 'hover:bg-slate-50'"
                        @click="toggleFilter('perlu_lengkapi')"
                    >
                        <CardContent class="pt-6">
                            <div class="flex items-center gap-2 text-muted-foreground text-xs font-medium uppercase tracking-wide mb-1">
                                <AlertTriangle class="w-4 h-4" /> Perlu Dilengkapi
                            </div>
                            <p class="text-2xl font-bold text-amber-600">
                                {{ summary.perempuan_lain + summary.kk_saja + summary.belum_didata }}
                            </p>
                            <p class="text-[11px] text-muted-foreground mt-0.5">
                                nama diambil dari sumber lain
                            </p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent class="pt-6">
                            <div class="flex items-center gap-2 text-muted-foreground text-xs font-medium uppercase tracking-wide mb-1">
                                <HelpCircle class="w-4 h-4" /> Belum Didata
                            </div>
                            <p class="text-2xl font-bold text-slate-500">{{ summary.belum_didata }}</p>
                        </CardContent>
                    </Card>
                </div>

                <div class="rounded-lg border border-pink-100 bg-pink-50/60 px-4 py-3 text-xs text-pink-900">
                    Nama yang ditampilkan diambil dari anggota keluarga berlabel <strong>"Istri"</strong>.
                    Kalau rumah itu belum punya data istri, sistem memakai nama Kepala Keluarga (atau anggota lain)
                    dan barisnya ditandai kuning — silakan lengkapi lewat menu Data Warga &rarr; Profil.
                    <span class="block mt-1">
                        <strong>Kontak Keluarga</strong> diambil dari nomor akun warga (pemilik/penyewa rumah),
                        jadi <strong>belum tentu nomor si ibu</strong> — bisa jadi nomor suami. Nama pemegang nomor
                        ditulis di bawahnya supaya jelas.
                    </span>
                    <span v-if="scope !== 'berpenghuni'" class="block mt-1">
                        Rumah kosong yang masih punya anggota keluarga terdata biasanya berarti status huni belum di-update
                        atau datanya sisa penghuni lama — perlu dicek.
                    </span>
                </div>

                <Card>
                    <CardContent class="pt-6 space-y-4">
                        <!-- Search -->
                        <div class="relative max-w-md">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                            <Input v-model="searchQuery" placeholder="Cari rumah atau nama..." class="pl-9 pr-9" />
                            <button
                                v-if="searchQuery"
                                @click="searchQuery = ''"
                                aria-label="Clear search"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                            >
                                <X class="w-4 h-4" />
                            </button>
                        </div>

                        <p class="text-xs text-muted-foreground">
                            Menampilkan {{ filteredRows.length }} dari {{ scopedRows.length }} rumah.
                        </p>

                        <div class="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead class="w-12 text-center">No</TableHead>
                                        <TableHead class="w-24 text-center">Rumah</TableHead>
                                        <TableHead>Nama Ibu</TableHead>
                                        <TableHead class="w-40">Sumber Data</TableHead>
                                        <TableHead>Kepala Keluarga</TableHead>
                                        <TableHead class="w-44">
                                            Kontak Keluarga
                                            <span class="block text-[10px] font-normal normal-case text-slate-400">
                                                nomor akun warga
                                            </span>
                                        </TableHead>
                                        <TableHead class="w-28 text-center">Anggota</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <template v-for="(r, i) in filteredRows" :key="r.id">
                                        <TableRow
                                            class="cursor-pointer"
                                            :class="!isLengkap(r) ? 'bg-amber-50/60 hover:bg-amber-50' : 'hover:bg-slate-50'"
                                            @click="toggleRow(r.id)"
                                        >
                                            <TableCell class="text-center text-muted-foreground">{{ i + 1 }}</TableCell>
                                            <TableCell class="text-center font-semibold">
                                                {{ r.rumah }}
                                                <span
                                                    v-if="scope !== 'berpenghuni' && r.status_huni !== 'berpenghuni'"
                                                    class="block text-[10px] font-normal text-slate-400 uppercase tracking-wide"
                                                >
                                                    kosong
                                                </span>
                                            </TableCell>
                                            <TableCell>
                                                <div class="font-medium text-slate-900">{{ r.nama }}</div>
                                                <div v-if="r.keterangan" class="text-[11px] text-amber-700 mt-0.5">
                                                    {{ r.keterangan }}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant="outline" :class="statusBadge(r.status).cls + ' text-[10px] px-1.5 py-0'">
                                                    {{ statusBadge(r.status).label }}
                                                </Badge>
                                            </TableCell>
                                            <TableCell class="text-slate-600">{{ r.kepala_keluarga || '-' }}</TableCell>
                                            <TableCell class="text-slate-600">
                                                <template v-if="r.kontak">
                                                    <div>{{ r.kontak }}</div>
                                                    <!-- Nomor ini milik pemegang akun, bukan otomatis nomor si ibu. -->
                                                    <div class="text-[11px] text-slate-400 truncate">
                                                        a.n. {{ r.kontak_nama }} ({{ r.kontak_slot }})
                                                    </div>
                                                </template>
                                                <span v-else>-</span>
                                            </TableCell>
                                            <TableCell class="text-center">
                                                <span class="inline-flex items-center gap-1 text-slate-600">
                                                    <ChevronDown v-if="expanded.has(r.id)" class="w-4 h-4" />
                                                    <ChevronRight v-else class="w-4 h-4" />
                                                    {{ r.jumlah_anggota }}
                                                </span>
                                            </TableCell>
                                        </TableRow>
                                        <TableRow v-if="expanded.has(r.id)" class="bg-slate-50/80">
                                            <TableCell :colspan="7" class="py-3">
                                                <p v-if="!r.anggota.length" class="text-xs text-muted-foreground italic">
                                                    Belum ada anggota keluarga yang didata untuk rumah ini.
                                                </p>
                                                <ul v-else class="grid sm:grid-cols-2 lg:grid-cols-3 gap-1.5">
                                                    <li
                                                        v-for="a in r.anggota"
                                                        :key="a.id"
                                                        class="text-xs flex items-center gap-2 bg-white rounded px-2.5 py-1.5 border border-slate-200"
                                                    >
                                                        <span
                                                            class="shrink-0 w-5 h-5 rounded-full text-[10px] font-bold flex items-center justify-center"
                                                            :class="a.jenis_kelamin === 'P' ? 'bg-pink-100 text-pink-700' : (a.jenis_kelamin === 'L' ? 'bg-sky-100 text-sky-700' : 'bg-slate-100 text-slate-500')"
                                                        >
                                                            {{ a.jenis_kelamin || '?' }}
                                                        </span>
                                                        <span class="font-medium text-slate-800 truncate">{{ a.nama }}</span>
                                                        <span class="text-slate-400 ml-auto shrink-0">{{ a.label }}</span>
                                                    </li>
                                                </ul>
                                            </TableCell>
                                        </TableRow>
                                    </template>
                                    <TableRow v-if="!filteredRows.length">
                                        <TableCell :colspan="7" class="text-center text-muted-foreground py-10">
                                            Tidak ada rumah yang cocok dengan pencarian.
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
