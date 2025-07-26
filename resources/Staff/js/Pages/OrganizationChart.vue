<template>
  <div class="organization-chart-container">
    <div class="mb-6">
      <h1 class="text-3xl font-bold text-gray-900 mb-2">Organization Chart</h1>
      <p class="text-gray-600">
        View the organizational structure of Eurofurence
      </p>
      <div class="mt-4 flex gap-4">
        <button
          @click="toggleExpanded"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
        >
          {{ isExpanded ? 'Show Simplified' : 'Show Teams' }}
        </button>
        <button
          @click="refreshData"
          class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors"
        >
          Refresh
        </button>
      </div>
    </div>

    <div class="organization-chart-wrapper bg-white rounded-lg shadow-lg p-6">
      <OrganizationChart
        v-if="chartData"
        :value="chartData"
        :collapsible="true"
        selectionMode="single"
        @node-select="onNodeSelect"
        class="organization-chart"
      >
        <template #default="slotProps">
          <div class="org-node" :class="`org-node-${slotProps.node.type}`">
            <!-- Node Header -->
            <div class="org-node-header">
              <div class="org-node-title">
                {{ slotProps.node.label }}
              </div>
              <div class="org-node-type">
                {{ slotProps.node.data.type }}
              </div>
            </div>

            <!-- Logo if available -->
            <div v-if="slotProps.node.data.logo_url" class="org-node-logo">
              <img 
                :src="slotProps.node.data.logo_url" 
                :alt="slotProps.node.label"
                class="w-12 h-12 rounded-full object-cover mx-auto"
              />
            </div>

            <!-- Leadership -->
            <div v-if="slotProps.node.data.leadership && slotProps.node.data.leadership.length > 0" 
                 class="org-node-leadership">
              <div class="leadership-title">Leadership</div>
              <div class="leadership-list">
                <div 
                  v-for="leader in slotProps.node.data.leadership.slice(0, 3)"
                  :key="leader.id"
                  class="leader-item"
                >
                  <img
                    v-if="leader.avatar_url"
                    :src="leader.avatar_url"
                    :alt="leader.name"
                    class="w-8 h-8 rounded-full object-cover"
                  />
                  <div v-else class="w-8 h-8 rounded-full bg-gray-300 flex items-center justify-center text-xs font-semibold">
                    {{ leader.name.charAt(0) }}
                  </div>
                  <div class="leader-info">
                    <div class="leader-name">{{ leader.name }}</div>
                    <div class="leader-role">{{ leader.role }}</div>
                  </div>
                </div>
                <div v-if="slotProps.node.data.leadership.length > 3" class="text-xs text-gray-500 mt-1">
                  +{{ slotProps.node.data.leadership.length - 3 }} more
                </div>
              </div>
            </div>

            <!-- Description -->
            <div v-if="slotProps.node.data.description" class="org-node-description">
              {{ slotProps.node.data.description }}
            </div>
          </div>
        </template>
      </OrganizationChart>
      
      <div v-else class="flex items-center justify-center h-64">
        <div class="text-gray-500">Loading organization chart...</div>
      </div>
    </div>

    <!-- Node Details Modal -->
    <div v-if="selectedNode" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg max-w-2xl w-full mx-4 max-h-[80vh] overflow-y-auto">
        <div class="p-6">
          <div class="flex justify-between items-start mb-4">
            <h2 class="text-2xl font-bold">{{ selectedNode.label }}</h2>
            <button @click="selectedNode = null" class="text-gray-500 hover:text-gray-700">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>
          
          <div class="space-y-4">
            <div>
              <strong>Type:</strong> {{ selectedNode.data.type }}
            </div>
            <div v-if="selectedNode.data.hierarchy_path">
              <strong>Path:</strong> {{ selectedNode.data.hierarchy_path }}
            </div>
            <div v-if="selectedNode.data.description">
              <strong>Description:</strong> {{ selectedNode.data.description }}
            </div>
            
            <!-- Full Leadership List -->
            <div v-if="selectedNode.data.leadership && selectedNode.data.leadership.length > 0">
              <strong>Leadership:</strong>
              <div class="mt-2 space-y-2">
                <div 
                  v-for="leader in selectedNode.data.leadership"
                  :key="leader.id"
                  class="flex items-center space-x-3 p-2 bg-gray-50 rounded"
                >
                  <img
                    v-if="leader.avatar_url"
                    :src="leader.avatar_url"
                    :alt="leader.name"
                    class="w-10 h-10 rounded-full object-cover"
                  />
                  <div v-else class="w-10 h-10 rounded-full bg-gray-300 flex items-center justify-center font-semibold">
                    {{ leader.name.charAt(0) }}
                  </div>
                  <div>
                    <div class="font-medium">{{ leader.name }}</div>
                    <div class="text-sm text-gray-600">{{ leader.role }}</div>
                    <div v-if="leader.title" class="text-sm text-gray-500">{{ leader.title }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import OrganizationChart from 'primevue/organizationchart'

// Props
const props = defineProps({
  organizationData: {
    type: Object,
    default: () => ({})
  }
})

// Reactive data
const chartData = ref(null)
const isExpanded = ref(false)
const selectedNode = ref(null)

// Methods
const loadData = () => {
  if (props.organizationData && Object.keys(props.organizationData).length > 0) {
    chartData.value = [props.organizationData]
  }
}

const toggleExpanded = () => {
  isExpanded.value = !isExpanded.value
  if (isExpanded.value) {
    router.visit('/staff/organization/expanded')
  } else {
    router.visit('/staff/organization')
  }
}

const refreshData = async () => {
  try {
    const response = await fetch('/staff/api/organization')
    const data = await response.json()
    chartData.value = [data]
  } catch (error) {
    console.error('Failed to refresh organization data:', error)
  }
}

const onNodeSelect = (node) => {
  selectedNode.value = node
}

// Lifecycle
onMounted(() => {
  loadData()
})
</script>

<style scoped>
.organization-chart-container {
  padding: 1rem;
  min-height: 100vh;
}

.organization-chart-wrapper {
  overflow-x: auto;
}

/* Organization Chart Node Styles */
:deep(.p-organizationchart) {
  .p-organizationchart-node-content {
    padding: 0;
    border: none;
    background: none;
  }
}

.org-node {
  background: white;
  border: 2px solid #e5e7eb;
  border-radius: 12px;
  padding: 1rem;
  min-width: 250px;
  max-width: 300px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
  cursor: pointer;
}

.org-node:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  transform: translateY(-2px);
}

.org-node-bod {
  border-color: #7c3aed;
  background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
}

.org-node-division {
  border-color: #059669;
  background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
}

.org-node-department {
  border-color: #dc2626;
  background: linear-gradient(135deg, #fef2f2 0%, #fecaca 100%);
}

.org-node-team {
  border-color: #2563eb;
  background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
}

.org-node-header {
  text-align: center;
  margin-bottom: 0.75rem;
}

.org-node-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 0.25rem;
}

.org-node-type {
  font-size: 0.75rem;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.org-node-logo {
  margin: 0.75rem 0;
  text-align: center;
}

.org-node-leadership {
  margin: 0.75rem 0;
}

.leadership-title {
  font-size: 0.875rem;
  font-weight: 600;
  color: #374151;
  margin-bottom: 0.5rem;
}

.leadership-list {
  space-y: 0.5rem;
}

.leader-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.25rem;
}

.leader-info {
  flex: 1;
  min-width: 0;
}

.leader-name {
  font-size: 0.75rem;
  font-weight: 500;
  color: #1f2937;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.leader-role {
  font-size: 0.625rem;
  color: #6b7280;
}

.org-node-description {
  font-size: 0.75rem;
  color: #6b7280;
  margin-top: 0.75rem;
  line-height: 1.4;
}
</style>
