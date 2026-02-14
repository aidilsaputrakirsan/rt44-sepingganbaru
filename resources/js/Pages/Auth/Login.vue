<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Home, Lock, LogIn, Loader2, Eye, EyeOff } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const form = useForm({
    no_rumah: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Login" />

    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-amber-50/80 via-white to-orange-50/50 p-4 relative">
        <!-- Subtle decorative shapes -->
        <div class="fixed inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-32 -right-32 w-72 h-72 bg-amber-100/40 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-32 -left-32 w-72 h-72 bg-orange-100/30 rounded-full blur-3xl"></div>
        </div>

        <div class="w-full max-w-sm relative z-10">
            <!-- Logo + Branding -->
            <div class="text-center mb-6">
                <img src="/logort.png" alt="Logo RT-44" class="w-20 h-20 mx-auto mb-3 drop-shadow-lg" />
                <h1 class="text-xl font-bold text-slate-800">RT-44 Sepinggan Baru</h1>
                <p class="text-xs text-slate-500 mt-0.5">Sistem Manajemen Iuran & Keuangan</p>
            </div>

            <!-- Login Card -->
            <Card class="shadow-xl shadow-amber-900/5 border-amber-100/50">
                <CardContent class="pt-6">
                    <!-- Status Message -->
                    <div v-if="status" class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700">
                        {{ status }}
                    </div>

                    <!-- Error Alert -->
                    <div v-if="form.errors.no_rumah || form.errors.password" class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-600">
                        {{ form.errors.no_rumah || form.errors.password }}
                    </div>

                    <form @submit.prevent="submit" class="space-y-4">
                        <!-- No Rumah -->
                        <div class="space-y-1.5">
                            <Label for="no_rumah" class="text-sm font-medium text-slate-700">No. Rumah atau Email</Label>
                            <div class="relative">
                                <Home class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                                <Input
                                    id="no_rumah"
                                    type="text"
                                    v-model="form.no_rumah"
                                    placeholder="Contoh: G1/1 atau g1-1@rt44.com"
                                    class="pl-10 h-11"
                                    required
                                    autofocus
                                    autocomplete="off"
                                />
                            </div>
                        </div>

                        <!-- Password -->
                        <div class="space-y-1.5">
                            <Label for="password" class="text-sm font-medium text-slate-700">Password</Label>
                            <div class="relative">
                                <Lock class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                                <Input
                                    id="password"
                                    :type="showPassword ? 'text' : 'password'"
                                    v-model="form.password"
                                    placeholder="Masukkan password"
                                    class="pl-10 pr-10 h-11"
                                    required
                                    autocomplete="current-password"
                                />
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors"
                                    tabindex="-1"
                                >
                                    <EyeOff v-if="showPassword" class="w-4 h-4" />
                                    <Eye v-else class="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                        <!-- Remember Me -->
                        <div class="flex items-center gap-2">
                            <input
                                id="remember"
                                type="checkbox"
                                v-model="form.remember"
                                class="h-4 w-4 rounded border-slate-300 text-amber-500 focus:ring-amber-500"
                            />
                            <Label for="remember" class="text-sm text-slate-500 cursor-pointer">Ingat saya</Label>
                        </div>

                        <!-- Submit Button -->
                        <Button
                            type="submit"
                            class="w-full h-11 bg-amber-500 hover:bg-amber-600 text-white font-semibold shadow-md shadow-amber-500/20 transition-all"
                            :disabled="form.processing"
                        >
                            <Loader2 v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" />
                            <LogIn v-else class="w-4 h-4 mr-2" />
                            {{ form.processing ? 'Memproses...' : 'Masuk' }}
                        </Button>
                    </form>
                </CardContent>
            </Card>

            <!-- Footer -->
            <p class="text-center text-[11px] text-slate-400 mt-5">
                &copy; {{ new Date().getFullYear() }} RT-44 Sepinggan Baru, Balikpapan
            </p>
        </div>
    </div>
</template>
