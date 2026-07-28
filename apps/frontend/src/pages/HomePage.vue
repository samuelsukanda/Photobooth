<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { usePhotoboothStore } from '../stores/photobooth'
import AudioPlayer from '../components/AudioPlayer.vue'

const router = useRouter()
const store = usePhotoboothStore()

const fileInput = ref<HTMLInputElement | null>(null)

// ==== GALLERY ====
const photos = ref<any[]>([])
const isGalleryLoading = ref(true)
const selectedPhoto = ref<any | null>(null)

onMounted(() => {
  fetchGallery()
})

const openNativeCamera = () => {
  fileInput.value?.click()
}

const onFileSelected = async (event: Event) => {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file) return

  try {
    await store.processPhoto(file)
    if (fileInput.value) fileInput.value.value = ''
    router.push('/preview')
  } catch (error) {
    console.error('Error rendering polaroid preview:', error)
    alert('Gagal memuat template frame atau logo.')
    if (fileInput.value) fileInput.value.value = ''
  }
}

// ==== GALLERY ====
const fetchGallery = async () => {
  isGalleryLoading.value = true
  try {
    const res = await fetch('/api/photos')
    if (res.ok) {
      photos.value = await res.json()
    }
  } catch (e) {
    console.error(e)
  } finally {
    isGalleryLoading.value = false
  }
}

const handleDownload = (photo: any) => {
  const url = photo.image_url
  const link = document.createElement('a')
  link.href = url
  link.download = `photobooth-${photo.id}.png`
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}

const handleShare = async (photo: any) => {
  const url = photo.image_url
  if (navigator.share) {
    try { await navigator.share({ title: 'Photobooth Samuel & Angela', text: 'Lihat foto photobooth saya!', url }) } catch (_) {}
  } else {
    alert('Browser tidak mendukung Web Share.')
  }
}

// ==== LIGHTBOX ====
const openLightbox = (photo: any) => {
  selectedPhoto.value = photo
}
const closeLightbox = () => {
  selectedPhoto.value = null
}
</script>

<template>
  <div class="flex flex-col w-full gap-6">
    <!-- Hidden native camera input -->
    <input
      ref="fileInput"
      type="file"
      accept="image/*"
      capture="environment"
      class="hidden"
      @change="onFileSelected"
    />

    <!-- STATE: LANDING -->
    <div class="flex flex-col items-center justify-center py-6 gap-4">
      <button
        @click="openNativeCamera"
        class="flex items-center gap-2 px-8 py-4 bg-primary text-white rounded-full font-bold shadow-xl hover:shadow-pink-300 hover:-translate-y-0.5 transition-all text-base border-none outline-none"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
        Ambil Foto
      </button>

      <!-- Processing spinner -->
      <div v-if="store.isProcessing" class="flex flex-col items-center justify-center py-10 gap-3">
        <div class="w-10 h-10 border-4 border-primary border-t-transparent rounded-full animate-spin"></div>
        <p class="text-gray-500 text-sm animate-pulse">Menambahkan frame...</p>
      </div>
    </div>

    <!-- GALLERY SECTION -->
    <div class="bg-white/90 backdrop-blur-sm rounded-3xl shadow-xl overflow-hidden">
      <div class="p-5 border-b border-gray-100 flex justify-between items-center">
        <h2 class="text-lg font-bold text-gray-800">Gallery Tamu</h2>
        <div class="flex items-center gap-2">
          <span class="text-xs font-semibold px-2 py-1 bg-gray-100 text-gray-500 rounded-full">{{ photos.length }} foto</span>
          <button @click="fetchGallery" class="p-1.5 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-full transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
          </button>
        </div>
      </div>

      <div class="p-4">
        <div v-if="isGalleryLoading" class="grid grid-cols-2 gap-5">
          <div v-for="i in 6" :key="i" class="animate-pulse flex flex-col gap-2">
            <div class="bg-gray-200 rounded-xl aspect-[3285/3313] w-full"></div>
            <div class="h-8 bg-gray-200 rounded-lg w-[85%] mx-auto mt-1"></div>
          </div>
        </div>
        <div v-else-if="photos.length === 0" class="py-10 text-center text-gray-400 text-sm">Belum ada foto. Jadilah yang pertama! 📸</div>
        <div v-else class="grid grid-cols-2 gap-5">
          <div v-for="photo in photos" :key="photo.id" class="flex flex-col gap-2">
            <div class="relative group cursor-pointer" @click="openLightbox(photo)">
              <img :src="photo.thumbnail_url || photo.image_url" class="gallery-frame w-full h-auto block" loading="lazy" />
            </div>

            <!-- Audio player if audio exists -->
            <div v-if="photo.audio_url" class="w-[85%] mx-auto">
              <AudioPlayer :src="photo.audio_url" compact />
            </div>

            <div class="flex items-center gap-2 mt-1 w-[85%] mx-auto">
              <button @click.stop="handleShare(photo)" class="flex-1 flex items-center justify-center gap-1.5 py-1.5 bg-gray-50 hover:bg-pink-50 text-gray-500 hover:text-pink-600 rounded-lg transition-colors text-[11px] font-semibold border border-gray-100 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" /></svg>
                Share
              </button>
              <button @click.stop="handleDownload(photo)" class="flex-1 flex items-center justify-center gap-1.5 py-1.5 bg-gray-50 hover:bg-pink-50 text-gray-500 hover:text-pink-600 rounded-lg transition-colors text-[11px] font-semibold border border-gray-100 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                Download
              </button>
            </div>
          </div>
        </div>
        

      </div>
    </div>

    <!-- LIGHTBOX MODAL -->
    <Teleport to="body">
      <Transition name="lightbox">
        <div v-if="selectedPhoto" class="lightbox-overlay" @click.self="closeLightbox">
          <!-- Close button -->
          <button @click="closeLightbox" class="lightbox-close" aria-label="Close">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>

          <!-- Photo -->
          <div class="lightbox-content">
            <img :src="selectedPhoto.image_url" class="lightbox-media polaroid-preview" />
          </div>

          <!-- Audio player in lightbox -->
          <div v-if="selectedPhoto.audio_url" class="lightbox-audio">
            <AudioPlayer :src="selectedPhoto.audio_url" />
          </div>

          <!-- Action buttons -->
          <div class="lightbox-actions">
            <button @click="handleShare(selectedPhoto)" class="lightbox-btn">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" /></svg>
              Bagikan
            </button>
            <button @click="handleDownload(selectedPhoto)" class="lightbox-btn">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
              Download
            </button>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>
