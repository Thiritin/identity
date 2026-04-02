export function useHoneypot() {
    return {
        full_name: '',
        valid_from: new Date().toISOString(),
    }
}
