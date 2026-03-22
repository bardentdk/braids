<!-- ============================================================ -->
<!-- PaymentSuccess.vue -->
<!-- ============================================================ -->
<script setup>
import { onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { gsap } from 'gsap'
import {
    PhCheckCircle, PhCalendarCheck, PhShoppingBag,
    PhArrowRight, PhEnvelope,
} from '@phosphor-icons/vue'

const props = defineProps({
    data: Object,
})

function formatPrice(val) {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(val ?? 0)
}

onMounted(() => {
    // Animation checkmark
    gsap.fromTo('.success-check',
        { scale: 0, opacity: 0 },
        { scale: 1, opacity: 1, duration: 0.6, ease: 'back.out(1.7)', delay: 0.2 }
    )
    gsap.fromTo('.success-card',
        { opacity: 0, y: 24 },
        { opacity: 1, y: 0, duration: 0.5, stagger: 0.1, ease: 'power2.out', delay: 0.5 }
    )
})
</script>

<template>
    <Head title="Paiement confirmé" />

    <div class="min-h-screen flex items-center justify-center p-4"
         style="background: linear-gradient(135deg, #faf7f4 0%, #f5ede4 100%);">

        <div class="w-full max-w-md space-y-5">

            <!-- Icône succès -->
            <div class="success-check flex justify-center">
                <div class="w-20 h-20 rounded-full flex items-center justify-center shadow-lg"
                     style="background: linear-gradient(135deg, #10b981, #059669);">
                    <PhCheckCircle :size="42" weight="fill" class="text-white" />
                </div>
            </div>

            <!-- Titre -->
            <div class="success-card text-center">
                <h1 class="font-cormorant text-3xl font-bold text-onyx-800 mb-1">
                    Paiement confirmé !
                </h1>
                <p class="text-sm font-poppins text-onyx-500">
                    {{ data.type === 'order'
                        ? 'Votre commande a bien été enregistrée.'
                        : 'Votre rendez-vous est confirmé.' }}
                </p>
            </div>

            <!-- Récap -->
            <div class="success-card bg-white rounded-2xl shadow-card p-5 space-y-3">

                <div class="flex items-center gap-3 pb-3 border-b border-cream-100">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                         style="background: rgba(16,185,129,0.1);">
                        <component :is="data.type === 'order' ? PhShoppingBag : PhCalendarCheck"
                                   :size="20" class="text-emerald-600" />
                    </div>
                    <div>
                        <p class="text-sm font-poppins font-semibold text-onyx-800">
                            {{ data.type === 'order' ? 'Commande' : 'Rendez-vous' }}
                            #{{ data.reference }}
                        </p>
                        <p class="text-xs font-poppins text-onyx-400">
                            {{ formatPrice(data.total) }} réglé
                        </p>
                    </div>
                </div>

                <!-- Détails RDV -->
                <template v-if="data.type === 'appointment'">
                    <div class="flex justify-between text-sm font-poppins">
                        <span class="text-onyx-500">Prestation</span>
                        <span class="font-semibold text-onyx-700">{{ data.service }}</span>
                    </div>
                    <div class="flex justify-between text-sm font-poppins">
                        <span class="text-onyx-500">Date</span>
                        <span class="font-semibold text-onyx-700">{{ data.date }}</span>
                    </div>
                    <div class="flex justify-between text-sm font-poppins">
                        <span class="text-onyx-500">Heure</span>
                        <span class="font-semibold text-onyx-700">{{ data.start_time }}</span>
                    </div>
                </template>

                <!-- Email -->
                <div class="flex items-center gap-2 pt-3 border-t border-cream-100 text-xs font-poppins text-onyx-400">
                    <PhEnvelope :size="14" />
                    Un email de confirmation a été envoyé à <strong class="text-onyx-600">{{ data.email }}</strong>
                </div>
            </div>

            <!-- Actions -->
            <div class="success-card flex flex-col gap-3">
                <Link :href="data.type === 'order' ? route('shop.index') : route('booking.services')"
                      class="flex items-center justify-center gap-2 py-3 rounded-xl font-poppins text-sm font-semibold text-white transition-all hover:opacity-90"
                      style="background: linear-gradient(135deg, #c4956a, #b07d52);">
                    {{ data.type === 'order' ? 'Continuer mes achats' : 'Voir les services' }}
                    <PhArrowRight :size="16" />
                </Link>
                <Link :href="route('home')"
                      class="flex items-center justify-center gap-2 py-3 rounded-xl font-poppins text-sm font-medium text-onyx-600 border border-cream-200 hover:bg-cream-50 transition-colors">
                    Retour à l'accueil
                </Link>
            </div>

        </div>
    </div>
</template>