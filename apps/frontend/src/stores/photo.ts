import { defineStore } from 'pinia'

export const usePhotoStore = defineStore('photo', {
  state: () => ({
    photoDataUrl: null as string | null,
    audioDataUrl: null as string | null,
  }),
  actions: {
    setPhoto(dataUrl: string) {
      this.photoDataUrl = dataUrl
    },
    setAudio(dataUrl: string) {
      this.audioDataUrl = dataUrl
    },
    clear() {
      this.photoDataUrl = null
      this.audioDataUrl = null
    }
  }
})
