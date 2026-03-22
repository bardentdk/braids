<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { gsap } from 'gsap'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import {
    PhCaretLeft, PhCaretRight, PhCalendarBlank,
    PhClock, PhUser, PhList,
} from '@phosphor-icons/vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
    appointments: Array,
    year:         Number,
    month:        Number,
})

const currentYear  = ref(props.year  ?? new Date().getFullYear())
const currentMonth = ref(props.month ?? new Date().getMonth() + 1)

const monthNames = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre']
const dayNames   = ['Lun','Mar','Mer','Jeu','Ven','Sam','Dim']

function prevMonth() {
    if (currentMonth.value === 1) { currentMonth.value = 12; currentYear.value-- }
    else currentMonth.value--
    navigate()
}
function nextMonth() {
    if (currentMonth.value === 12) { currentMonth.value = 1; currentYear.value++ }
    else currentMonth.value++
    navigate()
}
function navigate() {
    router.get(route('admin.rendez-vous.calendar'), {
        year: currentYear.value, month: currentMonth.value,
    }, { preserveState: true, replace: true })
}

const calendarDays = computed(() => {
    const days      = []
    const firstDay  = new Date(currentYear.value, currentMonth.value - 1, 1)
    const lastDay   = new Date(currentYear.value, currentMonth.value, 0)
    let startDow    = firstDay.getDay() - 1
    if (startDow < 0) startDow = 6

    for (let i = startDow - 1; i >= 0; i--) {
        const d = new Date(firstDay); d.setDate(d.getDate() - i - 1)
        days.push({ date: d, current: false })
    }
    const today = new Date()
    for (let i = 1; i <= lastDay.getDate(); i++) {
        const d = new Date(currentYear.value, currentMonth.value - 1, i)
        days.push({ date: d, current: true, today: d.toDateString() === today.toDateString(), key: d.toISOString().slice(0, 10) })
    }
    while (days.length < 42) {
        const d = new Date(lastDay); d.setDate(lastDay.getDate() + (days.length - lastDay.getDate() - startDow + 1))
        days.push({ date: d, current: false })
    }
    return days
})

const appointmentsByDate = computed(() => {
    const map = {}
    ;(props.appointments ?? []).forEach(a => {
        if (!map[a.date]) map[a.date] = []
        map[a.date].push(a)
    })
    return map
})

function apptForDay(day) { return day.key ? (appointmentsByDate.value[day.key] ?? []) : [] }

const selectedDay   = ref(null)
const selectedAppts = computed(() => selectedDay.value ? (appointmentsByDate.value[selectedDay.value] ?? []) : [])

function selectDay(day) {
    if (!day.current) return
    selectedDay.value = selectedDay.value === day.key ? null : day.key
}

const statusColors = { pending: '#d97706', confirmed: '#059669', completed: '#6366f1', cancelled: '#dc2626' }
const statusLabels = { pending: 'En attente', confirmed: 'Confirmé', completed: 'Terminé', cancelled: 'Annulé' }

onMounted(() => {
    gsap.fromTo('.cal-cell', { opacity: 0, scale: 0.97 }, { opacity: 1, scale: 1, duration: 0.3, stagger: 0.004, ease: 'power2.out' })
})
</script>

<template>
    <Head :title="`Calendrier — ${monthNames[currentMonth-1]} ${currentYear}`" />
    <div class="p-6 space-y-5">

        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h1 class="font-cormorant text-3xl font-bold text-onyx-800">Calendrier</h1>
                <p class="text-sm font-poppins text-onyx-500 mt-0.5">{{ appointments?.length ?? 0 }} rendez-vous ce mois</p>
            </div>
            <div class="flex items-center gap-2">
                <Link :href="route('admin.rendez-vous.index')"
                      class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-poppins font-medium border border-cream-200 bg-white text-onyx-700 hover:bg-cream-50 shadow-card transition-colors">
                    <PhList :size="16" /> Liste
                </Link>
                <Link :href="route('admin.disponibilites.index')"
                      class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-poppins font-semibold text-white shadow-md transition-all hover:opacity-90"
                      style="background: linear-gradient(135deg, #c4956a, #b07d52);">
                    <PhClock :size="16" /> Disponibilités
                </Link>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-4 gap-5 items-start">

            <!-- Calendrier -->
            <div class="xl:col-span-3 bg-white rounded-2xl shadow-card overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4" style="border-bottom: 1px solid #f5ede4;">
                    <button @click="prevMonth" class="w-9 h-9 rounded-xl flex items-center justify-center text-onyx-500 hover:bg-cream-100 transition-colors">
                        <PhCaretLeft :size="18" />
                    </button>
                    <h2 class="font-cormorant text-2xl font-bold text-onyx-800">{{ monthNames[currentMonth - 1] }} {{ currentYear }}</h2>
                    <button @click="nextMonth" class="w-9 h-9 rounded-xl flex items-center justify-center text-onyx-500 hover:bg-cream-100 transition-colors">
                        <PhCaretRight :size="18" />
                    </button>
                </div>

                <div class="grid grid-cols-7 px-2 pt-3 pb-1">
                    <div v-for="d in dayNames" :key="d" class="text-center text-xs font-poppins font-semibold text-onyx-400 uppercase tracking-wide py-1">{{ d }}</div>
                </div>

                <div class="grid grid-cols-7 gap-1 p-2">
                    <div v-for="(day, i) in calendarDays" :key="i"
                         class="cal-cell min-h-20 rounded-xl p-1.5 transition-all cursor-pointer"
                         :class="{ 'opacity-25 cursor-default': !day.current, 'ring-2 ring-cognac-400': selectedDay === day.key, 'hover:bg-cream-50': day.current && selectedDay !== day.key }"
                         :style="day.today ? 'background: rgba(196,149,106,0.08);' : ''"
                         @click="selectDay(day)">
                        <div class="flex items-center justify-center w-7 h-7 rounded-lg mb-1 text-sm font-poppins font-semibold"
                             :class="day.today ? 'text-white' : 'text-onyx-700'"
                             :style="day.today ? 'background: linear-gradient(135deg, #c4956a, #b07d52);' : ''">
                            {{ day.date.getDate() }}
                        </div>
                        <div class="space-y-0.5">
                            <div v-for="appt in apptForDay(day).slice(0, 2)" :key="appt.id"
                                 class="text-xs font-poppins font-medium px-1.5 py-0.5 rounded-md truncate"
                                 :style="{ background: (statusColors[appt.status] ?? '#c4956a') + '18', color: statusColors[appt.status] ?? '#c4956a' }">
                                {{ appt.time_start }} {{ appt.client_name?.split(' ')[0] }}
                            </div>
                            <div v-if="apptForDay(day).length > 2" class="text-xs font-poppins text-onyx-400 px-1.5">
                                +{{ apptForDay(day).length - 2 }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Panneau détail -->
            <div class="bg-white rounded-2xl shadow-card overflow-hidden">
                <div class="px-4 py-3.5" style="border-bottom: 1px solid #f5ede4;">
                    <p class="text-xs font-poppins font-semibold text-onyx-600 uppercase tracking-wide">
                        {{ selectedDay ? selectedDay : 'Sélectionner un jour' }}
                    </p>
                </div>
                <div v-if="selectedDay && selectedAppts.length" class="divide-y divide-cream-50">
                    <div v-for="appt in selectedAppts" :key="appt.id" class="p-4">
                        <div class="flex items-start justify-between mb-1.5">
                            <p class="text-sm font-poppins font-semibold text-onyx-800">{{ appt.client_name }}</p>
                            <span class="text-xs font-poppins font-semibold px-2 py-0.5 rounded-lg shrink-0 ml-2"
                                  :style="{ background: (statusColors[appt.status] ?? '#c4956a') + '18', color: statusColors[appt.status] ?? '#c4956a' }">
                                {{ statusLabels[appt.status] ?? appt.status }}
                            </span>
                        </div>
                        <p class="text-xs font-poppins text-onyx-500">{{ appt.service_name }}</p>
                        <p class="text-xs font-poppins text-onyx-400 flex items-center gap-1 mt-1">
                            <PhClock :size="11" />{{ appt.time_start }} – {{ appt.time_end }}
                        </p>
                        <Link :href="route('admin.rendez-vous.show', appt.id)"
                              class="inline-flex items-center gap-1 text-xs font-poppins text-cognac-600 hover:text-cognac-800 font-medium mt-2 transition-colors">
                            Voir détail <PhCaretRight :size="11" />
                        </Link>
                    </div>
                </div>
                <div v-else class="flex flex-col items-center justify-center py-12 px-4 text-center">
                    <PhCalendarBlank :size="36" class="text-onyx-200 mb-3" />
                    <p class="text-xs font-poppins text-onyx-400">
                        {{ selectedDay ? 'Aucun rendez-vous ce jour' : 'Cliquez sur un jour pour voir le détail' }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>