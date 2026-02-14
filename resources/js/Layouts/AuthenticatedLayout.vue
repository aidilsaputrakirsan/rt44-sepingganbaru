<script setup>
import { ref } from 'vue';
import Sidebar from '@/Components/Sidebar.vue';
import { Link } from '@inertiajs/vue3';
import { Sheet, SheetContent, SheetTrigger } from '@/Components/ui/sheet';
import { Menu } from 'lucide-vue-next';
import { Button } from '@/Components/ui/button';
</script>

<template>
    <div class="h-screen bg-[hsl(var(--background))] flex overflow-hidden">
        <!-- Sidebar (Desktop) -->
        <Sidebar class="hidden md:flex flex-shrink-0" />

        <!-- Main Content -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-50/50">
            <!-- Mobile Header -->
            <div class="md:hidden bg-white border-b border-slate-200 p-4 flex items-center justify-between">
                <Sheet>
                    <SheetTrigger as-child>
                        <Button variant="ghost" size="icon">
                            <Menu class="h-6 w-6" />
                        </Button>
                    </SheetTrigger>
                    <SheetContent side="left" class="p-0 w-64">
                        <Sidebar :mobile="true" />
                    </SheetContent>
                </Sheet>
                <div class="flex items-center gap-2">
                    <img src="/logort.png" alt="Logo" class="w-7 h-7 rounded" />
                    <h1 class="font-bold text-sm text-slate-800">RT-44 Sepinggan Baru</h1>
                </div>
                <div class="w-6"></div>
            </div>

            <!-- Page Heading -->
            <header class="bg-white shadow-sm border-b border-slate-100" v-if="$slots.header">
                <div class="max-w-7xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <div class="py-6 px-4 sm:px-6 lg:px-8">
                <slot />
            </div>
        </main>
    </div>
</template>
