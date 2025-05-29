<template>
  <!-- Error message -->
  <div v-if="error" class="py-2 text-sm text-red-600">Error loading products. Please try again.</div>

  <!-- Combobox -->
  <div class="relative w-full">
    <!-- Input and icons -->
    <div class="relative">
      <input
        v-model="searchText"
        type="text"
        placeholder="Type to search products... (min 3 characters)"
        class="w-full rounded-md border border-gray-300 bg-white pl-10 pr-10 py-2 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
        @input="handleSearchInput"
        @focus="handleFocus"
        @keydown.enter="selectFirstProduct"
        @keydown.arrow-down.prevent="moveHighlight(1)"
        @keydown.arrow-up.prevent="moveHighlight(-1)"
        @keydown.escape="closeDropdown"
      />
      <!-- Search icon -->
      <svg
        class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
      </svg>
      <!-- Loading spinner during search -->
      <svg
        v-if="isSearching"
        class="absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 animate-spin text-blue-600"
        viewBox="0 0 24 24"
      >
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8h8a8 8 0 01-16 0z" />
      </svg>
      <!-- Clear selection button -->
      <button
        v-if="!isSearching && selectedProduct"
        class="absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none"
        @click="clearSelection"
        title="Clear selection"
      >
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <!-- Dropdown -->
    <div
      v-if="isOpen && shouldShowDropdown"
      class="absolute z-10 mt-1 max-h-60 w-full overflow-y-auto rounded-md border border-gray-200 bg-white shadow-lg"
    >
      <!-- Results -->
      <div
        v-if="products.length && !isSearching"
        v-for="(product, index) in products"
        :key="product.id"
        class="flex cursor-pointer items-center px-4 py-2 text-sm text-gray-900 hover:bg-gray-50"
        :class="{
          'bg-blue-50': index === highlightedIndex,
          'bg-blue-100': selectedProduct?.id === product.id,
        }"
        @click="selectProduct(product)"
        @mousemove="highlightedIndex = index"
      >
        {{ product.name }}
        <svg
          v-if="selectedProduct?.id === product.id"
          class="ml-auto h-4 w-4 text-blue-600"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
      </div>

      <!-- Loading state -->
      <div v-if="isSearching" class="px-4 py-3 text-sm text-gray-500 text-center">
        <div class="flex items-center justify-center space-x-2">
          <svg class="h-4 w-4 animate-spin text-blue-600" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8h8a8 8 0 01-16 0z" />
          </svg>
          <span>Searching...</span>
        </div>
      </div>

      <!-- No results -->
      <div
        v-else-if="!products.length && hasSearched && !isSearching"
        class="px-4 py-3 text-sm text-gray-500 text-center"
      >
        No products found for "{{ searchText }}"
      </div>

      <!-- Instructions -->
      <div
        v-else-if="!hasSearched && searchText.length < 3"
        class="px-4 py-3 text-sm text-gray-500 text-center"
      >
        Type at least 3 characters to search
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, defineExpose, onMounted, onUnmounted } from 'vue';
import debounce from 'debounce';
import { ProductServices } from '@/services/productService';
import { ProductResource } from '@/pages/panel/product/interface/Product';

const emit = defineEmits<{
  (e: 'select', product: ProductResource): void;
}>();

const products = ref<ProductResource[]>([]);
const searchText = ref<string>('');
const error = ref<boolean>(false);
const isSearching = ref<boolean>(false);
const selectedProduct = ref<ProductResource | null>(null);
const isOpen = ref<boolean>(false);
const highlightedIndex = ref<number>(-1);
const hasSearched = ref<boolean>(false);

const shouldShowDropdown = computed(() => {
  return searchText.value.length >= 3 || hasSearched.value || isSearching.value;
});

const searchProducts = async (query: string) => {
  if (query.length < 3) {
    products.value = [];
    hasSearched.value = false;
    return;
  }

  try {
    isSearching.value = true;
    error.value = false;
    
    const response = await ProductServices.getProducts(query);
    products.value = response.products || [];
    hasSearched.value = true;
    
  } catch (e) {
    console.error('Error searching products:', e);
    error.value = true;
    products.value = [];
  } finally {
    isSearching.value = false;
  }
};

const debouncedSearch = debounce((query: string) => {
  searchProducts(query);
}, 500);

const handleSearchInput = () => {
  highlightedIndex.value = -1;
  
  if (searchText.value.length < 3) {
    products.value = [];
    hasSearched.value = false;
    isOpen.value = false;
    return;
  }
  
  isOpen.value = true;
  debouncedSearch(searchText.value);
};

const handleFocus = () => {
  if (searchText.value.length >= 3) {
    isOpen.value = true;
  }
};

const selectProduct = (product: ProductResource) => {
  selectedProduct.value = product;
  searchText.value = product.name;
  isOpen.value = false;
  highlightedIndex.value = -1;
  emit('select', product);
};

const selectFirstProduct = () => {
  if (products.value.length && highlightedIndex.value >= 0) {
    selectProduct(products.value[highlightedIndex.value]);
  } else if (products.value.length) {
    selectProduct(products.value[0]);
  }
};

const moveHighlight = (direction: number) => {
  if (!products.value.length) return;
  
  const maxIndex = products.value.length - 1;
  let newIndex = highlightedIndex.value + direction;

  if (newIndex < 0) newIndex = maxIndex;
  if (newIndex > maxIndex) newIndex = 0;

  highlightedIndex.value = newIndex;
  

  const dropdown = document.querySelector('.max-h-60');
  const highlightedElement = dropdown?.children[highlightedIndex.value];
  if (highlightedElement) {
    highlightedElement.scrollIntoView({ block: 'nearest' });
  }
};

const closeDropdown = () => {
  isOpen.value = false;
  highlightedIndex.value = -1;
  

  if (selectedProduct.value) {
    searchText.value = selectedProduct.value.name;
  }
};

const clearSelection = () => {
  selectedProduct.value = null;
  searchText.value = '';
  products.value = [];
  hasSearched.value = false;
  isOpen.value = false;
  highlightedIndex.value = -1;
  emit('select', null as any);
};

const reset = () => {
  clearSelection();
};

const handleClickOutside = (event: Event) => {
  const target = event.target as Element;
  if (!target.closest('.relative')) {
    closeDropdown();
  }
};

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
  debouncedSearch.clear();
});

defineExpose({ reset });
</script>

<style scoped>

.transition-all {
  transition: all 0.2s ease-in-out;
}


.max-h-60 {
  max-height: 15rem;
  scrollbar-width: thin;
  scrollbar-color: #cbd5e0 #f7fafc;
}

.max-h-60::-webkit-scrollbar {
  width: 6px;
}

.max-h-60::-webkit-scrollbar-track {
  background: #f7fafc;
}

.max-h-60::-webkit-scrollbar-thumb {
  background: #cbd5e0;
  border-radius: 3px;
}

.max-h-60::-webkit-scrollbar-thumb:hover {
  background: #a0aec0;
}
</style>