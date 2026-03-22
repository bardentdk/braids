<script setup>
import { ref, computed, onMounted, watch, markRaw  } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { gsap } from 'gsap'
import {
    PhBridge,
    PhArticle,
    PhHouse,
    PhCalendarCheck,
    PhClock, PhUsers,
    PhShoppingBag,
    PhTag,
    PhPackage,
    PhReceipt,
    PhCreditCard,
    PhTicket,
    PhImages,
    PhStar,
    PhChartBar,
    PhGear,
    PhBell,
    PhCaretRight,
    PhCaretDown,
    PhArrowSquareOut,
    PhSignOut,
} from '@phosphor-icons/vue'

const props = defineProps({
    collapsed: { type: Boolean, default: false },
})
const emit = defineEmits(['toggle'])

const page = usePage()
const currentUrl = computed(() => page.url)

// ── Navigation structure ───────────────────────────────────────────
const nav = ref([
    { label: 'Dashboard', route: 'admin.dashboard', icon: markRaw(PhHouse) },
    {
        label: 'Agenda',
        icon: markRaw(PhCalendarCheck),
        children: [
            { label: 'Rendez-vous',    icon: markRaw(PhCalendarCheck), route: 'admin.rendez-vous.index' },
            { label: 'Calendrier',     icon: markRaw(PhClock),         route: 'admin.rendez-vous.calendar' },
            { label: 'Disponibilités', icon: markRaw(PhClock),         route: 'admin.disponibilites.index' },
        ],
    },
    { label: 'Clients',    icon: markRaw(PhUsers),      route: 'admin.clients.index' },
    {
        label: 'Boutique',
        icon: markRaw(PhShoppingBag),
        children: [
            { label: 'Commandes',  icon: markRaw(PhShoppingBag), route: 'admin.commandes.index' },
            { label: 'Produits',   icon: markRaw(PhPackage),     route: 'admin.produits.index' },
            { label: 'Catégories', icon: markRaw(PhTag),         route: 'admin.categories.index' },
            { label: 'Coupons',    icon: markRaw(PhTicket),      route: 'admin.coupons.index' },
        ],
    },
    {
        label: 'Facturation',
        icon: markRaw(PhReceipt),
        children: [
            { label: 'Factures',  icon: markRaw(PhReceipt),    route: 'admin.factures.index' },
            { label: 'Paiements', icon: markRaw(PhCreditCard), route: 'admin.paiements.index' },
        ],
    },
    { label: 'Services', icon: markRaw(PhBridge),   route: 'admin.services.index' },
    {
        label: 'Blog',
        route: 'admin.blog.index',
        icon: markRaw(PhArticle),
        children: [
            { label: 'Articles', route: 'admin.blog.index' },
            { label: 'Catégories', route: 'admin.blog-categories.index' },
        ]
    },
    {
        label: 'Contenu',
        icon: markRaw(PhImages),
        children: [
            { label: 'Galerie', icon: markRaw(PhImages), route: 'admin.galerie.index' },
            { label: 'Avis',    icon: markRaw(PhStar),   route: 'admin.avis.index' },
        ],
    },
    { label: 'Rapports', icon: markRaw(PhChartBar), route: 'admin.rapports.index' },
])

const bottomNav = ref([
    { label: 'Paramètres',    icon: markRaw(PhGear), route: 'admin.parametres.index' },
    { label: 'Notifications', icon: markRaw(PhBell), route: 'admin.notifications.index' },
])
// ── État des sous-menus ────────────────────────────────────────────
const openGroups = ref({})

const isActive = (routeName) => {
    if (!routeName) return false
    try {
        const routeUrl = window.route(routeName)
        if (!routeUrl || routeUrl === '#') return false
        const path = new URL(routeUrl, window.location.origin).pathname
        return window.location.pathname === path
            || window.location.pathname.startsWith(path + '/')
    } catch {
        return false
    }
}


const isGroupActive = (items) => {
    if (!items || !Array.isArray(items)) return false
    return items.some(item => item.route && isActive(item.route))
}

function toggleGroup(label) {
    openGroups.value[label] = !openGroups.value[label]
}

// Auto-ouvrir le groupe actif
nav.value.forEach(item => {
    if (item.children && isGroupActive(item)) {
        openGroups.value[item.label] = true
    }
})

// ── Animations submenu ─────────────────────────────────────────────
function beforeEnterSubmenu(el) {
    el.style.height  = '0'
    el.style.opacity = '0'
}
function enterSubmenu(el, done) {
    gsap.to(el, {
        height:   el.scrollHeight,
        opacity:  1,
        duration: 0.3,
        ease:     'power2.out',
        onComplete: done,
    })
}
function leaveSubmenu(el, done) {
    gsap.to(el, {
        height:  0,
        opacity: 0,
        duration: 0.25,
        ease:    'power2.in',
        onComplete: done,
    })
}

const unreadCount = computed(() => page.props.auth?.user?.unread_notifications ?? 0)
</script>

<template>
    <aside
        class="flex flex-col h-screen sticky top-0 transition-all duration-300 ease-in-out select-none"
        :class="collapsed ? 'w-[72px]' : 'w-64'"
        style="background: #0d0d1a; border-right: 1px solid rgba(255,255,255,0.06);"
    >
        <!-- ── Logo ──────────────────────────────────────── -->
        <div class="flex items-center h-16 px-4 shrink-0"
             style="border-bottom: 1px solid rgba(255,255,255,0.06);">
            <div class="flex items-center gap-3 overflow-hidden">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0"
                     style="background: linear-gradient(135deg, #c4956a, #d4af37);">
                    <PhBridge :size="20" weight="bold" class="text-white" />
                </div>
                <Transition name="fade-slide">
                    <div v-if="!collapsed" class="overflow-hidden whitespace-nowrap">
                        <p class="font-cormorant text-lg font-bold text-white leading-none tracking-wide">
                            Patricia
                        </p>
                        <p class="text-xs font-poppins tracking-[0.2em] uppercase"
                           style="color: #c4956a;">
                            Braids Studio
                        </p>
                    </div>
                </Transition>
            </div>
        </div>

        <!-- ── Navigation principale ─────────────────────── -->
        <nav class="flex-1 overflow-y-auto overflow-x-hidden py-4 space-y-0.5 px-2">

            <template v-for="item in nav" :key="item.label">

                <!-- Item simple (sans enfants) -->
                <Link
                    v-if="!item.children"
                    :href="route(item.route)"
                    class="sidebar-item group flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 relative"
                    :class="isActive(item.route, item.exact)
                        ? 'sidebar-item--active'
                        : 'sidebar-item--default'"
                >
                    <!-- Active indicator -->
                    <div v-if="isActive(item.route, item.exact)"
                         class="absolute left-0 top-1/2 -translate-y-1/2 w-0.5 h-6 rounded-full"
                         style="background: #c4956a;" />

                    <component :is="item.icon" :size="20"
                               :class="isActive(item.route, item.exact) ? 'text-cognac-400' : 'text-white/40 group-hover:text-white/70'"
                               class="shrink-0 transition-colors" />

                    <Transition name="fade-slide">
                        <span v-if="!collapsed"
                              class="text-sm font-poppins font-medium whitespace-nowrap overflow-hidden transition-colors"
                              :class="isActive(item.route, item.exact) ? 'text-white' : 'text-white/50 group-hover:text-white/80'">
                            {{ item.label }}
                        </span>
                    </Transition>

                    <!-- Tooltip en mode collapsed -->
                    <div v-if="collapsed"
                         class="sidebar-tooltip absolute left-full ml-3 px-3 py-1.5 rounded-lg text-xs font-poppins whitespace-nowrap z-50 pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity"
                         style="background: #1a1a2e; color: white; border: 1px solid rgba(255,255,255,0.1);">
                        {{ item.label }}
                    </div>
                </Link>

                <!-- Item avec sous-menu -->
                <div v-else>
                    <button
                        @click="!collapsed && toggleGroup(item.label)"
                        class="sidebar-item group w-full flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 relative"
                        :class="isGroupActive(item) ? 'sidebar-item--active' : 'sidebar-item--default'"
                    >
                        <div v-if="isGroupActive(item)"
                             class="absolute left-0 top-1/2 -translate-y-1/2 w-0.5 h-6 rounded-full"
                             style="background: #c4956a;" />

                        <component :is="item.icon" :size="20"
                                   :class="isGroupActive(item) ? 'text-cognac-400' : 'text-white/40 group-hover:text-white/70'"
                                   class="shrink-0 transition-colors" />

                        <Transition name="fade-slide">
                            <span v-if="!collapsed"
                                  class="flex-1 text-sm font-poppins font-medium text-left whitespace-nowrap overflow-hidden transition-colors"
                                  :class="isGroupActive(item) ? 'text-white' : 'text-white/50 group-hover:text-white/80'">
                                {{ item.label }}
                            </span>
                        </Transition>

                        <Transition name="fade-slide">
                            <PhCaretDown v-if="!collapsed" :size="14"
                                         class="shrink-0 text-white/30 transition-transform duration-200"
                                         :class="openGroups[item.label] ? 'rotate-180' : ''" />
                        </Transition>

                        <!-- Tooltip collapsed -->
                        <div v-if="collapsed"
                             class="sidebar-tooltip absolute left-full ml-3 px-3 py-1.5 rounded-lg text-xs font-poppins whitespace-nowrap z-50 pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity"
                             style="background: #1a1a2e; color: white; border: 1px solid rgba(255,255,255,0.1);">
                            {{ item.label }}
                        </div>
                    </button>

                    <!-- Sous-menu -->
                    <Transition
                        @before-enter="beforeEnterSubmenu"
                        @enter="enterSubmenu"
                        @leave="leaveSubmenu"
                    >
                        <div v-if="openGroups[item.label] && !collapsed"
                             class="overflow-hidden mt-0.5 ml-3 pl-4 space-y-0.5"
                             style="border-left: 1px solid rgba(196,149,106,0.2);">
                            <Link
                                v-for="child in item.children"
                                :key="child.route"
                                :href="route(child.route)"
                                class="group flex items-center gap-3 px-3 py-2 rounded-lg transition-all duration-150"
                                :class="isActive(child.route)
                                    ? 'text-white bg-white/8'
                                    : 'text-white/40 hover:text-white/70 hover:bg-white/5'"
                            >
                                <component :is="child.icon" :size="15"
                                           :class="isActive(child.route) ? 'text-cognac-400' : 'text-white/30 group-hover:text-white/50'"
                                           class="shrink-0" />
                                <span class="text-xs font-poppins font-medium whitespace-nowrap">
                                    {{ child.label }}
                                </span>
                                <div v-if="isActive(child.route)"
                                     class="ml-auto w-1.5 h-1.5 rounded-full"
                                     style="background: #c4956a;" />
                            </Link>
                        </div>
                    </Transition>
                </div>

            </template>
        </nav>

        <!-- ── Séparateur ─────────────────────────────────── -->
        <div class="mx-4 h-px" style="background: rgba(255,255,255,0.06);" />

        <!-- ── Navigation bas ────────────────────────────── -->
        <div class="py-3 px-2 space-y-0.5">
            <Link
                v-for="item in bottomNav"
                :key="item.label"
                :href="route(item.route)"
                class="sidebar-item group flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 relative"
                :class="isActive(item.route) ? 'sidebar-item--active' : 'sidebar-item--default'"
            >
                <div class="relative shrink-0">
                    <component :is="item.icon" :size="20"
                               :class="isActive(item.route) ? 'text-cognac-400' : 'text-white/40 group-hover:text-white/70'"
                               class="transition-colors" />
                    <!-- Badge notifications -->
                    <div v-if="item.label === 'Notifications' && unreadCount > 0"
                         class="absolute -top-1.5 -right-1.5 w-4 h-4 rounded-full flex items-center justify-center text-white"
                         style="background: #e14d6e; font-size: 9px; font-weight: 700; font-family: 'Poppins', sans-serif;">
                        {{ unreadCount > 9 ? '9+' : unreadCount }}
                    </div>
                </div>

                <Transition name="fade-slide">
                    <span v-if="!collapsed"
                          class="text-sm font-poppins font-medium whitespace-nowrap transition-colors"
                          :class="isActive(item.route) ? 'text-white' : 'text-white/50 group-hover:text-white/80'">
                        {{ item.label }}
                    </span>
                </Transition>
            </Link>
        </div>

        <!-- ── Profil admin ───────────────────────────────── -->
        <div class="p-3 shrink-0" style="border-top: 1px solid rgba(255,255,255,0.06);">
            <div class="flex items-center gap-3 px-2 py-2 rounded-xl"
                 style="background: rgba(255,255,255,0.04);">
                <img :src="$page.props.auth.user?.avatar"
                     :alt="$page.props.auth.user?.name"
                     class="w-8 h-8 rounded-lg object-cover shrink-0" />

                <Transition name="fade-slide">
                    <div v-if="!collapsed" class="flex-1 min-w-0">
                        <p class="text-xs font-semibold text-white font-poppins truncate">
                            {{ $page.props.auth.user?.name }}
                        </p>
                        <p class="text-xs font-poppins truncate" style="color: #c4956a;">
                            Administratrice
                        </p>
                    </div>
                </Transition>

                <Transition name="fade-slide">
                    <Link v-if="!collapsed" :href="route('logout')" method="post" as="button"
                          class="text-white/30 hover:text-red-400 transition-colors shrink-0 p-1">
                        <PhSignOut :size="16" />
                    </Link>
                </Transition>
            </div>
        </div>

        <!-- ── Toggle collapse ───────────────────────────── -->
        <button
            @click="emit('toggle')"
            class="absolute -right-3 top-20 w-6 h-6 rounded-full flex items-center justify-center transition-all duration-200 hover:scale-110 z-10"
            style="background: #1a1a2e; border: 1px solid rgba(196,149,106,0.3);"
        >
            <PhCaretRight :size="12" class="text-cognac-400 transition-transform duration-200"
                          :class="collapsed ? '' : 'rotate-180'" />
        </button>
    </aside>
</template>

<style scoped>
.sidebar-item--active {
    background: rgba(196, 149, 106, 0.12);
}
.sidebar-item--default:hover {
    background: rgba(255, 255, 255, 0.05);
}
.sidebar-item--default {
    background: transparent;
}

/* Scrollbar sidebar */
nav::-webkit-scrollbar { width: 3px; }
nav::-webkit-scrollbar-track { background: transparent; }
nav::-webkit-scrollbar-thumb { background: rgba(196,149,106,0.3); border-radius: 999px; }

/* Transitions */
.fade-slide-enter-active { transition: all 0.2s ease; }
.fade-slide-leave-active { transition: all 0.15s ease; }
.fade-slide-enter-from   { opacity: 0; transform: translateX(-8px); }
.fade-slide-leave-to     { opacity: 0; transform: translateX(-8px); }
</style>