<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import { gsap } from 'gsap'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import {
    PhArrowLeft, PhScissors, PhImage, PhX, PhPlus, PhClock,
    PhCurrencyEur, PhWarning, PhFloppyDisk, PhSpinnerGap,
    PhCheckCircle, PhInfo, PhUsers, PhTrash,
} from '@phosphor-icons/vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
    service:    Object,
    categories: Array,
})

const breadcrumbs = [
    { label: 'Services', route: 'admin.services.index' },
    { label: props.service.name },
]

// ── Formulaire ─────────────────────────────────────────────────────
const form = useForm({
    _method:               'PUT',
    name:                  props.service.name ?? '',
    category:              props.service.category ?? '',
    short_description:     props.service.short_description ?? '',
    description:           props.service.description ?? '',
    duration:              props.service.duration ?? 60,
    buffer_time:           props.service.buffer_time ?? 15,
    price:                 props.service.price ?? '',
    deposit_amount:        props.service.deposit_amount ?? '',
    deposit_required:      props.service.deposit_required ?? false,
    image:                 null,
    includes:              props.service.includes ?? [],
    requirements:          props.service.requirements ?? [],
    max_clients_per_slot:  props.service.max_clients_per_slot ?? 1,
    is_active:             props.service.is_active ?? true,
    is_featured:           props.service.is_featured ?? false,
    requires_consultation: props.service.requires_consultation ?? false,
    sort_order:            props.service.sort_order ?? 0,
})

// ── Image ──────────────────────────────────────────────────────────
const imagePreview    = ref(props.service.image_url ?? null)
const imageChanged    = ref(false)

function onImageChange(e) {
    const file = e.target.files[0]
    if (!file) return
    form.image     = file
    imageChanged.value = true
    const reader = new FileReader()
    reader.onload = (ev) => { imagePreview.value = ev.target.result }
    reader.readAsDataURL(file)
}

// ── Durée ──────────────────────────────────────────────────────────
const durationFormatted = computed(() => {
    const d = parseInt(form.duration) || 0
    const h = Math.floor(d / 60)
    const m = d % 60
    if (h && m) return `${h}h${m < 10 ? '0' + m : m}`
    if (h) return `${h}h`
    return `${m}min`
})

const durationPresets = [
    { label: '30 min', value: 30  },
    { label: '1h',     value: 60  },
    { label: '1h30',   value: 90  },
    { label: '2h',     value: 120 },
    { label: '3h',     value: 180 },
    { label: '4h',     value: 240 },
    { label: '6h',     value: 360 },
    { label: '8h',     value: 480 },
]

// ── Includes / Requirements ────────────────────────────────────────
const includeInput     = ref('')
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
    { key: 'general', label: 'Général' },
    { key: 'tarif',   label: 'Tarif & durée' },
    { key: 'details', label: 'Détails' },
    { key: 'media',   label: 'Image' },
    { key: 'options', label: 'Options' },
]

// ── Soumission ─────────────────────────────────────────────────────
function submit() {
    form.post(route('admin.services.update', props.service.id), { forceFormData: true })
}

onMounted(() => {
    gsap.fromTo('.form-section',
        { opacity: 0, y: 16 },
        { opacity: 1, y: 0, duration: 0.35, ease: 'power2.out' }
    )
})
</script>

<template>
    <Head :title="`Modifier — ${service.name}`" />

    <div class="p-6 max-w-4xl mx-auto space-y-6">

        <!-- ── Header ─────────────────────────────────────────────── -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <Link :href="route('admin.services.show', service.id)"
                      class="w-9 h-9 rounded-xl flex items-center justify-center border border-cream-200 bg-white shadow-card text-onyx-500 hover:text-onyx-800 hover:bg-cream-100 transition-colors">
                    <PhArrowLeft :size="18" />
                </Link>
                <div>
                    <h1 class="font-cormorant text-3xl font-bold text-onyx-800">Modifier le service</h1>
                    <p class="text-sm font-poppins text-onyx-500 mt-0.5">{{ service.name }}</p>
                </div>
            </div>

            <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-poppins font-semibold border"
                 :class="service.is_active
                    ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                    : 'bg-gray-100 text-gray-500 border-gray-200'">
                <span class="w-1.5 h-1.5 rounded-full"
                      :class="service.is_active ? 'bg-emerald-500' : 'bg-gray-400'" />
                {{ service.is_active ? 'Actif' : 'Inactif' }}
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

            <!-- ── Général ─────────────────────────────────────────── -->
            <div v-show="activeTab === 'general'" class="space-y-5 form-section">
                <div class="bg-white rounded-2xl shadow-card p-6 space-y-5">
                    <h2 class="font-poppins font-semibold text-onyx-800 flex items-center gap-2">
                        <PhScissors :size="18" class="text-cognac-500" />
                        Informations générales
                    </h2>

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
                                Nom <span class="text-red-400">*</span>
                            </label>
                            <input v-model="form.name" type="text"
                                   class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border transition-colors focus:outline-none"
                                   :class="form.errors.name ? 'border-red-300' : 'border-cream-200 focus:border-cognac-400'" />
                            <p v-if="form.errors.name" class="text-xs text-red-500 font-poppins mt-1">{{ form.errors.name }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">Description courte</label>
                        <textarea v-model="form.short_description" rows="2"
                                  class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none transition-colors resize-none" />
                        <p class="text-xs text-onyx-400 font-poppins mt-1">{{ (form.short_description || '').length }}/500</p>
                    </div>

                    <div>
                        <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">Description complète</label>
                        <textarea v-model="form.description" rows="5"
                                  class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none transition-colors resize-y" />
                    </div>
                </div>
            </div>

            <!-- ── Tarif & durée ───────────────────────────────────── -->
            <div v-show="activeTab === 'tarif'" class="space-y-5 form-section">
                <div class="bg-white rounded-2xl shadow-card p-6 space-y-5">
                    <h2 class="font-poppins font-semibold text-onyx-800 flex items-center gap-2">
                        <PhClock :size="18" class="text-cognac-500" />
                        Durée
                    </h2>
                    <div class="flex flex-wrap gap-2">
                        <button v-for="preset in durationPresets" :key="preset.value"
                                type="button" @click="form.duration = preset.value"
                                class="px-3 py-1.5 rounded-xl text-xs font-poppins font-semibold transition-all border"
                                :class="form.duration === preset.value
                                    ? 'text-white border-transparent'
                                    : 'border-cream-200 text-onyx-600 hover:border-cognac-300'"
                                :style="form.duration === preset.value ? 'background: linear-gradient(135deg, #c4956a, #b07d52);' : ''">
                            {{ preset.label }}
                        </button>
                    </div>
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
                        </div>
                        <div>
                            <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">
                                Temps tampon (min)
                            </label>
                            <input v-model.number="form.buffer_time" type="number" min="0" max="120" step="5"
                                   class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none transition-colors" />
                        </div>
                    </div>
                    <div class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-poppins"
                         style="background: rgba(196,149,106,0.06); border: 1px solid rgba(196,149,106,0.2);">
                        <PhInfo :size="16" class="text-cognac-500 shrink-0" />
                        <span class="text-cognac-700">
                            Durée totale créneaux :
                            <strong>{{ Math.floor((form.duration + form.buffer_time) / 60) > 0
                                ? Math.floor((form.duration + form.buffer_time) / 60) + 'h' : '' }}
                            {{ (form.duration + form.buffer_time) % 60 > 0
                                ? (form.duration + form.buffer_time) % 60 + 'min' : '' }}</strong>
                        </span>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-card p-6 space-y-5">
                    <h2 class="font-poppins font-semibold text-onyx-800 flex items-center gap-2">
                        <PhCurrencyEur :size="18" class="text-cognac-500" />
                        Tarification
                    </h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">
                                Prix <span class="text-red-400">*</span>
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
                            <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">Acompte</label>
                            <div class="relative">
                                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm font-poppins text-onyx-400">€</span>
                                <input v-model="form.deposit_amount" type="number" step="0.01" min="0"
                                       class="w-full pl-8 pr-4 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none transition-colors" />
                            </div>
                        </div>
                    </div>
                    <label class="flex items-center gap-3 p-4 rounded-xl border border-cream-200 cursor-pointer hover:border-cognac-300 hover:bg-cream-50 transition-all">
                        <input type="checkbox" v-model="form.deposit_required" class="accent-cognac-500 w-4 h-4" />
                        <div>
                            <p class="text-sm font-poppins font-semibold text-onyx-700">Acompte obligatoire</p>
                            <p class="text-xs font-poppins text-onyx-400">Requis lors de la réservation</p>
                        </div>
                    </label>
                </div>
            </div>

            <!-- ── Détails ─────────────────────────────────────────── -->
            <div v-show="activeTab === 'details'" class="space-y-5 form-section">
                <!-- Includes -->
                <div class="bg-white rounded-2xl shadow-card p-6 space-y-4">
                    <h2 class="font-poppins font-semibold text-onyx-800 flex items-center gap-2">
                        <PhCheckCircle :size="18" class="text-emerald-500" />
                        Ce qui est inclus
                    </h2>
                    <div class="space-y-2">
                        <div v-for="(item, i) in form.includes" :key="i"
                             class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-emerald-50 border border-emerald-100">
                            <PhCheckCircle :size="15" class="text-emerald-500 shrink-0" />
                            <span class="flex-1 text-sm font-poppins text-onyx-700">{{ item }}</span>
                            <button type="button" @click="removeInclude(i)" class="text-onyx-300 hover:text-red-400 transition-colors">
                                <PhX :size="15" />
                            </button>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <input v-model="includeInput" @keydown="onIncludeKey" type="text" placeholder="Ajouter..."
                               class="flex-1 px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none transition-colors" />
                        <button type="button" @click="addInclude"
                                class="px-4 py-2.5 rounded-xl text-sm font-poppins font-semibold text-white"
                                style="background: linear-gradient(135deg, #c4956a, #b07d52);">
                            <PhPlus :size="16" weight="bold" />
                        </button>
                    </div>
                </div>

                <!-- Requirements -->
                <div class="bg-white rounded-2xl shadow-card p-6 space-y-4">
                    <h2 class="font-poppins font-semibold text-onyx-800 flex items-center gap-2">
                        <PhWarning :size="18" class="text-amber-500" />
                        Prérequis clients
                    </h2>
                    <div class="space-y-2">
                        <div v-for="(item, i) in form.requirements" :key="i"
                             class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-amber-50 border border-amber-100">
                            <PhWarning :size="15" class="text-amber-500 shrink-0" />
                            <span class="flex-1 text-sm font-poppins text-onyx-700">{{ item }}</span>
                            <button type="button" @click="removeRequirement(i)" class="text-onyx-300 hover:text-red-400 transition-colors">
                                <PhX :size="15" />
                            </button>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <input v-model="requirementInput" @keydown="onRequirementKey" type="text" placeholder="Ajouter un prérequis..."
                               class="flex-1 px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none transition-colors" />
                        <button type="button" @click="addRequirement"
                                class="px-4 py-2.5 rounded-xl text-sm font-poppins font-semibold text-white"
                                style="background: linear-gradient(135deg, #c4956a, #b07d52);">
                            <PhPlus :size="16" weight="bold" />
                        </button>
                    </div>
                </div>

                <!-- Capacité -->
                <div class="bg-white rounded-2xl shadow-card p-6">
                    <h2 class="font-poppins font-semibold text-onyx-800 flex items-center gap-2 mb-4">
                        <PhUsers :size="18" class="text-cognac-500" />
                        Capacité
                    </h2>
                    <div class="w-48">
                        <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">
                            Clients max / créneau
                        </label>
                        <input v-model.number="form.max_clients_per_slot" type="number" min="1" max="10"
                               class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none transition-colors" />
                    </div>
                </div>
            </div>

            <!-- ── Image ───────────────────────────────────────────── -->
            <div v-show="activeTab === 'media'" class="space-y-5 form-section">
                <div class="bg-white rounded-2xl shadow-card p-6 space-y-5">
                    <h2 class="font-poppins font-semibold text-onyx-800 flex items-center gap-2">
                        <PhImage :size="18" class="text-cognac-500" />
                        Photo du service
                    </h2>

                    <div class="flex items-start gap-5">
                        <div class="relative w-64 aspect-video rounded-2xl overflow-hidden group shrink-0">
                            <img :src="imagePreview" class="w-full h-full object-cover"
                                 :class="imageChanged ? 'ring-2 ring-cognac-400 ring-offset-2' : ''" />
                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                <label class="w-10 h-10 bg-white rounded-xl flex items-center justify-center cursor-pointer hover:bg-cream-50 shadow">
                                    <PhImage :size="17" class="text-onyx-700" />
                                    <input type="file" accept="image/*" class="hidden" @change="onImageChange" />
                                </label>
                            </div>
                        </div>
                        <div class="text-sm font-poppins text-onyx-500 space-y-1.5">
                            <p class="font-semibold text-onyx-700">Changer la photo</p>
                            <p class="text-xs text-onyx-400">Cliquez sur l'image pour la remplacer</p>
                            <p class="text-xs text-onyx-400">JPG, PNG, WebP — max 5 Mo</p>
                            <p class="text-xs text-onyx-400">Format recommandé : 16/9</p>
                            <span v-if="imageChanged"
                                  class="inline-flex items-center gap-1 text-xs text-cognac-600 font-semibold">
                                <PhCheckCircle :size="13" /> Nouvelle image sélectionnée
                            </span>
                        </div>
                    </div>
                    <p v-if="form.errors.image" class="text-xs text-red-500 font-poppins">{{ form.errors.image }}</p>
                </div>
            </div>

            <!-- ── Options ─────────────────────────────────────────── -->
            <div v-show="activeTab === 'options'" class="space-y-5 form-section">
                <div class="bg-white rounded-2xl shadow-card p-6 space-y-4">
                    <h2 class="font-poppins font-semibold text-onyx-800">Options de publication</h2>

                    <div class="space-y-3">
                        <label v-for="(opt, key) in {
                            is_active:             { title: 'Service actif',         desc: 'Visible et réservable en ligne' },
                            is_featured:           { title: 'Service en vedette',    desc: 'Mis en avant sur la vitrine' },
                            requires_consultation: { title: 'Consultation requise',  desc: 'Échange préalable avant confirmation' },
                        }" :key="key"
                               class="flex items-center justify-between p-4 rounded-xl border border-cream-200 cursor-pointer hover:border-cognac-300 hover:bg-cream-50 transition-all">
                            <div>
                                <p class="text-sm font-poppins font-semibold text-onyx-700">{{ opt.title }}</p>
                                <p class="text-xs font-poppins text-onyx-400">{{ opt.desc }}</p>
                            </div>
                            <div class="relative w-12 h-6 rounded-full transition-colors cursor-pointer"
                                 :style="{ background: form[key] ? '#c4956a' : '#d1d5db' }"
                                 @click="form[key] = !form[key]">
                                <div class="absolute top-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform"
                                     :class="form[key] ? 'translate-x-6' : 'translate-x-0.5'" />
                            </div>
                        </label>
                    </div>

                    <div class="w-32">
                        <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">Ordre d'affichage</label>
                        <input v-model.number="form.sort_order" type="number" min="0"
                               class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none transition-colors" />
                    </div>

                    <!-- Zone danger -->
                    <div class="pt-4 border-t border-cream-100">
                        <p class="text-xs font-poppins font-semibold text-onyx-500 uppercase tracking-wide mb-3">Zone de danger</p>
                        <Link :href="route('admin.services.destroy', service.id)"
                              method="delete" as="button"
                              class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-poppins font-semibold text-red-600 border border-red-200 hover:bg-red-50 transition-colors"
                              onclick="return confirm('Supprimer définitivement ce service ?')">
                            <PhTrash :size="16" />
                            Supprimer ce service
                        </Link>
                    </div>
                </div>
            </div>

            <!-- ── Boutons ─────────────────────────────────────────── -->
            <div class="flex items-center justify-between bg-white rounded-2xl shadow-card px-6 py-4">
                <Link :href="route('admin.services.show', service.id)"
                      class="px-4 py-2.5 rounded-xl text-sm font-poppins font-medium text-onyx-600 border border-cream-200 hover:bg-cream-50 transition-colors">
                    Annuler
                </Link>
                <div class="flex items-center gap-3">
                    <p v-if="form.isDirty"
                       class="text-xs font-poppins text-amber-600 flex items-center gap-1.5">
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