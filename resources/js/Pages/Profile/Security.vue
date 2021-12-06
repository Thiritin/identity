<template>
    <AppLayout>
        <template #header>
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h1 class="text-3xl font-bold text-white">{{ $trans('update-your-security-settings') }}</h1>
            </div>
        </template>
        <div>
            <WorkInProgressBanner/>
        </div>
    </AppLayout>
</template>

<script>
import AppLayout from "../../Layouts/AppLayout";
import WorkInProgressBanner from "./WorkInProgressBanner";

export default {
    name: "Security",
    components: {WorkInProgressBanner, AppLayout}
}
</script>

