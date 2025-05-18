// src/services/ProductMovementService.ts
import axios from 'axios';

export interface ProductMovementRequest {
    product_id: number;
    boxes: number;
    fractions: number;
    type: string;
    batch: string;
    expiry_date: string;
    unit_price: number;
    total_price: number;
    movement_id: number;
}

export interface ProductMovement {
    success: boolean;
    message: string;
    data: {
        id: number;
        productId: number;
        quantity: number;
        fractionQuantity: number;
        unitPrice: string;
        unitPriceEx: string;
        fractionPrice: string;
        totalPrice: string;
        labName: string;
        productName: string;
        unitPrices: string;
        batch: string;
        expiryDate: string;
        expiryDateDisplay: string;
        movementId: number;
        quantityStatus: number;
        quantityType: string;
        totalQuantity: string;
        generalPrice: string;
        status: number;
    };
}

export interface ProductMovementResponse {
    success: boolean;
    message: string;
    data: ProductMovement[];
    subtotal: string;
    tax: string;
    total: string;
}

export interface ProductMovementDeleteResponse {
    success: boolean;
    message: string;
    error?: string
}

export const ProductMovementServices = {
    // Add product movement
   async storeProductMovement(data: ProductMovementRequest): Promise<ProductMovementResponse> {
        const response = await axios.post('/panel/product-movements', {
            product_id: data.product_id,
            quantity: data.boxes,
            fraction_quantity: data.fractions,
            total_price: data.total_price,
            unit_price: data.unit_price,
            batch: data.batch,
            expiry_date: data.expiry_date,
            quantity_type: data.type === 'Box' ? 1 : data.type === 'Fraction' ? 0 : 2, // Fixed mapping
            movement_id: data.movement_id,
        });
        return response.data;
    },

    // Fetch product movements for a specific movement
    async getProductMovements(movementId: number): Promise<ProductMovementResponse> {
        try {
            const response = await axios.get('/panel/listar-product-movements', {
               params: { movementId }
            });
            return response.data;
        } catch (error) {
            console.error('Error fetching product movements:', error);
            throw error;
        }
    },

    // Delete a product movement
    async deleteProductMovement(productId: number, movementId: number): Promise<ProductMovementDeleteResponse> {
        try {
            const response = await axios.delete('/panel/product-movements', {
                data: {
                    product_id: productId,
                    movement_id: movementId,
                },
            });
            return response.data;
        } catch (error: any) {
            console.error('Error deleting product movement:', error);
            throw error;
        }
    }
};