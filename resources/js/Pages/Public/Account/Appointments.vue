<script setup>
// ============================================================
// Account/Appointments.vue
// ============================================================
import { Head, Link } from '@inertiajs/vue3'
import AccountLayout from '@/Layouts/AccountLayout.vue'
import {
    PhCalendarCheck, PhArrowLeft, PhClock, PhArrowRight,
    PhCheckCircle, PhCurrencyEur,
} from '@phosphor-icons/vue'

defineOptions({ layout: AccountLayout })

const props = defineProps({
    upcoming: Array,
    past:     Array,
})

function formatPrice(val) {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(val ?? 0)
}

const statusMap = {
    pending:   { label: 'En attente',   bg: 'rgba(245,158,11,0.1)',  color: '#d97706' },
    confirmed: { label: 'Confirmé',     bg: 'rgba(16,185,129,0.1)',  color: '#059669' },
    completed: { label: 'Terminé',      bg: 'rgba(99,102,241,0.1)',  color: '#6366f1' },
    cancelled: { label: 'Annulé',       bg: 'rgba(239,68,68,0.1)',   color: '#dc2626' },
    no_show:   { label: 'Non présenté', bg: 'rgba(107,114,128,0.1)', color: '#6b7280' },
}
</script>

<template>
    <Head title="Mes rendez-vous" />

    <div class="max-w-3xl mx-auto px-4 py-10 space-y-6">

        <div class="flex items-center gap-4">
            <Link :href="route('account.dashboard')"
                  class="w-9 h-9 rounded-xl flex items-center justify-center border border-cream-200 bg-white shadow-card text-onyx-500 hover:bg-cream-100 transition-colors">
                <PhArrowLeft :size="18" />
            </Link>
            <div>
                <h1 class="font-cormorant text-3xl font-bold text-onyx-800">Mes rendez-vous</h1>
                <p class="text-sm font-poppins text-onyx-500 mt-0.5">Consultez et gérez vos réservations</p>
            </div>
        </div>

        <!-- Prochains -->
        <div>
            <h2 class="font-poppins font-semibold text-onyx-800 mb-3 flex items-center gap-2">
                <PhCalendarCheck :size="16" class="text-cognac-500" />
                À venir
                <span v-if="upcoming.length"
                      class="text-xs font-poppins px-2 py-0.5 rounded-lg font-bold"
                      style="background: rgba(196,149,106,0.12); color: #b07d52;">
                    {{ upcoming.length }}
                </span>
            </h2>

            <div v-if="upcoming.length" class="space-y-4">
                <div v-for="appt in upcoming" :key="appt.id"
                     class="bg-white rounded-2xl shadow-card overflow-hidden hover:shadow-float transition-all duration-300">
                    <div class="flex items-stretch">
                        <div class="w-1.5 shrink-0"
                             :style="{ background: appt.status === 'confirmed' ? '#10b981' : '#f59e0b' }" />
                        <div class="flex-1 p-5">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-xl overflow-hidden shrink-0 bg-cream-100">
                                        <img v-if="appt.service.image_url" :src="appt.service.image_url"
                                             class="w-full h-full object-cover" />
                                    </div>
                                    <div>
                                        <h3 class="font-poppins font-bold text-onyx-800">{{ appt.service.name }}</h3>
                                        <p class="text-xs font-poppins text-onyx-400 mt-0.5">
                                            {{ appt.service.category_label }} · {{ appt.service.duration }}
                                        </p>
                                    </div>
                                </div>
                                <span class="px-2.5 py-1 rounded-xl text-xs font-poppins font-semibold shrink-0"
                                      :style="{ background: statusMap[appt.status]?.bg, color: statusMap[appt.status]?.color }">
                                    {{ statusMap[appt.status]?.label }}
                                </span>
                            </div>

                            <div class="grid grid-cols-2 gap-3 mt-4">
                                <div class="flex items-center gap-2 text-sm font-poppins text-onyx-600">
                                    <PhCalendarCheck :size="15" class="text-cognac-400 shrink-0" />
                                    {{ appt.date }}
                                </div>
                                <div class="flex items-center gap-2 text-sm font-poppins text-onyx-600">
                                    <PhClock :size="15" class="text-cognac-400 shrink-0" />
                                    {{ appt.start_time }} — {{ appt.end_time }}
                                </div>
                            </div>

                            <div class="flex items-center justify-between mt-4 pt-3 border-t border-cream-100">
                                <div class="flex items-center gap-3">
                                    <span class="text-base font-bold font-poppins text-onyx-800">{{ formatPrice(appt.price) }}</span>
                                    <span v-if="appt.deposit_paid"
                                          class="flex items-center gap-1 text-xs font-poppins text-emerald-600 font-semibold">
                                        <PhCheckCircle :size="13" /> Acompte payé
                                    </span>
                                    <span v-else-if="appt.deposit_amount > 0"
                                          class="flex items-center gap-1 text-xs font-poppins text-amber-600 font-semibold">
                                        <PhCurrencyEur :size="13" />
                                        Acompte : {{ formatPrice(appt.deposit_amount) }}
                                    </span>
                                </div>
                                <p class="text-xs font-poppins text-onyx-400">Réf. {{ appt.reference }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="bg-white rounded-2xl shadow-card p-8 text-center">
                <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4"
                     style="background: rgba(196,149,106,0.08);">
                    <PhCalendarCheck :size="32" class="text-onyx-300" />
                </div>
                <p class="font-poppins font-semibold text-onyx-600 mb-1">Aucun rendez-vous à venir</p>
                <p class="text-sm font-poppins text-onyx-400 mb-5">Réservez votre prochaine prestation</p>
                <Link :href="route('booking.services')"
                      class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-poppins text-sm font-semibold text-white"
                      style="background: linear-gradient(135deg, #c4956a, #b07d52);">
                    Réserver <PhArrowRight :size="15" weight="bold" />
                </Link>
            </div>
        </div>

        <!-- Historique -->
        <div v-if="past.length">
            <h2 class="font-poppins font-semibold text-onyx-800 mb-3 flex items-center gap-2">
                <PhClock :size="16" class="text-onyx-400" />
                Historique
            </h2>
            <div class="bg-white rounded-2xl shadow-card overflow-hidden">
                <div class="divide-y divide-cream-50">
                    <div v-for="appt in past" :key="appt.id"
                         class="flex items-center gap-4 px-5 py-4 hover:bg-cream-50/50 transition-colors">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                             style="background: rgba(196,149,106,0.1);">
                            <PhCalendarCheck :size="18" class="text-cognac-400" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-poppins font-semibold text-onyx-700">{{ appt.service.name }}</p>
                            <p class="text-xs font-poppins text-onyx-400">{{ appt.date }} à {{ appt.start_time }}</p>
                        </div>
                        <div class="text-right">
                            <span class="px-2 py-0.5 rounded-lg text-xs font-poppins font-semibold"
                                  :style="{ background: statusMap[appt.status]?.bg, color: statusMap[appt.status]?.color }">
                                {{ statusMap[appt.status]?.label }}
                            </span>
                            <p class="text-sm font-poppins font-semibold text-onyx-700 mt-0.5">{{ formatPrice(appt.price) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>