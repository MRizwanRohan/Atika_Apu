export const ORDER_STATUS_OPTIONS = [
    { value: "pending", label: "Pending", bgClass: "bg-blue-500 text-white" },
    {
        value: "processing",
        label: "Processing",
        bgClass: "bg-primary text-white",
    },
    {
        value: "cancelled",
        label: "Cancelled",
        bgClass: "bg-red-500 text-white",
    },
    { value: "shipped", label: "Shipped", bgClass: "bg-yellow-500 text-black" },
    {
        value: "delivered",
        label: "Delivered",
        bgClass: "bg-green-500 text-white",
    },
    {
        value: "returned",
        label: "Returned",
        bgClass: "bg-purple-500 text-white",
    },
    {
        value: "incomplete",
        label: "Incomplete",
        bgClass: "bg-gray-500 text-white",
    },
    { value: "on_hold", label: "On Hold", bgClass: "bg-teal-500 text-white" },
    {
        value: "confirmed",
        label: "Confirmed",
        bgClass: "bg-indigo-500 text-white",
    },
    {
        value: "partially_delivered",
        label: "Partially Delivered",
        bgClass: "bg-stone-500 text-white",
    },
    {
        value: "received_china",
        label: "Received in China Warehouse",
        bgClass: "bg-orange-600 text-white",
    },
    {
        value: "received_bd_airport",
        label: "Sent To BD",
        bgClass: "bg-pink-600 text-white",
    },
    {
        value: "received_bd_warehouse",
        label: "Arrived In BD",
        bgClass: "bg-emerald-600 text-white",
    },
    {
        value: "purchase_ok",
        label: "Purchase",
        bgClass: "bg-amber-500 text-white",
    },
];

export const PRE_ORDER_STATUS_VALUES = [
    "received_china",
    "received_bd_airport",
    "received_bd_warehouse",
    "purchase_ok",
];

export const getOrderStatusLabel = (status) => {
    return (
        ORDER_STATUS_OPTIONS.find((option) => option.value === status)?.label ||
        status
    );
};
