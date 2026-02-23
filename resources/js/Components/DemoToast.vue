<script setup>
import { useDemoGuard } from '@/composables/useDemoGuard';
import { ShieldAlert } from 'lucide-vue-next';

const { showToast } = useDemoGuard();
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition-all duration-300 ease-out"
      enter-from-class="translate-y-full opacity-0"
      enter-to-class="translate-y-0 opacity-100"
      leave-active-class="transition-all duration-200 ease-in"
      leave-from-class="translate-y-0 opacity-100"
      leave-to-class="translate-y-full opacity-0"
    >
      <div
        v-if="showToast"
        class="fixed bottom-6 left-1/2 -translate-x-1/2 z-[100] flex items-center gap-2.5 px-5 py-3 rounded-xl bg-amber-600 text-white text-sm font-semibold shadow-2xl shadow-amber-900/30"
      >
        <ShieldAlert class="w-4 h-4 shrink-0" />
        <span>Mode Demo — fitur ini hanya untuk admin</span>
      </div>
    </Transition>
  </Teleport>
</template>
