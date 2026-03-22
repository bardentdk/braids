<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'
import { gsap } from 'gsap'
import { ScrollTrigger } from 'gsap/ScrollTrigger'
import {
    PhBridge, PhList, PhX, PhShoppingBag, PhUser,
    PhCalendarCheck, PhInstagramLogo, PhTiktokLogo,
    PhArrowRight, PhPhone, PhEnvelope, PhMapPin,
    PhHeart, PhCaretDown,
} from '@phosphor-icons/vue'

gsap.registerPlugin(ScrollTrigger)

const page    = usePage()
const user    = computed(() => page.props.auth.user)
const flash   = computed(() => page.props.flash)
const settings = computed(() => page.props.app ?? {})

const mobileOpen    = ref(false)
const scrolled      = ref(false)
const cartCount     = ref(0)
const flashVisible  = ref(false)
const flashMsg      = ref({ type: '', message: '' })

const navLinks = [
    { label: 'Accueil',   href: 'home' },
    { label: 'Services',  href: 'booking.services' },
    { label: 'Boutique',  href: 'shop.index' },
    { label: 'Galerie',   href: 'gallery.index' },
    { label: 'Contact',   href: 'contact.show' },
]

// Scroll header
function handleScroll() {
    scrolled.value = window.scrollY > 60
}
onMounted(() => {
    window.addEventListener('scroll', handleScroll, { passive: true })

    // Animation header
    gsap.fromTo('.nav-link',
        { opacity: 0, y: -10 },
        { opacity: 1, y: 0, duration: 0.4, stagger: 0.07, ease: 'power2.out', delay: 0.3 }
    )
})
onUnmounted(() => window.removeEventListener('scroll', handleScroll))

// Flash messages
watch(() => flash.value, (val) => {
    const type = ['success', 'error', 'warning', 'info'].find(t => val?.[t])
    if (type) {
        flashMsg.value   = { type, message: val[type] }
        flashVisible.value = true
        setTimeout(() => { flashVisible.value = false }, 5000)
    }
}, { deep: true, immediate: true })

function closeMobile() { mobileOpen.value = false }
function isActive(routeName) {
    try { return route().current(routeName) || route().current(routeName + '*') }
    catch { return false }
}
</script>

<template>
    <div class="min-h-screen flex flex-col font-poppins" style="background: #faf7f4;">

        <!-- ── Flash message global ────────────────────── -->
        <Transition name="flash-pub">
            <div v-if="flashVisible"
                 class="fixed top-4 left-1/2 -translate-x-1/2 z-[200] w-full max-w-md px-4">
                <div class="rounded-2xl shadow-luxury px-5 py-4 flex items-center gap-3"
                     :class="{
                         'bg-emerald-600 text-white': flashMsg.type === 'success',
                         'bg-red-500 text-white':     flashMsg.type === 'error',
                         'bg-amber-500 text-white':   flashMsg.type === 'warning',
                         'bg-blue-500 text-white':    flashMsg.type === 'info',
                     }">
                    <p class="flex-1 text-sm font-medium">{{ flashMsg.message }}</p>
                    <button @click="flashVisible = false" class="opacity-70 hover:opacity-100">
                        <PhX :size="16" />
                    </button>
                </div>
            </div>
        </Transition>

        <!-- ── Navbar ───────────────────────────────────── -->
        <nav
            class="fixed top-0 left-0 right-0 z-50 transition-all duration-500"
            :class="scrolled
                ? 'py-3 shadow-luxury'
                : 'py-5'"
            :style="scrolled
                ? 'background: rgba(250,247,244,0.97); backdrop-filter: blur(20px);'
                : 'background: transparent;'"
        >
            <div class="container-brand flex items-center justify-between">

                <!-- Logo -->
                <Link :href="route('home')" class="flex items-center gap-3 group">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center transition-transform duration-300 group-hover:scale-110"
                         style="background: linear-gradient(135deg, #c4956a, #d4af37);">
                        <PhBridge :size="22" weight="bold" class="text-white" />
                    </div>
                    <div>
                        <p class="font-cormorant text-xl font-bold leading-none"
                           :class="scrolled ? 'text-onyx-800' : 'text-onyx-800'">
                            Patricia Braids
                        </p>
                        <p class="text-xs tracking-[0.2em] uppercase font-poppins"
                           style="color: #c4956a; line-height: 1.2; font-size: 9px;">
                            Studio Premium
                        </p>
                    </div>
                </Link>

                <!-- Navigation desktop -->
                <div class="hidden lg:flex items-center gap-1">
                    <Link
                        v-for="link in navLinks"
                        :key="link.href"
                        :href="route(link.href)"
                        class="nav-link px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200 relative group"
                        :class="isActive(link.href)
                            ? 'text-cognac-600 bg-cognac-50'
                            : 'text-onyx-600 hover:text-onyx-900 hover:bg-cream-200'"
                    >
                        {{ link.label }}
                        <span v-if="isActive(link.href)"
                              class="absolute bottom-1 left-1/2 -translate-x-1/2 w-1 h-1 rounded-full"
                              style="background: #c4956a;" />
                    </Link>
                </div>

                <!-- Actions desktop -->
                <div class="hidden lg:flex items-center gap-3">

                    <!-- Réserver -->
                    <Link :href="route('booking.services')" class="btn-primary py-2.5 text-sm">
                        <PhCalendarCheck :size="16" weight="bold" />
                        Prendre RDV
                    </Link>

                    <!-- Panier -->
                    <Link :href="route('cart.index')"
                          class="relative w-10 h-10 rounded-xl flex items-center justify-center transition-all hover:bg-cream-200">
                        <PhShoppingBag :size="20" class="text-onyx-600" />
                        <span v-if="cartCount > 0"
                              class="absolute -top-1 -right-1 w-5 h-5 rounded-full flex items-center justify-center text-white text-xs font-bold"
                              style="background: #e14d6e; font-size: 10px;">
                            {{ cartCount }}
                        </span>
                    </Link>

                    <!-- Auth -->
                    <Link v-if="user" :href="route('profile.show')"
                          class="w-10 h-10 rounded-xl overflow-hidden border-2 transition-all hover:scale-105"
                          style="border-color: rgba(196,149,106,0.4);">
                        <img :src="user.avatar" :alt="user.name" class="w-full h-full object-cover" />
                    </Link>
                    <Link v-else :href="route('login')"
                          class="btn-ghost py-2.5 text-sm border border-cream-300 hover:border-cognac-300 rounded-xl">
                        <PhUser :size="16" />
                        Connexion
                    </Link>
                </div>

                <!-- Burger mobile -->
                <div class="lg:hidden flex items-center gap-3">
                    <Link :href="route('cart.index')" class="relative w-10 h-10 rounded-xl flex items-center justify-center hover:bg-cream-200">
                        <PhShoppingBag :size="20" class="text-onyx-600" />
                        <span v-if="cartCount > 0"
                              class="absolute -top-1 -right-1 w-4 h-4 rounded-full flex items-center justify-center text-white text-xs font-bold"
                              style="background: #e14d6e; font-size: 9px;">
                            {{ cartCount }}
                        </span>
                    </Link>
                    <button @click="mobileOpen = !mobileOpen"
                            class="w-10 h-10 rounded-xl flex items-center justify-center hover:bg-cream-200 transition-colors">
                        <PhList v-if="!mobileOpen" :size="22" class="text-onyx-700" />
                        <PhX v-else :size="22" class="text-onyx-700" />
                    </button>
                </div>
            </div>
        </nav>

        <!-- ── Menu mobile ───────────────────────────────── -->
        <Transition name="mobile-menu">
            <div v-if="mobileOpen"
                 class="fixed inset-0 z-40 lg:hidden"
                 style="background: rgba(13,13,26,0.5); backdrop-filter: blur(4px);"
                 @click="closeMobile">
                <div class="absolute right-0 top-0 bottom-0 w-80 shadow-luxury flex flex-col"
                     style="background: #0d0d1a;"
                     @click.stop>

                    <!-- Header -->
                    <div class="flex items-center justify-between p-6"
                         style="border-bottom: 1px solid rgba(255,255,255,0.07);">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center"
                                 style="background: linear-gradient(135deg, #c4956a, #d4af37);">
                                <PhBridge :size="18" weight="bold" class="text-white" />
                            </div>
                            <span class="font-cormorant text-lg font-bold text-white">Patricia Braids</span>
                        </div>
                        <button @click="closeMobile" class="text-white/50 hover:text-white transition-colors">
                            <PhX :size="20" />
                        </button>
                    </div>

                    <!-- Links -->
                    <nav class="flex-1 p-6 space-y-1">
                        <Link v-for="link in navLinks" :key="link.href"
                              :href="route(link.href)"
                              @click="closeMobile"
                              class="flex items-center justify-between px-4 py-3 rounded-xl transition-all font-medium"
                              :class="isActive(link.href)
                                  ? 'text-white bg-white/10'
                                  : 'text-white/60 hover:text-white hover:bg-white/5'">
                            <span class="text-sm font-poppins">{{ link.label }}</span>
                            <PhArrowRight :size="14" class="opacity-50" />
                        </Link>
                    </nav>

                    <!-- CTA -->
                    <div class="p-6 space-y-3" style="border-top: 1px solid rgba(255,255,255,0.07);">
                        <Link :href="route('booking.services')"
                              @click="closeMobile"
                              class="btn-primary w-full justify-center py-3 text-sm">
                            <PhCalendarCheck :size="16" />
                            Prendre rendez-vous
                        </Link>
                        <Link v-if="!user" :href="route('login')"
                              @click="closeMobile"
                              class="flex items-center justify-center gap-2 w-full py-3 text-sm text-white/50 hover:text-white transition-colors font-poppins">
                            <PhUser :size="16" />
                            Se connecter
                        </Link>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- ── Contenu principal ────────────────────────── -->
        <main class="flex-1 pt-[72px]">
            <slot />
        </main>

        <!-- ── Footer ────────────────────────────────────── -->
        <footer style="background: #0d0d1a;">

            <!-- Footer principal -->
            <div class="container-brand py-16">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12">

                    <!-- Brand -->
                    <div class="lg:col-span-2 space-y-6">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-2xl flex items-center justify-center"
                                 style="background: linear-gradient(135deg, #c4956a, #d4af37);">
                                <PhBridge :size="26" weight="bold" class="text-white" />
                            </div>
                            <div>
                                <p class="font-cormorant text-2xl font-bold text-white">Patricia Braids</p>
                                <p class="text-xs tracking-[0.2em] uppercase font-poppins" style="color: #c4956a;">Studio Premium</p>
                            </div>
                        </div>

                        <p class="text-sm text-white/50 font-poppins leading-relaxed max-w-sm">
                            Studio de tresses premium dédié à sublimer votre beauté naturelle. Chaque création est une oeuvre unique, faite avec passion et expertise.
                        </p>

                        <!-- Réseaux -->
                        <div class="flex items-center gap-3">
                            <a href="#" target="_blank"
                               class="w-10 h-10 rounded-xl flex items-center justify-center transition-all hover:scale-110"
                               style="background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.1);">
                                <PhInstagramLogo :size="18" class="text-white/60" />
                            </a>
                            <a href="#" target="_blank"
                               class="w-10 h-10 rounded-xl flex items-center justify-center transition-all hover:scale-110"
                               style="background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.1);">
                                <PhTiktokLogo :size="18" class="text-white/60" />
                            </a>
                        </div>
                    </div>

                    <!-- Navigation -->
                    <div class="space-y-4">
                        <h4 class="text-xs font-semibold uppercase tracking-[0.2em] font-poppins" style="color: #c4956a;">Navigation</h4>
                        <div class="space-y-3">
                            <Link v-for="link in navLinks" :key="link.href"
                                  :href="route(link.href)"
                                  class="block text-sm text-white/50 hover:text-white transition-colors font-poppins">
                                {{ link.label }}
                            </Link>
                        </div>
                    </div>

                    <!-- Contact -->
                    <div class="space-y-4">
                        <h4 class="text-xs font-semibold uppercase tracking-[0.2em] font-poppins" style="color: #c4956a;">Contact</h4>
                        <div class="space-y-3">
                            <a href="tel:+33612345678"
                               class="flex items-center gap-3 text-sm text-white/50 hover:text-white transition-colors font-poppins">
                                <PhPhone :size="16" class="shrink-0 text-cognac-400" />
                                +33 6 12 34 56 78
                            </a>
                            <a href="mailto:contact@patricia-braids.com"
                               class="flex items-center gap-3 text-sm text-white/50 hover:text-white transition-colors font-poppins">
                                <PhEnvelope :size="16" class="shrink-0 text-cognac-400" />
                                contact@patricia-braids.com
                            </a>
                            <div class="flex items-start gap-3 text-sm text-white/50 font-poppins">
                                <PhMapPin :size="16" class="shrink-0 mt-0.5 text-cognac-400" />
                                Paris, France
                            </div>
                        </div>

                        <Link :href="route('booking.services')"
                              class="inline-flex items-center gap-2 mt-4 text-sm font-semibold transition-colors font-poppins"
                              style="color: #c4956a;">
                            <PhCalendarCheck :size="16" />
                            Prendre rendez-vous
                            <PhArrowRight :size="14" />
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Footer bottom -->
            <div style="border-top: 1px solid rgba(255,255,255,0.06);">
                <div class="container-brand py-5 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <p class="text-xs text-white/30 font-poppins">
                        &copy; {{ new Date().getFullYear() }} Patricia Braids — Tous droits réservés
                    </p>
                    <div class="flex items-center gap-4">
                        <a href="#" class="text-xs text-white/30 hover:text-white/60 font-poppins transition-colors">Mentions légales</a>
                        <a href="#" class="text-xs text-white/30 hover:text-white/60 font-poppins transition-colors">CGV</a>
                        <a href="#" class="text-xs text-white/30 hover:text-white/60 font-poppins transition-colors">Confidentialité</a>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
/* Flash */
.flash-pub-enter-active { transition: all 0.4s cubic-bezier(0.34,1.56,0.64,1); }
.flash-pub-leave-active { transition: all 0.25s ease; }
.flash-pub-enter-from   { opacity: 0; transform: translateX(-50%) translateY(-20px) scale(0.9); }
.flash-pub-leave-to     { opacity: 0; transform: translateX(-50%) translateY(-10px); }

/* Mobile menu */
.mobile-menu-enter-active { transition: all 0.35s cubic-bezier(0.25,0.46,0.45,0.94); }
.mobile-menu-leave-active { transition: all 0.25s ease; }
.mobile-menu-enter-from   { opacity: 0; }
.mobile-menu-leave-to     { opacity: 0; }
.mobile-menu-enter-from .absolute { transform: translateX(100%); }
.mobile-menu-leave-to .absolute   { transform: translateX(100%); }
</style>