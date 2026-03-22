<script setup>
import { computed, watch, ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { gsap } from 'gsap'
import { PhCheckCircle, PhWarning, PhXCircle, PhInfo, PhX } from '@phosphor-icons/vue'

const page    = usePage()
const flash   = computed(() => page.props.flash)
const visible = ref(true)
const panelRef = ref(null)

const config = {
    success: { icon: PhCheckCircle, bg: 'bg-emerald-50', border: 'border-emerald-200', text: 'text-emerald-800', icon_color: 'text-emerald-500' },
    error:   { icon: PhXCircle,     bg: 'bg-red-50',     border: 'border-red-200',     text: 'text-red-800',     icon_color: 'text-red-500' },
    warning: { icon: PhWarning,     bg: 'bg-amber-50',   border: 'border-amber-200',   text: 'text-amber-800',   icon_color: 'text-amber-500' },
    info:    { icon: PhInfo,        bg: 'bg-blue-50',    border: 'border-blue-200',    text: 'text-blue-800',    icon_color: 'text-blue-500' },
}

const activeMessage = computed(() => {
    for (const type of ['success', 'error', 'warning', 'info']) {
        if (flash.value?.[type]) return { type, message: flash.value[type] }
    }
    return null
})

watch(activeMessage, (val) => {
    if (val) {
        visible.value = true
        setTimeout(() => dismiss(), 5000)
    }
})

function dismiss() {
    if (panelRef.value) {
        gsap.to(panelRef.value, {
            opacity: 0, y: -10, duration: 0.3, ease: 'power2.in',
            onComplete: () => { visible.value = false }
        })
    }
}
</script>

<template>
    <Transition name="flash">
        <div v-if="activeMessage && visible" ref="panelRef"
             class="fixed top-20 right-6 z-50 max-w-sm w-full">
            <div class="flex items-start gap-3 p-4 rounded-2xl shadow-float border"
                 :class="[config[activeMessage.type].bg, config[activeMessage.type].border]">

                <component :is="config[activeMessage.type].icon"
                           :size="20"
                           :class="config[activeMessage.type].icon_color"
                           class="shrink-0 mt-0.5" />

                <p class="flex-1 text-sm font-poppins font-medium"
                   :class="config[activeMessage.type].text">
                    {{ activeMessage.message }}
                </p>

                <button @click="dismiss"
                        :class="config[activeMessage.type].text"
                        class="shrink-0 opacity-60 hover:opacity-100 transition-opacity">
                    <PhX :size="16" />
                </button>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.flash-enter-active { transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1); }
.flash-leave-active { transition: all 0.2s ease; }
.flash-enter-from   { opacity: 0; transform: translateX(100%); }
.flash-leave-to     { opacity: 0; transform: translateX(100%); }
</style>