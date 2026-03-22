<script setup>
import { onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { gsap } from 'gsap'
import PublicLayout from '@/Layouts/PublicLayout.vue'
import { PhArrowRight, PhClock, PhTag, PhBooks } from '@phosphor-icons/vue'

defineOptions({ layout: PublicLayout })

const props = defineProps({
    category:   Object,
    posts:      Object,
    categories: Array,
})

onMounted(() => {
    gsap.fromTo('.cat-card',
        { opacity: 0, y: 20, scale: 0.97 },
        { opacity: 1, y: 0, scale: 1, duration: 0.45, stagger: 0.06, ease: 'power3.out' }
    )
})
</script>

<template>
    <Head>
        <title>{{ category.name }} — Blog Patricia Braids</title>
        <meta name="description" :content="category.description ?? `Articles sur ${category.name} — Patricia Braids Studio`" />
        <link rel="canonical" :href="route('blog.category', category.slug)" />
    </Head>

    <div style="background: #faf7f4; min-height: 100vh;">

        <!-- Hero catégorie -->
        <section class="relative overflow-hidden py-16"
                 :style="category.cover_image_url
                    ? `background-image: url(${category.cover_image_url}); background-size: cover; background-position: center;`
                    : `background: linear-gradient(135deg, ${category.color}22, #0d0d1a11);`">
            <div class="absolute inset-0" style="background: linear-gradient(to bottom, rgba(13,13,26,0.7), rgba(250,247,244,0.95));" />
            <div class="relative max-w-6xl mx-auto px-4">
                <!-- Breadcrumb -->
                <nav class="flex items-center gap-2 text-xs font-poppins text-white/50 mb-6">
                    <Link :href="route('blog.index')" class="hover:text-white transition-colors">Blog</Link>
                    <span>/</span>
                    <span class="text-white/80">{{ category.name }}</span>
                </nav>

                <div class="flex items-center gap-4 mb-3">
                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center"
                         :style="{ background: category.color }">
                        <PhTag :size="22" class="text-white" />
                    </div>
                    <div>
                        <h1 class="font-cormorant text-4xl font-bold text-onyx-800">{{ category.name }}</h1>
                        <p class="text-sm font-poppins text-onyx-500">{{ posts.total }} article(s)</p>
                    </div>
                </div>

                <p v-if="category.description" class="text-base font-poppins text-onyx-600 max-w-xl leading-relaxed">
                    {{ category.description }}
                </p>
            </div>
        </section>

        <div class="max-w-6xl mx-auto px-4 py-10">

            <!-- Filtres par catégorie (Bento horizontal) -->
            <div class="flex items-center gap-2 overflow-x-auto pb-2 mb-8 scrollbar-hide">
                <Link :href="route('blog.index')"
                      class="flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-poppins font-medium whitespace-nowrap transition-all border border-cream-200 bg-white text-onyx-500 hover:border-cognac-300 hover:text-onyx-700 shrink-0">
                    <PhBooks :size="13" /> Tous les articles
                </Link>
                <Link v-for="cat in categories" :key="cat.id"
                      :href="route('blog.category', cat.slug)"
                      class="flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-poppins font-medium whitespace-nowrap transition-all shrink-0 border"
                      :class="cat.is_current
                          ? 'text-white border-transparent shadow-sm'
                          : 'border-cream-200 bg-white text-onyx-500 hover:border-cognac-300'"
                      :style="cat.is_current ? `background: ${cat.color};` : ''">
                    {{ cat.name }}
                    <span class="ml-1 opacity-60">{{ cat.posts_count }}</span>
                </Link>
            </div>

            <!-- Bento Grid -->
            <div v-if="posts.data.length" class="bento-grid">
                <Link v-for="(post, index) in posts.data" :key="post.id"
                      :href="route('blog.show', post.slug)"
                      class="cat-card group relative overflow-hidden rounded-3xl bg-white shadow-card hover:shadow-float transition-all duration-300 hover:-translate-y-1 block"
                      :class="{
                          'featured-card': post.is_featured || post.is_pinned,
                          'wide-card': index % 5 === 2,
                      }">

                    <!-- Image -->
                    <div class="overflow-hidden relative"
                         :class="post.is_featured ? 'h-72' : 'h-48'">
                        <img v-if="post.cover_image_url"
                             :src="post.cover_image_url" :alt="post.cover_image_alt"
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
                        <div v-else class="w-full h-full"
                             :style="`background: linear-gradient(135deg, ${category.color}15, #faf7f4);`" />

                        <!-- Overlay sur les featured -->
                        <div v-if="post.is_featured || post.is_pinned"
                             class="absolute inset-0"
                             style="background: linear-gradient(to top, rgba(0,0,0,0.7) 0%, transparent 60%);" />

                        <!-- Titre en overlay sur les featured -->
                        <div v-if="post.is_featured || post.is_pinned" class="absolute bottom-0 left-0 right-0 p-5">
                            <h2 class="font-cormorant text-2xl font-bold text-white group-hover:text-cognac-200 transition-colors">
                                {{ post.title }}
                            </h2>
                        </div>
                    </div>

                    <!-- Contenu (non featured) -->
                    <div v-if="!post.is_featured && !post.is_pinned" class="p-4">
                        <h3 class="font-cormorant text-lg font-bold text-onyx-800 group-hover:text-cognac-700 transition-colors leading-snug mb-2">
                            {{ post.title }}
                        </h3>
                        <p class="text-xs font-poppins text-onyx-400 line-clamp-2 mb-3">{{ post.excerpt }}</p>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-poppins text-onyx-400 flex items-center gap-1">
                                <PhClock :size="11" /> {{ post.reading_time }} min · {{ post.published_date }}
                            </span>
                            <span class="text-xs font-poppins text-cognac-500 font-semibold flex items-center gap-1">
                                Lire <PhArrowRight :size="12" />
                            </span>
                        </div>
                    </div>

                    <!-- Meta en overlay pour les featured -->
                    <div v-if="post.is_featured || post.is_pinned"
                         class="px-5 py-3 flex items-center justify-between" style="border-top: 1px solid #f5ede4;">
                        <span class="text-xs font-poppins text-onyx-400 flex items-center gap-1">
                            <PhClock :size="11" /> {{ post.reading_time }} min
                        </span>
                        <span class="text-xs font-poppins text-cognac-500 font-semibold flex items-center gap-1">
                            Lire l'article <PhArrowRight :size="12" />
                        </span>
                    </div>
                </Link>
            </div>

            <!-- Empty -->
            <div v-else class="flex flex-col items-center justify-center py-20 bg-white rounded-3xl">
                <div class="w-16 h-16 rounded-2xl flex items-center justify-center mb-4"
                     :style="{ background: category.color + '18' }">
                    <PhTag :size="32" :style="{ color: category.color }" />
                </div>
                <p class="font-cormorant text-2xl font-bold text-onyx-600 mb-2">Aucun article dans cette catégorie</p>
                <Link :href="route('blog.index')"
                      class="flex items-center gap-2 px-5 py-2.5 rounded-xl font-poppins text-sm font-semibold text-white mt-4"
                      style="background: linear-gradient(135deg, #c4956a, #b07d52);">
                    Voir tous les articles <PhArrowRight :size="15" />
                </Link>
            </div>

            <!-- Pagination -->
            <div v-if="posts.last_page > 1" class="flex items-center justify-center gap-3 mt-10">
                <Link v-if="posts.prev_page_url" :href="posts.prev_page_url"
                      class="px-5 py-2.5 rounded-2xl text-sm font-poppins font-medium bg-white border border-cream-200 text-onyx-600 hover:border-cognac-300 shadow-card transition-all hover:-translate-y-0.5">
                    ← Précédent
                </Link>
                <span class="text-xs font-poppins text-onyx-500">{{ posts.current_page }} / {{ posts.last_page }}</span>
                <Link v-if="posts.next_page_url" :href="posts.next_page_url"
                      class="px-5 py-2.5 rounded-2xl text-sm font-poppins font-medium bg-white border border-cream-200 text-onyx-600 hover:border-cognac-300 shadow-card transition-all hover:-translate-y-0.5">
                    Suivant →
                </Link>
            </div>
        </div>
    </div>
</template>

<style scoped>
.scrollbar-hide::-webkit-scrollbar { display: none; }
.scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }

.bento-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
}

.featured-card { grid-column: span 2; }
.wide-card     { grid-column: span 2; }

@media (max-width: 1024px) {
    .bento-grid { grid-template-columns: repeat(2, 1fr); }
    .featured-card { grid-column: span 2; }
}

@media (max-width: 640px) {
    .bento-grid { grid-template-columns: 1fr; }
    .featured-card, .wide-card { grid-column: span 1; }
}
</style>