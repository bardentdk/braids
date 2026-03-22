<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { gsap } from 'gsap'
import { ScrollTrigger } from 'gsap/ScrollTrigger'
import PublicLayout from '@/Layouts/PublicLayout.vue'
import {
    PhClock, PhArrowRight, PhTag, PhMagnifyingGlass,
    PhSparkle, PhBooks, PhX,
} from '@phosphor-icons/vue'

gsap.registerPlugin(ScrollTrigger)
defineOptions({ layout: PublicLayout })

const props = defineProps({
    posts:      Object,
    categories: Array,
    hero:       Object,
    filters:    Object,
    meta:       Object,
})

// ── Filtres ────────────────────────────────────────────────────────
const search         = ref(props.filters?.search ?? '')
const activeCategory = ref(props.filters?.category ?? '')

function filterByCategory(slug) {
    activeCategory.value = activeCategory.value === slug ? '' : slug
    applyFilters()
}

let searchTimer = null
function onSearchInput() {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(applyFilters, 450)
}

function clearSearch() {
    search.value = ''
    applyFilters()
}

function applyFilters() {
    router.get(route('blog.index'), {
        category: activeCategory.value || undefined,
        search:   search.value || undefined,
    }, { preserveState: true, replace: true })
}

// ── Disposition Bento ──────────────────────────────────────────────
// On assigne des tailles aux cartes pour créer un effet Bento varié
function getBentoClass(index, post) {
    if (post.is_featured || post.is_pinned) return 'bento-large'
    if (index % 7 === 1) return 'bento-wide'
    if (index % 7 === 3) return 'bento-tall'
    return 'bento-normal'
}

onMounted(() => {
    // Animation d'entrée des cartes au scroll
    gsap.utils.toArray('.bento-card').forEach((card, i) => {
        gsap.fromTo(card,
            { opacity: 0, y: 30, scale: 0.97 },
            {
                opacity: 1, y: 0, scale: 1,
                duration: 0.55,
                delay: i * 0.05,
                ease: 'power3.out',
                scrollTrigger: {
                    trigger: card,
                    start: 'top 90%',
                    toggleActions: 'play none none none',
                }
            }
        )
    })
})

function formatDate(date) { return date }
</script>

<template>
    <Head>
        <title>{{ meta.title }}</title>
        <meta name="description" :content="meta.description" />
        <meta property="og:title" :content="meta.title" />
        <meta property="og:description" :content="meta.description" />
        <meta property="og:image" :content="meta.og_image" />
        <meta property="og:type" content="website" />
        <link rel="canonical" :href="route('blog.index')" />
    </Head>

    <!-- ── Hero ───────────────────────────────────────────────────── -->
    <section class="relative overflow-hidden py-10" style="background: #0d0d1a; min-height: 420px;">
        <!-- Background pattern -->
        <div class="absolute inset-0 opacity-5"
             style="background-image: url('data:image/svg+xml,...'); background-size: 40px;"></div>

        <!-- Image si hero disponible -->
        <div v-if="hero?.cover_image_url"
             class="absolute inset-0"
             :style="`background-image: url(${hero.cover_image_url}); background-size: cover; background-position: center; opacity: 0.2;`" />

        <div class="relative container-brand py-20 flex flex-col items-start justify-end h-full">
            <div class="max-w-2xl">
                <!-- Eyebrow -->
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-6 h-px" style="background: #c4956a;"></div>
                    <span class="text-xs font-poppins font-semibold tracking-[0.3em] uppercase" style="color: #c4956a;">
                        Le Blog
                    </span>
                </div>

                <h1 class="font-cormorant text-5xl md:text-6xl font-bold text-white mb-4 leading-tight">
                    Conseils & inspirations<br>
                    <span style="color: #c4956a;">beauté naturelle</span>
                </h1>
                <p class="text-base font-poppins text-white/60 max-w-lg leading-relaxed mb-8">
                    Découvrez nos astuces d'experts, les dernières tendances capillaires et les secrets d'entretien de vos tresses.
                </p>

                <!-- Barre de recherche hero -->
                <div class="relative max-w-md">
                    <PhMagnifyingGlass :size="16" class="absolute left-4 top-1/2 -translate-y-1/2 text-white/40" />
                    <input v-model="search" type="text"
                           placeholder="Rechercher un article…"
                           @input="onSearchInput"
                           class="w-full pl-11 pr-10 py-3.5 rounded-2xl text-sm font-poppins text-white placeholder-white/30 focus:outline-none transition-colors"
                           style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.15); backdrop-filter: blur(10px);" />
                    <button v-if="search" @click="clearSearch"
                            class="absolute right-3.5 top-1/2 -translate-y-1/2 text-white/40 hover:text-white">
                        <PhX :size="15" />
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- ── Bento Catégories ───────────────────────────────────────── -->
    <section style="background: #faf7f4;" class="py-10">
        <div class="container-brand">

            <!-- Grid catégories en Bento -->
            <div v-if="categories.length" class="mb-10">
                <div class="flex items-center gap-2 mb-5">
                    <PhBooks :size="16" class="text-cognac-500" />
                    <h2 class="font-poppins font-semibold text-onyx-800 text-sm uppercase tracking-wide">
                        Thématiques
                    </h2>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                    <!-- Carte "Tous" -->
                    <button @click="filterByCategory('')"
                            class="flex flex-col items-center justify-center gap-2 p-4 rounded-2xl text-center transition-all hover:-translate-y-0.5"
                            :class="!activeCategory
                                ? 'text-white shadow-md'
                                : 'bg-white border border-cream-200 text-onyx-600 hover:border-cognac-300 shadow-card'"
                            :style="!activeCategory ? 'background: linear-gradient(135deg, #1a1a2e, #0d0d1a);' : ''">
                        <PhBooks :size="22" :class="!activeCategory ? 'text-cognac-400' : 'text-onyx-400'" />
                        <span class="text-xs font-poppins font-semibold">Tous</span>
                        <span class="text-xs font-poppins opacity-60">{{ posts.total }} articles</span>
                    </button>

                    <!-- Cartes catégories -->
                    <button v-for="cat in categories" :key="cat.id"
                            @click="filterByCategory(cat.slug)"
                            class="flex flex-col items-center justify-center gap-2 p-4 rounded-2xl text-center transition-all hover:-translate-y-0.5"
                            :class="activeCategory === cat.slug
                                ? 'text-white shadow-md'
                                : 'bg-white border border-cream-200 text-onyx-600 hover:border-cognac-300 shadow-card'"
                            :style="activeCategory === cat.slug ? `background: ${cat.color};` : ''">
                        <PhTag :size="22"
                               :style="{ color: activeCategory === cat.slug ? 'rgba(255,255,255,0.8)' : cat.color }" />
                        <span class="text-xs font-poppins font-semibold">{{ cat.name }}</span>
                        <span class="text-xs font-poppins opacity-60">{{ cat.posts_count }}</span>
                    </button>
                </div>
            </div>

            <!-- ── BENTO GRID articles ──────────────────────────── -->
            <div v-if="posts.data.length" class="bento-grid">
                <template v-for="(post, index) in posts.data" :key="post.id">

                    <!-- Carte featured / grande ─────────────────── -->
                    <Link v-if="post.is_featured || post.is_pinned"
                          :href="route('blog.show', post.slug)"
                          class="bento-card bento-large group relative overflow-hidden rounded-3xl block"
                          style="min-height: 400px;">
                        <!-- Image bg -->
                        <div v-if="post.cover_image_url"
                             class="absolute inset-0 transition-transform duration-700 group-hover:scale-105"
                             :style="`background: url(${post.cover_image_url}) center/cover no-repeat;`" />
                        <div v-else class="absolute inset-0"
                             :style="`background: linear-gradient(135deg, ${post.category?.color ?? '#c4956a'}33, #0d0d1a);`" />

                        <!-- Overlay gradient -->
                        <div class="absolute inset-0" style="background: linear-gradient(to top, rgba(0,0,0,0.85) 0%, rgba(0,0,0,0.2) 60%, transparent 100%);" />

                        <!-- Badges -->
                        <div class="absolute top-4 left-4 flex items-center gap-2">
                            <span v-if="post.is_pinned"
                                  class="flex items-center gap-1 px-2.5 py-1 rounded-xl text-xs font-poppins font-bold text-white"
                                  style="background: rgba(196,149,106,0.9);">
                                <PhSparkle :size="11" weight="fill" /> À la une
                            </span>
                            <span v-if="post.category"
                                  class="px-2.5 py-1 rounded-xl text-xs font-poppins font-bold text-white"
                                  :style="`background: ${post.category.color}99;`">
                                {{ post.category.name }}
                            </span>
                        </div>

                        <!-- Contenu -->
                        <div class="absolute bottom-0 left-0 right-0 p-6">
                            <h2 class="font-cormorant text-2xl md:text-3xl font-bold text-white mb-2 group-hover:text-cognac-200 transition-colors leading-tight">
                                {{ post.title }}
                            </h2>
                            <p class="text-sm font-poppins text-white/60 line-clamp-2 mb-3">{{ post.excerpt }}</p>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3 text-xs font-poppins text-white/50">
                                    <span>{{ post.published_date }}</span>
                                    <span class="flex items-center gap-1">
                                        <PhClock :size="11" /> {{ post.reading_time }} min
                                    </span>
                                </div>
                                <span class="flex items-center gap-1 text-xs font-poppins text-cognac-400 font-semibold group-hover:text-cognac-200 transition-colors">
                                    Lire <PhArrowRight :size="13" />
                                </span>
                            </div>
                        </div>
                    </Link>

                    <!-- Carte large horizontale ─────────────────── -->
                    <Link v-else-if="index % 7 === 1"
                          :href="route('blog.show', post.slug)"
                          class="bento-card bento-wide group relative overflow-hidden rounded-3xl flex bg-white shadow-card hover:shadow-float transition-all duration-300 hover:-translate-y-1 block">
                        <!-- Image -->
                        <div v-if="post.cover_image_url"
                             class="w-2/5 shrink-0 overflow-hidden">
                            <img :src="post.cover_image_url" :alt="post.cover_image_alt"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
                        </div>
                        <div v-else class="w-2/5 shrink-0"
                             :style="`background: linear-gradient(135deg, ${post.category?.color ?? '#c4956a'}22, #f5ede4);`" />

                        <!-- Texte -->
                        <div class="flex-1 p-6 flex flex-col justify-between">
                            <div>
                                <span v-if="post.category"
                                      class="inline-block px-2.5 py-1 rounded-xl text-xs font-poppins font-bold mb-3"
                                      :style="`background: ${post.category.color}18; color: ${post.category.color};`">
                                    {{ post.category.name }}
                                </span>
                                <h3 class="font-cormorant text-xl font-bold text-onyx-800 group-hover:text-cognac-700 transition-colors leading-snug mb-2">
                                    {{ post.title }}
                                </h3>
                                <p class="text-xs font-poppins text-onyx-500 line-clamp-3">{{ post.excerpt }}</p>
                            </div>
                            <div class="flex items-center justify-between mt-3">
                                <span class="text-xs font-poppins text-onyx-400 flex items-center gap-1">
                                    <PhClock :size="11" /> {{ post.reading_time }} min · {{ post.published_date }}
                                </span>
                                <span class="text-xs font-poppins text-cognac-500 font-semibold flex items-center gap-1 group-hover:gap-2 transition-all">
                                    Lire <PhArrowRight :size="12" />
                                </span>
                            </div>
                        </div>
                    </Link>

                    <!-- Carte haute ──────────────────────────────── -->
                    <Link v-else-if="index % 7 === 3"
                          :href="route('blog.show', post.slug)"
                          class="bento-card bento-tall group relative overflow-hidden rounded-3xl block bg-white shadow-card hover:shadow-float transition-all duration-300 hover:-translate-y-1"
                          style="min-height: 360px;">
                        <!-- Image -->
                        <div class="h-3/5 overflow-hidden relative">
                            <img v-if="post.cover_image_url"
                                 :src="post.cover_image_url" :alt="post.cover_image_alt"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
                            <div v-else class="w-full h-full"
                                 :style="`background: linear-gradient(135deg, ${post.category?.color ?? '#c4956a'}22, #f5ede4);`" />

                            <span v-if="post.category"
                                  class="absolute top-3 left-3 px-2.5 py-1 rounded-xl text-xs font-poppins font-bold"
                                  :style="`background: ${post.category.color}; color: white;`">
                                {{ post.category.name }}
                            </span>
                        </div>
                        <div class="p-5 flex flex-col justify-between h-2/5">
                            <div>
                                <h3 class="font-cormorant text-lg font-bold text-onyx-800 group-hover:text-cognac-700 transition-colors leading-snug mb-1.5">
                                    {{ post.title }}
                                </h3>
                                <p class="text-xs font-poppins text-onyx-400 line-clamp-2">{{ post.excerpt }}</p>
                            </div>
                            <div class="flex items-center justify-between mt-2">
                                <span class="text-xs font-poppins text-onyx-400">{{ post.reading_time }} min</span>
                                <span class="text-xs font-poppins text-cognac-500 font-semibold flex items-center gap-1">
                                    Lire <PhArrowRight :size="12" />
                                </span>
                            </div>
                        </div>
                    </Link>

                    <!-- Carte normale ────────────────────────────── -->
                    <Link v-else
                          :href="route('blog.show', post.slug)"
                          class="bento-card bento-normal group relative overflow-hidden rounded-3xl block bg-white shadow-card hover:shadow-float transition-all duration-300 hover:-translate-y-1">
                        <!-- Image -->
                        <div class="h-48 overflow-hidden relative">
                            <img v-if="post.cover_image_url"
                                 :src="post.cover_image_url" :alt="post.cover_image_alt"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
                            <div v-else class="w-full h-full"
                                 :style="`background: linear-gradient(135deg, ${post.category?.color ?? '#c4956a'}15, #faf7f4);`">
                                <div class="w-full h-full flex items-center justify-center opacity-20">
                                    <PhBooks :size="48" class="text-onyx-400" />
                                </div>
                            </div>

                            <!-- Badge catégorie -->
                            <span v-if="post.category"
                                  class="absolute top-3 left-3 px-2.5 py-1 rounded-xl text-xs font-poppins font-bold text-white"
                                  :style="`background: ${post.category.color};`">
                                {{ post.category.name }}
                            </span>
                        </div>

                        <!-- Contenu -->
                        <div class="p-4">
                            <h3 class="font-cormorant text-lg font-bold text-onyx-800 group-hover:text-cognac-700 transition-colors leading-snug mb-2">
                                {{ post.title }}
                            </h3>
                            <p class="text-xs font-poppins text-onyx-400 line-clamp-2 mb-3">{{ post.excerpt }}</p>
                            <div class="flex items-center justify-between">
                                <div class="flex flex-wrap gap-1">
                                    <span v-for="tag in (post.tags ?? []).slice(0, 2)" :key="tag"
                                          class="text-xs font-poppins px-2 py-0.5 rounded-lg"
                                          style="background: rgba(196,149,106,0.08); color: #9ca3af;">
                                        #{{ tag }}
                                    </span>
                                </div>
                                <span class="text-xs font-poppins text-onyx-400 flex items-center gap-1 shrink-0">
                                    <PhClock :size="11" />{{ post.reading_time }} min
                                </span>
                            </div>
                        </div>
                    </Link>
                </template>
            </div>

            <!-- Empty -->
            <div v-else class="flex flex-col items-center justify-center py-20 bg-white rounded-3xl">
                <div class="w-20 h-20 rounded-3xl flex items-center justify-center mb-5"
                     style="background: rgba(196,149,106,0.08);">
                    <PhBooks :size="40" class="text-onyx-300" />
                </div>
                <p class="font-cormorant text-2xl font-bold text-onyx-600 mb-2">Aucun article trouvé</p>
                <p class="text-sm font-poppins text-onyx-400 mb-5">Essayez une autre recherche ou catégorie</p>
                <button v-if="search || activeCategory" @click="search=''; activeCategory=''; applyFilters()"
                        class="flex items-center gap-2 px-5 py-2.5 rounded-xl font-poppins text-sm font-semibold text-white"
                        style="background: linear-gradient(135deg, #c4956a, #b07d52);">
                    Voir tous les articles
                </button>
            </div>

            <!-- Pagination -->
            <div v-if="posts.last_page > 1" class="flex items-center justify-center gap-2 mt-10">
                <Link v-if="posts.prev_page_url" :href="posts.prev_page_url"
                      class="flex items-center gap-2 px-5 py-2.5 rounded-2xl text-sm font-poppins font-medium bg-white border border-cream-200 text-onyx-600 hover:border-cognac-300 shadow-card transition-all hover:-translate-y-0.5">
                    ← Précédent
                </Link>
                <span class="text-xs font-poppins text-onyx-500 px-4">
                    {{ posts.current_page }} / {{ posts.last_page }}
                </span>
                <Link v-if="posts.next_page_url" :href="posts.next_page_url"
                      class="flex items-center gap-2 px-5 py-2.5 rounded-2xl text-sm font-poppins font-medium bg-white border border-cream-200 text-onyx-600 hover:border-cognac-300 shadow-card transition-all hover:-translate-y-0.5">
                    Suivant →
                </Link>
            </div>

        </div>
    </section>
</template>

<style scoped>
/* .container-brand { @apply max-w-6xl mx-auto px-4; } */

/* ── BENTO GRID ── */
.bento-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    grid-auto-rows: minmax(200px, auto);
}

.bento-large  { grid-column: span 2; grid-row: span 2; }
.bento-wide   { grid-column: span 2; }
.bento-tall   { grid-row: span 2; }
.bento-normal { grid-column: span 1; }

@media (max-width: 1024px) {
    .bento-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .bento-large { grid-column: span 2; grid-row: span 1; }
    .bento-wide  { grid-column: span 2; }
    .bento-tall  { grid-row: span 1; }
}

@media (max-width: 640px) {
    .bento-grid {
        grid-template-columns: 1fr;
    }
    .bento-large, .bento-wide, .bento-tall { grid-column: span 1; grid-row: span 1; }
}
</style>