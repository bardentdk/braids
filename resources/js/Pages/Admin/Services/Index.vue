<script setup>
import { ref, computed, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { usePage } from '@inertiajs/vue3'
import { markRaw } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import {
    PhScissors, PhPlus, PhMagnifyingGlass, PhFunnel, PhPencilSimple,
    PhTrash, PhEye, PhToggleLeft, PhToggleRight, PhStar, PhClock,
    PhCurrencyEur, PhCheckCircle, PhXCircle, PhArrowsClockwise,
    PhArrowsDownUp, PhUsers, PhX,
} from '@phosphor-icons/vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
    services:   Object,
    filters:    Object,
    categories: Array,
})

const breadcrumbs = [{ label: 'Services' }]

// ── Filtres ────────────────────────────────────────────────────────
const category = ref(props.filters?.category ?? '')
const active   = ref(props.filters?.active ?? '')

watch([category, active], () => applyFilters())

function applyFilters() {
    router.get(route('admin.services.index'), {
        category: category.value || undefined,
        active:   active.value !== '' ? active.value : undefined,
    }, { preserveState: true, replace: true })
}

function resetFilters() {
    category.value = ''
    active.value   = ''
    router.get(route('admin.services.index'))
}

const hasFilters = computed(() => category.value || active.value !== '')

// ── Actions ────────────────────────────────────────────────────────
const togglingId = ref(null)

function toggleService(service) {
    togglingId.value = service.id
    router.patch(route('admin.services.toggle', service.id), {}, {
        preserveState: true,
        onFinish: () => { togglingId.value = null },
    })
}

function deleteService(service) {
    if (!confirm(`Supprimer "${service.name}" ?`)) return
    router.delete(route('admin.services.destroy', service.id))
}

// ── Format ─────────────────────────────────────────────────────────
function formatPrice(val) {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(val ?? 0)
}

// ── Flash ──────────────────────────────────────────────────────────
const page  = usePage()
const flash = computed(() => page.props.flash)

// ── Couleurs par catégorie ─────────────────────────────────────────
const categoryColors = {
    braids:     { bg: 'rgba(196,149,106,0.12)', color: '#b07d52' },
    twists:     { bg: 'rgba(99,102,241,0.10)',  color: '#6366f1' },
    locs:       { bg: 'rgba(16,185,129,0.10)',  color: '#059669' },
    natural:    { bg: 'rgba(245,158,11,0.10)',  color: '#d97706' },
    extensions: { bg: 'rgba(236,72,153,0.10)',  color: '#db2777' },
    kids:       { bg: 'rgba(59,130,246,0.10)',  color: '#2563eb' },
    other:      { bg: 'rgba(107,114,128,0.10)', color: '#4b5563' },
}
function catStyle(cat) {
    return categoryColors[cat] ?? categoryColors.other
}
</script>

<template>
    <Head title="Services" />

    <div class="p-6 space-y-6">

        <!-- ── Header ─────────────────────────────────────────────── -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="font-cormorant text-3xl font-bold text-onyx-800">Services</h1>
                <p class="text-sm font-poppins text-onyx-500 mt-0.5">
                    Gérez vos prestations et tarifs
                </p>
            </div>
            <Link :href="route('admin.services.create')"
                  class="flex items-center gap-2 px-4 py-2.5 rounded-xl font-poppins text-sm font-semibold text-white transition-all hover:opacity-90 hover:-translate-y-0.5 shadow-md"
                  style="background: linear-gradient(135deg, #c4956a, #b07d52);">
                <PhPlus :size="17" weight="bold" />
                Nouveau service
            </Link>
        </div>

        <!-- ── Flash ──────────────────────────────────────────────── -->
        <div v-if="flash?.success"
             class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-poppins font-medium"
             style="background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.2); color: #059669;">
            <PhCheckCircle :size="18" />{{ flash.success }}
        </div>
        <div v-if="flash?.error"
             class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-poppins font-medium"
             style="background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.2); color: #dc2626;">
            <PhXCircle :size="18" />{{ flash.error }}
        </div>

        <!-- ── Stats rapides ───────────────────────────────────────── -->
        <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl p-4 shadow-card">
                <p class="text-2xl font-bold font-poppins text-onyx-800">{{ services.total }}</p>
                <p class="text-xs font-poppins text-onyx-400 mt-1">Total services</p>
            </div>
            <div class="bg-white rounded-2xl p-4 shadow-card">
                <p class="text-2xl font-bold font-poppins text-emerald-600">
                    {{ services.data.filter(s => s.is_active).length }}
                </p>
                <p class="text-xs font-poppins text-onyx-400 mt-1">Actifs (cette page)</p>
            </div>
            <div class="bg-white rounded-2xl p-4 shadow-card">
                <p class="text-2xl font-bold font-poppins text-cognac-600">
                    {{ services.data.filter(s => s.is_featured).length }}
                </p>
                <p class="text-xs font-poppins text-onyx-400 mt-1">En vedette (cette page)</p>
            </div>
            <div class="bg-white rounded-2xl p-4 shadow-card">
                <p class="text-2xl font-bold font-poppins text-onyx-800">
                    {{ categories.length }}
                </p>
                <p class="text-xs font-poppins text-onyx-400 mt-1">Catégories</p>
            </div>
        </div>

        <!-- ── Filtres ─────────────────────────────────────────────── -->
        <div class="bg-white rounded-2xl shadow-card p-4">
            <div class="flex flex-wrap items-center gap-3">

                <!-- Catégorie -->
                <div class="flex items-center gap-2">
                    <PhFunnel :size="15" class="text-onyx-400 shrink-0" />
                    <select v-model="category"
                            class="px-3 py-2.5 rounded-xl text-sm font-poppins text-onyx-700 bg-cream-50 border border-cream-200 focus:outline-none focus:border-cognac-400 transition-colors">
                        <option value="">Toutes les catégories</option>
                        <option v-for="cat in categories" :key="cat.value" :value="cat.value">
                            {{ cat.label }}
                        </option>
                    </select>
                </div>

                <!-- Statut -->
                <select v-model="active"
                        class="px-3 py-2.5 rounded-xl text-sm font-poppins text-onyx-700 bg-cream-50 border border-cream-200 focus:outline-none focus:border-cognac-400 transition-colors">
                    <option value="">Tous les statuts</option>
                    <option value="1">Actifs</option>
                    <option value="0">Inactifs</option>
                </select>

                <!-- Reset -->
                <button v-if="hasFilters" @click="resetFilters"
                        class="flex items-center gap-1.5 px-3 py-2.5 rounded-xl text-sm font-poppins text-onyx-500 hover:text-cognac-600 hover:bg-cream-100 transition-colors border border-cream-200">
                    <PhArrowsClockwise :size="15" />
                    Réinitialiser
                </button>

                <div class="flex-1" />
                <p class="text-xs font-poppins text-onyx-400">
                    {{ services.total }} service{{ services.total > 1 ? 's' : '' }}
                </p>
            </div>
        </div>

        <!-- ── Grille services ─────────────────────────────────────── -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            <div v-for="service in services.data" :key="service.id"
                 class="bg-white rounded-2xl shadow-card overflow-hidden group hover:shadow-float transition-all duration-300 hover:-translate-y-0.5">

                <!-- Image -->
                <div class="relative h-44 overflow-hidden bg-cream-50">
                    <img :src="service.image_url" :alt="service.name"
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />

                    <!-- Badges top-left -->
                    <div class="absolute top-3 left-3 flex flex-col gap-1.5">
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-poppins font-semibold"
                              :style="{ background: catStyle(service.category).bg, color: catStyle(service.category).color }">
                            {{ service.category_label }}
                        </span>
                        <span v-if="service.is_featured"
                              class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-poppins font-bold text-white"
                              style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                            <PhStar :size="11" weight="fill" /> Vedette
                        </span>
                        <span v-if="service.deposit_required"
                              class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-poppins font-semibold"
                              style="background: rgba(99,102,241,0.12); color: #6366f1;">
                            <PhCurrencyEur :size="11" /> Acompte
                        </span>
                    </div>

                    <!-- Statut actif dot -->
                    <div class="absolute top-3 right-3">
                        <span class="w-2.5 h-2.5 rounded-full block shadow"
                              :style="{ background: service.is_active ? '#10b981' : '#9ca3af' }" />
                    </div>

                    <!-- Hover overlay -->
                    <div class="absolute inset-0 bg-black/45 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                        <Link :href="route('admin.services.show', service.id)"
                              class="w-9 h-9 bg-white rounded-xl flex items-center justify-center shadow hover:bg-cream-50 transition-colors"
                              @click.stop>
                            <PhEye :size="17" class="text-onyx-700" />
                        </Link>
                        <Link :href="route('admin.services.edit', service.id)"
                              class="w-9 h-9 bg-white rounded-xl flex items-center justify-center shadow hover:bg-cream-50 transition-colors"
                              @click.stop>
                            <PhPencilSimple :size="17" class="text-onyx-700" />
                        </Link>
                        <button @click.stop="deleteService(service)"
                                class="w-9 h-9 bg-white rounded-xl flex items-center justify-center shadow hover:bg-red-50 transition-colors">
                            <PhTrash :size="17" class="text-red-500" />
                        </button>
                    </div>
                </div>

                <!-- Contenu -->
                <div class="p-4">
                    <h3 class="font-poppins font-semibold text-onyx-800 text-sm leading-snug mb-2">
                        {{ service.name }}
                    </h3>

                    <!-- Méta : durée + prix -->
                    <div class="flex items-center justify-between mb-3">
                        <span class="flex items-center gap-1.5 text-xs font-poppins text-onyx-500">
                            <PhClock :size="13" class="text-cognac-400" />
                            {{ service.duration_formatted }}
                        </span>
                        <span class="text-base font-bold font-poppins text-onyx-800">
                            {{ formatPrice(service.price) }}
                        </span>
                    </div>

                    <!-- Stats -->
                    <div class="flex items-center gap-3 mb-3 text-xs font-poppins text-onyx-400">
                        <span class="flex items-center gap-1">
                            <PhArrowsDownUp :size="12" />
                            {{ service.appointments_count }} RDV
                        </span>
                        <span class="flex items-center gap-1">
                            <PhStar :size="12" />
                            {{ service.reviews_count }} avis
                        </span>
                        <span v-if="service.max_clients_per_slot > 1" class="flex items-center gap-1">
                            <PhUsers :size="12" />
                            max {{ service.max_clients_per_slot }}
                        </span>
                    </div>

                    <!-- Actions bas -->
                    <div class="flex items-center gap-2 pt-3 border-t border-cream-100">
                        <button @click="toggleService(service)"
                                :disabled="togglingId === service.id"
                                class="flex-1 flex items-center justify-center gap-1.5 py-1.5 rounded-lg text-xs font-poppins font-medium transition-all disabled:opacity-60"
                                :class="service.is_active
                                    ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100'
                                    : 'bg-gray-50 text-gray-500 hover:bg-gray-100'">
                            <component :is="service.is_active ? markRaw(PhToggleRight) : markRaw(PhToggleLeft)" :size="15" />
                            {{ service.is_active ? 'Actif' : 'Inactif' }}
                        </button>
                        <Link :href="route('admin.services.edit', service.id)"
                              class="flex items-center justify-center w-8 h-8 rounded-lg bg-cream-50 hover:bg-cognac-50 text-onyx-500 hover:text-cognac-600 transition-colors">
                            <PhPencilSimple :size="15" />
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty state -->
        <div v-if="!services.data.length"
             class="flex flex-col items-center justify-center py-20">
            <div class="w-20 h-20 rounded-3xl flex items-center justify-center mb-5"
                 style="background: rgba(196,149,106,0.08);">
                <PhScissors :size="40" class="text-onyx-300" />
            </div>
            <p class="font-cormorant text-2xl font-bold text-onyx-600 mb-2">Aucun service trouvé</p>
            <p class="text-sm font-poppins text-onyx-400 mb-5">
                {{ hasFilters ? 'Modifiez vos filtres' : 'Créez votre première prestation' }}
            </p>
            <Link v-if="!hasFilters" :href="route('admin.services.create')"
                  class="flex items-center gap-2 px-5 py-2.5 rounded-xl font-poppins text-sm font-semibold text-white"
                  style="background: linear-gradient(135deg, #c4956a, #b07d52);">
                <PhPlus :size="17" weight="bold" /> Créer un service
            </Link>
        </div>

        <!-- Pagination -->
        <div v-if="services.last_page > 1"
             class="flex items-center justify-between bg-white rounded-2xl shadow-card px-5 py-3.5">
            <p class="text-xs font-poppins text-onyx-500">
                Page {{ services.current_page }} / {{ services.last_page }} · {{ services.total }} résultats
            </p>
            <div class="flex items-center gap-1.5">
                <Link v-if="services.prev_page_url" :href="services.prev_page_url"
                      class="px-3 py-1.5 rounded-lg text-sm font-poppins text-onyx-600 hover:bg-cream-100 border border-cream-200 transition-colors">
                    ← Précédent
                </Link>
                <Link v-if="services.next_page_url" :href="services.next_page_url"
                      class="px-3 py-1.5 rounded-lg text-sm font-poppins text-onyx-600 hover:bg-cream-100 border border-cream-200 transition-colors">
                    Suivant →
                </Link>
            </div>
        </div>

    </div>
</template>