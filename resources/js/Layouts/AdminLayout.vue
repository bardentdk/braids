<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import Sidebar from '@/Components/Admin/Sidebar.vue'
import AdminHeader from '@/Components/Admin/AdminHeader.vue'

const props = defineProps({
    title:       { type: String, default: '' },
    breadcrumbs: { type: Array, default: () => [] },
})

const sidebarCollapsed = ref(false)
const mobileOpen       = ref(false)
const isMobile = ref(typeof window !== 'undefined' ? window.innerWidth < 1024 : false)


function checkMobile() {
    isMobile.value = window.innerWidth < 1024
    // Sur mobile, on ferme la sidebar par défaut
    if (isMobile.value) mobileOpen.value = false
}

function toggleSidebar() {
    if (isMobile.value) {
        mobileOpen.value = !mobileOpen.value
    } else {
        sidebarCollapsed.value = !sidebarCollapsed.value
    }
}

onMounted(() => {
    checkMobile()
    window.addEventListener('resize', checkMobile)
})
onUnmounted(() => {
    window.removeEventListener('resize', checkMobile)
})
</script>

<template>
    <div class="flex h-screen overflow-hidden" style="background: #faf7f4;">

        <!-- ── Overlay mobile ─────────────────────────────── -->
        <Transition name="fade">
            <div
                v-if="isMobile && mobileOpen"
                class="fixed inset-0 z-40 lg:hidden"
                style="background: rgba(0,0,0,0.5); backdrop-filter: blur(2px);"
                @click="mobileOpen = false"
            />
        </Transition>

        <!-- ── Sidebar MOBILE (drawer, fixed) ────────────── -->
        <Transition name="slide-left">
            <div v-if="isMobile && mobileOpen" class="fixed inset-y-0 left-0 z-50">
                <Sidebar :collapsed="false" @toggle="mobileOpen = false" />
            </div>
        </Transition>

        <!-- Desktop — uniquement si PAS mobile -->
        <div v-if="!isMobile" class="shrink-0 relative z-20">
            <Sidebar :collapsed="sidebarCollapsed" @toggle="sidebarCollapsed = !sidebarCollapsed" />
        </div>

        <!-- ── Contenu principal ──────────────────────────── -->
        <div class="flex flex-col flex-1 min-w-0 overflow-hidden">

            <AdminHeader
                :title="title"
                :breadcrumbs="breadcrumbs"
                @toggle-sidebar="toggleSidebar"
            />

            <main class="flex-1 overflow-y-auto overflow-x-hidden">
                <slot />
            </main>

        </div>
    </div>
</template>

<style scoped>
/* Overlay */
.fade-enter-active, .fade-leave-active { transition: opacity 0.25s ease; }
.fade-enter-from, .fade-leave-to       { opacity: 0; }

/* Drawer mobile */
.slide-left-enter-active { transition: transform 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94); }
.slide-left-leave-active { transition: transform 0.25s cubic-bezier(0.55, 0, 1, 0.45); }
.slide-left-enter-from   { transform: translateX(-100%); }
.slide-left-leave-to     { transform: translateX(-100%); }
</style>