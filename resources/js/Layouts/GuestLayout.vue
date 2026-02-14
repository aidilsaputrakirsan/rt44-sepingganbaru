<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="flex min-h-screen flex-col items-center bg-gradient-to-br from-amber-50/80 via-white to-orange-50/50 pt-6 sm:justify-center sm:pt-0">
        <div>
            <Link href="/">
                <img src="/logort.png" alt="Logo RT-44" class="w-16 h-16 drop-shadow-lg" />
            </Link>
        </div>

        <div class="mt-6 w-full overflow-hidden bg-white px-6 py-4 shadow-xl shadow-amber-900/5 border border-amber-100/50 sm:max-w-md sm:rounded-lg">
            <slot />
        </div>
    </div>
</template>
