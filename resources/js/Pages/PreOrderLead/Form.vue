<template>
  <FrontendLayout>
    <Head title="Pre-Order Request" />
    <div class="min-h-screen py-12 px-4 sm:px-6 lg:px-8 bg-[#fdfdfd]">
      <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-2xl shadow-xl shadow-gray-100 overflow-hidden border border-gray-100">
          <div class="p-8 sm:p-12">
            <form @submit.prevent="submitForm" class="space-y-8">
              <!-- Product Images -->
              <div class="space-y-3">
                <label class="block text-xs sm:text-sm font-semibold text-gray-800">
                  Product Images <span class="text-gray-400 font-normal">(Upload up to 5 photos)</span> <span class="text-red-500">*</span>
                </label>
                
                <div
                  @click="triggerFilePicker"
                  @dragover.prevent="isDragActive = true"
                  @dragleave.prevent="isDragActive = false"
                  @drop.prevent="handleDrop"
                  class="relative flex flex-col items-center justify-center p-10 border-2 border-dashed rounded-2xl transition-all duration-300 group cursor-pointer"
                  :class="[
                    isDragActive 
                      ? 'border-primary bg-gray-50 scale-[1.01]' 
                      : 'border-gray-200 bg-gray-50 hover:border-primary hover:bg-gray-100/50'
                  ]"
                >
                  <div class="p-4 rounded-full bg-white shadow-sm border border-gray-100 group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-8 h-8 text-gray-400 group-hover:text-primary transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                  </div>
                  <p class="mt-4 text-sm sm:text-base font-semibold text-gray-700">Click here to upload product photos</p>
                  <p class="text-[10px] sm:text-xs text-gray-400 mt-1 uppercase tracking-wider">JPG, PNG formats supported</p>
                  
                  <input
                    ref="imageInput"
                    type="file"
                    accept="image/*"
                    multiple
                    @change="handleImageUpload"
                    class="hidden"
                  />
                </div>

                <!-- Image Previews -->
                <div v-if="images.length > 0" class="flex flex-wrap gap-4 mt-4">
                  <div v-for="(img, index) in images" :key="index" class="relative group">
                    <img :src="img.preview" class="w-24 h-24 object-cover rounded-xl border border-gray-200 shadow-sm" />
                    <button 
                      @click.stop="removeImage(index)" 
                      class="absolute -top-2 -right-2 p-1 bg-red-500 text-white rounded-full shadow-lg opacity-0 group-hover:opacity-100 transition-opacity"
                    >
                      <X class="w-3 h-3" />
                    </button>
                  </div>
                </div>
                <p v-if="errors.image" class="text-xs text-red-500 font-bold mt-1 uppercase tracking-tight">{{ errors.image }}</p>
              </div>

              <!-- Product Name -->
              <div class="space-y-2">
                <label for="product_interest" class="block text-xs sm:text-sm font-semibold text-gray-800">
                  Product Name / Title <span class="text-red-500">*</span>
                </label>
                <div class="relative group">
                  <input
                    id="product_interest"
                    v-model="form.product_interest"
                    type="text"
                    required
                    placeholder="e.g. iQOO 12 Pro Custom Anime Case"
                    class="block w-full px-5 py-4 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-300 placeholder:text-gray-400 group-hover:border-gray-300"
                    :class="{ 'border-red-500 ring-red-50 focus:border-red-500': errors.product_interest }"
                  />
                </div>
                <p v-if="errors.product_interest" class="text-[10px] sm:text-xs text-red-500 font-semibold mt-1 uppercase tracking-tight">{{ errors.product_interest }}</p>
              </div>

              <!-- Reference Link -->
              <div class="space-y-2">
                <label for="reference_link" class="block text-xs sm:text-sm font-semibold text-gray-800 group">
                  Reference Link <span class="text-gray-400 font-normal">(AliExpress, Amazon, etc.)</span>
                </label>
                <div class="relative flex items-center group">
                  <span class="absolute left-5 text-gray-400 group-focus-within:text-primary transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.828a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                  </span>
                  <input
                    id="reference_link"
                    v-model="form.reference_link"
                    type="url"
                    placeholder="Paste product URL"
                    class="block w-full pl-14 pr-5 py-4 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-300 placeholder:text-gray-400 group-hover:border-gray-300"
                  />
                </div>
                <p v-if="errors.reference_link" class="text-[10px] sm:text-xs text-red-500 font-semibold mt-1 uppercase tracking-tight">{{ errors.reference_link }}</p>
              </div>

              <!-- WhatsApp Number -->
              <div class="space-y-2">
                <label for="phone" class="block text-xs sm:text-sm font-semibold text-gray-800">
                  Your WhatsApp Number <span class="text-gray-400 font-normal">* (We will contact you here)</span>
                </label>
                <div class="relative flex items-center group">
                  <span class="absolute left-5 text-gray-400 group-focus-within:text-[#25D366] transition-colors">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                  </span>
                  <input
                    id="phone"
                    v-model="form.phone"
                    type="tel"
                    required
                    placeholder="e.g. 017XXXXXXXX"
                    class="block w-full pl-14 pr-5 py-4 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 focus:ring-2 focus:ring-[#25D366]/20 focus:border-[#25D366] transition-all duration-300 placeholder:text-gray-400 group-hover:border-gray-300"
                    :class="{ 'border-red-500 ring-red-50 focus:border-red-500': errors.phone }"
                  />
                </div>
                <p v-if="errors.phone" class="text-[10px] sm:text-xs text-red-500 font-semibold mt-1 uppercase tracking-tight">{{ errors.phone }}</p>
              </div>

              <!-- Message -->
              <div class="space-y-2">
                <label for="message" class="block text-xs sm:text-sm font-semibold text-gray-800">
                  Additional Note / Model Details
                </label>
                <textarea
                  id="message"
                  v-model="form.message"
                  rows="4"
                  placeholder="আপনার ফোনের মডেল, কালার বা অন্য কোনো চাহিদা থাকলে লিখুন..."
                  class="block w-full px-5 py-4 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-300 placeholder:text-gray-400 group-hover:border-gray-300"
                  :class="{ 'border-red-500 ring-red-50 focus:border-red-500': errors.message }"
                ></textarea>
                <p v-if="errors.message" class="text-[10px] sm:text-xs text-red-500 font-semibold mt-1 uppercase tracking-tight">{{ errors.message }}</p>
              </div>

              <!-- Submit Button -->
              <div class="pt-4">
                <button
                  type="submit"
                  :disabled="loading"
                  class="w-full flex items-center justify-center gap-3 py-4 sm:py-5 px-8 rounded-xl text-sm font-semibold text-white bg-[#001D3D] hover:bg-black transition-all duration-300 shadow-xl shadow-[#001D3D]/10 disabled:opacity-70 disabled:cursor-not-allowed group"
                >
                  <svg 
                    v-if="!loading"
                    class="w-5 h-5 transform group-hover:translate-x-1 group-hover:-translate-y-1 transition-transform" 
                    fill="none" stroke="currentColor" viewBox="0 0 24 24"
                  >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                  </svg>
                  <span v-if="loading" class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                  <span class="uppercase tracking-widest">{{ loading ? 'Processing Request...' : 'Submit Product Request' }}</span>
                </button>
              </div>

              <!-- Note Section -->
              <div class="bg-[#FFF8F0] border-l-4 border-orange-400 p-5 rounded-r-xl mt-8">
                <div class="flex gap-4">
                  <div class="flex-shrink-0">
                    <div class="w-8 h-8 rounded-full border-2 border-orange-400 flex items-center justify-center text-orange-400 font-bold">!</div>
                  </div>
                  <p class="text-xs sm:text-sm font-semibold text-orange-800 leading-relaxed">
                    প্রি-অর্ডার প্রোডাক্ট ডেলিভারি হতে ২০-২৫ দিন সময় লাগবে। প্রোডাক্টের মোট দামের ৫০% এডভান্স পে করতে হবে। রিকোয়েস্ট করার পর আমাদের প্যানেল থেকে আপনাকে কল করে বিস্তারিত বলা হবে।
                  </p>
                </div>
              </div>

              <!-- Bottom Shield Text -->
              <div class="flex items-center justify-center mt-6 gap-2 text-gray-400 font-medium text-[10px] sm:text-xs py-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                <span>No payment required right now.</span>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </FrontendLayout>
</template>

<script setup>
import FrontendLayout from '@/Layouts/FrontendLayout.vue'
import { ref, reactive } from 'vue'
import { router, Head } from '@inertiajs/vue3'
import { X, ChevronRight } from 'lucide-vue-next'
import { useToast } from '@/Composables/useToast'

const { success, error: toastError } = useToast()

const form = reactive({
  product_interest: '',
  reference_link: '',
  phone: '',
  message: '',
  images: [], // Support multiple internally
})

const images = ref([])
const errors = ref({})
const loading = ref(false)
const imageInput = ref(null)
const isDragActive = ref(false)

const triggerFilePicker = () => {
  if (imageInput.value) imageInput.value.click()
}

const handleImageUpload = (event) => {
  const files = event.target.files
  addFiles(files)
}

const handleDrop = (event) => {
  isDragActive.value = false
  const files = event.dataTransfer.files
  addFiles(files)
}

const addFiles = (files) => {
  if (!files) return
  
  Array.from(files).forEach(file => {
    if (images.value.length >= 5) {
      toastError('Maximum 5 images allowed')
      return
    }
    
    if (file.size > 5 * 1024 * 1024) {
      toastError('File too large (Max 5MB)')
      return
    }

    const reader = new FileReader()
    reader.onload = (e) => {
      images.value.push({
        file: file,
        preview: e.target.result
      })
    }
    reader.readAsDataURL(file)
  })
}

const removeImage = (index) => {
  images.value.splice(index, 1)
}

const submitForm = () => {
  loading.value = true
  errors.value = {}

  const formData = new FormData()
  formData.append('product_interest', form.product_interest)
  formData.append('reference_link', form.reference_link)
  formData.append('phone', form.phone)
  formData.append('message', form.message)
  
  // For now, we'll send only the first image to keep backend compatibility
  // unless we update backend to handle multiple. 
  // Most systems expect 'image' key for a single file.
  if (images.value.length > 0) {
    formData.append('image', images.value[0].file)
  }

  router.post('/pre-order-lead', formData, {
    onSuccess: () => {
      success('Product request submitted! We will contact you soon.')
      // Reset form
      form.product_interest = ''
      form.reference_link = ''
      form.phone = ''
      form.message = ''
      images.value = []
      if (imageInput.value) imageInput.value.value = ''
    },
    onError: (err) => {
      errors.value = err
      toastError('Please fix the errors below')
    },
    onFinish: () => {
      loading.value = false
    }
  })
}
</script>

<style scoped>
</style>