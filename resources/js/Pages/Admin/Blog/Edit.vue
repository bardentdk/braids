<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import { gsap } from 'gsap'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import {
    PhArrowLeft, PhFloppyDisk, PhSpinnerGap, PhRobot,
    PhMagicWand, PhLightbulb, PhX, PhCheckCircle, PhWarning,
    PhPencilSimple, PhTag, PhImage, PhGlobe, PhSparkle,
} from '@phosphor-icons/vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
    post:       Object,
    categories: Array,
    ai_model:   Object,
})

const page  = usePage()
const flash = computed(() => page.props.flash)

// ── Formulaire pré-rempli ─────────────────────────────────────────
const form = useForm({
    blog_category_id: props.post.blog_category_id ?? null,
    title:            props.post.title            ?? '',
    slug:             props.post.slug             ?? '',
    excerpt:          props.post.excerpt          ?? '',
    content:          props.post.content          ?? '',
    cover_image:      null,
    cover_image_alt:  props.post.cover_image_alt  ?? '',
    status:           props.post.status           ?? 'draft',
    is_featured:      props.post.is_featured      ?? false,
    is_pinned:        props.post.is_pinned        ?? false,
    published_at:     props.post.published_at
        ? new Date(props.post.published_at).toISOString().slice(0, 16)
        : '',
    meta_title:       props.post.meta_title        ?? '',
    meta_description: props.post.meta_description  ?? '',
    meta_keywords:    props.post.meta_keywords     ?? '',
    tags:             props.post.tags              ?? [],
    _method:          'PATCH',
})

const activeTab    = ref('content')
const imagePreview = ref(props.post.cover_image_url ?? null)

function onImageChange(e) {
    const file = e.target.files[0]
    if (!file) return
    form.cover_image = file
    const reader = new FileReader()
    reader.onload = ev => { imagePreview.value = ev.target.result }
    reader.readAsDataURL(file)
}

const tagInput = ref('')
function addTag() {
    const t = tagInput.value.trim().toLowerCase()
    if (t && !form.tags.includes(t)) form.tags.push(t)
    tagInput.value = ''
}
function removeTag(tag) { form.tags = form.tags.filter(x => x !== tag) }

const metaTitleCount = computed(() => form.meta_title.length)
const metaDescCount  = computed(() => form.meta_description.length)

function submit() {
    form.post(route('admin.blog.update', props.post.id), {
        forceFormData: true,
        preserveScroll: true,
    })
}

// ── Panneau IA (améliorer uniquement en édition) ──────────────────
const aiPanelOpen  = ref(false)
const aiLoading    = ref(false)
const aiError      = ref(null)
const aiInstruction = ref('')

async function aiImprove() {
    if (!form.content || !aiInstruction.value.trim()) return
    aiLoading.value = true
    aiError.value   = null

    try {
        const res = await window.axios.post(route('admin.blog.ai.improve'), {
            content:     form.content,
            instruction: aiInstruction.value,
        })
        form.content     = res.data.content
        aiInstruction.value = ''
        aiPanelOpen.value   = false
    } catch (err) {
        aiError.value = err.response?.data?.error ?? 'Erreur.'
    } finally {
        aiLoading.value = false
    }
}

onMounted(() => {
    gsap.fromTo('.form-section',
        { opacity: 0, y: 14 },
        { opacity: 1, y: 0, duration: 0.4, stagger: 0.06, ease: 'power2.out' }
    )
})
</script>

<template>
    <Head :title="`Modifier — ${post.title}`" />

    <!-- Panneau IA (amélioration) -->
    <Transition name="slide-right">
        <div v-if="aiPanelOpen" class="fixed inset-0 z-50 flex" @click.self="aiPanelOpen = false">
            <div class="flex-1" @click="aiPanelOpen = false" />
            <div class="w-full max-w-sm bg-white h-full shadow-2xl flex flex-col">
                <div class="flex items-center justify-between px-5 py-4"
                     style="background: linear-gradient(135deg, #0d0d1a, #1a1a2e);">
                    <div class="flex items-center gap-3">
                        <PhRobot :size="17" class="text-cognac-400" />
                        <p class="text-sm font-poppins font-bold text-white">Améliorer avec l'IA</p>
                    </div>
                    <button @click="aiPanelOpen = false" class="text-white/40 hover:text-white">
                        <PhX :size="17" />
                    </button>
                </div>
                <div class="flex-1 p-5 space-y-4">
                    <div v-if="aiError" class="px-3 py-2.5 rounded-xl text-xs font-poppins text-red-600 bg-red-50 border border-red-200">
                        {{ aiError }}
                    </div>
                    <div>
                        <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">Instruction</label>
                        <textarea v-model="aiInstruction" rows="4"
                                  placeholder="Ex: Rends le ton plus chaleureux, ajoute des exemples concrets…"
                                  class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none resize-none" />
                    </div>
                    <button @click="aiImprove" :disabled="aiLoading || !aiInstruction.trim()"
                            class="w-full flex items-center justify-center gap-2 py-3 rounded-xl text-sm font-poppins font-semibold text-white disabled:opacity-60"
                            style="background: linear-gradient(135deg, #6366f1, #4f46e5);">
                        <PhSpinnerGap v-if="aiLoading" :size="16" class="animate-spin" />
                        <PhSparkle v-else :size="16" />
                        {{ aiLoading ? 'Amélioration…' : 'Améliorer le contenu' }}
                    </button>
                </div>
            </div>
        </div>
    </Transition>

    <div class="p-6 space-y-5">

        <!-- Header -->
        <div class="form-section flex items-center justify-between gap-4 flex-wrap">
            <div class="flex items-center gap-3">
                <Link :href="route('admin.blog.index')"
                      class="w-9 h-9 rounded-xl flex items-center justify-center border border-cream-200 bg-white shadow-card text-onyx-500 hover:bg-cream-100 transition-colors">
                    <PhArrowLeft :size="17" />
                </Link>
                <div>
                    <h1 class="font-cormorant text-3xl font-bold text-onyx-800">Modifier l'article</h1>
                    <p class="text-xs font-poppins text-onyx-400 mt-0.5 truncate max-w-xs">{{ post.title }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button @click="aiPanelOpen = true"
                        class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-poppins font-semibold text-white"
                        style="background: linear-gradient(135deg, #0d0d1a, #1a1a2e); border: 1px solid rgba(196,149,106,0.3);">
                    <PhRobot :size="15" /> Améliorer avec l'IA
                </button>
                <select v-model="form.status"
                        class="px-3 py-2.5 rounded-xl text-sm font-poppins bg-white border border-cream-200 focus:border-cognac-400 focus:outline-none text-onyx-700">
                    <option value="draft">Brouillon</option>
                    <option value="published">Publié</option>
                    <option value="archived">Archivé</option>
                </select>
                <button @click="submit" :disabled="form.processing"
                        class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-poppins font-semibold text-white disabled:opacity-60 transition-all hover:opacity-90 hover:-translate-y-0.5 shadow-md"
                        style="background: linear-gradient(135deg, #c4956a, #b07d52);">
                    <PhSpinnerGap v-if="form.processing" :size="16" class="animate-spin" />
                    <PhFloppyDisk v-else :size="16" />
                    {{ form.processing ? 'Enregistrement…' : 'Mettre à jour' }}
                </button>
            </div>
        </div>

        <!-- Flash -->
        <div v-if="flash?.success" class="form-section flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-poppins font-medium"
             style="background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.2); color: #059669;">
            <PhCheckCircle :size="18" />{{ flash.success }}
        </div>

        <!-- Tabs -->
        <div class="form-section flex items-center gap-1 p-1 bg-white rounded-2xl shadow-card w-fit">
            <button v-for="tab in [
                { key: 'content',  label: 'Contenu',   icon: PhPencilSimple },
                { key: 'seo',      label: 'SEO',        icon: PhGlobe },
                { key: 'settings', label: 'Paramètres', icon: PhTag },
            ]" :key="tab.key"
                    @click="activeTab = tab.key"
                    class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-poppins font-medium transition-all"
                    :class="activeTab === tab.key ? 'bg-onyx-800 text-white' : 'text-onyx-500 hover:text-onyx-700'">
                <component :is="tab.icon" :size="14" />{{ tab.label }}
            </button>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-4 gap-5">
            <div class="xl:col-span-3 space-y-4">

                <!-- TAB CONTENT -->
                <template v-if="activeTab === 'content'">
                    <div class="form-section bg-white rounded-2xl shadow-card p-5 space-y-3">
                        <div>
                            <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">Titre *</label>
                            <input v-model="form.title" type="text"
                                   class="w-full px-4 py-3 rounded-xl text-lg font-poppins font-semibold bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none transition-colors" />
                        </div>
                        <div>
                            <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">Extrait</label>
                            <textarea v-model="form.excerpt" rows="2"
                                      class="w-full px-4 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none resize-none" />
                        </div>
                    </div>

                    <div class="form-section bg-white rounded-2xl shadow-card overflow-hidden">
                        <div class="flex items-center justify-between px-5 py-3 border-b border-cream-100">
                            <p class="text-xs font-poppins font-semibold text-onyx-600 uppercase tracking-wide">Contenu HTML</p>
                            <button @click="aiPanelOpen = true"
                                    class="flex items-center gap-1.5 text-xs font-poppins text-cognac-600 hover:text-cognac-800 font-medium">
                                <PhSparkle :size="13" /> Améliorer avec l'IA
                            </button>
                        </div>
                        <textarea v-model="form.content" rows="20"
                                  class="w-full px-5 py-4 text-sm font-mono bg-cream-50/30 text-onyx-800 focus:outline-none resize-none border-0" />
                    </div>

                    <!-- Image -->
                    <div class="form-section bg-white rounded-2xl shadow-card p-5 space-y-3">
                        <p class="text-xs font-poppins font-semibold text-onyx-600 uppercase tracking-wide flex items-center gap-2">
                            <PhImage :size="14" class="text-cognac-500" /> Image de couverture
                        </p>
                        <div v-if="imagePreview" class="relative">
                            <img :src="imagePreview" class="w-full h-48 object-cover rounded-xl" />
                            <button @click="imagePreview = null; form.cover_image = null"
                                    class="absolute top-2 right-2 w-7 h-7 rounded-lg bg-black/50 text-white flex items-center justify-center">
                                <PhX :size="13" />
                            </button>
                        </div>
                        <label v-else class="flex flex-col items-center justify-center h-28 rounded-xl border-2 border-dashed border-cream-300 cursor-pointer hover:border-cognac-400 bg-cream-50/50">
                            <PhImage :size="24" class="text-onyx-300 mb-1.5" />
                            <p class="text-xs font-poppins text-onyx-400">Choisir une image</p>
                            <input type="file" accept="image/*" class="hidden" @change="onImageChange" />
                        </label>
                        <input v-model="form.cover_image_alt" type="text" placeholder="Texte alternatif (alt SEO)"
                               class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none" />
                    </div>
                </template>

                <!-- TAB SEO -->
                <template v-if="activeTab === 'seo'">
                    <div class="form-section bg-white rounded-2xl shadow-card p-5 space-y-4">
                        <h2 class="font-poppins font-semibold text-onyx-800 text-sm flex items-center gap-2">
                            <PhGlobe :size="16" class="text-cognac-500" /> Optimisation SEO
                        </h2>
                        <div class="p-4 rounded-xl" style="background: #f8f9fa; border: 1px solid #e8eaed;">
                            <p class="text-xs font-poppins text-onyx-400 mb-1.5">Aperçu Google</p>
                            <p class="text-base font-medium" style="color: #1a0dab; font-family: Arial;">{{ form.meta_title || form.title }}</p>
                            <p class="text-xs" style="color: #006621; font-family: Arial;">patricia-braids.fr/blog/{{ form.slug }}</p>
                            <p class="text-xs mt-0.5" style="color: #545454; font-family: Arial; line-height: 1.4;">{{ form.meta_description || form.excerpt }}</p>
                        </div>
                        <div class="space-y-3">
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="block text-xs font-poppins font-semibold text-onyx-600 uppercase tracking-wide">Meta title</label>
                                    <span class="text-xs font-poppins" :class="metaTitleCount > 60 ? 'text-red-500 font-semibold' : 'text-emerald-600'">{{ metaTitleCount }}/60</span>
                                </div>
                                <input v-model="form.meta_title" type="text"
                                       class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none" />
                            </div>
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="block text-xs font-poppins font-semibold text-onyx-600 uppercase tracking-wide">Meta description</label>
                                    <span class="text-xs font-poppins" :class="metaDescCount > 160 ? 'text-red-500 font-semibold' : 'text-emerald-600'">{{ metaDescCount }}/160</span>
                                </div>
                                <textarea v-model="form.meta_description" rows="3"
                                          class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none resize-none" />
                            </div>
                            <div>
                                <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">Mots-clés</label>
                                <input v-model="form.meta_keywords" type="text"
                                       class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none" />
                            </div>
                            <div>
                                <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">Slug URL</label>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-poppins text-onyx-400">/blog/</span>
                                    <input v-model="form.slug" type="text"
                                           class="flex-1 px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none" />
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- TAB SETTINGS -->
                <template v-if="activeTab === 'settings'">
                    <div class="form-section bg-white rounded-2xl shadow-card p-5 space-y-4">
                        <h2 class="font-poppins font-semibold text-onyx-800 text-sm">Paramètres</h2>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="flex items-center gap-3 p-3 rounded-xl border border-cream-200 cursor-pointer hover:bg-cream-50">
                                <input type="checkbox" v-model="form.is_featured" class="accent-cognac-500 w-4 h-4" />
                                <div>
                                    <p class="text-sm font-poppins font-semibold text-onyx-700">Mis en avant</p>
                                    <p class="text-xs font-poppins text-onyx-400">Grande carte Bento</p>
                                </div>
                            </label>
                            <label class="flex items-center gap-3 p-3 rounded-xl border border-cream-200 cursor-pointer hover:bg-cream-50">
                                <input type="checkbox" v-model="form.is_pinned" class="accent-cognac-500 w-4 h-4" />
                                <div>
                                    <p class="text-sm font-poppins font-semibold text-onyx-700">Épinglé</p>
                                    <p class="text-xs font-poppins text-onyx-400">Toujours en premier</p>
                                </div>
                            </label>
                        </div>
                        <div>
                            <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">Date de publication</label>
                            <input v-model="form.published_at" type="datetime-local"
                                   class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none" />
                        </div>
                    </div>
                </template>
            </div>

            <!-- Sidebar -->
            <div class="space-y-4">
                <div class="form-section bg-white rounded-2xl shadow-card p-4 space-y-2">
                    <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">Catégorie</label>
                    <select v-model="form.blog_category_id"
                            class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none">
                        <option :value="null">Sans catégorie</option>
                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                    </select>
                </div>
                <div class="form-section bg-white rounded-2xl shadow-card p-4 space-y-2">
                    <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">Tags</label>
                    <div class="flex flex-wrap gap-1.5 mb-2">
                        <span v-for="tag in form.tags" :key="tag"
                              class="flex items-center gap-1 px-2.5 py-1 rounded-xl text-xs font-poppins font-medium"
                              style="background: rgba(196,149,106,0.12); color: #b07d52;">
                            {{ tag }}
                            <button @click="removeTag(tag)"><PhX :size="11" /></button>
                        </span>
                    </div>
                    <div class="flex gap-2">
                        <input v-model="tagInput" type="text" placeholder="Tag…"
                               @keydown.enter.prevent="addTag"
                               class="flex-1 px-3 py-2 rounded-xl text-xs font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none" />
                        <button @click="addTag" class="px-3 py-2 rounded-xl text-xs font-poppins font-semibold bg-cognac-50 text-cognac-700 hover:bg-cognac-100">+</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.slide-right-enter-active { transition: all 0.3s ease; }
.slide-right-leave-active { transition: all 0.2s ease; }
.slide-right-enter-from .fixed > div:last-child,
.slide-right-leave-to .fixed > div:last-child { transform: translateX(100%); }
</style>