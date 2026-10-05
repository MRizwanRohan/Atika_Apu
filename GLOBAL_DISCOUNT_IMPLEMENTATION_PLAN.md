# Global Offer — Current Implementation

## Admin settings

Use **Admin → Global Offer**. There is one settings form, one ON/OFF switch, and repeatable discount tier rows. Save all rows together using **Save settings**.

Each row has:

- Minimum order merchandise amount (BDT).
- Percentage discount, fixed amount discount, or free delivery.
- Discount value when applicable.

Example:

| Merchandise amount | Reward |
|---|---|
| ৳1,000 or more | 10% discount |
| ৳2,000 or more | 20% discount |
| ৳3,000 or more | Free delivery |

Add/remove rows within the same form. Existing rows keep their IDs when saved; removed rows are archived. No separate offer cards, names, priorities, schedules or individual enable switches are required.

## Customer display

The cart sidebar and checkout use the same progress component and server pricing.

For a ৳495 cart toward the ৳1,000 tier, show 49.5% progress and “Spend ৳505 more to unlock 10% discount.” After reaching the first tier, display the applied saving and progress toward the next tier. When all rewards are unlocked, show a completed bar.

The progress block appears for a nonempty cart with enabled, saved tiers. Turning the global switch OFF hides the block and removes automatic rewards. Admin explicitly explains this behavior.

## Calculation

- Thresholds are inclusive and exclude shipping.
- Current product/campaign prices determine the qualifying merchandise subtotal.
- Apply the best eligible monetary saving. Percentages do not accumulate: ৳2,000 at the 20% tier saves ৳400, not 30%.
- Compare valid coupon savings with the automatic monetary discount and apply the larger saving; coupon wins ties.
- Free delivery can combine with the selected monetary saving.
- Revalidate cart ownership, prices, discounts and shipping at order submission. Ask customers to review changed totals.
- Persist verified amounts and original reward snapshots in the order. Confirmed historical orders do not change when settings change. Incomplete orders revalidate on confirmation.

## Storage and validation

Existing `global_offers` rows internally represent tiers; no additional schema is needed for the simplified admin form. One transactional save updates their shared enabled state and all tier values. Existing order snapshot and financial columns remain in use.

Validate positive thresholds, valid reward types, positive discounts and percentage values up to 100. Regression tests cover repeatable saves, stable IDs, switch behavior, thresholds, tier selection, coupon comparison, free delivery, rounding, order persistence and stale quotes.
