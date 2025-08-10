<script setup>
import AppLayout from "../../layouts/AppLayout.vue";
import {Head, router} from '@inertiajs/vue3'
import {ref, computed, onMounted} from "vue";
import Card from "@Shared/components/volt/Card.vue";
import DataTable from "@Shared/components/volt/DataTable.vue";
import Column from "primevue/column";
import { Button } from "@/components/ui/button";
import Badge from "@Shared/components/volt/Badge.vue";
import Avatar from "@Shared/components/volt/Avatar.vue";
import { Input } from "@/components/ui/input";
import Dropdown from "@Shared/components/volt/Select.vue";
import Dialog from "@Shared/components/volt/Dialog.vue";
import {
    Search as MagnifyingGlassIcon,
    User as UserIcon,
    Mail as EnvelopeIcon,
    Phone as PhoneIcon,
    MapPin as MapPinIcon,
    Calendar as CalendarIcon,
    Languages as LanguageIcon,
    MessageCircle as TelegramIcon
} from "lucide-vue-next";

const props = defineProps({
    users: Array,
    filters: Object,
})

const searchTerm = ref(props.filters?.search || '');
const selectedDepartment = ref(props.filters?.department || null);
const selectedRole = ref(props.filters?.role || null);
const selectedUser = ref(null);
const showUserDialog = ref(false);

const departmentOptions = [
    { label: 'All Departments', value: null },
    { label: 'Art Show', value: 'art_show' },
    { label: 'Registration', value: 'registration' },
    { label: 'Security', value: 'security' },
    { label: 'IT', value: 'it' },
];

const roleOptions = [
    { label: 'All Roles', value: null },
    { label: 'Director', value: 'director' },
    { label: 'Division Director', value: 'division_director' },
    { label: 'Team Lead', value: 'team_lead' },
    { label: 'Staff Member', value: 'staff' },
];

// Debounced search function
let searchTimeout;
const performSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('staff.users.index'), {
            search: searchTerm.value,
            department: selectedDepartment.value,
            role: selectedRole.value,
        }, {
            preserveState: true,
            replace: true,
        });
    }, 300);
};

const filteredUsers = computed(() => {
    return props.users || [];
});

const viewUserProfile = (user) => {
    selectedUser.value = user;
    showUserDialog.value = true;
};

const getContactMethods = (user) => {
    const methods = [];
    if (user.email) methods.push({ type: 'email', value: user.email, icon: EnvelopeIcon });
    if (user.phone_numbers?.length) {
        user.phone_numbers.forEach(phone => {
            methods.push({ type: 'phone', value: phone, icon: PhoneIcon });
        });
    }
    if (user.telegram_username) {
        methods.push({ type: 'telegram', value: `@${user.telegram_username}`, icon: TelegramIcon });
    }
    return methods;
};

defineOptions({layout: AppLayout})
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <Head title="Staff Directory"></Head>
        
        <!-- Header Section -->
        <div class="bg-gradient-to-br from-green-600 to-green-800 text-white p-6">
            <div class="max-w-7xl mx-auto">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div>
                        <h1 class="text-3xl font-bold mb-2">Staff Directory</h1>
                        <p class="text-green-100">Find and connect with Eurofurence staff members</p>
                    </div>
                    
                    <!-- Search and Filter Controls -->
                    <div class="flex flex-col sm:flex-row gap-4 lg:min-w-96">
                        <div class="relative flex-1">
                            <MagnifyingGlassIcon class="h-5 w-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-green-200" />
                            <Input
                                v-model="searchTerm"
                                @input="performSearch"
                                placeholder="Search by name, email, or department..."
                                class="w-full pl-10 bg-green-700 border-green-600 text-white placeholder-green-200 focus:ring-green-400 focus:border-green-400"
                            />
                        </div>
                        <Dropdown
                            v-model="selectedDepartment"
                            @change="performSearch"
                            :options="departmentOptions"
                            option-label="label"
                            option-value="value"
                            placeholder="Department"
                            class="w-full sm:w-48 bg-green-700 border-green-600 text-white"
                        />
                        <Dropdown
                            v-model="selectedRole"
                            @change="performSearch"
                            :options="roleOptions"
                            option-label="label"
                            option-value="value"
                            placeholder="Role"
                            class="w-full sm:w-48 bg-green-700 border-green-600 text-white"
                        />
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-6 py-8">
            <Card>
                <template #content>
                    <DataTable 
                        :value="filteredUsers"
                        paginator 
                        :rows="20"
                        :rows-per-page-options="[10, 20, 50]"
                        responsive-layout="scroll"
                        class="p-datatable-sm"
                        :loading="false"
                    >
                        <Column field="profile" header="Profile" style="min-width: 250px">
                            <template #body="slotProps">
                                <div class="flex items-center space-x-3">
                                    <Avatar 
                                        :image="slotProps.data.profile_photo_url" 
                                        :label="slotProps.data.name.charAt(0)" 
                                        shape="circle" 
                                        size="large"
                                        class="flex-shrink-0"
                                    />
                                    <div class="min-w-0 flex-1">
                                        <div class="font-semibold text-gray-900 truncate">{{ slotProps.data.name }}</div>
                                        <div class="text-sm text-gray-600 truncate">{{ slotProps.data.email }}</div>
                                        <div class="text-sm text-gray-500" v-if="slotProps.data.username">
                                            @{{ slotProps.data.username }}
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </Column>
                        
                        <Column field="departments" header="Departments" style="min-width: 200px">
                            <template #body="slotProps">
                                <div class="space-y-1">
                                    <div 
                                        v-for="dept in slotProps.data.departments?.slice(0, 2)" 
                                        :key="dept.id"
                                        class="flex items-center justify-between"
                                    >
                                        <span class="text-sm text-gray-700 truncate">{{ dept.name }}</span>
                                        <Badge :value="dept.pivot.level" severity="info" class="ml-2 flex-shrink-0" />
                                    </div>
                                    <div v-if="(slotProps.data.departments?.length || 0) > 2" class="text-xs text-gray-500">
                                        +{{ slotProps.data.departments.length - 2 }} more
                                    </div>
                                </div>
                            </template>
                        </Column>
                        
                        <Column field="rank" header="Rank" style="min-width: 120px">
                            <template #body="slotProps">
                                <Badge 
                                    :value="slotProps.data.rank || 'Staff'" 
                                    :severity="slotProps.data.rank === 'Director' ? 'success' : 'secondary'" 
                                />
                            </template>
                        </Column>
                        
                        <Column field="location" header="Location" style="min-width: 150px">
                            <template #body="slotProps">
                                <div v-if="slotProps.data.city || slotProps.data.country" class="text-sm text-gray-600">
                                    <div class="flex items-center">
                                        <MapPinIcon class="h-4 w-4 mr-1 text-gray-400" />
                                        <span>{{ [slotProps.data.city, slotProps.data.country].filter(Boolean).join(', ') }}</span>
                                    </div>
                                </div>
                                <span v-else class="text-gray-400 text-sm">Not specified</span>
                            </template>
                        </Column>
                        
                        <Column field="contact" header="Contact" style="min-width: 150px">
                            <template #body="slotProps">
                                <div class="flex space-x-2">
                                    <Button 
                                        v-if="slotProps.data.email"
                                        size="sm" 
                                        variant="ghost"
                                        :title="`Email ${slotProps.data.name}`"
                                        @click="window.location.href = `mailto:${slotProps.data.email}`"
                                    >
                                        <EnvelopeIcon class="w-4 h-4" />
                                    </Button>
                                    <Button 
                                        v-if="slotProps.data.telegram_username"
                                        size="sm" 
                                        variant="ghost"
                                        :title="`Telegram @${slotProps.data.telegram_username}`"
                                        @click="window.open(`https://t.me/${slotProps.data.telegram_username}`, '_blank')"
                                        class="text-blue-600 hover:text-blue-700"
                                    >
                                        <TelegramIcon class="w-4 h-4" />
                                    </Button>
                                </div>
                            </template>
                        </Column>
                        
                        <Column header="Actions" style="min-width: 100px">
                            <template #body="slotProps">
                                <Button 
                                    size="sm" 
                                    variant="secondary" 
                                    @click="viewUserProfile(slotProps.data)"
                                >
                                    <UserIcon class="w-4 h-4 mr-2" />
                                    View Profile
                                </Button>
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </Card>
            
            <!-- Empty State -->
            <div v-if="filteredUsers.length === 0" class="text-center py-12">
                <UserIcon class="h-16 w-16 text-gray-400 mx-auto mb-4" />
                <h3 class="text-lg font-medium text-gray-900 mb-2">No staff members found</h3>
                <p class="text-gray-500">
                    {{ searchTerm ? 'Try adjusting your search terms or filters' : 'No staff members are currently available in the directory' }}
                </p>
            </div>
        </div>

        <!-- User Profile Dialog -->
        <Dialog 
            v-model:visible="showUserDialog" 
            :header="`${selectedUser?.name} - Profile`"
            modal 
            :style="{ width: '50rem' }"
            :breakpoints="{ '1199px': '75vw', '575px': '90vw' }"
        >
            <div v-if="selectedUser" class="space-y-6">
                <!-- Profile Header -->
                <div class="flex items-center space-x-4 pb-6 border-b border-gray-200">
                    <Avatar 
                        :image="selectedUser.profile_photo_url" 
                        :label="selectedUser.name.charAt(0)" 
                        shape="circle" 
                        size="xlarge"
                    />
                    <div>
                        <h3 class="text-2xl font-bold text-gray-900">{{ selectedUser.name }}</h3>
                        <p class="text-gray-600">{{ selectedUser.email }}</p>
                        <p class="text-gray-500" v-if="selectedUser.username">@{{ selectedUser.username }}</p>
                        <Badge 
                            :value="selectedUser.rank || 'Staff'" 
                            :severity="selectedUser.rank === 'Director' ? 'success' : 'secondary'" 
                            class="mt-2"
                        />
                    </div>
                </div>

                <!-- Contact Information -->
                <div>
                    <h4 class="text-lg font-semibold text-gray-900 mb-3 flex items-center">
                        <EnvelopeIcon class="h-5 w-5 mr-2 text-green-600" />
                        Contact Information
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div v-for="contact in getContactMethods(selectedUser)" :key="`${contact.type}-${contact.value}`">
                            <div class="flex items-center space-x-2 p-3 bg-gray-50 rounded-lg">
                                <component :is="contact.icon" v-if="typeof contact.icon !== 'string'" class="h-4 w-4 text-gray-600" />
                                <i v-else :class="contact.icon + ' text-gray-600'"></i>
                                <span class="text-sm text-gray-700">{{ contact.value }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Departments & Roles -->
                <div v-if="selectedUser.departments?.length">
                    <h4 class="text-lg font-semibold text-gray-900 mb-3 flex items-center">
                        <UserIcon class="h-5 w-5 mr-2 text-green-600" />
                        Departments & Roles
                    </h4>
                    <div class="space-y-3">
                        <div 
                            v-for="dept in selectedUser.departments" 
                            :key="dept.id"
                            class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                        >
                            <div>
                                <div class="font-medium text-gray-900">{{ dept.name }}</div>
                                <div class="text-sm text-gray-600">{{ dept.type }}</div>
                            </div>
                            <div class="text-right">
                                <Badge :value="dept.pivot.level" severity="info" />
                                <div v-if="dept.pivot.title" class="text-sm text-gray-600 mt-1">
                                    {{ dept.pivot.title }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Personal Information -->
                <div v-if="selectedUser.city || selectedUser.languages?.length || selectedUser.joined_ef_year">
                    <h4 class="text-lg font-semibold text-gray-900 mb-3 flex items-center">
                        <UserIcon class="h-5 w-5 mr-2 text-green-600" />
                        Personal Information
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div v-if="selectedUser.city || selectedUser.country" class="flex items-center space-x-2 p-3 bg-gray-50 rounded-lg">
                            <MapPinIcon class="h-4 w-4 text-gray-600" />
                            <span class="text-sm text-gray-700">
                                {{ [selectedUser.city, selectedUser.country].filter(Boolean).join(', ') }}
                            </span>
                        </div>
                        <div v-if="selectedUser.joined_ef_year" class="flex items-center space-x-2 p-3 bg-gray-50 rounded-lg">
                            <CalendarIcon class="h-4 w-4 text-gray-600" />
                            <span class="text-sm text-gray-700">Joined EF {{ selectedUser.joined_ef_year }}</span>
                        </div>
                        <div v-if="selectedUser.languages?.length" class="flex items-center space-x-2 p-3 bg-gray-50 rounded-lg md:col-span-2">
                            <LanguageIcon class="h-4 w-4 text-gray-600" />
                            <span class="text-sm text-gray-700">
                                Languages: {{ selectedUser.languages.join(', ') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </Dialog>
    </div>
</template>

<style scoped>
:deep(.p-dropdown.p-component) {
    background-color: rgb(21 128 61);
    border-color: rgb(22 101 52);
}

:deep(.p-dropdown .p-dropdown-label) {
    color: white;
}

:deep(.p-dropdown .p-dropdown-trigger) {
    color: rgb(187 247 208);
}
</style>