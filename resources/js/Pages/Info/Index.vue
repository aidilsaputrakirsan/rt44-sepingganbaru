<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle
} from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Button } from '@/Components/ui/button';
import {
    Newspaper, Plus, FileText, Search, X, AlertTriangle, Trash2, Download, Eye, Upload, ImageIcon, Image as ImageLucide
} from 'lucide-vue-next';

const props = defineProps({
    items: Array,
    canManage: Boolean,
});

const searchQuery = ref('');

const filteredItems = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();
    if (!q) return props.items;
    return props.items.filter(it =>
        it.title.toLowerCase().includes(q) ||
        (it.description || '').toLowerCase().includes(q)
    );
});

// ── Upload Modal ────────────────────────────────────────
const isUploadOpen = ref(false);
const uploadForm = useForm({
    title: '',
    description: '',
    file: null,
});
const previewUrl = ref(null);
const fileInputRef = ref(null);

const openUpload = () => {
    uploadForm.reset();
    previewUrl.value = null;
    if (fileInputRef.value) fileInputRef.value.value = '';
    isUploadOpen.value = true;
};

const onFileChange = (e) => {
    const f = e.target.files?.[0];
    if (!f) return;
    if (f.size > 10 * 1024 * 1024) {
        alert('Ukuran file maksimal 10 MB.');
        e.target.value = '';
        return;
    }
    uploadForm.file = f;
    // Buat preview kalau image
    if (/^image\//.test(f.type)) {
        const reader = new FileReader();
        reader.onload = (ev) => { previewUrl.value = ev.target.result; };
        reader.readAsDataURL(f);
    } else {
        previewUrl.value = null;
    }
};

const submitUpload = () => {
    uploadForm.post(route('info.store'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            isUploadOpen.value = false;
            uploadForm.reset();
            previewUrl.value = null;
        },
    });
};

// ── View Modal (image lightbox) ─────────────────────────
const isViewerOpen = ref(false);
const viewerItem = ref(null);

const openViewer = (item) => {
    viewerItem.value = item;
    isViewerOpen.value = true;
};

// ── Delete Modal ────────────────────────────────────────
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
    router.delete(route('info.destroy', deleteItem.value.id), {
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
    <Head title="Papan Informasi" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-2">
                <Newspaper class="w-6 h-6 text-blue-600 shrink-0" />
                <div>
                    <h2 class="text-xl font-bold text-slate-900 leading-tight">Papan Informasi</h2>
                    <p class="text-xs text-slate-500 uppercase tracking-wider font-medium">Informasi & Dokumen RT-44</p>
                </div>
            </div>
        </template>

        <div class="py-6 max-w-7xl mx-auto space-y-5">
            <!-- Top: search + upload -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-center gap-2 h-10 px-3 rounded-md border border-slate-200 bg-white shadow-sm focus-within:ring-2 focus-within:ring-blue-500/40 focus-within:border-blue-400 transition-colors w-full sm:max-w-md">
                    <Search class="w-4 h-4 text-slate-500 shrink-0" />
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari informasi..."
                        class="flex-1 min-w-0 h-full bg-transparent border-0 outline-none text-sm placeholder:text-slate-400"
                    />
                    <button v-if="searchQuery" type="button" class="text-slate-400 hover:text-slate-700 shrink-0" @click="searchQuery = ''">
                        <X class="w-4 h-4" />
                    </button>
                </div>
                <Button v-if="canManage" @click="openUpload" class="shadow-sm h-10 shrink-0 w-full sm:w-auto">
                    <Plus class="w-4 h-4 mr-1.5" /> Unggah Informasi
                </Button>
            </div>

            <!-- Empty state -->
            <div v-if="filteredItems.length === 0" class="text-center py-16 bg-white rounded-lg border border-slate-200">
                <Newspaper class="w-12 h-12 text-slate-300 mx-auto mb-3" />
                <p class="text-sm text-slate-500">
                    {{ searchQuery ? 'Tidak ada informasi yang cocok.' : 'Belum ada informasi diunggah.' }}
                </p>
                <Button v-if="canManage && !searchQuery" @click="openUpload" variant="outline" class="mt-4">
                    <Plus class="w-4 h-4 mr-1.5" /> Unggah Pertama
                </Button>
            </div>

            <!-- Grid -->
            <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="item in filteredItems"
                    :key="item.id"
                    class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden flex flex-col hover:shadow-md transition-shadow"
                >
                    <!-- Preview -->
                    <button
                        v-if="item.file_type === 'image'"
                        type="button"
                        class="relative w-full aspect-[4/3] bg-slate-100 overflow-hidden group"
                        @click="openViewer(item)"
                    >
                        <img :src="item.file_url" :alt="item.title" class="w-full h-full object-cover group-hover:scale-105 transition-transform" loading="lazy" />
                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/30 flex items-center justify-center transition-colors">
                            <Eye class="w-8 h-8 text-white opacity-0 group-hover:opacity-100 transition-opacity drop-shadow" />
                        </div>
                    </button>
                    <a
                        v-else
                        :href="item.file_url"
                        target="_blank"
                        class="relative w-full aspect-[4/3] bg-gradient-to-br from-red-50 to-orange-50 flex flex-col items-center justify-center gap-2 hover:from-red-100 hover:to-orange-100 transition-colors"
                    >
                        <FileText class="w-16 h-16 text-red-500" />
                        <span class="text-xs font-bold uppercase tracking-wider text-red-600">PDF</span>
                        <span class="text-xs text-slate-500">Klik untuk membuka</span>
                    </a>

                    <!-- Info -->
                    <div class="p-4 flex-1 flex flex-col gap-2">
                        <h3 class="font-bold text-slate-900 leading-snug line-clamp-2">{{ item.title }}</h3>
                        <p v-if="item.description" class="text-xs text-slate-600 line-clamp-3">{{ item.description }}</p>
                        <div class="text-[11px] text-slate-400 mt-auto pt-2 border-t border-slate-100 flex items-center justify-between">
                            <span>{{ item.created_at }}</span>
                            <span v-if="item.uploaded_by_name" class="truncate ml-2">
                                oleh {{ item.uploaded_by_name }}
                            </span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="px-4 pb-3 flex items-center gap-2">
                        <Button
                            v-if="item.file_type === 'image'"
                            size="sm" variant="outline" class="flex-1 h-9"
                            @click="openViewer(item)"
                        >
                            <Eye class="w-3.5 h-3.5 mr-1.5" /> Lihat
                        </Button>
                        <Button size="sm" variant="outline" as-child class="flex-1 h-9">
                            <a :href="item.file_url" target="_blank">
                                <Download class="w-3.5 h-3.5 mr-1.5" /> Unduh
                            </a>
                        </Button>
                        <button
                            v-if="canManage"
                            type="button"
                            class="h-9 w-9 rounded-md text-red-600 hover:bg-red-50 transition-colors flex items-center justify-center"
                            @click="askDelete(item)"
                            title="Hapus informasi"
                        >
                            <Trash2 class="w-3.5 h-3.5" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Upload Modal ────────────────────────────────── -->
        <Dialog v-model:open="isUploadOpen">
            <DialogContent class="sm:max-w-[520px]">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <Upload class="w-5 h-5 text-blue-600" /> Unggah Informasi Baru
                    </DialogTitle>
                    <DialogDescription class="pt-1 text-xs">
                        Format: JPG, PNG, atau PDF — maksimal 10 MB.
                    </DialogDescription>
                </DialogHeader>
                <div class="space-y-3 py-2">
                    <div>
                        <Label for="info_title">Judul <span class="text-red-500">*</span></Label>
                        <Input id="info_title" v-model="uploadForm.title" placeholder="mis. Standar Pelayanan Kelurahan" class="mt-1" />
                        <p v-if="uploadForm.errors.title" class="text-xs text-red-600 mt-0.5">{{ uploadForm.errors.title }}</p>
                    </div>
                    <div>
                        <Label for="info_desc">Keterangan (opsional)</Label>
                        <textarea
                            id="info_desc"
                            v-model="uploadForm.description"
                            rows="3"
                            placeholder="Penjelasan singkat informasi ini..."
                            class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-400 resize-y"
                        ></textarea>
                    </div>
                    <div>
                        <Label for="info_file">File <span class="text-red-500">*</span></Label>
                        <input
                            id="info_file"
                            ref="fileInputRef"
                            type="file"
                            accept=".jpg,.jpeg,.png,.pdf"
                            class="mt-1 block w-full text-sm text-slate-700 file:mr-3 file:py-2 file:px-3 file:rounded-md file:border-0 file:bg-blue-50 file:text-blue-700 file:font-semibold hover:file:bg-blue-100 file:cursor-pointer"
                            @change="onFileChange"
                        />
                        <p v-if="uploadForm.errors.file" class="text-xs text-red-600 mt-0.5">{{ uploadForm.errors.file }}</p>
                        <p v-if="uploadForm.progress" class="text-xs text-slate-500 mt-1">
                            Mengunggah... {{ uploadForm.progress.percentage }}%
                        </p>
                    </div>
                    <!-- Preview image -->
                    <div v-if="previewUrl" class="rounded-md border border-slate-200 overflow-hidden bg-slate-50">
                        <img :src="previewUrl" class="w-full max-h-60 object-contain" />
                    </div>
                </div>
                <DialogFooter class="gap-2 sm:gap-0">
                    <Button variant="outline" @click="isUploadOpen = false" :disabled="uploadForm.processing">Batal</Button>
                    <Button @click="submitUpload" :disabled="uploadForm.processing || !uploadForm.file">
                        {{ uploadForm.processing ? 'Mengunggah...' : 'Unggah' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- ── Image Viewer Modal ──────────────────────────── -->
        <Dialog v-model:open="isViewerOpen">
            <DialogContent class="max-w-5xl p-2 sm:p-4">
                <DialogHeader>
                    <DialogTitle class="text-base">{{ viewerItem?.title }}</DialogTitle>
                    <DialogDescription v-if="viewerItem?.description" class="text-xs text-slate-600">
                        {{ viewerItem.description }}
                    </DialogDescription>
                </DialogHeader>
                <div class="flex justify-center bg-slate-100 rounded-md overflow-hidden">
                    <img v-if="viewerItem?.file_type === 'image'" :src="viewerItem?.file_url" class="max-h-[75vh] w-auto object-contain" />
                </div>
                <DialogFooter class="gap-2 sm:gap-0">
                    <Button variant="outline" @click="isViewerOpen = false">Tutup</Button>
                    <Button as-child>
                        <a :href="viewerItem?.file_url" target="_blank" download>
                            <Download class="w-4 h-4 mr-1.5" /> Unduh
                        </a>
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- ── Delete Confirm Modal ────────────────────────── -->
        <Dialog v-model:open="isDeleteOpen">
            <DialogContent class="sm:max-w-[440px]">
                <DialogHeader>
                    <div class="mx-auto sm:mx-0 w-12 h-12 rounded-full bg-red-100 flex items-center justify-center mb-2">
                        <AlertTriangle class="w-6 h-6 text-red-600" />
                    </div>
                    <DialogTitle class="text-center sm:text-left text-red-700">Hapus Informasi?</DialogTitle>
                    <DialogDescription class="pt-2 text-center sm:text-left">
                        Hapus <strong class="text-slate-900">{{ deleteItem?.title }}</strong> dari Papan Informasi?
                        File akan terhapus permanen.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2 sm:gap-0">
                    <Button variant="outline" @click="isDeleteOpen = false" :disabled="deleteProcessing">Batal</Button>
                    <Button @click="confirmDelete" :disabled="deleteProcessing" class="bg-red-600 hover:bg-red-700 text-white">
                        {{ deleteProcessing ? 'Menghapus...' : 'Ya, Hapus' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AuthenticatedLayout>
</template>
