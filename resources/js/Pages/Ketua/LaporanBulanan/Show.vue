<script setup>
import { ref } from 'vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import {
    Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter
} from '@/Components/ui/dialog';
import { ArrowLeft, FileDown, Plus, Pencil, Trash2, Save, ImageOff } from 'lucide-vue-next';

const props = defineProps({
    report: Object,
    letterOptions: Array,
});

const formatTanggal = (d) => {
    if (!d) return '-';
    return new Date(d).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};

// ── Header form (TTD & tanggal) ──────────────────────────────
const headerForm = useForm({
    tanggal_pengesahan: props.report.tanggal_pengesahan ?? '',
    lurah_name: props.report.lurah_name ?? '',
    ketua_name: props.report.ketua_name ?? '',
});
const saveHeader = () => {
    headerForm.put(route('ketua.laporan-bulanan.update', props.report.id), { preserveScroll: true });
};

// ── Tambah / Edit kegiatan ───────────────────────────────────
const isModalOpen = ref(false);
const isEdit = ref(false);
const editId = ref(null);
const fileInputRef = ref(null);
const existingPhoto = ref(null);

const aForm = useForm({
    tanggal: '',
    uraian: '',
    no_surat: '',
    photo: null,
    remove_photo: false,
});

const openAdd = () => {
    isEdit.value = false;
    editId.value = null;
    aForm.reset();
    aForm.clearErrors();
    existingPhoto.value = null;
    if (fileInputRef.value) fileInputRef.value.value = '';
    isModalOpen.value = true;
};

const openEdit = (a) => {
    isEdit.value = true;
    editId.value = a.id;
    aForm.clearErrors();
    aForm.tanggal = a.tanggal;
    aForm.uraian = a.uraian;
    aForm.no_surat = a.no_surat ?? '';
    aForm.photo = null;
    aForm.remove_photo = false;
    existingPhoto.value = a.photo_url;
    if (fileInputRef.value) fileInputRef.value.value = '';
    isModalOpen.value = true;
};

const onFileChange = (e) => {
    const f = e.target.files?.[0];
    if (!f) { aForm.photo = null; return; }
    if (f.size > 10 * 1024 * 1024) {
        alert('Ukuran foto maksimal 10 MB.');
        e.target.value = '';
        return;
    }
    aForm.photo = f;
    aForm.remove_photo = false;
};

const removeExistingPhoto = () => {
    existingPhoto.value = null;
    aForm.photo = null;
    aForm.remove_photo = true;
    if (fileInputRef.value) fileInputRef.value.value = '';
};

const submitActivity = () => {
    const opts = {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            isModalOpen.value = false;
            aForm.reset();
            existingPhoto.value = null;
        },
    };
    if (isEdit.value) {
        aForm.post(route('ketua.laporan-bulanan.activities.update', [props.report.id, editId.value]), opts);
    } else {
        aForm.post(route('ketua.laporan-bulanan.activities.store', props.report.id), opts);
    }
};

// ── Hapus kegiatan ───────────────────────────────────────────
const isDeleteOpen = ref(false);
const deleteItem = ref(null);
const deleteProcessing = ref(false);
const askDelete = (a) => { deleteItem.value = a; isDeleteOpen.value = true; };
const confirmDelete = () => {
    if (!deleteItem.value) return;
    deleteProcessing.value = true;
    router.delete(route('ketua.laporan-bulanan.activities.destroy', [props.report.id, deleteItem.value.id]), {
        preserveScroll: true,
        onFinish: () => {
            deleteProcessing.value = false;
            isDeleteOpen.value = false;
            deleteItem.value = null;
        },
    });
};
</script>

<template>
    <Head :title="`Laporan ${report.bulan_label}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('ketua.laporan-bulanan.index')" class="text-gray-500 hover:text-gray-800">
                    <ArrowLeft class="h-5 w-5" />
                </Link>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Laporan {{ report.bulan_label }}</h2>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">

                <!-- Pengaturan laporan -->
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="font-semibold text-gray-800">Pengaturan & Tanda Tangan</h3>
                        <a :href="route('ketua.laporan-bulanan.pdf', report.id)" target="_blank">
                            <Button class="gap-2">
                                <FileDown class="h-4 w-4" />
                                Export PDF
                            </Button>
                        </a>
                    </div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <Label for="tgl_pengesahan">Tanggal Pengesahan</Label>
                            <Input id="tgl_pengesahan" v-model="headerForm.tanggal_pengesahan" type="date" class="mt-1" />
                        </div>
                        <div>
                            <Label for="lurah">Nama Lurah</Label>
                            <Input id="lurah" v-model="headerForm.lurah_name" class="mt-1" />
                        </div>
                        <div>
                            <Label for="ketua">Nama Ketua RT</Label>
                            <Input id="ketua" v-model="headerForm.ketua_name" class="mt-1" />
                        </div>
                    </div>
                    <div class="mt-4 flex justify-end">
                        <Button variant="outline" class="gap-2" :disabled="headerForm.processing" @click="saveHeader">
                            <Save class="h-4 w-4" />
                            {{ headerForm.processing ? 'Menyimpan...' : 'Simpan' }}
                        </Button>
                    </div>
                </div>

                <!-- Daftar kegiatan -->
                <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-gray-100 p-5">
                        <h3 class="font-semibold text-gray-800">Daftar Kegiatan ({{ report.activities.length }})</h3>
                        <Button class="gap-2" @click="openAdd">
                            <Plus class="h-4 w-4" />
                            Tambah Kegiatan
                        </Button>
                    </div>

                    <div v-if="report.activities.length" class="divide-y divide-gray-100">
                        <div
                            v-for="(a, idx) in report.activities"
                            :key="a.id"
                            class="flex items-start gap-4 p-5"
                        >
                            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gray-100 text-sm font-semibold text-gray-600">
                                {{ idx + 1 }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-medium text-gray-400">{{ formatTanggal(a.tanggal) }}</p>
                                <p class="mt-1 text-sm leading-relaxed text-gray-700">{{ a.uraian }}</p>
                                <div class="mt-2 flex flex-wrap items-center gap-3">
                                    <span v-if="a.no_surat" class="inline-flex items-center rounded bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700">
                                        No Surat: {{ a.no_surat }}
                                    </span>
                                    <img v-if="a.photo_url" :src="a.photo_url" class="h-16 w-24 rounded border border-gray-200 object-cover" />
                                </div>
                            </div>
                            <div class="flex shrink-0 items-center gap-1">
                                <Button size="sm" variant="outline" class="gap-1.5" @click="openEdit(a)">
                                    <Pencil class="h-3.5 w-3.5" />
                                </Button>
                                <Button size="sm" variant="outline" class="gap-1.5 text-red-600 hover:bg-red-50 hover:text-red-700" @click="askDelete(a)">
                                    <Trash2 class="h-3.5 w-3.5" />
                                </Button>
                            </div>
                        </div>
                    </div>

                    <div v-else class="py-14 text-center text-sm text-gray-500">
                        Belum ada kegiatan. Klik "Tambah Kegiatan".
                    </div>
                </div>

            </div>
        </div>

        <!-- ── Modal Tambah / Edit kegiatan ────────────────── -->
        <Dialog v-model:open="isModalOpen">
            <DialogContent class="max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>{{ isEdit ? 'Edit Kegiatan' : 'Tambah Kegiatan' }}</DialogTitle>
                    <DialogDescription class="pt-1 text-xs">
                        Dokumentasi bisa berupa No Surat (dari Agenda Surat) atau foto.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-3 py-2">
                    <div>
                        <Label for="a_tgl">Tanggal <span class="text-red-500">*</span></Label>
                        <Input id="a_tgl" v-model="aForm.tanggal" type="date" class="mt-1" />
                        <p v-if="aForm.errors.tanggal" class="mt-0.5 text-xs text-red-600">{{ aForm.errors.tanggal }}</p>
                    </div>
                    <div>
                        <Label for="a_uraian">Uraian Kegiatan <span class="text-red-500">*</span></Label>
                        <textarea
                            id="a_uraian"
                            v-model="aForm.uraian"
                            rows="4"
                            placeholder="Deskripsi kegiatan..."
                            class="mt-1 w-full resize-y rounded-md border border-slate-200 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-500/40"
                        ></textarea>
                        <p v-if="aForm.errors.uraian" class="mt-0.5 text-xs text-red-600">{{ aForm.errors.uraian }}</p>
                    </div>

                    <div class="rounded-lg border border-gray-100 bg-gray-50 p-3">
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">Dokumentasi</p>

                        <div>
                            <Label for="a_surat">No Surat (opsional)</Label>
                            <select
                                id="a_surat"
                                v-model="aForm.no_surat"
                                class="mt-1 block w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-500/40"
                            >
                                <option value="">— Tidak ada —</option>
                                <option v-for="opt in letterOptions" :key="opt.nomor" :value="opt.nomor">
                                    {{ opt.nomor }}{{ opt.keterangan ? ' — ' + opt.keterangan : '' }}
                                </option>
                            </select>
                        </div>

                        <div class="mt-3">
                            <Label for="a_photo">Foto (opsional)</Label>
                            <div v-if="existingPhoto" class="mt-1 flex items-center gap-3">
                                <img :src="existingPhoto" class="h-20 w-28 rounded border border-gray-200 object-cover" />
                                <Button size="sm" variant="outline" class="gap-1.5 text-red-600 hover:bg-red-50" @click="removeExistingPhoto">
                                    <ImageOff class="h-3.5 w-3.5" /> Hapus foto
                                </Button>
                            </div>
                            <input
                                v-else
                                id="a_photo"
                                ref="fileInputRef"
                                type="file"
                                accept=".jpg,.jpeg,.png"
                                class="mt-1 block w-full text-sm text-slate-700 file:mr-3 file:cursor-pointer file:rounded-md file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:font-semibold file:text-blue-700 hover:file:bg-blue-100"
                                @change="onFileChange"
                            />
                            <p class="mt-1 text-[11px] text-slate-400">Orientasi (landscape/portrait) terdeteksi otomatis. Maks 10 MB.</p>
                            <p v-if="aForm.errors.photo" class="mt-0.5 text-xs text-red-600">{{ aForm.errors.photo }}</p>
                            <p v-if="aForm.progress" class="mt-1 text-xs text-slate-500">Mengunggah... {{ aForm.progress.percentage }}%</p>
                        </div>
                    </div>
                </div>

                <DialogFooter class="gap-2 sm:gap-0">
                    <Button variant="outline" :disabled="aForm.processing" @click="isModalOpen = false">Batal</Button>
                    <Button :disabled="aForm.processing || !aForm.tanggal || !aForm.uraian" @click="submitActivity">
                        {{ aForm.processing ? 'Menyimpan...' : 'Simpan' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- ── Modal Hapus kegiatan ────────────────────────── -->
        <Dialog v-model:open="isDeleteOpen">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2 text-red-600">
                        <Trash2 class="h-5 w-5" /> Hapus Kegiatan
                    </DialogTitle>
                    <DialogDescription class="pt-1">Yakin menghapus kegiatan ini?</DialogDescription>
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
