<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { gsap } from 'gsap'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import {
    PhArrowLeft, PhScissors, PhImage, PhX, PhPlus, PhClock,
    PhCurrencyEur, PhWarning, PhFloppyDisk, PhSpinnerGap,
    PhCheckCircle, PhInfo, PhUsers,
} from '@phosphor-icons/vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
    categories: Array,
})

const breadcrumbs = [
    { label: 'Services', route: 'admin.services.index' },
    { label: 'Nouveau service' },
]

// ── Formulaire ─────────────────────────────────────────────────────
const form = useForm({
    name:                  '',
    category:              '',
    short_description:     '',
    description:           '',
    duration:              60,
    buffer_time:           15,
    price:                 '',
    deposit_amount:        '',
    deposit_required:      false,
    image:                 null,
    includes:              [],
    requirements:          [],
    max_clients_per_slot:  1,
    is_active:             true,
    is_featured:           false,
    requires_consultation: false,
    sort_order:            0,
})

// ── Image preview ──────────────────────────────────────────────────
const imagePreview = ref(null)

function onImageChange(e) {
    const file = e.target.files[0]
    if (!file) return
    form.image = file
    const reader = new FileReader()
    reader.onload = (ev) => { imagePreview.value = ev.target.result }
    reader.readAsDataURL(file)
}

function removeImage() {
    form.image    = null
    imagePreview.value = null
}

// ── Durée formatée ─────────────────────────────────────────────────
const durationFormatted = computed(() => {
    const d = parseInt(form.duration) || 0
    const h = Math.floor(d / 60)
    const m = d % 60
    if (h && m) return `${h}h${m < 10 ? '0' + m : m}`
    if (h) return `${h}h`
    return `${m}min`
})

// ── Durées prédéfinies ─────────────────────────────────────────────
const durationPresets = [
    { label: '30 min',  value: 30 },
    { label: '1h',      value: 60 },
    { label: '1h30',    value: 90 },
    { label: '2h',      value: 120 },
    { label: '3h',      value: 180 },
    { label: '4h',      value: 240 },
    { label: '6h',      value: 360 },
    { label: '8h',      value: 480 },
]

// ── Includes / Requirements dynamiques ────────────────────────────
const includeInput    = ref('')
const requirementInput = ref('')

function addInclude() {
    const val = includeInput.value.trim()
    if (val) { form.includes.push(val); includeInput.value = '' }
}
function removeInclude(i) { form.includes.splice(i, 1) }
function onIncludeKey(e) {
    if (e.key === 'Enter') { e.preventDefault(); addInclude() }
}

function addRequirement() {
    const val = requirementInput.value.trim()
    if (val) { form.requirements.push(val); requirementInput.value = '' }
}
function removeRequirement(i) { form.requirements.splice(i, 1) }
function onRequirementKey(e) {
    if (e.key === 'Enter') { e.preventDefault(); addRequirement() }
}

// ── Onglets ────────────────────────────────────────────────────────
const activeTab = ref('general')
const tabs = [
    { key: 'general',  label: 'Général' },
    { key: 'tarif',    label: 'Tarif & durée' },
    { key: 'details',  label: 'Détails' },
    { key: 'media',    label: 'Image' },
    { key: 'options',  label: 'Options' },
]

// ── Soumission ─────────────────────────────────────────────────────
function submit() {
    form.post(route('admin.services.store'), { forceFormData: true })
}

onMounted(() => {
    gsap.fromTo('.form-section',
        { opacity: 0, y: 18 },
        { opacity: 1, y: 0, duration: 0.4, stagger: 0.07, ease: 'power2.out' }
    )
})
</script>

<template>
    <Head title="Nouveau service" />

    <div class="p-6 max-w-4xl mx-auto space-y-6">

        <!-- ── Header ─────────────────────────────────────────────── -->
        <div class="flex items-center gap-4">
            <Link :href="route('admin.services.index')"
                  class="w-9 h-9 rounded-xl flex items-center justify-center border border-cream-200 bg-white shadow-card text-onyx-500 hover:text-onyx-800 hover:bg-cream-100 transition-colors">
                <PhArrowLeft :size="18" />
            </Link>
            <div>
                <h1 class="font-cormorant text-3xl font-bold text-onyx-800">Nouveau service</h1>
                <p class="text-sm font-poppins text-onyx-500 mt-0.5">Ajoutez une prestation à votre catalogue</p>
            </div>
        </div>

        <!-- ── Erreurs ─────────────────────────────────────────────── -->
        <div v-if="form.hasErrors"
             class="flex items-start gap-3 px-4 py-3 rounded-xl text-sm font-poppins"
             style="background: rgba(239,68,68,0.06); border: 1px solid rgba(239,68,68,0.2);">
            <PhWarning :size="18" class="text-red-500 shrink-0 mt-0.5" />
            <div>
                <p class="font-semibold text-red-700 mb-1">Erreurs à corriger :</p>
                <ul class="list-disc list-inside text-red-600 text-xs space-y-0.5">
                    <li v-for="(error, field) in form.errors" :key="field">{{ error }}</li>
                </ul>
            </div>
        </div>

        <!-- ── Tabs ────────────────────────────────────────────────── -->
        <div class="flex items-center gap-1 p-1 bg-white rounded-2xl shadow-card">
            <button v-for="tab in tabs" :key="tab.key" @click="activeTab = tab.key"
                    class="flex-1 py-2.5 rounded-xl text-sm font-poppins font-medium transition-all"
                    :class="activeTab === tab.key
                        ? 'bg-onyx-800 text-white shadow-sm'
                        : 'text-onyx-500 hover:text-onyx-700 hover:bg-cream-50'">
                {{ tab.label }}
            </button>
        </div>

        <form @submit.prevent="submit">

            <!-- ── TAB Général ────────────────────────────────────── -->
            <div v-show="activeTab === 'general'" class="space-y-5 form-section">
                <div class="bg-white rounded-2xl shadow-card p-6 space-y-5">
                    <h2 class="font-poppins font-semibold text-onyx-800 flex items-center gap-2">
                        <PhScissors :size="18" class="text-cognac-500" />
                        Informations générales
                    </h2>

                    <!-- Catégorie + Nom -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">
                                Catégorie <span class="text-red-400">*</span>
                            </label>
                            <select v-model="form.category"
                                    class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border transition-colors focus:outline-none"
                                    :class="form.errors.category ? 'border-red-300' : 'border-cream-200 focus:border-cognac-400'">
                                <option value="">Sélectionner</option>
                                <option v-for="cat in categories" :key="cat.value" :value="cat.value">
                                    {{ cat.label }}
                                </option>
                            </select>
                            <p v-if="form.errors.category" class="text-xs text-red-500 font-poppins mt-1">{{ form.errors.category }}</p>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">
                                Nom du service <span class="text-red-400">*</span>
                            </label>
                            <input v-model="form.name" type="text" placeholder="Ex: Box Braids — Longueur moyenne"
                                   class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border transition-colors focus:outline-none"
                                   :class="form.errors.name ? 'border-red-300' : 'border-cream-200 focus:border-cognac-400'" />
                            <p v-if="form.errors.name" class="text-xs text-red-500 font-poppins mt-1">{{ form.errors.name }}</p>
                        </div>
                    </div>

                    <!-- Description courte -->
                    <div>
                        <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">
                            Description courte
                        </label>
                        <textarea v-model="form.short_description" rows="2"
                                  placeholder="Accroche affichée sur la carte du service..."
                                  class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none transition-colors resize-none" />
                        <p class="text-xs text-onyx-400 font-poppins mt-1">
                            {{ (form.short_description || '').length }}/500
                        </p>
                    </div>

                    <!-- Description complète -->
                    <div>
                        <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">
                            Description complète
                        </label>
                        <textarea v-model="form.description" rows="5"
                                  placeholder="Technique utilisée, types de cheveux compatibles, préparation, etc."
                                  class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none transition-colors resize-y" />
                    </div>
                </div>
            </div>

            <!-- ── TAB Tarif & durée ───────────────────────────────── -->
            <div v-show="activeTab === 'tarif'" class="space-y-5 form-section">

                <!-- Durée -->
                <div class="bg-white rounded-2xl shadow-card p-6 space-y-5">
                    <h2 class="font-poppins font-semibold text-onyx-800 flex items-center gap-2">
                        <PhClock :size="18" class="text-cognac-500" />
                        Durée de la prestation
                    </h2>

                    <!-- Presets -->
                    <div>
                        <p class="text-xs font-poppins font-semibold text-onyx-600 mb-2 uppercase tracking-wide">Durée rapide</p>
                        <div class="flex flex-wrap gap-2">
                            <button v-for="preset in durationPresets" :key="preset.value"
                                    type="button"
                                    @click="form.duration = preset.value"
                                    class="px-3 py-1.5 rounded-xl text-xs font-poppins font-semibold transition-all border"
                                    :class="form.duration === preset.value
                                        ? 'text-white border-transparent'
                                        : 'border-cream-200 text-onyx-600 hover:border-cognac-300 hover:bg-cream-50'"
                                    :style="form.duration === preset.value
                                        ? 'background: linear-gradient(135deg, #c4956a, #b07d52);'
                                        : ''">
                                {{ preset.label }}
                            </button>
                        </div>
                    </div>

                    <!-- Durée manuelle -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">
                                Durée (minutes) <span class="text-red-400">*</span>
                            </label>
                            <div class="flex items-center gap-3">
                                <input v-model.number="form.duration" type="number" min="15" max="1440" step="5"
                                       class="flex-1 px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border transition-colors focus:outline-none"
                                       :class="form.errors.duration ? 'border-red-300' : 'border-cream-200 focus:border-cognac-400'" />
                                <span class="px-3 py-2 rounded-xl text-sm font-poppins font-bold text-cognac-600"
                                      style="background: rgba(196,149,106,0.1);">
                                    {{ durationFormatted }}
                                </span>
                            </div>
                            <p v-if="form.errors.duration" class="text-xs text-red-500 font-poppins mt-1">{{ form.errors.duration }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">
                                Temps tampon (min)
                            </label>
                            <input v-model.number="form.buffer_time" type="number" min="0" max="120" step="5"
                                   class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none transition-colors" />
                            <p class="text-xs text-onyx-400 font-poppins mt-1">Pause entre deux RDV</p>
                        </div>
                    </div>

                    <!-- Info durée totale -->
                    <div class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-poppins"
                         style="background: rgba(196,149,106,0.06); border: 1px solid rgba(196,149,106,0.2);">
                        <PhInfo :size="16" class="text-cognac-500 shrink-0" />
                        <span class="text-cognac-700">
                            Durée totale créneaux : <strong>
                                {{ Math.floor((form.duration + form.buffer_time) / 60) > 0
                                    ? Math.floor((form.duration + form.buffer_time) / 60) + 'h' : '' }}
                                {{ (form.duration + form.buffer_time) % 60 > 0
                                    ? (form.duration + form.buffer_time) % 60 + 'min' : '' }}
                            </strong> (prestation + temps tampon)
                        </span>
                    </div>
                </div>

                <!-- Prix -->
                <div class="bg-white rounded-2xl shadow-card p-6 space-y-5">
                    <h2 class="font-poppins font-semibold text-onyx-800 flex items-center gap-2">
                        <PhCurrencyEur :size="18" class="text-cognac-500" />
                        Tarification
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">
                                Prix de la prestation <span class="text-red-400">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm font-poppins text-onyx-400">€</span>
                                <input v-model="form.price" type="number" step="0.01" min="0" placeholder="0.00"
                                       class="w-full pl-8 pr-4 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border transition-colors focus:outline-none"
                                       :class="form.errors.price ? 'border-red-300' : 'border-cream-200 focus:border-cognac-400'" />
                            </div>
                            <p v-if="form.errors.price" class="text-xs text-red-500 font-poppins mt-1">{{ form.errors.price }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">
                                Montant acompte
                            </label>
                            <div class="relative">
                                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm font-poppins text-onyx-400">€</span>
                                <input v-model="form.deposit_amount" type="number" step="0.01" min="0" placeholder="0.00"
                                       class="w-full pl-8 pr-4 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none transition-colors" />
                            </div>
                        </div>
                    </div>

                    <label class="flex items-center gap-3 p-4 rounded-xl border border-cream-200 cursor-pointer hover:border-cognac-300 hover:bg-cream-50 transition-all">
                        <input type="checkbox" v-model="form.deposit_required" class="accent-cognac-500 w-4 h-4" />
                        <div>
                            <p class="text-sm font-poppins font-semibold text-onyx-700">Acompte obligatoire</p>
                            <p class="text-xs font-poppins text-onyx-400">
                                La cliente devra régler l'acompte lors de la réservation
                            </p>
                        </div>
                    </label>
                </div>
            </div>

            <!-- ── TAB Détails ─────────────────────────────────────── -->
            <div v-show="activeTab === 'details'" class="space-y-5 form-section">

                <!-- Ce qui est inclus -->
                <div class="bg-white rounded-2xl shadow-card p-6 space-y-4">
                    <h2 class="font-poppins font-semibold text-onyx-800 flex items-center gap-2">
                        <PhCheckCircle :size="18" class="text-emerald-500" />
                        Ce qui est inclus
                    </h2>
                    <p class="text-xs font-poppins text-onyx-400">Ex: Démêlage, Shampooing, Séchage...</p>

                    <!-- Liste -->
                    <div class="space-y-2">
                        <div v-for="(item, i) in form.includes" :key="i"
                             class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-emerald-50 border border-emerald-100">
                            <PhCheckCircle :size="15" class="text-emerald-500 shrink-0" />
                            <span class="flex-1 text-sm font-poppins text-onyx-700">{{ item }}</span>
                            <button type="button" @click="removeInclude(i)"
                                    class="text-onyx-300 hover:text-red-400 transition-colors">
                                <PhX :size="15" />
                            </button>
                        </div>
                    </div>

                    <!-- Ajout -->
                    <div class="flex gap-2">
                        <input v-model="includeInput" @keydown="onIncludeKey" type="text"
                               placeholder="Ajouter un élément inclus..."
                               class="flex-1 px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none transition-colors" />
                        <button type="button" @click="addInclude"
                                class="px-4 py-2.5 rounded-xl text-sm font-poppins font-semibold text-white transition-all hover:opacity-90"
                                style="background: linear-gradient(135deg, #c4956a, #b07d52);">
                            <PhPlus :size="16" weight="bold" />
                        </button>
                    </div>
                </div>

                <!-- Prérequis -->
                <div class="bg-white rounded-2xl shadow-card p-6 space-y-4">
                    <h2 class="font-poppins font-semibold text-onyx-800 flex items-center gap-2">
                        <PhWarning :size="18" class="text-amber-500" />
                        Prérequis clients
                    </h2>
                    <p class="text-xs font-poppins text-onyx-400">Ex: Cheveux lavés, Longueur minimum 5cm...</p>

                    <div class="space-y-2">
                        <div v-for="(item, i) in form.requirements" :key="i"
                             class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-amber-50 border border-amber-100">
                            <PhWarning :size="15" class="text-amber-500 shrink-0" />
                            <span class="flex-1 text-sm font-poppins text-onyx-700">{{ item }}</span>
                            <button type="button" @click="removeRequirement(i)"
                                    class="text-onyx-300 hover:text-red-400 transition-colors">
                                <PhX :size="15" />
                            </button>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <input v-model="requirementInput" @keydown="onRequirementKey" type="text"
                               placeholder="Ajouter un prérequis..."
                               class="flex-1 px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none transition-colors" />
                        <button type="button" @click="addRequirement"
                                class="px-4 py-2.5 rounded-xl text-sm font-poppins font-semibold text-white transition-all hover:opacity-90"
                                style="background: linear-gradient(135deg, #c4956a, #b07d52);">
                            <PhPlus :size="16" weight="bold" />
                        </button>
                    </div>
                </div>

                <!-- Clients par créneau -->
                <div class="bg-white rounded-2xl shadow-card p-6">
                    <h2 class="font-poppins font-semibold text-onyx-800 flex items-center gap-2 mb-4">
                        <PhUsers :size="18" class="text-cognac-500" />
                        Capacité
                    </h2>
                    <div class="w-48">
                        <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">
                            Clients maximum par créneau
                        </label>
                        <input v-model.number="form.max_clients_per_slot" type="number" min="1" max="10"
                               class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none transition-colors" />
                        <p class="text-xs text-onyx-400 font-poppins mt-1">1 = RDV individuel</p>
                    </div>
                </div>
            </div>

            <!-- ── TAB Image ───────────────────────────────────────── -->
            <div v-show="activeTab === 'media'" class="space-y-5 form-section">
                <div class="bg-white rounded-2xl shadow-card p-6 space-y-5">
                    <h2 class="font-poppins font-semibold text-onyx-800 flex items-center gap-2">
                        <PhImage :size="18" class="text-cognac-500" />
                        Photo du service
                    </h2>

                    <div v-if="imagePreview"
                         class="relative w-full max-w-sm aspect-video rounded-2xl overflow-hidden group">
                        <img :src="imagePreview" class="w-full h-full object-cover" />
                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                            <label class="w-10 h-10 bg-white rounded-xl flex items-center justify-center cursor-pointer hover:bg-cream-50 shadow">
                                <PhImage :size="18" class="text-onyx-700" />
                                <input type="file" accept="image/*" class="hidden" @change="onImageChange" />
                            </label>
                            <button type="button" @click="removeImage"
                                    class="w-10 h-10 bg-red-500 rounded-xl flex items-center justify-center text-white hover:bg-red-600 shadow">
                                <PhX :size="18" />
                            </button>
                        </div>
                    </div>

                    <label v-else
                           class="flex flex-col items-center justify-center w-full max-w-sm aspect-video rounded-2xl border-2 border-dashed cursor-pointer transition-all hover:border-cognac-400 hover:bg-cognac-50"
                           style="border-color: #e5d5c5;">
                        <div class="w-14 h-14 rounded-2xl flex items-center justify-center mb-3"
                             style="background: rgba(196,149,106,0.1);">
                            <PhImage :size="28" class="text-cognac-500" />
                        </div>
                        <p class="text-sm font-poppins font-semibold text-onyx-600">Ajouter une photo</p>
                        <p class="text-xs font-poppins text-onyx-400 mt-1">JPG, PNG, WebP — max 5 Mo</p>
                        <p class="text-xs font-poppins text-onyx-400">Format recommandé : 16/9</p>
                        <input type="file" accept="image/*" class="hidden" @change="onImageChange" />
                    </label>

                    <p v-if="form.errors.image" class="text-xs text-red-500 font-poppins">{{ form.errors.image }}</p>
                </div>
            </div>

            <!-- ── TAB Options ─────────────────────────────────────── -->
            <div v-show="activeTab === 'options'" class="space-y-5 form-section">
                <div class="bg-white rounded-2xl shadow-card p-6 space-y-4">
                    <h2 class="font-poppins font-semibold text-onyx-800">Options de publication</h2>

                    <div class="space-y-3">
                        <label class="flex items-center justify-between p-4 rounded-xl border border-cream-200 cursor-pointer hover:border-cognac-300 hover:bg-cream-50 transition-all">
                            <div>
                                <p class="text-sm font-poppins font-semibold text-onyx-700">Service actif</p>
                                <p class="text-xs font-poppins text-onyx-400">Visible et réservable en ligne</p>
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
                                <p class="text-sm font-poppins font-semibold text-onyx-700">Service en vedette</p>
                                <p class="text-xs font-poppins text-onyx-400">Mis en avant sur la vitrine</p>
                            </div>
                            <div class="relative w-12 h-6 rounded-full transition-colors cursor-pointer"
                                 :style="{ background: form.is_featured ? '#c4956a' : '#d1d5db' }"
                                 @click="form.is_featured = !form.is_featured">
                                <div class="absolute top-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform"
                                     :class="form.is_featured ? 'translate-x-6' : 'translate-x-0.5'" />
                            </div>
                        </label>

                        <label class="flex items-center justify-between p-4 rounded-xl border border-cream-200 cursor-pointer hover:border-cognac-300 hover:bg-cream-50 transition-all">
                            <div>
                                <p class="text-sm font-poppins font-semibold text-onyx-700">Consultation requise</p>
                                <p class="text-xs font-poppins text-onyx-400">Nécessite un échange préalable avant confirmation</p>
                            </div>
                            <div class="relative w-12 h-6 rounded-full transition-colors cursor-pointer"
                                 :style="{ background: form.requires_consultation ? '#c4956a' : '#d1d5db' }"
                                 @click="form.requires_consultation = !form.requires_consultation">
                                <div class="absolute top-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform"
                                     :class="form.requires_consultation ? 'translate-x-6' : 'translate-x-0.5'" />
                            </div>
                        </label>
                    </div>

                    <div class="w-32">
                        <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">
                            Ordre d'affichage
                        </label>
                        <input v-model.number="form.sort_order" type="number" min="0"
                               class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none transition-colors" />
                        <p class="text-xs text-onyx-400 font-poppins mt-1">0 = affiché en premier</p>
                    </div>
                </div>
            </div>

            <!-- ── Actions ─────────────────────────────────────────── -->
            <div class="flex items-center justify-between bg-white rounded-2xl shadow-card px-6 py-4">
                <Link :href="route('admin.services.index')"
                      class="px-4 py-2.5 rounded-xl text-sm font-poppins font-medium text-onyx-600 border border-cream-200 hover:bg-cream-50 transition-colors">
                    Annuler
                </Link>
                <div class="flex items-center gap-3">
                    <p v-if="form.isDirty" class="text-xs font-poppins text-onyx-400">Non enregistré</p>
                    <button type="submit" :disabled="form.processing"
                            class="flex items-center gap-2 px-5 py-2.5 rounded-xl font-poppins text-sm font-semibold text-white transition-all hover:opacity-90 hover:-translate-y-0.5 disabled:opacity-60 disabled:translate-y-0"
                            style="background: linear-gradient(135deg, #c4956a, #b07d52);">
                        <PhSpinnerGap v-if="form.processing" :size="17" class="animate-spin" />
                        <PhFloppyDisk v-else :size="17" />
                        {{ form.processing ? 'Enregistrement…' : 'Créer le service' }}
                    </button>
                </div>
            </div>

        </form>
    </div>
</template>