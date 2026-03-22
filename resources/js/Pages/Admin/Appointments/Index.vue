<script setup>
import { ref, computed, watch } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import {
    PhCalendarCheck, PhMagnifyingGlass, PhX, PhArrowsClockwise,
    PhCheckCircle, PhClock, PhUser, PhPhone, PhCaretRight,
    PhCalendarBlank,
} from '@phosphor-icons/vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
    appointments: Object,
    filters:      Object,
    stats:        Object,
})

const page  = usePage()
const flash = computed(() => page.props.flash)

const search = ref(props.filters?.search ?? '')
const status = ref(props.filters?.status ?? '')
const date   = ref(props.filters?.date   ?? '')

let t = null
watch(search, () => { clearTimeout(t); t = setTimeout(applyFilters, 380) })
watch([status, date], applyFilters)

function applyFilters() {
    router.get(route('admin.rendez-vous.index'), {
        search: search.value || undefined,
        status: status.value || undefined,
        date:   date.value   || undefined,
    }, { preserveState: true, replace: true })
}

function resetFilters() {
    search.value = ''; status.value = ''; date.value = ''
    router.get(route('admin.rendez-vous.index'))
}

const hasFilters = computed(() => search.value || status.value || date.value)

function confirmAppt(appt) {
    router.patch(route('admin.rendez-vous.confirm', appt.id), {}, { preserveScroll: true })
}
function cancelAppt(appt) {
    if (!confirm('Annuler ce rendez-vous ?')) return
    router.patch(route('admin.rendez-vous.cancel', appt.id), {}, { preserveScroll: true })
}
function completeAppt(appt) {
    router.patch(route('admin.rendez-vous.complete', appt.id), {}, { preserveScroll: true })
}
function sendReminder(appt) {
    router.post(route('admin.rendez-vous.reminder', appt.id), {}, { preserveScroll: true })
}

const statusMap = {
    pending:   { label: 'En attente',  bg: 'rgba(245,158,11,0.1)',  color: '#d97706' },
    confirmed: { label: 'Confirmé',    bg: 'rgba(16,185,129,0.1)',  color: '#059669' },
    completed: { label: 'Terminé',     bg: 'rgba(99,102,241,0.1)',  color: '#6366f1' },
    cancelled: { label: 'Annulé',      bg: 'rgba(239,68,68,0.1)',   color: '#dc2626' },
    no_show:   { label: 'Absent',      bg: 'rgba(107,114,128,0.1)', color: '#6b7280' },
}
</script>

<template>
    <Head title="Rendez-vous" />
    <div class="p-6 space-y-6">

        <!-- Header -->
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h1 class="font-cormorant text-3xl font-bold text-onyx-800">Rendez-vous</h1>
                <p class="text-sm font-poppins text-onyx-500 mt-0.5">{{ appointments.total }} rendez-vous au total</p>
            </div>
            <div class="flex items-center gap-2">
                <Link :href="route('admin.rendez-vous.calendar')"
                      class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-poppins font-medium border border-cream-200 bg-white text-onyx-700 hover:bg-cream-50 shadow-card transition-colors">
                    <PhCalendarBlank :size="16" /> Calendrier
                </Link>
                <Link :href="route('admin.disponibilites.index')"
                      class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-poppins font-semibold text-white shadow-md transition-all hover:opacity-90 hover:-translate-y-0.5"
                      style="background: linear-gradient(135deg, #c4956a, #b07d52);">
                    <PhClock :size="16" /> Disponibilités
                </Link>
            </div>
        </div>

        <!-- Flash -->
        <div v-if="flash?.success" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-poppins font-medium"
             style="background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.2); color: #059669;">
            <PhCheckCircle :size="18" />{{ flash.success }}
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
            <div v-for="s in [
                { label: 'Total',      value: stats?.total     ?? 0, color: '#6b7280' },
                { label: 'En attente', value: stats?.pending   ?? 0, color: '#d97706' },
                { label: 'Confirmés',  value: stats?.confirmed ?? 0, color: '#059669' },
                { label: 'Terminés',   value: stats?.completed ?? 0, color: '#6366f1' },
                { label: 'Annulés',    value: stats?.cancelled ?? 0, color: '#dc2626' },
            ]" :key="s.label" class="bg-white rounded-2xl shadow-card p-4">
                <p class="text-2xl font-bold font-poppins text-onyx-800">{{ s.value }}</p>
                <p class="text-xs font-poppins mt-0.5" :style="{ color: s.color }">{{ s.label }}</p>
            </div>
        </div>

        <!-- Filtres -->
        <div class="bg-white rounded-2xl shadow-card p-4 flex flex-wrap gap-3 items-center">
            <div class="relative flex-1 min-w-48">
                <PhMagnifyingGlass :size="15" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-onyx-400" />
                <input v-model="search" type="text" placeholder="Client, service…"
                       class="w-full pl-9 pr-8 py-2.5 rounded-xl text-sm font-poppins bg-cream-50 border border-cream-200 focus:border-cognac-400 focus:outline-none transition-colors" />
                <button v-if="search" @click="search=''" class="absolute right-3 top-1/2 -translate-y-1/2 text-onyx-300 hover:text-onyx-600">
                    <PhX :size="14" />
                </button>
            </div>
            <input v-model="date" type="date"
                   class="px-3 py-2.5 rounded-xl text-sm font-poppins text-onyx-700 bg-cream-50 border border-cream-200 focus:outline-none focus:border-cognac-400" />
            <select v-model="status" class="px-3 py-2.5 rounded-xl text-sm font-poppins text-onyx-700 bg-cream-50 border border-cream-200 focus:outline-none focus:border-cognac-400">
                <option value="">Tous les statuts</option>
                <option value="pending">En attente</option>
                <option value="confirmed">Confirmés</option>
                <option value="completed">Terminés</option>
                <option value="cancelled">Annulés</option>
                <option value="no_show">Absents</option>
            </select>
            <button v-if="hasFilters" @click="resetFilters"
                    class="flex items-center gap-1.5 px-3 py-2.5 rounded-xl text-sm font-poppins text-onyx-500 hover:text-cognac-600 border border-cream-200 hover:bg-cream-50 transition-colors">
                <PhArrowsClockwise :size="15" /> Réinitialiser
            </button>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-2xl shadow-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr style="border-bottom: 1px solid #f5ede4; background: #faf7f4;">
                            <th class="text-left px-5 py-3.5 text-xs font-poppins font-semibold text-onyx-500 uppercase tracking-wide">Client & Service</th>
                            <th class="text-left px-4 py-3.5 text-xs font-poppins font-semibold text-onyx-500 uppercase tracking-wide hidden md:table-cell">Date & Heure</th>
                            <th class="text-center px-4 py-3.5 text-xs font-poppins font-semibold text-onyx-500 uppercase tracking-wide hidden lg:table-cell">Durée</th>
                            <th class="text-left px-4 py-3.5 text-xs font-poppins font-semibold text-onyx-500 uppercase tracking-wide hidden xl:table-cell">Prix</th>
                            <th class="text-center px-4 py-3.5 text-xs font-poppins font-semibold text-onyx-500 uppercase tracking-wide">Statut</th>
                            <th class="text-right px-5 py-3.5 text-xs font-poppins font-semibold text-onyx-500 uppercase tracking-wide">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-cream-50">
                        <tr v-for="appt in appointments.data" :key="appt.id" class="hover:bg-cream-50/40 transition-colors">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0"
                                         style="background: rgba(196,149,106,0.1);">
                                        <PhUser :size="16" class="text-cognac-500" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-poppins font-semibold text-onyx-800">{{ appt.client_name }}</p>
                                        <p class="text-xs font-poppins text-onyx-400">{{ appt.service_name }}</p>
                                        <p v-if="appt.client_phone" class="text-xs font-poppins text-onyx-300 flex items-center gap-1 mt-0.5">
                                            <PhPhone :size="10" />{{ appt.client_phone }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 hidden md:table-cell">
                                <p class="text-sm font-poppins font-semibold text-onyx-700">{{ appt.date }}</p>
                                <p class="text-xs font-poppins text-onyx-400">{{ appt.time_start }} – {{ appt.time_end }}</p>
                            </td>
                            <td class="px-4 py-4 text-center hidden lg:table-cell">
                                <span class="text-sm font-poppins text-onyx-600">{{ appt.duration }} min</span>
                            </td>
                            <td class="px-4 py-4 hidden xl:table-cell">
                                <span class="text-sm font-poppins font-semibold text-onyx-700">{{ appt.price }}</span>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <span class="px-2.5 py-1 rounded-xl text-xs font-poppins font-semibold"
                                      :style="{ background: statusMap[appt.status]?.bg, color: statusMap[appt.status]?.color }">
                                    {{ statusMap[appt.status]?.label ?? appt.status }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex items-center justify-end gap-1 flex-wrap">
                                    <button v-if="appt.status === 'pending'"
                                            @click="confirmAppt(appt)"
                                            class="px-2.5 py-1.5 rounded-lg text-xs font-poppins font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 transition-colors whitespace-nowrap">
                                        Confirmer
                                    </button>
                                    <button v-if="appt.status === 'confirmed'"
                                            @click="completeAppt(appt)"
                                            class="px-2.5 py-1.5 rounded-lg text-xs font-poppins font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 transition-colors whitespace-nowrap">
                                        Terminer
                                    </button>
                                    <button v-if="appt.status === 'confirmed'"
                                            @click="sendReminder(appt)"
                                            class="px-2.5 py-1.5 rounded-lg text-xs font-poppins font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-200 transition-colors whitespace-nowrap">
                                        Rappel
                                    </button>
                                    <button v-if="['pending','confirmed'].includes(appt.status)"
                                            @click="cancelAppt(appt)"
                                            class="px-2.5 py-1.5 rounded-lg text-xs font-poppins font-semibold text-red-600 bg-red-50 hover:bg-red-100 border border-red-200 transition-colors whitespace-nowrap">
                                        Annuler
                                    </button>
                                    <Link :href="route('admin.rendez-vous.show', appt.id)"
                                          class="w-7 h-7 rounded-lg flex items-center justify-center text-onyx-400 hover:text-onyx-700 hover:bg-cream-100 transition-colors">
                                        <PhCaretRight :size="14" />
                                    </Link>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="!appointments.data?.length" class="flex flex-col items-center justify-center py-16">
                <div class="w-16 h-16 rounded-2xl flex items-center justify-center mb-4" style="background: rgba(196,149,106,0.08);">
                    <PhCalendarCheck :size="32" class="text-onyx-300" />
                </div>
                <p class="text-sm font-poppins font-semibold text-onyx-600 mb-1">Aucun rendez-vous</p>
                <p class="text-xs font-poppins text-onyx-400">Modifiez les filtres ou attendez de nouvelles réservations</p>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="appointments.last_page > 1" class="flex items-center justify-between bg-white rounded-2xl shadow-card px-5 py-3.5">
            <p class="text-xs font-poppins text-onyx-500">Page {{ appointments.current_page }} / {{ appointments.last_page }} · {{ appointments.total }} rendez-vous</p>
            <div class="flex gap-1.5">
                <Link v-if="appointments.prev_page_url" :href="appointments.prev_page_url"
                      class="px-3 py-1.5 rounded-lg text-sm font-poppins text-onyx-600 hover:bg-cream-100 border border-cream-200 transition-colors">← Précédent</Link>
                <Link v-if="appointments.next_page_url" :href="appointments.next_page_url"
                      class="px-3 py-1.5 rounded-lg text-sm font-poppins text-onyx-600 hover:bg-cream-100 border border-cream-200 transition-colors">Suivant →</Link>
            </div>
        </div>
    </div>
</template>