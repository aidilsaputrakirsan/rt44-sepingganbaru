<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import {
    Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter
} from '@/Components/ui/dialog';
import { FileSignature, Download, FileText, Plus, Pencil, Trash2, Upload } from 'lucide-vue-next';

const props = defineProps({
    items: Array,
});

const downloadUrl = (id) => route('ketua.surat-pernyataan.download', id);

// ── Tambah / Edit ────────────────────────────────────────────
const isModalOpen = ref(false);
const isEdit = ref(false);
const editId = ref(null);
const fileInputRef = ref(null);
const fileLabel = ref('');

const form = useForm({
    judul: '',
    deskripsi: '',
    file: null,
});

const openAdd = () => {
    isEdit.value = false;
    editId.value = null;
    form.reset();
    form.clearErrors();
    fileLabel.value = '';
    if (fileInputRef.value) fileInputRef.value.value = '';
    isModalOpen.value = true;
};

const openEdit = (item) => {
    isEdit.value = true;
    editId.value = item.id;
    form.clearErrors();
    form.judul = item.judul;
    form.deskripsi = item.deskripsi ?? '';
    form.file = null;
    fileLabel.value = '';
    if (fileInputRef.value) fileInputRef.value.value = '';
    isModalOpen.value = true;
};

const onFileChange = (e) => {
    const f = e.target.files?.[0];
    if (!f) { form.file = null; fileLabel.value = ''; return; }
    if (f.size > 10 * 1024 * 1024) {
        alert('Ukuran file maksimal 10 MB.');
        e.target.value = '';
        return;
    }
    form.file = f;
    fileLabel.value = f.name;
};

const submit = () => {
    const opts = {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            isModalOpen.value = false;
            form.reset();
            fileLabel.value = '';
        },
    };
    if (isEdit.value) {
        form.post(route('ketua.surat-pernyataan.update', editId.value), opts);
    } else {
        form.post(route('ketua.surat-pernyataan.store'), opts);
    }
};

// ── Hapus ────────────────────────────────────────────────────
const isDeleteOpen = ref(false);
const deleteItem = ref(null);
const deleteProcessing = ref(false);

const askDelete = (item) => {
    deleteItem.value = item;
    isDeleteOpen.value = true;
};

const confirmDelete = () => {
    if (!deleteItem.value) return;
    deleteProcessing.value = true;
    router.delete(route('ketua.surat-pernyataan.destroy', deleteItem.value.id), {
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
    <Head title="Surat Pernyataan" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Surat Pernyataan</h2>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">

                <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 p-4">
                        <FileSignature class="mt-0.5 h-5 w-5 shrink-0 text-amber-600" />
                        <div class="text-sm text-amber-800">
                            <p class="font-semibold">Blangko Surat Pernyataan</p>
                            <p class="mt-0.5 text-amber-700">
                                Kelola blangko (format Word/PDF). File yang ditambahkan otomatis tampil di
                                halaman ini dan di homepage untuk diunduh warga.
                            </p>
                        </div>
                    </div>
                    <Button class="shrink-0 gap-2" @click="openAdd">
                        <Plus class="h-4 w-4" />
                        Tambah Blangko
                    </Button>
                </div>

                <div v-if="items.length" class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div
                        v-for="item in items"
                        :key="item.id"
                        class="flex flex-col rounded-xl border border-gray-200 bg-white p-5 shadow-sm"
                    >
                        <div class="flex items-start gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-amber-100">
                                <FileText class="h-5 w-5 text-amber-600" />
                            </div>
                            <div class="min-w-0">
                                <h3 class="font-semibold text-gray-800">{{ item.judul }}</h3>
                                <p v-if="item.deskripsi" class="mt-1 text-sm leading-relaxed text-gray-500">{{ item.deskripsi }}</p>
                                <span class="mt-1 inline-block text-[10px] font-bold uppercase tracking-wider text-gray-400">{{ item.ext }}</span>
                            </div>
                        </div>

                        <div class="mt-4 flex items-center justify-between gap-2 border-t border-gray-100 pt-4">
                            <a v-if="item.available" :href="downloadUrl(item.id)">
                                <Button size="sm" class="gap-2">
                                    <Download class="h-4 w-4" />
                                    Download
                                </Button>
                            </a>
                            <span v-else class="text-xs font-medium text-red-500">File hilang</span>

                            <div class="flex items-center gap-1">
                                <Button size="sm" variant="outline" class="gap-1.5" @click="openEdit(item)">
                                    <Pencil class="h-3.5 w-3.5" />
                                    Edit
                                </Button>
                                <Button size="sm" variant="outline" class="gap-1.5 text-red-600 hover:bg-red-50 hover:text-red-700" @click="askDelete(item)">
                                    <Trash2 class="h-3.5 w-3.5" />
                                    Hapus
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="rounded-xl border border-dashed border-gray-300 bg-white py-16 text-center">
                    <FileSignature class="mx-auto h-10 w-10 text-gray-300" />
                    <p class="mt-3 text-sm text-gray-500">Belum ada blangko surat pernyataan.</p>
                    <Button class="mt-4 gap-2" @click="openAdd">
                        <Plus class="h-4 w-4" />
                        Tambah Blangko
                    </Button>
                </div>

            </div>
        </div>

        <!-- ── Modal Tambah / Edit ─────────────────────────── -->
        <Dialog v-model:open="isModalOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <Upload class="h-5 w-5 text-amber-600" />
                        {{ isEdit ? 'Edit Blangko' : 'Tambah Blangko' }}
                    </DialogTitle>
                    <DialogDescription class="pt-1 text-xs">
                        Format: DOC, DOCX, atau PDF — maksimal 10 MB.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-3 py-2">
                    <div>
                        <Label for="sp_judul">Judul <span class="text-red-500">*</span></Label>
                        <Input id="sp_judul" v-model="form.judul" placeholder="mis. Surat Pernyataan Ahli Waris" class="mt-1" />
                        <p v-if="form.errors.judul" class="mt-0.5 text-xs text-red-600">{{ form.errors.judul }}</p>
                    </div>
                    <div>
                        <Label for="sp_desc">Deskripsi (opsional)</Label>
                        <textarea
                            id="sp_desc"
                            v-model="form.deskripsi"
                            rows="3"
                            placeholder="Penjelasan singkat surat ini..."
                            class="mt-1 w-full resize-y rounded-md border border-slate-200 px-3 py-2 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500/40"
                        ></textarea>
                        <p v-if="form.errors.deskripsi" class="mt-0.5 text-xs text-red-600">{{ form.errors.deskripsi }}</p>
                    </div>
                    <div>
                        <Label for="sp_file">
                            File {{ isEdit ? '(kosongkan jika tidak diganti)' : '' }}
                            <span v-if="!isEdit" class="text-red-500">*</span>
                        </Label>
                        <input
                            id="sp_file"
                            ref="fileInputRef"
                            type="file"
                            accept=".doc,.docx,.pdf"
                            class="mt-1 block w-full text-sm text-slate-700 file:mr-3 file:cursor-pointer file:rounded-md file:border-0 file:bg-amber-50 file:px-3 file:py-2 file:font-semibold file:text-amber-700 hover:file:bg-amber-100"
                            @change="onFileChange"
                        />
                        <p v-if="fileLabel" class="mt-1 text-xs text-slate-500">Dipilih: {{ fileLabel }}</p>
                        <p v-if="form.errors.file" class="mt-0.5 text-xs text-red-600">{{ form.errors.file }}</p>
                        <p v-if="form.progress" class="mt-1 text-xs text-slate-500">Mengunggah... {{ form.progress.percentage }}%</p>
                    </div>
                </div>

                <DialogFooter class="gap-2 sm:gap-0">
                    <Button variant="outline" :disabled="form.processing" @click="isModalOpen = false">Batal</Button>
                    <Button :disabled="form.processing || (!isEdit && !form.file)" @click="submit">
                        {{ form.processing ? 'Menyimpan...' : (isEdit ? 'Simpan Perubahan' : 'Simpan') }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- ── Modal Hapus ─────────────────────────────────── -->
        <Dialog v-model:open="isDeleteOpen">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2 text-red-600">
                        <Trash2 class="h-5 w-5" /> Hapus Blangko
                    </DialogTitle>
                    <DialogDescription class="pt-1">
                        Yakin menghapus <span class="font-semibold">{{ deleteItem?.judul }}</span>?
                        File akan dihapus permanen dan tidak lagi muncul di homepage.
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
