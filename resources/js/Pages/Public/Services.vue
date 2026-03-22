<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { gsap } from 'gsap'
import { ScrollTrigger } from 'gsap/ScrollTrigger'
import PublicLayout from '@/Layouts/PublicLayout.vue'
import {
    PhBridge, PhClock, PhCalendarCheck, PhArrowRight,
    PhCheck, PhStar, PhFunnel, PhSparkle,
} from '@phosphor-icons/vue'

gsap.registerPlugin(ScrollTrigger)
defineOptions({ layout: PublicLayout })

const props = defineProps({
    services:   Array,
    categories: Array,
    filters:    Object,
    settings:   Object,
})

const selectedCategory = ref(props.filters?.category ?? null)

const filtered = computed(() =>
    selectedCategory.value
        ? props.services.filter(s => s.category === selectedCategory.value)
        : props.services
)

onMounted(() => {
    gsap.fromTo('.page-hero-content',
        { opacity: 0, y: 40 },
        { opacity: 1, y: 0, duration: 0.9, ease: 'power3.out' }
    )
    gsap.utils.toArray('.service-card').forEach((el, i) => {
        gsap.fromTo(el,
            { opacity: 0, y: 30 },
            {
                opacity: 1, y: 0, duration: 0.6, delay: i * 0.08, ease: 'power2.out',
                scrollTrigger: { trigger: el, start: 'top 88%', once: true },
            }
        )
    })
})

const formatPrice = (p) => new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(p)
</script>

<template>
    <Head title="Nos Services & Prestations" />

    <!-- Hero section -->
    <section class="relative py-24 overflow-hidden"
             style="background: linear-gradient(135deg, #0d0d1a, #1a1a2e);">

        <div class="absolute inset-0 opacity-15"
             style="background-image: radial-gradient(circle at 20% 50%, #c4956a 0%, transparent 40%), radial-gradient(circle at 80% 30%, #d4af37 0%, transparent 40%);" />

        <div class="container-brand relative z-10">
            <div class="page-hero-content max-w-2xl">
                <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full mb-5 text-xs font-semibold uppercase tracking-widest font-poppins"
                     style="background: rgba(196,149,106,0.15); color: #c4956a;">
                    <PhBridge :size="14" />
                    Nos prestations
                </div>
                <h1 class="font-cormorant text-5xl md:text-6xl font-bold text-white leading-tight mb-4">
                    Des tresses qui vous<br>
                    <span class="text-gradient-gold">ressemblent</span>
                </h1>
                <p class="text-lg text-white/60 font-poppins leading-relaxed mb-8">
                    Du box braids au knotless, en passant par les twists et locks — découvrez toutes nos créations capillaires.
                </p>
                <div class="flex items-center gap-4 text-sm text-white/50 font-poppins">
                    <div class="flex items-center gap-2">
                        <PhCheck :size="16" class="text-cognac-400" />
                        {{ settings.booking_cancellation_hours }}h d'annulation
                    </div>
                    <div class="flex items-center gap-2">
                        <PhCheck :size="16" class="text-cognac-400" />
                        Acompte sécurisé
                    </div>
                    <div class="flex items-center gap-2">
                        <PhCheck :size="16" class="text-cognac-400" />
                        Consultation offerte
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Filtres catégories -->
    <section class="sticky top-[72px] z-20 py-4 border-b"
             style="background: rgba(250,247,244,0.97); backdrop-filter: blur(12px); border-color: rgba(26,26,46,0.08);">
        <div class="container-brand flex items-center gap-3 overflow-x-auto pb-0 scrollbar-hide">
            <div class="flex items-center gap-2 mr-2 text-xs font-semibold text-onyx-400 uppercase tracking-widest font-poppins shrink-0">
                <PhFunnel :size="14" />
                Filtrer
            </div>

            <button @click="selectedCategory = null"
                    class="shrink-0 px-5 py-2 rounded-xl text-sm font-medium font-poppins transition-all"
                    :class="!selectedCategory
                        ? 'text-white shadow-float'
                        : 'text-onyx-500 hover:text-onyx-700 bg-cream-200'"
                    :style="!selectedCategory ? 'background: linear-gradient(135deg, #c4956a, #b07d52);' : ''">
                Tous ({{ services.length }})
            </button>

            <button v-for="cat in categories" :key="cat.value"
                    @click="selectedCategory = selectedCategory === cat.value ? null : cat.value"
                    class="shrink-0 px-5 py-2 rounded-xl text-sm font-medium font-poppins transition-all"
                    :class="selectedCategory === cat.value
                        ? 'text-white shadow-float'
                        : 'text-onyx-500 hover:text-onyx-700 bg-cream-200'"
                    :style="selectedCategory === cat.value ? 'background: linear-gradient(135deg, #c4956a, #b07d52);' : ''">
                {{ cat.label }} ({{ cat.count }})
            </button>
        </div>
    </section>

    <!-- Liste services -->
    <section class="py-16" style="background: #faf7f4;">
        <div class="container-brand">

            <p class="text-sm text-onyx-400 font-poppins mb-8">
                {{ filtered.length }} prestation{{ filtered.length > 1 ? 's' : '' }} disponible{{ filtered.length > 1 ? 's' : '' }}
            </p>

            <!-- Grille -->
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                <div v-for="service in filtered" :key="service.id"
                     class="service-card group bg-white rounded-3xl overflow-hidden shadow-card hover:shadow-float transition-all duration-500 hover:-translate-y-1.5 flex flex-col">

                    <!-- Image -->
                    <div class="relative overflow-hidden" style="aspect-ratio: 16/9;">
                        <div class="w-full h-full transition-transform duration-700 group-hover:scale-105 flex items-center justify-center"
                             style="background: linear-gradient(135deg, #1a1a2e, #2d2d4a);">
                            <PhBridge :size="52" class="text-cognac-400 opacity-30" />
                        </div>

                        <!-- Overlay -->
                        <div class="absolute inset-0"
                             style="background: linear-gradient(to top, rgba(13,13,26,0.5), transparent 60%);" />

                        <!-- Badges -->
                        <div class="absolute top-3 left-3 flex items-center gap-2">
                            <span class="px-2.5 py-1 rounded-lg text-xs font-semibold font-poppins"
                                  style="background: rgba(13,13,26,0.75); color: #c4956a; backdrop-filter: blur(8px);">
                                {{ service.category_label }}
                            </span>
                            <span v-if="service.is_featured"
                                  class="px-2.5 py-1 rounded-lg text-xs font-semibold font-poppins flex items-center gap-1"
                                  style="background: rgba(212,175,55,0.85); color: #1a1a2e;">
                                <PhSparkle :size="11" />
                                Populaire
                            </span>
                        </div>

                        <div class="absolute top-3 right-3 flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold text-white font-poppins"
                             style="background: rgba(13,13,26,0.75); backdrop-filter: blur(8px);">
                            <PhClock :size="11" />
                            {{ service.duration_formatted }}
                        </div>
                    </div>

                    <!-- Contenu -->
                    <div class="flex flex-col flex-1 p-6">

                        <div class="flex items-start justify-between gap-2 mb-2">
                            <h3 class="font-cormorant text-xl font-bold text-onyx-800 group-hover:text-cognac-700 transition-colors leading-snug">
                                {{ service.name }}
                            </h3>
                            <span class="font-cormorant text-xl font-bold shrink-0"
                                  style="color: #c4956a;">
                                {{ formatPrice(service.price) }}
                            </span>
                        </div>

                        <p class="text-sm text-onyx-400 font-poppins leading-relaxed mb-4 flex-1 line-clamp-2">
                            {{ service.short_description }}
                        </p>

                        <!-- Includes -->
                        <div v-if="service.includes?.length" class="space-y-1.5 mb-4">
                            <div v-for="item in service.includes.slice(0, 3)" :key="item"
                                 class="flex items-center gap-2 text-xs text-onyx-500 font-poppins">
                                <PhCheck :size="13" weight="bold" class="text-cognac-400 shrink-0" />
                                {{ item }}
                            </div>
                        </div>

                        <!-- Acompte -->
                        <div v-if="service.deposit_required"
                             class="flex items-center gap-2 px-3 py-2 rounded-xl mb-4 text-xs font-poppins"
                             style="background: rgba(196,149,106,0.08); color: #b07d52; border: 1px solid rgba(196,149,106,0.2);">
                            <PhCheck :size="13" weight="bold" />
                            Acompte de {{ formatPrice(service.deposit_amount) }} requis à la réservation
                        </div>

                        <!-- CTA -->
                        <Link :href="route('booking.show', service.slug)"
                              class="btn-primary w-full justify-center py-3.5 text-sm">
                            <PhCalendarCheck :size="16" weight="bold" />
                            Réserver ce soin
                            <PhArrowRight :size="16" class="ml-auto" />
                        </Link>
                    </div>
                </div>
            </div>

            <!-- État vide -->
            <div v-if="!filtered.length" class="py-24 text-center">
                <PhBridge :size="48" class="text-onyx-200 mx-auto mb-4" />
                <p class="text-onyx-500 font-poppins">Aucun service dans cette catégorie.</p>
                <button @click="selectedCategory = null"
                        class="mt-4 text-sm text-cognac-600 hover:text-cognac-800 font-poppins font-medium underline underline-offset-2">
                    Voir tous les services
                </button>
            </div>
        </div>
    </section>
</template>