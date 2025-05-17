<template>
    <div>
        <Select @update:modelValue="onSelect">
            <SelectTrigger>
                <SelectValue placeholder="Selecciona un producto" />
            </SelectTrigger>
            <SelectContent>
                <SelectGroup>
                    <SelectLabel>Productos</SelectLabel>
                    <SelectItem v-for="product in products" :key="product.id" :value="product.id">
                        {{ product.name }}
                    </SelectItem>
                </SelectGroup>
            </SelectContent>
        </Select>
    </div>
</template>

<script setup lang="ts">
import { Select, SelectContent, SelectGroup, SelectItem, SelectLabel, SelectTrigger, SelectValue } from '@/components/ui/select';
import { ref, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps<{
    initialId: number | null;
}>();

const emit = defineEmits<{
    (e: 'select', id: number, name: string): void;
}>();

const products = ref<{ id: number; name: string }[]>([]);

const fetchProducts = async () => {
    try {
        const response = await axios.get('/panel/products');
        products.value = response.data.products; // Adjust based on your API response
    } catch (error) {
        console.error('Error fetching products:', error);
    }
};

const onSelect = (id: number) => {
    const product = products.value.find(p => p.id === id);
    if (product) {
        emit('select', id, product.name);
    }
};

onMounted(() => {
    fetchProducts();
});
</script>