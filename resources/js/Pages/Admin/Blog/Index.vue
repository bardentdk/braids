<script setup>
import { ref, computed, watch } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import {
    PhPlus, PhMagnifyingGlass, PhEye, PhPencilSimple, PhTrash,
    PhArrowsClockwise, PhX, PhRobot, PhStar, PhPushPin,
    PhCheckCircle, PhXCircle, PhArticle, PhClock, PhBook,
} from '@phosphor-icons/vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
    posts:      Object,
    filters:    Object,
    categories: Array,
    stats:      Object,
    ai_model:   Object,
})

const page  = usePage()
const flash = computed(() => page.props.flash)

const search   = ref(props.filters?.search   ?? '')
const status   = ref(props.filters?.status   ?? '')
const category = ref(props.filters?.category ?? '')

let t = null
watch(search, () => { clearTimeout(t); t = setTimeout(applyFilters, 380) })
watch([status, category], () => applyFilters())

function applyFilters() {
    router.get(route('admin.blog.index'), {
        search:   search.value   || undefined,
        status:   status.value   || undefined,
        category: category.value || undefined,
    }, { preserveState: true, replace: true })
}

function resetFilters() {
    search.value = ''; status.value = ''; category.value = ''
    router.get(route('admin.blog.index'))
}

const hasFilters = computed(() => search.value || status.value || category.value)

function toggleStatus(post) {
    router.patch(route('admin.blog.toggle-status', post.id), {}, { preserveScroll: true })
}

function toggleFeatured(post) {
    router.patch(route('admin.blog.toggle-featured', post.id), {}, { preserveScroll: true })
}

function deletePost(post) {
    if (!confirm(`Supprimer "${post.title}" ?`)) return
    router.delete(route('admin.blog.destroy', post.id))
}

const statusMap = {
    draft:     { label: 'Brouillon',  bg: 'rgba(107,114,128,0.1)', color: '#6b7280' },
    published: { label: 'Publié',     bg: 'rgba(16,185,129,0.1)',  color: '#059669' },
    archived:  { label: 'Archivé',    bg: 'rgba(245,158,11,0.1)',  color: '#d97706' },
}
</script>

<template>
    <Head title="Blog — Articles" />
    <div class="p-6 space-y-6">

        <!-- Header -->
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h1 class="font-cormorant text-3xl font-bold text-onyx-800">Blog</h1>
                <p class="text-sm font-poppins text-onyx-500 mt-0.5 flex items-center gap-2">
                    {{ stats.total }} articles
                    <span class="w-1 h-1 rounded-full bg-onyx-300" />
                    Modèle IA : <span class="font-semibold text-cognac-600">{{ ai_model?.provider }} · {{ ai_model?.model }}</span>
                </p>
            </div>
            <div class="flex items-center gap-2">
                <Link :href="route('admin.blog-categories.index')"
                      class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-poppins font-medium border border-cream-200 bg-white text-onyx-700 hover:bg-cream-50 shadow-card transition-colors">
                    <PhBook :size="16" /> Catégories
                </Link>
                <Link :href="route('admin.blog.create')"
                      class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-poppins font-semibold text-white shadow-md transition-all hover:opacity-90 hover:-translate-y-0.5"
                      style="background: linear-gradient(135deg, #c4956a, #b07d52);">
                    <PhPlus :size="16" weight="bold" /> Nouvel article
                </Link>
            </div>
        </div>

        <!-- Flash -->
        <div v-if="flash?.success" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-poppins font-medium"
             style="background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.2); color: #059669;">
            <PhCheckCircle :size="18" /> {{ flash.success }}
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 xl:grid-cols-5 gap-3">
            <div v-for="(s, i) in [
                { label: 'Total',    value: stats.total,     color: '#6b7280' },
                { label: 'Publiés',  value: stats.published, color: '#059669' },
                { label: 'Brouillons', value: stats.draft,   color: '#d97706' },
                { label: 'En avant', value: stats.featured,  color: '#c4956a' },
                { label: 'Vues',     value: stats.views,     color: '#6366f1' },
            ]" :key="i" class="bg-white rounded-2xl shadow-card p-4">
                <p class="text-2xl font-bold font-poppins text-onyx-800">{{ s.value }}</p>
                <p class="text-xs font-poppins mt-0.5" :style="{ color: s.color }">{{ s.label }}</p>
            </div>
        </div>

        <!-- Filtres -->
        <div class="bg-white rounded-2xl shadow-card p-4 flex flex-wrap gap-3 items-center">
            <div class="relative flex-1 min-w-48">
                <PhMagnifyingGlass :size="15" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-onyx-400" />
                <input v-model="search" type="text" placeholder="Rechercher un article…"
                       class="w-full pl-9 pr-8 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none transition-colors" />
                <button v-if="search" @click="search=''" class="absolute right-3 top-1/2 -translate-y-1/2 text-onyx-300 hover:text-onyx-600">
                    <PhX :size="14" />
                </button>
            </div>
            <select v-model="status" class="px-3 py-2.5 rounded-xl text-sm font-poppins text-onyx-700 bg-cream-50 border border-cream-200 focus:outline-none focus:border-cognac-400">
                <option value="">Tous les statuts</option>
                <option value="published">Publiés</option>
                <option value="draft">Brouillons</option>
                <option value="archived">Archivés</option>
            </select>
            <select v-model="category" class="px-3 py-2.5 rounded-xl text-sm font-poppins text-onyx-700 bg-cream-50 border border-cream-200 focus:outline-none focus:border-cognac-400">
                <option value="">Toutes les catégories</option>
                <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
            <button v-if="hasFilters" @click="resetFilters"
                    class="flex items-center gap-1.5 px-3 py-2.5 rounded-xl text-sm font-poppins text-onyx-500 hover:text-cognac-600 border border-cream-200 hover:bg-cream-50 transition-colors">
                <PhArrowsClockwise :size="15" /> Réinitialiser
            </button>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-2xl shadow-card overflow-hidden">
            <table class="w-full">
                <thead>
                    <tr style="border-bottom: 1px solid #f5ede4; background: #faf7f4;">
                        <th class="text-left px-5 py-3.5 text-xs font-poppins font-semibold text-onyx-500 uppercase tracking-wide">Article</th>
                        <th class="text-center px-4 py-3.5 text-xs font-poppins font-semibold text-onyx-500 uppercase tracking-wide hidden md:table-cell">Statut</th>
                        <th class="text-center px-4 py-3.5 text-xs font-poppins font-semibold text-onyx-500 uppercase tracking-wide hidden lg:table-cell">Vues</th>
                        <th class="text-left px-4 py-3.5 text-xs font-poppins font-semibold text-onyx-500 uppercase tracking-wide hidden xl:table-cell">Date</th>
                        <th class="text-right px-5 py-3.5 text-xs font-poppins font-semibold text-onyx-500 uppercase tracking-wide">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-cream-50">
                    <tr v-for="post in posts.data" :key="post.id"
                        class="hover:bg-cream-50/40 transition-colors">
                        <!-- Titre + badges -->
                        <td class="px-5 py-4">
                            <div class="flex items-start gap-3">
                                <!-- Cover miniature -->
                                <div class="w-14 h-10 rounded-xl overflow-hidden shrink-0 bg-cream-100">
                                    <img v-if="post.cover_image_url" :src="post.cover_image_url"
                                         class="w-full h-full object-cover" />
                                    <div v-else class="w-full h-full flex items-center justify-center">
                                        <PhArticle :size="16" class="text-onyx-300" />
                                    </div>
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap mb-0.5">
                                        <p class="text-sm font-semibold font-poppins text-onyx-800 truncate max-w-xs">
                                            {{ post.title }}
                                        </p>
                                        <span v-if="post.ai_generated"
                                              class="flex items-center gap-0.5 px-1.5 py-0.5 rounded-md text-xs font-poppins font-bold"
                                              style="background: rgba(13,13,26,0.08); color: #c4956a;">
                                            <PhRobot :size="10" /> IA
                                        </span>
                                        <span v-if="post.is_pinned"
                                              class="flex items-center gap-0.5 px-1.5 py-0.5 rounded-md text-xs font-poppins font-bold"
                                              style="background: rgba(196,149,106,0.12); color: #b07d52;">
                                            <PhPushPin :size="10" />
                                        </span>
                                        <span v-if="post.is_featured"
                                              class="flex items-center gap-0.5 px-1.5 py-0.5 rounded-md text-xs font-poppins font-bold"
                                              style="background: rgba(245,158,11,0.12); color: #d97706;">
                                            <PhStar :size="10" />
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span v-if="post.category"
                                              class="text-xs font-poppins font-medium px-2 py-0.5 rounded-lg"
                                              :style="{ background: post.category.color + '18', color: post.category.color }">
                                            {{ post.category.name }}
                                        </span>
                                        <span class="text-xs font-poppins text-onyx-400 flex items-center gap-1">
                                            <PhClock :size="11" /> {{ post.reading_time }} min
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <!-- Statut -->
                        <td class="px-4 py-4 text-center hidden md:table-cell">
                            <button @click="toggleStatus(post)"
                                    class="px-2.5 py-1 rounded-xl text-xs font-poppins font-semibold transition-all hover:opacity-80"
                                    :style="{ background: statusMap[post.status]?.bg, color: statusMap[post.status]?.color }">
                                {{ statusMap[post.status]?.label }}
                            </button>
                        </td>
                        <!-- Vues -->
                        <td class="px-4 py-4 text-center hidden lg:table-cell">
                            <span class="text-sm font-poppins font-semibold text-onyx-600 flex items-center justify-center gap-1">
                                <PhEye :size="13" class="text-onyx-400" /> {{ post.views_count }}
                            </span>
                        </td>
                        <!-- Date -->
                        <td class="px-4 py-4 hidden xl:table-cell">
                            <span class="text-xs font-poppins text-onyx-500">{{ post.published_at ?? '—' }}</span>
                        </td>
                        <!-- Actions -->
                        <td class="px-5 py-4 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <a v-if="post.status === 'published'"
                                   :href="route('blog.show', post.slug)" target="_blank"
                                   class="w-8 h-8 rounded-lg flex items-center justify-center text-onyx-400 hover:text-blue-600 hover:bg-blue-50 transition-colors">
                                    <PhEye :size="15" />
                                </a>
                                <Link :href="route('admin.blog.edit', post.id)"
                                      class="w-8 h-8 rounded-lg flex items-center justify-center text-onyx-400 hover:text-onyx-700 hover:bg-cream-100 transition-colors">
                                    <PhPencilSimple :size="15" />
                                </Link>
                                <button @click="toggleFeatured(post)"
                                        class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors"
                                        :class="post.is_featured ? 'text-amber-500 bg-amber-50' : 'text-onyx-400 hover:text-amber-500 hover:bg-amber-50'">
                                    <PhStar :size="15" :weight="post.is_featured ? 'fill' : 'regular'" />
                                </button>
                                <button @click="deletePost(post)"
                                        class="w-8 h-8 rounded-lg flex items-center justify-center text-onyx-300 hover:text-red-500 hover:bg-red-50 transition-colors">
                                    <PhTrash :size="15" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Empty -->
            <div v-if="!posts.data.length" class="flex flex-col items-center justify-center py-16">
                <div class="w-16 h-16 rounded-2xl flex items-center justify-center mb-4" style="background: rgba(196,149,106,0.08);">
                    <PhArticle :size="32" class="text-onyx-300" />
                </div>
                <p class="text-sm font-poppins font-semibold text-onyx-600 mb-1">Aucun article</p>
                <p class="text-xs font-poppins text-onyx-400 mb-4">Créez votre premier article ou utilisez l'IA !</p>
                <Link :href="route('admin.blog.create')"
                      class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-poppins font-semibold text-white"
                      style="background: linear-gradient(135deg, #c4956a, #b07d52);">
                    <PhPlus :size="15" /> Créer un article
                </Link>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="posts.last_page > 1" class="flex items-center justify-between bg-white rounded-2xl shadow-card px-5 py-3.5">
            <p class="text-xs font-poppins text-onyx-500">Page {{ posts.current_page }} / {{ posts.last_page }} · {{ posts.total }} articles</p>
            <div class="flex gap-1.5">
                <Link v-if="posts.prev_page_url" :href="posts.prev_page_url"
                      class="px-3 py-1.5 rounded-lg text-sm font-poppins text-onyx-600 hover:bg-cream-100 border border-cream-200 transition-colors">← Précédent</Link>
                <Link v-if="posts.next_page_url" :href="posts.next_page_url"
                      class="px-3 py-1.5 rounded-lg text-sm font-poppins text-onyx-600 hover:bg-cream-100 border border-cream-200 transition-colors">Suivant →</Link>
            </div>
        </div>
    </div>
</template>