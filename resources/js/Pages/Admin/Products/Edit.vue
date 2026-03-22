<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import { gsap } from 'gsap'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import {
    PhPackage, PhArrowLeft, PhImage, PhX, PhPlus, PhTag,
    PhCurrencyEur, PhWarning, PhInfo, PhFloppyDisk,
    PhSpinnerGap, PhTrash, PhStar, PhCheckCircle,
} from '@phosphor-icons/vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
    product:    Object,
    categories: Array,
})

const breadcrumbs = [
    { label: 'Boutique' },
    { label: 'Produits', route: 'admin.produits.index' },
    { label: props.product.name },
]

// ── Formulaire ─────────────────────────────────────────────────────
const form = useForm({
    _method:           'PUT',
    category_id:       props.product.category_id ?? '',
    name:              props.product.name ?? '',
    short_description: props.product.short_description ?? '',
    description:       props.product.description ?? '',
    sku:               props.product.sku ?? '',
    price:             props.product.price ?? '',
    compare_price:     props.product.compare_price ?? '',
    cost_price:        props.product.cost_price ?? '',
    stock:             props.product.stock ?? 0,
    low_stock_alert:   props.product.low_stock_alert ?? 5,
    weight:            props.product.weight ?? '',
    dimensions:        props.product.dimensions ?? { length: '', width: '', height: '' },
    track_stock:       props.product.track_stock ?? true,
    allow_backorder:   props.product.allow_backorder ?? false,
    is_active:         props.product.is_active ?? true,
    is_featured:       props.product.is_featured ?? false,
    is_digital:        props.product.is_digital ?? false,
    thumbnail:         null,
    images:            [],
    tags:              props.product.tags ?? [],
    attributes:        props.product.attributes ?? [],
    sort_order:        props.product.sort_order ?? 0,
})

// ── Thumbnail ──────────────────────────────────────────────────────
const thumbnailPreview = ref(props.product.thumbnail_url ?? null)
const thumbnailChanged = ref(false)

function onThumbnailChange(e) {
    const file = e.target.files[0]
    if (!file) return
    form.thumbnail   = file
    thumbnailChanged.value = true
    const reader = new FileReader()
    reader.onload = (ev) => { thumbnailPreview.value = ev.target.result }
    reader.readAsDataURL(file)
}

// ── Images supplémentaires ─────────────────────────────────────────
const existingImages = ref(props.product.images ?? [])
const newImagePreviews = ref([])

function onImagesChange(e) {
    const files = Array.from(e.target.files)
    files.forEach(file => {
        form.images.push(file)
        const reader = new FileReader()
        reader.onload = (ev) => newImagePreviews.value.push({ src: ev.target.result, name: file.name })
        reader.readAsDataURL(file)
    })
}

function removeNewImage(index) {
    form.images.splice(index, 1)
    newImagePreviews.value.splice(index, 1)
}

function deleteExistingImage(image) {
    if (!confirm('Supprimer cette image ?')) return
    router.delete(route('admin.produits.images.delete', { product: props.product.id, image: image.id }), {
        preserveState: false,
        onSuccess: () => {
            existingImages.value = existingImages.value.filter(img => img.id !== image.id)
        },
    })
}

function setPrimaryImage(image) {
    router.patch(route('admin.produits.images.primary', { product: props.product.id, image: image.id }), {}, {
        preserveState: false,
    })
}

// ── Tags ───────────────────────────────────────────────────────────
const tagInput = ref('')

function addTag() {
    const tag = tagInput.value.trim()
    if (tag && !form.tags.includes(tag)) form.tags.push(tag)
    tagInput.value = ''
}

function removeTag(index) { form.tags.splice(index, 1) }

function onTagKeydown(e) {
    if (e.key === 'Enter' || e.key === ',') { e.preventDefault(); addTag() }
    if (e.key === 'Backspace' && !tagInput.value && form.tags.length) form.tags.pop()
}

// ── Marge calculée ─────────────────────────────────────────────────
const margin = computed(() => {
    const p = parseFloat(form.price)
    const c = parseFloat(form.cost_price)
    if (!p || !c || p === 0) return null
    return Math.round(((p - c) / p) * 100)
})

// ── Soumission ─────────────────────────────────────────────────────
function submit() {
    form.post(route('admin.produits.update', props.product.id), {
        forceFormData: true,
    })
}

// ── Onglets ────────────────────────────────────────────────────────
const activeTab = ref('general')
const tabs = [
    { key: 'general', label: 'Général' },
    { key: 'prix',    label: 'Prix & stock' },
    { key: 'media',   label: 'Médias' },
    { key: 'options', label: 'Options' },
]

onMounted(() => {
    gsap.fromTo('.form-section',
        { opacity: 0, y: 16 },
        { opacity: 1, y: 0, duration: 0.35, ease: 'power2.out' }
    )
})
</script>

<template>
    <Head :title="`Modifier — ${product.name}`" />

    <div class="p-6 max-w-5xl mx-auto space-y-6">

        <!-- ── Header ──────────────────────────────────────────────── -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <Link :href="route('admin.produits.show', product.id)"
                      class="w-9 h-9 rounded-xl flex items-center justify-center border border-cream-200 text-onyx-500 hover:text-onyx-800 hover:bg-cream-100 transition-colors bg-white shadow-card">
                    <PhArrowLeft :size="18" />
                </Link>
                <div>
                    <h1 class="font-cormorant text-3xl font-bold text-onyx-800">Modifier le produit</h1>
                    <p class="text-sm font-poppins text-onyx-500 mt-0.5">{{ product.name }}</p>
                </div>
            </div>

            <!-- Statut actuel -->
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-poppins font-semibold"
                 :class="product.is_active
                    ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                    : 'bg-gray-100 text-gray-500 border border-gray-200'">
                <span class="w-1.5 h-1.5 rounded-full"
                      :class="product.is_active ? 'bg-emerald-500' : 'bg-gray-400'" />
                {{ product.is_active ? 'Actif' : 'Inactif' }}
            </div>
        </div>

        <!-- ── Erreurs ──────────────────────────────────────────────── -->
        <div v-if="form.hasErrors"
             class="flex items-start gap-3 px-4 py-3 rounded-xl text-sm font-poppins"
             style="background: rgba(239,68,68,0.06); border: 1px solid rgba(239,68,68,0.2);">
            <PhWarning :size="18" class="text-red-500 shrink-0 mt-0.5" />
            <div>
                <p class="font-semibold text-red-700 mb-1">Erreurs à corriger :</p>
                <ul class="list-disc list-inside text-red-600 space-y-0.5 text-xs">
                    <li v-for="(error, field) in form.errors" :key="field">{{ error }}</li>
                </ul>
            </div>
        </div>

        <!-- ── Tabs ────────────────────────────────────────────────── -->
        <div class="flex items-center gap-1 p-1 bg-white rounded-2xl shadow-card">
            <button v-for="tab in tabs" :key="tab.key"
                    @click="activeTab = tab.key"
                    class="flex-1 py-2.5 rounded-xl text-sm font-poppins font-medium transition-all"
                    :class="activeTab === tab.key
                        ? 'bg-onyx-800 text-white shadow-sm'
                        : 'text-onyx-500 hover:text-onyx-700 hover:bg-cream-50'">
                {{ tab.label }}
            </button>
        </div>

        <form @submit.prevent="submit">

            <!-- ── Général ─────────────────────────────────────────── -->
            <div v-show="activeTab === 'general'" class="space-y-5 form-section">
                <div class="bg-white rounded-2xl shadow-card p-6 space-y-5">
                    <h2 class="font-poppins font-semibold text-onyx-800 flex items-center gap-2">
                        <PhPackage :size="18" class="text-cognac-500" />
                        Informations générales
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">
                                Catégorie <span class="text-red-400">*</span>
                            </label>
                            <select v-model="form.category_id"
                                    class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border transition-colors focus:outline-none"
                                    :class="form.errors.category_id ? 'border-red-300' : 'border-cream-200 focus:border-cognac-400'">
                                <option value="">Sélectionner</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                            </select>
                            <p v-if="form.errors.category_id" class="text-xs text-red-500 font-poppins mt-1">{{ form.errors.category_id }}</p>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">
                                Nom <span class="text-red-400">*</span>
                            </label>
                            <input v-model="form.name" type="text"
                                   class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border transition-colors focus:outline-none"
                                   :class="form.errors.name ? 'border-red-300' : 'border-cream-200 focus:border-cognac-400'" />
                            <p v-if="form.errors.name" class="text-xs text-red-500 font-poppins mt-1">{{ form.errors.name }}</p>
                        </div>
                    </div>

                    <div class="w-48">
                        <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">SKU</label>
                        <input v-model="form.sku" type="text"
                               class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none transition-colors" />
                        <p v-if="form.errors.sku" class="text-xs text-red-500 font-poppins mt-1">{{ form.errors.sku }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">Description courte</label>
                        <textarea v-model="form.short_description" rows="2"
                                  class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none transition-colors resize-none" />
                    </div>

                    <div>
                        <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">Description complète</label>
                        <textarea v-model="form.description" rows="6"
                                  class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none transition-colors resize-y" />
                    </div>

                    <div>
                        <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">Tags</label>
                        <div class="flex flex-wrap items-center gap-2 px-3 py-2 rounded-xl bg-cream-50 border border-cream-200 focus-within:border-cognac-400 transition-colors min-h-11">
                            <span v-for="(tag, i) in form.tags" :key="i"
                                  class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-poppins font-medium text-white"
                                  style="background: linear-gradient(135deg, #c4956a, #b07d52);">
                                <PhTag :size="11" />{{ tag }}
                                <button type="button" @click="removeTag(i)" class="hover:opacity-70"><PhX :size="11" /></button>
                            </span>
                            <input v-model="tagInput" @keydown="onTagKeydown" type="text"
                                   placeholder="Ajouter un tag"
                                   class="flex-1 min-w-24 text-sm font-poppins bg-transparent focus:outline-none text-onyx-700 placeholder-onyx-300" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Prix & Stock ────────────────────────────────────── -->
            <div v-show="activeTab === 'prix'" class="space-y-5 form-section">
                <div class="bg-white rounded-2xl shadow-card p-6 space-y-5">
                    <h2 class="font-poppins font-semibold text-onyx-800 flex items-center gap-2">
                        <PhCurrencyEur :size="18" class="text-cognac-500" />
                        Tarification
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">
                                Prix de vente <span class="text-red-400">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm font-poppins text-onyx-400">€</span>
                                <input v-model="form.price" type="number" step="0.01" min="0"
                                       class="w-full pl-8 pr-4 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border transition-colors focus:outline-none"
                                       :class="form.errors.price ? 'border-red-300' : 'border-cream-200 focus:border-cognac-400'" />
                            </div>
                            <p v-if="form.errors.price" class="text-xs text-red-500 font-poppins mt-1">{{ form.errors.price }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">Prix barré</label>
                            <div class="relative">
                                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm font-poppins text-onyx-400">€</span>
                                <input v-model="form.compare_price" type="number" step="0.01" min="0"
                                       class="w-full pl-8 pr-4 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none transition-colors" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">Prix d'achat</label>
                            <div class="relative">
                                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm font-poppins text-onyx-400">€</span>
                                <input v-model="form.cost_price" type="number" step="0.01" min="0"
                                       class="w-full pl-8 pr-4 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none transition-colors" />
                            </div>
                        </div>
                    </div>

                    <div v-if="margin !== null"
                         class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-poppins"
                         :style="margin >= 50
                            ? 'background: rgba(16,185,129,0.06); border: 1px solid rgba(16,185,129,0.2);'
                            : margin >= 20
                            ? 'background: rgba(245,158,11,0.06); border: 1px solid rgba(245,158,11,0.2);'
                            : 'background: rgba(239,68,68,0.06); border: 1px solid rgba(239,68,68,0.2);'">
                        <PhInfo :size="16" :class="margin >= 50 ? 'text-emerald-600' : margin >= 20 ? 'text-amber-600' : 'text-red-500'" />
                        <span :class="margin >= 50 ? 'text-emerald-700' : margin >= 20 ? 'text-amber-700' : 'text-red-600'">
                            Marge : <strong>{{ margin }}%</strong>
                        </span>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-card p-6 space-y-5">
                    <h2 class="font-poppins font-semibold text-onyx-800 flex items-center gap-2">
                        <PhWarning :size="18" class="text-cognac-500" />
                        Gestion du stock
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">
                                Quantité en stock <span class="text-red-400">*</span>
                            </label>
                            <input v-model="form.stock" type="number" min="0"
                                   class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border transition-colors focus:outline-none"
                                   :class="form.errors.stock ? 'border-red-300' : 'border-cream-200 focus:border-cognac-400'" />
                        </div>
                        <div>
                            <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">Seuil d'alerte</label>
                            <input v-model="form.low_stock_alert" type="number" min="0"
                                   class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none transition-colors" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <label class="flex items-center gap-3 p-4 rounded-xl border border-cream-200 cursor-pointer hover:border-cognac-300 hover:bg-cream-50 transition-all">
                            <input type="checkbox" v-model="form.track_stock" class="accent-cognac-500 w-4 h-4" />
                            <div>
                                <p class="text-sm font-poppins font-semibold text-onyx-700">Suivi du stock</p>
                                <p class="text-xs font-poppins text-onyx-400">Décompter à chaque vente</p>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 p-4 rounded-xl border border-cream-200 cursor-pointer hover:border-cognac-300 hover:bg-cream-50 transition-all">
                            <input type="checkbox" v-model="form.allow_backorder" class="accent-cognac-500 w-4 h-4" />
                            <div>
                                <p class="text-sm font-poppins font-semibold text-onyx-700">Commandes en rupture</p>
                                <p class="text-xs font-poppins text-onyx-400">Autoriser si stock = 0</p>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 p-4 rounded-xl border border-cream-200 cursor-pointer hover:border-cognac-300 hover:bg-cream-50 transition-all">
                            <input type="checkbox" v-model="form.is_digital" class="accent-cognac-500 w-4 h-4" />
                            <div>
                                <p class="text-sm font-poppins font-semibold text-onyx-700">Produit digital</p>
                                <p class="text-xs font-poppins text-onyx-400">Téléchargement uniquement</p>
                            </div>
                        </label>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">Poids (kg)</label>
                            <input v-model="form.weight" type="number" step="0.001" min="0"
                                   class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none transition-colors" />
                        </div>
                        <div>
                            <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">L (cm)</label>
                            <input v-model="form.dimensions.length" type="number" step="0.1"
                                   class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none transition-colors" />
                        </div>
                        <div>
                            <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">l (cm)</label>
                            <input v-model="form.dimensions.width" type="number" step="0.1"
                                   class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none transition-colors" />
                        </div>
                        <div>
                            <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">H (cm)</label>
                            <input v-model="form.dimensions.height" type="number" step="0.1"
                                   class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none transition-colors" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Médias ──────────────────────────────────────────── -->
            <div v-show="activeTab === 'media'" class="space-y-5 form-section">
                <div class="bg-white rounded-2xl shadow-card p-6 space-y-6">
                    <h2 class="font-poppins font-semibold text-onyx-800 flex items-center gap-2">
                        <PhImage :size="18" class="text-cognac-500" />
                        Images du produit
                    </h2>

                    <!-- Thumbnail -->
                    <div>
                        <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-3 uppercase tracking-wide">
                            Miniature principale
                        </label>
                        <div class="flex items-start gap-4">
                            <div class="relative w-40 h-40 rounded-2xl overflow-hidden group shrink-0">
                                <img :src="thumbnailPreview" class="w-full h-full object-cover"
                                     :class="thumbnailChanged ? 'ring-2 ring-cognac-400 ring-offset-2' : ''" />
                                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                    <label class="w-10 h-10 bg-white rounded-xl flex items-center justify-center cursor-pointer">
                                        <PhImage :size="17" class="text-onyx-700" />
                                        <input type="file" accept="image/*" class="hidden" @change="onThumbnailChange" />
                                    </label>
                                </div>
                            </div>
                            <div class="text-sm font-poppins text-onyx-500 space-y-1">
                                <p class="font-semibold text-onyx-700">Changer la miniature</p>
                                <p class="text-xs text-onyx-400">Cliquez sur l'image pour la remplacer</p>
                                <p class="text-xs text-onyx-400">JPG, PNG, WebP — max 5 Mo</p>
                                <span v-if="thumbnailChanged"
                                      class="inline-flex items-center gap-1 text-xs text-cognac-600 font-semibold">
                                    <PhCheckCircle :size="13" /> Nouvelle image sélectionnée
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Images existantes -->
                    <div v-if="existingImages.length">
                        <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-3 uppercase tracking-wide">
                            Images actuelles ({{ existingImages.length }})
                        </label>
                        <div class="flex flex-wrap gap-3">
                            <div v-for="img in existingImages" :key="img.id"
                                 class="relative w-28 h-28 rounded-xl overflow-hidden group"
                                 :class="img.is_primary ? 'ring-2 ring-cognac-400 ring-offset-2' : ''">
                                <img :src="img.url" class="w-full h-full object-cover" />
                                <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-1.5">
                                    <button type="button" @click="setPrimaryImage(img)"
                                            class="w-8 h-8 bg-yellow-500 rounded-lg flex items-center justify-center text-white"
                                            :title="img.is_primary ? 'Image principale' : 'Définir comme principale'">
                                        <PhStar :size="14" :weight="img.is_primary ? 'fill' : 'regular'" />
                                    </button>
                                    <button type="button" @click="deleteExistingImage(img)"
                                            class="w-8 h-8 bg-red-500 rounded-lg flex items-center justify-center text-white">
                                        <PhTrash :size="14" />
                                    </button>
                                </div>
                                <div v-if="img.is_primary"
                                     class="absolute bottom-1 left-1/2 -translate-x-1/2 px-2 py-0.5 rounded-md text-white text-xs font-poppins font-bold"
                                     style="background: rgba(196,149,106,0.9); font-size: 9px;">
                                    Principale
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Nouvelles images -->
                    <div>
                        <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-3 uppercase tracking-wide">
                            Ajouter des images
                        </label>
                        <div class="flex flex-wrap gap-3">
                            <div v-for="(img, i) in newImagePreviews" :key="i"
                                 class="relative w-28 h-28 rounded-xl overflow-hidden group ring-2 ring-cognac-300 ring-offset-2">
                                <img :src="img.src" class="w-full h-full object-cover" />
                                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                    <button type="button" @click="removeNewImage(i)"
                                            class="w-8 h-8 bg-red-500 rounded-lg flex items-center justify-center text-white">
                                        <PhX :size="14" />
                                    </button>
                                </div>
                            </div>
                            <label class="flex flex-col items-center justify-center w-28 h-28 rounded-xl border-2 border-dashed cursor-pointer transition-all hover:border-cognac-400 hover:bg-cognac-50"
                                   style="border-color: #e5d5c5;">
                                <PhPlus :size="20" class="text-onyx-300" />
                                <p class="text-xs font-poppins text-onyx-400 mt-1.5">Ajouter</p>
                                <input type="file" accept="image/*" multiple class="hidden" @change="onImagesChange" />
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Options ─────────────────────────────────────────── -->
            <div v-show="activeTab === 'options'" class="space-y-5 form-section">
                <div class="bg-white rounded-2xl shadow-card p-6 space-y-5">
                    <h2 class="font-poppins font-semibold text-onyx-800">Options de publication</h2>

                    <div class="space-y-3">
                        <label class="flex items-center justify-between p-4 rounded-xl border border-cream-200 cursor-pointer hover:border-cognac-300 hover:bg-cream-50 transition-all">
                            <div>
                                <p class="text-sm font-poppins font-semibold text-onyx-700">Produit actif</p>
                                <p class="text-xs font-poppins text-onyx-400">Visible sur la boutique</p>
                            </div>
                            <div class="relative w-12 h-6 rounded-full transition-colors cursor-pointer"
                                 :style="{ background: form.is_active ? '#c4956a' : '#d1d5db' }"
                                 @click="form.is_active = !form.is_active">
                                <div class="absolute top-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform"
                                     :class="form.is_active ? 'translate-x-6' : 'translate-x-0.5'" />
                            </div>
                        </label>
                        <label class="flex items-center justify-between p-4 rounded-xl border border-cream-200 cursor-pointer hover:border-cognac-300 hover:bg-cream-50 transition-all">
                            <div>
                                <p class="text-sm font-poppins font-semibold text-onyx-700">Produit en vedette</p>
                                <p class="text-xs font-poppins text-onyx-400">Affiché en priorité sur la vitrine</p>
                            </div>
                            <div class="relative w-12 h-6 rounded-full transition-colors cursor-pointer"
                                 :style="{ background: form.is_featured ? '#c4956a' : '#d1d5db' }"
                                 @click="form.is_featured = !form.is_featured">
                                <div class="absolute top-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform"
                                     :class="form.is_featured ? 'translate-x-6' : 'translate-x-0.5'" />
                            </div>
                        </label>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">Ordre d'affichage</label>
                            <input v-model="form.sort_order" type="number" min="0"
                                   class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none transition-colors" />
                        </div>
                    </div>

                    <!-- Zone danger -->
                    <div class="pt-4 mt-4 border-t border-cream-100">
                        <p class="text-xs font-poppins font-semibold text-onyx-500 uppercase tracking-wide mb-3">Zone de danger</p>
                        <Link :href="route('admin.produits.destroy', product.id)"
                              method="delete" as="button"
                              class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-poppins font-semibold text-red-600 border border-red-200 hover:bg-red-50 transition-colors"
                              onclick="return confirm('Supprimer définitivement ce produit ?')">
                            <PhTrash :size="16" />
                            Supprimer ce produit
                        </Link>
                    </div>
                </div>
            </div>

            <!-- ── Actions ─────────────────────────────────────────── -->
            <div class="flex items-center justify-between bg-white rounded-2xl shadow-card px-6 py-4">
                <Link :href="route('admin.produits.show', product.id)"
                      class="px-4 py-2.5 rounded-xl text-sm font-poppins font-medium text-onyx-600 border border-cream-200 hover:bg-cream-50 transition-colors">
                    Annuler
                </Link>
                <div class="flex items-center gap-3">
                    <p v-if="form.isDirty" class="text-xs font-poppins text-amber-600 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500" />
                        Modifications non enregistrées
                    </p>
                    <button type="submit" :disabled="form.processing"
                            class="flex items-center gap-2 px-5 py-2.5 rounded-xl font-poppins text-sm font-semibold text-white transition-all hover:opacity-90 hover:-translate-y-0.5 disabled:opacity-60 disabled:translate-y-0"
                            style="background: linear-gradient(135deg, #c4956a, #b07d52);">
                        <PhSpinnerGap v-if="form.processing" :size="17" class="animate-spin" />
                        <PhFloppyDisk v-else :size="17" />
                        {{ form.processing ? 'Enregistrement…' : 'Enregistrer les modifications' }}
                    </button>
                </div>
            </div>

        </form>
    </div>
</template>