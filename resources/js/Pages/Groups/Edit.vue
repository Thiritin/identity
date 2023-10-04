<template>
    <AppLayout :title="$trans('edit_group') + ' - ' + transProp(group.name)">
        <div>
            <GroupHorizontalBox :can-see-settings="false" :group="group"/>
            <Card>
                <CardBody>
                    <form class="space-y-8 divide-y divide-gray-200" @submit.prevent="submit">
                        <div class="space-y-8 divide-y divide-gray-200 sm:space-y-5">
                            <div class="space-y-6 sm:space-y-5">
                                <div>
                                    <SettingsHeader>Profile</SettingsHeader>
                                    <SettingsSubHeader>This information will be displayed publicly so be careful what
                                        you share.
                                    </SettingsSubHeader>
                                </div>

                                <div class="space-y-6 sm:space-y-5">
                                    <BaseField v-model="form.logo"
                                               :error="form.errors.logo"
                                               label="Cover"
                                               type="file"
                                    ></BaseField>
                                    <BaseField v-model="form.name"
                                               :error="form.errors.name"
                                               label="Name"
                                               language="en"
                                               type="text"
                                    ></BaseField>
                                    <BaseField v-model="form.description"
                                               :error="form.errors.description"
                                               label="Description"
                                               language="en"
                                               rows="8"
                                               type="textarea"
                                    ></BaseField>
                                    <PrimaryButton class="w-full" type="submit">Save</PrimaryButton>
                                </div>
                            </div>
                        </div>
                    </form>
                </CardBody>
            </Card>
        </div>
    </AppLayout>
</template>

<script>
import AppLayout from '@/Layouts/AppLayout.vue'
import Card from '@/Components/Card.vue'
import AvatarImage from "@/Pages/Profile/AvatarImage.vue";
import UserLevelBadge from "@/Pages/Groups/UserTitleBadge.vue";
import GroupHorizontalBox from "@/Components/GroupHorizontalBox.vue";
import CardBody from "@/Components/CardBody.vue";
import SettingsHeader from "@/Components/Settings/SettingsHeader.vue";
import SettingsSubHeader from "@/Components/Settings/SettingsSubHeader.vue";
import TwoColumnInputLayout from "@/Components/Form/Layouts/TwoColumnInput.vue";
import BaseLabel from "@/Components/Form/BaseLabel.vue";
import BaseInput from "@/Components/BaseInput.vue";
import BaseField from "@/Components/Form/BaseField.vue";
import {useForm} from "@inertiajs/vue3";
import PrimaryButton from "@/Components/PrimaryButton.vue";

export default {
    name: 'View',
    props: {
        group: Object
    },
    components: {
        PrimaryButton,
        BaseField,
        BaseInput,
        BaseLabel,
        TwoColumnInputLayout,
        SettingsSubHeader,
        SettingsHeader, CardBody, GroupHorizontalBox, UserLevelBadge, AvatarImage, Card, AppLayout
    },
    data() {
        return {
            language: "en",
            form: useForm({
                name: this.group.name,
                description: this.group.description,
                logo: null
            })
        }
    },
    methods: {
        submit() {
            this.form.put(route('groups.update', this.group.hashid), {
                preserveScroll: true,
                onSuccess: () => {
                    this.$inertia.reload()
                }
            })
        }
    }
}
</script>

