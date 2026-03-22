<script setup>
import { computed } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'
import PublicLayout from '@/Layouts/PublicLayout.vue'
import {
    PhHouse, PhShoppingBag, PhCalendarCheck, PhReceipt,
    PhUser, PhCaretRight, PhStar, PhSignOut,
    PhScissors, PhArrowSquareOut,
} from '@phosphor-icons/vue'

defineOptions({ layout: PublicLayout })

const page   = usePage()
const user   = computed(() => page.props.auth?.user)
const client = computed(() => page.props.auth?.client ?? null)

const initials = computed(() => {
    const name = user.value?.name ?? ''
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
})

// Navigation espace client
const navItems = [
    { label: 'Tableau de bord',  route: 'account.dashboard',    icon: PhHouse         },
    { label: 'Mes commandes',    route: 'account.orders',        icon: PhShoppingBag   },
    { label: 'Mes rendez-vous',  route: 'account.appointments',  icon: PhCalendarCheck },
    { label: 'Mes factures',     route: 'account.invoices',      icon: PhReceipt       },
    { label: 'Mon profil',       route: 'profile.show',          icon: PhUser          },
]

// Liens vers la vitrine publique
const vitrineItems = [
    { label: 'Accueil',     route: 'home',             icon: PhHouse         },
    { label: 'Prendre RDV', route: 'booking.services', icon: PhScissors      },
    { label: 'Boutique',    route: 'shop.index',        icon: PhShoppingBag   },
]

function isActive(routeName) {
    try { return route().current(routeName) } catch { return false }
}

function logout() {
    router.post(route('logout'))
}
</script>

<template>
    <div class="max-w-6xl mx-auto px-4 py-10">
        <div class="flex gap-7 items-start">

            <!-- ── Sidebar desktop ──────────────────────────── -->
            <aside class="hidden lg:block w-64 shrink-0 sticky top-[90px]">

                <!-- Carte profil -->
                <div class="bg-white rounded-2xl shadow-card p-5 mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl overflow-hidden shrink-0">
                            <img v-if="user?.avatar" :src="user.avatar" :alt="user?.name"
                                 class="w-full h-full object-cover" />
                            <div v-else
                                 class="w-full h-full flex items-center justify-center text-white text-sm font-bold font-poppins"
                                 style="background: linear-gradient(135deg, #c4956a, #d4af37);">
                                {{ initials }}
                            </div>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-poppins font-bold text-onyx-800 truncate">{{ user?.name }}</p>
                            <div class="flex items-center gap-1.5 mt-0.5">
                                <span v-if="client?.is_vip"
                                      class="inline-flex items-center gap-1 text-xs font-poppins font-bold px-2 py-0.5 rounded-lg text-white"
                                      style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                                    <PhStar :size="10" weight="fill" /> VIP
                                </span>
                                <span v-else class="text-xs font-poppins text-onyx-400">Cliente</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Navigation espace client -->
                <nav class="bg-white rounded-2xl shadow-card overflow-hidden mb-3">

                    <!-- Section Mon espace -->
                    <div class="px-3.5 pt-3 pb-1">
                        <p class="text-xs font-poppins font-semibold uppercase tracking-widest"
                           style="color: rgba(196,149,106,0.7); font-size: 9px;">
                            Mon espace
                        </p>
                    </div>

                    <div class="px-2 pb-2 space-y-0.5">
                        <Link v-for="item in navItems" :key="item.route"
                              :href="route(item.route)"
                              class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all duration-200 group"
                              :class="isActive(item.route)
                                  ? 'text-cognac-700 font-semibold'
                                  : 'text-onyx-500 hover:text-onyx-800 hover:bg-cream-50'"
                              :style="isActive(item.route) ? 'background: rgba(196,149,106,0.1);' : ''">
                            <component :is="item.icon" :size="17"
                                       :class="isActive(item.route)
                                           ? 'text-cognac-500'
                                           : 'text-onyx-400 group-hover:text-cognac-400'"
                                       class="shrink-0 transition-colors" />
                            <span class="text-sm font-poppins flex-1">{{ item.label }}</span>
                            <PhCaretRight v-if="isActive(item.route)"
                                          :size="13" class="text-cognac-400 shrink-0" />
                        </Link>
                    </div>

                    <!-- Section Vitrine -->
                    <div class="px-3.5 pt-2 pb-1" style="border-top: 1px solid #f5ede4;">
                        <p class="text-xs font-poppins font-semibold uppercase tracking-widest"
                           style="color: rgba(107,114,128,0.6); font-size: 9px;">
                            Vitrine
                        </p>
                    </div>

                    <div class="px-2 pb-2 space-y-0.5">
                        <Link v-for="item in vitrineItems" :key="item.route"
                              :href="route(item.route)"
                              class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all duration-200 group text-onyx-500 hover:text-onyx-800 hover:bg-cream-50">
                            <component :is="item.icon" :size="17"
                                       class="text-onyx-300 group-hover:text-cognac-400 shrink-0 transition-colors" />
                            <span class="text-sm font-poppins flex-1">{{ item.label }}</span>
                            <PhArrowSquareOut :size="12" class="text-onyx-200 group-hover:text-onyx-400 shrink-0" />
                        </Link>
                    </div>

                    <!-- Déconnexion -->
                    <div class="px-2 pb-2" style="border-top: 1px solid #f5ede4;">
                        <button @click="logout"
                                class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all group text-red-400 hover:text-red-600 hover:bg-red-50 mt-1">
                            <PhSignOut :size="17" class="shrink-0 transition-colors" />
                            <span class="text-sm font-poppins">Déconnexion</span>
                        </button>
                    </div>
                </nav>
            </aside>

            <!-- ── Contenu principal ──────────────────────────── -->
            <main class="flex-1 min-w-0">

                <!-- Nav mobile (tabs scrollables) -->
                <div class="lg:hidden mb-5 -mx-4 px-4">
                    <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-hide">

                        <!-- Tabs espace client -->
                        <Link v-for="item in navItems" :key="item.route"
                              :href="route(item.route)"
                              class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-sm font-poppins font-medium whitespace-nowrap transition-all shrink-0"
                              :class="isActive(item.route)
                                  ? 'text-white shadow-sm'
                                  : 'text-onyx-600 bg-white border border-cream-200 hover:border-cognac-300'"
                              :style="isActive(item.route)
                                  ? 'background: linear-gradient(135deg, #c4956a, #b07d52);'
                                  : ''">
                            <component :is="item.icon" :size="15" />
                            {{ item.label }}
                        </Link>

                        <!-- Séparateur visuel -->
                        <div class="w-px h-7 bg-cream-200 shrink-0 mx-1" />

                        <!-- Liens vitrine en mobile -->
                        <Link v-for="item in vitrineItems" :key="'v-' + item.route"
                              :href="route(item.route)"
                              class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-sm font-poppins font-medium whitespace-nowrap transition-all shrink-0 text-onyx-400 bg-white border border-cream-200 hover:border-onyx-300 hover:text-onyx-700">
                            <component :is="item.icon" :size="15" />
                            {{ item.label }}
                        </Link>
                    </div>
                </div>

                <!-- Slot pages account -->
                <slot />
            </main>
        </div>
    </div>
</template>

<style scoped>
.scrollbar-hide::-webkit-scrollbar { display: none; }
.scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
</style>