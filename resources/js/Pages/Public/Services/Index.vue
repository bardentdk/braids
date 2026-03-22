<script setup>
import { onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { gsap } from 'gsap'
import { ScrollTrigger } from 'gsap/ScrollTrigger'
import PublicLayout from '@/Layouts/PublicLayout.vue'
import { PhClock, PhArrowRight, PhStar, PhScissors } from '@phosphor-icons/vue'

gsap.registerPlugin(ScrollTrigger)
defineOptions({ layout: PublicLayout })

const props = defineProps({
    services:   Array,
    categories: Array,
})

onMounted(() => {
    gsap.utils.toArray('.service-card').forEach((card, i) => {
        gsap.fromTo(card,
            { opacity: 0, y: 30 },
            {
                opacity: 1, y: 0, duration: 0.5,
                delay: i * 0.07,
                ease: 'power3.out',
                scrollTrigger: { trigger: card, start: 'top 90%' },
            }
        )
    })
})

function formatPrice(val) {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(val)
}
</script>

<template>
    <Head>
        <title>Nos services — Patricia Braids Studio</title>
        <meta name="description" content="Découvrez nos prestations de tresses, twists, locs et soins capillaires. Réservez votre session en ligne." />
    </Head>

    <!-- Hero -->
    <section class="relative py-20 overflow-hidden" style="background: #0d0d1a;">
        <div class="absolute inset-0 opacity-10"
             style="background: radial-gradient(ellipse at 30% 50%, #c4956a 0%, transparent 70%);" />
        <div class="max-w-5xl mx-auto px-4 relative text-center">
            <div class="flex items-center justify-center gap-2 mb-4">
                <div class="w-6 h-px" style="background: #c4956a;"></div>
                <span class="text-xs font-poppins font-semibold tracking-[0.3em] uppercase" style="color: #c4956a;">Nos prestations</span>
                <div class="w-6 h-px" style="background: #c4956a;"></div>
            </div>
            <h1 class="font-cormorant text-5xl md:text-6xl font-bold text-white mb-4">
                L'art des tresses<br><span style="color: #c4956a;">à votre service</span>
            </h1>
            <p class="text-base font-poppins text-white/50 max-w-xl mx-auto">
                Choisissez votre prestation et réservez directement en ligne. Chaque session est une expérience unique.
            </p>
        </div>
    </section>

    <!-- Services -->
    <section style="background: #faf7f4;" class="py-14">
        <div class="max-w-5xl mx-auto px-4">

            <!-- Catégories (si présentes) -->
            <div v-if="categories?.length" class="flex flex-wrap gap-2 mb-10 justify-center">
                <button class="px-4 py-2 rounded-2xl text-sm font-poppins font-semibold text-white shadow-sm"
                        style="background: linear-gradient(135deg, #1a1a2e, #0d0d1a);">
                    Tous
                </button>
                <button v-for="cat in categories" :key="cat.id"
                        class="px-4 py-2 rounded-2xl text-sm font-poppins font-medium text-onyx-600 bg-white border border-cream-200 hover:border-cognac-300 transition-colors">
                    {{ cat.name }}
                </button>
            </div>

            <!-- Grille services -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <Link v-for="service in services" :key="service.id"
                      :href="route('booking.show', service.slug)"
                      class="service-card group block bg-white rounded-3xl overflow-hidden shadow-card hover:shadow-float transition-all duration-300 hover:-translate-y-1.5">

                    <!-- Image -->
                    <div class="relative h-52 overflow-hidden">
                        <img v-if="service.cover_image_url"
                             :src="service.cover_image_url" :alt="service.name"
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-600" />
                        <div v-else class="w-full h-full flex items-center justify-center"
                             style="background: linear-gradient(135deg, rgba(196,149,106,0.15), #0d0d1a11);">
                            <PhScissors :size="48" class="text-onyx-200" />
                        </div>

                        <!-- Badge durée -->
                        <div class="absolute top-3 right-3 flex items-center gap-1 px-2.5 py-1 rounded-xl text-xs font-poppins font-bold text-white"
                             style="background: rgba(13,13,26,0.75); backdrop-filter: blur(8px);">
                            <PhClock :size="11" />{{ service.duration }} min
                        </div>
                    </div>

                    <!-- Contenu -->
                    <div class="p-5">
                        <h3 class="font-cormorant text-xl font-bold text-onyx-800 group-hover:text-cognac-700 transition-colors mb-1.5">
                            {{ service.name }}
                        </h3>
                        <p class="text-xs font-poppins text-onyx-500 leading-relaxed line-clamp-2 mb-4">
                            {{ service.description }}
                        </p>
                        <div class="flex items-center justify-between">
                            <p class="font-poppins font-bold text-onyx-800 text-lg">
                                {{ formatPrice(service.price) }}
                            </p>
                            <span class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-sm font-poppins font-semibold text-white transition-all group-hover:gap-2"
                                  style="background: linear-gradient(135deg, #c4956a, #b07d52);">
                                Réserver <PhArrowRight :size="14" />
                            </span>
                        </div>
                    </div>
                </Link>
            </div>

            <!-- Empty -->
            <div v-if="!services?.length" class="flex flex-col items-center justify-center py-20">
                <PhScissors :size="48" class="text-onyx-200 mb-4" />
                <p class="font-cormorant text-2xl font-bold text-onyx-500 mb-2">Aucun service disponible</p>
                <p class="text-sm font-poppins text-onyx-400">Revenez bientôt, de nouvelles prestations arrivent !</p>
            </div>
        </div>
    </section>
</template>