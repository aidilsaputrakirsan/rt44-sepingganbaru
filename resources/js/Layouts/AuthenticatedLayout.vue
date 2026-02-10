<script setup>
import { ref } from 'vue';
import Sidebar from '@/Components/Sidebar.vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link } from '@inertiajs/vue3';
import { Sheet, SheetContent, SheetTrigger } from '@/Components/ui/sheet';
import { Menu } from 'lucide-vue-next';
import { Button } from '@/Components/ui/button';

const showingNavigationDropdown = ref(false);
</script>

<template>
    <div class="h-screen bg-gray-100 dark:bg-gray-900 flex overflow-hidden">
        <!-- Sidebar (Desktop) -->
        <Sidebar class="hidden md:flex flex-shrink-0" />

        <!-- Main Content -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 dark:bg-gray-900">
            <!-- Mobile Header -->
            <div class="md:hidden bg-white dark:bg-gray-800 border-b p-4 flex items-center justify-between">
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
                <h1 class="font-bold text-lg">RT-44 Sepinggan Baru</h1>
                <div class="w-6"></div> <!-- Spacer for centering if needed -->
            </div>
            
            <!-- Page Heading -->
            <header class="bg-white dark:bg-gray-800 shadow" v-if="$slots.header">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
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
