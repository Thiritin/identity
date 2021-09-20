<template>
    <div class="text-gray-800">
        <span class="block mb-2 text-sm">Your password must meet the following requirements</span>
        <ul class="text-sm list-disc ml-4">
            <li>Minimum of 8 characters</li>
            <li>Must contain lowercase and uppercase letter</li>
            <li>Must contain numbers</li>
            <li>Must not been listed with <a ref="nofollow" href="https://haveibeenpwned.com/Passwords" target="_blank">"have
                i been pwned?"</a></li>
        </ul>
    </div>
</template>
<script>
export default {
    name: 'PasswordInfoBox'
}
</script>
