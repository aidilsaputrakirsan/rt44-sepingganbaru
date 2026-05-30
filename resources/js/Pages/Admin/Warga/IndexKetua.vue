<script setup>
import { ref, computed, watch } from 'vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle
} from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue
} from '@/Components/ui/select';
import {
    Users, Plus, Search, X, Home, Phone, Mail, IdCard, UserPlus, UserMinus, Pencil,
    UserCircle, ChevronRight, AlertTriangle
} from 'lucide-vue-next';

const props = defineProps({
    houses: Array,
});

// ── Filter & Search ─────────────────────────────────────────
const searchQuery = ref('');
const statusFilter = ref('semua'); // semua | berpenghuni | kosong | kontrak | tanpa_kontrak

const filteredHouses = computed(() => {
    const q = searchQuery.value.toLowerCase().trim();
    return props.houses.filter(h => {
        // Search filter
        if (q) {
            const matchRumah = (h.blok + '/' + h.nomor).toLowerCase().includes(q);
            const matchOwner = (h.owner?.name || '').toLowerCase().includes(q);
            const matchTenant = (h.tenant?.name || '').toLowerCase().includes(q);
            if (!matchRumah && !matchOwner && !matchTenant) return false;
        }
        // Status filter
        if (statusFilter.value === 'berpenghuni' && h.status_huni !== 'berpenghuni') return false;
        if (statusFilter.value === 'kosong' && h.status_huni !== 'kosong') return false;
        if (statusFilter.value === 'kontrak' && !h.tenant) return false;
        if (statusFilter.value === 'tanpa_kontrak' && h.tenant) return false;
        return true;
    });
});

const stats = computed(() => ({
    total: props.houses.length,
    berpenghuni: props.houses.filter(h => h.status_huni === 'berpenghuni').length,
    kosong: props.houses.filter(h => h.status_huni === 'kosong').length,
    kontrak: props.houses.filter(h => !!h.tenant).length,
    tanpaKontrak: props.houses.filter(h => !h.tenant).length,
}));

// ── Tambah Warga (Rumah baru) ───────────────────────────────
const isAddOpen = ref(false);
const addForm = useForm({
    blok: '',
    nomor: '',
    status_huni: 'berpenghuni',
    resident_status: 'pemilik',
    name: '',
    email: '',
    phone_number: '',
    is_subsidized: false,
});

const generateEmail = (blok, nomor) => {
    if (!blok) return '';
    const b = blok.toLowerCase();
    const n = (nomor || '').toLowerCase();
    return n ? `${b}-${n}@rt44.com` : `${b}@rt44.com`;
};

watch(() => [addForm.blok, addForm.nomor], ([b, n]) => {
    addForm.email = generateEmail(b, n);
});

const openAdd = () => {
    addForm.reset();
    addForm.status_huni = 'berpenghuni';
    addForm.resident_status = 'pemilik';
    isAddOpen.value = true;
};

const submitAdd = () => {
    addForm.post(route('admin.warga.store'), {
        onSuccess: () => { isAddOpen.value = false; addForm.reset(); },
    });
};

// ── Edit Data Warga (Pemilik) ───────────────────────────────
const isEditOpen = ref(false);
const editHouse = ref(null);
const editForm = useForm({
    status_huni: 'berpenghuni',
    resident_status: 'pemilik',
    name: '',
    email: '',
    phone_number: '',
    is_subsidized: false,
});

const openEdit = (house) => {
    editHouse.value = house;
    editForm.status_huni = house.status_huni;
    editForm.resident_status = house.resident_status || 'belum_diketahui';
    editForm.name = house.owner?.name || '';
    editForm.email = house.owner?.email || generateEmail(house.blok, house.nomor);
    editForm.phone_number = house.owner?.phone_number || '';
    editForm.is_subsidized = !!house.is_subsidized;
    isEditOpen.value = true;
};

const submitEdit = () => {
    editForm.put(route('admin.warga.update', editHouse.value.id), {
        onSuccess: () => { isEditOpen.value = false; },
    });
};

// ── Kelola Kontrak ──────────────────────────────────────────
const isTenantOpen = ref(false);
const tenantHouse = ref(null);
const tenantMode = ref('create');
const tenantForm = useForm({ name: '', phone_number: '' });

const openTenantCreate = (house) => {
    tenantHouse.value = house;
    tenantMode.value = 'create';
    tenantForm.reset();
    isTenantOpen.value = true;
};

const openTenantEdit = (house) => {
    tenantHouse.value = house;
    tenantMode.value = 'edit';
    tenantForm.reset();
    tenantForm.name = house.tenant?.name || '';
    tenantForm.phone_number = house.tenant?.phone_number || '';
    isTenantOpen.value = true;
};

const submitTenant = () => {
    if (tenantMode.value === 'create') {
        tenantForm.post(route('admin.warga.tenant.store', tenantHouse.value.id), {
            preserveScroll: true,
            onSuccess: () => { isTenantOpen.value = false; tenantForm.reset(); },
        });
    } else {
        tenantForm.put(route('admin.warga.tenant.update', tenantHouse.value.id), {
            preserveScroll: true,
            onSuccess: () => { isTenantOpen.value = false; tenantForm.reset(); },
        });
    }
};

// Custom modal hapus kontrak (replace browser confirm)
const isDeleteTenantOpen = ref(false);
const deleteTenantHouse = ref(null);
const deleteTenantProcessing = ref(false);

const askRemoveTenant = (house) => {
    deleteTenantHouse.value = house;
    isDeleteTenantOpen.value = true;
};

const confirmRemoveTenant = () => {
    if (!deleteTenantHouse.value) return;
    deleteTenantProcessing.value = true;
    router.delete(route('admin.warga.tenant.destroy', deleteTenantHouse.value.id), {
        preserveScroll: true,
        onFinish: () => {
            deleteTenantProcessing.value = false;
            isDeleteTenantOpen.value = false;
            deleteTenantHouse.value = null;
        },
    });
};

// ── Helpers ─────────────────────────────────────────────────
const statusBadge = (status) => {
    if (status === 'pemilik') return { label: 'Pemilik', cls: 'bg-emerald-50 text-emerald-700 border-emerald-200' };
    if (status === 'kontrak') return { label: 'Kontrak', cls: 'bg-amber-50 text-amber-700 border-amber-200' };
    return { label: 'TBD', cls: 'bg-slate-50 text-slate-500 border-slate-200' };
};
const huniLabel = (s) => s === 'berpenghuni' ? 'Berpenghuni' : 'Kosong';
</script>

<template>
    <Head title="Data Warga (Ketua)" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-2">
                <Users class="w-6 h-6 text-blue-600 shrink-0" />
                <div>
                    <h2 class="text-xl font-bold text-slate-900 leading-tight">Data Warga</h2>
                    <p class="text-xs text-slate-500 uppercase tracking-wider font-medium">Mode Ketua RT</p>
                </div>
            </div>
        </template>

        <div class="py-6 max-w-7xl mx-auto space-y-6">
            <!-- Top bar: filter pills (left) + search & add button (right) -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                <!-- Filter pills (left) -->
                <div class="flex flex-wrap gap-2 min-w-0">
                    <button
                        type="button"
                        @click="statusFilter = 'semua'"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold border-2 transition-all"
                        :class="statusFilter === 'semua'
                            ? 'bg-blue-600 text-white border-blue-600 shadow-md shadow-blue-500/30'
                            : 'bg-white text-slate-700 border-slate-200 hover:border-slate-300 hover:bg-slate-50'"
                    >
                        Total
                        <span
                            class="px-1.5 py-0.5 rounded-full text-[11px] font-extrabold leading-none min-w-[22px] text-center"
                            :class="statusFilter === 'semua' ? 'bg-white/25 text-white' : 'bg-slate-100 text-slate-900'"
                        >{{ stats.total }}</span>
                    </button>
                    <button
                        type="button"
                        @click="statusFilter = 'berpenghuni'"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold border-2 transition-all"
                        :class="statusFilter === 'berpenghuni'
                            ? 'bg-emerald-600 text-white border-emerald-600 shadow-md shadow-emerald-500/30'
                            : 'bg-white text-emerald-700 border-emerald-200 hover:bg-emerald-50'"
                    >
                        Berpenghuni
                        <span
                            class="px-1.5 py-0.5 rounded-full text-[11px] font-extrabold leading-none min-w-[22px] text-center"
                            :class="statusFilter === 'berpenghuni' ? 'bg-white/25 text-white' : 'bg-emerald-100 text-emerald-900'"
                        >{{ stats.berpenghuni }}</span>
                    </button>
                    <button
                        type="button"
                        @click="statusFilter = 'kosong'"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold border-2 transition-all"
                        :class="statusFilter === 'kosong'
                            ? 'bg-slate-700 text-white border-slate-700 shadow-md shadow-slate-500/30'
                            : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                    >
                        Kosong
                        <span
                            class="px-1.5 py-0.5 rounded-full text-[11px] font-extrabold leading-none min-w-[22px] text-center"
                            :class="statusFilter === 'kosong' ? 'bg-white/25 text-white' : 'bg-slate-100 text-slate-900'"
                        >{{ stats.kosong }}</span>
                    </button>
                    <button
                        type="button"
                        @click="statusFilter = 'kontrak'"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold border-2 transition-all"
                        :class="statusFilter === 'kontrak'
                            ? 'bg-amber-600 text-white border-amber-600 shadow-md shadow-amber-500/30'
                            : 'bg-white text-amber-700 border-amber-200 hover:bg-amber-50'"
                    >
                        Ada Kontrak
                        <span
                            class="px-1.5 py-0.5 rounded-full text-[11px] font-extrabold leading-none min-w-[22px] text-center"
                            :class="statusFilter === 'kontrak' ? 'bg-white/25 text-white' : 'bg-amber-100 text-amber-900'"
                        >{{ stats.kontrak }}</span>
                    </button>
                </div>

                <!-- Search + Tambah (right) -->
                <div class="flex items-center gap-2 lg:shrink-0 w-full lg:w-auto">
                    <div class="flex items-center gap-2 h-10 px-3 rounded-md border border-slate-200 bg-white shadow-sm focus-within:ring-2 focus-within:ring-blue-500/40 focus-within:border-blue-400 transition-colors flex-1 lg:w-64">
                        <Search class="w-4 h-4 text-slate-500 shrink-0" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari rumah / nama..."
                            class="flex-1 min-w-0 h-full bg-transparent border-0 outline-none text-sm text-slate-800 placeholder:text-slate-400"
                        />
                        <button
                            v-if="searchQuery"
                            type="button"
                            class="text-slate-400 hover:text-slate-700 shrink-0"
                            @click="searchQuery = ''"
                            aria-label="Clear search"
                        >
                            <X class="w-4 h-4" />
                        </button>
                    </div>
                    <Button @click="openAdd" class="shadow-sm h-10 shrink-0">
                        <Plus class="w-4 h-4 mr-1.5" /> Tambah
                    </Button>
                </div>
            </div>

            <!-- Card grid -->
            <div v-if="filteredHouses.length === 0" class="text-center py-16 bg-white rounded-lg border border-slate-200">
                <Home class="w-12 h-12 text-slate-300 mx-auto mb-3" />
                <p class="text-sm text-slate-500">Tidak ada rumah yang cocok.</p>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                <div
                    v-for="house in filteredHouses"
                    :key="house.id"
                    class="bg-white rounded-xl border border-slate-200 shadow-sm hover:shadow-md hover:border-blue-200 transition-all overflow-hidden"
                >
                    <!-- Header: rumah + status -->
                    <div class="px-4 pt-4 pb-3 border-b border-slate-100 flex items-start justify-between gap-3">
                        <div class="flex items-center gap-2 min-w-0">
                            <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center shrink-0">
                                <Home class="w-5 h-5 text-blue-600" />
                            </div>
                            <div class="min-w-0">
                                <div class="font-bold text-lg text-slate-900 leading-tight">{{ house.blok }}/{{ house.nomor }}</div>
                                <div class="flex items-center gap-1 mt-0.5">
                                    <span
                                        class="text-[10px] font-semibold uppercase tracking-wide px-1.5 py-0.5 rounded"
                                        :class="house.status_huni === 'berpenghuni' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'"
                                    >
                                        {{ huniLabel(house.status_huni) }}
                                    </span>
                                    <Badge variant="outline" :class="statusBadge(house.resident_status).cls + ' text-[10px] px-1.5 py-0'">
                                        {{ statusBadge(house.resident_status).label }}
                                    </Badge>
                                    <Badge v-if="house.is_subsidized" variant="outline" class="bg-indigo-50 text-indigo-600 border-indigo-200 text-[10px] px-1.5 py-0">
                                        Subsidi
                                    </Badge>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Body: pemilik + kontrak -->
                    <div class="px-4 py-3 space-y-3">
                        <!-- Pemilik -->
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-full bg-emerald-50 flex items-center justify-center shrink-0 mt-0.5">
                                <UserCircle class="w-5 h-5 text-emerald-600" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="text-[10px] uppercase tracking-wider font-bold text-emerald-700">Pemilik</div>
                                <div class="font-semibold text-slate-900 truncate">{{ house.owner?.name || '-' }}</div>
                                <div v-if="house.owner?.phone_number" class="text-xs text-slate-500 flex items-center gap-1">
                                    <Phone class="w-3 h-3" /> {{ house.owner.phone_number }}
                                </div>
                            </div>
                        </div>

                        <!-- Kontrak -->
                        <div v-if="house.tenant" class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-full bg-amber-50 flex items-center justify-center shrink-0 mt-0.5">
                                <UserCircle class="w-5 h-5 text-amber-600" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="text-[10px] uppercase tracking-wider font-bold text-amber-700">Kontrak</div>
                                <div class="font-semibold text-slate-900 truncate">{{ house.tenant.name }}</div>
                                <div v-if="house.tenant.phone_number" class="text-xs text-slate-500 flex items-center gap-1">
                                    <Phone class="w-3 h-3" /> {{ house.tenant.phone_number }}
                                </div>
                            </div>
                        </div>
                        <div v-else>
                            <button
                                type="button"
                                class="w-full text-sm text-blue-600 hover:text-blue-700 hover:bg-blue-50 py-2 rounded-lg border border-dashed border-blue-200 flex items-center justify-center gap-1.5 transition-colors"
                                @click="openTenantCreate(house)"
                            >
                                <UserPlus class="w-4 h-4" /> Tambah Data Kontrak
                            </button>
                        </div>
                    </div>

                    <!-- Footer: action buttons -->
                    <div class="px-4 py-3 bg-slate-50/60 border-t border-slate-100 flex flex-wrap gap-2">
                        <Button size="sm" variant="outline" class="flex-1 min-w-[80px] h-9" @click="openEdit(house)">
                            <Pencil class="w-3.5 h-3.5 mr-1" /> Edit
                        </Button>
                        <Button v-if="house.owner" size="sm" variant="outline" as-child class="flex-1 min-w-[80px] h-9 text-emerald-700 border-emerald-200 hover:bg-emerald-50">
                            <Link :href="route('admin.warga.profil', { house: house.id })">
                                <IdCard class="w-3.5 h-3.5 mr-1" /> Profil Pemilik
                            </Link>
                        </Button>
                        <Button v-if="house.tenant" size="sm" variant="outline" as-child class="flex-1 min-w-[80px] h-9 text-amber-700 border-amber-200 hover:bg-amber-50">
                            <Link :href="route('admin.warga.profil', { house: house.id, slot: 'tenant' })">
                                <IdCard class="w-3.5 h-3.5 mr-1" /> Profil Kontrak
                            </Link>
                        </Button>
                        <Button v-if="house.tenant" size="sm" variant="ghost" class="h-9 text-slate-500 hover:bg-slate-100" @click="openTenantEdit(house)" title="Edit data kontrak">
                            <UserPlus class="w-3.5 h-3.5" />
                        </Button>
                        <Button v-if="house.tenant" size="sm" variant="ghost" class="h-9 text-red-600 hover:bg-red-50" @click="askRemoveTenant(house)" title="Hapus kontrak">
                            <UserMinus class="w-3.5 h-3.5" />
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Tambah Warga Modal ───────────────────────────── -->
        <Dialog v-model:open="isAddOpen">
            <DialogContent class="sm:max-w-[500px]">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <Plus class="w-5 h-5 text-blue-600" /> Tambah Warga Baru
                    </DialogTitle>
                    <DialogDescription class="pt-1 text-xs">
                        Saat rumah baru ditambah, sistem otomatis generate 12 tagihan setahun.
                    </DialogDescription>
                </DialogHeader>
                <div class="space-y-3 py-2">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <Label for="add_blok">Blok <span class="text-red-500">*</span></Label>
                            <Input id="add_blok" v-model="addForm.blok" placeholder="G1" class="mt-1" />
                            <p v-if="addForm.errors.blok" class="text-xs text-red-600 mt-0.5">{{ addForm.errors.blok }}</p>
                        </div>
                        <div>
                            <Label for="add_nomor">Nomor <span class="text-red-500">*</span></Label>
                            <Input id="add_nomor" v-model="addForm.nomor" placeholder="1" class="mt-1" />
                            <p v-if="addForm.errors.nomor" class="text-xs text-red-600 mt-0.5">{{ addForm.errors.nomor }}</p>
                        </div>
                    </div>
                    <div>
                        <Label for="add_name">Nama Pemilik</Label>
                        <Input id="add_name" v-model="addForm.name" placeholder="Nama lengkap" class="mt-1" />
                    </div>
                    <div>
                        <Label for="add_phone">No HP/WhatsApp</Label>
                        <Input id="add_phone" v-model="addForm.phone_number" placeholder="08xxxxxxxxxx" class="mt-1" />
                    </div>
                    <div>
                        <Label for="add_email">Email Login</Label>
                        <Input id="add_email" v-model="addForm.email" type="email" placeholder="email@contoh.com" class="mt-1" />
                        <p v-if="addForm.errors.email" class="text-xs text-red-600 mt-0.5">{{ addForm.errors.email }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <Label>Status Huni</Label>
                            <Select v-model="addForm.status_huni">
                                <SelectTrigger class="mt-1"><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="berpenghuni">Berpenghuni</SelectItem>
                                    <SelectItem value="kosong">Kosong</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div>
                            <Label>Status Kepemilikan</Label>
                            <Select v-model="addForm.resident_status">
                                <SelectTrigger class="mt-1"><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="pemilik">Pemilik</SelectItem>
                                    <SelectItem value="kontrak">Kontrak</SelectItem>
                                    <SelectItem value="belum_diketahui">Belum Diketahui</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </div>
                <DialogFooter class="gap-2 sm:gap-0">
                    <Button variant="outline" @click="isAddOpen = false">Batal</Button>
                    <Button @click="submitAdd" :disabled="addForm.processing">
                        {{ addForm.processing ? 'Menyimpan...' : 'Tambah Warga' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- ── Edit Warga Modal ─────────────────────────────── -->
        <Dialog v-model:open="isEditOpen">
            <DialogContent class="sm:max-w-[500px]">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <Pencil class="w-5 h-5 text-blue-600" /> Edit — {{ editHouse?.blok }}/{{ editHouse?.nomor }}
                    </DialogTitle>
                    <DialogDescription class="pt-1 text-xs">Edit data pemilik & status rumah.</DialogDescription>
                </DialogHeader>
                <div class="space-y-3 py-2">
                    <div>
                        <Label for="edit_name">Nama Pemilik</Label>
                        <Input id="edit_name" v-model="editForm.name" placeholder="Nama lengkap" class="mt-1" />
                    </div>
                    <div>
                        <Label for="edit_phone">No HP/WhatsApp</Label>
                        <Input id="edit_phone" v-model="editForm.phone_number" placeholder="08xxxxxxxxxx" class="mt-1" />
                    </div>
                    <div>
                        <Label for="edit_email">Email</Label>
                        <Input id="edit_email" v-model="editForm.email" type="email" class="mt-1" />
                        <p v-if="editForm.errors.email" class="text-xs text-red-600 mt-0.5">{{ editForm.errors.email }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <Label>Status Huni</Label>
                            <Select v-model="editForm.status_huni">
                                <SelectTrigger class="mt-1"><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="berpenghuni">Berpenghuni</SelectItem>
                                    <SelectItem value="kosong">Kosong</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div>
                            <Label>Status Kepemilikan</Label>
                            <Select v-model="editForm.resident_status">
                                <SelectTrigger class="mt-1"><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="pemilik">Pemilik</SelectItem>
                                    <SelectItem value="kontrak">Kontrak</SelectItem>
                                    <SelectItem value="belum_diketahui">Belum Diketahui</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </div>
                <DialogFooter class="gap-2 sm:gap-0">
                    <Button variant="outline" @click="isEditOpen = false">Batal</Button>
                    <Button @click="submitEdit" :disabled="editForm.processing">
                        {{ editForm.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- ── Hapus Kontrak Modal (custom, bukan confirm browser) ── -->
        <Dialog v-model:open="isDeleteTenantOpen">
            <DialogContent class="sm:max-w-[440px]">
                <DialogHeader>
                    <div class="mx-auto sm:mx-0 w-12 h-12 rounded-full bg-red-100 flex items-center justify-center mb-2">
                        <AlertTriangle class="w-6 h-6 text-red-600" />
                    </div>
                    <DialogTitle class="text-center sm:text-left text-red-700">Hapus Data Kontrak?</DialogTitle>
                    <DialogDescription class="pt-2 text-center sm:text-left">
                        Anda akan menghapus data kontrak
                        <strong class="text-slate-900">{{ deleteTenantHouse?.tenant?.name || '-' }}</strong>
                        dari rumah <strong class="text-slate-900">{{ deleteTenantHouse?.blok }}/{{ deleteTenantHouse?.nomor }}</strong>.
                    </DialogDescription>
                </DialogHeader>
                <div class="rounded-lg bg-red-50 border border-red-200 p-3 text-xs text-red-700 space-y-1">
                    <p class="font-semibold">Yang akan terhapus:</p>
                    <ul class="list-disc list-inside space-y-0.5 ml-1">
                        <li>Akun data kontrak</li>
                        <li>Profil kontrak (jumlah anggota keluarga, nomor KK)</li>
                        <li>File Kartu Keluarga kontrak</li>
                        <li>Semua file KTP kontrak</li>
                    </ul>
                    <p class="mt-2 font-semibold">⚠ Aksi ini tidak dapat dibatalkan.</p>
                </div>
                <DialogFooter class="gap-2 sm:gap-0">
                    <Button variant="outline" @click="isDeleteTenantOpen = false" :disabled="deleteTenantProcessing">
                        Batal
                    </Button>
                    <Button @click="confirmRemoveTenant" :disabled="deleteTenantProcessing" class="bg-red-600 hover:bg-red-700 text-white">
                        {{ deleteTenantProcessing ? 'Menghapus...' : 'Ya, Hapus Kontrak' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- ── Kelola Kontrak Modal ─────────────────────────── -->
        <Dialog v-model:open="isTenantOpen">
            <DialogContent class="sm:max-w-[440px]">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2 text-amber-700">
                        <UserPlus class="w-5 h-5" />
                        {{ tenantMode === 'create' ? 'Tambah' : 'Edit' }} Kontrak — {{ tenantHouse?.blok }}/{{ tenantHouse?.nomor }}
                    </DialogTitle>
                    <DialogDescription class="pt-1 text-xs">
                        Pemilik tetap: <strong>{{ tenantHouse?.owner?.name || '-' }}</strong>. Kontrak tidak punya akun login terpisah.
                    </DialogDescription>
                </DialogHeader>
                <div class="space-y-3 py-2">
                    <div>
                        <Label for="tenant_name">Nama Penghuni Kontrak <span class="text-red-500">*</span></Label>
                        <Input id="tenant_name" v-model="tenantForm.name" placeholder="Nama lengkap" class="mt-1" />
                        <p v-if="tenantForm.errors.name" class="text-xs text-red-600 mt-0.5">{{ tenantForm.errors.name }}</p>
                    </div>
                    <div>
                        <Label for="tenant_phone">No HP/WhatsApp</Label>
                        <Input id="tenant_phone" v-model="tenantForm.phone_number" placeholder="08xxxxxxxxxx" class="mt-1" />
                    </div>
                </div>
                <DialogFooter class="gap-2 sm:gap-0">
                    <Button variant="outline" @click="isTenantOpen = false">Batal</Button>
                    <Button @click="submitTenant" :disabled="tenantForm.processing" class="bg-amber-600 hover:bg-amber-700">
                        {{ tenantForm.processing ? 'Memproses...' : (tenantMode === 'create' ? 'Tambah Kontrak' : 'Simpan') }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AuthenticatedLayout>
</template>
