<template>
  <div class="message-options">
    <transition-group name="slide-up" tag="div" class="options-container">
      <a
        v-if="show"
        key="whatsapp"
        :href="`https://wa.me/${generalSettings?.whatsapp_number || '1234567890'}`"
        target="_blank"
        class="option-link whatsapp-link"
        aria-label="Contact via WhatsApp"
      >
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
            <path d="M16.75 13.96c.25.13.41.2.46.3.06.11.04.61-.21 1.18-.2.56-1.24 1.1-1.7 1.12-.46.02-.47.36-2.96-.73-2.49-1.09-3.99-3.75-4.11-3.92-.12-.17-.96-1.38-.92-2.61.05-1.22.69-1.8.95-2.04.24-.26.51-.29.68-.26h.47c.15 0 .36-.06.55.45l.69 1.87c.06.13.1.28.01.44l-.27.41-.39.42c-.12.12-.26.25-.12.5.12.26.62 1.09 1.32 1.78.91.88 1.71 1.17 1.95 1.3.24.14.39.12.54-.04l.81-.94c.19-.25.35-.19.58-.11l1.67.88M12 2a10 10 0 0 1 10 10 10 10 0 0 1-10 10c-1.97 0-3.8-.57-5.35-1.55L2 22l1.55-4.65A9.969 9.969 0 0 1 2 12 10 10 0 0 1 12 2m0 2a8 8 0 0 0-8 8c0 1.72.54 3.31 1.46 4.61L4.5 19.5l2.89-.96A7.95 7.95 0 0 0 12 20a8 8 0 0 0 8-8 8 8 0 0 0-8-8z"></path>
        </svg>
      </a>
      <a
        v-if="show"
        key="messenger"
        :href="`https://m.me/${generalSettings?.facebook_page_id || '#'}`"
        target="_blank"
        class="option-link messenger-link"
        aria-label="Contact via Messenger"
      >
        <svg xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" width="512" height="512" x="0" y="0" viewBox="0 0 24 24" style="enable-background:new 0 0 512 512" xml:space="preserve" class=""><g><g fill="CurrentColor"><path fill-rule="evenodd" d="M17.345 1.32c-.864-.07-1.94-.07-3.311-.07H9.966c-1.371 0-2.447 0-3.311.07-.88.073-1.607.221-2.265.557A5.75 5.75 0 0 0 1.877 4.39c-.336.658-.484 1.385-.556 2.265-.071.864-.071 1.94-.071 3.311v4.262a4.522 4.522 0 0 0 4.522 4.522h.601c.249 0 .419.251.327.482-.687 1.716 1.29 3.268 2.794 2.194l2.611-1.865.05-.035a4.25 4.25 0 0 1 2.42-.776h.756c1.549 0 2.493 0 3.287-.232a5.75 5.75 0 0 0 3.9-3.9c.232-.794.232-1.738.232-3.287V9.966c0-1.371 0-2.447-.07-3.311-.073-.88-.221-1.607-.557-2.265a5.75 5.75 0 0 0-2.513-2.513c-.658-.336-1.385-.484-2.265-.556zM5.071 3.214c.411-.21.919-.333 1.706-.397.796-.065 1.81-.066 3.223-.066h4c1.413 0 2.427 0 3.223.066.787.064 1.295.188 1.707.397a4.25 4.25 0 0 1 1.857 1.858c.21.411.333.919.397 1.706.065.796.066 1.81.066 3.223v1.184c0 1.742-.008 2.452-.172 3.012a4.25 4.25 0 0 1-2.882 2.882c-.56.164-1.27.172-3.012.172h-.619a5.75 5.75 0 0 0-3.275 1.05l-2.667 1.905c-.286.204-.661-.09-.53-.416a1.851 1.851 0 0 0-1.72-2.539h-.601a3.022 3.022 0 0 1-3.022-3.022V10c0-1.413 0-2.427.066-3.223.064-.787.188-1.295.397-1.706a4.25 4.25 0 0 1 1.858-1.858z" clip-rule="evenodd" fill="CurrentColor" opacity="1" data-original="CurrentColor" class=""></path><path d="M9 10a1 1 0 1 1-2 0 1 1 0 0 1 2 0zM13 10a1 1 0 1 1-2 0 1 1 0 0 1 2 0zM17 10a1 1 0 1 1-2 0 1 1 0 0 1 2 0z" fill="CurrentColor" opacity="1" data-original="CurrentColor" class=""></path></g></g></svg>
      </a>
    </transition-group>
    <button @click="toggleShow" class="message-btn" aria-label="Toggle message options">
      <MessageSquare v-if="!show" size="24" />
      <X v-else size="24" />
    </button>
  </div>
</template>

<script setup>
import { MessageSquare, X } from 'lucide-vue-next'

const settingsStore = useSettingsStore()
const generalSettings = computed(() => settingsStore.generalSettings)

const show = ref(false)

const toggleShow = () => {
  show.value = !show.value
}
</script>

<style scoped>
.message-options {
  position: fixed;
  bottom: 60px;
  right: 20px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  z-index: 1000;
}

.options-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
}

.message-btn {
  background: #007bff;
  border: none;
  border-radius: 50%;
  width: 50px;
  height: 50px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
  transition: transform 0.2s, background-color 0.2s;
  color: white;
}

.message-btn:hover {
  transform: scale(1.1);
  background: #0056b3;
}

.option-link svg {
  width: 30px;
  height: 30px;
  fill: white;
  color: white;
}

.icon {
  width: 30px;
  height: 30px;
}

.option-link {
  border-radius: 50%;
  width: 50px;
  height: 50px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
  transition: transform 0.2s;
}

.option-link:hover {
  transform: scale(1.1);
}

.whatsapp-link {
  background: #25d366;
}

.messenger-link {
  background: #007bff;
}

/* Smooth bottom-up transition for options */
.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.3s ease;
}

.slide-up-enter-from,
.slide-up-leave-to {
  opacity: 0;
  transform: translateY(60px);
}

.slide-up-move {
  transition: transform 0.3s ease;
}
</style>