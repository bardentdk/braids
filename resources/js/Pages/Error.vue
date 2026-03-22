<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import PublicLayout from '@/Layouts/PublicLayout.vue'

defineOptions({ layout: PublicLayout })

const props = defineProps({
    status: {
        type: Number,
        default: 500,
    },
})

const config = computed(() => ({
    404: {
        title:       'Page introuvable',
        description: 'La page que vous cherchez n\'existe pas ou a été déplacée.',
        emoji:       '🔍',
    },
    403: {
        title:       'Accès refusé',
        description: 'Vous n\'avez pas les permissions pour accéder à cette page.',
        emoji:       '🔒',
    },
    500: {
        title:       'Erreur serveur',
        description: 'Une erreur inattendue s\'est produite. Notre équipe a été notifiée.',
        emoji:       '⚙️',
    },
    503: {
        title:       'Service indisponible',
        description: 'Le site est temporairement en maintenance. Revenez dans quelques minutes.',
        emoji:       '🛠️',
    },
}[props.status] ?? {
    title:       'Erreur',
    description: 'Une erreur s\'est produite.',
    emoji:       '❌',
}))
</script>

<template>
    <Head :title="`${status} — ${config.title}`" />

    <div class="min-h-screen flex items-center justify-center px-4"
         style="background: #faf7f4;">
        <div class="text-center max-w-lg">
            <!-- Emoji -->
            <div class="text-7xl mb-6">{{ config.emoji }}</div>

            <!-- Code erreur -->
            <div class="font-cormorant text-8xl font-bold mb-2"
                 style="color: rgba(196,149,106,0.3);">
                {{ status }}
            </div>

            <!-- Titre -->
            <h1 class="font-cormorant text-3xl font-bold text-onyx-800 mb-3">
                {{ config.title }}
            </h1>

            <!-- Description -->
            <p class="font-poppins text-sm text-onyx-500 leading-relaxed mb-8">
                {{ config.description }}
            </p>

            <!-- Actions -->
            <div class="flex items-center justify-center gap-3 flex-wrap">
                <Link :href="route('home')"
                      class="flex items-center gap-2 px-6 py-3 rounded-2xl text-sm font-poppins font-semibold text-white shadow-md transition-all hover:opacity-90 hover:-translate-y-0.5"
                      style="background: linear-gradient(135deg, #c4956a, #b07d52);">
                    ← Retour à l'accueil
                </Link>
                <button @click="() => window.history.back()"
                        class="px-6 py-3 rounded-2xl text-sm font-poppins font-medium text-onyx-600 border border-cream-200 bg-white hover:bg-cream-50 transition-colors">
                    Page précédente
                </button>
            </div>
        </div>
    </div>
</template>