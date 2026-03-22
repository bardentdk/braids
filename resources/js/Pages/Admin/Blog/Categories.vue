<script setup>
import { ref, computed } from 'vue'
import { Head, router, useForm, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import {
    PhPlus, PhPencilSimple, PhTrash, PhCheckCircle,
    PhXCircle, PhX, PhTag,
} from '@phosphor-icons/vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({ categories: Array })

const page  = usePage()
const flash = computed(() => page.props.flash)

// ── Palette couleurs prédéfinies ──────────────────────────────────
const colorPalette = [
    '#c4956a', '#d4af37', '#10b981', '#6366f1', '#ec4899',
    '#f59e0b', '#3b82f6', '#8b5cf6', '#ef4444', '#0d0d1a',
]

// ── Icônes Phosphor disponibles ───────────────────────────────────
const iconOptions = [
    { value: 'PhScissors',     label: 'Ciseaux' },
    { value: 'PhHeart',        label: 'Cœur' },
    { value: 'PhStar',         label: 'Étoile' },
    { value: 'PhFlower',       label: 'Fleur' },
    { value: 'PhSparkleStar',  label: 'Scintillement' },
    { value: 'PhBraid',        label: 'Tresse' },
    { value: 'PhDropHalf',     label: 'Goutte' },
    { value: 'PhLeaf',         label: 'Feuille' },
    { value: 'PhCrown',        label: 'Couronne' },
    { value: 'PhSunHorizon',   label: 'Soleil' },
]

// ── Modal création / édition ─────────────────────────────────────
const showModal    = ref(false)
const editingCat   = ref(null)

const form = useForm({
    name:             '',
    description:      '',
    color:            '#c4956a',
    icon:             '',
    meta_title:       '',
    meta_description: '',
    sort_order:       0,
    is_active:        true,
})

function openCreate() {
    editingCat.value = null
    form.reset()
    form.color = '#c4956a'
    showModal.value = true
}

function openEdit(cat) {
    editingCat.value = cat
    form.name             = cat.name
    form.description      = cat.description ?? ''
    form.color            = cat.color ?? '#c4956a'
    form.icon             = cat.icon ?? ''
    form.sort_order       = cat.sort_order ?? 0
    form.is_active        = cat.is_active
    form.meta_title       = ''
    form.meta_description = ''
    showModal.value = true
}

function submit() {
    if (editingCat.value) {
        form.patch(route('admin.blog-categories.update', editingCat.value.id), {
            preserveScroll: true,
            onSuccess: () => { showModal.value = false },
        })
    } else {
        form.post(route('admin.blog-categories.store'), {
            preserveScroll: true,
            onSuccess: () => { showModal.value = false; form.reset() },
        })
    }
}

function deleteCategory(cat) {
    if (!confirm(`Supprimer la catégorie "${cat.name}" ?`)) return
    router.delete(route('admin.blog-categories.destroy', cat.id), { preserveScroll: true })
}
</script>

<template>
    <Head title="Catégories Blog" />

    <!-- Modal -->
    <Transition name="fade">
        <div v-if="showModal"
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             style="background: rgba(0,0,0,0.5); backdrop-filter: blur(4px);"
             @click.self="showModal = false">
            <div class="bg-white rounded-2xl shadow-luxury w-full max-w-lg p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="font-poppins font-bold text-onyx-800">
                        {{ editingCat ? 'Modifier' : 'Nouvelle' }} catégorie
                    </h3>
                    <button @click="showModal = false" class="text-onyx-400 hover:text-onyx-700">
                        <PhX :size="20" />
                    </button>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">
                            Nom <span class="text-red-400">*</span>
                        </label>
                        <input v-model="form.name" type="text"
                               class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none" />
                    </div>

                    <div>
                        <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">
                            Description
                        </label>
                        <textarea v-model="form.description" rows="2"
                                  class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none resize-none" />
                    </div>

                    <!-- Couleur -->
                    <div>
                        <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-2 uppercase tracking-wide">
                            Couleur
                        </label>
                        <div class="flex items-center gap-2 flex-wrap">
                            <button v-for="c in colorPalette" :key="c"
                                    @click="form.color = c"
                                    class="w-7 h-7 rounded-xl border-2 transition-all hover:scale-110"
                                    :style="{ background: c, borderColor: form.color === c ? '#1a1a2e' : 'transparent' }" />
                            <input v-model="form.color" type="color"
                                   class="w-7 h-7 rounded-xl cursor-pointer border border-cream-200" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">
                                Icône
                            </label>
                            <select v-model="form.icon"
                                    class="w-full px-3 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none">
                                <option value="">Aucune</option>
                                <option v-for="ic in iconOptions" :key="ic.value" :value="ic.value">{{ ic.label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">
                                Ordre d'affichage
                            </label>
                            <input v-model="form.sort_order" type="number"
                                   class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none" />
                        </div>
                    </div>

                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" v-model="form.is_active" class="accent-cognac-500 w-4 h-4 rounded" />
                        <span class="text-sm font-poppins text-onyx-700">Catégorie active</span>
                    </label>
                </div>

                <div class="flex gap-3 pt-2">
                    <button @click="showModal = false"
                            class="flex-1 py-2.5 rounded-xl text-sm font-poppins font-medium text-onyx-600 border border-cream-200 hover:bg-cream-50 transition-colors">
                        Annuler
                    </button>
                    <button @click="submit" :disabled="form.processing || !form.name"
                            class="flex-1 py-2.5 rounded-xl text-sm font-poppins font-semibold text-white disabled:opacity-60 transition-all hover:opacity-90"
                            style="background: linear-gradient(135deg, #c4956a, #b07d52);">
                        {{ form.processing ? 'Enregistrement…' : (editingCat ? 'Mettre à jour' : 'Créer') }}
                    </button>
                </div>
            </div>
        </div>
    </Transition>

    <div class="p-6 space-y-6">

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="font-cormorant text-3xl font-bold text-onyx-800">Catégories blog</h1>
                <p class="text-sm font-poppins text-onyx-500 mt-0.5">{{ categories.length }} catégorie(s)</p>
            </div>
            <button @click="openCreate"
                    class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-poppins font-semibold text-white shadow-md transition-all hover:opacity-90 hover:-translate-y-0.5"
                    style="background: linear-gradient(135deg, #c4956a, #b07d52);">
                <PhPlus :size="16" weight="bold" /> Nouvelle catégorie
            </button>
        </div>

        <!-- Flash -->
        <div v-if="flash?.success" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-poppins font-medium"
             style="background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.2); color: #059669;">
            <PhCheckCircle :size="18" />{{ flash.success }}
        </div>
        <div v-if="flash?.error" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-poppins font-medium"
             style="background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.2); color: #dc2626;">
            <PhXCircle :size="18" />{{ flash.error }}
        </div>

        <!-- Bento grid catégories -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <div v-for="cat in categories" :key="cat.id"
                 class="bg-white rounded-2xl shadow-card overflow-hidden group hover:shadow-float transition-all duration-300">

                <!-- Bande couleur en haut -->
                <div class="h-2" :style="{ background: cat.color }" />

                <div class="p-5">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center"
                                 :style="{ background: cat.color + '18' }">
                                <PhTag :size="18" :style="{ color: cat.color }" />
                            </div>
                            <div>
                                <p class="font-poppins font-bold text-onyx-800 text-sm">{{ cat.name }}</p>
                                <div class="flex items-center gap-1.5 mt-0.5">
                                    <span class="text-xs font-poppins"
                                          :style="{ color: cat.is_active ? '#059669' : '#9ca3af' }">
                                        {{ cat.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                    <span class="text-xs font-poppins text-onyx-400">·</span>
                                    <span class="text-xs font-poppins text-onyx-400">
                                        {{ cat.published_posts_count }}/{{ cat.posts_count }} articles publiés
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button @click="openEdit(cat)"
                                    class="w-7 h-7 rounded-lg flex items-center justify-center text-onyx-400 hover:text-onyx-700 hover:bg-cream-100 transition-colors">
                                <PhPencilSimple :size="14" />
                            </button>
                            <button @click="deleteCategory(cat)"
                                    class="w-7 h-7 rounded-lg flex items-center justify-center text-onyx-300 hover:text-red-500 hover:bg-red-50 transition-colors">
                                <PhTrash :size="14" />
                            </button>
                        </div>
                    </div>

                    <p v-if="cat.description" class="text-xs font-poppins text-onyx-400 leading-relaxed line-clamp-2">
                        {{ cat.description }}
                    </p>

                    <!-- Barre progression articles -->
                    <div class="mt-3">
                        <div class="h-1.5 rounded-full overflow-hidden bg-cream-100">
                            <div class="h-full rounded-full transition-all duration-500"
                                 :style="{
                                     width: cat.posts_count > 0 ? `${(cat.published_posts_count / cat.posts_count) * 100}%` : '0%',
                                     background: cat.color
                                 }" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Carte ajouter -->
            <button @click="openCreate"
                    class="bg-white rounded-2xl border-2 border-dashed border-cream-300 p-5 flex flex-col items-center justify-center gap-3 hover:border-cognac-400 hover:bg-cream-50 transition-all group min-h-32">
                <div class="w-10 h-10 rounded-2xl flex items-center justify-center border-2 border-dashed border-cream-300 group-hover:border-cognac-400 transition-colors">
                    <PhPlus :size="20" class="text-onyx-300 group-hover:text-cognac-500 transition-colors" />
                </div>
                <p class="text-xs font-poppins font-semibold text-onyx-400 group-hover:text-cognac-600 transition-colors">
                    Nouvelle catégorie
                </p>
            </button>
        </div>
    </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>