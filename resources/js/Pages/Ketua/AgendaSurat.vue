<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import {
    Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter
} from '@/Components/ui/dialog';
import { BookText, Plus, Pencil, Trash2, Search, Hash, FileText } from 'lucide-vue-next';

const props = defineProps({
    entries: Array,
    nextNumber: Object,
});

// ── Search ───────────────────────────────────────────────────
const searchQuery = ref('');
const filteredEntries = computed(() => {
    const q = searchQuery.value.toLowerCase().trim();
    if (!q) return props.entries;
    return props.entries.filter(e =>
        (e.nomor_format || '').toLowerCase().includes(q) ||
        (e.jenis || '').toLowerCase().includes(q) ||
        (e.keterangan || '').toLowerCase().includes(q)
    );
});

const formatTanggal = (d) => {
    if (!d) return '-';
    return new Date(d).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};

// ── Tambah / Edit ────────────────────────────────────────────
const isModalOpen = ref(false);
const isEdit = ref(false);
const form = useForm({
    id: null,
    nomor_urut: '',
    jenis: 'Surat Pengantar',
    keterangan: '',
    tanggal: new Date().toISOString().slice(0, 10),
});

const openAdd = () => {
    isEdit.value = false;
    form.reset();
    form.clearErrors();
    form.tanggal = new Date().toISOString().slice(0, 10);
    form.nomor_urut = props.nextNumber?.nomor_urut ?? '';
    form.jenis = 'Surat Pengantar';
    isModalOpen.value = true;
};

const openEdit = (e) => {
    isEdit.value = true;
    form.clearErrors();
    form.id = e.id;
    form.nomor_urut = e.nomor_urut;
    form.jenis = e.jenis;
    form.keterangan = e.keterangan ?? '';
    form.tanggal = e.tanggal;
    isModalOpen.value = true;
};

const submit = () => {
    if (isEdit.value) {
        form.put(route('ketua.agenda-surat.update', form.id), {
            preserveScroll: true,
            onSuccess: () => { isModalOpen.value = false; },
        });
    } else {
        form.post(route('ketua.agenda-surat.store'), {
            preserveScroll: true,
            onSuccess: () => { isModalOpen.value = false; },
        });
    }
};

const destroy = (e) => {
    if (!confirm(`Hapus nomor ${e.nomor_format} dari agenda?`)) return;
    router.delete(route('ketua.agenda-surat.destroy', e.id), { preserveScroll: true });
};
</script>

<template>
    <Head title="Agenda Surat" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <BookText class="w-5 h-5 text-blue-600" />
                <h2 class="text-lg font-semibold text-slate-800">Agenda Surat</h2>
            </div>
        </template>

        <div class="max-w-5xl mx-auto space-y-5">
            <!-- Info nomor berikutnya -->
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <Hash class="w-5 h-5 text-blue-600 shrink-0" />
                    <div>
                        <p class="text-xs text-blue-700">Nomor berikutnya (tahun {{ nextNumber.tahun }})</p>
                        <p class="text-base font-mono font-semibold text-blue-900">{{ nextNumber.nomor_format }}</p>
                    </div>
                </div>
                <Button class="bg-blue-600 hover:bg-blue-700 text-white gap-1.5" @click="openAdd">
                    <Plus class="w-4 h-4" /> Tambah Manual
                </Button>
            </div>

            <!-- Search -->
            <div class="relative">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                <Input v-model="searchQuery" placeholder="Cari nomor / jenis / keterangan..." class="pl-9" />
            </div>

            <!-- Tabel -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-600">
                        <tr>
                            <th class="text-left font-medium px-4 py-3">Nomor Surat</th>
                            <th class="text-left font-medium px-4 py-3">Jenis</th>
                            <th class="text-left font-medium px-4 py-3">Keterangan</th>
                            <th class="text-left font-medium px-4 py-3">Tanggal</th>
                            <th class="text-right font-medium px-4 py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="e in filteredEntries" :key="e.id" class="hover:bg-slate-50/60">
                            <td class="px-4 py-3 font-mono font-medium text-slate-800">{{ e.nomor_format }}</td>
                            <td class="px-4 py-3">{{ e.jenis }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ e.keterangan || '-' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ formatTanggal(e.tanggal) }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <a
                                        v-if="e.has_pdf"
                                        :href="route('ketua.surat-pengantar.reprint', e.id)"
                                        target="_blank"
                                        rel="noopener"
                                        title="Buka / cetak ulang PDF surat"
                                        class="inline-flex items-center justify-center h-9 px-3 rounded-md text-sm font-medium text-blue-600 hover:bg-blue-50 transition-colors"
                                    >
                                        <FileText class="w-4 h-4 mr-1.5" /> PDF
                                    </a>
                                    <Button variant="ghost" size="sm" class="text-slate-600 hover:bg-slate-100" @click="openEdit(e)">
                                        <Pencil class="w-4 h-4" />
                                    </Button>
                                    <Button variant="ghost" size="sm" class="text-red-600 hover:bg-red-50" @click="destroy(e)">
                                        <Trash2 class="w-4 h-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="filteredEntries.length === 0">
                            <td colspan="5" class="px-4 py-10 text-center text-slate-400">
                                Belum ada nomor surat tercatat.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal Tambah/Edit -->
        <Dialog v-model:open="isModalOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{ isEdit ? 'Edit Nomor Surat' : 'Tambah Nomor Surat' }}</DialogTitle>
                    <DialogDescription>Nomor urut di-reset tiap tahun. Format: 001/RT.44/VI/2026.</DialogDescription>
                </DialogHeader>
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label>Nomor Urut</Label>
                            <Input v-model="form.nomor_urut" type="number" min="1" />
                            <p v-if="form.errors.nomor_urut" class="text-xs text-red-500">{{ form.errors.nomor_urut }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <Label>Tanggal</Label>
                            <Input type="date" v-model="form.tanggal" />
                            <p v-if="form.errors.tanggal" class="text-xs text-red-500">{{ form.errors.tanggal }}</p>
                        </div>
                    </div>
                    <div class="space-y-1.5">
                        <Label>Jenis Surat</Label>
                        <Input v-model="form.jenis" placeholder="mis. Surat Pengantar, Surat Keterangan" />
                        <p v-if="form.errors.jenis" class="text-xs text-red-500">{{ form.errors.jenis }}</p>
                    </div>
                    <div class="space-y-1.5">
                        <Label>Keterangan <span class="text-slate-400 text-xs">(opsional)</span></Label>
                        <Input v-model="form.keterangan" placeholder="Peruntukan / nama pemohon" />
                        <p v-if="form.errors.keterangan" class="text-xs text-red-500">{{ form.errors.keterangan }}</p>
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="isModalOpen = false">Batal</Button>
                    <Button @click="submit" :disabled="form.processing">Simpan</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AuthenticatedLayout>
</template>
