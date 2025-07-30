<script setup>
import AppLayout from "../../layouts/AppLayout.vue";
import {Head, useForm} from '@inertiajs/vue3'
import {ref} from "vue";
import Card from "@Shared/components/volt/Card.vue";
import InputText from "@Shared/components/volt/InputText.vue";
import Textarea from "@Shared/components/volt/Textarea.vue";
import Calendar from "primevue/calendar";
import MultiSelect from "@Shared/components/volt/MultiSelect.vue";
import Chips from "primevue/chips";
import { Button } from "@/components/ui/button";
import FileUpload from "primevue/fileupload";
import Avatar from "@Shared/components/volt/Avatar.vue";
import Divider from "@Shared/components/volt/Divider.vue";
import InputNumber from "@Shared/components/volt/InputNumber.vue";
import Dropdown from "@Shared/components/volt/Select.vue";
import Message from "@Shared/components/volt/Message.vue";
import {
    UserIcon,
    EnvelopeIcon,
    PhoneIcon,
    MapPinIcon,
    CalendarIcon,
    LanguageIcon,
    IdentificationIcon
} from "@heroicons/vue/24/outline/index.js";

const props = defineProps({
    user: Object,
    countries: Array,
    languages: Array,
})

const form = useForm({
    // Basic Information
    first_name: props.user.first_name || '',
    last_name: props.user.last_name || '',
    nickname: props.user.nickname || '',
    email: props.user.email || '',
    username: props.user.username || '',
    
    // Contact Information
    phone_numbers: props.user.phone_numbers || [],
    telegram_username: props.user.telegram_username || '',
    
    // Address Information
    address_line_1: props.user.address_line_1 || '',
    address_line_2: props.user.address_line_2 || '',
    city: props.user.city || '',
    state_province: props.user.state_province || '',
    postal_code: props.user.postal_code || '',
    country: props.user.country || '',
    
    // Personal Details
    date_of_birth: props.user.date_of_birth ? new Date(props.user.date_of_birth) : null,
    languages: props.user.languages || [],
    
    // EF-specific Information
    credit_as: props.user.credit_as || '',
    joined_ef_year: props.user.joined_ef_year || null,
    first_ef_year: props.user.first_ef_year || '',
    
    // Profile Photo
    profile_photo: null,
});

const languageOptions = [
    { label: 'English', value: 'en' },
    { label: 'German', value: 'de' },
    { label: 'French', value: 'fr' },
    { label: 'Spanish', value: 'es' },
    { label: 'Italian', value: 'it' },
    { label: 'Dutch', value: 'nl' },
    { label: 'Portuguese', value: 'pt' },
    { label: 'Russian', value: 'ru' },
    { label: 'Japanese', value: 'ja' },
    { label: 'Korean', value: 'ko' },
    { label: 'Chinese', value: 'zh' },
];

const countryOptions = [
    { label: 'Germany', value: 'DE' },
    { label: 'United States', value: 'US' },
    { label: 'United Kingdom', value: 'GB' },
    { label: 'Canada', value: 'CA' },
    { label: 'France', value: 'FR' },
    { label: 'Netherlands', value: 'NL' },
    { label: 'Austria', value: 'AT' },
    { label: 'Switzerland', value: 'CH' },
    // Add more countries as needed
];

const currentYear = new Date().getFullYear();
const efYearOptions = Array.from({ length: currentYear - 1992 }, (_, i) => ({
    label: (1993 + i).toString(),
    value: 1993 + i
})).reverse();

const onFileSelect = (event) => {
    form.profile_photo = event.files[0];
};

const submitForm = () => {
    form.post(route('staff.profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            // Handle success
        },
        onError: (errors) => {
            console.error('Form errors:', errors);
        }
    });
};

defineOptions({layout: AppLayout})
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <Head title="Edit Profile"></Head>
        
        <!-- Header Section -->
        <div class="bg-gradient-to-br from-green-600 to-green-800 text-white p-6">
            <div class="max-w-4xl mx-auto">
                <h1 class="text-3xl font-bold mb-2">Edit Profile</h1>
                <p class="text-green-100">Keep your staff directory information up to date</p>
            </div>
        </div>

        <div class="max-w-4xl mx-auto px-6 py-8">
            <form @submit.prevent="submitForm" class="space-y-8">
                
                <!-- Profile Photo Section -->
                <Card>
                    <template #header>
                        <div class="p-6 pb-0">
                            <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                                <UserIcon class="h-5 w-5 mr-2 text-green-600" />
                                Profile Photo
                            </h2>
                        </div>
                    </template>
                    <template #content>
                        <div class="flex items-center space-x-6">
                            <Avatar 
                                :image="user.profile_photo_url" 
                                :label="user.name?.charAt(0)" 
                                shape="circle" 
                                size="xlarge"
                                class="flex-shrink-0"
                            />
                            <div class="flex-1">
                                <FileUpload 
                                    mode="basic" 
                                    name="profile_photo"
                                    accept="image/*" 
                                    :maxFileSize="2000000"
                                    @select="onFileSelect"
                                    choose-label="Choose New Photo"
                                    class="mb-2"
                                />
                                <p class="text-sm text-gray-600">
                                    Upload a professional photo (max 2MB). JPG, PNG, or GIF formats accepted.
                                </p>
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- Basic Information -->
                <Card>
                    <template #header>
                        <div class="p-6 pb-0">
                            <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                                <IdentificationIcon class="h-5 w-5 mr-2 text-green-600" />
                                Basic Information
                            </h2>
                        </div>
                    </template>
                    <template #content>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700">First Name</label>
                                <InputText v-model="form.first_name" class="w-full" />
                                <div v-if="form.errors.first_name" class="text-sm text-red-600">{{ form.errors.first_name }}</div>
                            </div>
                            
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700">Last Name</label>
                                <InputText v-model="form.last_name" class="w-full" />
                                <div v-if="form.errors.last_name" class="text-sm text-red-600">{{ form.errors.last_name }}</div>
                            </div>
                            
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700">Nickname/Display Name</label>
                                <InputText v-model="form.nickname" placeholder="How you'd like to be called" class="w-full" />
                                <div v-if="form.errors.nickname" class="text-sm text-red-600">{{ form.errors.nickname }}</div>
                            </div>
                            
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700">Username</label>
                                <InputText v-model="form.username" disabled class="w-full opacity-50" />
                                <p class="text-xs text-gray-500">Username cannot be changed</p>
                            </div>
                            
                            <div class="space-y-2 md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Email Address</label>
                                <InputText v-model="form.email" type="email" disabled class="w-full opacity-50" />
                                <p class="text-xs text-gray-500">Contact an administrator to change your email address</p>
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- Contact Information -->
                <Card>
                    <template #header>
                        <div class="p-6 pb-0">
                            <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                                <EnvelopeIcon class="h-5 w-5 mr-2 text-green-600" />
                                Contact Information
                            </h2>
                        </div>
                    </template>
                    <template #content>
                        <div class="space-y-6">
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700">Phone Numbers</label>
                                <Chips 
                                    v-model="form.phone_numbers" 
                                    placeholder="Add phone numbers (press Enter to add)"
                                    class="w-full"
                                />
                                <p class="text-xs text-gray-500">Add multiple phone numbers. Press Enter after each number.</p>
                                <div v-if="form.errors.phone_numbers" class="text-sm text-red-600">{{ form.errors.phone_numbers }}</div>
                            </div>
                            
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700">Telegram Username</label>
                                <InputText 
                                    v-model="form.telegram_username" 
                                    placeholder="username (without @)"
                                    class="w-full"
                                />
                                <p class="text-xs text-gray-500">Your Telegram username for easy communication</p>
                                <div v-if="form.errors.telegram_username" class="text-sm text-red-600">{{ form.errors.telegram_username }}</div>
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- Address Information -->
                <Card>
                    <template #header>
                        <div class="p-6 pb-0">
                            <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                                <MapPinIcon class="h-5 w-5 mr-2 text-green-600" />
                                Address Information
                            </h2>
                        </div>
                    </template>
                    <template #content>
                        <div class="space-y-6">
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700">Address Line 1</label>
                                <InputText v-model="form.address_line_1" placeholder="Street address" class="w-full" />
                                <div v-if="form.errors.address_line_1" class="text-sm text-red-600">{{ form.errors.address_line_1 }}</div>
                            </div>
                            
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700">Address Line 2</label>
                                <InputText v-model="form.address_line_2" placeholder="Apartment, suite, etc. (optional)" class="w-full" />
                                <div v-if="form.errors.address_line_2" class="text-sm text-red-600">{{ form.errors.address_line_2 }}</div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-gray-700">City</label>
                                    <InputText v-model="form.city" class="w-full" />
                                    <div v-if="form.errors.city" class="text-sm text-red-600">{{ form.errors.city }}</div>
                                </div>
                                
                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-gray-700">State/Province</label>
                                    <InputText v-model="form.state_province" class="w-full" />
                                    <div v-if="form.errors.state_province" class="text-sm text-red-600">{{ form.errors.state_province }}</div>
                                </div>
                                
                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-gray-700">Postal Code</label>
                                    <InputText v-model="form.postal_code" class="w-full" />
                                    <div v-if="form.errors.postal_code" class="text-sm text-red-600">{{ form.errors.postal_code }}</div>
                                </div>
                            </div>
                            
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700">Country</label>
                                <Dropdown 
                                    v-model="form.country" 
                                    :options="countryOptions"
                                    option-label="label"
                                    option-value="value"
                                    placeholder="Select your country"
                                    class="w-full"
                                    filter
                                />
                                <div v-if="form.errors.country" class="text-sm text-red-600">{{ form.errors.country }}</div>
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- Personal Details -->
                <Card>
                    <template #header>
                        <div class="p-6 pb-0">
                            <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                                <UserIcon class="h-5 w-5 mr-2 text-green-600" />
                                Personal Details
                            </h2>
                        </div>
                    </template>
                    <template #content>
                        <div class="space-y-6">
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700">Date of Birth</label>
                                <Calendar 
                                    v-model="form.date_of_birth" 
                                    date-format="dd/mm/yy"
                                    :show-icon="true"
                                    placeholder="Select your birth date"
                                    class="w-full"
                                />
                                <p class="text-xs text-gray-500">This information is kept private and used for age verification only</p>
                                <div v-if="form.errors.date_of_birth" class="text-sm text-red-600">{{ form.errors.date_of_birth }}</div>
                            </div>
                            
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700">Languages</label>
                                <MultiSelect 
                                    v-model="form.languages" 
                                    :options="languageOptions"
                                    option-label="label"
                                    option-value="value"  
                                    placeholder="Select languages you speak"
                                    class="w-full"
                                    :max-selected-labels="3"
                                />
                                <p class="text-xs text-gray-500">Select all languages you're comfortable communicating in</p>
                                <div v-if="form.errors.languages" class="text-sm text-red-600">{{ form.errors.languages }}</div>
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- EF-Specific Information -->
                <Card>
                    <template #header>
                        <div class="p-6 pb-0">
                            <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                                <CalendarIcon class="h-5 w-5 mr-2 text-green-600" />
                                Eurofurence Information
                            </h2>
                        </div>
                    </template>
                    <template #content>
                        <div class="space-y-6">
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700">Credit As</label>
                                <InputText 
                                    v-model="form.credit_as" 
                                    placeholder="How you'd like to be credited (Team, Department, etc.)"
                                    class="w-full"
                                />
                                <p class="text-xs text-gray-500">How you'd like to appear in credits and official materials</p>
                                <div v-if="form.errors.credit_as" class="text-sm text-red-600">{{ form.errors.credit_as }}</div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-gray-700">Year Joined EF Staff</label>
                                    <Dropdown 
                                        v-model="form.joined_ef_year" 
                                        :options="efYearOptions"
                                        option-label="label"
                                        option-value="value"
                                        placeholder="Select year"
                                        class="w-full"
                                    />
                                    <div v-if="form.errors.joined_ef_year" class="text-sm text-red-600">{{ form.errors.joined_ef_year }}</div>
                                </div>
                                
                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-gray-700">First EF as Staff</label>
                                    <InputText 
                                        v-model="form.first_ef_year" 
                                        placeholder="e.g., EF25"
                                        class="w-full"
                                    />
                                    <div v-if="form.errors.first_ef_year" class="text-sm text-red-600">{{ form.errors.first_ef_year }}</div>
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- Form Actions -->
                <div class="flex justify-end space-x-4 pt-6">
                    <Button 
                        variant="secondary" 
                        @click="$inertia.visit(route('staff.dashboard'))"
                    >
                        Cancel
                    </Button>
                    <Button 
                        type="submit"
                        :disabled="form.processing"
                    >
                        <i v-if="!form.processing" class="pi pi-save mr-2"></i>
                        <span v-if="form.processing" class="animate-spin mr-2">⏳</span>
                        Save Profile
                    </Button>
                </div>
            </form>
            
            <!-- Success Message -->
            <Message 
                v-if="$page.props.flash?.success" 
                severity="success" 
                :closable="false"
                class="mt-4"
            >
                {{ $page.props.flash.success }}
            </Message>
        </div>
    </div>
</template>

<style scoped>
:deep(.p-fileupload-basic .p-button) {
    @apply bg-green-600 border-green-600 hover:bg-green-700;
}
</style>