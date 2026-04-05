import { ref, watch } from 'vue'
import { toast } from 'vue-sonner'
import { trans } from 'laravel-vue-i18n'

const STORAGE_KEY = 'devMode:enabled'
const CLICK_RESET_MS = 1500
const UNLOCK_CLICKS = 5

function readStorage() {
    try {
        return window.sessionStorage.getItem(STORAGE_KEY) === '1'
    } catch {
        return false
    }
}

function writeStorage(value) {
    try {
        if (value) {
            window.sessionStorage.setItem(STORAGE_KEY, '1')
        } else {
            window.sessionStorage.removeItem(STORAGE_KEY)
        }
    } catch {
        // Private mode / disabled storage: fall back to in-memory only.
    }
}

// Module-scope state: singleton across all importers.
const enabled = ref(readStorage())
let clickCount = 0
let resetTimer = null

watch(enabled, (v) => writeStorage(v))

function clearTimer() {
    if (resetTimer) {
        clearTimeout(resetTimer)
        resetTimer = null
    }
}

function registerClick() {
    if (enabled.value) {
        enabled.value = false
        clickCount = 0
        clearTimer()
        toast.success(trans('devmode_disabled'))
        return
    }

    clickCount += 1
    clearTimer()

    if (clickCount >= UNLOCK_CLICKS) {
        clickCount = 0
        enabled.value = true
        toast.success(trans('devmode_enabled'))
        return
    }

    resetTimer = setTimeout(() => {
        clickCount = 0
        resetTimer = null
    }, CLICK_RESET_MS)
}

function disable() {
    if (enabled.value) {
        enabled.value = false
    }
    clickCount = 0
    clearTimer()
}

export function useDevMode() {
    return { enabled, registerClick, disable }
}
