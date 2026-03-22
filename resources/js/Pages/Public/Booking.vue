<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { gsap } from 'gsap'
import PublicLayout from '@/Layouts/PublicLayout.vue'
import {
    PhCalendarCheck, PhClock, PhArrowLeft, PhArrowRight,
    PhCheck, PhBridge, PhUser, PhEnvelope, PhPhone,
    PhNote, PhCaretLeft, PhCaretRight, PhLockKey,
    PhSparkle, PhWarning,
} from '@phosphor-icons/vue'

defineOptions({ layout: PublicLayout })

const props = defineProps({
    service:        Object,
    availableDates: Array,
    settings:       Object,
})

// ── Étapes ────────────────────────────────────────────────────────
const currentStep = ref(1)
const steps = [
    { id: 1, label: 'Date & Heure' },
    { id: 2, label: 'Vos informations' },
    { id: 3, label: 'Confirmation' },
]

// ── Sélection date/heure ──────────────────────────────────────────
const selectedDate  = ref(null)
const selectedSlot  = ref(null)
const slots         = ref([])
const slotsLoading  = ref(false)
const slotsError    = ref(null)
const dateOffset    = ref(0)
const visibleCount  = 7

const visibleDates = computed(() =>
    props.availableDates.slice(dateOffset.value, dateOffset.value + visibleCount)
)

const canPrevDates = computed(() => dateOffset.value > 0)
const canNextDates = computed(() => dateOffset.value + visibleCount < props.availableDates.length)

function prevDates() {
    if (canPrevDates.value) dateOffset.value -= visibleCount
}
function nextDates() {
    if (canNextDates.value) dateOffset.value += visibleCount
}

async function selectDate(date) {
    selectedDate.value = date
    selectedSlot.value = null
    slots.value        = []
    slotsError.value   = null
    slotsLoading.value = true

    try {
        const res = await fetch(route('booking.slots', props.service.slug), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ date: date.date }),
        })
        const data = await res.json()
        slots.value = data.slots ?? []

        if (!slots.value.length) {
            slotsError.value = data.reason === 'blocked'
                ? 'Cette journée est indisponible.'
                : 'Aucun créneau disponible ce jour-là.'
        }
    } catch {
        slotsError.value = 'Erreur lors du chargement des créneaux.'
    } finally {
        slotsLoading.value = false
    }
}

// ── Formulaire ────────────────────────────────────────────────────
const form = useForm({
    first_name:   '',
    last_name:    '',
    email:        '',
    phone:        '',
    notes:        '',
    hair_details: { hair_type: '', hair_length: '', special_request: '' },
    date:         '',
    start_time:   '',
})

function goToStep2() {
    if (!selectedDate.value || !selectedSlot.value) return
    form.date       = selectedDate.value.date
    form.start_time = selectedSlot.value.start
    currentStep.value = 2
    window.scrollTo({ top: 0, behavior: 'smooth' })
    gsap.fromTo('.step-content', { opacity: 0, x: 30 }, { opacity: 1, x: 0, duration: 0.4, ease: 'power2.out' })
}

function goToStep3() {
    if (!form.first_name || !form.last_name || !form.email || !form.phone) return
    currentStep.value = 3
    window.scrollTo({ top: 0, behavior: 'smooth' })
    gsap.fromTo('.step-content', { opacity: 0, x: 30 }, { opacity: 1, x: 0, duration: 0.4, ease: 'power2.out' })
}

function goBack() {
    currentStep.value--
    gsap.fromTo('.step-content', { opacity: 0, x: -30 }, { opacity: 1, x: 0, duration: 0.4, ease: 'power2.out' })
}

function submit() {
    form.post(route('booking.store', props.service.slug))
}

onMounted(() => {
    gsap.fromTo('.booking-hero', { opacity: 0, y: 30 }, { opacity: 1, y: 0, duration: 0.8, ease: 'power3.out' })
})

const formatPrice = (p) => new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(p)
const hairTypes   = ['1a','1b','1c','2a','2b','2c','3a','3b','3c','4a','4b','4c']
const hairLengths = ['Court (< 10cm)', 'Mi-court (10-20cm)', 'Moyen (20-35cm)', 'Long (35-50cm)', 'Très long (> 50cm)']
</script>

<template>
    <Head :title="`Réserver — ${service.name}`" />

    <!-- Hero compact -->
    <section class="relative py-16" style="background: #0d0d1a;">
        <div class="absolute inset-0 opacity-10"
             style="background: radial-gradient(circle at 30% 50%, #c4956a, transparent 50%);" />

        <div class="container-brand booking-hero relative z-10">
            <Link :href="route('booking.services')"
                  class="inline-flex items-center gap-2 text-sm text-white/50 hover:text-white transition-colors font-poppins mb-6">
                <PhArrowLeft :size="16" />
                Retour aux services
            </Link>

            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
                <div>
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full mb-3 text-xs font-poppins"
                         style="background: rgba(196,149,106,0.15); color: #c4956a;">
                        <PhBridge :size="12" />
                        {{ service.category_label }}
                    </div>
                    <h1 class="font-cormorant text-4xl md:text-5xl font-bold text-white">{{ service.name }}</h1>
                    <p class="mt-2 text-white/60 font-poppins">{{ service.short_description }}</p>
                </div>

                <div class="flex items-center gap-6 text-right shrink-0">
                    <div>
                        <p class="text-3xl font-bold font-cormorant text-white">{{ formatPrice(service.price) }}</p>
                        <p class="text-sm text-white/40 font-poppins flex items-center gap-1 justify-end">
                            <PhClock :size="13" />
                            {{ service.duration_formatted }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Corps booking -->
    <section class="py-12" style="background: #faf7f4;">
        <div class="container-brand">
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">

                <!-- ── Formulaire principal ─────────────────── -->
                <div class="xl:col-span-2">

                    <!-- Stepper -->
                    <div class="flex items-center gap-0 mb-8">
                        <div v-for="(step, i) in steps" :key="step.id"
                             class="flex items-center flex-1">
                            <div class="flex items-center gap-2.5 shrink-0">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold font-poppins transition-all duration-300"
                                     :class="step.id < currentStep
                                         ? 'text-white'
                                         : step.id === currentStep
                                             ? 'text-white ring-4 ring-offset-2 ring-cognac-200'
                                             : 'text-onyx-300 bg-cream-200'"
                                     :style="step.id <= currentStep ? 'background: linear-gradient(135deg, #c4956a, #b07d52);' : ''">
                                    <PhCheck v-if="step.id < currentStep" :size="14" weight="bold" />
                                    <span v-else>{{ step.id }}</span>
                                </div>
                                <span class="hidden md:block text-xs font-poppins font-semibold"
                                      :class="step.id === currentStep ? 'text-cognac-700' : 'text-onyx-400'">
                                    {{ step.label }}
                                </span>
                            </div>
                            <div v-if="i < steps.length - 1"
                                 class="flex-1 h-0.5 mx-3 transition-all duration-300"
                                 :style="step.id < currentStep ? 'background: #c4956a;' : 'background: #e5e7eb;'" />
                        </div>
                    </div>

                    <!-- ══ ÉTAPE 1 : Date & Heure ══ -->
                    <div v-if="currentStep === 1" class="step-content space-y-6">

                        <!-- Sélecteur de dates -->
                        <div class="bg-white rounded-3xl p-6 shadow-card">
                            <h3 class="font-poppins font-semibold text-onyx-800 mb-4">
                                Choisissez une date
                            </h3>

                            <div class="flex items-center gap-2">
                                <button @click="prevDates" :disabled="!canPrevDates"
                                        class="w-9 h-9 rounded-xl flex items-center justify-center transition-all disabled:opacity-30 hover:bg-cream-100">
                                    <PhCaretLeft :size="16" class="text-onyx-600" />
                                </button>

                                <div class="flex-1 grid grid-cols-7 gap-1.5">
                                    <button v-for="date in visibleDates" :key="date.date"
                                            @click="selectDate(date)"
                                            class="flex flex-col items-center gap-1 p-2 rounded-xl text-center transition-all duration-200 border-2"
                                            :class="selectedDate?.date === date.date
                                                ? 'text-white border-cognac-400 shadow-float'
                                                : 'border-transparent hover:border-cream-300 hover:bg-cream-50'"
                                            :style="selectedDate?.date === date.date
                                                ? 'background: linear-gradient(135deg, #c4956a, #b07d52);'
                                                : ''">
                                        <span class="text-xs uppercase tracking-wide font-poppins"
                                              :class="selectedDate?.date === date.date ? 'text-white/80' : 'text-onyx-400'">
                                            {{ date.day }}
                                        </span>
                                        <span class="text-lg font-bold font-cormorant leading-none"
                                              :class="selectedDate?.date === date.date ? 'text-white' : 'text-onyx-800'">
                                            {{ date.number }}
                                        </span>
                                        <span class="text-xs font-poppins"
                                              :class="selectedDate?.date === date.date ? 'text-white/70' : 'text-onyx-400'">
                                            {{ date.month }}
                                        </span>
                                        <div v-if="date.is_today"
                                             class="w-1 h-1 rounded-full mt-0.5"
                                             :class="selectedDate?.date === date.date ? 'bg-white/50' : 'bg-cognac-400'" />
                                    </button>
                                </div>

                                <button @click="nextDates" :disabled="!canNextDates"
                                        class="w-9 h-9 rounded-xl flex items-center justify-center transition-all disabled:opacity-30 hover:bg-cream-100">
                                    <PhCaretRight :size="16" class="text-onyx-600" />
                                </button>
                            </div>
                        </div>

                        <!-- Sélecteur de créneaux -->
                        <div class="bg-white rounded-3xl p-6 shadow-card">
                            <h3 class="font-poppins font-semibold text-onyx-800 mb-4">
                                {{ selectedDate ? `Créneaux du ${selectedDate.label}` : 'Sélectionnez d\'abord une date' }}
                            </h3>

                            <!-- Loading -->
                            <div v-if="slotsLoading" class="py-10 flex items-center justify-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="w-8 h-8 rounded-full border-2 border-cognac-400 border-t-transparent animate-spin" />
                                    <p class="text-sm text-onyx-400 font-poppins">Chargement des créneaux...</p>
                                </div>
                            </div>

                            <!-- Erreur -->
                            <div v-else-if="slotsError" class="py-8 text-center">
                                <PhWarning :size="32" class="text-amber-400 mx-auto mb-2" />
                                <p class="text-sm text-onyx-500 font-poppins">{{ slotsError }}</p>
                            </div>

                            <!-- Pas de date sélectionnée -->
                            <div v-else-if="!selectedDate" class="py-10 text-center text-onyx-300">
                                <PhCalendarCheck :size="40" class="mx-auto mb-3 opacity-40" />
                                <p class="text-sm font-poppins">Choisissez une date pour voir les créneaux</p>
                            </div>

                            <!-- Créneaux -->
                            <div v-else-if="slots.length" class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-2.5">
                                <button v-for="slot in slots" :key="slot.start"
                                        @click="selectedSlot = slot"
                                        class="py-3 px-2 rounded-xl text-sm font-semibold font-poppins text-center transition-all duration-200 border-2"
                                        :class="selectedSlot?.start === slot.start
                                            ? 'text-white border-cognac-400 shadow-float'
                                            : 'border-cream-200 text-onyx-600 hover:border-cognac-300 hover:bg-cream-50'"
                                        :style="selectedSlot?.start === slot.start
                                            ? 'background: linear-gradient(135deg, #c4956a, #b07d52);'
                                            : ''">
                                    {{ slot.label }}
                                </button>
                            </div>
                        </div>

                        <!-- Bouton suivant -->
                        <div class="flex justify-end">
                            <button @click="goToStep2"
                                    :disabled="!selectedDate || !selectedSlot"
                                    class="btn-primary py-4 px-8 disabled:opacity-40 disabled:cursor-not-allowed">
                                Continuer
                                <PhArrowRight :size="18" weight="bold" />
                            </button>
                        </div>
                    </div>

                    <!-- ══ ÉTAPE 2 : Informations ══ -->
                    <div v-if="currentStep === 2" class="step-content space-y-5">
                        <div class="bg-white rounded-3xl p-7 shadow-card">
                            <h3 class="font-poppins font-semibold text-onyx-800 mb-6">Vos coordonnées</h3>

                            <div class="space-y-5">
                                <!-- Prénom / Nom -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-onyx-600 uppercase tracking-widest mb-2 font-poppins">Prénom</label>
                                        <div class="relative">
                                            <PhUser :size="16" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-onyx-400 pointer-events-none" />
                                            <input v-model="form.first_name" type="text" placeholder="Amina"
                                                   class="input-brand pl-10 text-sm" />
                                        </div>
                                        <p v-if="form.errors.first_name" class="mt-1 text-xs text-red-500">{{ form.errors.first_name }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-onyx-600 uppercase tracking-widest mb-2 font-poppins">Nom</label>
                                        <input v-model="form.last_name" type="text" placeholder="Diallo" class="input-brand text-sm" />
                                        <p v-if="form.errors.last_name" class="mt-1 text-xs text-red-500">{{ form.errors.last_name }}</p>
                                    </div>
                                </div>

                                <!-- Email -->
                                <div>
                                    <label class="block text-xs font-semibold text-onyx-600 uppercase tracking-widest mb-2 font-poppins">Email</label>
                                    <div class="relative">
                                        <PhEnvelope :size="16" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-onyx-400 pointer-events-none" />
                                        <input v-model="form.email" type="email" placeholder="amina@email.com"
                                               class="input-brand pl-10 text-sm" />
                                    </div>
                                    <p v-if="form.errors.email" class="mt-1 text-xs text-red-500">{{ form.errors.email }}</p>
                                </div>

                                <!-- Téléphone -->
                                <div>
                                    <label class="block text-xs font-semibold text-onyx-600 uppercase tracking-widest mb-2 font-poppins">Téléphone</label>
                                    <div class="relative">
                                        <PhPhone :size="16" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-onyx-400 pointer-events-none" />
                                        <input v-model="form.phone" type="tel" placeholder="+33 6 12 34 56 78"
                                               class="input-brand pl-10 text-sm" />
                                    </div>
                                    <p v-if="form.errors.phone" class="mt-1 text-xs text-red-500">{{ form.errors.phone }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Détails capillaires -->
                        <div class="bg-white rounded-3xl p-7 shadow-card">
                            <h3 class="font-poppins font-semibold text-onyx-800 mb-2">Détails capillaires</h3>
                            <p class="text-xs text-onyx-400 font-poppins mb-5">Ces informations permettent à Patricia de préparer au mieux votre séance.</p>

                            <div class="space-y-4">
                                <!-- Type de cheveux -->
                                <div>
                                    <label class="block text-xs font-semibold text-onyx-600 uppercase tracking-widest mb-2 font-poppins">Type de cheveux</label>
                                    <div class="grid grid-cols-6 gap-2">
                                        <button v-for="type in hairTypes" :key="type"
                                                type="button"
                                                @click="form.hair_details.hair_type = form.hair_details.hair_type === type ? '' : type"
                                                class="py-2 rounded-xl text-xs font-semibold font-poppins text-center transition-all border-2"
                                                :class="form.hair_details.hair_type === type
                                                    ? 'text-white border-cognac-400'
                                                    : 'border-cream-200 text-onyx-500 hover:border-cognac-200'"
                                                :style="form.hair_details.hair_type === type ? 'background: linear-gradient(135deg, #c4956a, #b07d52);' : ''">
                                            {{ type }}
                                        </button>
                                    </div>
                                </div>

                                <!-- Longueur -->
                                <div>
                                    <label class="block text-xs font-semibold text-onyx-600 uppercase tracking-widest mb-2 font-poppins">Longueur</label>
                                    <select v-model="form.hair_details.hair_length"
                                            class="input-brand text-sm appearance-none">
                                        <option value="">Sélectionnez une longueur</option>
                                        <option v-for="l in hairLengths" :key="l" :value="l">{{ l }}</option>
                                    </select>
                                </div>

                                <!-- Notes -->
                                <div>
                                    <label class="block text-xs font-semibold text-onyx-600 uppercase tracking-widest mb-2 font-poppins">
                                        Notes & demandes spéciales
                                        <span class="normal-case font-normal text-onyx-400 ml-1">(optionnel)</span>
                                    </label>
                                    <div class="relative">
                                        <PhNote :size="16" class="absolute left-3.5 top-3.5 text-onyx-400 pointer-events-none" />
                                        <textarea v-model="form.notes"
                                                  rows="3"
                                                  placeholder="Couleur souhaitée, inspirations, contraintes particulières..."
                                                  class="input-brand pl-10 pt-3 text-sm resize-none" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Navigation -->
                        <div class="flex items-center justify-between">
                            <button @click="goBack" class="btn-ghost flex items-center gap-2 text-sm">
                                <PhArrowLeft :size="16" />
                                Retour
                            </button>
                            <button @click="goToStep3"
                                    :disabled="!form.first_name || !form.last_name || !form.email || !form.phone"
                                    class="btn-primary py-4 px-8 disabled:opacity-40 disabled:cursor-not-allowed">
                                Confirmer les informations
                                <PhArrowRight :size="18" weight="bold" />
                            </button>
                        </div>
                    </div>

                    <!-- ══ ÉTAPE 3 : Confirmation ══ -->
                    <div v-if="currentStep === 3" class="step-content space-y-5">

                        <div class="bg-white rounded-3xl p-7 shadow-card">
                            <div class="flex items-center gap-3 mb-6">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center"
                                     style="background: rgba(16,185,129,0.1);">
                                    <PhCheck :size="20" weight="bold" class="text-emerald-500" />
                                </div>
                                <div>
                                    <h3 class="font-poppins font-semibold text-onyx-800">Récapitulatif de votre réservation</h3>
                                    <p class="text-xs text-onyx-400 font-poppins">Vérifiez les informations avant de confirmer</p>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <!-- Service -->
                                <div class="p-4 rounded-2xl" style="background: #faf7f4; border: 1px solid #edddd0;">
                                    <p class="text-xs text-onyx-400 font-poppins uppercase tracking-widest mb-2">Prestation</p>
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="font-semibold text-onyx-800 font-poppins">{{ service.name }}</p>
                                            <p class="text-sm text-onyx-500 font-poppins flex items-center gap-1.5 mt-0.5">
                                                <PhClock :size="13" />
                                                {{ service.duration_formatted }}
                                            </p>
                                        </div>
                                        <p class="font-cormorant text-xl font-bold" style="color: #c4956a;">
                                            {{ formatPrice(service.price) }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Date & Heure -->
                                <div class="p-4 rounded-2xl" style="background: #faf7f4; border: 1px solid #edddd0;">
                                    <p class="text-xs text-onyx-400 font-poppins uppercase tracking-widest mb-2">Date & heure</p>
                                    <div class="flex items-center gap-3">
                                        <PhCalendarCheck :size="20" class="text-cognac-500 shrink-0" />
                                        <div>
                                            <p class="font-semibold text-onyx-800 font-poppins capitalize">
                                                {{ selectedDate?.label }}
                                            </p>
                                            <p class="text-sm text-onyx-500 font-poppins">{{ selectedSlot?.start }} — {{ selectedSlot?.end }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Client -->
                                <div class="p-4 rounded-2xl" style="background: #faf7f4; border: 1px solid #edddd0;">
                                    <p class="text-xs text-onyx-400 font-poppins uppercase tracking-widest mb-2">Vos coordonnées</p>
                                    <div class="space-y-1.5">
                                        <p class="font-semibold text-onyx-800 font-poppins">{{ form.first_name }} {{ form.last_name }}</p>
                                        <p class="text-sm text-onyx-500 font-poppins">{{ form.email }}</p>
                                        <p class="text-sm text-onyx-500 font-poppins">{{ form.phone }}</p>
                                    </div>
                                </div>

                                <!-- Acompte si requis -->
                                <div v-if="service.deposit_required"
                                     class="p-4 rounded-2xl flex items-start gap-3"
                                     style="background: rgba(196,149,106,0.08); border: 1px solid rgba(196,149,106,0.25);">
                                    <PhLockKey :size="18" class="text-cognac-500 shrink-0 mt-0.5" />
                                    <div>
                                        <p class="text-sm font-semibold text-cognac-700 font-poppins">Acompte requis</p>
                                        <p class="text-xs text-onyx-500 font-poppins mt-0.5 leading-relaxed">
                                            Un acompte de {{ formatPrice(service.deposit_amount) }} vous sera demandé pour confirmer définitivement votre réservation.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Navigation -->
                        <div class="flex items-center justify-between">
                            <button @click="goBack" class="btn-ghost flex items-center gap-2 text-sm">
                                <PhArrowLeft :size="16" />
                                Modifier
                            </button>
                            <button @click="submit"
                                    :disabled="form.processing"
                                    class="btn-primary py-4 px-8">
                                <span v-if="!form.processing" class="flex items-center gap-2 relative z-10">
                                    <PhSparkle :size="18" weight="bold" />
                                    Confirmer la réservation
                                </span>
                                <span v-else class="flex items-center gap-2">
                                    <svg class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                    </svg>
                                    Envoi...
                                </span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ── Récap sidebar ────────────────────────── -->
                <div class="hidden xl:block">
                    <div class="sticky top-[100px] space-y-4">

                        <!-- Card service -->
                        <div class="bg-white rounded-3xl overflow-hidden shadow-card">
                            <div class="p-5 flex items-center justify-center"
                                 style="background: linear-gradient(135deg, #1a1a2e, #2d2d4a); min-height: 120px;">
                                <PhBridge :size="48" class="text-cognac-400 opacity-40" />
                            </div>
                            <div class="p-5">
                                <p class="font-cormorant text-xl font-bold text-onyx-800 mb-1">{{ service.name }}</p>
                                <p class="text-xs text-onyx-400 font-poppins mb-4">{{ service.category_label }} · {{ service.duration_formatted }}</p>

                                <div class="space-y-2">
                                    <div class="flex items-center justify-between text-sm font-poppins">
                                        <span class="text-onyx-500">Prestation</span>
                                        <span class="font-semibold text-onyx-800">{{ formatPrice(service.price) }}</span>
                                    </div>
                                    <div v-if="service.deposit_required" class="flex items-center justify-between text-sm font-poppins text-cognac-600">
                                        <span>Acompte</span>
                                        <span class="font-semibold">{{ formatPrice(service.deposit_amount) }}</span>
                                    </div>
                                    <div class="h-px bg-cream-200 my-2" />
                                    <div class="flex items-center justify-between text-sm font-poppins">
                                        <span class="text-onyx-500">Total</span>
                                        <span class="font-bold text-onyx-800 text-base">{{ formatPrice(service.price) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Rassurance -->
                        <div class="bg-white rounded-2xl p-5 shadow-card space-y-3">
                            <div v-for="item in [
                                { icon: PhCheck, text: 'Confirmation par email immédiate' },
                                { icon: PhCheck, text: `Annulation possible ${settings.booking_cancellation_hours ?? 48}h avant` },
                                { icon: PhCheck, text: 'Paiement sécurisé' },
                            ]" :key="item.text"
                                 class="flex items-center gap-3 text-sm font-poppins text-onyx-600">
                                <div class="w-5 h-5 rounded-full flex items-center justify-center shrink-0"
                                     style="background: rgba(196,149,106,0.12);">
                                    <component :is="item.icon" :size="11" weight="bold" class="text-cognac-500" />
                                </div>
                                {{ item.text }}
                            </div>
                        </div>

                        <!-- Includes -->
                        <div v-if="service.includes?.length" class="bg-white rounded-2xl p-5 shadow-card">
                            <p class="text-xs font-semibold text-onyx-600 uppercase tracking-widest font-poppins mb-3">Inclus dans la séance</p>
                            <div class="space-y-2">
                                <div v-for="item in service.includes" :key="item"
                                     class="flex items-center gap-2 text-sm font-poppins text-onyx-600">
                                    <PhSparkle :size="14" class="text-gold-500 shrink-0" />
                                    {{ item }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>