<script setup>
import { ref, computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { 
 Table, TableBody, TableCell, TableHead, TableHeader, TableRow 
} from '@/Components/ui/table';
import { 
 Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger 
} from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { 
 Select, SelectContent, SelectItem, SelectTrigger, SelectValue 
} from '@/Components/ui/select';
import {
 Users, Plus, Trash2, Edit2, Upload, FileText, CheckCircle2, AlertCircle, Search, X, FileSpreadsheet, RefreshCw
} from 'lucide-vue-next';

const props = defineProps({
 houses: Array,
});

const searchQuery = ref('');
const filteredHouses = computed(() => {
 const q = searchQuery.value.toLowerCase().trim();
 if (!q) return props.houses;
 return props.houses.filter(h =>
 (h.blok + '/' + h.nomor).toLowerCase().includes(q) ||
 (h.owner?.name || '').toLowerCase().includes(q)
 );
});

const isAddModalOpen = ref(false);
const isEditModalOpen = ref(false);
const isImportModalOpen = ref(false);
const selectedHouse = ref(null);

const form = useForm({
 blok: '',
 nomor: '',
 status_huni: 'berpenghuni',
 resident_status: 'belum_diketahui',
 name: '',
 email: '',
 phone_number: '',
});

const editForm = useForm({
 status_huni: 'berpenghuni',
 resident_status: 'belum_diketahui',
 name: '',
 email: '',
 phone_number: '',
});

const importForm = useForm({
 file: null,
});

const openAddModal = () => {
 form.reset();
 isAddModalOpen.value = true;
};

const openEditModal = (house) => {
 selectedHouse.value = house;
 editForm.status_huni = house.status_huni;
 editForm.resident_status = house.resident_status || 'belum_diketahui';
 editForm.name = house.owner ? house.owner.name : '';
 editForm.email = house.owner ? house.owner.email : '';
 editForm.phone_number = house.owner ? house.owner.phone_number : '';
 isEditModalOpen.value = true;
};

const submitAdd = () => {
 form.post(route('admin.warga.store'), {
 onSuccess: () => {
 isAddModalOpen.value = false;
 form.reset();
 },
 });
};

const submitUpdate = () => {
 editForm.put(route('admin.warga.update', selectedHouse.value.id), {
 onSuccess: () => {
 isEditModalOpen.value = false;
 },
 });
};

const recalculateForm = useForm({});

const recalculateDues = (house) => {
 if (confirm(`Recalculate tagihan rumah ${house.blok}/${house.nomor}?\nTagihan bulan ini & mendatang yang belum lunas akan disesuaikan dengan status huni saat ini.`)) {
 recalculateForm.post(route('admin.warga.recalculate', house.id));
 }
};

const deleteHouse = (id) => {
 if (confirm('Apakah Anda yakin ingin menghapus data rumah ini?')) {
 useForm({}).delete(route('admin.warga.destroy', id));
 }
};

const handleImport = () => {
 importForm.post(route('admin.warga.import'), {
 forceFormData: true,
 onSuccess: () => {
 isImportModalOpen.value = false;
 importForm.reset();
 },
 });
};

const getResidentStatusLabel = (status) => {
 if (status === 'pemilik') return 'Pemilik';
 if (status === 'kontrak') return 'Kontrak';
 return 'Belum Diketahui';
};

const getResidentStatusVariant = (status) => {
 if (status === 'pemilik') return 'default';
 if (status === 'kontrak') return 'outline';
 return 'outline';
};
</script>

<template>
 <Head title="Data Warga" />

 <AuthenticatedLayout>
 <template #header>
 <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
 <div>
 <h2 class="text-2xl font-bold text-slate-900 flex items-center gap-2">
 <Users class="w-6 h-6 text-indigo-600" />
 Data Warga
 </h2>
 <p class="text-slate-500 mt-1 uppercase text-sm tracking-wider font-medium">
 Manajemen Rumah & Penghuni RT-44
 </p>
 </div>

 <div class="flex items-center gap-3">
 <Button variant="outline" @click="isImportModalOpen = true" class="flex items-center gap-2 shadow-sm border-slate-200">
 <FileSpreadsheet class="w-4 h-4" />
 Import Excel
 </Button>
 <Button @click="openAddModal" class="bg-indigo-600 hover:bg-indigo-700 shadow-md flex items-center gap-2">
 <Plus class="w-4 h-4" />
 Tambah Warga
 </Button>
 </div>
 </div>
 </template>

 <div class="py-12">
 <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
 <!-- Info Section -->
 <div class="bg-blue-50 border border-blue-100 p-4 rounded-xl flex items-start gap-4">
 <div class="bg-blue-100 p-2 rounded-lg text-blue-600">
 <CheckCircle2 class="w-5 h-5" />
 </div>
 <div>
 <h4 class="font-bold text-blue-900">Tips Kelola Data</h4>
 <p class="text-sm text-blue-700 mt-1">
 Mengubah status huni akan otomatis menyesuaikan tagihan bulan ini & mendatang yang belum lunas.
 Gunakan tombol <span class="inline-flex items-center"><RefreshCw class="w-3 h-3 mx-0.5" /></span> untuk recalculate manual jika diperlukan.
 </p>
 </div>
 </div>

 <!-- Data Table -->
 <div class="bg-white overflow-hidden shadow-xl sm:rounded-2xl border border-slate-200">
 <div class="p-6">
 <!-- Search -->
 <div class="mb-4 relative max-w-xs">
 <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
 <Input
 v-model="searchQuery"
 type="text"
 placeholder="Cari rumah atau pemilik..."
 class="pl-9 pr-8 h-9 text-sm"
 />
 <button v-if="searchQuery" @click="searchQuery = ''" class="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground">
 <X class="w-4 h-4" />
 </button>
 </div>

 <Table>
 <TableHeader>
 <TableRow class="hover:bg-transparent border-slate-200">
 <TableHead class="w-32">Rumah</TableHead>
 <TableHead>Pemilik / Penghuni</TableHead>
 <TableHead>Kontak</TableHead>
 <TableHead class="text-center">Status Huni</TableHead>
 <TableHead class="text-center">Status Kepemilikan</TableHead>
 <TableHead class="text-right">Action</TableHead>
 </TableRow>
 </TableHeader>
 <TableBody>
 <TableRow v-for="house in filteredHouses" :key="house.id" class="border-slate-100">
 <TableCell class="font-bold text-indigo-600 py-4">
 {{ house.blok }}/{{ house.nomor }}
 </TableCell>
 <TableCell>
 <div class="font-semibold text-slate-900">
 {{ house.owner ? house.owner.name : '-' }}
 </div>
 <div class="text-xs text-slate-500 truncate max-w-[200px]" v-if="house.owner">
 {{ house.owner.email }}
 </div>
 </TableCell>
 <TableCell class="text-slate-600 italic text-sm">
 {{ house.owner?.phone_number || '-' }}
 </TableCell>
 <TableCell class="text-center">
 <Badge 
 :variant="house.status_huni === 'berpenghuni' ? 'default' : 'outline'"
 :class="house.status_huni === 'berpenghuni' ? 'bg-green-100 text-green-700 border-green-200 hover:bg-green-100' : 'bg-slate-50 text-slate-600 border-slate-200'"
 >
 {{ house.status_huni === 'berpenghuni' ? 'Berpenghuni' : 'Kosong' }}
 </Badge>
 </TableCell>
 <TableCell class="text-center">
 <Badge
 :variant="getResidentStatusVariant(house.resident_status)"
 :class="house.resident_status === 'belum_diketahui' ? 'bg-amber-50 text-amber-600 border-amber-200' : ''"
 >
 {{ getResidentStatusLabel(house.resident_status) }}
 </Badge>
 </TableCell>
 <TableCell class="text-right">
 <div class="flex items-center justify-end gap-1">
 <Button
 variant="ghost"
 size="icon"
 class="h-8 w-8 text-slate-500 hover:text-indigo-600"
 @click="openEditModal(house)"
 title="Edit data warga"
 >
 <Edit2 class="w-4 h-4" />
 </Button>
 <Button
 variant="ghost"
 size="icon"
 class="h-8 w-8 text-slate-500 hover:text-amber-600"
 @click="recalculateDues(house)"
 :disabled="recalculateForm.processing"
 title="Recalculate tagihan berdasarkan status huni saat ini"
 >
 <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': recalculateForm.processing }" />
 </Button>
 <Button
 variant="ghost"
 size="icon"
 class="h-8 w-8 text-slate-500 hover:text-red-600"
 @click="deleteHouse(house.id)"
 >
 <Trash2 class="w-4 h-4" />
 </Button>
 </div>
 </TableCell>
 </TableRow>

 <TableRow v-if="filteredHouses.length === 0">
 <TableCell colspan="6" class="text-center py-12 text-slate-500">
 Belum ada data warga.
 </TableCell>
 </TableRow>
 </TableBody>
 </Table>
 </div>
 </div>
 </div>
 </div>

 <!-- Add Modal -->
 <Dialog v-model:open="isAddModalOpen">
 <DialogContent class="sm:max-w-[500px]">
 <DialogHeader>
 <DialogTitle>Tambah Data Warga</DialogTitle>
 <DialogDescription>
 Input data rumah dan pemilik baru ke dalam sistem.
 </DialogDescription>
 </DialogHeader>
 <div class="grid gap-4 py-4">
 <div class="grid grid-cols-2 gap-4">
 <div class="grid gap-2">
 <Label for="blok">Blok</Label>
 <Input id="blok" v-model="form.blok" placeholder="Misal: G1" />
 </div>
 <div class="grid gap-2">
 <Label for="nomor">Nomor Rumah</Label>
 <Input id="nomor" v-model="form.nomor" placeholder="Misal: 1" />
 </div>
 </div>
 
 <div class="grid grid-cols-2 gap-4">
 <div class="grid gap-2">
 <Label>Status Huni</Label>
 <Select v-model="form.status_huni">
 <SelectTrigger>
 <SelectValue placeholder="Pilih status" />
 </SelectTrigger>
 <SelectContent>
 <SelectItem value="berpenghuni">Berpenghuni</SelectItem>
 <SelectItem value="kosong">Kosong</SelectItem>
 </SelectContent>
 </Select>
 </div>
 <div class="grid gap-2">
 <Label>Status Kepemilikan</Label>
 <Select v-model="form.resident_status">
 <SelectTrigger>
 <SelectValue placeholder="Pilih status" />
 </SelectTrigger>
 <SelectContent>
 <SelectItem value="pemilik">Pemilik</SelectItem>
 <SelectItem value="kontrak">Kontrak</SelectItem>
 <SelectItem value="belum_diketahui">Belum Diketahui</SelectItem>
 </SelectContent>
 </Select>
 </div>
 </div>

 <div class="border-t pt-4 mt-2">
 <p class="text-sm font-bold text-slate-900 mb-4">Informasi Penghuni / Pemilik</p>
 <div class="grid gap-4">
 <div class="grid gap-2">
 <Label for="name">Nama Lengkap</Label>
 <Input id="name" v-model="form.name" />
 </div>
 <div class="grid gap-2">
 <Label for="email">Email (Username)</Label>
 <Input id="email" v-model="form.email" type="email" placeholder="email@rt44.com" />
 </div>
 <div class="grid gap-2">
 <Label for="phone">Nomor HP</Label>
 <Input id="phone" v-model="form.phone_number" placeholder="08..." />
 </div>
 </div>
 </div>
 </div>
 <DialogFooter>
 <Button variant="outline" @click="isAddModalOpen = false">Batal</Button>
 <Button @click="submitAdd" :disabled="form.processing" class="bg-indigo-600 hover:bg-indigo-700">
 Simpan Data
 </Button>
 </DialogFooter>
 </DialogContent>
 </Dialog>

 <!-- Edit Modal -->
 <Dialog v-model:open="isEditModalOpen">
 <DialogContent class="sm:max-w-[500px]">
 <DialogHeader>
 <DialogTitle>Ubah Data Warga</DialogTitle>
 <DialogDescription>
 Perbarui informasi rumah {{ selectedHouse?.blok }}/{{ selectedHouse?.nomor }}
 </DialogDescription>
 </DialogHeader>
 <div class="grid gap-4 py-4">
 <div class="grid grid-cols-2 gap-4">
 <div class="grid gap-2">
 <Label>Status Huni</Label>
 <Select v-model="editForm.status_huni">
 <SelectTrigger>
 <SelectValue placeholder="Pilih status" />
 </SelectTrigger>
 <SelectContent>
 <SelectItem value="berpenghuni">Berpenghuni</SelectItem>
 <SelectItem value="kosong">Kosong</SelectItem>
 </SelectContent>
 </Select>
 </div>
 <div class="grid gap-2">
 <Label>Status Kepemilikan</Label>
 <Select v-model="editForm.resident_status">
 <SelectTrigger>
 <SelectValue placeholder="Pilih status" />
 </SelectTrigger>
 <SelectContent>
 <SelectItem value="pemilik">Pemilik</SelectItem>
 <SelectItem value="kontrak">Kontrak</SelectItem>
 <SelectItem value="belum_diketahui">Belum Diketahui</SelectItem>
 </SelectContent>
 </Select>
 </div>
 </div>

 <div class="border-t pt-4 mt-2">
 <p class="text-sm font-bold text-slate-900 mb-4">Informasi Penghuni / Pemilik</p>
 <div class="grid gap-4">
 <div class="grid gap-2">
 <Label for="edit_name">Nama Lengkap</Label>
 <Input id="edit_name" v-model="editForm.name" />
 </div>
 <div class="grid gap-2">
 <Label for="edit_email">Email (Username)</Label>
 <Input id="edit_email" v-model="editForm.email" type="email" />
 </div>
 <div class="grid gap-2">
 <Label for="edit_phone">Nomor HP</Label>
 <Input id="edit_phone" v-model="editForm.phone_number" />
 </div>
 </div>
 </div>
 </div>
 <DialogFooter>
 <Button variant="outline" @click="isEditModalOpen = false">Batal</Button>
 <Button @click="submitUpdate" :disabled="editForm.processing" class="bg-indigo-600 hover:bg-indigo-700">
 Update Data
 </Button>
 </DialogFooter>
 </DialogContent>
 </Dialog>

 <!-- Import Modal -->
 <Dialog v-model:open="isImportModalOpen">
 <DialogContent class="sm:max-w-[525px]">
 <DialogHeader>
 <DialogTitle>Import Data Warga</DialogTitle>
 <DialogDescription>
 Upload file Excel (.xlsx) untuk memproses data warga secara massal.
 </DialogDescription>
 </DialogHeader>
 <div class="grid gap-4 py-4">
 <!-- Error display -->
 <div v-if="importForm.errors.file" class="bg-red-50 border border-red-200 rounded-lg p-4">
 <div class="flex items-start gap-2">
 <AlertCircle class="w-5 h-5 text-red-500 mt-0.5 shrink-0" />
 <div class="text-sm text-red-700 whitespace-pre-line">{{ importForm.errors.file }}</div>
 </div>
 </div>

 <div class="bg-indigo-50 p-4 rounded-lg border border-indigo-100 space-y-2">
 <p class="text-xs font-bold text-indigo-900 uppercase tracking-wider">Format Kolom Excel</p>
 <div class="text-xs text-slate-600 space-y-1">
 <p><span class="font-semibold">A.</span> Rumah <span class="text-slate-400">(wajib, format: G1/2)</span></p>
 <p><span class="font-semibold">B.</span> Pemilik / Penghuni <span class="text-slate-400">(opsional)</span></p>
 <p><span class="font-semibold">C.</span> Kontak <span class="text-slate-400">(opsional)</span></p>
 <p><span class="font-semibold">D.</span> Status Huni <span class="text-slate-400">(Berpenghuni / Kosong)</span></p>
 <p><span class="font-semibold">E.</span> Status Kepemilikan <span class="text-slate-400">(Pemilik / Kontrak, opsional)</span></p>
 </div>
 </div>

 <a
 :href="route('admin.warga.template')"
 class="flex items-center gap-2 text-sm text-indigo-600 hover:text-indigo-700 font-semibold"
 >
 <FileSpreadsheet class="w-4 h-4" />
 Download Template Excel
 </a>

 <div class="grid gap-2">
 <Label for="excel_file">File Excel (.xlsx, .xls)</Label>
 <Input
 id="excel_file"
 type="file"
 accept=".xlsx,.xls"
 @input="importForm.file = $event.target.files[0]; importForm.clearErrors('file')"
 />
 </div>
 </div>
 <DialogFooter>
 <Button variant="outline" @click="isImportModalOpen = false">Batal</Button>
 <Button
 @click="handleImport"
 :disabled="importForm.processing || !importForm.file"
 class="bg-indigo-600 hover:bg-indigo-700"
 >
 <Upload class="w-4 h-4 mr-2" />
 Mulai Import
 </Button>
 </DialogFooter>
 </DialogContent>
 </Dialog>

 </AuthenticatedLayout>
</template>
