<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { router, usePage, Link } from '@inertiajs/vue3'
import { gsap } from 'gsap'
import {
    PhBell,
    PhMagnifyingGlass,
    PhX,
    PhCalendarCheck,
    PhUsers,
    PhPackage,
    PhChartBar,
    PhCaretDown,
    PhUser,
    PhGear,
    PhSignOut,
    PhArrowSquareOut,
    PhList,
    PhCircleNotch,
} from '@phosphor-icons/vue'

defineProps({
    title:       { type: String, default: '' },
    breadcrumbs: { type: Array, default: () => [] },
})
const emit = defineEmits(['toggle-sidebar'])

const page       = usePage()
const user       = computed(() => page.props.auth.user)
const flash      = computed(() => page.props.flash)

// ── Recherche globale ──────────────────────────────────────────────
const searchQuery    = ref('')
const searchOpen     = ref(false)
const searchResults  = ref([])
const searchLoading  = ref(false)
const searchRef      = ref(null)

function openSearch() {
    searchOpen.value = true
    setTimeout(() => searchRef.value?.focus(), 50)
    gsap.fromTo('.search-panel',
        { opacity: 0, y: -8 },
        { opacity: 1, y: 0, duration: 0.2, ease: 'power2.out' }
    )
}

function closeSearch() {
    searchQuery.value   = ''
    searchResults.value = []
    searchOpen.value    = false
}

let searchTimer = null
function onSearchInput() {
    clearTimeout(searchTimer)
    if (!searchQuery.value.trim()) {
        searchResults.value = []
        return
    }
    searchLoading.value = true
    searchTimer = setTimeout(() => performSearch(), 300)
}

async function performSearch() {
    // Recherche locale rapide (peut être étendue à une vraie API)
    const q = searchQuery.value.toLowerCase()
    const results = []

    const quickLinks = [
        { type: 'page', label: 'Dashboard',         route: 'admin.dashboard',           icon: PhChartBar },
        { type: 'page', label: 'Rendez-vous',        route: 'admin.rendez-vous.index',   icon: PhCalendarCheck },
        { type: 'page', label: 'Clients',            route: 'admin.clients.index',       icon: PhUsers },
        { type: 'page', label: 'Produits',           route: 'admin.produits.index',      icon: PhPackage },
        { type: 'page', label: 'Factures',           route: 'admin.factures.index',      icon: PhChartBar },
        { type: 'page', label: 'Rapports financiers',route: 'admin.rapports.index',      icon: PhChartBar },
        { type: 'page', label: 'Paramètres',         route: 'admin.parametres.index',    icon: PhGear },
    ]

    quickLinks.forEach(link => {
        if (link.label.toLowerCase().includes(q)) results.push(link)
    })

    searchResults.value = results.slice(0, 6)
    searchLoading.value = false
}

// ── Notifications panel ────────────────────────────────────────────
const notifOpen     = ref(false)
const notifications = computed(() => page.props.flash?.notifications ?? [])
const unread        = computed(() => user.value ? page.props.auth.user.unread_notifications_count ?? 0 : 0)

function toggleNotif() {
    notifOpen.value = !notifOpen.value
    profileOpen.value = false
    if (notifOpen.value) {
        gsap.fromTo('.notif-panel',
            { opacity: 0, y: -8, scale: 0.97 },
            { opacity: 1, y: 0, scale: 1, duration: 0.2, ease: 'power2.out' }
        )
    }
}

// ── Profile dropdown ───────────────────────────────────────────────
const profileOpen = ref(false)

function toggleProfile() {
    profileOpen.value = !profileOpen.value
    notifOpen.value   = false
    if (profileOpen.value) {
        gsap.fromTo('.profile-panel',
            { opacity: 0, y: -8, scale: 0.97 },
            { opacity: 1, y: 0, scale: 1, duration: 0.2, ease: 'power2.out' }
        )
    }
}

// Fermer les panels au clic extérieur
function handleOutsideClick(e) {
    if (!e.target.closest('.notif-wrapper'))   notifOpen.value   = false
    if (!e.target.closest('.profile-wrapper')) profileOpen.value = false
    if (!e.target.closest('.search-wrapper') && searchOpen.value) closeSearch()
}

onMounted(() => document.addEventListener('click', handleOutsideClick))
onUnmounted(() => document.removeEventListener('click', handleOutsideClick))

// Flash messages
const flashVisible = ref(true)
watch(() => flash.value, () => { flashVisible.value = true }, { deep: true })
</script>

<template>
    <header class="h-16 flex items-center justify-between px-6 gap-4 shrink-0 sticky top-0 z-30"
            style="background: rgba(250,247,244,0.95); backdrop-filter: blur(12px); border-bottom: 1px solid rgba(26,26,46,0.08);">

        <!-- ── Gauche : Toggle + Breadcrumb ───────────────── -->
        <div class="flex items-center gap-4 min-w-0">

            <!-- Burger -->
            <button @click="emit('toggle-sidebar')"
                    class="w-9 h-9 rounded-xl flex items-center justify-center transition-all hover:bg-cream-200">
                <PhList :size="20" class="text-onyx-600" />
            </button>

            <!-- Breadcrumbs -->
            <nav class="hidden md:flex items-center gap-1.5 min-w-0">
                <Link :href="route('admin.dashboard')"
                      class="text-sm font-poppins text-onyx-400 hover:text-onyx-700 transition-colors shrink-0">
                    Admin
                </Link>
                <template v-for="(crumb, i) in breadcrumbs" :key="i">
                    <svg class="w-3 h-3 text-onyx-300 shrink-0" viewBox="0 0 12 12" fill="none">
                        <path d="M4 2L8 6L4 10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                    <Link v-if="crumb.route" :href="route(crumb.route)"
                          class="text-sm font-poppins text-onyx-400 hover:text-onyx-700 transition-colors whitespace-nowrap">
                        {{ crumb.label }}
                    </Link>
                    <span v-else class="text-sm font-poppins font-semibold text-onyx-700 truncate">
                        {{ crumb.label }}
                    </span>
                </template>
            </nav>

            <!-- Title mobile -->
            <span class="md:hidden text-base font-cormorant font-semibold text-onyx-800 truncate">
                {{ title }}
            </span>
        </div>

        <!-- ── Droite : Actions ────────────────────────────── -->
        <div class="flex items-center gap-2 shrink-0">

            <!-- Recherche globale -->
            <div class="search-wrapper relative">
                <button @click="openSearch"
                        class="w-9 h-9 rounded-xl flex items-center justify-center transition-all hover:bg-cream-200">
                    <PhMagnifyingGlass :size="18" class="text-onyx-500" />
                </button>

                <!-- Panel recherche -->
                <Transition name="dropdown">
                    <div v-if="searchOpen"
                         class="search-panel absolute right-0 top-12 w-80 rounded-2xl shadow-luxury overflow-hidden z-50"
                         style="background: white; border: 1px solid rgba(26,26,46,0.1);">

                        <div class="p-3 border-b border-cream-200">
                            <div class="relative">
                                <PhMagnifyingGlass :size="16" class="absolute left-3 top-1/2 -translate-y-1/2 text-onyx-400" />
                                <input
                                    ref="searchRef"
                                    v-model="searchQuery"
                                    @input="onSearchInput"
                                    type="text"
                                    placeholder="Rechercher une page, un client..."
                                    class="w-full pl-9 pr-9 py-2.5 text-sm rounded-xl font-poppins outline-none transition-all"
                                    style="background: #f9f7f4; border: 1.5px solid #edddd0; color: #1a1a2e;"
                                />
                                <button @click="closeSearch"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-onyx-400 hover:text-onyx-600">
                                    <PhX :size="14" />
                                </button>
                            </div>
                        </div>

                        <div class="max-h-64 overflow-y-auto">
                            <div v-if="searchLoading" class="flex items-center justify-center p-6">
                                <PhCircleNotch :size="20" class="text-cognac-400 animate-spin" />
                            </div>

                            <div v-else-if="searchResults.length" class="p-2">
                                <p class="px-3 py-1.5 text-xs font-semibold text-onyx-400 uppercase tracking-widest font-poppins">
                                    Résultats
                                </p>
                                <Link
                                    v-for="result in searchResults"
                                    :key="result.label"
                                    :href="route(result.route)"
                                    @click="closeSearch"
                                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-cream-100 transition-colors group"
                                >
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
                                         style="background: rgba(196,149,106,0.1);">
                                        <component :is="result.icon" :size="16" class="text-cognac-500" />
                                    </div>
                                    <span class="text-sm font-poppins text-onyx-700 group-hover:text-onyx-900">{{ result.label }}</span>
                                    <PhArrowSquareOut :size="14" class="ml-auto text-onyx-300 group-hover:text-onyx-500" />
                                </Link>
                            </div>

                            <div v-else-if="searchQuery.trim() && !searchLoading"
                                 class="p-6 text-center">
                                <p class="text-sm text-onyx-400 font-poppins">Aucun résultat pour "{{ searchQuery }}"</p>
                            </div>

                            <div v-else class="p-4">
                                <p class="text-xs font-poppins text-onyx-400 mb-2">Navigation rapide</p>
                                <div class="flex flex-wrap gap-2">
                                    <Link :href="route('admin.rendez-vous.index')"
                                          @click="closeSearch"
                                          class="px-3 py-1.5 rounded-lg text-xs font-poppins font-medium transition-colors"
                                          style="background: rgba(196,149,106,0.1); color: #b07d52;">
                                        Rendez-vous
                                    </Link>
                                    <Link :href="route('admin.clients.index')"
                                          @click="closeSearch"
                                          class="px-3 py-1.5 rounded-lg text-xs font-poppins font-medium transition-colors"
                                          style="background: rgba(196,149,106,0.1); color: #b07d52;">
                                        Clients
                                    </Link>
                                    <Link :href="route('admin.factures.index')"
                                          @click="closeSearch"
                                          class="px-3 py-1.5 rounded-lg text-xs font-poppins font-medium transition-colors"
                                          style="background: rgba(196,149,106,0.1); color: #b07d52;">
                                        Factures
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>

            <!-- Notifications -->
            <div class="notif-wrapper relative">
                <button @click.stop="toggleNotif"
                        class="relative w-9 h-9 rounded-xl flex items-center justify-center transition-all hover:bg-cream-200">
                    <PhBell :size="18" class="text-onyx-500" />
                    <span v-if="unread > 0"
                          class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full animate-pulse-glow"
                          style="background: #e14d6e;" />
                </button>

                <Transition name="dropdown">
                    <div v-if="notifOpen"
                         class="notif-panel absolute right-0 top-12 w-80 rounded-2xl shadow-luxury z-50"
                         style="background: white; border: 1px solid rgba(26,26,46,0.1);">

                        <div class="flex items-center justify-between px-4 py-3"
                             style="border-bottom: 1px solid #f5ede4;">
                            <div class="flex items-center gap-2">
                                <PhBell :size="16" class="text-cognac-500" />
                                <span class="text-sm font-semibold font-poppins text-onyx-800">Notifications</span>
                                <span v-if="unread > 0"
                                      class="text-white text-xs font-bold px-1.5 py-0.5 rounded-full"
                                      style="background: #e14d6e;">{{ unread }}</span>
                            </div>
                            <Link :href="route('admin.notifications.index')"
                                  class="text-xs text-cognac-600 hover:text-cognac-800 font-poppins font-medium">
                                Tout voir
                            </Link>
                        </div>

                        <div class="p-4">
                            <div class="space-y-3">
                                <!-- Notif demo -->
                                <div class="flex items-start gap-3 p-3 rounded-xl hover:bg-cream-50 transition-colors cursor-pointer"
                                     style="border: 1px solid #f5ede4;">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
                                         style="background: rgba(196,149,106,0.1);">
                                        <PhCalendarCheck :size="16" class="text-cognac-500" />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-xs font-semibold text-onyx-800 font-poppins">Nouveau rendez-vous</p>
                                        <p class="text-xs text-onyx-500 font-poppins mt-0.5 leading-relaxed">
                                            Amina Diallo — Box Braids demain 10h
                                        </p>
                                        <p class="text-xs text-onyx-400 font-poppins mt-1">Il y a 5 min</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="px-4 pb-3">
                            <Link :href="route('admin.notifications.read-all')"
                                  method="patch" as="button"
                                  class="w-full text-center text-xs text-onyx-400 hover:text-cognac-600 font-poppins transition-colors py-1">
                                Tout marquer comme lu
                            </Link>
                        </div>
                    </div>
                </Transition>
            </div>

            <!-- Séparateur -->
            <div class="w-px h-6" style="background: rgba(26,26,46,0.1);" />

            <!-- Profil -->
            <div class="profile-wrapper relative">
                <button @click.stop="toggleProfile"
                        class="flex items-center gap-2.5 pl-1 pr-3 py-1.5 rounded-xl transition-all hover:bg-cream-200">
                    <img :src="user?.avatar" :alt="user?.name"
                         class="w-7 h-7 rounded-lg object-cover shrink-0" />
                    <div class="hidden md:block text-left">
                        <p class="text-xs font-semibold font-poppins text-onyx-800 leading-none whitespace-nowrap">
                            {{ user?.name?.split(' ')[0] }}
                        </p>
                        <p class="text-xs font-poppins mt-0.5" style="color: #c4956a;">Admin</p>
                    </div>
                    <PhCaretDown :size="13" class="text-onyx-400 transition-transform duration-200"
                                 :class="profileOpen ? 'rotate-180' : ''" />
                </button>

                <Transition name="dropdown">
                    <div v-if="profileOpen"
                         class="profile-panel absolute right-0 top-12 w-56 rounded-2xl shadow-luxury z-50"
                         style="background: white; border: 1px solid rgba(26,26,46,0.1);">

                        <!-- User info -->
                        <div class="p-4" style="border-bottom: 1px solid #f5ede4;">
                            <div class="flex items-center gap-3">
                                <img :src="user?.avatar" :alt="user?.name"
                                     class="w-10 h-10 rounded-xl object-cover" />
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold font-poppins text-onyx-800 truncate">{{ user?.name }}</p>
                                    <p class="text-xs font-poppins text-onyx-400 truncate">{{ user?.email }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Menu items -->
                        <div class="p-2">
                            <Link :href="route('profile.show')"
                                  class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-cream-50 transition-colors group">
                                <PhUser :size="16" class="text-onyx-400 group-hover:text-cognac-500 transition-colors" />
                                <span class="text-sm font-poppins text-onyx-600 group-hover:text-onyx-800">Mon profil</span>
                            </Link>
                            <Link :href="route('admin.parametres.index')"
                                  class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-cream-50 transition-colors group">
                                <PhGear :size="16" class="text-onyx-400 group-hover:text-cognac-500 transition-colors" />
                                <span class="text-sm font-poppins text-onyx-600 group-hover:text-onyx-800">Paramètres</span>
                            </Link>
                            <Link :href="route('home')" target="_blank"
                                  class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-cream-50 transition-colors group">
                                <PhArrowSquareOut :size="16" class="text-onyx-400 group-hover:text-cognac-500 transition-colors" />
                                <span class="text-sm font-poppins text-onyx-600 group-hover:text-onyx-800">Voir la vitrine</span>
                            </Link>
                        </div>

                        <div class="p-2 pt-0" style="border-top: 1px solid #f5ede4;">
                            <Link :href="route('logout')" method="post" as="button"
                                  class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-red-50 transition-colors group">
                                <PhSignOut :size="16" class="text-red-400 group-hover:text-red-600 transition-colors" />
                                <span class="text-sm font-poppins text-red-500 group-hover:text-red-700">Se déconnecter</span>
                            </Link>
                        </div>
                    </div>
                </Transition>
            </div>
        </div>
    </header>
</template>

<style scoped>
.dropdown-enter-active { transition: all 0.2s cubic-bezier(0.25, 0.46, 0.45, 0.94); }
.dropdown-leave-active { transition: all 0.15s ease; }
.dropdown-enter-from   { opacity: 0; transform: translateY(-8px) scale(0.97); }
.dropdown-leave-to     { opacity: 0; transform: translateY(-8px) scale(0.97); }
</style>