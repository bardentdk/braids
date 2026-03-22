<script setup>
import { ref, computed, watch } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import PublicLayout from '@/Layouts/PublicLayout.vue'
import {
    PhArrowLeft, PhClock, PhCalendarBlank, PhCheckCircle,
    PhSpinnerGap, PhUser, PhPhone, PhEnvelope, PhStickerStar,
} from '@phosphor-icons/vue'

defineOptions({ layout: PublicLayout })

const props = defineProps({
    service: Object,
    auth:    Object,
})

// ── Étapes ────────────────────────────────────────────────────────
const step = ref(1) // 1 = date, 2 = créneau, 3 = infos client, 4 = confirmation

// ── Sélection date ─────────────────────────────────────────────────
const selectedDate  = ref('')
const availableSlots = ref([])
const loadingSlots   = ref(false)

// Calendrier simplifié : prochains 30 jours
const today = new Date()
const days = Array.from({ length: 30 }, (_, i) => {
    const d = new Date(today)
    d.setDate(today.getDate() + i + 1)
    return {
        key:   d.toISOString().slice(0, 10),
        label: d.toLocaleDateString('fr-FR', { weekday: 'short', day: 'numeric', month: 'short' }),
        dow:   d.getDay(),
    }
}).filter(d => d.dow !== 0) // exclure dimanche

async function selectDate(dateKey) {
    selectedDate.value  = dateKey
    availableSlots.value = []
    loadingSlots.value   = true
    step.value = 2

    try {
        const res = await window.axios.post(
            route('booking.slots', props.service.slug),
            { date: dateKey }
        )
        availableSlots.value = res.data.slots ?? []
    } catch {
        availableSlots.value = []
    } finally {
        loadingSlots.value = false
    }
}

// ── Sélection créneau ──────────────────────────────────────────────
const selectedSlot = ref(null)

function selectSlot(slot) {
    selectedSlot.value = slot
    step.value = 3
}

// ── Formulaire client ──────────────────────────────────────────────
const form = useForm({
    service_id:  props.service.id,
    date:        computed(() => selectedDate.value),
    time_start:  computed(() => selectedSlot.value?.start),
    first_name:  props.auth?.user?.name?.split(' ')[0] ?? '',
    last_name:   props.auth?.user?.name?.split(' ').slice(1).join(' ') ?? '',
    email:       props.auth?.user?.email ?? '',
    phone:       props.auth?.client?.phone ?? '',
    notes:       '',
})

function submitBooking() {
    router.post(route('booking.store', props.service.slug), {
        service_id: props.service.id,
        date:       selectedDate.value,
        time_start: selectedSlot.value?.start,
        first_name: form.first_name,
        last_name:  form.last_name,
        email:      form.email,
        phone:      form.phone,
        notes:      form.notes,
    }, {
        onSuccess: () => { step.value = 4 },
        preserveScroll: true,
    })
}

function formatPrice(val) {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(val)
}

const selectedDateLabel = computed(() => {
    if (!selectedDate.value) return ''
    return days.find(d => d.key === selectedDate.value)?.label ?? selectedDate.value
})
</script>

<template>
    <Head :title="`Réserver — ${service.name}`" />

    <div style="background: #faf7f4; min-height: 100vh;">

        <!-- Header service -->
        <div class="relative overflow-hidden py-12" style="background: #0d0d1a;">
            <div class="absolute inset-0 opacity-20"
                 :style="service.cover_image_url
                    ? `background-image: url(${service.cover_image_url}); background-size: cover; background-position: center;`
                    : 'background: radial-gradient(ellipse at 30% 50%, #c4956a 0%, transparent 70%);'" />
            <div class="absolute inset-0" style="background: linear-gradient(to bottom, rgba(13,13,26,0.5), rgba(13,13,26,0.9));" />

            <div class="relative max-w-3xl mx-auto px-4">
                <Link :href="route('booking.services')"
                      class="inline-flex items-center gap-2 text-white/40 hover:text-white/80 text-sm font-poppins mb-5 transition-colors">
                    <PhArrowLeft :size="15" /> Retour aux services
                </Link>
                <h1 class="font-cormorant text-4xl font-bold text-white mb-2">{{ service.name }}</h1>
                <div class="flex items-center gap-4 text-sm font-poppins text-white/50">
                    <span class="flex items-center gap-1.5"><PhClock :size="14" />{{ service.duration }} min</span>
                    <span class="font-bold text-white/80 text-lg">{{ formatPrice(service.price) }}</span>
                </div>
            </div>
        </div>

        <div class="max-w-3xl mx-auto px-4 py-8">

            <!-- Stepper -->
            <div class="flex items-center gap-2 mb-8">
                <div v-for="(s, i) in [
                    { n: 1, label: 'Date' },
                    { n: 2, label: 'Créneau' },
                    { n: 3, label: 'Vos infos' },
                    { n: 4, label: 'Confirmé' },
                ]" :key="s.n" class="flex items-center gap-2">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-poppins font-bold transition-all"
                             :class="step >= s.n ? 'text-white' : 'text-onyx-400 bg-cream-200'"
                             :style="step >= s.n ? 'background: linear-gradient(135deg, #c4956a, #b07d52);' : ''">
                            <PhCheckCircle v-if="step > s.n" :size="16" weight="fill" />
                            <span v-else>{{ s.n }}</span>
                        </div>
                        <span class="text-xs font-poppins hidden sm:block"
                              :class="step >= s.n ? 'text-onyx-700 font-semibold' : 'text-onyx-400'">
                            {{ s.label }}
                        </span>
                    </div>
                    <div v-if="i < 3" class="w-8 h-px flex-1" style="background: #e5d5c5;" />
                </div>
            </div>

            <!-- ── ÉTAPE 1 : Choisir une date ────────────────────── -->
            <div v-if="step === 1">
                <h2 class="font-cormorant text-2xl font-bold text-onyx-800 mb-5">Choisissez une date</h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                    <button v-for="day in days" :key="day.key"
                            @click="selectDate(day.key)"
                            class="p-3.5 rounded-2xl text-sm font-poppins font-medium text-center transition-all border hover:-translate-y-0.5"
                            :class="selectedDate === day.key
                                ? 'text-white border-transparent shadow-md'
                                : 'bg-white border-cream-200 text-onyx-700 hover:border-cognac-300 shadow-card'"
                            :style="selectedDate === day.key ? 'background: linear-gradient(135deg, #c4956a, #b07d52);' : ''">
                        {{ day.label }}
                    </button>
                </div>
            </div>

            <!-- ── ÉTAPE 2 : Choisir un créneau ─────────────────── -->
            <div v-if="step === 2">
                <div class="flex items-center gap-3 mb-5">
                    <button @click="step = 1; selectedSlot = null"
                            class="w-8 h-8 rounded-xl flex items-center justify-center border border-cream-200 bg-white text-onyx-500 hover:bg-cream-100">
                        <PhArrowLeft :size="15" />
                    </button>
                    <h2 class="font-cormorant text-2xl font-bold text-onyx-800">
                        Créneaux du {{ selectedDateLabel }}
                    </h2>
                </div>

                <div v-if="loadingSlots" class="flex items-center justify-center py-16">
                    <PhSpinnerGap :size="32" class="text-cognac-400 animate-spin" />
                </div>

                <div v-else-if="availableSlots.length" class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3">
                    <button v-for="slot in availableSlots" :key="slot.start"
                            @click="selectSlot(slot)"
                            :disabled="!slot.available"
                            class="p-3 rounded-xl text-sm font-poppins font-semibold text-center transition-all disabled:opacity-30 disabled:cursor-not-allowed"
                            :class="slot.available
                                ? 'bg-white border border-cream-200 text-onyx-700 hover:border-cognac-400 hover:bg-cognac-50 shadow-card hover:-translate-y-0.5'
                                : 'bg-cream-50 border border-cream-100 text-onyx-300 line-through'">
                        {{ slot.start }}
                    </button>
                </div>

                <div v-else class="bg-white rounded-2xl shadow-card p-10 text-center">
                    <PhCalendarBlank :size="40" class="text-onyx-200 mx-auto mb-3" />
                    <p class="font-poppins font-semibold text-onyx-600 mb-1">Aucun créneau disponible</p>
                    <p class="text-sm font-poppins text-onyx-400 mb-5">Ce jour est complet ou non disponible.</p>
                    <button @click="step = 1" class="text-sm font-poppins text-cognac-600 hover:text-cognac-800 font-medium transition-colors">
                        ← Choisir une autre date
                    </button>
                </div>
            </div>

            <!-- ── ÉTAPE 3 : Informations client ─────────────────── -->
            <div v-if="step === 3">
                <div class="flex items-center gap-3 mb-5">
                    <button @click="step = 2"
                            class="w-8 h-8 rounded-xl flex items-center justify-center border border-cream-200 bg-white text-onyx-500 hover:bg-cream-100">
                        <PhArrowLeft :size="15" />
                    </button>
                    <h2 class="font-cormorant text-2xl font-bold text-onyx-800">Vos informations</h2>
                </div>

                <!-- Résumé RDV -->
                <div class="bg-white rounded-2xl shadow-card p-4 mb-5 flex items-center gap-4">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                         style="background: rgba(196,149,106,0.1);">
                        <PhCalendarBlank :size="18" class="text-cognac-500" />
                    </div>
                    <div>
                        <p class="text-sm font-poppins font-bold text-onyx-800">{{ service.name }}</p>
                        <p class="text-xs font-poppins text-onyx-500">
                            {{ selectedDateLabel }} à {{ selectedSlot?.start }} · {{ service.duration }} min · {{ formatPrice(service.price) }}
                        </p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-card p-5 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">Prénom *</label>
                            <div class="relative">
                                <PhUser :size="14" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-onyx-400" />
                                <input v-model="form.first_name" type="text" placeholder="Marie"
                                       class="w-full pl-9 pr-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none transition-colors" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">Nom *</label>
                            <input v-model="form.last_name" type="text" placeholder="Dupont"
                                   class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none transition-colors" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">Email *</label>
                        <div class="relative">
                            <PhEnvelope :size="14" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-onyx-400" />
                            <input v-model="form.email" type="email" placeholder="marie@email.com"
                                   class="w-full pl-9 pr-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none transition-colors" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">Téléphone *</label>
                        <div class="relative">
                            <PhPhone :size="14" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-onyx-400" />
                            <input v-model="form.phone" type="tel" placeholder="+33 6 00 00 00 00"
                                   class="w-full pl-9 pr-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none transition-colors" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-poppins font-semibold text-onyx-600 mb-1.5 uppercase tracking-wide">Notes / demandes spéciales</label>
                        <textarea v-model="form.notes" rows="3"
                                  placeholder="Précisez si vous avez des attentes particulières..."
                                  class="w-full px-3.5 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none resize-none transition-colors" />
                    </div>

                    <!-- Erreurs -->
                    <div v-if="Object.keys(form.errors).length" class="px-3 py-2.5 rounded-xl text-xs font-poppins text-red-600"
                         style="background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.2);">
                        <p v-for="(e, k) in form.errors" :key="k">{{ e }}</p>
                    </div>

                    <button @click="submitBooking" :disabled="form.processing || !form.first_name || !form.email || !form.phone"
                            class="w-full flex items-center justify-center gap-2 py-3.5 rounded-2xl text-sm font-poppins font-bold text-white disabled:opacity-60 transition-all hover:opacity-90 hover:-translate-y-0.5 shadow-md"
                            style="background: linear-gradient(135deg, #c4956a, #b07d52);">
                        <PhSpinnerGap v-if="form.processing" :size="18" class="animate-spin" />
                        <PhCheckCircle v-else :size="18" />
                        {{ form.processing ? 'Réservation en cours…' : 'Confirmer le rendez-vous' }}
                    </button>
                    <p class="text-xs font-poppins text-onyx-400 text-center">
                        Vous recevrez une confirmation par email.
                    </p>
                </div>
            </div>

            <!-- ── ÉTAPE 4 : Confirmation ─────────────────────────── -->
            <div v-if="step === 4" class="flex flex-col items-center justify-center py-8 text-center">
                <div class="w-20 h-20 rounded-3xl flex items-center justify-center mb-6 shadow-md"
                     style="background: linear-gradient(135deg, #c4956a, #b07d52);">
                    <PhCheckCircle :size="40" weight="fill" class="text-white" />
                </div>
                <h2 class="font-cormorant text-3xl font-bold text-onyx-800 mb-2">Rendez-vous confirmé !</h2>
                <p class="text-sm font-poppins text-onyx-500 max-w-sm mb-2">
                    Votre réservation pour <strong>{{ service.name }}</strong> le <strong>{{ selectedDateLabel }}</strong> à <strong>{{ selectedSlot?.start }}</strong> est enregistrée.
                </p>
                <p class="text-xs font-poppins text-onyx-400 mb-8">
                    Un email de confirmation vous a été envoyé à {{ form.email }}.
                </p>
                <div class="flex items-center gap-3">
                    <Link :href="route('booking.services')"
                          class="px-5 py-2.5 rounded-2xl text-sm font-poppins font-medium text-onyx-600 border border-cream-200 bg-white hover:bg-cream-50 transition-colors">
                        Voir d'autres services
                    </Link>
                    <Link v-if="auth?.user" :href="route('account.appointments')"
                          class="flex items-center gap-2 px-5 py-2.5 rounded-2xl text-sm font-poppins font-semibold text-white transition-all hover:opacity-90"
                          style="background: linear-gradient(135deg, #c4956a, #b07d52);">
                        <PhStickerStar :size="16" /> Mes rendez-vous
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>