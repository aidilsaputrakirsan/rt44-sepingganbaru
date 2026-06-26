<script setup>
import { ref } from 'vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';
import {
    Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter
} from '@/Components/ui/dialog';
import { ClipboardList, Plus, FileDown, Trash2, Eye, CalendarDays } from 'lucide-vue-next';

const props = defineProps({
    reports: Array,
});

// ── Buat laporan ─────────────────────────────────────────────
const isCreateOpen = ref(false);
const now = new Date();
const defaultMonth = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;

const form = useForm({
    period: defaultMonth,
});

const openCreate = () => {
    form.reset();
    form.clearErrors();
    form.period = defaultMonth;
    isCreateOpen.value = true;
};

const submitCreate = () => {
    // kirim sebagai tanggal 1 bulan tsb
    router.post(route('ketua.laporan-bulanan.store'), { period: form.period + '-01' }, {
        onSuccess: () => { isCreateOpen.value = false; },
    });
};

// ── Hapus ────────────────────────────────────────────────────
const isDeleteOpen = ref(false);
const deleteItem = ref(null);
const deleteProcessing = ref(false);

const askDelete = (r) => { deleteItem.value = r; isDeleteOpen.value = true; };
const confirmDelete = () => {
    if (!deleteItem.value) return;
    deleteProcessing.value = true;
    router.delete(route('ketua.laporan-bulanan.destroy', deleteItem.value.id), {
        onFinish: () => {
            deleteProcessing.value = false;
            isDeleteOpen.value = false;
            deleteItem.value = null;
        },
    });
};
</script>

<template>
    <Head title="Laporan Bulanan" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Laporan Bulanan</h2>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">

                <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="flex items-start gap-3 rounded-lg border border-blue-200 bg-blue-50 p-4">
                        <ClipboardList class="mt-0.5 h-5 w-5 shrink-0 text-blue-600" />
                        <div class="text-sm text-blue-800">
                            <p class="font-semibold">Laporan Pelaksanaan Tugas Ketua RT-44</p>
                            <p class="mt-0.5 text-blue-700">
                                Buat laporan kegiatan bulanan untuk diserahkan ke kelurahan. Input kegiatan, dokumentasi
                                (No Surat / foto), lalu export ke PDF.
                            </p>
                        </div>
                    </div>
                    <Button class="shrink-0 gap-2" @click="openCreate">
                        <Plus class="h-4 w-4" />
                        Buat Laporan
                    </Button>
                </div>

                <div v-if="reports.length" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="r in reports"
                        :key="r.id"
                        class="flex flex-col rounded-xl border border-gray-200 bg-white p-5 shadow-sm"
                    >
                        <div class="flex items-start gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-100">
                                <CalendarDays class="h-5 w-5 text-blue-600" />
                            </div>
                            <div class="min-w-0">
                                <h3 class="font-semibold text-gray-800">{{ r.bulan_label }}</h3>
                                <p class="mt-0.5 text-sm text-gray-500">{{ r.activities_count }} kegiatan</p>
                            </div>
                        </div>

                        <div class="mt-4 flex items-center justify-between gap-2 border-t border-gray-100 pt-4">
                            <Link :href="route('ketua.laporan-bulanan.show', r.id)">
                                <Button size="sm" class="gap-2">
                                    <Eye class="h-4 w-4" />
                                    Buka
                                </Button>
                            </Link>
                            <div class="flex items-center gap-1">
                                <a :href="route('ketua.laporan-bulanan.pdf', r.id)" target="_blank">
                                    <Button size="sm" variant="outline" class="gap-1.5">
                                        <FileDown class="h-3.5 w-3.5" />
                                        PDF
                                    </Button>
                                </a>
                                <Button size="sm" variant="outline" class="gap-1.5 text-red-600 hover:bg-red-50 hover:text-red-700" @click="askDelete(r)">
                                    <Trash2 class="h-3.5 w-3.5" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="rounded-xl border border-dashed border-gray-300 bg-white py-16 text-center">
                    <ClipboardList class="mx-auto h-10 w-10 text-gray-300" />
                    <p class="mt-3 text-sm text-gray-500">Belum ada laporan bulanan.</p>
                    <Button class="mt-4 gap-2" @click="openCreate">
                        <Plus class="h-4 w-4" />
                        Buat Laporan
                    </Button>
                </div>

            </div>
        </div>

        <!-- ── Modal Buat ──────────────────────────────────── -->
        <Dialog v-model:open="isCreateOpen">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>Buat Laporan Bulanan</DialogTitle>
                    <DialogDescription class="pt-1 text-xs">
                        Pilih bulan laporan. Jika laporan bulan tsb sudah ada, akan dibuka yang sudah ada.
                    </DialogDescription>
                </DialogHeader>
                <div class="py-2">
                    <Label for="period">Bulan</Label>
                    <input
                        id="period"
                        v-model="form.period"
                        type="month"
                        class="mt-1 block w-full rounded-md border border-slate-200 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-500/40"
                    />
                </div>
                <DialogFooter class="gap-2 sm:gap-0">
                    <Button variant="outline" @click="isCreateOpen = false">Batal</Button>
                    <Button :disabled="!form.period" @click="submitCreate">Lanjut</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- ── Modal Hapus ─────────────────────────────────── -->
        <Dialog v-model:open="isDeleteOpen">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2 text-red-600">
                        <Trash2 class="h-5 w-5" /> Hapus Laporan
                    </DialogTitle>
                    <DialogDescription class="pt-1">
                        Yakin menghapus laporan <span class="font-semibold">{{ deleteItem?.bulan_label }}</span>?
                        Semua kegiatan & foto di dalamnya ikut terhapus.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2 sm:gap-0">
                    <Button variant="outline" :disabled="deleteProcessing" @click="isDeleteOpen = false">Batal</Button>
                    <Button class="bg-red-600 hover:bg-red-700" :disabled="deleteProcessing" @click="confirmDelete">
                        {{ deleteProcessing ? 'Menghapus...' : 'Hapus' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AuthenticatedLayout>
</template>
