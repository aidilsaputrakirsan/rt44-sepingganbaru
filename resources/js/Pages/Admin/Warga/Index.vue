<script setup>
import { ref, computed, watch } from 'vue';
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
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
 Users, Plus, Trash2, Edit2, Upload, FileText, AlertCircle, Search, X, FileSpreadsheet
} from 'lucide-vue-next';

const props = defineProps({
 houses: Array,
});

const isDemo = computed(() => usePage().props.auth.is_demo);

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
const isDeleteModalOpen = ref(false);
const selectedHouse = ref(null);
const houseToDelete = ref(null);

const generateEmail = (blok, nomor) => {
 if (!blok) return '';
 const b = blok.toLowerCase();
 const n = (nomor || '').toLowerCase();
 return n ? `${b}-${n}@rt44.com` : `${b}@rt44.com`;
};

const form = useForm({
 blok: '',
 nomor: '',
 status_huni: 'berpenghuni',
 resident_status: 'belum_diketahui',
 name: '',
 email: '',
 phone_number: '',
 is_subsidized: false,
});

// Auto-generate email saat blok/nomor berubah di form Tambah
watch(() => [form.blok, form.nomor], ([blok, nomor]) => {
 form.email = generateEmail(blok, nomor);
});

const editForm = useForm({
 status_huni: 'berpenghuni',
 resident_status: 'belum_diketahui',
 name: '',
 email: '',
 phone_number: '',
 is_subsidized: false,
});

const importForm = useForm({
 file: null,
});

const openAddModal = () => {
 form.reset();
 isAddModalOpen.value = true;
};

const editEmailGenerated = computed(() => {
 if (!selectedHouse.value) return '';
 return generateEmail(selectedHouse.value.blok, selectedHouse.value.nomor);
});

const openEditModal = (house) => {
 selectedHouse.value = house;
 editForm.status_huni = house.status_huni;
 editForm.resident_status = house.resident_status || 'belum_diketahui';
 editForm.name = house.owner ? house.owner.name : '';
 editForm.email = house.owner ? house.owner.email : generateEmail(house.blok, house.nomor);
 editForm.phone_number = house.owner ? house.owner.phone_number : '';
 editForm.is_subsidized = !!house.is_subsidized;
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

const deleteHouse = (house) => {
 if (isDemo.value) return;
 houseToDelete.value = house;
 isDeleteModalOpen.value = true;
};

const confirmDelete = () => {
 if (!houseToDelete.value) return;
 
 router.delete(route('admin.warga.destroy', houseToDelete.value.id), {
   preserveScroll: true,
   onSuccess: () => {
     isDeleteModalOpen.value = false;
     houseToDelete.value = null;
   },
 });
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

 <div v-if="!isDemo" class="flex items-center gap-3">
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
 <TableHead v-if="!isDemo" class="text-center">Aksi</TableHead>
 </TableRow>
 </TableHeader>
 <TableBody>
 <TableRow v-for="house in filteredHouses" :key="house.id" class="border-slate-100 cursor-pointer hover:bg-slate-50/50 transition-colors" @click="!isDemo && openEditModal(house)">
 <TableCell class="font-bold text-indigo-600 py-4">
 <div class="flex items-center gap-2">
  <span>{{ house.blok }}/{{ house.nomor }}</span>
  <Badge v-if="house.is_subsidized" variant="outline" class="bg-indigo-50 text-indigo-600 border-indigo-200 uppercase text-[10px]">
  Subsidi
  </Badge>
 </div>
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
 <TableCell v-if="!isDemo" class="text-center">
 <div class="flex items-center justify-center gap-1">
 <Button
 variant="ghost"
 size="icon"
 class="h-8 w-8 text-slate-500 hover:text-indigo-600"
 @click.stop="openEditModal(house)"
 title="Edit data warga"
 >
 <Edit2 class="w-4 h-4" />
 </Button>
 <button
 class="h-8 w-8 text-slate-500 hover:text-red-600 flex items-center justify-center rounded-md hover:bg-slate-100 transition-colors"
 @click.stop="deleteHouse(house)"
 title="Hapus data rumah"
 >
 <Trash2 class="w-4 h-4" />
 </button>
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
 <div class="grid gap-2 mt-4">
 <Label>Kewajiban Iuran</Label>
 <Select v-model="form.is_subsidized">
 <SelectTrigger>
 <SelectValue placeholder="Pilih jenis" />
 </SelectTrigger>
 <SelectContent>
 <SelectItem :value="false">Reguler (Wajib Bayar)</SelectItem>
 <SelectItem :value="true">Bebas Iuran (Subsidi RT)</SelectItem>
 </SelectContent>
 </Select>
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
 <Input id="email" v-model="form.email" type="email" readonly class="bg-slate-50 cursor-not-allowed" />
 <p class="text-xs text-slate-500">Otomatis: <span class="font-mono font-semibold text-indigo-600">{{ form.email || 'blok-nomor@rt44.com' }}</span></p>
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
 <div class="grid gap-2 mt-4">
 <Label>Kewajiban Iuran</Label>
 <Select v-model="editForm.is_subsidized">
 <SelectTrigger>
 <SelectValue placeholder="Pilih jenis" />
 </SelectTrigger>
 <SelectContent>
 <SelectItem :value="false">Reguler (Wajib Bayar)</SelectItem>
 <SelectItem :value="true">Bebas Iuran (Subsidi RT)</SelectItem>
 </SelectContent>
 </Select>
 </div>
 <p class="text-[10px] text-amber-600 flex flex-col items-start gap-0.5 leading-tight mt-1">
 <span>⚡ Mengubah status huni otomatis menyesuaikan tagihan berjalan.</span>
 <span>⚡ Beralih ke opsi <b>Subsidi</b> otomatis <b>menghapus</b> seluruh tagihan yang belum dibayar.</span>
 </p>

 <div class="border-t pt-4 mt-2">
 <p class="text-sm font-bold text-slate-900 mb-4">Informasi Penghuni / Pemilik</p>
 <div class="grid gap-4">
 <div class="grid gap-2">
 <Label for="edit_name">Nama Lengkap</Label>
 <Input id="edit_name" v-model="editForm.name" />
 </div>
 <div class="grid gap-2">
 <Label for="edit_email">Email (Username)</Label>
 <Input id="edit_email" v-model="editForm.email" type="email" readonly class="bg-slate-50 cursor-not-allowed" />
 <p class="text-xs text-slate-500">Format: <span class="font-mono font-semibold text-indigo-600">{{ editEmailGenerated }}</span></p>
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

 <!-- Delete Confirmation Modal -->
 <Dialog v-model:open="isDeleteModalOpen">
 <DialogContent class="sm:max-w-[400px]">
 <DialogHeader>
 <DialogTitle class="flex items-center gap-2 text-red-600">
 <AlertCircle class="w-5 h-5" />
 Konfirmasi Hapus
 </DialogTitle>
 <DialogDescription class="pt-2 text-slate-600">
 Apakah Anda yakin ingin menghapus data rumah <strong>{{ houseToDelete?.blok }}/{{ houseToDelete?.nomor }}</strong>?
 <p class="mt-2 text-xs text-red-500 font-medium">Aksi ini menghapus seluruh riwayat tagihan & tidak dapat dibatalkan.</p>
 </DialogDescription>
 </DialogHeader>
 <DialogFooter class="gap-2 sm:gap-0">
 <Button variant="outline" @click="isDeleteModalOpen = false" class="border-slate-200">Batal</Button>
 <Button @click="confirmDelete" class="bg-red-600 hover:bg-red-700 text-white shadow-md">
 Ya, Hapus Data
 </Button>
 </DialogFooter>
 </DialogContent>
 </Dialog>

 </AuthenticatedLayout>
</template>
