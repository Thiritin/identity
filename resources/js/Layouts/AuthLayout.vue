<template>
    <div :class="{ dark: darkMode }">
        <SkipToContent />
        <div class="bg-primary-600 auth-background relative min-h-screen dark:text-primary-300">
            <!-- Centered Content -->
            <div class="relative z-10 flex min-h-screen items-center justify-center px-4 py-8">
                <div class="flex flex-col items-center w-full max-w-md">
                    <div ref="card" class="auth-card w-full max-w-md rounded-xl rounded-b-none bg-white/90 px-6 py-8 shadow-2xl backdrop-blur-sm dark:bg-primary-900/90 sm:px-10 sm:py-10">
                        <!-- Slot Content -->
                        <main id="main-content" class="flex-1 w-full flex flex-col justify-center">
                            <Transition
                                name="page"
                                mode="out-in"
                                @before-leave="onBeforeLeave"
                                @leave="onLeave"
                                @enter="onEnter"
                                @after-enter="onAfterEnter"
                            >
                                <div :key="$page.url">
                                    <slot name="header">
                                        <AuthHeader v-if="!$page.props.hideUserInfo && user" class="mb-8"></AuthHeader>
                                    </slot>
                                    <slot></slot>
                                </div>
                            </Transition>
                        </main>
                    </div>
                    <!-- Footer: artwork left, legal right -->
                    <div class="w-full max-w-md flex items-center justify-between bg-black/40 backdrop-blur-sm rounded-b-xl px-4 py-2 text-xs text-white/70">
                        <div>
                            {{ $t('footer_artwork_by') }}
                            <a class="hover:underline" href="https://www.furaffinity.net/user/jukajo">Jukajo</a>
                        </div>
                        <nav aria-label="Legal" class="flex flex-wrap gap-x-4 gap-y-1">
                            <a v-for="item in visibleNavigation" :key="item.name" :href="item.href" target="_blank" class="hover:text-white transition-colors">
                                {{ $t(item.name) }}
                            </a>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
import { ref, computed } from 'vue'
import { usePage } from "@inertiajs/vue3"
import { useTheme } from '@/Composables/useTheme'
import SkipToContent from '@/Components/SkipToContent.vue'

const { darkMode } = useTheme()
const user = usePage().props.user
const card = ref(null)

const navigation = [
    { name: 'footer_support', href: 'https://help.eurofurence.org/contact/' },
    { name: 'footer_legal_notice', href: 'https://help.eurofurence.org/legal/imprint' },
    { name: 'footer_privacy_policy', href: 'https://help.eurofurence.org/legal/privacy' },
]

const visibleNavigation = computed(() => navigation)
let previousHeight = 0

function onBeforeLeave() {
    if (card.value) {
        previousHeight = card.value.offsetHeight
        card.value.style.height = previousHeight + 'px'
    }
}

function onLeave(el, done) {
    // Keep card locked at previous height during leave
    el.addEventListener('transitionend', done, { once: true })
}

function onEnter(el, done) {
    if (card.value) {
        // New content is mounted but invisible (opacity 0)
        // Temporarily remove fixed height to measure natural size
        requestAnimationFrame(() => {
            card.value.style.height = 'auto'
            const newHeight = card.value.offsetHeight
            // Snap back to previous height
            card.value.style.height = previousHeight + 'px'
            // Force reflow
            card.value.offsetHeight
            // Animate to new height
            card.value.style.height = newHeight + 'px'

            // Wait for both the content fade and card height to finish
            el.addEventListener('transitionend', done, { once: true })
        })
    } else {
        done()
    }
}

function onAfterEnter() {
    if (card.value) {
        card.value.style.height = ''
    }
}
</script>
<script>
import AuthHeader from "../Components/Auth/AuthHeader.vue";

export default {
    components: {AuthHeader},
}
</script>
<style>

.auth-card {
    transition: height 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
}

.page-enter-active {
    transition: opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.page-leave-active {
    transition: opacity 0.15s cubic-bezier(0.4, 0, 0.2, 1);
}

.page-enter-from {
    opacity: 0;
}

.page-leave-to {
    opacity: 0;
}

.auth-background {
    background-repeat: no-repeat;
    background-size: cover;
    background-image: url('../../assets/fantastic_furry_festival.jpg');
}

</style>
