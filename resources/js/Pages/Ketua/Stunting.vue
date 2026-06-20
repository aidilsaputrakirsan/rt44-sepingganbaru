<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription
} from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Line } from 'vue-chartjs';
import {
    Chart as ChartJS, CategoryScale, LinearScale, PointElement, LineElement,
    Title, Tooltip, Legend, Filler,
} from 'chart.js';
import { Baby, Search, Calendar, Info, Ruler, Trash2, Plus, AlertTriangle, ShieldCheck, Upload, Download, X } from 'lucide-vue-next';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend, Filler);

const props = defineProps({
    balita: Array,
    ringkasan: Object,
    generated_at: String,
});

const page = usePage();
const flash = computed(() => page.props.flash || {});
const flashError = computed(() => (page.props.errors && page.props.errors.file) || null);

// Import Excel Posyandu
const importForm = useForm({ file: null });
const importInput = ref(null);
const onPickFile = (e) => {
    importForm.file = e.target.files[0] || null;
    if (importForm.file) submitImport();
};
const submitImport = () => {
    importForm.post(route('ketua.stunting.import'), {
        preserveScroll: true,
        forceFormData: true,
        onFinish: () => { importForm.reset('file'); if (importInput.value) importInput.value.value = ''; },
    });
};

// Warna level status WHO
const levelStyle = {
    ok:     { badge: 'bg-emerald-100 text-emerald-700', dot: 'bg-emerald-500' },
    warn:   { badge: 'bg-amber-100 text-amber-700',     dot: 'bg-amber-500' },
    bad:    { badge: 'bg-orange-100 text-orange-700',   dot: 'bg-orange-500' },
    severe: { badge: 'bg-red-100 text-red-700',         dot: 'bg-red-500' },
    high:   { badge: 'bg-sky-100 text-sky-700',         dot: 'bg-sky-500' },
};
const ls = (lvl) => levelStyle[lvl] || { badge: 'bg-slate-100 text-slate-500', dot: 'bg-slate-400' };

// Filter & search
const search = ref('');
const filter = ref('semua'); // semua | stunting | belum
const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();
    return props.balita.filter(b => {
        if (filter.value === 'stunting') {
            const lvl = b.latest?.haz?.kategori?.level;
            if (lvl !== 'bad' && lvl !== 'severe') return false;
        } else if (filter.value === 'belum' && b.latest) {
            return false;
        }
        if (!q) return true;
        return b.nama.toLowerCase().includes(q) || b.rumah.toLowerCase().includes(q);
    });
});


// Detail balita
const isOpen = ref(false);
const detail = ref(null);
const openDetail = (b) => { detail.value = b; isOpen.value = true; resetForm(b); };

// Grafik pertumbuhan: HAZ (TB/U) per umur + garis ambang -2 & -3 SD
const chartData = computed(() => {
    const ms = detail.value?.measurements || [];
    const labels = ms.map(m => m.umur_fmt);
    const haz = ms.map(m => m.haz?.z ?? null);
    return {
        labels,
        datasets: [
            { label: 'Z-score TB/U (anak)', data: haz, borderColor: '#7c3aed', backgroundColor: '#7c3aed22', tension: 0.3, spanGaps: true, pointRadius: 4 },
            { label: 'Batas Pendek (-2 SD)', data: labels.map(() => -2), borderColor: '#f59e0b', borderDash: [6, 4], pointRadius: 0, borderWidth: 1.5 },
            { label: 'Batas Sangat Pendek (-3 SD)', data: labels.map(() => -3), borderColor: '#ef4444', borderDash: [6, 4], pointRadius: 0, borderWidth: 1.5 },
            { label: 'Median (0 SD)', data: labels.map(() => 0), borderColor: '#10b981', borderDash: [2, 3], pointRadius: 0, borderWidth: 1 },
        ],
    };
});
const chartOptions = {
    responsive: true, maintainAspectRatio: false,
    scales: { y: { suggestedMin: -4, suggestedMax: 4, title: { display: true, text: 'Z-score' } } },
    plugins: { legend: { labels: { boxWidth: 12, font: { size: 10 } } } },
};

// Form tambah pengukuran
const form = useForm({
    tanggal_ukur: '', berat_kg: '', tinggi_cm: '', cara_ukur: 'berdiri',
    lila_cm: '', lingkar_kepala_cm: '', vitamin_a: false, asi_eksklusif: [],
    pmt_ke: '', pmt_sumber: '', catatan: '',
});
const resetForm = (b) => {
    form.reset();
    form.clearErrors();
    form.tanggal_ukur = new Date().toISOString().slice(0, 10);
    form.cara_ukur = b && b.umur_bulan < 24 ? 'terlentang' : 'berdiri';
};
const submit = () => {
    form.post(route('ketua.stunting.store', detail.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            // refresh detail dari props terbaru
            const updated = props.balita.find(x => x.id === detail.value.id);
            if (updated) detail.value = updated;
            resetForm(detail.value);
        },
    });
};
const hapusUkur = (m) => {
    if (!confirm('Hapus pengukuran ini?')) return;
    router.delete(route('ketua.stunting.destroy', m.id), {
        preserveScroll: true,
        onSuccess: () => {
            const updated = props.balita.find(x => x.id === detail.value.id);
            if (updated) detail.value = updated;
        },
    });
};
</script>

<template>
    <Head title="Stunting Balita" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-2">
                <Baby class="w-6 h-6 text-violet-600 shrink-0" />
                <div>
                    <h2 class="text-xl font-bold text-slate-900 leading-tight">Pemantauan Stunting Balita</h2>
                    <p class="text-xs text-slate-500 uppercase tracking-wider font-medium">Status Gizi (Standar WHO) · RT-44</p>
                </div>
            </div>
        </template>

        <div class="py-6 max-w-7xl mx-auto space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-center gap-2 text-xs text-slate-500">
                    <Calendar class="w-3.5 h-3.5" />
                    Data per {{ generated_at }} — balita = usia di bawah 5 tahun, dihitung otomatis dari tanggal lahir.
                </div>
                <div class="flex items-center gap-2">
                    <a :href="route('ketua.stunting.template')"
                        class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-sm font-semibold px-3 py-2 transition-colors">
                        <Download class="w-4 h-4" />
                        Template Excel
                    </a>
                    <input ref="importInput" type="file" accept=".xlsx,.xls" class="hidden" @change="onPickFile" />
                    <button type="button" :disabled="importForm.processing"
                        class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-3 py-2 transition-colors disabled:opacity-50"
                        @click="importInput?.click()">
                        <Upload class="w-4 h-4" />
                        {{ importForm.processing ? 'Mengimpor…' : 'Import Excel Posyandu' }}
                    </button>
                </div>
            </div>

            <!-- Flash -->
            <div v-if="flash.success" class="rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">
                {{ flash.success }}
            </div>
            <div v-if="flashError" class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                {{ flashError }}
            </div>

            <!-- Ringkasan -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
                    <div class="flex items-center gap-2 text-slate-500 text-xs font-semibold uppercase tracking-wider mb-1">
                        <Baby class="w-4 h-4" /> Total Balita
                    </div>
                    <div class="text-3xl font-extrabold text-slate-900">{{ ringkasan.total }}</div>
                    <p class="text-xs text-slate-400 mt-0.5">usia 0 - 59 bulan</p>
                </div>
                <div class="bg-white rounded-xl border border-emerald-200 shadow-sm p-4">
                    <div class="flex items-center gap-2 text-emerald-600 text-xs font-semibold uppercase tracking-wider mb-1">
                        <ShieldCheck class="w-4 h-4" /> Normal
                    </div>
                    <div class="text-3xl font-extrabold text-emerald-600">{{ ringkasan.normal }}</div>
                    <p class="text-xs text-slate-400 mt-0.5">TB/U normal (terakhir diukur)</p>
                </div>
                <div class="bg-white rounded-xl border border-red-200 shadow-sm p-4">
                    <div class="flex items-center gap-2 text-red-600 text-xs font-semibold uppercase tracking-wider mb-1">
                        <AlertTriangle class="w-4 h-4" /> Stunting
                    </div>
                    <div class="text-3xl font-extrabold text-red-600">{{ ringkasan.stunting_total }}</div>
                    <p class="text-xs text-slate-400 mt-0.5">{{ ringkasan.stunting }} pendek · {{ ringkasan.severe }} sangat pendek</p>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
                    <div class="flex items-center gap-2 text-amber-600 text-xs font-semibold uppercase tracking-wider mb-1">
                        <Info class="w-4 h-4" /> Belum Diukur
                    </div>
                    <div class="text-3xl font-extrabold text-amber-600">{{ ringkasan.belum_ukur }}</div>
                    <p class="text-xs text-slate-400 mt-0.5">belum ada data BB/TB</p>
                </div>
            </div>

            <!-- Toolbar -->
            <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                <div class="relative flex-1">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                    <Input v-model="search" placeholder="Cari nama atau rumah…" class="pl-9" />
                </div>
                <div class="flex items-center gap-1 rounded-lg bg-slate-100 p-1 text-xs font-semibold">
                    <button type="button" class="rounded-md px-3 py-1.5" :class="filter === 'semua' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'" @click="filter = 'semua'">Semua</button>
                    <button type="button" class="rounded-md px-3 py-1.5" :class="filter === 'stunting' ? 'bg-white text-red-600 shadow-sm' : 'text-slate-500'" @click="filter = 'stunting'">Stunting</button>
                    <button type="button" class="rounded-md px-3 py-1.5" :class="filter === 'belum' ? 'bg-white text-amber-600 shadow-sm' : 'text-slate-500'" @click="filter = 'belum'">Belum Diukur</button>
                </div>
            </div>

            <!-- Tabel balita -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-400 border-b">
                        <tr>
                            <th class="text-left py-3 px-4 font-semibold">Nama</th>
                            <th class="text-center py-3 px-2 font-semibold">JK</th>
                            <th class="text-center py-3 px-2 font-semibold">Umur</th>
                            <th class="text-right py-3 px-2 font-semibold">BB / TB</th>
                            <th class="text-center py-3 px-2 font-semibold">Status TB/U</th>
                            <th class="text-right py-3 px-4 font-semibold">Rumah</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="b in filtered" :key="b.id" class="hover:bg-violet-50/40 cursor-pointer" @click="openDetail(b)">
                            <td class="py-2.5 px-4 text-slate-800 font-medium">
                                {{ b.nama }}
                                <span class="text-[10px] text-slate-400">· {{ b.slot }}</span>
                            </td>
                            <td class="py-2.5 px-2 text-center">
                                <span class="text-xs font-semibold" :class="b.jk === 'L' ? 'text-blue-600' : (b.jk === 'P' ? 'text-pink-600' : 'text-slate-400')">{{ b.jk || '?' }}</span>
                            </td>
                            <td class="py-2.5 px-2 text-center text-slate-600 whitespace-nowrap">{{ b.umur_fmt }}</td>
                            <td class="py-2.5 px-2 text-right text-slate-600 whitespace-nowrap">
                                <span v-if="b.latest">{{ b.latest.berat_kg ?? '–' }} kg / {{ b.latest.tinggi_cm ?? '–' }} cm</span>
                                <span v-else class="text-slate-300">—</span>
                            </td>
                            <td class="py-2.5 px-2 text-center">
                                <span v-if="b.latest?.haz" class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full" :class="ls(b.latest.haz.kategori.level).badge">
                                    {{ b.latest.haz.kategori.label }}
                                    <span class="opacity-60">({{ b.latest.haz.z }})</span>
                                </span>
                                <span v-else class="text-xs text-amber-600 font-medium">Belum diukur</span>
                            </td>
                            <td class="py-2.5 px-4 text-right font-mono text-xs text-slate-500">{{ b.rumah }}</td>
                        </tr>
                        <tr v-if="!filtered.length">
                            <td colspan="6" class="py-8 text-center text-sm text-slate-400">Tidak ada balita yang cocok.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Detail balita -->
        <Dialog v-model:open="isOpen">
            <DialogContent class="sm:max-w-[640px] max-h-[88vh] flex flex-col">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <Baby class="w-5 h-5 text-violet-600" />
                        {{ detail?.nama }}
                    </DialogTitle>
                    <DialogDescription>
                        {{ detail?.jk === 'L' ? 'Laki-laki' : (detail?.jk === 'P' ? 'Perempuan' : '?') }} ·
                        {{ detail?.umur_fmt }} · Rumah {{ detail?.rumah }}
                    </DialogDescription>
                </DialogHeader>

                <div class="overflow-y-auto -mx-2 px-2 space-y-5">
                    <!-- Status terkini -->
                    <div v-if="detail?.latest" class="grid grid-cols-3 gap-2">
                        <div v-for="ind in [
                            { key: 'haz', label: 'TB/U (Stunting)' },
                            { key: 'wfl', label: 'BB/TB (Wasting)' },
                            { key: 'waz', label: 'BB/U' },
                        ]" :key="ind.key" class="rounded-lg border border-slate-100 p-2.5 text-center">
                            <p class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">{{ ind.label }}</p>
                            <template v-if="detail.latest[ind.key]">
                                <span class="inline-flex items-center gap-1 mt-1 text-xs font-semibold px-2 py-0.5 rounded-full" :class="ls(detail.latest[ind.key].kategori.level).badge">
                                    {{ detail.latest[ind.key].kategori.label }}
                                </span>
                                <p class="text-[11px] text-slate-400 mt-1">z = {{ detail.latest[ind.key].z }}</p>
                            </template>
                            <p v-else class="text-xs text-slate-300 mt-2">—</p>
                        </div>
                    </div>

                    <!-- Data Posyandu terkini -->
                    <div v-if="detail?.latest" class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500">
                        <span v-if="detail.latest.lila_cm != null">LILA: <b class="text-slate-700">{{ detail.latest.lila_cm }} cm</b></span>
                        <span v-if="detail.latest.lingkar_kepala_cm != null">Lingkar kepala: <b class="text-slate-700">{{ detail.latest.lingkar_kepala_cm }} cm</b></span>
                        <span v-if="detail.latest.vitamin_a != null">Vit. A: <b class="text-slate-700">{{ detail.latest.vitamin_a ? 'Ya' : 'Tidak' }}</b></span>
                        <span v-if="detail.latest.asi_eksklusif?.length">ASI eksklusif bln: <b class="text-slate-700">{{ detail.latest.asi_eksklusif.join(', ') }}</b></span>
                        <span v-if="detail.latest.pmt_ke != null">PMT ke-<b class="text-slate-700">{{ detail.latest.pmt_ke }}</b><template v-if="detail.latest.pmt_sumber"> ({{ detail.latest.pmt_sumber }})</template></span>
                    </div>

                    <!-- Grafik pertumbuhan -->
                    <div v-if="(detail?.measurements?.length || 0) >= 1">
                        <h4 class="text-xs font-bold text-slate-700 mb-2 flex items-center gap-1.5"><Ruler class="w-3.5 h-3.5" /> Grafik Pertumbuhan (TB/U)</h4>
                        <div class="h-56 rounded-lg border border-slate-100 p-2">
                            <Line :data="chartData" :options="chartOptions" />
                        </div>
                    </div>

                    <!-- Riwayat pengukuran -->
                    <div>
                        <h4 class="text-xs font-bold text-slate-700 mb-2">Riwayat Pengukuran</h4>
                        <table v-if="detail?.measurements?.length" class="w-full text-sm">
                            <thead class="text-[11px] uppercase tracking-wider text-slate-400 border-b">
                                <tr>
                                    <th class="text-left py-1.5 font-semibold">Tanggal</th>
                                    <th class="text-center py-1.5 font-semibold">Umur</th>
                                    <th class="text-right py-1.5 font-semibold">BB/TB</th>
                                    <th class="text-center py-1.5 font-semibold">TB/U</th>
                                    <th class="py-1.5"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="m in [...detail.measurements].reverse()" :key="m.id">
                                    <td class="py-2 text-slate-700">{{ m.tanggal_fmt }}</td>
                                    <td class="py-2 text-center text-slate-500 whitespace-nowrap">{{ m.umur_fmt }}</td>
                                    <td class="py-2 text-right text-slate-600 whitespace-nowrap">{{ m.berat_kg ?? '–' }}kg / {{ m.tinggi_cm ?? '–' }}cm</td>
                                    <td class="py-2 text-center">
                                        <span v-if="m.haz" class="inline-flex items-center gap-1 text-[11px] font-semibold px-1.5 py-0.5 rounded-full" :class="ls(m.haz.kategori.level).badge">
                                            {{ m.haz.z }}
                                        </span>
                                        <span v-else class="text-slate-300">—</span>
                                    </td>
                                    <td class="py-2 text-right">
                                        <button type="button" class="text-slate-300 hover:text-red-500" @click="hapusUkur(m)"><Trash2 class="w-3.5 h-3.5" /></button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <p v-else class="text-xs text-slate-400">Belum ada pengukuran. Tambahkan di bawah.</p>
                    </div>

                    <!-- Form tambah pengukuran -->
                    <form @submit.prevent="submit" class="rounded-lg bg-slate-50 border border-slate-100 p-3 space-y-3">
                        <h4 class="text-xs font-bold text-slate-700 flex items-center gap-1.5"><Plus class="w-3.5 h-3.5" /> Tambah Pengukuran</h4>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="text-[11px] font-medium text-slate-500">Tanggal Ukur</label>
                                <Input type="date" v-model="form.tanggal_ukur" />
                                <p v-if="form.errors.tanggal_ukur" class="text-[11px] text-red-500 mt-0.5">{{ form.errors.tanggal_ukur }}</p>
                            </div>
                            <div>
                                <label class="text-[11px] font-medium text-slate-500">Cara Ukur</label>
                                <select v-model="form.cara_ukur" class="w-full rounded-md border-slate-200 text-sm focus:border-violet-400 focus:ring-violet-400">
                                    <option value="terlentang">Terlentang (panjang)</option>
                                    <option value="berdiri">Berdiri (tinggi)</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-[11px] font-medium text-slate-500">Berat (kg)</label>
                                <Input type="number" step="0.1" v-model="form.berat_kg" placeholder="mis. 9.5" />
                                <p v-if="form.errors.berat_kg" class="text-[11px] text-red-500 mt-0.5">{{ form.errors.berat_kg }}</p>
                            </div>
                            <div>
                                <label class="text-[11px] font-medium text-slate-500">Tinggi/Panjang (cm)</label>
                                <Input type="number" step="0.1" v-model="form.tinggi_cm" placeholder="mis. 75.2" />
                                <p v-if="form.errors.tinggi_cm" class="text-[11px] text-red-500 mt-0.5">{{ form.errors.tinggi_cm }}</p>
                            </div>
                        </div>
                        <!-- Data Posyandu tambahan (opsional) -->
                        <details class="rounded-md border border-slate-200 bg-white">
                            <summary class="cursor-pointer select-none px-3 py-2 text-[11px] font-semibold text-slate-500">Data Posyandu lainnya (opsional)</summary>
                            <div class="p-3 pt-0 space-y-3">
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-[11px] font-medium text-slate-500">LILA (cm)</label>
                                        <Input type="number" step="0.1" v-model="form.lila_cm" placeholder="lingkar lengan" />
                                    </div>
                                    <div>
                                        <label class="text-[11px] font-medium text-slate-500">Lingkar Kepala (cm)</label>
                                        <Input type="number" step="0.1" v-model="form.lingkar_kepala_cm" />
                                    </div>
                                    <div>
                                        <label class="text-[11px] font-medium text-slate-500">PMT pemberian ke-</label>
                                        <Input type="number" v-model="form.pmt_ke" placeholder="mis. 3" />
                                    </div>
                                    <div>
                                        <label class="text-[11px] font-medium text-slate-500">Sumber PMT</label>
                                        <Input v-model="form.pmt_sumber" placeholder="pusat/daerah" />
                                    </div>
                                </div>
                                <label class="flex items-center gap-2 text-xs text-slate-600">
                                    <input type="checkbox" v-model="form.vitamin_a" class="rounded border-slate-300 text-violet-600 focus:ring-violet-400" />
                                    Mendapat kapsul Vitamin A
                                </label>
                                <div>
                                    <p class="text-[11px] font-medium text-slate-500 mb-1">ASI eksklusif (bulan ke-)</p>
                                    <div class="flex flex-wrap gap-1.5">
                                        <label v-for="b in [0,1,2,3,4,5,6]" :key="b" class="inline-flex items-center gap-1 text-xs rounded-md border px-2 py-1 cursor-pointer"
                                               :class="form.asi_eksklusif.includes(b) ? 'bg-violet-50 border-violet-300 text-violet-700' : 'border-slate-200 text-slate-500'">
                                            <input type="checkbox" :value="b" v-model="form.asi_eksklusif" class="hidden" />
                                            {{ b }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </details>

                        <Input v-model="form.catatan" placeholder="Catatan (opsional)" />
                        <button type="submit" :disabled="form.processing" class="w-full rounded-lg bg-violet-600 hover:bg-violet-700 text-white text-sm font-semibold py-2 transition-colors disabled:opacity-50">
                            Simpan Pengukuran
                        </button>
                    </form>
                </div>
            </DialogContent>
        </Dialog>
    </AuthenticatedLayout>
</template>
