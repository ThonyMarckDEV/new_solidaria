<template>
    <!-- Initial loading state -->
    <div v-if="isLoading" class="flex items-center space-x-2 py-2">
      <div class="h-4 w-4 animate-spin rounded-full border-b-2 border-t-2 border-primary"></div>
      <span class="text-sm text-muted-foreground">Loading products...</span>
    </div>
  
    <!-- Error message -->
    <div v-else-if="error" class="py-2 text-sm text-red-500">Error loading products. Please try again.</div>
  
    <!-- Combobox with controlled height -->
    <Combobox v-else by="id" v-model="selectedProduct">
      <ComboboxAnchor>
        <div class="relative w-full items-center">
          <ComboboxInput
            class="pl-9"
            :display-value="(val) => val?.name ?? ''"
            :model-value="searchText"
            placeholder="Select product..."
            @update:model-value="handleSearchInput"
          />
          <span class="absolute inset-y-0 start-0 flex items-center justify-center px-3">
            <Search class="size-4 text-muted-foreground" />
          </span>
          <!-- Search indicator -->
          <span v-if="isSearching" class="absolute inset-y-0 end-0 flex items-center justify-center px-3">
            <div class="h-4 w-4 animate-spin rounded-full border-b-2 border-t-2 border-primary"></div>
          </span>
        </div>
      </ComboboxAnchor>
  
      <!-- Scrollable container -->
      <ComboboxList class="max-h-60 overflow-y-auto">
        <ComboboxEmpty>No products found.</ComboboxEmpty>
        <ComboboxGroup>
          <ComboboxItem
            v-for="product in filteredProducts"
            :key="product.id"
            :value="product"
            @select="onSelect(product)"
            class="px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-800"
          >
            {{ product.name }}
            <ComboboxItemIndicator>
              <Check class="ml-auto h-4 w-4" />
            </ComboboxItemIndicator>
          </ComboboxItem>
        </ComboboxGroup>
      </ComboboxList>
    </Combobox>
</template>

<script setup lang="ts">
import {
  Combobox,
  ComboboxAnchor,
  ComboboxEmpty,
  ComboboxGroup,
  ComboboxInput,
  ComboboxItem,
  ComboboxItemIndicator,
  ComboboxList,
} from '@/components/ui/combobox';
import { Check, Search } from 'lucide-vue-next';
import { onMounted, ref, computed, defineExpose } from 'vue';
import debounce from 'debounce';
import { ProductServices } from '@/services/productService';
import { ProductResource } from '@/pages/panel/product/interface/Product';

const emit = defineEmits<{
  (e: 'select', product_id: number): void;
}>();

// State
const products = ref<ProductResource[]>([]);
const searchText = ref<string>('');
const error = ref<boolean>(false);
const isLoading = ref<boolean>(true);
const isSearching = ref<boolean>(false);
const selectedProduct = ref<ProductResource | null>(null);
const initialLoadDone = ref<boolean>(false);

const filteredProducts = computed(() => {
  if (!searchText.value) return products.value;

  return products.value.filter((product) =>
    product.name.toLowerCase().includes(searchText.value.toLowerCase())
  );
});

const initialLoadProducts = async () => {
  if (initialLoadDone.value) return;

  try {
    isLoading.value = true;
    const response = await ProductServices.getProducts('');
    products.value = response || [];
    error.value = false;
    initialLoadDone.value = true;
  } catch (e) {
    console.error('Error loading products:', e);
    error.value = true;
  } finally {
    isLoading.value = false;
  }
};

const searchProducts = async (query: string) => {
  if (!initialLoadDone.value) return;

  try {
    isSearching.value = true;
    const response = await ProductServices.getProducts(query);
    products.value = response || [];
    error.value = false;
  } catch (e) {
    console.error('Error searching products:', e);
    error.value = true;
  } finally {
    isSearching.value = false;
  }
};

const handleSearchInput = (value: string) => {
  searchText.value = value;
  if (initialLoadDone.value) {
    debouncedSearch(value);
  }
};

const debouncedSearch = debounce((value: string) => {
  if (value.length >= 3 || value === '') {
    searchProducts(value);
  }
}, 400);

const onSelect = (product: ProductResource) => {
  selectedProduct.value = product;
  emit('select', product.id);
};

onMounted(() => {
  initialLoadProducts();
});
</script>

<style scoped></style>