import { ref } from 'vue'
import { defineStore } from 'pinia'
import frameUrl from '../assets/Frame.png'

export const usePhotoboothStore = defineStore('photobooth', () => {
  // ==== Photo state ====
  const mergedImageUrl = ref<string | null>(null)
  const isProcessing = ref(false)

  // ==== Audio state ====
  const audioBlob = ref<Blob | null>(null)
  const audioDataUrl = ref<string | null>(null)
  const audioObjectUrl = ref<string | null>(null)

  // ==== Frame layout constants ====
  const PHOTO_W = 1200
  const PHOTO_H = 1210
  const ORIG_FRAME_W = 15625
  const ORIG_FRAME_H = 15625
  const ORIG_CUTOUT_X = 2431
  const ORIG_CUTOUT_Y = 1117
  const ORIG_CUTOUT_W = 10263
  const ORIG_CUTOUT_H = 10341
  const SCALE_X = PHOTO_W / ORIG_CUTOUT_W
  const SCALE_Y = PHOTO_H / ORIG_CUTOUT_H
  const CANVAS_W = Math.round(ORIG_FRAME_W * SCALE_X)
  const CANVAS_H = Math.round(ORIG_FRAME_H * SCALE_Y)
  const CUTOUT_X = Math.round(ORIG_CUTOUT_X * SCALE_X)
  const CUTOUT_Y = Math.round(ORIG_CUTOUT_Y * SCALE_Y)

  // Helper to load images
  const loadImage = (src: string): Promise<HTMLImageElement> => {
    return new Promise((resolve, reject) => {
      const img = new Image()
      img.crossOrigin = 'anonymous'
      img.onload = () => resolve(img)
      img.onerror = (e) => reject(e)
      img.src = src
    })
  }

  /**
   * Composes the user photo inside the Polaroid frame.
   */
  const composePhotoWithFrame = async (photoSrc: string): Promise<string> => {
    const [frameImg, photoImg] = await Promise.all([
      loadImage(frameUrl),
      loadImage(photoSrc)
    ])

    const offscreen = document.createElement('canvas')
    offscreen.width = CANVAS_W
    offscreen.height = CANVAS_H
    const ctx = offscreen.getContext('2d')!

    ctx.fillStyle = '#ffffff'
    ctx.fillRect(CUTOUT_X, CUTOUT_Y, PHOTO_W, PHOTO_H)

    ctx.save()
    ctx.beginPath()
    ctx.rect(CUTOUT_X, CUTOUT_Y, PHOTO_W, PHOTO_H)
    ctx.clip()

    const imgRatio = photoImg.width / photoImg.height
    const cutoutRatio = PHOTO_W / PHOTO_H
    let drawW: number, drawH: number, drawX: number, drawY: number

    if (imgRatio > cutoutRatio) {
      drawH = PHOTO_H
      drawW = PHOTO_H * imgRatio
      drawX = CUTOUT_X + (PHOTO_W - drawW) / 2
      drawY = CUTOUT_Y
    } else {
      drawW = PHOTO_W
      drawH = PHOTO_W / imgRatio
      drawX = CUTOUT_X
      drawY = CUTOUT_Y + (PHOTO_H - drawH) / 2
    }

    ctx.drawImage(photoImg, drawX, drawY, drawW, drawH)
    ctx.restore()

    ctx.drawImage(frameImg, 0, 0, CANVAS_W, CANVAS_H)

    return offscreen.toDataURL('image/webp', 0.85)
  }

  // Process a file from camera input
  const processPhoto = async (file: File): Promise<void> => {
    isProcessing.value = true
    try {
      const photoSrc = await new Promise<string>((resolve, reject) => {
        const reader = new FileReader()
        reader.onload = (e) => resolve(e.target?.result as string)
        reader.onerror = (e) => reject(e)
        reader.readAsDataURL(file)
      })
      mergedImageUrl.value = await composePhotoWithFrame(photoSrc)
    } finally {
      isProcessing.value = false
    }
  }

  // Set audio from recording
  const setAudio = (blob: Blob) => {
    audioBlob.value = blob
    const reader = new FileReader()
    reader.readAsDataURL(blob)
    reader.onloadend = () => {
      const dataUrl = reader.result as string
      audioDataUrl.value = dataUrl
      // Use Data URL for playback instead of Blob URL to fix iOS WebKit duration & playback issues
      audioObjectUrl.value = dataUrl
    }
  }

  // Clear audio
  const clearAudio = () => {
    audioBlob.value = null
    audioDataUrl.value = null
    audioObjectUrl.value = null
  }

  // Reset everything
  const reset = () => {
    mergedImageUrl.value = null
    isProcessing.value = false
    clearAudio()
  }

  // Upload to server
  const uploadToServer = async (includeAudio = false): Promise<boolean> => {
    if (!mergedImageUrl.value) return false

    const controller = new AbortController()
    const timeout = setTimeout(() => controller.abort(), 30000)

    try {
      const payload: any = {
        image: mergedImageUrl.value,
        guest_token: 'demo-token'
      }
      if (includeAudio && audioDataUrl.value) {
        payload.audio = audioDataUrl.value
      }
      const response = await fetch('/api/photos', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify(payload),
        signal: controller.signal
      })
      clearTimeout(timeout)
      if (response.ok) {
        reset()
        return true
      } else {
        const err = await response.text()
        console.error('Upload error:', err)
        alert('Gagal mengupload. Error: ' + err)
        return false
      }
    } catch (error: any) {
      clearTimeout(timeout)
      console.error(error)
      if (error.name === 'AbortError') {
        alert('Upload timeout. Pastikan server backend berjalan dan coba lagi.')
      } else {
        alert('Terjadi kesalahan saat upload. Pastikan server backend berjalan.')
      }
      return false
    }
  }

  return {
    mergedImageUrl,
    isProcessing,
    audioBlob,
    audioDataUrl,
    audioObjectUrl,
    composePhotoWithFrame,
    processPhoto,
    setAudio,
    clearAudio,
    reset,
    uploadToServer,
  }
})
