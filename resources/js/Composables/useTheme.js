import { ref, computed, watchEffect } from 'vue'
import { usePage } from '@inertiajs/vue3'

const systemDark = ref(window.matchMedia('(prefers-color-scheme: dark)').matches)

window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
    systemDark.value = e.matches
})

export function useTheme() {
    const page = usePage()

    const theme = computed(() => page.props.user?.preferences?.theme ?? 'system')

    const darkMode = computed(() => {
        if (theme.value === 'dark') return true
        if (theme.value === 'light') return false
        return systemDark.value
    })

    // Sync `.dark` onto <html> so teleported portals (dialogs, popovers,
    // dropdowns) also receive the dark variant.
    watchEffect(() => {
        document.documentElement.classList.toggle('dark', darkMode.value)
    })

    return { darkMode, theme }
}
