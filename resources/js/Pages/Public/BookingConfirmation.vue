<script setup>
import { onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { gsap } from 'gsap'
import PublicLayout from '@/Layouts/PublicLayout.vue'
import {
    PhCheckCircle, PhCalendarCheck, PhClock, PhCreditCard,
    PhArrowRight, PhCalendarBlank,
} from '@phosphor-icons/vue'

defineOptions({ layout: PublicLayout })

const props = defineProps({
    appointment: Object,
    service:     Object,
})

function formatPrice(val) {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(val ?? 0)
}

const statusColor = {
    pending:   '#d97706',
    confirmed: '#059669',
}

onMounted(() => {
    gsap.fromTo('.confirm-card',
        { opacity: 0, y: 30, scale: 0.96 },
        { opacity: 1, y: 0, scale: 1, duration: 0.6, stagger: 0.1, ease: 'power3.out' }
    )
    // Animation de la checkmark
    gsap.fromTo('.check-icon',
        { scale: 0, rotation: -30 },
        { scale: 1, rotation: 0, duration: 0.7, ease: 'back.out(1.7)', delay: 0.2 }
    )
})
</script>

<template>
    <Head :title="`Réservation confirmée — ${service.name}`" />

    <div style="background: #faf7f4; min-height: 100vh;">

        <!-- Hero minimal -->
        <div class="py-12" style="background: #0d0d1a;">
            <div class="max-w-2xl mx-auto px-4 text-center">
                <div class="check-icon w-20 h-20 rounded-3xl mx-auto mb-5 flex items-center justify-center shadow-luxury"
                     style="background: linear-gradient(135deg, #c4956a, #b07d52);">
                    <PhCheckCircle :size="40" weight="fill" class="text-white" />
                </div>
                <h1 class="font-cormorant text-4xl font-bold text-white mb-2">
                    Rendez-vous enregistré !
                </h1>
                <p class="text-sm font-poppins text-white/50">
                    Référence : <strong class="text-cognac-400">{{ appointment.reference }}</strong>
                </p>
            </div>
        </div>

        <div class="max-w-2xl mx-auto px-4 py-8 space-y-4">

            <!-- Récap du RDV -->
            <div class="confirm-card bg-white rounded-2xl shadow-card overflow-hidden">
                <div class="px-5 py-4 flex items-center gap-2" style="border-bottom: 1px solid #f5ede4;">
                    <PhCalendarCheck :size="16" class="text-cognac-500" />
                    <h2 class="font-poppins font-semibold text-onyx-800 text-sm">Votre rendez-vous</h2>
                </div>
                <div class="p-5 space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-poppins text-onyx-500">Prestation</span>
                        <span class="text-sm font-poppins font-bold text-onyx-800">{{ service.name }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-poppins text-onyx-500">Date</span>
                        <span class="text-sm font-poppins font-semibold text-onyx-700">{{ appointment.date }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-poppins text-onyx-500">Heure</span>
                        <span class="text-sm font-poppins font-semibold text-onyx-700 flex items-center gap-1">
                            <PhClock :size="13" />
                            {{ appointment.time_start }} – {{ appointment.time_end }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-poppins text-onyx-500">Durée</span>
                        <span class="text-sm font-poppins text-onyx-700">{{ service.duration }} min</span>
                    </div>
                    <div class="flex justify-between items-center pt-2" style="border-top: 1px solid #f5ede4;">
                        <span class="text-sm font-poppins font-bold text-onyx-800">Prix total</span>
                        <span class="text-xl font-poppins font-bold text-onyx-800">{{ formatPrice(appointment.price) }}</span>
                    </div>
                </div>
            </div>

            <!-- Statut & paiement acompte -->
            <div class="confirm-card bg-white rounded-2xl shadow-card p-5">

                <!-- Statut actuel -->
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-3 h-3 rounded-full animate-pulse"
                         :style="{ background: statusColor[appointment.status] ?? '#6b7280' }" />
                    <div>
                        <p class="text-sm font-poppins font-bold text-onyx-800">
                            {{ appointment.status === 'pending' ? 'En attente de confirmation' : 'Confirmé' }}
                        </p>
                        <p class="text-xs font-poppins text-onyx-400">
                            {{ appointment.status === 'pending'
                                ? 'Nous vous contacterons dans les 24h pour confirmer votre créneau.'
                                : 'Votre rendez-vous est confirmé. À bientôt !' }}
                        </p>
                    </div>
                </div>

                <!-- Acompte requis -->
                <template v-if="appointment.deposit_required && !appointment.deposit_paid">
                    <div class="p-4 rounded-xl mb-4"
                         style="background: rgba(245,158,11,0.08); border: 1px solid rgba(245,158,11,0.2);">
                        <div class="flex items-start gap-3">
                            <PhCreditCard :size="18" class="text-amber-600 mt-0.5 shrink-0" />
                            <div>
                                <p class="text-sm font-poppins font-bold text-amber-800 mb-1">
                                    Acompte requis — {{ formatPrice(appointment.deposit_amount) }}
                                </p>
                                <p class="text-xs font-poppins text-amber-700 leading-relaxed">
                                    Un acompte est demandé pour sécuriser votre réservation.
                                    Il sera déduit du montant total lors de la prestation.
                                </p>
                            </div>
                        </div>
                    </div>

                    <Link :href="route('payment.show', { type: 'appointment', id: appointment.id })"
                          class="flex items-center justify-center gap-2 w-full py-3.5 rounded-2xl text-sm font-poppins font-bold text-white transition-all hover:opacity-90 hover:-translate-y-0.5 shadow-md"
                          style="background: linear-gradient(135deg, #c4956a, #b07d52);">
                        <PhCreditCard :size="17" />
                        Régler l'acompte de {{ formatPrice(appointment.deposit_amount) }}
                        <PhArrowRight :size="15" />
                    </Link>
                </template>

                <!-- Acompte déjà payé -->
                <div v-else-if="appointment.deposit_paid"
                     class="flex items-center gap-3 p-3 rounded-xl"
                     style="background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.2);">
                    <PhCheckCircle :size="18" class="text-emerald-600" weight="fill" />
                    <p class="text-sm font-poppins font-semibold text-emerald-700">
                        Acompte réglé — {{ formatPrice(appointment.deposit_amount) }}
                    </p>
                </div>

                <!-- Pas d'acompte requis -->
                <div v-else
                     class="flex items-center gap-3 p-3 rounded-xl"
                     style="background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.2);">
                    <PhCheckCircle :size="18" class="text-emerald-600" weight="fill" />
                    <p class="text-sm font-poppins font-semibold text-emerald-700">
                        Aucun acompte requis — règlement le jour de la prestation
                    </p>
                </div>
            </div>

            <!-- Info email -->
            <div class="confirm-card px-5 py-4 rounded-2xl text-sm font-poppins text-onyx-500 text-center"
                 style="background: rgba(196,149,106,0.06); border: 1px solid rgba(196,149,106,0.15);">
                📧 Un email de confirmation vous a été envoyé avec tous les détails.
            </div>

            <!-- Actions -->
            <div class="confirm-card flex items-center gap-3 flex-wrap">
                <Link :href="route('booking.services')"
                      class="flex-1 flex items-center justify-center gap-2 py-3 rounded-2xl text-sm font-poppins font-medium text-onyx-600 border border-cream-200 bg-white hover:bg-cream-50 transition-colors">
                    Voir d'autres services
                </Link>
                <Link :href="route('account.appointments')"
                      class="flex-1 flex items-center justify-center gap-2 py-3 rounded-2xl text-sm font-poppins font-semibold text-white transition-all hover:opacity-90"
                      style="background: linear-gradient(135deg, #0d0d1a, #1a1a2e);">
                    <PhCalendarBlank :size="15" />
                    Mes rendez-vous
                </Link>
            </div>
        </div>
    </div>
</template>