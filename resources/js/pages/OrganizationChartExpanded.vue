<template>
  <div class="organization-chart-container">
    <div class="mb-6">
      <h1 class="text-3xl font-bold text-gray-900 mb-2">Organization Chart - Expanded View</h1>
      <p class="text-gray-600">
        Complete organizational structure including all teams
      </p>
      <div class="mt-4 flex gap-4">
        <button
          @click="toggleExpanded"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
        >
          Show Simplified
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
        class="organization-chart expanded"
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
                class="w-10 h-10 rounded-full object-cover mx-auto"
              />
            </div>

            <!-- Leadership - Compact view for expanded chart -->
            <div v-if="slotProps.node.data.leadership && slotProps.node.data.leadership.length > 0" 
                 class="org-node-leadership compact">
              <div class="leadership-avatars">
                <img
                  v-for="leader in slotProps.node.data.leadership.slice(0, 2)"
                  :key="leader.id"
                  v-if="leader.avatar_url"
                  :src="leader.avatar_url"
                  :alt="leader.name"
                  :title="`${leader.name} - ${leader.role}`"
                  class="w-6 h-6 rounded-full object-cover border-2 border-white -ml-1 first:ml-0"
                />
                <div 
                  v-if="slotProps.node.data.leadership.length > 2"
                  class="w-6 h-6 rounded-full bg-gray-300 border-2 border-white -ml-1 flex items-center justify-center text-xs font-semibold"
                  :title="`+${slotProps.node.data.leadership.length - 2} more leaders`"
                >
                  +{{ slotProps.node.data.leadership.length - 2 }}
                </div>
              </div>
            </div>

            <!-- Stats for expanded view -->
            <div v-if="slotProps.node.children && slotProps.node.children.length > 0" class="org-node-stats">
              <div class="stat-item">
                <span class="stat-number">{{ slotProps.node.children.length }}</span>
                <span class="stat-label">
                  {{ slotProps.node.type === 'division' ? 'Departments' : 
                     slotProps.node.type === 'department' ? 'Teams' : 'Sub-groups' }}
                </span>
              </div>
            </div>
          </div>
        </template>
      </OrganizationChart>
      
      <div v-else class="flex items-center justify-center h-64">
        <div class="text-gray-500">Loading expanded organization chart...</div>
      </div>
    </div>

    <!-- Node Details Modal -->
    <div v-if="selectedNode" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg max-w-4xl w-full mx-4 max-h-[80vh] overflow-y-auto">
        <div class="p-6">
          <div class="flex justify-between items-start mb-4">
            <h2 class="text-2xl font-bold">{{ selectedNode.label }}</h2>
            <button @click="selectedNode = null" class="text-gray-500 hover:text-gray-700">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>
          
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Basic Info -->
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
            </div>

            <!-- Leadership -->
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

          <!-- Sub-organizations -->
          <div v-if="selectedNode.children && selectedNode.children.length > 0" class="mt-6">
            <strong>Sub-organizations:</strong>
            <div class="mt-2 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
              <div
                v-for="child in selectedNode.children"
                :key="child.key"
                class="p-3 bg-gray-50 rounded border cursor-pointer hover:bg-gray-100"
                @click="selectedNode = child"
              >
                <div class="font-medium">{{ child.label }}</div>
                <div class="text-sm text-gray-600">{{ child.data.type }}</div>
                <div v-if="child.data.leadership && child.data.leadership.length > 0" class="text-xs text-gray-500 mt-1">
                  {{ child.data.leadership.length }} leader(s)
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
const selectedNode = ref(null)

// Methods
const loadData = () => {
  if (props.organizationData && Object.keys(props.organizationData).length > 0) {
    chartData.value = [props.organizationData]
  }
}

const toggleExpanded = () => {
  router.visit('/staff/organization')
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

/* Expanded Organization Chart Styles */
:deep(.p-organizationchart.expanded) {
  .p-organizationchart-node-content {
    padding: 0;
    border: none;
    background: none;
  }
}

.expanded .org-node {
  background: white;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  padding: 0.75rem;
  min-width: 180px;
  max-width: 220px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
  cursor: pointer;
}

.expanded .org-node:hover {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
  transform: translateY(-1px);
}

.expanded .org-node-header {
  text-align: center;
  margin-bottom: 0.5rem;
}

.expanded .org-node-title {
  font-size: 0.875rem;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 0.125rem;
  line-height: 1.2;
}

.expanded .org-node-type {
  font-size: 0.625rem;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.expanded .org-node-logo {
  margin: 0.5rem 0;
  text-align: center;
}

.org-node-leadership.compact {
  margin: 0.5rem 0;
  text-align: center;
}

.leadership-avatars {
  display: flex;
  justify-content: center;
  align-items: center;
  flex-wrap: wrap;
}

.org-node-stats {
  margin-top: 0.5rem;
  text-align: center;
}

.stat-item {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.stat-number {
  font-size: 1.125rem;
  font-weight: 700;
  color: #1f2937;
  line-height: 1;
}

.stat-label {
  font-size: 0.625rem;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

/* Type-specific colors for expanded view */
.expanded .org-node-bod {
  border-color: #7c3aed;
  background: linear-gradient(135deg, #faf5ff 0%, #f3e8ff 100%);
}

.expanded .org-node-division {
  border-color: #059669;
  background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
}

.expanded .org-node-department {
  border-color: #dc2626;
  background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
}

.expanded .org-node-team {
  border-color: #2563eb;
  background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
}
</style>
