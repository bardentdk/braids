<script setup>
import { ref, onMounted } from 'vue'
import { Head } from '@inertiajs/vue3'
import { gsap } from 'gsap'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import {
    PhCurrencyEur, PhCalendarCheck, PhUserPlus, PhShoppingBag,
    PhArrowUp, PhArrowDown, PhClock, PhWarning,
} from '@phosphor-icons/vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
    kpis:                 Object,
    stats:                Object,
    todayAppointments:    Array,
    upcomingAppointments: Array,
    recentOrders:         Array,
    overdueInvoices:      Array,
    lowStockProducts:     Array,
    revenueChart:         Array,
    revenueBySource:      Object,
    pendingReviews:       Array,
    currentMonth:         String,
})

const kpiIcons = {
    revenue:      PhCurrencyEur,
    appointments: PhCalendarCheck,
    new_clients:  PhUserPlus,
    orders:       PhShoppingBag,
}

onMounted(() => {
    gsap.fromTo('.kpi-card',
        { opacity: 0, y: 24, scale: 0.97 },
        { opacity: 1, y: 0, scale: 1, duration: 0.5, stagger: 0.1, ease: 'power3.out' }
    )
    gsap.fromTo('.dashboard-section',
        { opacity: 0, y: 20 },
        { opacity: 1, y: 0, duration: 0.5, stagger: 0.08, ease: 'power2.out', delay: 0.4 }
    )
})

function formatCurrency(val) {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(val ?? 0)
}
function formatNumber(val) {
    return new Intl.NumberFormat('fr-FR').format(val ?? 0)
}
</script>

<template>
    <Head title="Dashboard" />

    <!-- ✅ PAS de <AdminLayout> ici — Inertia l'injecte via defineOptions -->
    <div class="p-6 space-y-6">

        <!-- ── Header page ──────────────────────────────── -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="font-cormorant text-3xl font-bold text-onyx-800">
                    Bonjour, Patricia
                </h1>
                <p class="text-sm font-poppins text-onyx-500 mt-0.5">
                    {{ currentMonth }} — Vue d'ensemble de votre activité
                </p>
            </div>
        </div>

        <!-- ── KPI Cards ────────────────────────────────── -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            <div
                v-for="(kpi, key) in kpis"
                :key="key"
                class="kpi-card bg-white rounded-2xl p-5 shadow-card hover:shadow-float transition-all duration-300 hover:-translate-y-0.5 group"
            >
                <div class="flex items-start justify-between mb-4">
                    <div class="w-11 h-11 rounded-xl flex items-center justify-center transition-all duration-300 group-hover:scale-110"
                         style="background: linear-gradient(135deg, rgba(196,149,106,0.15), rgba(212,175,55,0.1));">
                        <component :is="kpiIcons[key]" :size="22" class="text-cognac-500" />
                    </div>
                    <div class="flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-semibold font-poppins"
                         :class="kpi.evolution >= 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-600'">
                        <PhArrowUp v-if="kpi.evolution >= 0" :size="12" />
                        <PhArrowDown v-else :size="12" />
                        {{ Math.abs(kpi.evolution) }}%
                    </div>
                </div>
                <div>
                    <p class="text-2xl font-bold text-onyx-800 font-poppins">
                        {{ kpi.suffix === '€' ? formatCurrency(kpi.current) : formatNumber(kpi.current) }}
                    </p>
                    <p class="text-xs font-poppins text-onyx-400 mt-1 font-medium tracking-wide">
                        {{ kpi.label }}
                    </p>
                </div>
                <div class="mt-4 h-1 rounded-full overflow-hidden" style="background: #f5ede4;">
                    <div class="h-full rounded-full transition-all duration-1000"
                         style="background: linear-gradient(90deg, #c4956a, #d4af37);"
                         :style="{ width: Math.min(100, (kpi.current / (kpi.previous || 1)) * 100) + '%' }" />
                </div>
                <p class="mt-1.5 text-xs text-onyx-400 font-poppins">
                    vs {{ kpi.suffix === '€' ? formatCurrency(kpi.previous) : formatNumber(kpi.previous) }} le mois dernier
                </p>
            </div>
        </div>

        <!-- ── Corps principal ──────────────────────────── -->
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

            <!-- Agenda du jour -->
            <div class="dashboard-section xl:col-span-2 bg-white rounded-2xl shadow-card overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4" style="border-bottom: 1px solid #f5ede4;">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: rgba(196,149,106,0.1);">
                            <PhCalendarCheck :size="17" class="text-cognac-500" />
                        </div>
                        <h3 class="font-poppins font-semibold text-onyx-800">Agenda du jour</h3>
                        <span class="badge badge-brand text-xs">{{ todayAppointments.length }} RDV</span>
                    </div>
                    <a :href="route('admin.rendez-vous.calendar')"
                       class="text-xs font-poppins text-cognac-600 hover:text-cognac-800 font-medium transition-colors">
                        Voir le calendrier
                    </a>
                </div>
                <div class="divide-y divide-cream-100">
                    <div v-if="!todayAppointments.length"
                         class="flex flex-col items-center justify-center py-12 text-center">
                        <div class="w-12 h-12 rounded-2xl flex items-center justify-center mb-3" style="background: #f5ede4;">
                            <PhCalendarCheck :size="22" class="text-onyx-300" />
                        </div>
                        <p class="text-sm font-poppins text-onyx-400">Aucun rendez-vous aujourd'hui</p>
                    </div>
                </div>
            </div>

            <!-- Colonne droite -->
            <div class="space-y-5">

                <!-- Factures en retard -->
                <div v-if="overdueInvoices.length" class="dashboard-section bg-white rounded-2xl shadow-card overflow-hidden">
                    <div class="flex items-center gap-3 px-5 py-4" style="border-bottom: 1px solid #f5ede4;">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-red-50">
                            <PhWarning :size="17" class="text-red-500" />
                        </div>
                        <h3 class="font-poppins font-semibold text-onyx-800 text-sm">Factures en retard</h3>
                        <span class="badge badge-error text-xs">{{ overdueInvoices.length }}</span>
                    </div>
                    <div class="p-3 space-y-2">
                        <a v-for="inv in overdueInvoices.slice(0,4)" :key="inv.id"
                           :href="route('admin.factures.show', inv.id)"
                           class="flex items-center justify-between p-3 rounded-xl hover:bg-cream-50 transition-colors">
                            <div>
                                <p class="text-xs font-semibold font-poppins text-onyx-700">{{ inv.client.name }}</p>
                                <p class="text-xs text-onyx-400 font-poppins">{{ inv.invoice_number }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs font-bold text-red-600 font-poppins">{{ formatCurrency(inv.amount_due) }}</p>
                                <p class="text-xs text-red-400 font-poppins">{{ inv.days_overdue }}j de retard</p>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Stocks faibles -->
                <div v-if="lowStockProducts.length" class="dashboard-section bg-white rounded-2xl shadow-card overflow-hidden">
                    <div class="flex items-center gap-3 px-5 py-4" style="border-bottom: 1px solid #f5ede4;">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-amber-50">
                            <PhWarning :size="17" class="text-amber-500" />
                        </div>
                        <h3 class="font-poppins font-semibold text-onyx-800 text-sm">Stocks faibles</h3>
                        <span class="badge badge-warning text-xs">{{ lowStockProducts.length }}</span>
                    </div>
                    <div class="p-3 space-y-2">
                        <a v-for="product in lowStockProducts" :key="product.id"
                           :href="route('admin.produits.show', product.id)"
                           class="flex items-center gap-3 p-3 rounded-xl hover:bg-cream-50 transition-colors">
                            <img :src="product.thumbnail" class="w-9 h-9 rounded-lg object-cover shrink-0" />
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-semibold font-poppins text-onyx-700 truncate">{{ product.name }}</p>
                                <div class="mt-1 flex items-center gap-2">
                                    <div class="flex-1 h-1 rounded-full bg-cream-200">
                                        <div class="h-full rounded-full bg-amber-400"
                                             :style="{ width: (product.stock / product.threshold * 100) + '%' }" />
                                    </div>
                                    <span class="text-xs font-bold font-poppins text-amber-600">{{ product.stock }}</span>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Stats globales -->
                <div class="dashboard-section bg-white rounded-2xl shadow-card p-5">
                    <h3 class="font-poppins font-semibold text-onyx-800 text-sm mb-4">Vue globale</h3>
                    <div class="grid grid-cols-2 gap-3">
                        <div v-for="(stat, key) in {
                            'Total clients':   stats.total_clients,
                            'RDV en attente':  stats.pending_appointments,
                            'Avis à modérer':  stats.unread_reviews,
                            'Produits actifs': stats.total_products,
                        }" :key="key"
                             class="p-3 rounded-xl text-center"
                             style="background: #f9f7f4; border: 1px solid #edddd0;">
                            <p class="text-xl font-bold font-poppins text-onyx-800">{{ formatNumber(stat) }}</p>
                            <p class="text-xs font-poppins text-onyx-400 mt-0.5">{{ key }}</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- ── Prochains RDV + Commandes récentes ───────── -->
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">

            <!-- Prochains RDV -->
            <div class="dashboard-section bg-white rounded-2xl shadow-card overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4" style="border-bottom: 1px solid #f5ede4;">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: rgba(59,130,246,0.1);">
                            <PhClock :size="17" class="text-blue-500" />
                        </div>
                        <h3 class="font-poppins font-semibold text-onyx-800">Prochains rendez-vous</h3>
                    </div>
                    <a :href="route('admin.rendez-vous.index')" class="text-xs font-poppins text-cognac-600 hover:text-cognac-800 font-medium">
                        Tout voir
                    </a>
                </div>
                <div class="divide-y divide-cream-50">
                    <div v-if="!upcomingAppointments?.length"
                         class="py-8 text-center text-sm font-poppins text-onyx-400">
                        Aucun rendez-vous à venir
                    </div>
                    <div v-for="appt in upcomingAppointments" :key="appt.id"
                         class="flex items-center gap-4 px-6 py-3 hover:bg-cream-50 transition-colors">
                        <img :src="appt.client.avatar" :alt="appt.client.name"
                             class="w-8 h-8 rounded-lg object-cover shrink-0" />
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold font-poppins text-onyx-700 truncate">{{ appt.client.name }}</p>
                            <p class="text-xs text-onyx-400 font-poppins">{{ appt.service.name }}</p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-xs font-semibold font-poppins text-onyx-700">{{ appt.date }}</p>
                            <p class="text-xs text-onyx-400 font-poppins">{{ appt.start_time }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dernières commandes -->
            <div class="dashboard-section bg-white rounded-2xl shadow-card overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4" style="border-bottom: 1px solid #f5ede4;">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: rgba(196,149,106,0.1);">
                            <PhShoppingBag :size="17" class="text-cognac-500" />
                        </div>
                        <h3 class="font-poppins font-semibold text-onyx-800">Dernières commandes</h3>
                    </div>
                    <a :href="route('admin.commandes.index')" class="text-xs font-poppins text-cognac-600 hover:text-cognac-800 font-medium">
                        Tout voir
                    </a>
                </div>
                <div class="divide-y divide-cream-50">
                    <div v-if="!recentOrders?.length"
                         class="py-8 text-center text-sm font-poppins text-onyx-400">
                        Aucune commande récente
                    </div>
                    <div v-for="order in recentOrders" :key="order.id"
                         class="flex items-center gap-4 px-6 py-3 hover:bg-cream-50 transition-colors">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0" style="background: rgba(196,149,106,0.1);">
                            <PhShoppingBag :size="16" class="text-cognac-400" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold font-poppins text-onyx-700 truncate">{{ order.client.name }}</p>
                            <p class="text-xs text-onyx-400 font-poppins">{{ order.order_number }}</p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-sm font-bold font-poppins text-onyx-800">{{ formatCurrency(order.total) }}</p>
                            <span class="badge text-xs"
                                  :class="{
                                    'badge-success': order.status === 'delivered',
                                    'badge-info':    ['processing','shipped'].includes(order.status),
                                    'badge-warning': order.status === 'pending',
                                    'badge-error':   order.status === 'cancelled',
                                  }">
                                {{ order.status_label }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>