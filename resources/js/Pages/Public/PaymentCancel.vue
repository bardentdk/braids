<script setup>
import { onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { gsap } from 'gsap'
import { PhXCircle, PhArrowLeft, PhArrowRight } from '@phosphor-icons/vue'

const props = defineProps({
    type: String,
    id:   Number,
})

onMounted(() => {
    gsap.fromTo('.cancel-content',
        { opacity: 0, y: 20 },
        { opacity: 1, y: 0, duration: 0.5, stagger: 0.1, ease: 'power2.out' }
    )
})
</script>

<template>
    <Head title="Paiement annulé" />

    <div class="min-h-screen flex items-center justify-center p-4"
         style="background: linear-gradient(135deg, #faf7f4 0%, #f5ede4 100%);">

        <div class="w-full max-w-md space-y-5 text-center">

            <div class="cancel-content flex justify-center">
                <div class="w-20 h-20 rounded-full flex items-center justify-center shadow-lg bg-red-100">
                    <PhXCircle :size="42" weight="fill" class="text-red-500" />
                </div>
            </div>

            <div class="cancel-content">
                <h1 class="font-cormorant text-3xl font-bold text-onyx-800 mb-1">Paiement annulé</h1>
                <p class="text-sm font-poppins text-onyx-500">
                    Votre paiement a été annulé. Aucun montant n'a été débité.
                </p>
            </div>

            <div class="cancel-content bg-white rounded-2xl shadow-card p-5 text-left">
                <p class="text-sm font-poppins text-onyx-600 leading-relaxed">
                    Si vous avez rencontré un problème, n'hésitez pas à réessayer ou à nous contacter.
                    Votre {{ type === 'order' ? 'commande' : 'réservation' }} n'a pas été validée.
                </p>
            </div>

            <div class="cancel-content flex flex-col gap-3">
                <Link :href="route('payment.show', { type, id })"
                      class="flex items-center justify-center gap-2 py-3 rounded-xl font-poppins text-sm font-semibold text-white transition-all hover:opacity-90"
                      style="background: linear-gradient(135deg, #c4956a, #b07d52);">
                    <PhArrowLeft :size="16" />
                    Réessayer le paiement
                </Link>
                <Link :href="route('home')"
                      class="flex items-center justify-center gap-2 py-3 rounded-xl font-poppins text-sm font-medium text-onyx-600 border border-cream-200 hover:bg-cream-50 transition-colors">
                    Retour à l'accueil
                </Link>
            </div>

        </div>
    </div>
</template>