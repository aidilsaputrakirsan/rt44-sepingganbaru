<script setup>
import { ref, computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter
} from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import {
    ShieldCheck, KeyRound, Eye, EyeOff, Copy, Check, Pencil, Mail, Phone, RefreshCw, UserCircle
} from 'lucide-vue-next';

const props = defineProps({
    accounts: Array,
});

// ── Reveal password per akun ────────────────────────────────
const revealed = ref({});         // { [id]: bool }
const copiedId = ref(null);
const toggleReveal = (id) => { revealed.value[id] = !revealed.value[id]; };

const copyPassword = async (acc) => {
    if (!acc.password_plain) return;
    try {
        await navigator.clipboard.writeText(acc.password_plain);
        copiedId.value = acc.id;
        setTimeout(() => { if (copiedId.value === acc.id) copiedId.value = null; }, 1500);
    } catch (e) { /* clipboard tidak tersedia */ }
};

// ── Reset password modal ────────────────────────────────────
const isPwOpen = ref(false);
const pwAccount = ref(null);
const pwForm = useForm({ password: '' });
const pwShow = ref(true);

const openPw = (acc) => {
    pwAccount.value = acc;
    pwForm.reset();
    pwForm.clearErrors();
    pwShow.value = true;
    isPwOpen.value = true;
};

const genPassword = () => {
    const chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    let out = '';
    for (let i = 0; i < 10; i++) out += chars[Math.floor(Math.random() * chars.length)];
    pwForm.password = out;
};

const submitPw = () => {
    pwForm.put(route('superadmin.akun.password', pwAccount.value.id), {
        preserveScroll: true,
        onSuccess: () => { isPwOpen.value = false; },
    });
};

// ── Edit profil modal ───────────────────────────────────────
const isProfOpen = ref(false);
const profForm = useForm({ name: '', email: '', phone_number: '' });
const profAccount = ref(null);

const openProf = (acc) => {
    profAccount.value = acc;
    profForm.name = acc.name;
    profForm.email = acc.email;
    profForm.phone_number = acc.phone_number || '';
    profForm.clearErrors();
    isProfOpen.value = true;
};

const submitProf = () => {
    profForm.put(route('superadmin.akun.profile', profAccount.value.id), {
        preserveScroll: true,
        onSuccess: () => { isProfOpen.value = false; },
    });
};

const roleBadge = (role) => role === 'admin'
    ? { label: 'Bendahara', cls: 'bg-emerald-50 text-emerald-700 border-emerald-200' }
    : { label: 'Ketua', cls: 'bg-blue-50 text-blue-700 border-blue-200' };
</script>

<template>
    <Head title="Kelola Akun" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-2">
                <ShieldCheck class="w-6 h-6 text-rose-600 shrink-0" />
                <div>
                    <h2 class="text-xl font-bold text-slate-900 leading-tight">Kelola Akun Pengelola</h2>
                    <p class="text-xs text-slate-500 uppercase tracking-wider font-medium">Super Admin · Akun Bendahara & Ketua</p>
                </div>
            </div>
        </template>

        <div class="py-6 max-w-4xl mx-auto space-y-5">
            <!-- Peringatan keamanan -->
            <div class="flex items-start gap-2 rounded-lg bg-rose-50 border border-rose-200 p-3 text-xs text-rose-800">
                <KeyRound class="w-4 h-4 shrink-0 mt-0.5" />
                <p>
                    Password tersimpan terenkripsi dan hanya terlihat di halaman ini. Jaga kerahasiaan akun super admin.
                    Akun yang menampilkan <em>"belum tersimpan"</em> berarti passwordnya dibuat sebelum panel ini — lakukan
                    <strong>Reset Password</strong> sekali untuk menyimpannya.
                    Untuk mengganti password <strong>super admin sendiri</strong>, ubah <code>SUPERADMIN_PASSWORD</code> di file
                    <code>.env</code> server lalu jalankan ulang <code>SuperAdminSeeder</code> — file <code>.env</code> adalah sumber kebenarannya.
                </p>
            </div>

            <div v-if="accounts.length === 0" class="text-center py-16 bg-white rounded-lg border border-slate-200 text-sm text-slate-500">
                Belum ada akun admin/ketua.
            </div>

            <div v-else class="space-y-3">
                <div
                    v-for="acc in accounts"
                    :key="acc.id"
                    class="bg-white rounded-xl border border-slate-200 shadow-sm p-4"
                >
                    <div class="flex items-start justify-between gap-3 flex-wrap">
                        <!-- Identitas -->
                        <div class="flex items-start gap-3 min-w-0">
                            <div class="w-11 h-11 rounded-lg bg-slate-100 flex items-center justify-center shrink-0">
                                <UserCircle class="w-6 h-6 text-slate-500" />
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-slate-900 truncate">{{ acc.name }}</span>
                                    <Badge variant="outline" :class="roleBadge(acc.role).cls + ' text-[10px] px-1.5 py-0'">
                                        {{ roleBadge(acc.role).label }}
                                    </Badge>
                                </div>
                                <div class="text-xs text-slate-500 flex items-center gap-1 mt-0.5">
                                    <Mail class="w-3 h-3 shrink-0" /> {{ acc.email }}
                                </div>
                                <div v-if="acc.phone_number" class="text-xs text-slate-500 flex items-center gap-1 mt-0.5">
                                    <Phone class="w-3 h-3 shrink-0" /> {{ acc.phone_number }}
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-2 shrink-0">
                            <Button size="sm" variant="outline" class="h-9" @click="openProf(acc)">
                                <Pencil class="w-3.5 h-3.5 mr-1.5" /> Edit
                            </Button>
                            <Button size="sm" class="h-9 bg-rose-600 hover:bg-rose-700" @click="openPw(acc)">
                                <KeyRound class="w-3.5 h-3.5 mr-1.5" /> Reset Password
                            </Button>
                        </div>
                    </div>

                    <!-- Password row -->
                    <div class="mt-3 pt-3 border-t border-slate-100 flex items-center gap-2">
                        <span class="text-[11px] uppercase tracking-wider font-bold text-slate-400 w-20 shrink-0">Password</span>
                        <template v-if="acc.password_plain">
                            <code class="font-mono text-sm text-slate-800 bg-slate-50 border border-slate-200 rounded px-2 py-1 min-w-0">
                                {{ revealed[acc.id] ? acc.password_plain : '•'.repeat(acc.password_plain.length) }}
                            </code>
                            <button type="button" class="p-1.5 text-slate-400 hover:text-slate-700 rounded hover:bg-slate-100" @click="toggleReveal(acc.id)" :title="revealed[acc.id] ? 'Sembunyikan' : 'Lihat'">
                                <component :is="revealed[acc.id] ? EyeOff : Eye" class="w-4 h-4" />
                            </button>
                            <button type="button" class="p-1.5 text-slate-400 hover:text-slate-700 rounded hover:bg-slate-100" @click="copyPassword(acc)" title="Salin">
                                <component :is="copiedId === acc.id ? Check : Copy" class="w-4 h-4" :class="copiedId === acc.id ? 'text-emerald-600' : ''" />
                            </button>
                        </template>
                        <span v-else class="text-xs text-amber-600 italic">belum tersimpan — reset dulu untuk menyimpan</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Reset Password Modal ─────────────────────────── -->
        <Dialog v-model:open="isPwOpen">
            <DialogContent class="sm:max-w-[440px]">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2 text-rose-700">
                        <KeyRound class="w-5 h-5" /> Reset Password
                    </DialogTitle>
                    <DialogDescription class="pt-1">
                        Akun <strong>{{ pwAccount?.name }}</strong> ({{ pwAccount?.email }}). Password lama akan diganti.
                    </DialogDescription>
                </DialogHeader>
                <div class="space-y-3 py-2">
                    <div>
                        <Label for="new_pw">Password Baru <span class="text-red-500">*</span></Label>
                        <div class="flex items-center gap-2 mt-1">
                            <Input id="new_pw" :type="pwShow ? 'text' : 'password'" v-model="pwForm.password" placeholder="min. 6 karakter" class="flex-1" />
                            <button type="button" class="p-2 text-slate-400 hover:text-slate-700 rounded hover:bg-slate-100" @click="pwShow = !pwShow">
                                <component :is="pwShow ? EyeOff : Eye" class="w-4 h-4" />
                            </button>
                        </div>
                        <p v-if="pwForm.errors.password" class="text-xs text-red-600 mt-0.5">{{ pwForm.errors.password }}</p>
                        <button type="button" class="mt-2 inline-flex items-center gap-1 text-xs text-rose-600 hover:text-rose-700 font-medium" @click="genPassword">
                            <RefreshCw class="w-3 h-3" /> Generate otomatis
                        </button>
                    </div>
                </div>
                <DialogFooter class="gap-2 sm:gap-0">
                    <Button variant="outline" @click="isPwOpen = false">Batal</Button>
                    <Button class="bg-rose-600 hover:bg-rose-700" @click="submitPw" :disabled="pwForm.processing">
                        {{ pwForm.processing ? 'Menyimpan...' : 'Simpan Password' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- ── Edit Profil Modal ────────────────────────────── -->
        <Dialog v-model:open="isProfOpen">
            <DialogContent class="sm:max-w-[440px]">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <Pencil class="w-5 h-5 text-slate-700" /> Edit Akun
                    </DialogTitle>
                    <DialogDescription class="pt-1">Ubah identitas akun tanpa menyentuh password.</DialogDescription>
                </DialogHeader>
                <div class="space-y-3 py-2">
                    <div>
                        <Label for="prof_name">Nama</Label>
                        <Input id="prof_name" v-model="profForm.name" class="mt-1" />
                        <p v-if="profForm.errors.name" class="text-xs text-red-600 mt-0.5">{{ profForm.errors.name }}</p>
                    </div>
                    <div>
                        <Label for="prof_email">Email Login</Label>
                        <Input id="prof_email" type="email" v-model="profForm.email" class="mt-1" />
                        <p v-if="profForm.errors.email" class="text-xs text-red-600 mt-0.5">{{ profForm.errors.email }}</p>
                    </div>
                    <div>
                        <Label for="prof_phone">No HP/WhatsApp</Label>
                        <Input id="prof_phone" v-model="profForm.phone_number" class="mt-1" placeholder="08xxxxxxxxxx" />
                    </div>
                </div>
                <DialogFooter class="gap-2 sm:gap-0">
                    <Button variant="outline" @click="isProfOpen = false">Batal</Button>
                    <Button @click="submitProf" :disabled="profForm.processing">
                        {{ profForm.processing ? 'Menyimpan...' : 'Simpan' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AuthenticatedLayout>
</template>
