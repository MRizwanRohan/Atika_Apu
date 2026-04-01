import { ref } from 'vue';

const isCartSidebarOpen = ref(false);

export function useCartSidebar() {
    const openCart = () => {
        isCartSidebarOpen.value = true;
    };

    const closeCart = () => {
        isCartSidebarOpen.value = false;
    };

    const toggleCart = () => {
        isCartSidebarOpen.value = !isCartSidebarOpen.value;
    };

    return {
        isCartSidebarOpen,
        openCart,
        closeCart,
        toggleCart
    };
}
