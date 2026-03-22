<script setup>
import { ref, onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { gsap } from 'gsap'
import { ScrollTrigger } from 'gsap/ScrollTrigger'
import PublicLayout from '@/Layouts/PublicLayout.vue'
import {
    PhCalendarCheck, PhArrowRight, PhStar, PhStarHalf,
    PhShoppingBag, PhBridge, PhSparkle, PhQuotes,
    PhPlay, PhArrowUpRight, PhUsers, PhTrophy,
    PhShieldCheck, PhClock, PhCheck,
} from '@phosphor-icons/vue'

gsap.registerPlugin(ScrollTrigger)

defineOptions({ layout: PublicLayout })

const props = defineProps({
    featuredServices: Array,
    galleryFeatured:  Array,
    reviews:          Array,
    stats:            Object,
    settings:         Object,
})

onMounted(() => {
    // Hero animations
    const heroTl = gsap.timeline({ defaults: { ease: 'power3.out' } })
    heroTl
        .fromTo('.hero-badge',  { opacity: 0, scale: 0.8, y: 20 }, { opacity: 1, scale: 1, y: 0, duration: 0.7 })
        .fromTo('.hero-title',  { opacity: 0, y: 40 },              { opacity: 1, y: 0, duration: 1 }, '-=0.4')
        .fromTo('.hero-sub',    { opacity: 0, y: 20 },              { opacity: 1, y: 0, duration: 0.7 }, '-=0.6')
        .fromTo('.hero-ctas',   { opacity: 0, y: 20 },              { opacity: 1, y: 0, duration: 0.6 }, '-=0.4')
        .fromTo('.hero-stats',  { opacity: 0, y: 20 },              { opacity: 1, y: 0, duration: 0.6 }, '-=0.3')
        .fromTo('.hero-image-main', { opacity: 0, scale: 0.9, x: 40 }, { opacity: 1, scale: 1, x: 0, duration: 1.2, ease: 'power2.out' }, '-=1.2')

    // Floating elements
    gsap.to('.float-card-1', { y: -16, duration: 3.5, repeat: -1, yoyo: true, ease: 'sine.inOut' })
    gsap.to('.float-card-2', { y: 12, duration: 4, repeat: -1, yoyo: true, ease: 'sine.inOut', delay: 1 })
    gsap.to('.float-orb-hero', { scale: 1.1, duration: 5, repeat: -1, yoyo: true, ease: 'sine.inOut' })

    // Scroll animations
    gsap.utils.toArray('.reveal-up').forEach((el) => {
        gsap.fromTo(el,
            { opacity: 0, y: 50 },
            {
                opacity: 1, y: 0, duration: 0.8, ease: 'power3.out',
                scrollTrigger: {
                    trigger: el,
                    start: 'top 85%',
                    once: true,
                },
            }
        )
    })

    gsap.utils.toArray('.reveal-left').forEach((el) => {
        gsap.fromTo(el,
            { opacity: 0, x: -40 },
            {
                opacity: 1, x: 0, duration: 0.8, ease: 'power3.out',
                scrollTrigger: { trigger: el, start: 'top 85%', once: true },
            }
        )
    })

    gsap.utils.toArray('.reveal-right').forEach((el) => {
        gsap.fromTo(el,
            { opacity: 0, x: 40 },
            {
                opacity: 1, x: 0, duration: 0.8, ease: 'power3.out',
                scrollTrigger: { trigger: el, start: 'top 85%', once: true },
            }
        )
    })

    gsap.utils.toArray('.stagger-children').forEach((el) => {
        gsap.fromTo(el.children,
            { opacity: 0, y: 30 },
            {
                opacity: 1, y: 0, duration: 0.6, stagger: 0.12, ease: 'power3.out',
                scrollTrigger: { trigger: el, start: 'top 80%', once: true },
            }
        )
    })

    // Stats counter
    gsap.utils.toArray('.stat-number').forEach((el) => {
        const target = parseInt(el.dataset.target)
        scrollTrigger: ScrollTrigger.create({
            trigger: el,
            start: 'top 80%',
            once: true,
            onEnter: () => {
                gsap.fromTo({ val: 0 }, { val: target, duration: 2, ease: 'power2.out',
                    onUpdate: function() {
                        el.textContent = Math.round(this.targets()[0].val).toLocaleString('fr-FR')
                    }
                })
            },
        })
    })
})

function renderStars(rating) {
    return Array.from({ length: 5 }, (_, i) => ({
        full:  i + 1 <= Math.floor(rating),
        half:  i + 1 === Math.ceil(rating) && ! Number.isInteger(rating),
        empty: i + 1 > Math.ceil(rating),
    }))
}

const formatPrice = (p) => new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(p)
</script>

<template>
    <Head title="Studio de Tresses Premium — Accueil" />

    <!-- ════════════════════════════════════════════ -->
    <!-- HERO                                         -->
    <!-- ════════════════════════════════════════════ -->
    <section class="relative min-h-screen flex items-center overflow-hidden bg-onyx-900">

        <!-- Arrière-plan gradient -->
        <div class="absolute inset-0"
             style="background: linear-gradient(135deg, #0d0d1a 0%, #1a1a2e 40%, #0f0f20 100%);" />

        <!-- Orbes décoratives -->
        <div class="float-orb-hero absolute top-1/4 right-1/4 w-96 h-96 rounded-full opacity-20 blur-3xl"
             style="background: radial-gradient(circle, #c4956a, transparent);" />
        <div class="absolute bottom-1/3 left-1/5 w-72 h-72 rounded-full opacity-10 blur-3xl"
             style="background: radial-gradient(circle, #d4af37, transparent);" />
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full h-full opacity-5"
             style="background-image: radial-gradient(ellipse at center, rgba(196,149,106,0.3) 0%, transparent 70%);" />

        <!-- Grille subtile -->
        <div class="absolute inset-0 opacity-[0.03]"
             style="background-image: linear-gradient(rgba(196,149,106,1) 1px, transparent 1px), linear-gradient(90deg, rgba(196,149,106,1) 1px, transparent 1px); background-size: 64px 64px;" />

        <div class="container-brand relative z-10 py-24">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">

                <!-- Texte -->
                <div class="space-y-8">

                    <!-- Badge -->
                    <div class="hero-badge inline-flex items-center gap-2 px-4 py-2 rounded-full border font-poppins text-xs font-semibold tracking-widest uppercase"
                         style="background: rgba(196,149,106,0.12); border-color: rgba(196,149,106,0.3); color: #c4956a;">
                        <PhSparkle :size="14" weight="fill" />
                        Studio de tresses premium à Paris
                    </div>

                    <!-- Titre -->
                    <h1 class="hero-title font-cormorant text-5xl md:text-6xl xl:text-7xl font-bold text-white leading-[1.05]">
                        L'art des tresses,<br>
                        <span class="text-gradient-gold">sublimé</span>
                        <span class="text-white"> pour vous</span>
                    </h1>

                    <!-- Description -->
                    <p class="hero-sub text-lg text-white/60 font-poppins leading-relaxed max-w-lg">
                        Découvrez une expérience capillaire d'exception. Chaque création est une signature, chaque séance une parenthèse de bien-être.
                    </p>

                    <!-- CTAs -->
                    <div class="hero-ctas flex flex-wrap items-center gap-4">
                        <Link :href="route('booking.services')" class="btn-primary py-4 px-8 text-base">
                            <PhCalendarCheck :size="20" weight="bold" />
                            Réserver ma séance
                            <PhArrowRight :size="18" />
                        </Link>
                        <Link :href="route('gallery.index')"
                              class="flex items-center gap-2.5 text-sm font-semibold font-poppins transition-all hover:gap-3"
                              style="color: rgba(255,255,255,0.6);">
                            Voir la galerie
                            <PhArrowUpRight :size="16" />
                        </Link>
                    </div>

                    <!-- Stats -->
                    <div class="hero-stats flex items-center gap-8 pt-4"
                         style="border-top: 1px solid rgba(255,255,255,0.08);">
                        <div v-for="(stat, label) in {
                            [`${stats.clients}+`]:       'Clientes satisfaites',
                            [`${stats.appointments}+`]:  'Séances réalisées',
                            [`${stats.avg_rating}/5`]:   'Note moyenne',
                        }" :key="label">
                            <p class="font-cormorant text-2xl font-bold text-white">{{ stat }}</p>
                            <p class="text-xs text-white/40 font-poppins mt-0.5">{{ label }}</p>
                        </div>
                    </div>
                </div>

                <!-- Visuel héro -->
                <div class="hero-image-main relative hidden lg:flex items-center justify-center">
                    <div class="relative w-full max-w-md">

                        <!-- Image principale -->
                        <div class="relative z-10 rounded-3xl overflow-hidden shadow-luxury"
                             style="aspect-ratio: 3/4;">
                            <div class="w-full h-full"
                                 style="background: linear-gradient(135deg, #c4956a20, #d4af3715), url('/assets/img/boho_banner.jpg') center/cover no-repeat; min-height: 480px;">
                                <!-- Fallback gradient si pas d'image -->
                                <div class="absolute inset-0 flex items-center justify-center"
                                     style="background: linear-gradient(135deg, #1a1a2e, #2d2d4a);">
                                    <div class="text-center">
                                        <PhBridge :size="80" class="text-cognac-400 mx-auto mb-4 opacity-40" />
                                        <p class="text-white/30 text-sm font-poppins">Photo du studio</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Carte flottante 1 — RDV du jour -->
                        <div class="float-card-1 absolute -left-12 top-1/4 z-20 glass-dark rounded-2xl p-4 shadow-luxury">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                                     style="background: rgba(196,149,106,0.2);">
                                    <PhCalendarCheck :size="20" class="text-cognac-400" />
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-white font-poppins">Prochain RDV</p>
                                    <p class="text-xs text-white/50 font-poppins">Disponible dès demain</p>
                                </div>
                            </div>
                        </div>

                        <!-- Carte flottante 2 — Note -->
                        <div class="float-card-2 absolute -right-10 bottom-1/4 z-20 glass-dark rounded-2xl p-4 shadow-luxury">
                            <div class="flex items-center gap-2.5">
                                <div class="flex">
                                    <PhStar v-for="i in 5" :key="i" :size="16" weight="fill"
                                            class="text-gold-400" />
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-white font-poppins">{{ stats.avg_rating }}/5</p>
                                    <p class="text-xs text-white/40 font-poppins">{{ stats.appointments }} avis</p>
                                </div>
                            </div>
                        </div>

                        <!-- Cercle décoratif -->
                        <div class="absolute -inset-6 rounded-3xl -z-10 opacity-30"
                             style="background: conic-gradient(from 0deg, #c4956a, #d4af37, #c4956a); animation: spin-slow 12s linear infinite;" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Scroll indicator -->
        <div class="absolute bottom-8 left-1/2 -translate-x-1/2 flex flex-col items-center gap-2 animate-bounce">
            <div class="w-5 h-8 rounded-full border-2 border-white/20 flex items-start justify-center p-1">
                <div class="w-1 h-2 rounded-full bg-white/40" style="animation: float 1.5s ease-in-out infinite;" />
            </div>
        </div>
    </section>

    <!-- ════════════════════════════════════════════ -->
    <!-- GARANTIES                                    -->
    <!-- ════════════════════════════════════════════ -->
    <section class="py-12 bg-white">
        <div class="container-brand">
            <div class="stagger-children grid grid-cols-2 md:grid-cols-4 gap-6">
                <div v-for="feat in [
                    { icon: PhShieldCheck, title: 'Produits certifiés', sub: 'Extensions et soins premium' },
                    { icon: PhClock,       title: 'Horaires flexibles', sub: 'Du lundi au samedi' },
                    { icon: PhUsers,       title: 'Sur rendez-vous',    sub: 'Expérience personnalisée' },
                    { icon: PhTrophy,      title: '5 ans d\'expertise', sub: 'Maitrise certifiée' },
                ]" :key="feat.title"
                     class="flex items-center gap-3 p-4 rounded-2xl"
                     style="background: #faf7f4; border: 1px solid #edddd0;">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                         style="background: rgba(196,149,106,0.12);">
                        <component :is="feat.icon" :size="20" class="text-cognac-500" />
                    </div>
                    <div>
                        <p class="text-sm font-semibold font-poppins text-onyx-800">{{ feat.title }}</p>
                        <p class="text-xs text-onyx-400 font-poppins">{{ feat.sub }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ════════════════════════════════════════════ -->
    <!-- SERVICES PHARES                              -->
    <!-- ════════════════════════════════════════════ -->
    <section class="py-24 bg-cream-100">
        <div class="container-brand">

            <!-- Header section -->
            <div class="reveal-up text-center max-w-2xl mx-auto mb-16">
                <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full mb-4 text-xs font-semibold uppercase tracking-widest font-poppins"
                     style="background: rgba(196,149,106,0.12); color: #c4956a;">
                    <PhBridge :size="14" />
                    Nos prestations
                </div>
                <h2 class="font-cormorant text-4xl md:text-5xl font-bold text-onyx-800">
                    Des créations taillées<br>
                    <span class="text-gradient-brand">pour votre unicité</span>
                </h2>
                <p class="mt-4 text-base text-onyx-500 font-poppins leading-relaxed">
                    Chaque tresse est un art. Découvrez nos prestations signatures, réalisées avec les meilleures techniques et produits.
                </p>
            </div>

            <!-- Grille services -->
            <div class="stagger-children grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                <div v-for="service in featuredServices" :key="service.id"
                     class="group bg-white rounded-3xl overflow-hidden shadow-card hover:shadow-float transition-all duration-500 hover:-translate-y-1.5 cursor-pointer">

                    <!-- Image / Placeholder -->
                    <div class="relative overflow-hidden"
                         style="aspect-ratio: 4/3;">
                        <div v-if="service.image_url && !service.image_url.includes('placeholder')"
                             class="w-full h-full bg-cover bg-center transition-transform duration-700 group-hover:scale-110"
                             :style="`background-image: url('${service.image_url}')`" />
                        <div v-else
                             class="w-full h-full flex items-center justify-center transition-transform duration-500 group-hover:scale-105"
                             style="background: linear-gradient(135deg, #1a1a2e, #2d2d4a);">
                            <PhBridge :size="48" class="text-cognac-400 opacity-50" />
                        </div>

                        <!-- Overlay gradient -->
                        <div class="absolute inset-0"
                             style="background: linear-gradient(to top, rgba(13,13,26,0.6) 0%, transparent 60%);" />

                        <!-- Badge catégorie -->
                        <div class="absolute top-4 left-4 px-3 py-1.5 rounded-xl text-xs font-semibold font-poppins"
                             style="background: rgba(13,13,26,0.7); color: #c4956a; backdrop-filter: blur(8px);">
                            {{ service.category_label }}
                        </div>

                        <!-- Durée -->
                        <div class="absolute top-4 right-4 px-3 py-1.5 rounded-xl text-xs font-semibold font-poppins flex items-center gap-1.5"
                             style="background: rgba(13,13,26,0.7); color: white; backdrop-filter: blur(8px);">
                            <PhClock :size="12" />
                            {{ service.duration_formatted }}
                        </div>

                        <!-- Prix -->
                        <div class="absolute bottom-4 right-4">
                            <span class="text-xl font-bold text-white font-cormorant">{{ formatPrice(service.price) }}</span>
                        </div>
                    </div>

                    <!-- Contenu -->
                    <div class="p-6">
                        <div class="flex items-start justify-between gap-3 mb-3">
                            <h3 class="font-cormorant text-xl font-bold text-onyx-800 group-hover:text-cognac-700 transition-colors">
                                {{ service.name }}
                            </h3>

                            <!-- Rating -->
                            <div v-if="service.avg_rating" class="flex items-center gap-1 shrink-0">
                                <PhStar :size="14" weight="fill" class="text-gold-400" />
                                <span class="text-xs font-semibold font-poppins text-onyx-600">{{ service.avg_rating }}</span>
                            </div>
                        </div>

                        <p class="text-sm text-onyx-400 font-poppins leading-relaxed line-clamp-2 mb-4">
                            {{ service.short_description }}
                        </p>

                        <!-- Acompte -->
                        <div v-if="service.deposit_required"
                             class="flex items-center gap-2 text-xs text-cognac-600 font-poppins mb-4">
                            <PhCheck :size="14" weight="bold" />
                            Acompte {{ formatPrice(service.deposit_amount) }} requis
                        </div>

                        <Link :href="route('booking.show', service.slug)"
                              class="flex items-center justify-between w-full px-4 py-3 rounded-xl text-sm font-semibold font-poppins transition-all duration-200 group/btn"
                              style="background: rgba(196,149,106,0.1); color: #b07d52;">
                            <span>Réserver ce soin</span>
                            <PhArrowRight :size="16" class="transition-transform group-hover/btn:translate-x-1" />
                        </Link>
                    </div>
                </div>
            </div>

            <!-- CTA voir tous -->
            <div class="reveal-up text-center mt-12">
                <Link :href="route('booking.services')" class="btn-secondary py-3.5 px-8">
                    Voir toutes les prestations
                    <PhArrowRight :size="18" />
                </Link>
            </div>
        </div>
    </section>

    <!-- ════════════════════════════════════════════ -->
    <!-- GALERIE PREVIEW                              -->
    <!-- ════════════════════════════════════════════ -->
    <section class="py-24 overflow-hidden" style="background: #0d0d1a;">
        <div class="container-brand">

            <div class="reveal-up flex flex-col md:flex-row items-end justify-between gap-8 mb-12">
                <div class="max-w-lg">
                    <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full mb-4 text-xs font-semibold uppercase tracking-widest font-poppins"
                         style="background: rgba(196,149,106,0.12); color: #c4956a;">
                        <PhSparkle :size="14" />
                        Nos créations
                    </div>
                    <h2 class="font-cormorant text-4xl md:text-5xl font-bold text-white">
                        L'excellence en images
                    </h2>
                    <p class="mt-3 text-base text-white/50 font-poppins leading-relaxed">
                        Chaque photo raconte une histoire unique. Explorez notre galerie de créations.
                    </p>
                </div>
                <Link :href="route('gallery.index')"
                      class="shrink-0 flex items-center gap-2 text-sm font-semibold font-poppins transition-all hover:gap-3"
                      style="color: #c4956a;">
                    Voir la galerie complète
                    <PhArrowRight :size="16" />
                </Link>
            </div>

            <!-- Masonry gallery preview -->
            <div v-if="galleryFeatured.length" class="reveal-up grid grid-cols-2 md:grid-cols-4 gap-3">
                <div v-for="(img, i) in galleryFeatured.slice(0, 8)" :key="img.id"
                     class="group relative overflow-hidden rounded-2xl cursor-pointer"
                     :class="i === 0 || i === 5 ? 'row-span-2' : ''"
                     :style="`aspect-ratio: ${(i === 0 || i === 5) ? '1/2' : '1/1'}`">

                    <div class="w-full h-full bg-cover bg-center transition-transform duration-700 group-hover:scale-110"
                         :style="`background-image: url('${img.thumbnail_url}'); background-color: #1a1a2e;`">
                        <div class="w-full h-full flex items-center justify-center"
                             style="background: linear-gradient(135deg, #1a1a2e80, #2d2d4a80);">
                            <PhBridge :size="32" class="text-cognac-400 opacity-30" />
                        </div>
                    </div>

                    <!-- Overlay hover -->
                    <div class="absolute inset-0 flex items-end p-4 opacity-0 group-hover:opacity-100 transition-all duration-300"
                         style="background: linear-gradient(to top, rgba(13,13,26,0.8), transparent);">
                        <div>
                            <p v-if="img.title" class="text-white text-sm font-semibold font-poppins">{{ img.title }}</p>
                            <p class="text-white/60 text-xs font-poppins capitalize">{{ img.category }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fallback si pas d'images -->
            <div v-else class="reveal-up flex items-center justify-center py-16 rounded-3xl"
                 style="border: 1px dashed rgba(196,149,106,0.3);">
                <div class="text-center">
                    <PhBridge :size="48" class="text-cognac-400 mx-auto mb-4 opacity-40" />
                    <p class="text-white/40 font-poppins text-sm">Galerie en cours de constitution</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ════════════════════════════════════════════ -->
    <!-- SECTION WHY US                              -->
    <!-- ════════════════════════════════════════════ -->
    <section class="py-24 bg-white">
        <div class="container-brand">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">

                <!-- Visuel -->
                <div class="reveal-left relative">
                    <div class="relative rounded-3xl overflow-hidden shadow-luxury"
                         style="aspect-ratio: 4/5; background: linear-gradient(135deg, #c4956a20, #1a1a2e);">
                        <div class="absolute inset-0 flex items-center justify-center">
                            <PhBridge :size="100" class="text-cognac-400 opacity-20" />
                        </div>

                        <!-- Card info -->
                        <div class="absolute bottom-6 left-6 right-6 glass-card rounded-2xl p-5">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-10 h-10 rounded-xl overflow-hidden shrink-0"
                                     style="background: linear-gradient(135deg, #c4956a, #d4af37);">
                                    <PhBridge :size="22" class="text-white m-auto mt-2.5" />
                                </div>
                                <div>
                                    <p class="text-sm font-semibold font-poppins text-onyx-800">Patricia</p>
                                    <p class="text-xs text-onyx-500 font-poppins">Fondatrice & Styliste</p>
                                </div>
                            </div>
                            <p class="text-xs text-onyx-600 font-poppins leading-relaxed italic">
                                "Chaque cliente mérite une expérience unique. Mon but est de vous faire rayonner."
                            </p>
                        </div>
                    </div>

                    <!-- Décoration -->
                    <div class="absolute -bottom-6 -right-6 w-48 h-48 rounded-3xl -z-10 opacity-30"
                         style="background: linear-gradient(135deg, #c4956a, #d4af37);" />
                </div>

                <!-- Texte -->
                <div class="reveal-right space-y-8">
                    <div>
                        <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full mb-4 text-xs font-semibold uppercase tracking-widest font-poppins"
                             style="background: rgba(196,149,106,0.12); color: #c4956a;">
                            Notre engagement
                        </div>
                        <h2 class="font-cormorant text-4xl md:text-5xl font-bold text-onyx-800">
                            Pourquoi choisir<br>
                            <span class="text-gradient-brand">Patricia Braids ?</span>
                        </h2>
                    </div>

                    <div class="space-y-4">
                        <div v-for="item in [
                            { icon: PhShieldCheck, title: 'Expertise certifiée',    desc: 'Plus de 5 ans de formation et pratique dans les meilleures techniques de tressage africain et afro-caribéen.' },
                            { icon: PhSparkle,     title: 'Produits premium',        desc: 'Extensions de qualité supérieure, soins sans sulfate, accessoires certifiés hypoallergéniques.' },
                            { icon: PhUsers,       title: 'Approche personnalisée',  desc: 'Chaque coiffure est étudiée selon la morphologie, la texture et les envies de chaque cliente.' },
                            { icon: PhClock,       title: 'Ponctualité garantie',    desc: 'Chaque séance commence et se termine à l\'heure. Votre temps est précieux, nous le respectons.' },
                        ]" :key="item.title"
                             class="flex items-start gap-4 p-4 rounded-2xl transition-all hover:shadow-card"
                             style="border: 1px solid #f5ede4;">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 mt-0.5"
                                 style="background: rgba(196,149,106,0.1);">
                                <component :is="item.icon" :size="20" class="text-cognac-500" />
                            </div>
                            <div>
                                <p class="font-semibold text-sm font-poppins text-onyx-800 mb-1">{{ item.title }}</p>
                                <p class="text-sm text-onyx-400 font-poppins leading-relaxed">{{ item.desc }}</p>
                            </div>
                        </div>
                    </div>

                    <Link :href="route('booking.services')" class="btn-primary inline-flex py-4 px-8">
                        <PhCalendarCheck :size="20" weight="bold" />
                        Commencer mon expérience
                        <PhArrowRight :size="18" />
                    </Link>
                </div>
            </div>
        </div>
    </section>

    <!-- ════════════════════════════════════════════ -->
    <!-- AVIS CLIENTS                                 -->
    <!-- ════════════════════════════════════════════ -->
    <section v-if="reviews.length" class="py-24" style="background: #faf7f4;">
        <div class="container-brand">

            <div class="reveal-up text-center max-w-xl mx-auto mb-14">
                <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full mb-4 text-xs font-semibold uppercase tracking-widest font-poppins"
                     style="background: rgba(196,149,106,0.12); color: #c4956a;">
                    <PhStar :size="14" weight="fill" />
                    Témoignages
                </div>
                <h2 class="font-cormorant text-4xl md:text-5xl font-bold text-onyx-800">
                    Ce qu'elles disent de nous
                </h2>
                <p class="mt-3 text-base text-onyx-500 font-poppins">
                    La satisfaction de nos clientes est notre plus belle récompense.
                </p>
            </div>

            <!-- Grille avis -->
            <div class="stagger-children grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                <div v-for="review in reviews" :key="review.id"
                     class="bg-white rounded-3xl p-7 shadow-card hover:shadow-float transition-all duration-300 hover:-translate-y-1 flex flex-col">

                    <!-- Quote icon -->
                    <PhQuotes :size="32" weight="fill" class="text-cognac-200 mb-4 shrink-0" />

                    <!-- Stars -->
                    <div class="flex gap-0.5 mb-4">
                        <template v-for="(star, i) in renderStars(review.rating)" :key="i">
                            <PhStar v-if="star.full"     :size="16" weight="fill" class="text-gold-400" />
                            <PhStarHalf v-if="star.half" :size="16" weight="fill" class="text-gold-400" />
                            <PhStar v-if="star.empty"    :size="16" class="text-cream-300" />
                        </template>
                    </div>

                    <!-- Titre -->
                    <p v-if="review.title" class="font-semibold text-sm font-poppins text-onyx-800 mb-2">
                        {{ review.title }}
                    </p>

                    <!-- Commentaire -->
                    <p class="text-sm text-onyx-500 font-poppins leading-relaxed flex-1 line-clamp-4">
                        {{ review.comment }}
                    </p>

                    <!-- Service tag -->
                    <div v-if="review.service"
                         class="mt-4 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-poppins font-medium"
                         style="background: rgba(196,149,106,0.1); color: #b07d52;">
                        <PhBridge :size="12" />
                        {{ review.service.name }}
                    </div>

                    <!-- Client -->
                    <div class="flex items-center gap-3 mt-5 pt-4"
                         style="border-top: 1px solid #f5ede4;">
                        <img :src="review.client.avatar_url" :alt="review.client.name"
                             class="w-9 h-9 rounded-full object-cover shrink-0" />
                        <div>
                            <p class="text-sm font-semibold font-poppins text-onyx-700">{{ review.client.name }}</p>
                            <p class="text-xs text-onyx-400 font-poppins">{{ review.date }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ════════════════════════════════════════════ -->
    <!-- CTA FINAL                                    -->
    <!-- ════════════════════════════════════════════ -->
    <section class="py-24 relative overflow-hidden" style="background: linear-gradient(135deg, #0d0d1a, #1a1a2e);">

        <!-- Décoration -->
        <div class="absolute inset-0 opacity-10"
             style="background-image: radial-gradient(circle at 30% 50%, #c4956a 0%, transparent 50%), radial-gradient(circle at 70% 50%, #d4af37 0%, transparent 50%);" />

        <div class="container-brand relative z-10 text-center">
            <div class="reveal-up max-w-2xl mx-auto space-y-8">
                <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-xs font-semibold uppercase tracking-widest font-poppins"
                     style="background: rgba(196,149,106,0.12); color: #c4956a;">
                    <PhSparkle :size="14" />
                    Votre transformation commence ici
                </div>

                <h2 class="font-cormorant text-4xl md:text-6xl font-bold text-white">
                    Prête à vous sublimer ?
                </h2>

                <p class="text-lg text-white/60 font-poppins leading-relaxed">
                    Réservez votre séance dès maintenant et vivez une expérience capillaire hors du commun.
                </p>

                <div class="flex flex-wrap items-center justify-center gap-4">
                    <Link :href="route('booking.services')"
                          class="btn-primary py-5 px-10 text-base">
                        <PhCalendarCheck :size="22" weight="bold" />
                        Réserver ma séance
                        <PhArrowRight :size="20" />
                    </Link>
                    <Link :href="route('contact.show')"
                          class="btn-secondary py-5 px-10 text-base text-white border-white/20 hover:border-white/50 hover:bg-white/5">
                        Nous contacter
                    </Link>
                </div>

                <!-- Rassurance -->
                <div class="flex flex-wrap items-center justify-center gap-6 pt-2">
                    <div v-for="item in ['Annulation 48h à l\'avance', 'Acompte sécurisé', 'Réponse sous 24h']"
                         :key="item"
                         class="flex items-center gap-2 text-sm text-white/50 font-poppins">
                        <PhCheck :size="16" weight="bold" class="text-cognac-400" />
                        {{ item }}
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>