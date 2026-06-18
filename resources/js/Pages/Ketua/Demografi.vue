<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription
} from '@/Components/ui/dialog';
import { BarChart3, Users, UserCheck, UserX, Calendar, Info, ChevronRight, ArrowUp, ArrowDown, ArrowUpDown, Church } from 'lucide-vue-next';

const props = defineProps({
    kategori: Array,
    ringkasan: Object,
    agama: Object,
    generated_at: String,
});

// Map warna agama → kelas Tailwind literal (jangan di-generate dinamis biar tidak ke-purge).
const agamaColorMap = {
    emerald: { bg: 'bg-emerald-50', text: 'text-emerald-700', ring: 'border-emerald-200', bar: 'bg-emerald-500' },
    sky:     { bg: 'bg-sky-50',     text: 'text-sky-700',     ring: 'border-sky-200',     bar: 'bg-sky-500' },
    blue:    { bg: 'bg-blue-50',    text: 'text-blue-700',    ring: 'border-blue-200',    bar: 'bg-blue-500' },
    amber:   { bg: 'bg-amber-50',   text: 'text-amber-700',   ring: 'border-amber-200',   bar: 'bg-amber-500' },
    orange:  { bg: 'bg-orange-50',  text: 'text-orange-700',  ring: 'border-orange-200',  bar: 'bg-orange-500' },
    red:     { bg: 'bg-red-50',     text: 'text-red-700',     ring: 'border-red-200',     bar: 'bg-red-500' },
    slate:   { bg: 'bg-slate-100',  text: 'text-slate-700',   ring: 'border-slate-200',   bar: 'bg-slate-400' },
};
const ac = (key) => agamaColorMap[key] || agamaColorMap.slate;
const agamaTotal = computed(() => (props.agama?.data || []).reduce((s, a) => s + a.count, 0));
const agamaPct = (n) => agamaTotal.value > 0 ? Math.round((n / agamaTotal.value) * 100) : 0;

// Map warna kategori → kelas Tailwind literal (jangan di-generate dinamis biar tidak ke-purge).
const colorMap = {
    pink:    { bg: 'bg-pink-50',    text: 'text-pink-700',    ring: 'border-pink-200',    bar: 'bg-pink-500',    dot: 'bg-pink-500' },
    rose:    { bg: 'bg-rose-50',    text: 'text-rose-700',    ring: 'border-rose-200',    bar: 'bg-rose-500',    dot: 'bg-rose-500' },
    amber:   { bg: 'bg-amber-50',   text: 'text-amber-700',   ring: 'border-amber-200',   bar: 'bg-amber-500',   dot: 'bg-amber-500' },
    lime:    { bg: 'bg-lime-50',    text: 'text-lime-700',    ring: 'border-lime-200',    bar: 'bg-lime-500',    dot: 'bg-lime-500' },
    emerald: { bg: 'bg-emerald-50', text: 'text-emerald-700', ring: 'border-emerald-200', bar: 'bg-emerald-500', dot: 'bg-emerald-500' },
    sky:     { bg: 'bg-sky-50',     text: 'text-sky-700',     ring: 'border-sky-200',     bar: 'bg-sky-500',     dot: 'bg-sky-500' },
    violet:  { bg: 'bg-violet-50',  text: 'text-violet-700',  ring: 'border-violet-200',  bar: 'bg-violet-500',  dot: 'bg-violet-500' },
};
const c = (key) => colorMap[key] || colorMap.emerald;

const maxCount = computed(() => Math.max(1, ...props.kategori.map(k => k.count)));
const totalTerdata = computed(() => props.ringkasan.terdata || 0);

const pct = (n) => {
    const t = totalTerdata.value;
    return t > 0 ? Math.round((n / t) * 100) : 0;
};

// Drilldown daftar warga per kategori
const isDetailOpen = ref(false);
const detailCat = ref(null);
const sortKey = ref('umur');   // 'umur' | 'rumah'
const sortDir = ref('asc');    // 'asc' | 'desc'

const openDetail = (cat) => {
    if (!cat.count) return;
    detailCat.value = cat;
    sortKey.value = 'umur';
    sortDir.value = 'asc';
    isDetailOpen.value = true;
};

const toggleSort = (key) => {
    if (sortKey.value === key) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortKey.value = key;
        sortDir.value = 'asc';
    }
};

const sortedPeople = computed(() => {
    const people = [...(detailCat.value?.people || [])];
    const dir = sortDir.value === 'asc' ? 1 : -1;
    people.sort((a, b) => {
        let cmp;
        if (sortKey.value === 'rumah') {
            // natural sort (G2/9 < G10/1), lalu umur sebagai tie-breaker
            cmp = a.rumah.localeCompare(b.rumah, undefined, { numeric: true, sensitivity: 'base' });
            if (cmp === 0) cmp = a.umur_total_bulan - b.umur_total_bulan;
        } else {
            cmp = a.umur_total_bulan - b.umur_total_bulan;
            if (cmp === 0) cmp = a.rumah.localeCompare(b.rumah, undefined, { numeric: true });
        }
        return cmp * dir;
    });
    return people;
});

const fmtUmur = (p) => {
    const th = p.umur_tahun, bl = p.umur_bulan;
    if (th === 0) return `${bl} bln`;
    return bl > 0 ? `${th} th ${bl} bln` : `${th} th`;
};
</script>

<template>
    <Head title="Demografi Warga" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-2">
                <BarChart3 class="w-6 h-6 text-violet-600 shrink-0" />
                <div>
                    <h2 class="text-xl font-bold text-slate-900 leading-tight">Demografi Warga</h2>
                    <p class="text-xs text-slate-500 uppercase tracking-wider font-medium">Statistik Kependudukan RT-44</p>
                </div>
            </div>
        </template>

        <div class="py-6 max-w-7xl mx-auto space-y-6">
            <!-- Info update otomatis -->
            <div class="flex items-center gap-2 text-xs text-slate-500">
                <Calendar class="w-3.5 h-3.5" />
                Data per {{ generated_at }} — kategori usia dihitung otomatis & terbarui setiap hari.
            </div>

            <!-- Ringkasan cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
                    <div class="flex items-center gap-2 text-slate-500 text-xs font-semibold uppercase tracking-wider mb-1">
                        <UserCheck class="w-4 h-4" /> Terdata Usia
                    </div>
                    <div class="text-3xl font-extrabold text-slate-900">{{ ringkasan.terdata }}</div>
                    <p class="text-xs text-slate-400 mt-0.5">dari {{ ringkasan.total_anggota }} anggota terdaftar</p>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
                    <div class="flex items-center gap-2 text-amber-600 text-xs font-semibold uppercase tracking-wider mb-1">
                        <UserX class="w-4 h-4" /> Belum Terdata
                    </div>
                    <div class="text-3xl font-extrabold text-amber-600">{{ ringkasan.belum_terdata }}</div>
                    <p class="text-xs text-slate-400 mt-0.5">tanggal lahir belum diisi</p>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
                    <div class="flex items-center gap-2 text-blue-600 text-xs font-semibold uppercase tracking-wider mb-1">
                        <Users class="w-4 h-4" /> Laki-laki
                    </div>
                    <div class="text-3xl font-extrabold text-blue-600">{{ ringkasan.laki }}</div>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
                    <div class="flex items-center gap-2 text-pink-600 text-xs font-semibold uppercase tracking-wider mb-1">
                        <Users class="w-4 h-4" /> Perempuan
                    </div>
                    <div class="text-3xl font-extrabold text-pink-600">{{ ringkasan.perempuan }}</div>
                </div>
            </div>

            <!-- Peringatan data belum lengkap -->
            <div v-if="ringkasan.belum_terdata > 0" class="flex items-start gap-2 rounded-lg bg-amber-50 border border-amber-200 p-3 text-xs text-amber-800">
                <Info class="w-4 h-4 shrink-0 mt-0.5" />
                <p>
                    <strong>{{ ringkasan.belum_terdata }} anggota</strong> belum memiliki tanggal lahir, sehingga belum masuk kategori usia.
                    Lengkapi tanggal lahir di <strong>Data Warga → Profil</strong> agar statistik akurat.
                </p>
            </div>

            <!-- Kartu kategori usia -->
            <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4">
                <button
                    v-for="cat in kategori"
                    :key="cat.key"
                    type="button"
                    @click="openDetail(cat)"
                    class="text-left bg-white rounded-xl border shadow-sm p-4 transition-all hover:shadow-md"
                    :class="[c(cat.color).ring, cat.count ? 'hover:-translate-y-0.5 cursor-pointer' : 'opacity-70 cursor-default']"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="inline-flex items-center gap-1.5 text-sm font-bold uppercase tracking-wider px-2.5 py-1 rounded-full" :class="[c(cat.color).bg, c(cat.color).text]">
                            {{ cat.label }}
                        </span>
                        <ChevronRight v-if="cat.count" class="w-5 h-5 text-slate-300" />
                    </div>
                    <div class="flex items-baseline gap-1.5">
                        <span class="text-4xl font-extrabold text-slate-900">{{ cat.count }}</span>
                        <span class="text-sm text-slate-500">jiwa · {{ pct(cat.count) }}%</span>
                    </div>
                    <p class="text-sm text-slate-500 mt-0.5">{{ cat.desc }}</p>
                    <!-- progress bar -->
                    <div class="mt-3 h-2 w-full rounded-full bg-slate-100 overflow-hidden">
                        <div class="h-full rounded-full transition-all" :class="c(cat.color).bar" :style="{ width: (cat.count / maxCount * 100) + '%' }"></div>
                    </div>
                    <!-- gender split -->
                    <div class="mt-2.5 flex items-center gap-4 text-sm font-medium text-slate-600">
                        <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>L {{ cat.L }}</span>
                        <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-pink-500"></span>P {{ cat.P }}</span>
                        <span v-if="cat.lainnya" class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-slate-400"></span>? {{ cat.lainnya }}</span>
                    </div>
                </button>
            </div>

            <!-- Komposisi Agama -->
            <div v-if="agama" class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="flex items-center gap-2 text-sm font-bold text-slate-800">
                        <Church class="w-4 h-4 text-violet-600" /> Komposisi Agama
                    </h3>
                    <span class="text-xs text-slate-400">{{ agamaTotal }} dari {{ agama.total }} anggota</span>
                </div>

                <div v-if="agama.data.length" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                    <div
                        v-for="a in agama.data"
                        :key="a.label"
                        class="rounded-xl border p-4"
                        :class="ac(a.color).ring"
                    >
                        <span class="inline-flex items-center text-xs font-bold uppercase tracking-wider px-2.5 py-1 rounded-full" :class="[ac(a.color).bg, ac(a.color).text]">
                            {{ a.label }}
                        </span>
                        <div class="flex items-baseline gap-1.5 mt-2">
                            <span class="text-3xl font-extrabold text-slate-900">{{ a.count }}</span>
                            <span class="text-sm text-slate-500">jiwa · {{ agamaPct(a.count) }}%</span>
                        </div>
                        <div class="mt-3 h-2 w-full rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-full transition-all" :class="ac(a.color).bar" :style="{ width: agamaPct(a.count) + '%' }"></div>
                        </div>
                    </div>
                </div>
                <p v-else class="text-sm text-slate-400">Belum ada data agama yang terisi.</p>

                <p v-if="agama.belum_terdata > 0" class="mt-3 text-xs text-slate-400">
                    <Info class="inline w-3.5 h-3.5 -mt-0.5" />
                    {{ agama.belum_terdata }} anggota belum mengisi data agama.
                </p>
            </div>

            <!-- Bar chart perbandingan -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                <h3 class="text-sm font-bold text-slate-800 mb-4">Distribusi Kategori Usia</h3>
                <div class="space-y-3">
                    <div v-for="cat in kategori" :key="cat.key" class="flex items-center gap-3">
                        <div class="w-24 shrink-0 text-xs font-semibold text-slate-600 text-right">{{ cat.label }}</div>
                        <div class="flex-1 h-6 rounded bg-slate-50 overflow-hidden">
                            <div
                                class="h-full rounded flex items-center justify-end px-2 transition-all"
                                :class="c(cat.color).bar"
                                :style="{ width: Math.max(cat.count / maxCount * 100, cat.count ? 6 : 0) + '%' }"
                            >
                                <span v-if="cat.count" class="text-[11px] font-bold text-white">{{ cat.count }}</span>
                            </div>
                        </div>
                        <div class="w-10 shrink-0 text-xs text-slate-400">{{ pct(cat.count) }}%</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Drilldown daftar warga per kategori -->
        <Dialog v-model:open="isDetailOpen">
            <DialogContent class="sm:max-w-[520px] max-h-[80vh] flex flex-col">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <span v-if="detailCat" class="w-3 h-3 rounded-full" :class="c(detailCat.color).dot"></span>
                        {{ detailCat?.label }} — {{ detailCat?.count }} jiwa
                    </DialogTitle>
                    <DialogDescription>{{ detailCat?.desc }}</DialogDescription>
                </DialogHeader>
                <p class="text-[11px] text-slate-400 -mt-1">Klik judul kolom <strong>Umur</strong> atau <strong>Rumah</strong> untuk mengurutkan.</p>
                <div class="overflow-y-auto -mx-2 px-2">
                    <table class="w-full text-sm">
                        <thead class="text-[11px] uppercase tracking-wider text-slate-400 border-b sticky top-0 bg-white">
                            <tr>
                                <th class="text-left py-2 font-semibold">Nama</th>
                                <th class="text-center py-2 font-semibold">JK</th>
                                <th class="text-center py-2 font-semibold">
                                    <button type="button" class="inline-flex items-center gap-1 hover:text-slate-700 uppercase tracking-wider" @click="toggleSort('umur')">
                                        Umur
                                        <component :is="sortKey === 'umur' ? (sortDir === 'asc' ? ArrowUp : ArrowDown) : ArrowUpDown" class="w-3 h-3" :class="sortKey === 'umur' ? 'text-violet-600' : 'text-slate-300'" />
                                    </button>
                                </th>
                                <th class="text-right py-2 font-semibold">
                                    <button type="button" class="inline-flex items-center gap-1 hover:text-slate-700 uppercase tracking-wider ml-auto" @click="toggleSort('rumah')">
                                        Rumah
                                        <component :is="sortKey === 'rumah' ? (sortDir === 'asc' ? ArrowUp : ArrowDown) : ArrowUpDown" class="w-3 h-3" :class="sortKey === 'rumah' ? 'text-violet-600' : 'text-slate-300'" />
                                    </button>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="(p, i) in sortedPeople" :key="i" :class="sortKey === 'rumah' ? 'hover:bg-violet-50/40' : ''">
                                <td class="py-2 text-slate-800">
                                    {{ p.nama }}
                                    <span class="text-[10px] text-slate-400">· {{ p.slot }}</span>
                                </td>
                                <td class="py-2 text-center">
                                    <span class="text-xs font-semibold" :class="p.jk === 'L' ? 'text-blue-600' : (p.jk === 'P' ? 'text-pink-600' : 'text-slate-400')">
                                        {{ p.jk || '?' }}
                                    </span>
                                </td>
                                <td class="py-2 text-center text-slate-600 whitespace-nowrap">{{ fmtUmur(p) }}</td>
                                <td class="py-2 text-right font-mono text-xs text-slate-500">{{ p.rumah }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </DialogContent>
        </Dialog>
    </AuthenticatedLayout>
</template>
