<script setup>
import { onMounted, computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { gsap } from 'gsap'
import PublicLayout from '@/Layouts/PublicLayout.vue'
import {
    PhArrowLeft, PhArrowRight, PhClock, PhCalendarBlank,
    PhEye, PhTag, PhArrowUpRight,
} from '@phosphor-icons/vue'

defineOptions({ layout: PublicLayout })

const props = defineProps({
    post:    Object,
    related: Array,
    prev:    Object,
    next:    Object,
})

const jsonLd = computed(() => JSON.stringify({
    '@context': 'https://schema.org',
    '@type': 'Article',
    'headline': props.post.title,
    'description': props.post.meta_description,
    'image': props.post.og_image,
    'datePublished': props.post.published_date,
    'author': {
        '@type': 'Person',
        'name': props.post.author ?? 'Patricia Braids'
    },
    'publisher': {
        '@type': 'Organization',
        'name': 'Patricia Braids Studio',
        'logo': { '@type': 'ImageObject', 'url': '/images/logo.png' }
    }
}))

onMounted(() => {
    gsap.fromTo('.article-body',
        { opacity: 0, y: 20 },
        { opacity: 1, y: 0, duration: 0.6, ease: 'power2.out' }
    )
    gsap.fromTo('.sidebar-card',
        { opacity: 0, x: 20 },
        { opacity: 1, x: 0, duration: 0.5, stagger: 0.1, ease: 'power2.out', delay: 0.2 }
    )
})
</script>

<template>
    <Head>
        <title>{{ post.meta_title }}</title>
        <meta name="description"        :content="post.meta_description" />
        <meta name="keywords"           :content="post.meta_keywords" />
        <meta property="og:type"        content="article" />
        <meta property="og:title"       :content="post.meta_title" />
        <meta property="og:description" :content="post.meta_description" />
        <meta property="og:image"       :content="post.og_image" />
        <meta property="og:url"         :content="route('blog.show', post.slug)" />
        <meta name="twitter:card"        content="summary_large_image" />
        <meta name="twitter:title"       :content="post.meta_title" />
        <meta name="twitter:description" :content="post.meta_description" />
        <meta name="twitter:image"       :content="post.og_image" />
        <link rel="canonical"           :href="route('blog.show', post.slug)" />

        <!-- ✅ JSON-LD via component — évite l'erreur Vue "script in template" -->
        <component :is="'script'" type="application/ld+json">{{ jsonLd }}</component>
    </Head>

    <div style="background: #faf7f4; min-height: 100vh;">

        <!-- ── Hero image ────────────────────────────────────────── -->
        <div v-if="post.cover_image_url"
             class="relative w-full overflow-hidden"
             style="height: 50vh; max-height: 520px;">
            <img :src="post.cover_image_url" :alt="post.cover_image_alt"
                 class="w-full h-full object-cover" />
            <div class="absolute inset-0" style="background: linear-gradient(to bottom, transparent 30%, rgba(250,247,244,1) 100%);" />
        </div>

        <div class="max-w-6xl mx-auto px-4 py-10">
            <div class="flex gap-10 items-start">

                <!-- ── Article principal ────────────────────────── -->
                <article class="article-body flex-1 min-w-0">

                    <!-- Breadcrumb -->
                    <nav class="flex items-center gap-2 text-xs font-poppins text-onyx-400 mb-6">
                        <Link :href="route('home')" class="hover:text-cognac-600 transition-colors">Accueil</Link>
                        <span>/</span>
                        <Link :href="route('blog.index')" class="hover:text-cognac-600 transition-colors">Blog</Link>
                        <span v-if="post.category">/</span>
                        <Link v-if="post.category" :href="route('blog.category', post.category.slug)"
                              class="hover:text-cognac-600 transition-colors">{{ post.category.name }}</Link>
                    </nav>

                    <!-- Catégorie badge -->
                    <div v-if="post.category" class="mb-4">
                        <Link :href="route('blog.category', post.category.slug)"
                              class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-poppins font-bold text-white"
                              :style="{ background: post.category.color }">
                            <PhTag :size="11" />{{ post.category.name }}
                        </Link>
                    </div>

                    <!-- Titre -->
                    <h1 class="font-cormorant text-4xl md:text-5xl font-bold text-onyx-800 leading-tight mb-5">
                        {{ post.title }}
                    </h1>

                    <!-- Meta -->
                    <div class="flex flex-wrap items-center gap-4 text-xs font-poppins text-onyx-400 mb-8 pb-6"
                         style="border-bottom: 1px solid #f5ede4;">
                        <span class="flex items-center gap-1.5">
                            <PhCalendarBlank :size="13" /> {{ post.published_date }}
                        </span>
                        <span class="flex items-center gap-1.5">
                            <PhClock :size="13" /> {{ post.reading_time }} min de lecture
                        </span>
                        <span class="flex items-center gap-1.5">
                            <PhEye :size="13" /> {{ post.views_count }} vue(s)
                        </span>
                        <span v-if="post.author" class="flex items-center gap-1.5">
                            Par <strong class="text-onyx-600">{{ post.author }}</strong>
                        </span>
                    </div>

                    <!-- Corps de l'article -->
                    <div class="prose-patricia" v-html="post.content" />

                    <!-- Tags -->
                    <div v-if="post.tags?.length" class="mt-10 pt-6 flex flex-wrap gap-2" style="border-top: 1px solid #f5ede4;">
                        <Link v-for="tag in post.tags" :key="tag"
                              :href="route('blog.index', { tag })"
                              class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-poppins font-medium text-onyx-600 border border-cream-200 bg-white hover:border-cognac-300 hover:text-cognac-700 transition-all">
                            <PhTag :size="11" />#{{ tag }}
                        </Link>
                    </div>

                    <!-- Navigation prev/next -->
                    <div class="mt-10 grid grid-cols-2 gap-4">
                        <Link v-if="prev" :href="route('blog.show', prev.slug)"
                              class="group flex items-center gap-3 p-4 rounded-2xl bg-white shadow-card hover:shadow-float transition-all hover:-translate-y-0.5 border border-cream-200 hover:border-cognac-300">
                            <PhArrowLeft :size="16" class="text-cognac-500 shrink-0 group-hover:-translate-x-0.5 transition-transform" />
                            <div class="min-w-0">
                                <p class="text-xs font-poppins text-onyx-400 mb-0.5">Article précédent</p>
                                <p class="text-sm font-poppins font-semibold text-onyx-700 truncate">{{ prev.title }}</p>
                            </div>
                        </Link>
                        <div v-else />

                        <Link v-if="next" :href="route('blog.show', next.slug)"
                              class="group flex items-center justify-end gap-3 p-4 rounded-2xl bg-white shadow-card hover:shadow-float transition-all hover:-translate-y-0.5 border border-cream-200 hover:border-cognac-300 text-right">
                            <div class="min-w-0">
                                <p class="text-xs font-poppins text-onyx-400 mb-0.5">Article suivant</p>
                                <p class="text-sm font-poppins font-semibold text-onyx-700 truncate">{{ next.title }}</p>
                            </div>
                            <PhArrowRight :size="16" class="text-cognac-500 shrink-0 group-hover:translate-x-0.5 transition-transform" />
                        </Link>
                    </div>
                </article>

                <!-- ── Sidebar ─────────────────────────────────── -->
                <aside class="hidden xl:block w-72 shrink-0 sticky top-[90px] space-y-5">

                    <!-- CTA Réservation -->
                    <div class="sidebar-card rounded-2xl overflow-hidden"
                         style="background: linear-gradient(135deg, #1a1a2e, #0d0d1a);">
                        <div class="p-5">
                            <p class="font-cormorant text-xl font-bold text-white mb-2">
                                Envie de vous faire coiffer ?
                            </p>
                            <p class="text-xs font-poppins text-white/50 mb-4">
                                Réservez votre session avec Patricia
                            </p>
                            <Link :href="route('booking.services')"
                                  class="flex items-center justify-center gap-2 w-full py-2.5 rounded-xl text-sm font-poppins font-semibold text-white transition-all hover:opacity-90"
                                  style="background: linear-gradient(135deg, #c4956a, #b07d52);">
                                Prendre RDV <PhArrowUpRight :size="14" />
                            </Link>
                        </div>
                    </div>

                    <!-- Articles liés -->
                    <div v-if="related.length" class="sidebar-card bg-white rounded-2xl shadow-card overflow-hidden">
                        <div class="px-4 py-3 border-b border-cream-100">
                            <p class="text-xs font-poppins font-semibold text-onyx-600 uppercase tracking-wide">À lire aussi</p>
                        </div>
                        <div class="divide-y divide-cream-50">
                            <Link v-for="r in related" :key="r.id"
                                  :href="route('blog.show', r.slug)"
                                  class="flex items-start gap-3 p-4 hover:bg-cream-50 transition-colors group">
                                <div class="w-14 h-12 rounded-xl overflow-hidden shrink-0 bg-cream-100">
                                    <img v-if="r.cover_image_url" :src="r.cover_image_url"
                                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs font-poppins font-semibold text-onyx-700 group-hover:text-cognac-700 transition-colors leading-snug line-clamp-2">
                                        {{ r.title }}
                                    </p>
                                    <p class="text-xs font-poppins text-onyx-400 mt-1 flex items-center gap-1">
                                        <PhClock :size="10" /> {{ r.reading_time }} min
                                    </p>
                                </div>
                            </Link>
                        </div>
                        <div class="p-3 border-t border-cream-100">
                            <Link :href="route('blog.index')"
                                  class="flex items-center justify-center gap-1.5 text-xs font-poppins font-semibold text-cognac-600 hover:text-cognac-800 transition-colors">
                                Voir tous les articles <PhArrowRight :size="12" />
                            </Link>
                        </div>
                    </div>

                </aside>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Styles de l'article — non scoped pour s'appliquer au v-html */
.prose-patricia h2 {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.75rem;
    font-weight: 700;
    color: #1a1a2e;
    margin-top: 2.5rem;
    margin-bottom: 1rem;
    line-height: 1.3;
}
.prose-patricia h3 {
    font-family: 'Poppins', sans-serif;
    font-size: 1.125rem;
    font-weight: 600;
    color: #1a1a2e;
    margin-top: 2rem;
    margin-bottom: 0.75rem;
}
.prose-patricia p {
    font-family: 'Poppins', sans-serif;
    font-size: 0.9375rem;
    color: #374151;
    line-height: 1.8;
    margin-bottom: 1.25rem;
}
.prose-patricia strong { color: #1a1a2e; font-weight: 600; }
.prose-patricia ul, .prose-patricia ol {
    margin: 1rem 0 1.25rem 1.5rem;
    space-y: 0.375rem;
}
.prose-patricia li {
    font-family: 'Poppins', sans-serif;
    font-size: 0.9375rem;
    color: #374151;
    line-height: 1.7;
    margin-bottom: 0.375rem;
}
.prose-patricia ul li::marker { color: #c4956a; }
.prose-patricia a { color: #c4956a; text-decoration: underline; }
.prose-patricia blockquote {
    border-left: 3px solid #c4956a;
    padding-left: 1rem;
    margin: 1.5rem 0;
    font-style: italic;
    color: #6b7280;
}
</style>