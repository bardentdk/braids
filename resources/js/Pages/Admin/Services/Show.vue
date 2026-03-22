<script setup>
import { ref, onMounted } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { gsap } from 'gsap'
import { markRaw } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import {
    PhArrowLeft, PhPencilSimple, PhTrash, PhToggleLeft, PhToggleRight,
    PhClock, PhCurrencyEur, PhStar, PhCheckCircle, PhWarning, PhUsers,
    PhCalendarCheck, PhArrowSquareOut, PhInfo, PhPercent,
} from '@phosphor-icons/vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
    service:        Object,
    stats:          Object,
    recent_reviews: Array,
})

const breadcrumbs = [
    { label: 'Services', route: 'admin.services.index' },
    { label: props.service.name },
]

// ── Actions ────────────────────────────────────────────────────────
const toggling = ref(false)

function toggleService() {
    toggling.value = true
    router.patch(route('admin.services.toggle', props.service.id), {}, {
        preserveState: false,
        onFinish: () => { toggling.value = false },
    })
}

function deleteService() {
    if (!confirm(`Supprimer définitivement "${props.service.name}" ?`)) return
    router.delete(route('admin.services.destroy', props.service.id))
}

// ── Format ─────────────────────────────────────────────────────────
function formatPrice(val) {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(val ?? 0)
}

// ── Couleur catégorie ──────────────────────────────────────────────
const categoryColors = {
    braids:     { bg: 'rgba(196,149,106,0.12)', color: '#b07d52' },
    twists:     { bg: 'rgba(99,102,241,0.10)',  color: '#6366f1' },
    locs:       { bg: 'rgba(16,185,129,0.10)',  color: '#059669' },
    natural:    { bg: 'rgba(245,158,11,0.10)',  color: '#d97706' },
    extensions: { bg: 'rgba(236,72,153,0.10)',  color: '#db2777' },
    kids:       { bg: 'rgba(59,130,246,0.10)',  color: '#2563eb' },
    other:      { bg: 'rgba(107,114,128,0.10)', color: '#4b5563' },
}
function catStyle(cat) { return categoryColors[cat] ?? categoryColors.other }

onMounted(() => {
    gsap.fromTo('.service-card',
        { opacity: 0, y: 18 },
        { opacity: 1, y: 0, duration: 0.4, stagger: 0.07, ease: 'power2.out' }
    )
})
</script>

<template>
    <Head :title="service.name" />

    <div class="p-6 space-y-6">

        <!-- ── Header ─────────────────────────────────────────────── -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <Link :href="route('admin.services.index')"
                      class="w-9 h-9 rounded-xl flex items-center justify-center border border-cream-200 bg-white shadow-card text-onyx-500 hover:text-onyx-800 hover:bg-cream-100 transition-colors">
                    <PhArrowLeft :size="18" />
                </Link>
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="font-cormorant text-3xl font-bold text-onyx-800">{{ service.name }}</h1>
                        <span class="px-2.5 py-1 rounded-lg text-xs font-poppins font-semibold"
                              :style="{ background: catStyle(service.category).bg, color: catStyle(service.category).color }">
                            {{ service.category_label }}
                        </span>
                    </div>
                    <div class="flex items-center gap-3 mt-0.5">
                        <span class="flex items-center gap-1.5 text-sm font-poppins text-onyx-500">
                            <PhClock :size="14" class="text-cognac-400" />
                            {{ service.duration_formatted }}
                        </span>
                        <span class="text-onyx-300">·</span>
                        <span class="text-sm font-bold font-poppins text-onyx-800">{{ formatPrice(service.price) }}</span>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-2">
                <button @click="toggleService" :disabled="toggling"
                        class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-sm font-poppins font-medium border transition-all disabled:opacity-60"
                        :class="service.is_active
                            ? 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100'
                            : 'border-gray-200 bg-gray-50 text-gray-600 hover:bg-gray-100'">
                    <component :is="service.is_active ? markRaw(PhToggleRight) : markRaw(PhToggleLeft)" :size="18" />
                    {{ service.is_active ? 'Actif' : 'Inactif' }}
                </button>

                <Link :href="route('admin.services.edit', service.id)"
                      class="flex items-center gap-2 px-3.5 py-2 rounded-xl text-sm font-poppins font-medium border border-cream-200 bg-white text-onyx-700 hover:bg-cream-50 transition-colors shadow-card">
                    <PhPencilSimple :size="16" />
                    Modifier
                </Link>

                <button @click="deleteService"
                        class="w-9 h-9 rounded-xl flex items-center justify-center border border-red-200 bg-white text-red-500 hover:bg-red-50 transition-colors">
                    <PhTrash :size="16" />
                </button>
            </div>
        </div>

        <!-- ── Corps ──────────────────────────────────────────────── -->
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

            <!-- Gauche : image + description -->
            <div class="xl:col-span-2 space-y-5">

                <!-- Image -->
                <div class="service-card bg-white rounded-2xl shadow-card overflow-hidden">
                    <div class="relative aspect-video bg-cream-50">
                        <img :src="service.image_url" :alt="service.name"
                             class="w-full h-full object-cover" />
                        <!-- Badges sur image -->
                        <div class="absolute top-4 left-4 flex gap-2">
                            <span v-if="service.is_featured"
                                  class="flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-poppins font-bold text-white shadow"
                                  style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                                <PhStar :size="12" weight="fill" /> En vedette
                            </span>
                            <span v-if="service.requires_consultation"
                                  class="flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-poppins font-bold"
                                  style="background: rgba(99,102,241,0.15); color: #6366f1;">
                                <PhInfo :size="12" /> Consultation requise
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Description -->
                <div class="service-card bg-white rounded-2xl shadow-card p-5">
                    <h2 class="font-poppins font-semibold text-onyx-800 text-sm mb-4">Description</h2>

                    <div v-if="service.short_description"
                         class="px-4 py-3 rounded-xl mb-4 text-sm font-poppins text-onyx-700 leading-relaxed"
                         style="background: rgba(196,149,106,0.06); border-left: 3px solid #c4956a;">
                        {{ service.short_description }}
                    </div>

                    <p v-if="service.description"
                       class="text-sm font-poppins text-onyx-600 leading-relaxed whitespace-pre-wrap">
                        {{ service.description }}
                    </p>
                    <p v-if="!service.short_description && !service.description"
                       class="text-sm font-poppins text-onyx-400 italic">Aucune description renseignée</p>
                </div>

                <!-- Includes + Requirements -->
                <div v-if="service.includes?.length || service.requirements?.length"
                     class="service-card grid grid-cols-1 sm:grid-cols-2 gap-5">

                    <!-- Inclus -->
                    <div v-if="service.includes?.length"
                         class="bg-white rounded-2xl shadow-card p-5">
                        <h2 class="font-poppins font-semibold text-onyx-800 text-sm mb-3 flex items-center gap-2">
                            <PhCheckCircle :size="16" class="text-emerald-500" />
                            Inclus dans la prestation
                        </h2>
                        <ul class="space-y-2">
                            <li v-for="item in service.includes" :key="item"
                                class="flex items-center gap-2.5 text-sm font-poppins text-onyx-700">
                                <span class="w-5 h-5 rounded-lg flex items-center justify-center shrink-0"
                                      style="background: rgba(16,185,129,0.1);">
                                    <PhCheckCircle :size="12" class="text-emerald-500" />
                                </span>
                                {{ item }}
                            </li>
                        </ul>
                    </div>

                    <!-- Prérequis -->
                    <div v-if="service.requirements?.length"
                         class="bg-white rounded-2xl shadow-card p-5">
                        <h2 class="font-poppins font-semibold text-onyx-800 text-sm mb-3 flex items-center gap-2">
                            <PhWarning :size="16" class="text-amber-500" />
                            Prérequis clients
                        </h2>
                        <ul class="space-y-2">
                            <li v-for="item in service.requirements" :key="item"
                                class="flex items-center gap-2.5 text-sm font-poppins text-onyx-700">
                                <span class="w-5 h-5 rounded-lg flex items-center justify-center shrink-0"
                                      style="background: rgba(245,158,11,0.1);">
                                    <PhWarning :size="12" class="text-amber-500" />
                                </span>
                                {{ item }}
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Avis récents -->
                <div v-if="recent_reviews?.length"
                     class="service-card bg-white rounded-2xl shadow-card overflow-hidden">
                    <div class="flex items-center justify-between px-5 py-4"
                         style="border-bottom: 1px solid #f5ede4;">
                        <div class="flex items-center gap-3">
                            <PhStar :size="16" weight="fill" class="text-amber-400" />
                            <h2 class="font-poppins font-semibold text-onyx-800 text-sm">Avis récents</h2>
                            <span class="text-xs font-poppins px-2 py-0.5 rounded-lg bg-amber-50 text-amber-700 font-semibold">
                                {{ stats.avg_rating?.toFixed(1) ?? '—' }}/5
                            </span>
                        </div>
                        <Link :href="route('admin.avis.index')"
                              class="text-xs font-poppins text-cognac-600 hover:text-cognac-800 transition-colors font-medium">
                            Voir tous
                        </Link>
                    </div>
                    <div class="divide-y divide-cream-50">
                        <div v-for="review in recent_reviews" :key="review.id"
                             class="px-5 py-4">
                            <div class="flex items-center justify-between mb-1">
                                <p class="text-sm font-poppins font-semibold text-onyx-700">{{ review.client.name }}</p>
                                <div class="flex items-center gap-0.5">
                                    <PhStar v-for="i in 5" :key="i" :size="12"
                                            :weight="i <= review.rating ? 'fill' : 'regular'"
                                            :class="i <= review.rating ? 'text-amber-400' : 'text-onyx-200'" />
                                </div>
                            </div>
                            <p class="text-xs font-poppins text-onyx-500">{{ review.comment }}</p>
                            <p class="text-xs font-poppins text-onyx-300 mt-1">{{ review.date }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Droite : stats + infos -->
            <div class="space-y-5">

                <!-- Stats -->
                <div class="service-card bg-white rounded-2xl shadow-card p-5">
                    <h2 class="font-poppins font-semibold text-onyx-800 text-sm mb-4 flex items-center gap-2">
                        <PhCalendarCheck :size="16" class="text-cognac-500" />
                        Performances
                    </h2>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="p-3 rounded-xl text-center" style="background: #f9f7f4; border: 1px solid #edddd0;">
                            <p class="text-xl font-bold font-poppins text-onyx-800">
                                {{ stats.total_revenue ? formatPrice(stats.total_revenue) : '—' }}
                            </p>
                            <p class="text-xs font-poppins text-onyx-400 mt-0.5">CA généré</p>
                        </div>
                        <div class="p-3 rounded-xl text-center" style="background: #f9f7f4; border: 1px solid #edddd0;">
                            <p class="text-xl font-bold font-poppins text-onyx-800">{{ service.appointments_count }}</p>
                            <p class="text-xs font-poppins text-onyx-400 mt-0.5">RDV total</p>
                        </div>
                        <div class="p-3 rounded-xl text-center" style="background: #f9f7f4; border: 1px solid #edddd0;">
                            <p class="text-xl font-bold font-poppins text-emerald-600">{{ stats.completion_rate }}%</p>
                            <p class="text-xs font-poppins text-onyx-400 mt-0.5">Taux complétion</p>
                        </div>
                        <div class="p-3 rounded-xl text-center" style="background: #f9f7f4; border: 1px solid #edddd0;">
                            <p class="text-xl font-bold font-poppins text-onyx-800">{{ stats.upcoming }}</p>
                            <p class="text-xs font-poppins text-onyx-400 mt-0.5">RDV à venir</p>
                        </div>
                    </div>
                </div>

                <!-- Tarif & durée -->
                <div class="service-card bg-white rounded-2xl shadow-card p-5">
                    <h2 class="font-poppins font-semibold text-onyx-800 text-sm mb-4 flex items-center gap-2">
                        <PhCurrencyEur :size="16" class="text-cognac-500" />
                        Tarif & durée
                    </h2>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-poppins text-onyx-500">Prix de la prestation</span>
                            <span class="text-xl font-bold font-poppins text-onyx-800">{{ formatPrice(service.price) }}</span>
                        </div>
                        <div v-if="service.deposit_required" class="flex items-center justify-between">
                            <span class="text-xs font-poppins text-onyx-500">Acompte requis</span>
                            <span class="text-sm font-poppins font-semibold text-indigo-600">
                                {{ formatPrice(service.deposit_amount) }}
                            </span>
                        </div>
                        <div class="pt-2 border-t border-cream-100 flex items-center justify-between">
                            <span class="text-xs font-poppins text-onyx-500 flex items-center gap-1">
                                <PhClock :size="12" />
                                Durée prestation
                            </span>
                            <span class="text-sm font-bold font-poppins text-cognac-600">{{ service.duration_formatted }}</span>
                        </div>
                        <div v-if="service.buffer_time" class="flex items-center justify-between">
                            <span class="text-xs font-poppins text-onyx-500">Temps tampon</span>
                            <span class="text-sm font-poppins text-onyx-600">{{ service.buffer_time }} min</span>
                        </div>
                    </div>
                </div>

                <!-- Infos supplémentaires -->
                <div class="service-card bg-white rounded-2xl shadow-card p-5">
                    <h2 class="font-poppins font-semibold text-onyx-800 text-sm mb-4">Paramètres</h2>
                    <div class="space-y-2.5 text-xs font-poppins">
                        <div class="flex items-center justify-between">
                            <span class="text-onyx-400 flex items-center gap-1">
                                <PhUsers :size="12" /> Clients max / créneau
                            </span>
                            <span class="font-semibold text-onyx-700">{{ service.max_clients_per_slot }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-onyx-400">Consultation requise</span>
                            <span :class="service.requires_consultation ? 'text-indigo-600 font-semibold' : 'text-onyx-400'">
                                {{ service.requires_consultation ? '✓ Oui' : '✗ Non' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-onyx-400">Acompte obligatoire</span>
                            <span :class="service.deposit_required ? 'text-indigo-600 font-semibold' : 'text-onyx-400'">
                                {{ service.deposit_required ? '✓ Oui' : '✗ Non' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-onyx-400">Ordre d'affichage</span>
                            <span class="font-semibold text-onyx-700">{{ service.sort_order }}</span>
                        </div>
                    </div>
                </div>

                <!-- Voir sur la vitrine -->
                <a :href="`/services`" target="_blank"
                   class="service-card flex items-center justify-center gap-2 w-full py-3 rounded-2xl border border-cream-200 bg-white text-sm font-poppins font-medium text-onyx-600 hover:text-cognac-600 hover:border-cognac-300 hover:bg-cognac-50 transition-all shadow-card">
                    <PhArrowSquareOut :size="16" />
                    Voir sur la vitrine
                </a>
            </div>
        </div>
    </div>
</template>