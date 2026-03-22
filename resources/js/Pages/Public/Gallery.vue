<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head } from '@inertiajs/vue3'
import { gsap } from 'gsap'
import { ScrollTrigger } from 'gsap/ScrollTrigger'
import PublicLayout from '@/Layouts/PublicLayout.vue'
import {
    PhImages, PhX, PhArrowLeft, PhArrowRight,
    PhBridge, PhFunnel, PhMagnifyingGlass,
} from '@phosphor-icons/vue'

gsap.registerPlugin(ScrollTrigger)
defineOptions({ layout: PublicLayout })

const props = defineProps({
    images:     Object,
    categories: Array,
    filters:    Object,
    total:      Number,
})

const selectedCategory = ref(props.filters?.category ?? null)
const lightboxOpen     = ref(false)
const lightboxIndex    = ref(0)
const lightboxImages   = computed(() => props.images.data)

const filtered = computed(() =>
    selectedCategory.value
        ? props.images.data.filter(img => img.category === selectedCategory.value)
        : props.images.data
)

const currentLightbox = computed(() => lightboxImages.value[lightboxIndex.value])

function openLightbox(index) {
    lightboxIndex.value = index
    lightboxOpen.value  = true
    document.body.style.overflow = 'hidden'
    gsap.fromTo('.lightbox-inner', { opacity: 0, scale: 0.92 }, { opacity: 1, scale: 1, duration: 0.3, ease: 'power2.out' })
}

function closeLightbox() {
    gsap.to('.lightbox-inner', {
        opacity: 0, scale: 0.95, duration: 0.2, ease: 'power2.in',
        onComplete: () => {
            lightboxOpen.value = false
            document.body.style.overflow = ''
        },
    })
}

function prevLightbox() {
    lightboxIndex.value = (lightboxIndex.value - 1 + lightboxImages.value.length) % lightboxImages.value.length
}

function nextLightbox() {
    lightboxIndex.value = (lightboxIndex.value + 1) % lightboxImages.value.length
}

// Keyboard navigation
function handleKeydown(e) {
    if (!lightboxOpen.value) return
    if (e.key === 'Escape')    closeLightbox()
    if (e.key === 'ArrowLeft') prevLightbox()
    if (e.key === 'ArrowRight')nextLightbox()
}

onMounted(() => {
    window.addEventListener('keydown', handleKeydown)

    gsap.fromTo('.gallery-hero-content', { opacity: 0, y: 40 }, { opacity: 1, y: 0, duration: 0.9, ease: 'power3.out' })

    gsap.utils.toArray('.gallery-item').forEach((el, i) => {
        gsap.fromTo(el,
            { opacity: 0, scale: 0.92 },
            {
                opacity: 1, scale: 1, duration: 0.5, delay: (i % 4) * 0.08,
                ease: 'power2.out',
                scrollTrigger: { trigger: el, start: 'top 90%', once: true },
            }
        )
    })
})

import { onUnmounted } from 'vue'
onUnmounted(() => window.removeEventListener('keydown', handleKeydown))
</script>

<template>
    <Head title="Galerie — Nos Créations" />

    <!-- Hero -->
    <section class="relative py-24" style="background: linear-gradient(135deg, #0d0d1a, #1a1a2e);">
        <div class="absolute inset-0 opacity-15"
             style="background: radial-gradient(circle at 70% 40%, #c4956a, transparent 50%);" />

        <div class="container-brand gallery-hero-content relative z-10 text-center">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full mb-5 text-xs font-semibold uppercase tracking-widest font-poppins"
                 style="background: rgba(196,149,106,0.15); color: #c4956a;">
                <PhImages :size="14" />
                Notre galerie
            </div>
            <h1 class="font-cormorant text-5xl md:text-6xl font-bold text-white mb-4">
                L'excellence en images
            </h1>
            <p class="text-lg text-white/60 font-poppins max-w-xl mx-auto">
                {{ total }} créations uniques. Chaque tresse raconte une histoire.
            </p>
        </div>
    </section>

    <!-- Filtres -->
    <section class="sticky top-[72px] z-20 py-4 border-b"
             style="background: rgba(250,247,244,0.97); backdrop-filter: blur(12px); border-color: rgba(26,26,46,0.08);">
        <div class="container-brand flex items-center gap-3 overflow-x-auto">
            <div class="flex items-center gap-2 mr-2 text-xs font-semibold text-onyx-400 uppercase tracking-widest font-poppins shrink-0">
                <PhFunnel :size="14" />
            </div>
            <button @click="selectedCategory = null"
                    class="shrink-0 px-4 py-2 rounded-xl text-sm font-medium font-poppins transition-all"
                    :class="!selectedCategory ? 'text-white shadow-float' : 'text-onyx-500 bg-cream-200'"
                    :style="!selectedCategory ? 'background: linear-gradient(135deg, #c4956a, #b07d52);' : ''">
                Tout ({{ total }})
            </button>
            <button v-for="cat in categories" :key="cat.value"
                    @click="selectedCategory = selectedCategory === cat.value ? null : cat.value"
                    class="shrink-0 px-4 py-2 rounded-xl text-sm font-medium font-poppins transition-all"
                    :class="selectedCategory === cat.value ? 'text-white shadow-float' : 'text-onyx-500 bg-cream-200'"
                    :style="selectedCategory === cat.value ? 'background: linear-gradient(135deg, #c4956a, #b07d52);' : ''">
                {{ cat.label }} ({{ cat.count }})
            </button>
        </div>
    </section>

    <!-- Galerie masonry -->
    <section class="py-12" style="background: #faf7f4;">
        <div class="container-brand">

            <!-- Grille -->
            <div class="columns-2 md:columns-3 xl:columns-4 gap-4 space-y-4">
                <div v-for="(img, index) in filtered" :key="img.id"
                     class="gallery-item break-inside-avoid group relative cursor-pointer overflow-hidden rounded-2xl shadow-card hover:shadow-float transition-all duration-500 hover:-translate-y-1"
                     @click="openLightbox(index)">

                    <!-- Image -->
                    <div class="relative overflow-hidden">
                        <div class="w-full transition-transform duration-700 group-hover:scale-108"
                             :style="`background-color: #1a1a2e; background-image: url('${img.thumbnail_url}'); background-size: cover; background-position: center; padding-bottom: ${img.is_featured ? '133%' : '100%'};`">
                        </div>

                        <!-- Overlay hover -->
                        <div class="absolute inset-0 flex flex-col justify-end p-4 opacity-0 group-hover:opacity-100 transition-all duration-300"
                             style="background: linear-gradient(to top, rgba(13,13,26,0.85), rgba(13,13,26,0.2) 60%, transparent);">
                            <div class="translate-y-3 group-hover:translate-y-0 transition-transform duration-300">
                                <p v-if="img.title" class="text-white text-sm font-semibold font-poppins leading-snug">
                                    {{ img.title }}
                                </p>
                                <p class="text-white/60 text-xs font-poppins capitalize mt-0.5">{{ img.category }}</p>
                            </div>

                            <!-- Icône zoom -->
                            <div class="absolute top-4 right-4 w-9 h-9 rounded-xl flex items-center justify-center translate-y-2 opacity-0 group-hover:translate-y-0 group-hover:opacity-100 transition-all duration-300"
                                 style="background: rgba(196,149,106,0.9);">
                                <PhMagnifyingGlass :size="16" class="text-white" />
                            </div>
                        </div>

                        <!-- Badge featured -->
                        <div v-if="img.is_featured"
                             class="absolute top-3 left-3 w-6 h-6 rounded-full flex items-center justify-center"
                             style="background: rgba(212,175,55,0.9);">
                            <PhBridge :size="12" class="text-white" weight="bold" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- État vide -->
            <div v-if="!filtered.length" class="py-24 text-center">
                <PhImages :size="48" class="text-onyx-200 mx-auto mb-4" />
                <p class="text-onyx-400 font-poppins">Aucune image dans cette catégorie.</p>
            </div>
        </div>
    </section>

    <!-- ── Lightbox ─────────────────────────────────── -->
    <Teleport to="body">
        <div v-if="lightboxOpen"
             class="fixed inset-0 z-[100] flex items-center justify-center"
             style="background: rgba(0,0,0,0.95); backdrop-filter: blur(8px);"
             @click.self="closeLightbox">

            <div class="lightbox-inner relative w-full max-w-4xl mx-4 flex flex-col items-center">

                <!-- Fermer -->
                <button @click="closeLightbox"
                        class="absolute -top-12 right-0 w-10 h-10 rounded-xl flex items-center justify-center text-white/60 hover:text-white hover:bg-white/10 transition-all">
                    <PhX :size="22" />
                </button>

                <!-- Image -->
                <div class="w-full max-h-[75vh] rounded-2xl overflow-hidden">
                    <div class="w-full h-full flex items-center justify-center"
                         style="min-height: 400px; background: #1a1a2e;">
                        <PhBridge :size="80" class="text-cognac-400 opacity-20" />
                    </div>
                </div>

                <!-- Infos -->
                <div v-if="currentLightbox?.title || currentLightbox?.category"
                     class="mt-4 text-center">
                    <p v-if="currentLightbox?.title" class="text-white font-semibold font-poppins">{{ currentLightbox.title }}</p>
                    <p class="text-white/50 text-sm font-poppins capitalize">{{ currentLightbox?.category }}</p>
                </div>

                <!-- Navigation -->
                <div class="flex items-center gap-6 mt-6">
                    <button @click="prevLightbox"
                            class="w-12 h-12 rounded-2xl flex items-center justify-center text-white transition-all hover:bg-white/10"
                            style="border: 1px solid rgba(255,255,255,0.15);">
                        <PhArrowLeft :size="20" />
                    </button>
                    <span class="text-white/50 text-sm font-poppins">
                        {{ lightboxIndex + 1 }} / {{ lightboxImages.length }}
                    </span>
                    <button @click="nextLightbox"
                            class="w-12 h-12 rounded-2xl flex items-center justify-center text-white transition-all hover:bg-white/10"
                            style="border: 1px solid rgba(255,255,255,0.15);">
                        <PhArrowRight :size="20" />
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>