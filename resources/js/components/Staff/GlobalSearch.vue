<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import InputText from 'primevue/inputtext';
import OverlayPanel from 'primevue/overlaypanel';
import Avatar from 'primevue/avatar';
import Badge from 'primevue/badge';
import { 
    MagnifyingGlassIcon, 
    UserIcon, 
    UserGroupIcon,
    BuildingOfficeIcon,
    CommandLineIcon 
} from '@heroicons/vue/24/outline';

const searchQuery = ref('');
const searchResults = ref([]);
const isLoading = ref(false);
const overlayPanel = ref();
const searchInput = ref();
const showResults = ref(false);

let searchTimeout;

const performSearch = async () => {
    if (!searchQuery.value.trim() || searchQuery.value.length < 2) {
        searchResults.value = [];
        showResults.value = false;
        return;
    }

    isLoading.value = true;
    
    try {
        const response = await fetch(route('staff.search'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            body: JSON.stringify({ query: searchQuery.value })
        });
        
        const data = await response.json();
        searchResults.value = data.results || [];
        showResults.value = true;
        
        if (overlayPanel.value && searchResults.value.length > 0) {
            overlayPanel.value.show(null, searchInput.value);
        }
    } catch (error) {
        console.error('Search error:', error);
    } finally {
        isLoading.value = false;
    }
};

const debouncedSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(performSearch, 300);
};

watch(searchQuery, debouncedSearch);

const groupedResults = computed(() => {
    const groups = {
        users: [],
        groups: [],
        departments: []
    };
    
    searchResults.value.forEach(result => {
        if (result.type === 'user') {
            groups.users.push(result);
        } else if (result.type === 'group') {
            groups.groups.push(result);
        } else if (result.type === 'department') {
            groups.departments.push(result);
        }
    });
    
    return groups;
});

const selectResult = (result) => {
    if (result.type === 'user') {
        router.visit(route('staff.users.show', { user: result.id }));
    } else if (result.type === 'group' || result.type === 'department') {
        router.visit(route('staff.groups.show', { group: result.hashid }));
    }
    
    overlayPanel.value?.hide();
    searchQuery.value = '';
};

const navigateToFullSearch = () => {
    router.visit(route('staff.users.index', { search: searchQuery.value }));
    overlayPanel.value?.hide();
    searchQuery.value = '';
};

// Keyboard shortcuts
const handleKeydown = (event) => {
    // Cmd/Ctrl + K to focus search
    if ((event.metaKey || event.ctrlKey) && event.key === 'k') {
        event.preventDefault();
        searchInput.value?.focus();
    }
    
    // Escape to close search results
    if (event.key === 'Escape' && showResults.value) {
        overlayPanel.value?.hide();
        searchInput.value?.blur();
    }
};

onMounted(() => {
    document.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeydown);
    clearTimeout(searchTimeout);
});

const hideResults = () => {
    setTimeout(() => {
        showResults.value = false;
    }, 200);
};

const getResultIcon = (type) => {
    switch (type) {
        case 'user': return UserIcon;
        case 'group': return UserGroupIcon;
        case 'department': return BuildingOfficeIcon;
        default: return MagnifyingGlassIcon;
    }
};
</script>

<template>
    <div class="relative">
        <div class="relative">
            <MagnifyingGlassIcon class="h-4 w-4 absolute left-3 top-1/2 transform -translate-y-1/2 text-green-200" />
            <InputText
                ref="searchInput"
                v-model="searchQuery"
                placeholder="Search staff, groups... (⌘K)"
                class="w-full pl-10 pr-4 py-2 bg-green-700 border-green-600 text-white placeholder-green-200 focus:ring-green-400 focus:border-green-400 text-sm"
                @focus="showResults && overlayPanel.show(null, searchInput)"
                @blur="hideResults"
            />
            <div v-if="isLoading" class="absolute right-3 top-1/2 transform -translate-y-1/2">
                <i class="pi pi-spinner pi-spin text-green-200"></i>
            </div>
        </div>

        <OverlayPanel ref="overlayPanel" class="w-96 max-w-sm">
            <div v-if="searchResults.length > 0" class="space-y-4">
                
                <!-- Users Section -->
                <div v-if="groupedResults.users.length > 0">
                    <h4 class="text-xs font-semibold text-gray-600 uppercase tracking-wide mb-2 flex items-center">
                        <UserIcon class="h-3 w-3 mr-1" />
                        Staff Members
                    </h4>
                    <div class="space-y-1">
                        <div 
                            v-for="user in groupedResults.users.slice(0, 3)" 
                            :key="`user-${user.id}`"
                            @click="selectResult(user)"
                            class="flex items-center space-x-3 p-2 hover:bg-gray-50 rounded-lg cursor-pointer transition-colors"
                        >
                            <Avatar 
                                :image="user.profile_photo_url" 
                                :label="user.name.charAt(0)" 
                                size="small" 
                                shape="circle"
                            />
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-gray-900 truncate">{{ user.name }}</div>
                                <div class="text-xs text-gray-500 truncate">{{ user.email }}</div>
                            </div>
                            <Badge v-if="user.rank" :value="user.rank" severity="secondary" />
                        </div>
                    </div>
                </div>

                <!-- Groups Section -->
                <div v-if="groupedResults.groups.length > 0">
                    <h4 class="text-xs font-semibold text-gray-600 uppercase tracking-wide mb-2 flex items-center">
                        <UserGroupIcon class="h-3 w-3 mr-1" />
                        Groups
                    </h4>
                    <div class="space-y-1">
                        <div 
                            v-for="group in groupedResults.groups.slice(0, 3)" 
                            :key="`group-${group.id}`"
                            @click="selectResult(group)"
                            class="flex items-center space-x-3 p-2 hover:bg-gray-50 rounded-lg cursor-pointer transition-colors"
                        >
                            <Avatar 
                                :image="group.logo_url" 
                                :label="group.name.charAt(0)" 
                                size="small" 
                                shape="circle"
                                class="bg-green-100 text-green-600"
                            />
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-gray-900 truncate">{{ group.name }}</div>
                                <div class="text-xs text-gray-500 truncate">{{ group.type }}</div>
                            </div>
                            <Badge :value="`${group.members_count} members`" severity="info" />
                        </div>
                    </div>
                </div>

                <!-- Departments Section -->
                <div v-if="groupedResults.departments.length > 0">
                    <h4 class="text-xs font-semibold text-gray-600 uppercase tracking-wide mb-2 flex items-center">
                        <BuildingOfficeIcon class="h-3 w-3 mr-1" />
                        Departments
                    </h4>
                    <div class="space-y-1">
                        <div 
                            v-for="dept in groupedResults.departments.slice(0, 3)" 
                            :key="`dept-${dept.id}`"
                            @click="selectResult(dept)"
                            class="flex items-center space-x-3 p-2 hover:bg-gray-50 rounded-lg cursor-pointer transition-colors"
                        >
                            <Avatar 
                                :image="dept.logo_url" 
                                :label="dept.name.charAt(0)" 
                                size="small" 
                                shape="circle"
                                class="bg-blue-100 text-blue-600"
                            />
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-gray-900 truncate">{{ dept.name }}</div>
                                <div class="text-xs text-gray-500 truncate">{{ dept.description || 'Department' }}</div>
                            </div>
                            <Badge :value="`${dept.members_count} members`" severity="info" />
                        </div>
                    </div>
                </div>

                <!-- View All Results -->
                <div class="pt-3 border-t border-gray-200">
                    <button 
                        @click="navigateToFullSearch"
                        class="w-full flex items-center justify-center space-x-2 p-2 text-sm text-green-600 hover:text-green-700 hover:bg-green-50 rounded-lg transition-colors"
                    >
                        <MagnifyingGlassIcon class="h-4 w-4" />
                        <span>View all results for "{{ searchQuery }}"</span>
                    </button>
                </div>
            </div>

            <div v-else-if="searchQuery.length >= 2 && !isLoading" class="text-center py-6">
                <MagnifyingGlassIcon class="h-8 w-8 text-gray-400 mx-auto mb-2" />
                <p class="text-sm text-gray-500">No results found for "{{ searchQuery }}"</p>
                <button 
                    @click="navigateToFullSearch"
                    class="mt-2 text-xs text-green-600 hover:text-green-700"
                >
                    Try the full search
                </button>
            </div>

            <div v-else-if="searchQuery.length < 2" class="text-center py-6">
                <div class="space-y-2">
                    <CommandLineIcon class="h-6 w-6 text-gray-400 mx-auto" />
                    <p class="text-xs text-gray-500">Type to search staff, groups, and departments</p>
                    <p class="text-xs text-gray-400">Press ⌘K to focus search</p>
                </div>
            </div>
        </OverlayPanel>
    </div>
</template>

<style scoped>
:deep(.p-overlaypanel .p-overlaypanel-content) {
    padding: 1rem;
}

:deep(.p-inputtext) {
    font-size: 0.875rem;
}
</style>