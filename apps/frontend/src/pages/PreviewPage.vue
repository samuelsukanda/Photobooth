<script setup lang="ts">
import { ref, onBeforeUnmount, computed } from 'vue'
import { useRouter } from 'vue-router'
import { usePhotoboothStore } from '../stores/photobooth'
import WaveAnimation from '../components/WaveAnimation.vue'

const router = useRouter()
const store = usePhotoboothStore()

// Redirect if no photo
if (!store.mergedImageUrl) {
  router.replace('/')
}

// ==== STATE ====
type PreviewState = 'preview' | 'voice' | 'uploading'
const state = ref<PreviewState>('preview')

// ==== VOICE ====
const isRecording = ref(false)
const recordingTime = ref(0)
const maxTime = 30
let timerInterval: any = null
const mediaRecorder = ref<MediaRecorder | null>(null)
const audioChunks = ref<Blob[]>([])
let audioStream: MediaStream | null = null

const hasAudio = computed(() => !!store.audioObjectUrl)

onBeforeUnmount(() => {
  stopRecording()
})

// ==== PREVIEW METHODS ====
const retake = () => {
  store.reset()
  router.replace('/')
}

const startVoice = () => {
  state.value = 'voice'
}

// ==== VOICE METHODS ====
const startRecording = async () => {
  try {
    audioStream = await navigator.mediaDevices.getUserMedia({ audio: true })

    let mimeType = 'audio/webm'
    if (MediaRecorder.isTypeSupported('audio/webm')) {
      mimeType = 'audio/webm'
    } else if (MediaRecorder.isTypeSupported('audio/mp4')) {
      mimeType = 'audio/mp4'
    } else if (MediaRecorder.isTypeSupported('audio/ogg')) {
      mimeType = 'audio/ogg'
    } else {
      mimeType = '' // Fallback to browser default
    }

    mediaRecorder.value = new MediaRecorder(audioStream, mimeType ? { mimeType } : undefined)
    audioChunks.value = []

    mediaRecorder.value.ondataavailable = (e) => {
      if (e.data.size > 0) audioChunks.value.push(e.data)
    }

    mediaRecorder.value.onstop = () => {
      const type = mediaRecorder.value?.mimeType || mimeType
      const blob = new Blob(audioChunks.value, { type })
      store.setAudio(blob)
    }

    mediaRecorder.value.start()
    isRecording.value = true
    recordingTime.value = 0
    timerInterval = setInterval(() => {
      recordingTime.value++
      if (recordingTime.value >= maxTime) stopRecording()
    }, 1000)
  } catch (e) {
    alert('Gagal mengakses mikrofon.')
  }
}

const stopRecording = () => {
  if (mediaRecorder.value && isRecording.value) {
    mediaRecorder.value.stop()
    isRecording.value = false
    clearInterval(timerInterval)
    if (audioStream) audioStream.getTracks().forEach(t => t.stop())
  }
}

const deleteRecording = () => {
  store.clearAudio()
  recordingTime.value = 0
}

// ==== UPLOAD ====
const uploadPhoto = async (includeAudio = false) => {
  state.value = 'uploading'
  const success = await store.uploadToServer(includeAudio)
  if (success) {
    router.replace('/')
  } else {
    state.value = 'preview'
  }
}
</script>

<template>
  <div class="bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden flex flex-col">

    <!-- STATE: PREVIEW -->
    <div v-if="state === 'preview'" class="flex flex-col">
      <div class="p-6 flex items-center justify-center">
        <img v-if="store.mergedImageUrl" :src="store.mergedImageUrl" class="polaroid-preview w-full max-w-[280px] h-auto rounded-sm transform hover:scale-[1.02] transition-transform duration-300" />
      </div>
      <div class="p-4 flex flex-col gap-2 bg-white">
        <div class="grid grid-cols-2 gap-2">
          <button @click="startVoice" class="py-3 bg-primary text-white font-bold rounded-xl shadow hover:bg-pink-600 transition-colors text-sm">
            🎙️ Rekam Suara
          </button>
          <button @click="uploadPhoto(false)" class="py-3 bg-gray-800 text-white font-bold rounded-xl shadow hover:bg-gray-900 transition-colors text-sm">
            💾 Simpan Foto
          </button>
        </div>
        <button @click="retake" class="w-full py-2 text-gray-400 text-sm hover:text-gray-600 transition-colors">
          Ambil Ulang
        </button>
      </div>
    </div>

    <!-- STATE: VOICE -->
    <div v-else-if="state === 'voice'" class="flex flex-col">
      <!-- Photo always visible at top -->
      <div class="p-6 pb-2 flex items-center justify-center">
        <img v-if="store.mergedImageUrl" :src="store.mergedImageUrl" class="polaroid-preview w-full max-w-[220px] h-auto rounded-sm" />
      </div>

      <div class="p-6 pt-2 flex flex-col items-center gap-4">
        <!-- Wave animation & recording info -->
        <div class="flex flex-col items-center gap-3 w-full">
          <WaveAnimation :active="isRecording" />

          <p v-if="!hasAudio && !isRecording" class="text-gray-500 text-sm text-center">
            Tekan tombol untuk merekam pesan suara Anda (maks. 30 detik)
          </p>
          <p v-if="isRecording || (!hasAudio && !isRecording)" class="text-2xl font-bold text-gray-800">
            {{ recordingTime }}s / {{ maxTime }}s
          </p>
        </div>

        <!-- Playback -->
        <div v-if="hasAudio && !isRecording" class="w-full flex flex-col items-center gap-2">
          <audio :src="store.audioObjectUrl!" controls class="w-full max-w-[280px] rounded-xl"></audio>
          <button @click="deleteRecording" class="text-red-400 text-xs underline">Hapus & Rekam Ulang</button>
        </div>

        <!-- Record / Stop button -->
        <button v-if="!hasAudio && !isRecording" @click="startRecording" class="w-14 h-14 bg-red-500 rounded-full shadow-lg flex items-center justify-center hover:scale-105 transition-transform">
          <div class="w-4 h-4 bg-white rounded-full"></div>
        </button>
        <button v-else-if="isRecording" @click="stopRecording" class="w-14 h-14 bg-red-500 rounded-full shadow-lg flex items-center justify-center hover:scale-105 transition-transform animate-pulse">
          <div class="w-5 h-5 bg-white rounded-sm"></div>
        </button>

        <!-- Actions -->
        <div class="flex flex-col gap-2 w-full mt-2">
          <button
            @click="uploadPhoto(true)"
            :disabled="!hasAudio"
            class="w-full py-3 bg-primary text-white font-bold rounded-xl shadow disabled:opacity-40 hover:bg-pink-600 transition-colors text-sm"
          >
            📤 Upload Foto + Suara
          </button>
          <button @click="state = 'preview'" class="text-gray-400 text-xs text-center">Kembali ke Preview</button>
        </div>
      </div>
    </div>

    <!-- STATE: UPLOADING -->
    <div v-else-if="state === 'uploading'" class="flex flex-col items-center justify-center py-14 gap-4">
      <div class="w-12 h-12 border-4 border-primary border-t-transparent rounded-full animate-spin"></div>
      <p class="text-gray-500 text-sm animate-pulse">Menyimpan kenangan...</p>
    </div>

  </div>
</template>
