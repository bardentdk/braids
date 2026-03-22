<script setup>
import { ref, onMounted } from 'vue'
import { Head } from '@inertiajs/vue3'
import { gsap } from 'gsap'
import {
    PhLock, PhCreditCard, PhArrowLeft, PhCheckCircle,
    PhSpinnerGap, PhShieldCheck, PhWarning,
} from '@phosphor-icons/vue'

const props = defineProps({
    type:           String,
    id:             Number,
    amount:         Number,
    description:    String,
    reference:      String,
    cancelRoute:    String,
    stripeKey:      String,
    paypalClientId: String,
})

// ── État global ────────────────────────────────────────────────────
const paymentMethod = ref('stripe')
const processing    = ref(false)
const errorMsg      = ref(null)

// ── Stripe ─────────────────────────────────────────────────────────
const stripeReady   = ref(false)
const stripeError   = ref(null)

let stripe         = null
let elements       = null
let paymentElement = null

async function initStripe() {
    stripeError.value = null
    stripeReady.value = false

    try {
        // 1. Charger Stripe.js si pas encore présent
        if (!window.Stripe) {
            await loadScript('https://js.stripe.com/v3/')
        }
        stripe = window.Stripe(props.stripeKey)

        // 2. Créer le PaymentIntent via AXIOS (CSRF géré automatiquement par bootstrap.js)
        const { data } = await window.axios.post(route('payment.stripe.intent'), {
            type: props.type,
            id:   props.id,
        })

        if (data.error) {
            stripeError.value = data.error
            return
        }

        // 3. Monter Stripe Elements
        elements = stripe.elements({
            clientSecret: data.client_secret,
            appearance: {
                theme: 'stripe',
                variables: {
                    colorPrimary:     '#c4956a',
                    colorBackground:  '#faf7f4',
                    colorText:        '#1a1a2e',
                    colorDanger:      '#e14d6e',
                    fontFamily:       '"Poppins", sans-serif',
                    borderRadius:     '12px',
                },
                rules: {
                    '.Input': {
                        border:          '1px solid #e5d5c5',
                        backgroundColor: '#faf7f4',
                        boxShadow:       'none',
                        padding:         '12px 14px',
                    },
                    '.Input:focus': { border: '1px solid #c4956a', boxShadow: 'none' },
                    '.Label': {
                        color:          '#555',
                        fontWeight:     '600',
                        fontSize:       '11px',
                        textTransform:  'uppercase',
                        letterSpacing:  '0.08em',
                    },
                },
            },
        })

        paymentElement = elements.create('payment', { layout: 'tabs' })
        paymentElement.mount('#stripe-payment-element')
        paymentElement.on('ready',  ()  => { stripeReady.value = true })
        paymentElement.on('change', (e) => {
            if (e.error) stripeError.value = e.error.message
            else stripeError.value = null
        })

    } catch (e) {
        stripeError.value = e?.response?.data?.error
            ?? e?.message
            ?? 'Impossible d\'initialiser le formulaire de paiement.'
    }
}

async function payWithStripe() {
    if (processing.value || !stripeReady.value) return
    processing.value = true
    errorMsg.value   = null

    try {
        // 1. Confirmer le paiement côté Stripe
        const successUrl = window.location.origin
            + '/paiement/' + props.type + '/' + props.id + '/succes'

        const { error: stripeErr, paymentIntent } = await stripe.confirmPayment({
            elements,
            confirmParams: { return_url: successUrl },
            redirect: 'if_required',
        })

        if (stripeErr) {
            errorMsg.value   = stripeErr.message
            processing.value = false
            return
        }

        if (paymentIntent?.status === 'succeeded') {
            // 2. Confirmer côté serveur via AXIOS
            const { data } = await window.axios.post(route('payment.stripe.confirm'), {
                payment_intent_id: paymentIntent.id,
                type: props.type,
                id:   props.id,
            })

            if (data.success && data.redirect) {
                window.location.href = data.redirect
                return
            }

            errorMsg.value = data.error ?? 'Erreur lors de la confirmation.'
        } else {
            errorMsg.value = 'Le paiement n\'a pas abouti. Veuillez réessayer.'
        }

    } catch (e) {
        errorMsg.value = e?.response?.data?.error
            ?? 'Une erreur est survenue. Veuillez réessayer.'
    } finally {
        processing.value = false
    }
}

// ── PayPal ─────────────────────────────────────────────────────────
const paypalLoading = ref(false)

async function initPaypal() {
    if (window.paypal) { renderPaypalButtons(); return }
    paypalLoading.value = true
    try {
        await loadScript(
            `https://www.paypal.com/sdk/js?client-id=${props.paypalClientId}&currency=EUR&locale=fr_FR`
        )
        renderPaypalButtons()
    } catch {
        errorMsg.value = 'Impossible de charger PayPal.'
    } finally {
        paypalLoading.value = false
    }
}

function renderPaypalButtons() {
    const container = document.getElementById('paypal-button-container')
    if (!container || !window.paypal) return
    container.innerHTML = ''

    window.paypal.Buttons({
        style: { shape: 'rect', color: 'gold', layout: 'vertical', label: 'pay', height: 45 },

        createOrder: async () => {
            const { data } = await window.axios.post(route('payment.paypal.create'), {
                type: props.type,
                id:   props.id,
            })
            if (data.error) throw new Error(data.error)
            return data.paypal_order_id ?? data.order_id
        },

        onApprove: (data) => {
            processing.value = true
            const captureUrl = route('payment.paypal.capture', { type: props.type, id: props.id })
            window.location.href = captureUrl + '?token=' + data.orderID
        },

        onError: () => { errorMsg.value = 'Paiement PayPal échoué.'; processing.value = false },
        onCancel: () => { errorMsg.value = 'Paiement PayPal annulé.' },
    }).render('#paypal-button-container')
}

async function switchMethod(method) {
    paymentMethod.value = method
    errorMsg.value      = null
    if (method === 'paypal') await initPaypal()
}

// ── Utilitaires ────────────────────────────────────────────────────
function loadScript(src) {
    return new Promise((resolve, reject) => {
        if (document.querySelector(`script[src="${src}"]`)) { resolve(); return }
        const s = document.createElement('script')
        s.src = src; s.onload = resolve
        s.onerror = () => reject(new Error(`Échec chargement: ${src}`))
        document.head.appendChild(s)
    })
}

function formatPrice(val) {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(val ?? 0)
}

onMounted(async () => {
    gsap.fromTo('.payment-card',
        { opacity: 0, y: 24 },
        { opacity: 1, y: 0, duration: 0.5, stagger: 0.1, ease: 'power3.out' }
    )
    await initStripe()
})
</script>

<template>
    <Head :title="`Paiement — ${reference}`" />

    <div class="min-h-screen flex items-center justify-center p-4"
         style="background: linear-gradient(135deg, #faf7f4 0%, #f5ede4 100%);">
        <div class="w-full max-w-lg space-y-5">

            <!-- Header -->
            <div class="payment-card flex items-center justify-between">
                <a :href="cancelRoute"
                   class="flex items-center gap-2 text-sm font-poppins text-onyx-500 hover:text-onyx-800 transition-colors">
                    <PhArrowLeft :size="16" /> Retour
                </a>
                <span class="flex items-center gap-1.5 text-xs font-poppins text-onyx-400">
                    <PhLock :size="14" class="text-emerald-500" /> Paiement sécurisé SSL
                </span>
            </div>

            <!-- Récap -->
            <div class="payment-card bg-white rounded-2xl shadow-card p-5">
                <div class="flex items-start justify-between mb-1">
                    <h2 class="font-cormorant text-xl font-bold text-onyx-800">
                        {{ type === 'order' ? 'Récapitulatif commande' : 'Acompte rendez-vous' }}
                    </h2>
                    <span class="text-xs font-poppins text-onyx-400 bg-cream-100 px-2 py-1 rounded-lg ml-3 shrink-0">
                        {{ reference }}
                    </span>
                </div>
                <p class="text-sm font-poppins text-onyx-500 mb-4">{{ description }}</p>
                <div class="flex items-center justify-between pt-4 border-t border-cream-100">
                    <span class="text-sm font-poppins font-semibold text-onyx-600">
                        {{ type === 'appointment' ? 'Acompte à régler' : 'Total à payer' }}
                    </span>
                    <span class="text-2xl font-bold font-poppins text-onyx-800">{{ formatPrice(amount) }}</span>
                </div>
            </div>

            <!-- Choix méthode -->
            <div class="payment-card bg-white rounded-2xl shadow-card p-4">
                <p class="text-xs font-poppins font-semibold text-onyx-500 uppercase tracking-wide mb-3">Mode de paiement</p>
                <div class="grid grid-cols-2 gap-3">
                    <button @click="switchMethod('stripe')"
                            class="flex items-center justify-center gap-2.5 py-3 rounded-xl border-2 transition-all font-poppins text-sm font-semibold"
                            :class="paymentMethod === 'stripe'
                                ? 'border-cognac-400 bg-cognac-50 text-cognac-700'
                                : 'border-cream-200 text-onyx-500 hover:border-cream-300 hover:bg-cream-50'">
                        <PhCreditCard :size="20" /> Carte bancaire
                    </button>
                    <button @click="switchMethod('paypal')"
                            class="flex items-center justify-center gap-2.5 py-3 rounded-xl border-2 transition-all font-poppins text-sm font-semibold"
                            :class="paymentMethod === 'paypal'
                                ? 'border-blue-400 bg-blue-50 text-blue-700'
                                : 'border-cream-200 text-onyx-500 hover:border-cream-300 hover:bg-cream-50'">
                        PayPal
                    </button>
                </div>
            </div>

            <!-- Stripe -->
            <div v-show="paymentMethod === 'stripe'"
                 class="payment-card bg-white rounded-2xl shadow-card p-5 space-y-5">

                <div class="flex items-center gap-2">
                    <PhCreditCard :size="18" class="text-cognac-500" />
                    <h3 class="font-poppins font-semibold text-onyx-800 text-sm">Paiement par carte</h3>
                </div>

                <!-- Chargement -->
                <div v-if="!stripeReady && !stripeError"
                     class="flex flex-col items-center justify-center py-10 gap-3">
                    <PhSpinnerGap :size="32" class="text-cognac-400 animate-spin" />
                    <p class="text-xs font-poppins text-onyx-400">Chargement du formulaire…</p>
                </div>

                <!-- Erreur init -->
                <div v-if="stripeError"
                     class="flex items-start gap-3 px-4 py-3 rounded-xl"
                     style="background: rgba(239,68,68,0.06); border: 1px solid rgba(239,68,68,0.2);">
                    <PhWarning :size="18" class="text-red-500 shrink-0 mt-0.5" />
                    <div>
                        <p class="text-sm font-poppins font-semibold text-red-700">Erreur de chargement</p>
                        <p class="text-xs font-poppins text-red-600 mt-0.5">{{ stripeError }}</p>
                        <button @click="initStripe"
                                class="mt-2 text-xs font-poppins font-semibold text-cognac-600 underline">
                            Réessayer
                        </button>
                    </div>
                </div>

                <!-- Formulaire Stripe -->
                <div id="stripe-payment-element" :class="stripeReady ? '' : 'hidden'" />

                <!-- Erreur paiement -->
                <div v-if="errorMsg && paymentMethod === 'stripe'"
                     class="flex items-center gap-2 px-4 py-3 rounded-xl text-sm font-poppins"
                     style="background: rgba(239,68,68,0.06); border: 1px solid rgba(239,68,68,0.2);">
                    <PhWarning :size="16" class="text-red-500 shrink-0" />
                    <span class="text-red-600">{{ errorMsg }}</span>
                </div>

                <!-- Bouton payer -->
                <button @click="payWithStripe"
                        :disabled="!stripeReady || processing"
                        class="w-full flex items-center justify-center gap-2.5 py-3.5 rounded-xl font-poppins text-sm font-semibold text-white transition-all hover:opacity-90 hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed disabled:translate-y-0 shadow-md"
                        style="background: linear-gradient(135deg, #c4956a, #b07d52);">
                    <PhSpinnerGap v-if="processing" :size="18" class="animate-spin" />
                    <PhLock v-else :size="18" />
                    {{ processing ? 'Traitement en cours…' : `Payer ${formatPrice(amount)}` }}
                </button>

                <div class="flex items-center justify-center gap-3 text-xs font-poppins text-onyx-300">
                    <span>Cartes acceptées :</span>
                    <span class="px-1.5 py-0.5 border border-cream-200 rounded font-bold">VISA</span>
                    <span class="px-1.5 py-0.5 border border-cream-200 rounded font-bold">MC</span>
                    <span class="px-1.5 py-0.5 border border-cream-200 rounded font-bold">AMEX</span>
                </div>
            </div>

            <!-- PayPal -->
            <div v-show="paymentMethod === 'paypal'"
                 class="payment-card bg-white rounded-2xl shadow-card p-5 space-y-4">
                <h3 class="font-poppins font-semibold text-onyx-800 text-sm">Payer avec PayPal</h3>
                <p class="text-xs font-poppins text-onyx-400 leading-relaxed">
                    Vous serez redirigé vers PayPal pour finaliser votre paiement de
                    <strong class="text-onyx-700">{{ formatPrice(amount) }}</strong>.
                </p>
                <div v-if="paypalLoading" class="flex items-center justify-center py-8 gap-3">
                    <PhSpinnerGap :size="28" class="text-blue-400 animate-spin" />
                    <span class="text-xs font-poppins text-onyx-400">Chargement PayPal…</span>
                </div>
                <div id="paypal-button-container" />
                <div v-if="errorMsg && paymentMethod === 'paypal'"
                     class="flex items-center gap-2 px-4 py-3 rounded-xl text-sm font-poppins"
                     style="background: rgba(239,68,68,0.06); border: 1px solid rgba(239,68,68,0.2);">
                    <PhWarning :size="16" class="text-red-500 shrink-0" />
                    <span class="text-red-600">{{ errorMsg }}</span>
                </div>
            </div>

            <!-- Badges sécurité -->
            <div class="payment-card flex items-center justify-center gap-6 flex-wrap text-xs font-poppins text-onyx-400">
                <span class="flex items-center gap-1.5">
                    <PhShieldCheck :size="14" class="text-emerald-500" /> Chiffrement SSL
                </span>
                <span class="flex items-center gap-1.5">
                    <PhLock :size="14" class="text-emerald-500" /> 3D Secure
                </span>
                <span class="flex items-center gap-1.5">
                    <PhCheckCircle :size="14" class="text-emerald-500" /> Données protégées
                </span>
            </div>

        </div>
    </div>
</template>