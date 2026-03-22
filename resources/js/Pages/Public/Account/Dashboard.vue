<script setup>
import { onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { gsap } from 'gsap'
import AccountLayout from '@/Layouts/AccountLayout.vue'
import {
    PhShoppingBag, PhCalendarCheck, PhReceipt, PhUser,
    PhStar, PhArrowRight, PhClock, PhCheckCircle,
    PhCurrencyEur, PhHeart,
} from '@phosphor-icons/vue'

// ← Layout Inertia persistant — pas de wrapper standalone dans le template
defineOptions({ layout: AccountLayout })

const props = defineProps({
    client:           Object,
    stats:            Object,
    recent_orders:    Array,
    next_appointment: Object,
})

function formatPrice(val) {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(val ?? 0)
}

const statusColors = {
    pending:    { bg: 'rgba(245,158,11,0.1)',  color: '#d97706' },
    processing: { bg: 'rgba(99,102,241,0.1)',  color: '#6366f1' },
    shipped:    { bg: 'rgba(59,130,246,0.1)',  color: '#2563eb' },
    delivered:  { bg: 'rgba(16,185,129,0.1)',  color: '#059669' },
    cancelled:  { bg: 'rgba(239,68,68,0.1)',   color: '#dc2626' },
}
function statusStyle(s) { return statusColors[s] ?? { bg: 'rgba(107,114,128,0.1)', color: '#6b7280' } }

onMounted(() => {
    gsap.fromTo('.dash-card',
        { opacity: 0, y: 22, scale: 0.98 },
        { opacity: 1, y: 0, scale: 1, duration: 0.45, stagger: 0.08, ease: 'power3.out' }
    )
})
</script>

<template>
    <Head title="Mon espace" />

    <!-- Pas de <div class="min-h-screen"> ici — le PublicLayout s'en charge -->
    <div class="max-w-5xl mx-auto px-4 py-10 space-y-6">

        <!-- ── Header ────────────────────────────────────────── -->
        <div class="dash-card flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-2xl overflow-hidden shadow-md shrink-0">
                    <img v-if="client?.avatar_url" :src="client.avatar_url" :alt="client?.full_name"
                         class="w-full h-full object-cover" />
                    <div v-else class="w-full h-full flex items-center justify-center text-white text-xl font-bold font-poppins"
                         style="background: linear-gradient(135deg, #c4956a, #d4af37);">
                        {{ client?.full_name?.split(' ').map(n => n[0]).join('').slice(0, 2) ?? 'C' }}
                    </div>
                </div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h1 class="font-cormorant text-3xl font-bold text-onyx-800">
                            Bonjour, {{ client?.first_name ?? 'chère cliente' }} !
                        </h1>
                        <span v-if="client?.is_vip"
                              class="flex items-center gap-1 px-2.5 py-1 rounded-xl text-xs font-poppins font-bold text-white"
                              style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                            <PhStar :size="11" weight="fill" /> VIP
                        </span>
                    </div>
                    <p class="text-sm font-poppins text-onyx-500 mt-0.5">
                        Cliente depuis {{ client?.member_since }} · {{ client?.email }}
                    </p>
                </div>
            </div>
            <Link :href="route('profile.show')"
                  class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-poppins font-medium text-onyx-600 border border-cream-200 bg-white hover:bg-cream-50 transition-colors shadow-card">
                <PhUser :size="16" />
                Mon profil
            </Link>
        </div>

        <!-- ── Stats ─────────────────────────────────────────── -->
        <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
            <div v-for="(stat, i) in [
                { label: 'Commandes',        value: stats.total_orders,        icon: PhShoppingBag,    color: '#c4956a', bg: 'rgba(196,149,106,0.1)' },
                { label: 'Dépenses totales', value: formatPrice(stats.total_spent), icon: PhCurrencyEur, color: '#10b981', bg: 'rgba(16,185,129,0.1)' },
                { label: 'Rendez-vous',      value: stats.total_appointments,  icon: PhCalendarCheck, color: '#6366f1', bg: 'rgba(99,102,241,0.1)'  },
                { label: 'Points fidélité',  value: stats.loyalty_points,      icon: PhHeart,         color: '#f59e0b', bg: 'rgba(245,158,11,0.1)'  },
            ]" :key="i"
                 class="dash-card bg-white rounded-2xl shadow-card p-5 group hover:shadow-float transition-all duration-300 hover:-translate-y-0.5">
                <div class="w-11 h-11 rounded-xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform"
                     :style="{ background: stat.bg }">
                    <component :is="stat.icon" :size="20" :style="{ color: stat.color }" />
                </div>
                <p class="text-2xl font-bold font-poppins text-onyx-800">{{ stat.value }}</p>
                <p class="text-xs font-poppins text-onyx-400 mt-0.5">{{ stat.label }}</p>
            </div>
        </div>

        <!-- ── Prochain RDV + Commandes ───────────────────────── -->
        <div class="grid grid-cols-1 xl:grid-cols-5 gap-6">

            <!-- Prochain RDV -->
            <div class="xl:col-span-2 dash-card">
                <div v-if="next_appointment"
                     class="rounded-2xl overflow-hidden shadow-card h-full"
                     style="background: linear-gradient(135deg, #1a1a2e, #2d2d4e);">
                    <div class="p-5">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-8 h-8 rounded-xl flex items-center justify-center"
                                 style="background: rgba(196,149,106,0.2);">
                                <PhCalendarCheck :size="16" class="text-cognac-400" />
                            </div>
                            <p class="text-xs font-poppins font-semibold text-white/60 uppercase tracking-widest">
                                Prochain rendez-vous
                            </p>
                        </div>
                        <h3 class="font-cormorant text-2xl font-bold text-white mb-1">
                            {{ next_appointment.service }}
                        </h3>
                        <p class="text-sm font-poppins text-white/70 mb-1 flex items-center gap-2">
                            <PhCalendarCheck :size="14" />{{ next_appointment.date }}
                        </p>
                        <p class="text-sm font-poppins text-white/70 mb-4 flex items-center gap-2">
                            <PhClock :size="14" />{{ next_appointment.start_time }}
                        </p>
                        <div class="flex items-center justify-between">
                            <span class="px-3 py-1.5 rounded-xl text-xs font-poppins font-bold"
                                  :style="next_appointment.status === 'confirmed'
                                      ? 'background: rgba(16,185,129,0.15); color: #34d399'
                                      : 'background: rgba(245,158,11,0.15); color: #fbbf24'">
                                {{ next_appointment.status === 'confirmed' ? '✓ Confirmé' : 'En attente' }}
                            </span>
                            <p class="text-base font-bold font-poppins text-white">{{ formatPrice(next_appointment.price) }}</p>
                        </div>
                        <div v-if="next_appointment.deposit_amount > 0 && !next_appointment.deposit_paid"
                             class="mt-3 px-3 py-2 rounded-xl text-xs font-poppins flex items-center gap-2"
                             style="background: rgba(245,158,11,0.15); color: #fbbf24;">
                            <PhCurrencyEur :size="13" />
                            Acompte de {{ formatPrice(next_appointment.deposit_amount) }} à régler
                        </div>
                    </div>
                </div>
                <div v-else class="bg-white rounded-2xl shadow-card p-5 h-full flex flex-col items-center justify-center text-center min-h-48">
                    <div class="w-16 h-16 rounded-2xl flex items-center justify-center mb-4"
                         style="background: rgba(196,149,106,0.08);">
                        <PhCalendarCheck :size="32" class="text-onyx-300" />
                    </div>
                    <p class="font-poppins font-semibold text-onyx-600 mb-1">Aucun rendez-vous à venir</p>
                    <p class="text-xs font-poppins text-onyx-400 mb-4">Réservez votre prochaine prestation</p>
                    <Link :href="route('booking.services')"
                          class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-poppins font-semibold text-white"
                          style="background: linear-gradient(135deg, #c4956a, #b07d52);">
                        Réserver <PhArrowRight :size="15" weight="bold" />
                    </Link>
                </div>
            </div>

            <!-- Commandes récentes -->
            <div class="xl:col-span-3 dash-card bg-white rounded-2xl shadow-card overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-cream-100">
                    <div class="flex items-center gap-2">
                        <PhShoppingBag :size="16" class="text-cognac-500" />
                        <h2 class="font-poppins font-semibold text-onyx-800 text-sm">Commandes récentes</h2>
                    </div>
                    <Link :href="route('account.orders')"
                          class="text-xs font-poppins text-cognac-600 hover:text-cognac-800 transition-colors font-medium flex items-center gap-1">
                        Voir tout <PhArrowRight :size="13" />
                    </Link>
                </div>
                <div v-if="recent_orders.length" class="divide-y divide-cream-50">
                    <div v-for="order in recent_orders" :key="order.id"
                         class="flex items-center gap-4 px-5 py-4 hover:bg-cream-50/50 transition-colors">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                             style="background: rgba(196,149,106,0.1);">
                            <PhShoppingBag :size="18" class="text-cognac-400" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-poppins font-semibold text-onyx-800">{{ order.order_number }}</p>
                            <p class="text-xs font-poppins text-onyx-400">{{ order.date }} · {{ order.items_count }} article(s)</p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-sm font-bold font-poppins text-onyx-800">{{ formatPrice(order.total) }}</p>
                            <span class="text-xs font-poppins font-semibold px-2 py-0.5 rounded-lg"
                                  :style="{ background: statusStyle(order.status).bg, color: statusStyle(order.status).color }">
                                {{ order.status_label }}
                            </span>
                        </div>
                    </div>
                </div>
                <div v-else class="flex flex-col items-center justify-center py-10">
                    <p class="text-sm font-poppins text-onyx-400 mb-3">Aucune commande</p>
                    <Link :href="route('shop.index')"
                          class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-poppins font-semibold text-white"
                          style="background: linear-gradient(135deg, #c4956a, #b07d52);">
                        Voir la boutique <PhArrowRight :size="15" weight="bold" />
                    </Link>
                </div>
            </div>
        </div>

        <!-- ── Navigation rapide ──────────────────────────────── -->
        <div class="dash-card grid grid-cols-2 md:grid-cols-4 gap-4">
            <Link v-for="(nav, i) in [
                { label: 'Mes commandes',  desc: 'Suivre mes achats',    route: 'account.orders',       icon: PhShoppingBag,   color: '#c4956a' },
                { label: 'Mes RDV',        desc: 'Mes réservations',     route: 'account.appointments', icon: PhCalendarCheck, color: '#6366f1' },
                { label: 'Mes factures',   desc: 'Télécharger les PDF',  route: 'account.invoices',     icon: PhReceipt,       color: '#10b981' },
                { label: 'Mon profil',     desc: 'Modifier mes infos',   route: 'profile.show',         icon: PhUser,          color: '#f59e0b' },
            ]" :key="i"
                 :href="route(nav.route)"
                 class="bg-white rounded-2xl shadow-card p-4 flex items-center gap-3 hover:shadow-float transition-all duration-300 hover:-translate-y-0.5 group">
                <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform"
                     :style="{ background: `${nav.color}15` }">
                    <component :is="nav.icon" :size="20" :style="{ color: nav.color }" />
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-poppins font-semibold text-onyx-800 truncate">{{ nav.label }}</p>
                    <p class="text-xs font-poppins text-onyx-400 truncate">{{ nav.desc }}</p>
                </div>
            </Link>
        </div>

    </div>
</template>