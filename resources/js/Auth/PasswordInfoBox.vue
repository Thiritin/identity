<template>
    <div>
        <span
            class="block mb-2 text-xs">{{ $trans('password_requirements_title') }}</span>
        <ul class="text-xs list-disc ml-4">
            <li>{{ $trans('password_requirement_1') }}</li>
            <li>{{ $trans('password_requirement_2') }}</li>
            <li>{{ $trans('password_requirement_3') }}</li>
            <li>{{ $trans('password_requirement_4') }}</li>
        </ul>
    </div>
</template>
<script>
export default {
    name: 'PasswordInfoBox'
}
</script>
