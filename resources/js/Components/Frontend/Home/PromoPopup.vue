<script setup>
import { ref, onMounted } from 'vue';

const props = defineProps({
    enabled: Boolean,
    image: String,
    link: String
});

const isVisible = ref(false);

onMounted(() => {
    if (props.enabled && props.image) {
        // Check if the popup was already shown in this session
        const hasSeenPopup = sessionStorage.getItem('hasSeenPopup');
        
        if (!hasSeenPopup) {
            // Show popup with a small delay
            setTimeout(() => {
                isVisible.value = true;
            }, 1500);
        }
    }
});

const closePopup = () => {
    isVisible.value = false;
    sessionStorage.setItem('hasSeenPopup', 'true');
};

const handlePopupClick = () => {
    if (props.link) {
        window.location.href = props.link;
    }
};
</script>

<template>
    <div v-if="isVisible" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 transition-opacity duration-300">
        <div class="relative max-w-lg w-full bg-white rounded-lg shadow-2xl overflow-hidden animate-in zoom-in duration-300">
            <!-- Close Button -->
            <button 
                @click="closePopup" 
                class="absolute top-2 right-2 z-10 p-1 bg-white/80 hover:bg-white rounded-full transition-colors shadow-sm"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-800" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <!-- Popup Content -->
            <div 
                :class="{ 'cursor-pointer': link }" 
                @click="handlePopupClick"
            >
                <img 
                    :src="image" 
                    class="w-full h-auto object-contain max-h-[80vh]" 
                    alt="Promotional Popup"
                    @error="closePopup"
                />
            </div>
        </div>
    </div>
</template>

<style scoped>
.animate-in {
    animation-fill-mode: forwards;
}
@keyframes zoom-in {
    from {
        opacity: 0;
        transform: scale(0.9);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}
.zoom-in {
    animation-name: zoom-in;
}
</style>
