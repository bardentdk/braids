<script setup>
import { ref, onMounted } from 'vue'
import { useForm, Head, Link } from '@inertiajs/vue3'
import { gsap } from 'gsap'
import { PhBridge, PhEnvelope, PhArrowLeft, PhPaperPlaneTilt, PhCheckCircle } from '@phosphor-icons/vue'

defineProps({ status: String })

const form    = useForm({ email: '' })
const formRef = ref(null)

onMounted(() => {
    gsap.fromTo(formRef.value,
        { opacity: 0, y: 30 },
        { opacity: 1, y: 0, duration: 0.7, ease: 'power3.out' }
    )
})

function submit() {
    form.post(route('password.email'))
}
</script>

<template>
    <Head title="Mot de passe oublié" />

    <div class="min-h-screen bg-cream-100 bg-pattern-braids flex items-center justify-center p-6">
        <div ref="formRef" class="w-full max-w-md">
            <div class="glass-card rounded-3xl shadow-luxury p-10">

                <!-- Logo -->
                <div class="flex items-center gap-3 mb-8">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center"
                         style="background: linear-gradient(135deg, #c4956a, #d4af37);">
                        <PhBridge :size="22" weight="bold" class="text-white" />
                    </div>
                    <span class="font-cormorant text-xl font-semibold text-onyx-800">Patricia Braids</span>
                </div>

                <!-- Succès -->
                <div v-if="status"
                     class="mb-6 p-5 rounded-2xl bg-emerald-50 border border-emerald-200">
                    <div class="flex items-start gap-3">
                        <PhCheckCircle :size="22" class="text-emerald-600 shrink-0 mt-0.5" />
                        <div>
                            <p class="text-sm font-semibold text-emerald-800 font-poppins">Email envoyé</p>
                            <p class="text-sm text-emerald-700 font-poppins mt-0.5">{{ status }}</p>
                        </div>
                    </div>
                </div>

                <div v-else>
                    <div class="mb-8">
                        <h2 class="font-cormorant text-3xl font-bold text-onyx-800">
                            Mot de passe oublié ?
                        </h2>
                        <p class="mt-2 text-sm text-onyx-500 font-poppins leading-relaxed">
                            Entrez votre adresse email et nous vous enverrons un lien pour réinitialiser votre mot de passe.
                        </p>
                    </div>

                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <label class="block text-xs font-semibold text-onyx-600 uppercase tracking-widest mb-2">
                                Adresse email
                            </label>
                            <div class="relative">
                                <PhEnvelope :size="18" class="absolute left-4 top-1/2 -translate-y-1/2 text-onyx-400 pointer-events-none" />
                                <input
                                    v-model="form.email"
                                    type="email"
                                    placeholder="votre@email.com"
                                    class="input-brand pl-11"
                                    :class="{ 'border-red-400': form.errors.email }"
                                />
                            </div>
                            <p v-if="form.errors.email" class="mt-1.5 text-xs text-red-500">{{ form.errors.email }}</p>
                        </div>

                        <button type="submit" :disabled="form.processing" class="btn-primary w-full justify-center py-4">
                            <span v-if="!form.processing" class="flex items-center gap-2 relative z-10">
                                <PhPaperPlaneTilt :size="18" weight="bold" />
                                Envoyer le lien
                            </span>
                            <span v-else class="flex items-center gap-2">
                                <svg class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                                Envoi...
                            </span>
                        </button>
                    </form>
                </div>

                <div class="mt-8 pt-6 border-t border-cream-200">
                    <Link :href="route('login')"
                          class="flex items-center gap-2 text-sm text-onyx-500 hover:text-cognac-600 transition-colors font-poppins">
                        <PhArrowLeft :size="16" />
                        Retour à la connexion
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>