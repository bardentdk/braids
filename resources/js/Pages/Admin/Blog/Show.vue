<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import {
    PhArrowLeft, PhPencilSimple, PhTrash, PhEye,
    PhCheckCircle, PhXCircle, PhRobot, PhStar, PhPushPin,
    PhClock, PhTag, PhGlobe, PhCalendarBlank, PhArrowSquareOut,
} from '@phosphor-icons/vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
    post: Object,
})

const page  = usePage()
const flash = computed(() => page.props.flash)

function toggleStatus() {
    router.patch(route('admin.blog.toggle-status', props.post.id), {}, { preserveScroll: true })
}

function toggleFeatured() {
    router.patch(route('admin.blog.toggle-featured', props.post.id), {}, { preserveScroll: true })
}

function deletePost() {
    if (!confirm(`Supprimer l'article "${props.post.title}" ? Cette action est irréversible.`)) return
    router.delete(route('admin.blog.destroy', props.post.id))
}

const statusMap = {
    draft:     { label: 'Brouillon',  bg: 'rgba(107,114,128,0.1)', color: '#6b7280' },
    published: { label: 'Publié',     bg: 'rgba(16,185,129,0.1)',  color: '#059669' },
    archived:  { label: 'Archivé',    bg: 'rgba(245,158,11,0.1)',  color: '#d97706' },
}
</script>

<template>
    <Head :title="post.title" />

    <div class="p-6 space-y-6">

        <!-- Header -->
        <div class="flex items-start justify-between gap-4 flex-wrap">
            <div class="flex items-start gap-4">
                <Link :href="route('admin.blog.index')"
                      class="w-9 h-9 rounded-xl flex items-center justify-center border border-cream-200 bg-white shadow-card text-onyx-500 hover:bg-cream-100 transition-colors mt-0.5">
                    <PhArrowLeft :size="17" />
                </Link>
                <div>
                    <div class="flex items-center gap-2 flex-wrap mb-1">
                        <h1 class="font-cormorant text-3xl font-bold text-onyx-800">{{ post.title }}</h1>
                        <span class="px-2.5 py-1 rounded-xl text-xs font-poppins font-semibold"
                              :style="{ background: statusMap[post.status]?.bg, color: statusMap[post.status]?.color }">
                            {{ statusMap[post.status]?.label }}
                        </span>
                        <span v-if="post.ai_generated"
                              class="flex items-center gap-1 px-2.5 py-1 rounded-xl text-xs font-poppins font-bold"
                              style="background: rgba(13,13,26,0.08); color: #c4956a;">
                            <PhRobot :size="11" /> IA · {{ post.ai_model }}
                        </span>
                    </div>
                    <div class="flex items-center gap-3 text-xs font-poppins text-onyx-400">
                        <span v-if="post.category"
                              class="px-2 py-0.5 rounded-lg font-medium"
                              :style="{ background: post.category.color + '18', color: post.category.color }">
                            {{ post.category.name }}
                        </span>
                        <span class="flex items-center gap-1">
                            <PhClock :size="11" /> {{ post.reading_time }} min de lecture
                        </span>
                        <span class="flex items-center gap-1">
                            <PhEye :size="11" /> {{ post.views_count }} vue(s)
                        </span>
                        <span v-if="post.published_at" class="flex items-center gap-1">
                            <PhCalendarBlank :size="11" /> {{ post.published_at }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-2 flex-wrap">
                <!-- Voir sur le site (si publié) -->
                <a v-if="post.status === 'published'"
                   :href="route('blog.show', post.slug)" target="_blank"
                   class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-sm font-poppins font-medium border border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100 transition-colors">
                    <PhArrowSquareOut :size="15" /> Voir
                </a>

                <!-- Toggle mis en avant -->
                <button @click="toggleFeatured"
                        class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-sm font-poppins font-medium transition-colors"
                        :class="post.is_featured
                            ? 'border border-amber-300 bg-amber-50 text-amber-700 hover:bg-amber-100'
                            : 'border border-cream-200 bg-white text-onyx-600 hover:bg-cream-50 shadow-card'">
                    <PhStar :size="15" :weight="post.is_featured ? 'fill' : 'regular'" />
                    {{ post.is_featured ? 'En avant' : 'Mettre en avant' }}
                </button>

                <!-- Toggle statut -->
                <button @click="toggleStatus"
                        class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-sm font-poppins font-medium transition-colors"
                        :class="post.status === 'published'
                            ? 'border border-orange-200 bg-orange-50 text-orange-700 hover:bg-orange-100'
                            : 'border border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100'">
                    <PhCheckCircle v-if="post.status !== 'published'" :size="15" />
                    <PhXCircle v-else :size="15" />
                    {{ post.status === 'published' ? 'Dépublier' : 'Publier' }}
                </button>

                <!-- Modifier -->
                <Link :href="route('admin.blog.edit', post.id)"
                      class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-sm font-poppins font-medium border border-cream-200 bg-white text-onyx-700 hover:bg-cream-50 shadow-card transition-colors">
                    <PhPencilSimple :size="15" /> Modifier
                </Link>

                <!-- Supprimer -->
                <button @click="deletePost"
                        class="w-9 h-9 rounded-xl flex items-center justify-center border border-red-200 bg-white text-red-500 hover:bg-red-50 transition-colors">
                    <PhTrash :size="15" />
                </button>
            </div>
        </div>

        <!-- Flash -->
        <div v-if="flash?.success" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-poppins font-medium"
             style="background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.2); color: #059669;">
            <PhCheckCircle :size="18" />{{ flash.success }}
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

            <!-- ── Contenu article ─────────────────────────────── -->
            <div class="xl:col-span-2 space-y-5">

                <!-- Extrait -->
                <div v-if="post.excerpt" class="bg-white rounded-2xl shadow-card p-5">
                    <p class="text-xs font-poppins font-semibold text-onyx-500 uppercase tracking-wide mb-2">Extrait</p>
                    <p class="text-sm font-poppins text-onyx-700 leading-relaxed italic">{{ post.excerpt }}</p>
                </div>

                <!-- Image de couverture -->
                <div v-if="post.cover_image_url" class="bg-white rounded-2xl shadow-card overflow-hidden">
                    <img :src="post.cover_image_url" :alt="post.cover_image_alt ?? post.title"
                         class="w-full h-64 object-cover" />
                    <p v-if="post.cover_image_alt" class="px-4 py-2 text-xs font-poppins text-onyx-400 italic">
                        Alt : {{ post.cover_image_alt }}
                    </p>
                </div>

                <!-- Contenu HTML prévisualisé -->
                <div class="bg-white rounded-2xl shadow-card overflow-hidden">
                    <div class="flex items-center justify-between px-5 py-3.5 border-b border-cream-100">
                        <p class="text-xs font-poppins font-semibold text-onyx-600 uppercase tracking-wide">Contenu</p>
                        <Link :href="route('admin.blog.edit', post.id)"
                              class="flex items-center gap-1.5 text-xs font-poppins text-cognac-600 hover:text-cognac-800 font-medium transition-colors">
                            <PhPencilSimple :size="12" /> Modifier
                        </Link>
                    </div>
                    <!-- Rendu HTML -->
                    <div class="px-6 py-5 prose-patricia max-w-none"
                         v-html="post.content" />
                </div>
            </div>

            <!-- ── Sidebar infos ──────────────────────────────── -->
            <div class="space-y-4">

                <!-- Méta publication -->
                <div class="bg-white rounded-2xl shadow-card p-5 space-y-3">
                    <h2 class="font-poppins font-semibold text-onyx-800 text-sm flex items-center gap-2">
                        <PhCalendarBlank :size="15" class="text-cognac-500" /> Publication
                    </h2>
                    <div class="space-y-2 text-sm font-poppins">
                        <div class="flex justify-between">
                            <span class="text-onyx-500">Statut</span>
                            <span class="font-semibold px-2 py-0.5 rounded-lg text-xs"
                                  :style="{ background: statusMap[post.status]?.bg, color: statusMap[post.status]?.color }">
                                {{ statusMap[post.status]?.label }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-onyx-500">Auteur</span>
                            <span class="font-semibold text-onyx-700">{{ post.author?.name }}</span>
                        </div>
                        <div v-if="post.published_at" class="flex justify-between">
                            <span class="text-onyx-500">Publié le</span>
                            <span class="font-semibold text-onyx-700">{{ post.published_at }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-onyx-500">Lecture</span>
                            <span class="font-semibold text-onyx-700">{{ post.reading_time }} min</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-onyx-500">Vues</span>
                            <span class="font-semibold text-onyx-700">{{ post.views_count }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-onyx-500">Mis en avant</span>
                            <span class="font-semibold" :class="post.is_featured ? 'text-amber-600' : 'text-onyx-400'">
                                {{ post.is_featured ? '★ Oui' : 'Non' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-onyx-500">Épinglé</span>
                            <span class="font-semibold" :class="post.is_pinned ? 'text-cognac-600' : 'text-onyx-400'">
                                {{ post.is_pinned ? '📌 Oui' : 'Non' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- SEO -->
                <div class="bg-white rounded-2xl shadow-card p-5 space-y-3">
                    <h2 class="font-poppins font-semibold text-onyx-800 text-sm flex items-center gap-2">
                        <PhGlobe :size="15" class="text-cognac-500" /> SEO
                    </h2>

                    <!-- Aperçu Google -->
                    <div class="p-3 rounded-xl" style="background: #f8f9fa; border: 1px solid #e8eaed;">
                        <p class="text-xs font-poppins text-onyx-400 mb-1.5">Aperçu Google</p>
                        <p class="text-sm font-medium leading-tight" style="color: #1a0dab; font-family: Arial;">
                            {{ post.meta_title || post.title }}
                        </p>
                        <p class="text-xs mt-0.5" style="color: #006621; font-family: Arial;">
                            patricia-braids.fr/blog/{{ post.slug }}
                        </p>
                        <p v-if="post.meta_description" class="text-xs mt-0.5 leading-snug" style="color: #545454; font-family: Arial;">
                            {{ post.meta_description }}
                        </p>
                    </div>

                    <div class="space-y-2 text-xs font-poppins">
                        <div v-if="post.meta_title">
                            <span class="text-onyx-400 uppercase tracking-wide font-semibold">Meta title</span>
                            <p class="text-onyx-700 mt-0.5">{{ post.meta_title }}
                                <span class="text-onyx-400">({{ post.meta_title.length }}/60)</span>
                            </p>
                        </div>
                        <div v-if="post.meta_description">
                            <span class="text-onyx-400 uppercase tracking-wide font-semibold">Meta description</span>
                            <p class="text-onyx-700 mt-0.5">{{ post.meta_description }}
                                <span class="text-onyx-400">({{ post.meta_description.length }}/160)</span>
                            </p>
                        </div>
                        <div v-if="post.meta_keywords">
                            <span class="text-onyx-400 uppercase tracking-wide font-semibold">Mots-clés</span>
                            <p class="text-onyx-700 mt-0.5">{{ post.meta_keywords }}</p>
                        </div>
                    </div>
                </div>

                <!-- Tags -->
                <div v-if="post.tags?.length" class="bg-white rounded-2xl shadow-card p-5">
                    <h2 class="font-poppins font-semibold text-onyx-800 text-sm flex items-center gap-2 mb-3">
                        <PhTag :size="15" class="text-cognac-500" /> Tags
                    </h2>
                    <div class="flex flex-wrap gap-2">
                        <span v-for="tag in post.tags" :key="tag"
                              class="px-2.5 py-1 rounded-xl text-xs font-poppins font-medium"
                              style="background: rgba(196,149,106,0.1); color: #b07d52;">
                            #{{ tag }}
                        </span>
                    </div>
                </div>

                <!-- Actions rapides -->
                <div class="bg-white rounded-2xl shadow-card p-4 space-y-2">
                    <Link :href="route('admin.blog.edit', post.id)"
                          class="flex items-center justify-center gap-2 w-full py-2.5 rounded-xl text-sm font-poppins font-semibold text-white transition-all hover:opacity-90"
                          style="background: linear-gradient(135deg, #c4956a, #b07d52);">
                        <PhPencilSimple :size="15" /> Modifier l'article
                    </Link>
                    <Link :href="route('admin.blog.index')"
                          class="flex items-center justify-center gap-2 w-full py-2.5 rounded-xl text-sm font-poppins font-medium text-onyx-600 border border-cream-200 hover:bg-cream-50 transition-colors">
                        ← Retour à la liste
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
/* Styles prose pour le rendu HTML du contenu — non scoped pour v-html */
.prose-patricia h2 {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.6rem;
    font-weight: 700;
    color: #1a1a2e;
    margin-top: 2rem;
    margin-bottom: 0.75rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #f5ede4;
}
.prose-patricia h3 {
    font-family: 'Poppins', sans-serif;
    font-size: 1rem;
    font-weight: 600;
    color: #1a1a2e;
    margin-top: 1.5rem;
    margin-bottom: 0.5rem;
}
.prose-patricia p {
    font-family: 'Poppins', sans-serif;
    font-size: 0.9rem;
    color: #374151;
    line-height: 1.8;
    margin-bottom: 1rem;
}
.prose-patricia strong { color: #1a1a2e; font-weight: 600; }
.prose-patricia ul, .prose-patricia ol {
    margin: 0.75rem 0 1rem 1.25rem;
}
.prose-patricia li {
    font-family: 'Poppins', sans-serif;
    font-size: 0.9rem;
    color: #374151;
    line-height: 1.7;
    margin-bottom: 0.25rem;
}
.prose-patricia ul li::marker { color: #c4956a; }
.prose-patricia a { color: #c4956a; text-decoration: underline; }
.prose-patricia blockquote {
    border-left: 3px solid #c4956a;
    padding-left: 1rem;
    margin: 1.25rem 0;
    font-style: italic;
    color: #6b7280;
}
</style>