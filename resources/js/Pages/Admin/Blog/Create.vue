<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { gsap } from 'gsap'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import {
    PhArrowLeft, PhFloppyDisk, PhSpinnerGap, PhRobot,
    PhMagicWand, PhLightbulb, PhArrowsClockwise, PhX,
    PhCheckCircle, PhWarning, PhEye, PhPencilSimple,
    PhTag, PhImage, PhGlobe, PhSparkle, PhCaretDown,
    PhPaperPlaneTilt,
} from '@phosphor-icons/vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
    categories: Array,
    ai_model:   Object,
})

// ── Formulaire principal ───────────────────────────────────────────
const form = useForm({
    blog_category_id: null,
    title:            '',
    slug:             '',
    excerpt:          '',
    content:          '',
    cover_image:      null,
    cover_image_alt:  '',
    status:           'draft',
    is_featured:      false,
    is_pinned:        false,
    published_at:     '',
    meta_title:       '',
    meta_description: '',
    meta_keywords:    '',
    tags:             [],
    ai_generated:     false,
    ai_model:         '',
})

// ── Tabs ───────────────────────────────────────────────────────────
const activeTab = ref('content') // content | seo | settings

// ── Preview image ──────────────────────────────────────────────────
const imagePreview = ref(null)
function onImageChange(e) {
    const file = e.target.files[0]
    if (!file) return
    form.cover_image = file
    const reader = new FileReader()
    reader.onload = ev => { imagePreview.value = ev.target.result }
    reader.readAsDataURL(file)
}

// ── Tags ───────────────────────────────────────────────────────────
const tagInput = ref('')
function addTag() {
    const t = tagInput.value.trim().toLowerCase()
    if (t && !form.tags.includes(t)) form.tags.push(t)
    tagInput.value = ''
}
function removeTag(tag) { form.tags = form.tags.filter(x => x !== tag) }

// ── Compteurs SEO ──────────────────────────────────────────────────
const metaTitleCount = computed(() => form.meta_title.length)
const metaDescCount  = computed(() => form.meta_description.length)

// ── Submit ─────────────────────────────────────────────────────────
function submit() {
    form.post(route('admin.blog.store'), {
        forceFormData: true,
        preserveScroll: true,
    })
}

// ══════════════════════════════════════════════════════════════════
// ── PANNEAU IA ────────────────────────────────────────────────────
// ══════════════════════════════════════════════════════════════════

const aiPanelOpen    = ref(false)
const aiLoading      = ref(false)
const aiError        = ref(null)
const aiStep         = ref('generate') // generate | improve | topics | titles

// Formulaire IA
const aiSubject   = ref('')
const aiTone      = ref('expert')
const aiKeywords  = ref('')
const aiWordCount = ref(600)

// Résultats IA
const aiTopics     = ref([])
const aiTitles     = ref([])
const aiImproveInstruction = ref('')

const tones = [
    { value: 'expert',         label: 'Expert & Professionnel' },
    { value: 'friendly',       label: 'Chaleureux & Proche' },
    { value: 'inspirational',  label: 'Inspirant & Poétique' },
    { value: 'educational',    label: 'Pédagogique & Clair' },
]

// Générer article complet
async function aiGenerate() {
    if (!aiSubject.value.trim()) return
    aiLoading.value = true
    aiError.value   = null

    try {
        const res = await window.axios.post(route('admin.blog.ai.generate'), {
            subject:    aiSubject.value,
            tone:       aiTone.value,
            keywords:   aiKeywords.value,
            word_count: aiWordCount.value,
        })

        const data = res.data.data
        form.title            = data.title       || form.title
        form.excerpt          = data.excerpt      || form.excerpt
        form.content          = data.content      || form.content
        form.meta_title       = data.meta_title   || form.meta_title
        form.meta_description = data.meta_description || form.meta_description
        form.meta_keywords    = data.meta_keywords || form.meta_keywords
        form.tags             = data.tags          || form.tags
        form.ai_generated     = true
        form.ai_model         = data.ai_model      || ''

        aiPanelOpen.value = false

    } catch (err) {
        aiError.value = err.response?.data?.error ?? 'Erreur inattendue.'
    } finally {
        aiLoading.value = false
    }
}

// Améliorer le contenu existant
async function aiImprove() {
    if (!form.content || !aiImproveInstruction.value.trim()) return
    aiLoading.value = true
    aiError.value   = null

    try {
        const res = await window.axios.post(route('admin.blog.ai.improve'), {
            content:     form.content,
            instruction: aiImproveInstruction.value,
        })
        form.content              = res.data.content
        aiImproveInstruction.value = ''
        aiPanelOpen.value         = false
    } catch (err) {
        aiError.value = err.response?.data?.error ?? 'Erreur.'
    } finally {
        aiLoading.value = false
    }
}

// Suggestions de sujets
async function aiGetTopics() {
    aiLoading.value = true
    aiError.value   = null

    try {
        const res = await window.axios.post(route('admin.blog.ai.suggest-topics'))
        aiTopics.value = res.data.topics
    } catch (err) {
        aiError.value = err.response?.data?.error ?? 'Erreur.'
    } finally {
        aiLoading.value = false
    }
}

// Propositions de titres
async function aiGetTitles() {
    if (!aiSubject.value.trim()) return
    aiLoading.value = true
    aiError.value   = null

    try {
        const res = await window.axios.post(route('admin.blog.ai.titles'), {
            subject: aiSubject.value,
        })
        aiTitles.value = res.data.proposals
    } catch (err) {
        aiError.value = err.response?.data?.error ?? 'Erreur.'
    } finally {
        aiLoading.value = false
    }
}

// Appliquer un titre proposé
function applyTitle(proposal) {
    form.title   = proposal.title
    form.excerpt = proposal.excerpt
    aiPanelOpen.value = false
}

// Appliquer un sujet suggéré
function applySuggestedTopic(topic) {
    aiSubject.value = topic
    aiStep.value    = 'generate'
    aiTopics.value  = []
}

onMounted(() => {
    gsap.fromTo('.form-section',
        { opacity: 0, y: 16 },
        { opacity: 1, y: 0, duration: 0.4, stagger: 0.07, ease: 'power2.out' }
    )
})
</script>

<template>
    <Head title="Nouvel article" />

    <!-- ── Panneau IA ──────────────────────────────────────────────── -->
    <Transition name="slide-right">
        <div v-if="aiPanelOpen"
             class="fixed inset-0 z-50 flex"
             @click.self="aiPanelOpen = false">
            <!-- Backdrop -->
            <div class="flex-1 bg-black/30 backdrop-blur-sm" @click="aiPanelOpen = false" />

            <!-- Drawer -->
            <div class="w-full max-w-md bg-white h-full shadow-2xl flex flex-col overflow-hidden">

                <!-- Header drawer -->
                <div class="flex items-center justify-between px-5 py-4"
                     style="background: linear-gradient(135deg, #0d0d1a, #1a1a2e); border-bottom: 1px solid rgba(196,149,106,0.2);">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-xl flex items-center justify-center"
                             style="background: rgba(196,149,106,0.2);">
                            <PhRobot :size="17" class="text-cognac-400" />
                        </div>
                        <div>
                            <p class="text-sm font-poppins font-bold text-white">Assistante IA</p>
                            <p class="text-xs font-poppins" style="color: rgba(196,149,106,0.8);">
                                {{ ai_model?.provider }} · {{ ai_model?.model }}
                            </p>
                        </div>
                    </div>
                    <button @click="aiPanelOpen = false" class="text-white/40 hover:text-white">
                        <PhX :size="18" />
                    </button>
                </div>

                <!-- Tabs IA -->
                <div class="flex border-b border-cream-200">
                    <button v-for="tab in [
                        { key: 'generate', label: 'Générer', icon: PhMagicWand },
                        { key: 'improve',  label: 'Améliorer', icon: PhSparkle },
                        { key: 'topics',   label: 'Sujets', icon: PhLightbulb },
                        { key: 'titles',   label: 'Titres', icon: PhPencilSimple },
                    ]" :key="tab.key"
                            @click="aiStep = tab.key"
                            class="flex-1 flex items-center justify-center gap-1.5 py-2.5 text-xs font-poppins font-medium transition-all border-b-2"
                            :class="aiStep === tab.key
                                ? 'border-cognac-500 text-cognac-700 bg-cognac-50/50'
                                : 'border-transparent text-onyx-500 hover:text-onyx-700'">
                        <component :is="tab.icon" :size="13" />
                        {{ tab.label }}
                    </button>
                </div>

                <!-- Corps IA -->
                <div class="flex-1 overflow-y-auto p-5 space-y-4">

                    <!-- Erreur -->
                    <div v-if="aiError"
                         class="flex items-start gap-2 px-3 py-2.5 rounded-xl text-xs font-poppins"
                         style="background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.2); color: #dc2626;">
                        <PhWarning :size="15" class="shrink-0 mt-0.5" />{{ aiError }}
                    </div>

                    <!-- ── Générer article complet ── -->
                    <template v-if="aiStep === 'generate'">
                        <div>
                            <label class="ai-label">Sujet de l'article *</label>
                            <input v-model="aiSubject" type="text"
                                   placeholder="Ex: Comment entretenir ses box braids..."
                                   class="ai-input" @keydown.enter="aiGenerate" />
                        </div>
                        <div>
                            <label class="ai-label">Ton rédactionnel</label>
                            <select v-model="aiTone" class="ai-input">
                                <option v-for="t in tones" :key="t.value" :value="t.value">{{ t.label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="ai-label">Mots-clés SEO (optionnel)</label>
                            <input v-model="aiKeywords" type="text"
                                   placeholder="tresses, soins, afro..."
                                   class="ai-input" />
                        </div>
                        <div>
                            <label class="ai-label">Longueur approximative</label>
                            <div class="flex items-center gap-3">
                                <input v-model="aiWordCount" type="range" min="200" max="1500" step="100"
                                       class="flex-1 accent-cognac-500" />
                                <span class="text-xs font-poppins font-semibold text-onyx-600 w-20 text-right">
                                    ~{{ aiWordCount }} mots
                                </span>
                            </div>
                        </div>
                        <button @click="aiGenerate" :disabled="aiLoading || !aiSubject.trim()"
                                class="w-full flex items-center justify-center gap-2 py-3 rounded-xl text-sm font-poppins font-semibold text-white disabled:opacity-60 transition-all hover:opacity-90"
                                style="background: linear-gradient(135deg, #c4956a, #b07d52);">
                            <PhSpinnerGap v-if="aiLoading" :size="17" class="animate-spin" />
                            <PhMagicWand v-else :size="17" />
                            {{ aiLoading ? 'Génération en cours…' : 'Générer l\'article' }}
                        </button>
                        <p class="text-xs font-poppins text-onyx-400 text-center">
                            ⚡ Powered by {{ ai_model?.provider }} — L'article sera entièrement rédigé et remplira tous les champs.
                        </p>
                    </template>

                    <!-- ── Améliorer contenu ── -->
                    <template v-if="aiStep === 'improve'">
                        <div class="px-3 py-2.5 rounded-xl text-xs font-poppins"
                             :class="form.content ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200'">
                            {{ form.content ? '✓ Contenu détecté — prêt à améliorer' : '⚠ Rédigez d\'abord du contenu dans l\'éditeur' }}
                        </div>
                        <div>
                            <label class="ai-label">Instruction d'amélioration *</label>
                            <textarea v-model="aiImproveInstruction" rows="3"
                                      placeholder="Ex: Rends le ton plus chaleureux, ajoute des exemples concrets, améliore le SEO..."
                                      class="ai-input resize-none" />
                        </div>
                        <button @click="aiImprove" :disabled="aiLoading || !form.content || !aiImproveInstruction.trim()"
                                class="w-full flex items-center justify-center gap-2 py-3 rounded-xl text-sm font-poppins font-semibold text-white disabled:opacity-60"
                                style="background: linear-gradient(135deg, #6366f1, #4f46e5);">
                            <PhSpinnerGap v-if="aiLoading" :size="17" class="animate-spin" />
                            <PhSparkle v-else :size="17" />
                            {{ aiLoading ? 'Amélioration…' : 'Améliorer le contenu' }}
                        </button>
                    </template>

                    <!-- ── Suggestions sujets ── -->
                    <template v-if="aiStep === 'topics'">
                        <button @click="aiGetTopics" :disabled="aiLoading"
                                class="w-full flex items-center justify-center gap-2 py-3 rounded-xl text-sm font-poppins font-semibold text-white disabled:opacity-60"
                                style="background: linear-gradient(135deg, #10b981, #059669);">
                            <PhSpinnerGap v-if="aiLoading" :size="17" class="animate-spin" />
                            <PhLightbulb v-else :size="17" />
                            {{ aiLoading ? 'Génération…' : 'Obtenir des idées d\'articles' }}
                        </button>
                        <div v-if="aiTopics.length" class="space-y-2">
                            <p class="text-xs font-poppins font-semibold text-onyx-600 uppercase tracking-wide">
                                Idées générées — clique pour utiliser
                            </p>
                            <button v-for="(topic, i) in aiTopics" :key="i"
                                    @click="applySuggestedTopic(topic)"
                                    class="w-full text-left px-3.5 py-2.5 rounded-xl text-sm font-poppins text-onyx-700 bg-cream-50 border border-cream-200 hover:border-cognac-300 hover:bg-cream-100 transition-all">
                                {{ topic }}
                            </button>
                        </div>
                    </template>

                    <!-- ── Propositions titres ── -->
                    <template v-if="aiStep === 'titles'">
                        <div>
                            <label class="ai-label">Sujet pour les titres</label>
                            <input v-model="aiSubject" type="text"
                                   placeholder="Sujet de l'article..."
                                   class="ai-input" />
                        </div>
                        <button @click="aiGetTitles" :disabled="aiLoading || !aiSubject.trim()"
                                class="w-full flex items-center justify-center gap-2 py-3 rounded-xl text-sm font-poppins font-semibold text-white disabled:opacity-60"
                                style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                            <PhSpinnerGap v-if="aiLoading" :size="17" class="animate-spin" />
                            <PhPencilSimple v-else :size="17" />
                            {{ aiLoading ? 'Génération…' : 'Générer 3 titres' }}
                        </button>
                        <div v-if="aiTitles.length" class="space-y-2">
                            <p class="text-xs font-poppins font-semibold text-onyx-600 uppercase tracking-wide">
                                Propositions
                            </p>
                            <div v-for="(proposal, i) in aiTitles" :key="i"
                                 class="p-3 rounded-xl border border-cream-200 bg-white hover:border-cognac-300 transition-all cursor-pointer"
                                 @click="applyTitle(proposal)">
                                <p class="text-sm font-poppins font-semibold text-onyx-800 mb-1">{{ proposal.title }}</p>
                                <p class="text-xs font-poppins text-onyx-400">{{ proposal.excerpt }}</p>
                                <p class="text-xs font-poppins text-cognac-500 mt-1 font-medium">Cliquer pour appliquer →</p>
                            </div>
                        </div>
                    </template>

                </div>
            </div>
        </div>
    </Transition>

    <!-- ── Page principale ─────────────────────────────────────────── -->
    <div class="p-6 space-y-5">

        <!-- Header -->
        <div class="form-section flex items-center justify-between gap-4 flex-wrap">
            <div class="flex items-center gap-3">
                <Link :href="route('admin.blog.index')"
                      class="w-9 h-9 rounded-xl flex items-center justify-center border border-cream-200 bg-white shadow-card text-onyx-500 hover:bg-cream-100 transition-colors">
                    <PhArrowLeft :size="17" />
                </Link>
                <div>
                    <h1 class="font-cormorant text-3xl font-bold text-onyx-800">Nouvel article</h1>
                    <p v-if="form.ai_generated" class="text-xs font-poppins flex items-center gap-1.5 mt-0.5"
                       style="color: #c4956a;">
                        <PhRobot :size="12" /> Contenu généré par IA · {{ form.ai_model }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <!-- Bouton IA -->
                <button @click="aiPanelOpen = true"
                        class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-poppins font-semibold text-white transition-all hover:opacity-90 hover:-translate-y-0.5 shadow-md"
                        style="background: linear-gradient(135deg, #0d0d1a, #1a1a2e); border: 1px solid rgba(196,149,106,0.3);">
                    <PhRobot :size="16" />
                    IA Rédaction
                    <span class="ml-1 px-1.5 py-0.5 rounded-md text-xs font-bold"
                          style="background: rgba(196,149,106,0.25); color: #c4956a;">
                        {{ ai_model?.provider }}
                    </span>
                </button>

                <!-- Statut -->
                <select v-model="form.status"
                        class="px-3 py-2.5 rounded-xl text-sm font-poppins bg-white border border-cream-200 focus:border-cognac-400 focus:outline-none text-onyx-700">
                    <option value="draft">Brouillon</option>
                    <option value="published">Publié</option>
                    <option value="archived">Archivé</option>
                </select>

                <!-- Sauvegarder -->
                <button @click="submit" :disabled="form.processing"
                        class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-poppins font-semibold text-white disabled:opacity-60 transition-all hover:opacity-90 hover:-translate-y-0.5 shadow-md"
                        style="background: linear-gradient(135deg, #c4956a, #b07d52);">
                    <PhSpinnerGap v-if="form.processing" :size="17" class="animate-spin" />
                    <PhFloppyDisk v-else :size="17" />
                    {{ form.processing ? 'Enregistrement…' : 'Enregistrer' }}
                </button>
            </div>
        </div>

        <!-- Tabs -->
        <div class="form-section flex items-center gap-1 p-1 bg-white rounded-2xl shadow-card w-fit">
            <button v-for="tab in [
                { key: 'content',  label: 'Contenu',   icon: PhPencilSimple },
                { key: 'seo',      label: 'SEO',        icon: PhGlobe        },
                { key: 'settings', label: 'Paramètres', icon: PhTag          },
            ]" :key="tab.key"
                    @click="activeTab = tab.key"
                    class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-poppins font-medium transition-all"
                    :class="activeTab === tab.key
                        ? 'bg-onyx-800 text-white'
                        : 'text-onyx-500 hover:text-onyx-700'">
                <component :is="tab.icon" :size="14" />
                {{ tab.label }}
            </button>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-4 gap-5">

            <!-- ── Contenu principal ─────────────────────────────── -->
            <div class="xl:col-span-3 space-y-4">

                <!-- TAB CONTENT -->
                <template v-if="activeTab === 'content'">

                    <!-- Titre -->
                    <div class="form-section bg-white rounded-2xl shadow-card p-5 space-y-3">
                        <div>
                            <label class="field-label">Titre <span class="text-red-400">*</span></label>
                            <input v-model="form.title" type="text" placeholder="Titre de l'article"
                                   class="w-full px-4 py-3 rounded-xl text-lg font-poppins font-semibold bg-cream-50 border transition-colors focus:outline-none"
                                   :class="form.errors.title ? 'border-red-300' : 'border-cream-200 focus:border-cognac-400'" />
                            <p v-if="form.errors.title" class="text-xs text-red-500 font-poppins mt-1">{{ form.errors.title }}</p>
                        </div>
                        <div>
                            <label class="field-label">Extrait (accroche)</label>
                            <textarea v-model="form.excerpt" rows="2" placeholder="Résumé accrocheur visible dans les listes…"
                                      class="w-full px-4 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none transition-colors resize-none" />
                            <p class="text-xs font-poppins text-onyx-400 mt-1">{{ form.excerpt.length }} / 500 caractères</p>
                        </div>
                    </div>

                    <!-- Contenu HTML -->
                    <div class="form-section bg-white rounded-2xl shadow-card overflow-hidden">
                        <div class="flex items-center justify-between px-5 py-3 border-b border-cream-100">
                            <p class="text-xs font-poppins font-semibold text-onyx-600 uppercase tracking-wide">Contenu HTML</p>
                            <button @click="aiStep = 'improve'; aiPanelOpen = true"
                                    class="flex items-center gap-1.5 text-xs font-poppins text-cognac-600 hover:text-cognac-800 font-medium transition-colors">
                                <PhSparkle :size="13" /> Améliorer avec l'IA
                            </button>
                        </div>
                        <textarea v-model="form.content" rows="20"
                                  placeholder="<h2>Introduction</h2><p>Contenu de l'article...</p>"
                                  class="w-full px-5 py-4 text-sm font-mono bg-cream-50/30 text-onyx-800 focus:outline-none resize-none border-0"
                                  :class="form.errors.content ? 'ring-2 ring-red-200' : ''" />
                        <p v-if="form.errors.content" class="px-5 pb-3 text-xs text-red-500 font-poppins">{{ form.errors.content }}</p>
                    </div>

                    <!-- Image de couverture -->
                    <div class="form-section bg-white rounded-2xl shadow-card p-5 space-y-3">
                        <p class="text-xs font-poppins font-semibold text-onyx-600 uppercase tracking-wide flex items-center gap-2">
                            <PhImage :size="14" class="text-cognac-500" /> Image de couverture
                        </p>
                        <div v-if="imagePreview" class="relative">
                            <img :src="imagePreview" class="w-full h-48 object-cover rounded-xl" />
                            <button @click="imagePreview = null; form.cover_image = null"
                                    class="absolute top-2 right-2 w-7 h-7 rounded-lg bg-black/50 text-white flex items-center justify-center hover:bg-black/70 transition-colors">
                                <PhX :size="13" />
                            </button>
                        </div>
                        <label v-else class="flex flex-col items-center justify-center h-32 rounded-xl border-2 border-dashed border-cream-300 cursor-pointer hover:border-cognac-400 transition-colors bg-cream-50/50">
                            <PhImage :size="28" class="text-onyx-300 mb-2" />
                            <p class="text-xs font-poppins text-onyx-400">Cliquer pour choisir une image</p>
                            <p class="text-xs font-poppins text-onyx-300">JPG, PNG — 3 Mo max</p>
                            <input type="file" accept="image/*" class="hidden" @change="onImageChange" />
                        </label>
                        <input v-model="form.cover_image_alt" type="text" placeholder="Texte alternatif (alt) — important pour le SEO"
                               class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none transition-colors" />
                    </div>
                </template>

                <!-- TAB SEO -->
                <template v-if="activeTab === 'seo'">
                    <div class="form-section bg-white rounded-2xl shadow-card p-5 space-y-4">
                        <h2 class="font-poppins font-semibold text-onyx-800 text-sm flex items-center gap-2">
                            <PhGlobe :size="16" class="text-cognac-500" /> Optimisation SEO
                        </h2>

                        <!-- Aperçu Google -->
                        <div class="p-4 rounded-xl" style="background: #f8f9fa; border: 1px solid #e8eaed;">
                            <p class="text-xs font-poppins text-onyx-400 mb-2">Aperçu dans Google</p>
                            <p class="text-base font-medium" style="color: #1a0dab; font-family: Arial; line-height: 1.3;">
                                {{ form.meta_title || form.title || 'Titre de l\'article' }}
                            </p>
                            <p class="text-xs" style="color: #006621; font-family: Arial;">
                                patricia-braids.fr/blog/{{ form.slug || 'slug-de-larticle' }}
                            </p>
                            <p class="text-xs mt-0.5" style="color: #545454; font-family: Arial; line-height: 1.4;">
                                {{ form.meta_description || form.excerpt || 'Description de l\'article qui apparaît dans les résultats de recherche…' }}
                            </p>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="field-label">Titre SEO (meta title)</label>
                                    <span class="text-xs font-poppins"
                                          :class="metaTitleCount > 60 ? 'text-red-500 font-semibold' : metaTitleCount > 50 ? 'text-amber-500' : 'text-emerald-600'">
                                        {{ metaTitleCount }}/60
                                    </span>
                                </div>
                                <input v-model="form.meta_title" type="text"
                                       :placeholder="form.title || 'Titre SEO optimisé'"
                                       class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none transition-colors" />
                            </div>

                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="field-label">Meta description</label>
                                    <span class="text-xs font-poppins"
                                          :class="metaDescCount > 160 ? 'text-red-500 font-semibold' : metaDescCount > 140 ? 'text-amber-500' : 'text-emerald-600'">
                                        {{ metaDescCount }}/160
                                    </span>
                                </div>
                                <textarea v-model="form.meta_description" rows="3"
                                          placeholder="Description pour les moteurs de recherche (155 caractères max)"
                                          class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none transition-colors resize-none" />
                            </div>

                            <div>
                                <label class="field-label">Mots-clés (séparés par des virgules)</label>
                                <input v-model="form.meta_keywords" type="text"
                                       placeholder="tresses, braids, soins capillaires afro"
                                       class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none transition-colors" />
                            </div>

                            <div>
                                <label class="field-label">Slug URL</label>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-poppins text-onyx-400 shrink-0">/blog/</span>
                                    <input v-model="form.slug" type="text"
                                           placeholder="slug-de-l-article"
                                           class="flex-1 px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none transition-colors" />
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- TAB SETTINGS -->
                <template v-if="activeTab === 'settings'">
                    <div class="form-section bg-white rounded-2xl shadow-card p-5 space-y-4">
                        <h2 class="font-poppins font-semibold text-onyx-800 text-sm">Paramètres de publication</h2>

                        <div class="grid grid-cols-2 gap-4">
                            <label class="flex items-center gap-3 p-3 rounded-xl border border-cream-200 cursor-pointer hover:bg-cream-50 transition-colors">
                                <input type="checkbox" v-model="form.is_featured" class="accent-cognac-500 w-4 h-4 rounded" />
                                <div>
                                    <p class="text-sm font-poppins font-semibold text-onyx-700">Mis en avant</p>
                                    <p class="text-xs font-poppins text-onyx-400">Grande carte Bento</p>
                                </div>
                            </label>
                            <label class="flex items-center gap-3 p-3 rounded-xl border border-cream-200 cursor-pointer hover:bg-cream-50 transition-colors">
                                <input type="checkbox" v-model="form.is_pinned" class="accent-cognac-500 w-4 h-4 rounded" />
                                <div>
                                    <p class="text-sm font-poppins font-semibold text-onyx-700">Épinglé</p>
                                    <p class="text-xs font-poppins text-onyx-400">Toujours en premier</p>
                                </div>
                            </label>
                        </div>

                        <div>
                            <label class="field-label">Date de publication</label>
                            <input v-model="form.published_at" type="datetime-local"
                                   class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none transition-colors" />
                            <p class="text-xs font-poppins text-onyx-400 mt-1">Laisser vide pour publier immédiatement si le statut est "Publié"</p>
                        </div>
                    </div>
                </template>
            </div>

            <!-- ── Sidebar droite ────────────────────────────────── -->
            <div class="space-y-4">

                <!-- Catégorie -->
                <div class="form-section bg-white rounded-2xl shadow-card p-4 space-y-2">
                    <label class="field-label">Catégorie</label>
                    <select v-model="form.blog_category_id"
                            class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none">
                        <option :value="null">Sans catégorie</option>
                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                    </select>
                </div>

                <!-- Tags -->
                <div class="form-section bg-white rounded-2xl shadow-card p-4 space-y-2">
                    <label class="field-label">Tags</label>
                    <div class="flex flex-wrap gap-1.5 mb-2">
                        <span v-for="tag in form.tags" :key="tag"
                              class="flex items-center gap-1 px-2.5 py-1 rounded-xl text-xs font-poppins font-medium"
                              style="background: rgba(196,149,106,0.12); color: #b07d52;">
                            {{ tag }}
                            <button @click="removeTag(tag)" class="hover:text-red-500 transition-colors">
                                <PhX :size="11" />
                            </button>
                        </span>
                    </div>
                    <div class="flex gap-2">
                        <input v-model="tagInput" type="text" placeholder="Ajouter un tag…"
                               @keydown.enter.prevent="addTag"
                               class="flex-1 px-3 py-2 rounded-xl text-xs font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none" />
                        <button @click="addTag"
                                class="px-3 py-2 rounded-xl text-xs font-poppins font-semibold bg-cognac-50 text-cognac-700 hover:bg-cognac-100 transition-colors">
                            +
                        </button>
                    </div>
                </div>

                <!-- Statut rapide -->
                <div class="form-section bg-white rounded-2xl shadow-card p-4 space-y-2">
                    <label class="field-label">Statut</label>
                    <div class="space-y-1.5">
                        <label v-for="s in [
                            { value: 'draft', label: 'Brouillon', color: '#6b7280' },
                            { value: 'published', label: 'Publié', color: '#10b981' },
                            { value: 'archived', label: 'Archivé', color: '#9ca3af' },
                        ]" :key="s.value"
                               class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" v-model="form.status" :value="s.value" class="accent-cognac-500" />
                            <span class="text-sm font-poppins" :style="{ color: form.status === s.value ? s.color : '#6b7280' }">
                                {{ s.label }}
                            </span>
                        </label>
                    </div>
                </div>

                <!-- IA badge -->
                <div v-if="form.ai_generated"
                     class="form-section rounded-2xl p-3.5 text-xs font-poppins"
                     style="background: linear-gradient(135deg, rgba(13,13,26,0.05), rgba(196,149,106,0.08)); border: 1px solid rgba(196,149,106,0.2);">
                    <div class="flex items-center gap-2 mb-1">
                        <PhRobot :size="14" class="text-cognac-500" />
                        <span class="font-semibold text-onyx-700">Généré par IA</span>
                    </div>
                    <p class="text-onyx-500">Modèle : {{ form.ai_model }}</p>
                    <p class="text-onyx-400 mt-0.5">Pense à relire et personnaliser avant publication.</p>
                </div>

            </div>
        </div>
    </div>
</template>

<style scoped>
@reference "../../../../css/app.css";
.ai-label  { @apply block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide; }
.ai-input  { @apply w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none transition-colors; }
.field-label { @apply block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide; }

.slide-right-enter-active { transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94); }
.slide-right-leave-active { transition: all 0.25s ease; }
.slide-right-enter-from .fixed > div:last-child { transform: translateX(100%); }
.slide-right-leave-to .fixed > div:last-child   { transform: translateX(100%); }
</style>