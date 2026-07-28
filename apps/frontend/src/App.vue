<script setup lang="ts">
import { useRoute } from 'vue-router'

const route = useRoute()
</script>

<template>
  <div class="min-h-screen font-sans bg-[#fdf2f8] relative overflow-hidden flex flex-col items-center">

    <!-- FLOATING HEARTS ANIMATION -->
    <div class="fixed inset-0 pointer-events-none overflow-hidden z-0">
      <div 
        v-for="i in 18" 
        :key="i"
        class="absolute bottom-[-50px] text-pink-400/40 text-2xl animate-float-heart"
        :style="{
          left: `${(i * 5.5) % 100}%`,
          animationDelay: `${(i * 0.7) % 8}s`,
          animationDuration: `${7 + (i * 0.5) % 7}s`,
          fontSize: `${16 + (i * 3) % 20}px`
        }"
      >
        ❤
      </div>
    </div>

    <!-- MAIN CONTAINER -->
    <div class="relative z-10 w-full max-w-lg flex flex-col pb-8 px-4 gap-6" :class="{ 'pt-6': route.path !== '/' }">
      
      <!-- HEADER BANNER -->
      <div v-if="route.path === '/'" class="w-full overflow-hidden relative">
        <img src="./assets/bg.webp" class="w-full h-auto object-cover block" alt="Header Banner" />
        
        <!-- CLOUD EFFECT LAYER 1 -->
        <svg class="absolute bottom-[-1px] left-0 right-0 w-full h-24 text-[#fdf2f8]/15 fill-current pointer-events-none" viewBox="0 0 1000 100" preserveAspectRatio="none">
          <path d="M 0 100 L 0 65 Q 80 35, 180 65 Q 260 25, 340 65 Q 450 15, 550 65 Q 650 35, 750 65 Q 850 20, 930 65 Q 965 40, 1000 65 L 1000 100 Z" />
        </svg>
        
        <!-- CLOUD EFFECT LAYER 2 -->
        <svg class="absolute bottom-[-2px] left-0 right-0 w-full h-18 text-[#fdf2f8]/35 fill-current pointer-events-none" viewBox="0 0 1000 100" preserveAspectRatio="none">
          <path d="M 0 100 L 0 55 Q 60 25, 120 55 Q 200 15, 280 55 Q 360 25, 420 55 Q 480 5, 560 55 Q 630 25, 680 55 Q 740 15, 810 55 Q 870 25, 920 55 Q 960 10, 1000 55 L 1000 100 Z" />
        </svg>

        <!-- CLOUD EFFECT LAYER 3 -->
        <svg class="absolute bottom-[-3px] left-0 right-0 w-full h-12 text-[#fdf2f8]/55 fill-current pointer-events-none" viewBox="0 0 1000 100" preserveAspectRatio="none">
          <path d="M 0 100 L 0 45 Q 40 15, 80 45 Q 120 5, 180 45 Q 230 15, 270 45 Q 310 0, 380 45 Q 440 15, 480 45 Q 530 5, 590 45 Q 640 15, 680 45 Q 720 0, 790 45 Q 840 15, 880 45 Q 920 0, 1000 45 L 1000 100 Z" />
        </svg>
      </div>

      <!-- ROUTER VIEW - Pages render here -->
      <router-view v-slot="{ Component }">
        <transition name="page-fade" mode="out-in">
          <component :is="Component" />
        </transition>
      </router-view>

      <div class="pb-6 text-center">
        <p class="text-gray-400 text-xs">❤ Samuel & Angela Wedding Photobooth</p>
      </div>
    </div>
  </div>
</template>

<style>
@keyframes float-heart {
  0% {
    transform: translateY(0) rotate(0deg) scale(0.8);
    opacity: 0;
  }
  10% {
    opacity: 0.6;
  }
  90% {
    opacity: 0.6;
  }
  100% {
    transform: translateY(-105vh) rotate(360deg) scale(1.2);
    opacity: 0;
  }
}
.animate-float-heart {
  animation-name: float-heart;
  animation-iteration-count: infinite;
  animation-timing-function: linear;
}

/* Polaroid 3D shadow effect */
.polaroid-preview,
.gallery-frame {
  filter:
    drop-shadow(0 2px 3px rgba(0, 0, 0, 0.12))
    drop-shadow(0 8px 16px rgba(0, 0, 0, 0.14))
    drop-shadow(0 16px 32px rgba(0, 0, 0, 0.10));
  transition: filter 0.3s ease, transform 0.3s ease;
}
.polaroid-preview:hover,
.gallery-frame:hover {
  filter:
    drop-shadow(0 4px 6px rgba(0, 0, 0, 0.15))
    drop-shadow(0 12px 24px rgba(0, 0, 0, 0.18))
    drop-shadow(0 24px 48px rgba(0, 0, 0, 0.12));
}

/* ---- Lightbox ---- */
.lightbox-overlay {
  position: fixed;
  inset: 0;
  z-index: 50;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: rgba(0, 0, 0, 0.85);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  padding: 1rem;
}
.lightbox-close {
  position: absolute;
  top: 1rem;
  right: 1rem;
  z-index: 60;
  width: 2.5rem;
  height: 2.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 9999px;
  border: none;
  background: rgba(255, 255, 255, 0.15);
  color: #fff;
  cursor: pointer;
  transition: background 0.2s ease;
}
.lightbox-close:hover {
  background: rgba(255, 255, 255, 0.3);
}
.lightbox-content {
  display: flex;
  align-items: center;
  justify-content: center;
  max-width: 90vw;
  max-height: 65vh;
}
.lightbox-media {
  max-width: 90vw;
  max-height: 65vh;
  object-fit: contain;
  border-radius: 4px;
}
.lightbox-audio {
  width: 90vw;
  max-width: 360px;
  margin-top: 0.75rem;
}
.lightbox-actions {
  display: flex;
  gap: 0.75rem;
  margin-top: 1.25rem;
}
.lightbox-btn {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.6rem 1.25rem;
  border-radius: 9999px;
  border: none;
  font-size: 0.85rem;
  font-weight: 600;
  color: #fff;
  background: rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(6px);
  cursor: pointer;
  transition: background 0.2s ease;
}
.lightbox-btn:hover {
  background: rgba(255, 255, 255, 0.3);
}

/* Lightbox transitions */
.lightbox-enter-active,
.lightbox-leave-active {
  transition: opacity 0.25s ease;
}
.lightbox-enter-from,
.lightbox-leave-to {
  opacity: 0;
}

/* Page transition */
.page-fade-enter-active,
.page-fade-leave-active {
  transition: opacity 0.2s ease;
}
.page-fade-enter-from,
.page-fade-leave-to {
  opacity: 0;
}
</style>
