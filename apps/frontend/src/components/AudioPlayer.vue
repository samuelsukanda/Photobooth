<script setup lang="ts">
import { ref, computed } from 'vue'

const props = defineProps<{
  src: string
  compact?: boolean
}>()

const audio = ref<HTMLAudioElement | null>(null)
const isPlaying = ref(false)
const currentTime = ref(0)
const duration = ref(0)
const isDragging = ref(false)

const progress = computed(() => {
  if (!duration.value) return 0
  return (currentTime.value / duration.value) * 100
})

const formatTime = (s: number) => {
  const m = Math.floor(s / 60)
  const sec = Math.floor(s % 60)
  return `${m}:${sec.toString().padStart(2, '0')}`
}

const togglePlay = () => {
  if (!audio.value) return
  if (isPlaying.value) {
    audio.value.pause()
  } else {
    audio.value.play()
  }
}

const onPlay = () => { isPlaying.value = true }
const onPause = () => { isPlaying.value = false }
const onEnded = () => { isPlaying.value = false; currentTime.value = 0 }

const onTimeUpdate = () => {
  if (!isDragging.value && audio.value) {
    currentTime.value = audio.value.currentTime
  }
}

const onLoadedMetadata = () => {
  if (audio.value) {
    if (audio.value.duration === Infinity || isNaN(audio.value.duration)) {
      // Workaround for Safari/iOS bug with MediaRecorder blobs missing duration header
      audio.value.currentTime = 1e101
      setTimeout(() => {
        if (audio.value) {
          audio.value.currentTime = 0
          duration.value = audio.value.duration
        }
      }, 200)
    } else {
      duration.value = audio.value.duration
    }
  }
}

const onSeek = (e: MouseEvent) => {
  const bar = e.currentTarget as HTMLElement
  const rect = bar.getBoundingClientRect()
  const ratio = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width))
  if (audio.value) {
    audio.value.currentTime = ratio * duration.value
    currentTime.value = audio.value.currentTime
  }
}

const onTouchSeek = (e: TouchEvent) => {
  const bar = e.currentTarget as HTMLElement
  const rect = bar.getBoundingClientRect()
  const touch = e.touches[0]
  const ratio = Math.max(0, Math.min(1, (touch.clientX - rect.left) / rect.width))
  if (audio.value) {
    audio.value.currentTime = ratio * duration.value
    currentTime.value = audio.value.currentTime
  }
}

const handleDownloadAudio = () => {
  const link = document.createElement('a')
  link.href = props.src
  link.download = 'voice-message.webm'
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}
</script>

<template>
  <div class="audio-player" :class="{ 'audio-player--compact': compact }">
    <audio
      ref="audio"
      :src="src"
      preload="metadata"
      @play="onPlay"
      @pause="onPause"
      @ended="onEnded"
      @timeupdate="onTimeUpdate"
      @loadedmetadata="onLoadedMetadata"
    />

    <!-- Play / Pause -->
    <button class="audio-player__play" @click.stop="togglePlay" type="button">
      <!-- Play icon -->
      <svg v-if="!isPlaying" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
        <path d="M8 5v14l11-7z"/>
      </svg>
      <!-- Pause icon -->
      <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
        <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>
      </svg>
    </button>

    <!-- Progress bar -->
    <div class="audio-player__bar" @click.stop="onSeek" @touchstart.stop="onTouchSeek">
      <div class="audio-player__track">
        <div class="audio-player__fill" :style="{ width: progress + '%' }"></div>
        <div class="audio-player__knob" :style="{ left: progress + '%' }"></div>
      </div>
    </div>

    <!-- Time -->
    <span class="audio-player__time">{{ formatTime(currentTime) }} / {{ formatTime(duration) }}</span>

    <!-- Download -->
    <button class="audio-player__download" @click.stop="handleDownloadAudio" type="button" title="Download audio">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
      </svg>
    </button>
  </div>
</template>

<style scoped>
.audio-player {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  background: linear-gradient(135deg, #fdf2f8, #fce7f3);
  border-radius: 12px;
  border: 1px solid rgba(236, 72, 153, 0.15);
  width: 100%;
  box-sizing: border-box;
}

.audio-player--compact {
  padding: 6px 10px;
  gap: 6px;
  border-radius: 10px;
}

.audio-player__play {
  flex-shrink: 0;
  width: 28px;
  height: 28px;
  border-radius: 50%;
  border: none;
  background: #ec4899;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: background 0.2s, transform 0.15s;
  padding: 0;
}
.audio-player--compact .audio-player__play {
  width: 24px;
  height: 24px;
}
.audio-player__play:hover {
  background: #db2777;
  transform: scale(1.08);
}
.audio-player__play svg {
  width: 14px;
  height: 14px;
}
.audio-player--compact .audio-player__play svg {
  width: 12px;
  height: 12px;
}

.audio-player__bar {
  flex: 1;
  height: 20px;
  display: flex;
  align-items: center;
  cursor: pointer;
  position: relative;
  touch-action: none;
}

.audio-player__track {
  width: 100%;
  height: 4px;
  background: rgba(236, 72, 153, 0.2);
  border-radius: 2px;
  position: relative;
  overflow: visible;
}

.audio-player__fill {
  height: 100%;
  background: linear-gradient(90deg, #ec4899, #f472b6);
  border-radius: 2px;
  transition: width 0.1s linear;
}

.audio-player__knob {
  position: absolute;
  top: 50%;
  width: 10px;
  height: 10px;
  background: #ec4899;
  border: 2px solid white;
  border-radius: 50%;
  transform: translate(-50%, -50%);
  box-shadow: 0 1px 4px rgba(0,0,0,0.2);
  transition: left 0.1s linear;
}

.audio-player__time {
  flex-shrink: 0;
  font-size: 10px;
  font-weight: 600;
  color: #9ca3af;
  min-width: 58px;
  text-align: center;
  font-variant-numeric: tabular-nums;
}
.audio-player--compact .audio-player__time {
  font-size: 9px;
  min-width: 50px;
}

.audio-player__download {
  flex-shrink: 0;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  border: none;
  background: rgba(236, 72, 153, 0.12);
  color: #ec4899;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: background 0.2s, transform 0.15s;
  padding: 0;
}
.audio-player--compact .audio-player__download {
  width: 20px;
  height: 20px;
}
.audio-player__download:hover {
  background: rgba(236, 72, 153, 0.25);
  transform: scale(1.08);
}
.audio-player__download svg {
  width: 13px;
  height: 13px;
}
.audio-player--compact .audio-player__download svg {
  width: 11px;
  height: 11px;
}
</style>
