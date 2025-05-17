<template>
    <Dialog :open="modal" @update:open="closeModal">
        <DialogContent class="sm:max-w-[100vw] sm:max-h-[100vh] h-screen w-screen p-8 bg-gradient-to-br from-white to-emerald-50 dark:from-gray-800 dark:to-blue-900">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h2 class="text-2xl font-extrabold text-emerald-800 dark:text-blue-200 tracking-wide">
                        DETALLE DE MOVIMIENTO N°: {{ movementData.id }}
                    </h2>
                    <div class="grid grid-cols-3 gap-6 mt-4">
                        <div>
                            <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Código</p>
                            <p class="text-sm text-emerald-700 dark:text-blue-100 font-medium">{{ movementData.code }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Proveedor</p>
                            <p class="text-sm text-emerald-700 dark:text-blue-100 font-medium">{{ movementData.supplier?.name }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Fecha de Emisión</p>
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
                    Nuevo
                </Button>
            </div>

            <!-- Table Section -->
            <div class="mb-8 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-emerald-200 dark:border-blue-700 p-6">
                <div v-if="selectedProducts.length > 0">
                    <h3 class="text-xl font-bold text-emerald-800 dark:text-blue-200 mb-4">Productos Seleccionados</h3>
                    <Table>
                        <TableHeader>
                            <TableRow class="bg-emerald-100 dark:bg-blue-800">
                                <TableHead class="text-center text-emerald-900 dark:text-blue-200 font-semibold">Tipo</TableHead>
                                <TableHead class="text-center text-emerald-900 dark:text-blue-200 font-semibold">Cantidad</TableHead>
                                <TableHead class="text-center text-emerald-900 dark:text-blue-200 font-semibold">Producto</TableHead>
                                <TableHead class="text-center text-emerald-900 dark:text-blue-200 font-semibold">Laboratorio</TableHead>
                                <TableHead class="text-center text-emerald-900 dark:text-blue-200 font-semibold">Lote</TableHead>
                                <TableHead class="text-center text-emerald-900 dark:text-blue-200 font-semibold">Vencimiento</TableHead>
                                <TableHead class="text-center text-emerald-900 dark:text-blue-200 font-semibold">PVF</TableHead>
                                <TableHead class="text-center text-emerald-900 dark:text-blue-200 font-semibold">PVF + IGV</TableHead>
                                <TableHead class="text-center text-emerald-900 dark:text-blue-200 font-semibold">Total</TableHead>
                                <TableHead class="text-center text-emerald-900 dark:text-blue-200 font-semibold">Acciones</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow 
                                v-for="(product, index) in selectedProducts" 
                                :key="index" 
                                class="hover:bg-emerald-50 dark:hover:bg-blue-950 transition-colors duration-200"
                            >
                                <td class="text-center text-gray-800 dark:text-gray-200 py-3">{{ product.type || 'N/A' }}</td>
                                <td class="text-center text-gray-800 dark:text-gray-200 py-3">{{ product.quantity }}</td>
                                <td class="text-center text-gray-800 dark:text-gray-200 py-3">{{ product.name }}</td>
                                <td class="text-center text-gray-800 dark:text-gray-200 py-3">{{ product.laboratory || 'N/A' }}</td>
                                <td class="text-center text-gray-800 dark:text-gray-200 py-3">{{ product.lot || 'N/A' }}</td>
                                <td class="text-center text-gray-800 dark:text-gray-200 py-3">{{ product.expiry_date || 'N/A' }}</td>
                                <td class="text-center text-gray-800 dark:text-gray-200 py-3">{{ product.unit_price || '0.00' }}</td>
                                <td class="text-center text-gray-800 dark:text-gray-200 py-3">{{ calculatePriceWithIGV(product.unit_price) }}</td>
                                <td class="text-center text-gray-800 dark:text-gray-200 py-3">{{ (product.quantity * product.unit_price).toFixed(2) }}</td>
                                <td class="text-center py-3">
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        class="text-red-600 hover:text-red-800 hover:bg-red-100 dark:hover:bg-red-900 rounded-full p-2 transition-colors duration-200"
                                        @click="removeProduct(index)"
                                    >
                                        <Trash class="w-5 h-5" />
                                        <span class="sr-only">Eliminar producto</span>
                                    </Button>
                                </td>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
                <div v-else>
                    <div class="flex justify-between items-center mb-6">
                        <span class="text-emerald-600 dark:text-blue-400 font-semibold cursor-pointer hover:underline">Detalles del Movimiento</span>
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
                                placeholder="Buscar..." 
                                class="w-1/4 border-emerald-300 dark:border-blue-600 rounded-lg p-2 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 focus:ring-2 focus:ring-emerald-500 dark:focus:ring-blue-500 transition-all duration-200"
                            />
                        </div>
                    </div>
                    <Table>
                        <TableHeader>
                            <TableRow class="bg-emerald-100 dark:bg-blue-800">
                                <TableHead class="text-center text-emerald-900 dark:text-blue-200 font-semibold">Tipo</TableHead>
                                <TableHead class="text-center text-emerald-900 dark:text-blue-200 font-semibold">Cantidad</TableHead>
                                <TableHead class="text-center text-emerald-900 dark:text-blue-200 font-semibold">Producto</TableHead>
                                <TableHead class="text-center text-emerald-900 dark:text-blue-200 font-semibold">Laboratorio</TableHead>
                                <TableHead class="text-center text-emerald-900 dark:text-blue-200 font-semibold">Lote</TableHead>
                                <TableHead class="text-center text-emerald-900 dark:text-blue-200 font-semibold">Vencimiento</TableHead>
                                <TableHead class="text-center text-emerald-900 dark:text-blue-200 font-semibold">PVF</TableHead>
                                <TableHead class="text-center text-emerald-900 dark:text-blue-200 font-semibold">PVF + IGV</TableHead>
                                <TableHead class="text-center text-emerald-900 dark:text-blue-200 font-semibold">Total</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow>
                                <td class="text-center text-gray-600 dark:text-gray-400 py-4" colspan="9">No hay productos disponibles.</td>
                            </TableRow>
                        </TableBody>
                    </Table>
                    <div class="flex justify-end mt-6">
                        <div class="text-right">
                            <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Subtotal: 0.00</p>
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
                    Volver
                </Button>
                <Button
                    type="button"
                    :disabled="selectedProducts.length === 0"
                    class="bg-gradient-to-r from-emerald-500 to-emerald-600 dark:from-blue-800 dark:to-blue-900 text-white font-semibold rounded-lg shadow-lg hover:from-emerald-600 hover:to-emerald-700 dark:hover:from-blue-900 dark:hover:to-blue-950 transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed"
                    @click="onSubmit"
                >
                    Guardar Productos
                </Button>
            </div>

            <!-- Add Product Modal -->
            <AddProductModal
                :modal="addProductModalOpen"
                @emit-close="closeAddProductModal"
                @add-product="addProductFromModal"
            />
        </DialogContent>
    </Dialog>
</template>

<script setup lang="ts">
import Button from '@/components/ui/button/Button.vue';
import { Dialog, DialogContent, DialogDescription } from '@/components/ui/dialog'; // Added DialogDescription
import { Table, TableBody, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Input } from '@/components/ui/input'; // Added explicit import for Input
import { ref } from 'vue';
import { MovementResource } from '../interface/Movement';
import AddProductModal from './addProductModal.vue';
import { Plus, Trash } from 'lucide-vue-next';

// Props and Emits
const props = defineProps<{
    modal: boolean;
    movementData: MovementResource;
}>();

const emit = defineEmits<{
    (e: 'emit-close', open: boolean): void;
    (e: 'add-products', movementId: number, products: { product_id: number; quantity: number; name: string; type?: string; laboratory?: string; lot?: string; expiry_date?: string; unit_price?: number }[]): void;
}>();

// State for selected products and add product modal
const selectedProducts = ref<{ product_id: number; quantity: number; name: string; type?: string; laboratory?: string; lot?: string; expiry_date?: string; unit_price?: number }[]>([]);
const addProductModalOpen = ref(false);

// Format date function
const formatDate = (dateString) => {
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

// Calculate price with IGV (assuming 18% IGV)
const calculatePriceWithIGV = (unitPrice) => {
    if (!unitPrice) return '0.00';
    const igv = 0.18; // 18% IGV
    return (unitPrice * (1 + igv)).toFixed(2);
};

// Calculate subtotal and total
const calculateSubtotal = () => {
    return selectedProducts.value.reduce((sum, product) => sum + (product.quantity * (product.unit_price || 0)), 0).toFixed(2);
};

const calculateTotal = () => {
    const subtotal = parseFloat(calculateSubtotal());
    const igv = 0.18; // 18% IGV
    return (subtotal * (1 + igv)).toFixed(2);
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
const addProductFromModal = (product) => {
    selectedProducts.value.push(product);
    closeAddProductModal();
};

// Remove product from the list
const removeProduct = (index: number) => {
    selectedProducts.value.splice(index, 1);
};

// Submit all products
const onSubmit = () => {
    emit('add-products', props.movementData.id, selectedProducts.value);
    closeModal();
};

// Close modal
const closeModal = () => {
    selectedProducts.value = [];
    emit('emit-close', false);
};
</script>