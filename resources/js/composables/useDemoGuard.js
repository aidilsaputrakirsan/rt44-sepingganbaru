import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

const showToast = ref(false);
let toastTimer = null;

export function useDemoGuard() {
    const isDemo = computed(() => usePage().props.auth.is_demo);

    const demoGuard = () => {
        if (!isDemo.value) return true;

        // Show toast
        showToast.value = true;
        if (toastTimer) clearTimeout(toastTimer);
        toastTimer = setTimeout(() => {
            showToast.value = false;
        }, 2500);

        return false;
    };

    return { isDemo, demoGuard, showToast };
}
