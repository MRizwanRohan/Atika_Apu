import { computed, toValue } from "vue";

export const formatPrice = (price) =>
    new Intl.NumberFormat("en-BD", {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(price);

/** Display price, crossed-out price and discount for a product card. */
export function useProductPricing(productSource) {
    const product = computed(() => toValue(productSource));

    const activeCampaign = computed(() => {
        const campaigns = product.value.campaigns;
        if (!campaigns || campaigns.length === 0) return null;
        const now = new Date();
        return campaigns.find((c) => {
            const start = new Date(c.start_date);
            const end = new Date(c.end_date);
            return c.status === "active" && now >= start && now <= end;
        });
    });

    const pricing = computed(() => {
        const p = product.value;

        // If product is variable, display lowest variation price by default
        if (p.type === "variable" && p.variations && p.variations.length > 0) {
            const numericVars = p.variations.map((v) => ({
                price: parseFloat(v.price || 0),
                previous_price:
                    v.previous_price !== null && v.previous_price !== undefined
                        ? parseFloat(v.previous_price)
                        : null,
            }));

            const minVar = numericVars.reduce(
                (acc, v) => (acc === null || v.price < acc.price ? v : acc),
                null,
            );
            let main = minVar ? minVar.price : parseFloat(p.price);
            let cross = null;
            let discountValue = 0;
            let discountType = null;

            if (minVar) {
                const candidates = numericVars
                    .filter((v) => v.previous_price && v.previous_price > main)
                    .map((v) => v.previous_price);
                if (candidates.length > 0) {
                    cross = Math.max(...candidates);
                    discountValue = ((cross - main) / cross) * 100;
                    discountType = "percentage";
                }
            }

            return { main, cross, discountValue, discountType };
        }

        // Fallback logic for simple products
        let main = parseFloat(p.price);
        let cross = null;
        let discountValue = 0;
        let discountType = null;

        if (activeCampaign.value) {
            cross = main;
            discountValue = parseFloat(activeCampaign.value.discount_amount);
            discountType = activeCampaign.value.discount_type;

            if (discountType === "percentage") {
                main = main - main * (discountValue / 100);
            } else if (discountType === "fixed") {
                main = main - discountValue;
            }
        } else if (p.previous_price && parseFloat(p.previous_price) > parseFloat(p.price)) {
            cross = parseFloat(p.previous_price);
            main = parseFloat(p.price);
            discountValue = ((cross - main) / cross) * 100;
            discountType = "percentage";
        }

        return { main, cross, discountValue, discountType };
    });

    const isOutOfStock = computed(() => product.value.stock <= 0 && !product.value.is_pre_order);
    const isPreOrderMode = computed(() => product.value.stock <= 0 && product.value.is_pre_order);

    return { activeCampaign, pricing, isOutOfStock, isPreOrderMode, formatPrice };
}
