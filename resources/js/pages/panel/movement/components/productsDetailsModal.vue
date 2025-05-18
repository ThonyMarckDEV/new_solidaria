<template>
    <Dialog :open="modal" @update:open="closeModal">
        <DialogContent class="sm:max-w-[100vw] sm:max-h-[100vh] h-screen w-screen p-8 bg-gradient-to-br from-white to-emerald-50 dark:from-gray-800 dark:to-blue-900">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h2 class="text-2xl font-extrabold text-emerald-800 dark:text-blue-200 tracking-wide">
                        Movement Details #{{ movementData.id }}
                    </h2>
                    <div class="grid grid-cols-3 gap-6 mt-4">
                        <div>
                            <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Code</p>
                            <p class="text-sm text-emerald-700 dark:text-blue-100 font-medium">{{ movementData.code }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Supplier</p>
                            <p class="text-sm text-emerald-700 dark:text-blue-100 font-medium">{{ movementData.supplier?.name }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Issue Date</p>
                            <p class="text-sm text-emerald-700 dark:text-blue-100 font-medium">{{ formatDate(movementData.issue_date) }}</p>
                        </div>
                    </div>
                </div>
                <Button 
                    variant="default" 
                    class="bg-gradient-to-r from-emerald-500 to-emerald-600 dark:from-blue-800 dark:to-blue-900 text-white font-semibold rounded-lg shadow-lg hover:from-emerald-600 hover:to-emerald-700 dark:hover:from-blue-900 dark:hover:to-blue-950 transition-all duration-300"
                    @click="openAddProductModal"
                >
                    <Plus class="w-5 h-5 mr-2" />
                    Add New
                </Button>
            </div>

            <!-- Error Message -->
            <div v-if="errorMessage" class="mb-4 p-4 bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 rounded-lg">
                {{ errorMessage }}
            </div>

            <!-- Table Section -->
            <div class="mb-8 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-emerald-200 dark:border-blue-700 p-6">
                <div v-if="productMovements.data.length > 0">
                    <h3 class="text-xl font-bold text-emerald-800 dark:text-blue-200 mb-4">Selected Products</h3>
                    <Table>
                        <TableHeader>
                            <TableRow class="bg-emerald-100 dark:bg-blue-800">
                                <TableHead class="text-center text-emerald-900 dark:text-blue-200 font-semibold">Type</TableHead>
                                <TableHead class="text-center text-emerald-900 dark:text-blue-200 font-semibold">Quantity</TableHead>
                                <TableHead class="text-center text-emerald-900 dark:text-blue-200 font-semibold">Product</TableHead>
                                <TableHead class="text-center text-emerald-900 dark:text-blue-200 font-semibold">Laboratory</TableHead>
                                <TableHead class="text-center text-emerald-900 dark:text-blue-200 font-semibold">Batch</TableHead>
                                <TableHead class="text-center text-emerald-900 dark:text-blue-200 font-semibold">Expiry</TableHead>
                                <TableHead class="text-center text-emerald-900 dark:text-blue-200 font-semibold">Unit Price</TableHead>
                                <TableHead class="text-center text-emerald-900 dark:text-blue-200 font-semibold">Unit Price + Tax</TableHead>
                                <TableHead class="text-center text-emerald-900 dark:text-blue-200 font-semibold">Total</TableHead>
                                <TableHead class="text-center text-emerald-900 dark:text-blue-200 font-semibold">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow 
                                v-for="product in productMovements.data" 
                                :key="product.id" 
                                class="hover:bg-emerald-50 dark:hover:bg-blue-950 transition-colors duration-200"
                            >
                                <td class="text-center text-gray-800 dark:text-gray-200 py-3">{{ product.quantityType }}</td>
                                <td class="text-center text-gray-800 dark:text-gray-200 py-3">{{ product.totalQuantity }}</td>
                                <td class="text-center text-gray-800 dark:text-gray-200 py-3">{{ product.productName }}</td>
                                <td class="text-center text-gray-800 dark:text-gray-200 py-3">{{ product.labName }}</td>
                                <td class="text-center text-gray-800 dark:text-gray-200 py-3">{{ product.batch }}</td>
                                <td class="text-center text-gray-800 dark:text-gray-200 py-3">{{ product.expiryDateDisplay }}</td>
                                <td class="text-center text-gray-800 dark:text-gray-200 py-3">{{ product.unitPrice }}</td>
                                <td class="text-center text-gray-800 dark:text-gray-200 py-3">{{ calculatePriceWithTax(product.unitPrice) }}</td>
                                <td class="text-center text-gray-800 dark:text-gray-200 py-3">{{ product.totalPrice }}</td>
                                <td class="text-center py-3">
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        class="text-red-600 hover:text-red-800 hover:bg-red-100 dark:hover:bg-red-900 rounded-full p-2 transition-colors duration-200"
                                        @click="removeProduct(product.id)"
                                    >
                                        <Trash class="w-5 h-5" />
                                        <span class="sr-only">Delete product</span>
                                    </Button>
                                </td>
                            </TableRow>
                        </TableBody>
                    </Table>
                    <!-- Subtotal, Tax, Total -->
                    <div class="flex justify-end mt-6">
                        <div class="text-right">
                            <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Subtotal: {{ productMovements.subtotal }}</p>
                            <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Tax: {{ productMovements.tax }}</p>
                            <p class="text-sm font-bold text-emerald-800 dark:text-blue-200">Total: {{ productMovements.total }}</p>
                        </div>
                    </div>
                </div>
                <div v-else>
                    <div class="flex justify-between items-center mb-6">
                        <span class="text-emerald-600 dark:text-blue-400 font-semibold cursor-pointer hover:underline">Movement Details</span>
                        <div class="flex items-center space-x-3">
                            <Button 
                                variant="outline" 
                                size="sm" 
                                disabled 
                                class="border-emerald-300 dark:border-blue-600 text-emerald-600 dark:text-blue-400 hover:bg-emerald-100 dark:hover:bg-blue-800 rounded-lg transition-colors duration-200"
                            >
                                <span><<</span>
                            </Button>
                            <Button 
                                variant="outline" 
                                size="sm" 
                                disabled 
                                class="border-emerald-300 dark:border-blue-600 text-emerald-600 dark:text-blue-400 hover:bg-emerald-100 dark:hover:bg-blue-800 rounded-lg transition-colors duration-200"
                            >
                                <span><</span>
                            </Button>
                            <span class="text-gray-700 dark:text-gray-300 font-medium">Showing 0 of 0 products</span>
                            <Button 
                                variant="outline" 
                                size="sm" 
                                disabled 
                                class="border-emerald-300 dark:border-blue-600 text-emerald-600 dark:text-blue-400 hover:bg-emerald-100 dark:hover:bg-blue-800 rounded-lg transition-colors duration-200"
                            >
                                <span>></span>
                            </Button>
                            <Button 
                                variant="outline" 
                                size="sm" 
                                disabled 
                                class="border-emerald-300 dark:border-blue-600 text-emerald-600 dark:text-blue-400 hover:bg-emerald-100 dark:hover:bg-blue-800 rounded-lg transition-colors duration-200"
                            >
                                <span>>></span>
                            </Button>
                            <select class="border border-emerald-300 dark:border-blue-600 rounded-lg p-2 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 focus:ring-2 focus:ring-emerald-500 dark:focus:ring-blue-500 transition-all duration-200">
                                <option>10</option>
                            </select>
                            <Input 
                                type="text" 
                                placeholder="Search..." 
                                class="w-1/4 border-emerald-300 dark:border-blue-600 rounded-lg p-2 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 focus:ring-2 focus:ring-emerald-500 dark:focus:ring-blue-500 transition-all duration-200"
                            />
                        </div>
                    </div>
                    <Table>
                        <TableHeader>
                            <TableRow class="bg-emerald-100 dark:bg-blue-800">
                                <TableHead class="text-center text-emerald-900 dark:text-blue-200 font-semibold">Type</TableHead>
                                <TableHead class="text-center text-emerald-900 dark:text-blue-200 font-semibold">Quantity</TableHead>
                                <TableHead class="text-center text-emerald-900 dark:text-blue-200 font-semibold">Product</TableHead>
                                <TableHead class="text-center text-emerald-900 dark:text-blue-200 font-semibold">Laboratory</TableHead>
                                <TableHead class="text-center text-emerald-900 dark:text-blue-200 font-semibold">Batch</TableHead>
                                <TableHead class="text-center text-emerald-900 dark:text-blue-200 font-semibold">Expiry</TableHead>
                                <TableHead class="text-center text-emerald-900 dark:text-blue-200 font-semibold">Unit Price</TableHead>
                                <TableHead class="text-center text-emerald-900 dark:text-blue-200 font-semibold">Unit Price + Tax</TableHead>
                                <TableHead class="text-center text-emerald-900 dark:text-blue-200 font-semibold">Total</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow>
                                <td class="text-center text-gray-600 dark:text-gray-400 py-4" colspan="9">No products available.</td>
                            </TableRow>
                        </TableBody>
                    </Table>
                    <div class="flex justify-end mt-6">
                        <div class="text-right">
                            <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Subtotal: 0.00</p>
                            <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Tax: 0.00</p>
                            <p class="text-sm font-bold text-emerald-800 dark:text-blue-200">Total: 0.00</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="flex justify-end gap-4">
                <Button 
                    type="button" 
                    variant="outline" 
                    class="border-emerald-300 dark:border-blue-600 text-emerald-600 dark:text-blue-400 font-semibold rounded-lg shadow-md hover:bg-emerald-100 dark:hover:bg-blue-800 transition-all duration-300"
                    @click="closeModal"
                >
                    Back
                </Button>
                <Button
                    type="button"
                    :disabled="productMovements.data.length === 0"
                    class="bg-gradient-to-r from-emerald-500 to-emerald-600 dark:from-blue-800 dark:to-blue-900 text-white font-semibold rounded-lg shadow-lg hover:from-emerald-600 hover:to-emerald-700 dark:hover:from-blue-900 dark:hover:to-blue-950 transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed"
                    @click="onSubmit"
                >
                    Save Products
                </Button>
            </div>

            <!-- Add Product Modal -->
            <AddProductModal
                :modal="addProductModalOpen"
                :movement-id="props.movementData.id"
                @emit-close="closeAddProductModal"
                @add-product="addProductFromModal"
            />
        </DialogContent>
    </Dialog>
</template>

<script setup lang="ts">
import Button from '@/components/ui/button/Button.vue';
import { Dialog, DialogContent } from '@/components/ui/dialog';
import { Table, TableBody, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { ref, onMounted } from 'vue';
import { MovementResource } from '../interface/Movement';
import AddProductModal from './addProductModal.vue';
import { Plus, Trash } from 'lucide-vue-next';
import { ProductMovementServices, ProductMovement, ProductMovementResponse } from '@/services/productMovementService';

// Props and Emits
const props = defineProps<{
    modal: boolean;
    movementData: MovementResource;
}>();

const emit = defineEmits<{
    (e: 'emit-close', open: boolean): void;
    (e: 'add-products', movementId: number, products: ProductMovement[]): void;
}>();

// State for product movements and error handling
const productMovements = ref<ProductMovementResponse>({
    success: true,
    message: '',
    data: [],
    subtotal: '0.00',
    tax: '0.00',
    total: '0.00',
});
const errorMessage = ref<string>('');

// Format date function
const formatDate = (dateString: string) => {
    if (!dateString) return '';
    try {
        if (dateString.includes('T')) {
            return dateString.split('T')[0];
        }
        if (dateString.includes('/')) {
            const [day, month, year] = dateString.split('/');
            return `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;
        }
        return dateString;
    } catch (e) {
        console.error('Error formatting date:', e);
        return '';
    }
};

// Calculate price with tax (assuming 18% tax)
const calculatePriceWithTax = (unitPrice: string) => {
    if (!unitPrice) return '0.00';
    const tax = 0.18; // 18% tax
    return (parseFloat(unitPrice) * (1 + tax)).toFixed(2);
};

// Fetch product movements
const fetchProductMovements = async () => {
    try {
        const response = await ProductMovementServices.getProductMovements(props.movementData.id);
        productMovements.value = response;
        errorMessage.value = response.data.length === 0 ? 'No product movements found for this movement.' : '';
    } catch (error: any) {
        console.error('Error fetching product movements:', error);
        errorMessage.value = error.response?.status === 404
            ? 'Movement not found.'
            : error.response?.data?.message || 'Failed to load product movements. Please try again later.';
    }
};

// Open Add Product Modal
const openAddProductModal = () => {
    addProductModalOpen.value = true;
};

// Close Add Product Modal
const closeAddProductModal = () => {
    addProductModalOpen.value = false;
};

// Add product from the second modal
const addProductModalOpen = ref(false);
const addProductFromModal = async (product: ProductMovement) => {
    try {
        productMovements.value.data.push(product);
        updateTotals();
        await fetchProductMovements(); // Refresh to ensure consistency
        errorMessage.value = productMovements.value.data.length === 0 ? 'No product movements found for this movement.' : '';
    } catch (error) {
        console.error('Error adding product:', error);
        errorMessage.value = 'Failed to add product. Please try again.';
    }
    closeAddProductModal();
};

// Remove product from the list
const removeProduct = async (id: number) => {
    try {
        await ProductMovementServices.deleteProductMovement(id);
        productMovements.value.data = productMovements.value.data.filter(p => p.id !== id);
        updateTotals();
        errorMessage.value = productMovements.value.data.length === 0 ? 'No product movements found for this movement.' : '';
    } catch (error) {
        console.error('Error deleting product movement:', error);
        errorMessage.value = 'Failed to delete product. Please try again.';
    }
};

// Update totals (fallback if backend doesn't provide them)
const updateTotals = () => {
    const subtotal = productMovements.value.data.reduce((sum, p) => sum + parseFloat(p.totalPrice), 0);
    const tax = subtotal * 0.18; // 18% tax
    const total = subtotal + tax;
    productMovements.value.subtotal = subtotal.toFixed(2);
    productMovements.value.tax = tax.toFixed(2);
    productMovements.value.total = total.toFixed(2);
};

// Submit all products
const onSubmit = () => {
    emit('add-products', props.movementData.id, productMovements.value.data);
    closeModal();
};

// Close modal
const closeModal = () => {
    productMovements.value = { success: true, message: '', data: [], subtotal: '0.00', tax: '0.00', total: '0.00' };
    errorMessage.value = '';
    emit('emit-close', false);
};

// Fetch data when modal opens
onMounted(() => {
    if (props.modal && props.movementData.id) {
        fetchProductMovements();
    }
});
</script>