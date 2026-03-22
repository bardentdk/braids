<script setup>
import { ref, onMounted } from 'vue'
import { useForm, Head, Link } from '@inertiajs/vue3'
import { gsap } from 'gsap'
import { PhBraid, PhLockKey, PhEye, PhEyeSlash, PhArrowRight } from '@phosphor-icons/vue'

const props = defineProps({ token: String, email: String })

const form = useForm({
    token:                 props.token,
    email:                 props.email,
    password:              '',
    password_confirmation: '',
})

const showPassword = ref(false)
const showConfirm  = ref(false)
const formRef      = ref(null)

onMounted(() => {
    gsap.fromTo(formRef.value,
        { opacity: 0, y: 30 },
        { opacity: 1, y: 0, duration: 0.7, ease: 'power3.out' }
    )
})

function submit() {
    form.post(route('password.update'))
}
</script>

<template>
    <Head title="Réinitialiser le mot de passe" />

    <div class="min-h-screen bg-cream-100 bg-pattern-braids flex items-center justify-center p-6">
        <div ref="formRef" class="w-full max-w-md">
            <div class="glass-card rounded-3xl shadow-luxury p-10">

                <!-- Logo -->
                <div class="flex items-center gap-3 mb-8">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center"
                         style="background: linear-gradient(135deg, #c4956a, #d4af37);">
                        <PhBraid :size="22" weight="bold" class="text-white" />
                    </div>
                    <span class="font-cormorant text-xl font-semibold text-onyx-800">Patricia Braids</span>
                </div>

                <div class="mb-8">
                    <h2 class="font-cormorant text-3xl font-bold text-onyx-800">Nouveau mot de passe</h2>
                    <p class="mt-2 text-sm text-onyx-500 font-poppins">Choisissez un mot de passe sécurisé pour votre compte.</p>
                </div>

                <form @submit.prevent="submit" class="space-y-5">

                    <div>
                        <label class="block text-xs font-semibold text-onyx-600 uppercase tracking-widest mb-2">Nouveau mot de passe</label>
                        <div class="relative">
                            <PhLockKey :size="18" class="absolute left-4 top-1/2 -translate-y-1/2 text-onyx-400 pointer-events-none" />
                            <input
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                placeholder="Minimum 8 caractères"
                                class="input-brand pl-11 pr-12"
                                :class="{ 'border-red-400': form.errors.password }"
                            />
                            <button type="button" @click="showPassword = !showPassword"
                                    class="absolute right-4 top-1/2 -translate-y-1/2 text-onyx-400 hover:text-onyx-600">
                                <PhEye v-if="!showPassword" :size="18" />
                                <PhEyeSlash v-else :size="18" />
                            </button>
                        </div>
                        <p v-if="form.errors.password" class="mt-1.5 text-xs text-red-500">{{ form.errors.password }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-onyx-600 uppercase tracking-widest mb-2">Confirmation</label>
                        <div class="relative">
                            <PhLockKey :size="18" class="absolute left-4 top-1/2 -translate-y-1/2 text-onyx-400 pointer-events-none" />
                            <input
                                v-model="form.password_confirmation"
                                :type="showConfirm ? 'text' : 'password'"
                                placeholder="Répétez le mot de passe"
                                class="input-brand pl-11 pr-12"
                            />
                            <button type="button" @click="showConfirm = !showConfirm"
                                    class="absolute right-4 top-1/2 -translate-y-1/2 text-onyx-400 hover:text-onyx-600">
                                <PhEye v-if="!showConfirm" :size="18" />
                                <PhEyeSlash v-else :size="16" />
                            </button>
                        </div>
                        <p v-if="form.errors.token" class="mt-1.5 text-xs text-red-500">{{ form.errors.token }}</p>
                    </div>

                    <button type="submit" :disabled="form.processing" class="btn-primary w-full justify-center py-4 mt-2">
                        <span v-if="!form.processing" class="flex items-center gap-2 relative z-10">
                            Réinitialiser le mot de passe
                            <PhArrowRight :size="18" weight="bold" />
                        </span>
                        <span v-else class="flex items-center gap-2">
                            <svg class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            Réinitialisation...
                        </span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>