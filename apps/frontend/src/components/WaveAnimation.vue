<script setup lang="ts">
defineProps<{
  active?: boolean
}>()
</script>

<template>
  <div class="wave-container" :class="{ 'wave-container--active': active }">
    <div class="wave-bar" v-for="i in 7" :key="i" :style="{ animationDelay: `${(i - 1) * 0.12}s` }"></div>
  </div>
</template>

<style scoped>
.wave-container {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 3px;
  height: 48px;
  padding: 0 16px;
}

.wave-bar {
  width: 4px;
  height: 8px;
  border-radius: 3px;
  background: linear-gradient(180deg, #f9a8d4, #ec4899);
  transition: height 0.3s ease, opacity 0.3s ease;
  opacity: 0.35;
}

.wave-container--active .wave-bar {
  animation: wave-bounce 0.8s ease-in-out infinite alternate;
  opacity: 1;
}

@keyframes wave-bounce {
  0% {
    height: 8px;
    opacity: 0.5;
  }
  50% {
    height: 28px;
    opacity: 0.85;
  }
  100% {
    height: 40px;
    opacity: 1;
    box-shadow: 0 0 8px rgba(236, 72, 153, 0.5);
  }
}
</style>
