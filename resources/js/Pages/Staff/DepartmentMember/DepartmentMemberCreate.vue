<script setup>

import {defineProps} from 'vue';
import AppLayout from "../../../Layouts/AppLayout.vue";
import SiteHeader from "../../../Components/Staff/SiteHeader.vue";
import {Head, useForm} from '@inertiajs/vue3'
import BaseInput from "../../../Components/BaseInput.vue";
import PrimaryButton from "../../../Components/PrimaryButton.vue";

defineOptions({layout: AppLayout})
const props = defineProps({
    department: Object,
})
const form = useForm({
    email: '',
})
</script>

<template>
    <Head title="Add a member"></Head>
    <SiteHeader :title="department.name + ' - Add a member'"></SiteHeader>
    <div>
        <div class="max-w-sm mx-auto mt-12">
            <form action="#" method="post"
                  @submit.prevent="form.post(route('staff.departments.members.store',{department: department.hashid}))">
                <div class="text-sm text-gray-700">
                    <p class="mb-4">This form allows you to add new members to a department. They can be either existing STAFF or new STAFF.</p>
                    <p>Adding them to your department will give them access to all staff apps.</p>
                </div>
                <div class="mb-4">
                    <BaseInput :error="form.errors.email" v-model="form.email" label="E-Mail">test</BaseInput>
                </div>
                <!-- Submit Button -->
                <div class="flex justify-end">
                    <PrimaryButton type="submit" class="btn btn-primary w-full">Add</PrimaryButton>
                </div>
            </form>
        </div>
    </div>
</template>

<style scoped>

</style>
