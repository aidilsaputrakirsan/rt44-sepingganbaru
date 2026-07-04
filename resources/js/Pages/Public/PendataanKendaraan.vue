<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed, nextTick } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Car, Bike, Search, CheckCircle2, Home, X, Info, ArrowLeft } from 'lucide-vue-next';

const props = defineProps({
    houses: {
        type: Array,
        default: () => [],
    },
});

const search = ref('');
const showList = ref(false);
const selectedHouse = ref(null);
const submitted = ref(false);
const searchInput = ref(null);

const filteredHouses = computed(() => {
    const q = search.value.trim().toLowerCase();
    const list = !q ? props.houses : props.houses.filter(h => h.label.toLowerCase().includes(q));
    return list.slice(0, 30);
});

const form = useForm({
    house_id: null,
    jumlah_mobil: 0,
    jumlah_motor: 0,
});

const hadPrevious = computed(() => selectedHouse.value?.jumlah_mobil != null || selectedHouse.value?.jumlah_motor != null);

const selectHouse = (house) => {
    selectedHouse.value = house;
    form.house_id = house.id;
    form.jumlah_mobil = house.jumlah_mobil ?? 0;
    form.jumlah_motor = house.jumlah_motor ?? 0;
    showList.value = false;
};

const changeHouse = () => {
    selectedHouse.value = null;
    form.house_id = null;
    form.jumlah_mobil = 0;
    form.jumlah_motor = 0;
    search.value = '';
    nextTick(() => searchInput.value?.focus?.());
};

const onBlur = () => {
    // Delay agar klik pada item list sempat terdaftar sebelum list disembunyikan.
    setTimeout(() => { showList.value = false; }, 150);
};

const submit = () => {
    form.post(route('kendaraan.store'), {
        preserveScroll: true,
        onSuccess: () => {
            submitted.value = true;
        },
    });
};

</script>

<template>
    <Head title="Pendataan Kendaraan Warga" />

    <div class="min-h-screen flex flex-col items-center justify-center bg-gradient-to-br from-amber-50/60 via-white to-orange-50/40 px-4 py-10">
        <div class="w-full max-w-md">
            <div class="flex flex-col items-center text-center mb-6">
                <img src="/images/sticker.jpeg" alt="Contoh Stiker RT-44" class="w-28 h-28 object-contain mb-3 drop-shadow" />
                <h1 class="text-xl font-bold text-slate-900">RT-44 Sepinggan Baru</h1>
                <p class="text-sm text-slate-500 mt-1">Pendataan Kendaraan Warga — untuk keperluan stiker penanda RT</p>
            </div>

            <Card>
                <template v-if="!submitted">
                    <CardHeader>
                        <CardTitle class="text-base">Isi Data Kendaraan</CardTitle>
                        <p class="text-sm text-muted-foreground">
                            Silakan pilih rumah Anda dan isi jumlah kendaraan (mobil & motor) yang dimiliki.
                        </p>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="submit" class="space-y-4">
                            <div>
                                <Label class="mb-1 block">Rumah</Label>

                                <div v-if="selectedHouse" class="flex items-center justify-between rounded-md border border-input bg-slate-50 px-3 py-2">
                                    <div class="flex items-center gap-2 text-sm font-medium text-slate-800">
                                        <Home class="w-4 h-4 text-amber-600 shrink-0" />
                                        {{ selectedHouse.label }}
                                    </div>
                                    <button type="button" @click="changeHouse" class="text-muted-foreground hover:text-foreground">
                                        <X class="w-4 h-4" />
                                    </button>
                                </div>

                                <div v-else class="relative">
                                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                                    <Input
                                        ref="searchInput"
                                        v-model="search"
                                        type="text"
                                        placeholder="Cari blok/nomor rumah atau nama..."
                                        class="pl-9"
                                        autocomplete="off"
                                        @focus="showList = true"
                                        @blur="onBlur"
                                    />
                                    <div
                                        v-if="showList"
                                        class="absolute z-10 mt-1 w-full max-h-56 overflow-y-auto rounded-md border border-input bg-white shadow-lg"
                                    >
                                        <button
                                            v-for="house in filteredHouses"
                                            :key="house.id"
                                            type="button"
                                            @mousedown.prevent
                                            @click="selectHouse(house)"
                                            class="w-full text-left px-3 py-2 text-sm hover:bg-amber-50 transition-colors"
                                        >
                                            {{ house.label }}
                                        </button>
                                        <p v-if="filteredHouses.length === 0" class="px-3 py-2 text-sm text-muted-foreground">
                                            Rumah tidak ditemukan.
                                        </p>
                                    </div>
                                </div>
                                <p v-if="form.errors.house_id" class="text-xs text-red-600 mt-1">{{ form.errors.house_id }}</p>
                            </div>

                            <div v-if="hadPrevious" class="flex items-start gap-2 rounded-md bg-sky-50 border border-sky-200 px-3 py-2 text-xs text-sky-700">
                                <Info class="w-4 h-4 shrink-0 mt-0.5" />
                                <span>
                                    Data sebelumnya: <b>{{ selectedHouse.jumlah_mobil ?? 0 }} mobil</b>, <b>{{ selectedHouse.jumlah_motor ?? 0 }} motor</b>
                                    <template v-if="selectedHouse.diisi_pada"> (diisi {{ selectedHouse.diisi_pada }})</template>.
                                    Ubah angka di bawah lalu kirim untuk memperbarui.
                                </span>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <Label class="mb-1 flex items-center gap-1.5"><Car class="w-4 h-4 text-slate-500" /> Jumlah Mobil</Label>
                                    <Input v-model.number="form.jumlah_mobil" type="number" min="0" max="20" />
                                    <p v-if="form.errors.jumlah_mobil" class="text-xs text-red-600 mt-1">{{ form.errors.jumlah_mobil }}</p>
                                </div>
                                <div>
                                    <Label class="mb-1 flex items-center gap-1.5"><Bike class="w-4 h-4 text-slate-500" /> Jumlah Motor</Label>
                                    <Input v-model.number="form.jumlah_motor" type="number" min="0" max="20" />
                                    <p v-if="form.errors.jumlah_motor" class="text-xs text-red-600 mt-1">{{ form.errors.jumlah_motor }}</p>
                                </div>
                            </div>

                            <Button type="submit" class="w-full bg-amber-600 hover:bg-amber-700" :disabled="form.processing || !form.house_id">
                                {{ hadPrevious ? 'Perbarui Data' : 'Kirim Data' }}
                            </Button>

                            <p class="text-[11px] text-muted-foreground text-center">
                                Sudah pernah isi? Mengisi ulang akan menggantikan data sebelumnya.
                            </p>
                        </form>
                    </CardContent>
                </template>

                <template v-else>
                    <CardContent class="py-10 flex flex-col items-center text-center">
                        <CheckCircle2 class="w-14 h-14 text-emerald-500 mb-3" />
                        <p class="font-semibold text-slate-900">Data berhasil disimpan</p>
                        <p class="text-sm text-muted-foreground mt-1">Terima kasih, data kendaraan Anda sudah tercatat.</p>
                    </CardContent>
                </template>
            </Card>

            <div class="text-center mt-6">
                <Link href="/" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-amber-700 transition-colors">
                    <ArrowLeft class="w-4 h-4" />
                    Kembali ke Halaman Utama
                </Link>
            </div>
        </div>
    </div>
</template>
