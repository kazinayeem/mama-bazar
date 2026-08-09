import { DEV_CUSTOMER_PHONE } from "../src/config/dev-credentials";

export const userSeeds = [
  { name: "Karim Rahman", phone: DEV_CUSTOMER_PHONE, shippingArea: "Dhaka", shippingAddress: "House 12, Road 5, Dhanmondi, Dhaka", role: "user" as const },
  { name: "Farzana Akter", phone: "01710000002", shippingArea: "Dhaka", shippingAddress: "Flat 4B, Uttara Sector 6, Dhaka", role: "user" as const },
  { name: "Tanvir Islam", phone: "01710000003", shippingArea: "Outside Dhaka", shippingAddress: "College Road, Khulna", role: "user" as const },
  { name: "Nusrat Jahan", phone: "01710000004", shippingArea: "Dhaka", shippingAddress: "Banani DOHS, Road 11, Dhaka", role: "user" as const },
  { name: "Rakib Hasan", phone: "01710000005", shippingArea: "Outside Dhaka", shippingAddress: "Ajimpur, Sylhet", role: "user" as const },
  { name: "Saima Chowdhury", phone: "01710000006", shippingArea: "Dhaka", shippingAddress: "Mirpur 10, Dhaka", role: "user" as const },
  { name: "Arif Hossain", phone: "01710000007", shippingArea: "Outside Dhaka", shippingAddress: "M.A. Bari Road, Chattogram", role: "user" as const },
  { name: "Mim Khan", phone: "01710000008", shippingArea: "Dhaka", shippingAddress: "Bashundhara R/A, Dhaka", role: "user" as const },
];

export interface OrderSeed {
  user: string;
  status: string;
  paymentStatus: string;
  paymentMethod: string;
  transactionId?: string;
  senderNumber?: string;
  address: string;
  area: string;
  items: { product: string; quantity: number }[];
  coupon?: string;
  daysAgo: number;
  note?: string;
}

export const orderSeeds: OrderSeed[] = [
  {
    user: "karim-rahman", status: "delivered", paymentStatus: "success", paymentMethod: "cod",
    address: "House 12, Road 5, Dhanmondi", area: "Dhanmondi", daysAgo: 21,
    items: [{ product: "samsung-galaxy-s24-ultra", quantity: 1 }, { product: "jbl-tune-510bt-headphones", quantity: 1 }],
  },
  {
    user: "farzana-akter", status: "delivered", paymentStatus: "success", paymentMethod: "bkash",
    transactionId: "DEVTRX99214510", senderNumber: "01810000002", daysAgo: 18,
    address: "Flat 4B, Uttara Sector 6", area: "Uttara",
    items: [{ product: "philips-air-fryer-55l", quantity: 1 }, { product: "tp-link-smart-wifi-bulb", quantity: 2 }],
  },
  {
    user: "tanvir-islam", status: "delivered", paymentStatus: "success", paymentMethod: "cod",
    address: "College Road, Khulna", area: "Khulna", daysAgo: 14,
    items: [{ product: "premium-cotton-panjabi", quantity: 2 }, { product: "mens-slim-fit-formal-shirt", quantity: 1 }],
  },
  {
    user: "nusrat-jahan", status: "shipped", paymentStatus: "verified", paymentMethod: "cod",
    address: "House 11, DOHS, Dhaka", area: "Banani", daysAgo: 6,
    items: [{ product: "designer-silk-saree", quantity: 1 }, { product: "baseus-65w-gan-charger", quantity: 1 }],
  },
  {
    user: "rakib-hasan", status: "shipped", paymentStatus: "verified", paymentMethod: "bkash",
    address: "Ajimpur, Sylhet", area: "Sylhet", daysAgo: 5, transactionId: "DEVTRX10051020", senderNumber: "01710000005",
    items: [{ product: "samsung-galaxy-watch-7", quantity: 1 }],
  },
  {
    user: "saima-chowdhury", status: "confirmed", paymentStatus: "payment_pending", paymentMethod: "cod",
    address: "Mirpur 10, Dhaka", area: "Mirpur", daysAgo: 2,
    items: [{ product: "xiaomi-smart-band-9", quantity: 1 }, { product: "urban-backpack-25l", quantity: 1 }],
  },
  {
    user: "arif-hossain", status: "pending", paymentStatus: "pending", paymentMethod: "cod",
    address: "M.A. Bari Road, Chattogram", area: "Chattogram", daysAgo: 0,
    items: [{ product: "casual-canvas-sneakers", quantity: 1 }, { product: "premium-cotton-panjabi", quantity: 1 }],
  },
  {
    user: "mim-khan", status: "delivered", paymentStatus: "success", paymentMethod: "nagad",
    address: "Bashundhara R/A, Dhaka", area: "Bashundhara", daysAgo: 11, transactionId: "NAG-889912",
    items: [{ product: "sony-extra-bass-speaker", quantity: 1 }, { product: "walton-25l-rice-cooker", quantity: 1 }],
  },
  {
    user: "karim-rahman", status: "cancelled", paymentStatus: "failed", paymentMethod: "cod",
    address: "House 12, Road 5, Dhanmondi", area: "Dhanmondi", daysAgo: 30,
    coupon: "SAVE200",
    items: [{ product: "samsung-51-soundbar", quantity: 1 }],
  },
  {
    user: "nusrat-jahan", status: "out_for_delivery", paymentStatus: "verified", paymentMethod: "cod",
    address: "Highland 11, DO IT", area: "Banani", daysAgo: 4,
    items: [{ product: "smart-air-purifier-hepa", quantity: 1 }, { product: "trendy-casual-saree", quantity: 1 }],
  },
  {
    user: "farzana-akter", status: "returned", paymentStatus: "refunded", paymentMethod: "bkash",
    address: "Flat 4B, Uttara Sector 6", area: "Uttara", daysAgo: 25, transactionId: "DEVTRX-33100",
    items: [{ product: "office-a-line-dress", quantity: 1 }],
  },
  {
    user: "rakib-hasan", status: "packed", paymentStatus: "verified", paymentMethod: "cod",
    address: "Ajimpur, Sylhet", area: "Sylhet", daysAgo: 1,
    items: [{ product: "running-performance-sneakers", quantity: 1 }, { product: "smart-table-lamp", quantity: 1 }],
  },
  {
    user: "saima-chowdhury", status: "processing", paymentStatus: "verified", paymentMethod: "cod",
    address: "Mirpur 10, Dhaka", area: "Mirpur", daysAgo: 3,
    items: [{ product: "philips-600w-blender", quantity: 1 }],
  },
  {
    user: "tanvir-islam", status: "confirmed", paymentStatus: "payment_verification", paymentMethod: "bkash",
    address: "College Road, Khulna", area: "Khulna", daysAgo: 1, transactionId: "BK-41099301",
    items: [{ product: "wifi-security-camera-360", quantity: 1 }, { product: "philips-steam-iron", quantity: 1 }],
  },
  {
    user: "mim-khan", status: "cancelled", paymentStatus: "rejected", paymentMethod: "cod",
    address: "Bashundhara R/A, Dhaka", area: "Bashundhara", daysAgo: 40,
    items: [{ product: "delonghi-coffee-maker", quantity: 1 }],
  },
  {
    user: "arif-hossain", status: "delivered", paymentStatus: "success", paymentMethod: "cod",
    address: "M.A. Bari Road, Chattogram", area: "Chattogram", daysAgo: 7,
    coupon: "WELCOME10",
    items: [{ product: "sony-wh-ch520", quantity: 1 }, { product: "casual-maxi-dress", quantity: 1 }],
  },
];

export const couponSeeds = [
  { code: "WELCOME10", discountType: "percentage" as const, discountValue: 10, minOrderAmount: 1500, status: "active" as const },
  { code: "SAVE200", discountType: "fixed" as const, discountValue: 200, minOrderAmount: 1000, status: "active" as const },
  { code: "FIRSTORDER", discountType: "percentage" as const, discountValue: 15, minOrderAmount: 2500, status: "active" as const },
  { code: "FLASH10", discountType: "percentage" as const, discountValue: 10, minOrderAmount: 0, status: "inactive" as const },
];