import { createRouter, createWebHistory } from 'vue-router'
import HomePage from '../pages/HomePage.vue'

const routes = [
  { path: '/', name: 'Home', component: HomePage },
  { path: '/preview', name: 'Preview', component: () => import('../pages/PreviewPage.vue') },
]

export const router = createRouter({
  history: createWebHistory(),
  routes,
})
