<template>
    <div>
        <span
            class="block mb-2 text-xs">{{ $trans('password_requirements_title') }}</span>
        <ul class="text-xs list-disc ml-4">
            <li :class="{'text-red-700 font-semibold': !correctLength}">{{ $trans('password_requirement_1') }}</li>
            <li :class="{'text-red-700 font-semibold': !correctLowerUpper}">{{ $trans('password_requirement_2') }}</li>
            <li :class="{'text-red-700 font-semibold': !correctNumber}">{{ $trans('password_requirement_3') }}</li>
            <li :class="{'text-red-700 font-semibold': !correctSymbol}">{{ $trans('password_requirement_4') }}</li>
        </ul>
    </div>
</template>
<script>
export default {
    name: 'PasswordInfoBox',
    props: {
        correctLength: Boolean,
        correctLowerUpper: Boolean,
        correctNumber: Boolean,
        correctSymbol: Boolean
    }
}
</script>
