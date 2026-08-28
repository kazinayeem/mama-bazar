import { lazy, Suspense } from 'react'
import { Navigate, Route, Routes } from 'react-router-dom'
import { Analytics } from '@vercel/analytics/react'
import ProtectedRoute from './components/common/ProtectedRoute'
import { PageSkeleton } from './components/common/Skeletons'
import MainLayout from './components/layout/MainLayout'
import UserDashboardLayout from './components/layout/UserDashboardLayout'
import HomePage from './pages/HomePage'
const ShopPage = lazy(() => import('./pages/ShopPage'))
const ProductDetailsPage = lazy(() => import('./pages/ProductDetailsPage'))
const CartPage = lazy(() => import('./pages/CartPage'))
const CheckoutPage = lazy(() => import('./pages/CheckoutPage'))
const OrderTrackingPage = lazy(() => import('./pages/OrderTrackingPage'))
const OrderSuccessPage = lazy(() => import('./pages/OrderSuccessPage'))
const LoginPage = lazy(() => import('./pages/LoginPage'))
const RegisterPage = lazy(() => import('./pages/RegisterPage'))
const ForgotPasswordPage = lazy(() => import('./pages/ForgotPasswordPage'))
const PolicyPage = lazy(() => import('./pages/PolicyPage'))
const FaqPage = lazy(() => import('./pages/FaqPage'))
const AboutPage = lazy(() => import('./pages/AboutPage'))
const ContactPage = lazy(() => import('./pages/ContactPage'))
const PrivacyPolicyPage = lazy(() => import('./pages/PrivacyPolicyPage'))
const TermsAndConditionsPage = lazy(() => import('./pages/TermsAndConditionsPage'))
const CookiePolicyPage = lazy(() => import('./pages/CookiePolicyPage'))
const NotFoundPage = lazy(() => import('./pages/NotFoundPage'))
const UserDashboardPage = lazy(() => import('./pages/UserDashboardPage'))

const AdminDashboardPage = lazy(() => import('./pages/admin/AdminDashboardPage'))
const AdminProductsPage = lazy(() => import('./pages/admin/AdminProductsPage'))
const AdminProductCreatePage = lazy(() => import('./features/products/pages/AdminProductCreatePage'))
const AdminProductEditPage = lazy(() => import('./features/products/pages/AdminProductEditPage'))
const AdminProductViewPage = lazy(() => import('./features/products/pages/AdminProductViewPage'))
const AdminOrdersPage = lazy(() => import('./pages/admin/AdminOrdersPage'))
const AdminOrderInvoicePage = lazy(() => import('./pages/admin/AdminOrderInvoicePage'))
const AdminCategoriesPage = lazy(() => import('./pages/admin/AdminCategoriesPage'))
const AdminCustomersPage = lazy(() => import('./pages/admin/AdminCustomersPage'))
const AdminCouponsPage = lazy(() => import('./pages/admin/AdminCouponsPage'))
const AdminAnalyticsPage = lazy(() => import('./pages/admin/AdminAnalyticsPage'))
const AdminMarketingPage = lazy(() => import('./pages/admin/AdminMarketingPage'))
const AdminSettingsPage = lazy(() => import('./pages/admin/AdminSettingsPage'))
const AdminBrandsPage = lazy(() => import('./pages/admin/AdminBrandsPage'))
const AdminBannersPage = lazy(() => import('./pages/admin/AdminBannersPage'))
const AdminMediaPage = lazy(() => import('./pages/admin/AdminMediaPage'))
const AdminInventoryPage = lazy(() => import('./pages/admin/AdminInventoryPage'))
const AdminColorsPage = lazy(() => import('./pages/admin/AdminColorsPage'))
const AdminSizesPage = lazy(() => import('./pages/admin/AdminSizesPage'))
const AdminCollectionsPage = lazy(() => import('./pages/admin/AdminCollectionsPage'))
const AdminVendorsPage = lazy(() => import('./pages/admin/AdminVendorsPage'))
const AdminSuppliersPage = lazy(() => import('./pages/admin/AdminSuppliersPage'))
const AdminShippingPage = lazy(() => import('./pages/admin/AdminShippingPage'))
const AdminPaymentMethodsPage = lazy(() => import('./pages/admin/AdminPaymentMethodsPage'))
const AdminCheckoutNoticesPage = lazy(() => import('./pages/admin/AdminCheckoutNoticesPage'))
const AdminHomepagePage = lazy(() => import('./pages/admin/AdminHomepagePage'))
const AdminPoliciesPage = lazy(() => import('./pages/admin/AdminPoliciesPage'))
const AdminExpensesPage = lazy(() => import('./pages/admin/AdminExpensesPage'))
const AdminExpenseCategoriesPage = lazy(() => import('./pages/admin/AdminExpenseCategoriesPage'))
const AdminExpenseReportsPage = lazy(() => import('./pages/admin/AdminExpenseReportsPage'))

const DashboardOverviewPage = lazy(() => import('./pages/dashboard/DashboardOverviewPage'))
const DashboardOrdersPage = lazy(() => import('./pages/dashboard/DashboardOrdersPage'))
const DashboardProfilePage = lazy(() => import('./pages/dashboard/DashboardProfilePage'))
const DashboardAddressesPage = lazy(() => import('./pages/dashboard/DashboardAddressesPage'))
const DashboardSecurityPage = lazy(() => import('./pages/dashboard/DashboardSecurityPage'))

import AdminLayout from './components/layout/AdminLayout'

const AdminRoute = ({ children }: { children: React.ReactNode }) => (
  <ProtectedRoute allowedRoles={['admin', 'manager']}>{children}</ProtectedRoute>
)

function App() {
  return (
    <Suspense fallback={<PageSkeleton />}>
      <Analytics />
      <Routes>
        <Route element={<MainLayout />}>
          <Route index element={<HomePage />} />
          <Route path="shop" element={<ShopPage />} />
          <Route path="products/:slug" element={<ProductDetailsPage />} />
          <Route path="cart" element={<CartPage />} />
          <Route path="checkout" element={<CheckoutPage />} />
          <Route path="track" element={<OrderTrackingPage />} />
          <Route path="order/success" element={<OrderSuccessPage />} />
          <Route path="auth/login" element={<LoginPage />} />
          <Route path="auth/register" element={<RegisterPage />} />
          <Route path="auth/forgot-password" element={<ForgotPasswordPage />} />
          <Route path="refund-policy" element={<PolicyPage slug="return-refund" />} />
          <Route path="return-refund-policy" element={<PolicyPage slug="return-refund" />} />
          <Route path="shipping-policy" element={<PolicyPage slug="shipping" />} />
          <Route path="about" element={<AboutPage />} />
          <Route path="privacy-policy" element={<PrivacyPolicyPage />} />
          <Route path="terms-and-conditions" element={<TermsAndConditionsPage />} />
          <Route path="cookie-policy" element={<CookiePolicyPage />} />
          <Route path="payment-policy" element={<PolicyPage slug="payment" />} />
          <Route path="cancellation-policy" element={<PolicyPage slug="cancellation" />} />
          <Route path="warranty-policy" element={<PolicyPage slug="warranty" />} />
          <Route path="faq" element={<FaqPage />} />
          <Route path="contact" element={<ContactPage />} />
          <Route
            path="dashboard"
            element={
              <ProtectedRoute allowedRoles={['user']}>
                <UserDashboardLayout />
              </ProtectedRoute>
            }
          >
            <Route index element={<Navigate replace to="overview" />} />
            <Route path="overview" element={<DashboardOverviewPage />} />
            <Route path="orders" element={<DashboardOrdersPage />} />
            <Route path="profile" element={<DashboardProfilePage />} />
            <Route path="addresses" element={<DashboardAddressesPage />} />
            <Route path="security" element={<DashboardSecurityPage />} />
            <Route path="legacy" element={<UserDashboardPage />} />
          </Route>
          <Route path="*" element={<NotFoundPage />} />
        </Route>

        {/* Admin Routes with Persistent AdminLayout */}
        <Route
          path="admin"
          element={
            <AdminRoute>
              <AdminLayout />
            </AdminRoute>
          }
        >
          <Route index element={<Navigate replace to="dashboard" />} />
          <Route path="dashboard" element={<AdminDashboardPage />} />
          <Route path="products" element={<AdminProductsPage />} />
          <Route path="products/create" element={<AdminProductCreatePage />} />
          <Route path="products/:id/edit" element={<AdminProductEditPage />} />
          <Route path="products/:id" element={<AdminProductViewPage />} />
          <Route path="orders" element={<AdminOrdersPage />} />
          <Route path="orders/:id/invoice" element={<AdminOrderInvoicePage />} />
          <Route path="shipping" element={<AdminShippingPage />} />
          <Route path="payment-methods" element={<AdminPaymentMethodsPage />} />
          <Route path="checkout-notices" element={<AdminCheckoutNoticesPage />} />
          <Route path="categories" element={<AdminCategoriesPage />} />
          <Route path="brands" element={<AdminBrandsPage />} />
          <Route path="collections" element={<AdminCollectionsPage />} />
          <Route path="colors" element={<AdminColorsPage />} />
          <Route path="sizes" element={<AdminSizesPage />} />
          <Route path="vendors" element={<AdminVendorsPage />} />
          <Route path="suppliers" element={<AdminSuppliersPage />} />
          <Route path="banners" element={<AdminBannersPage />} />
          <Route path="policies" element={<AdminPoliciesPage />} />
          <Route path="homepage" element={<AdminHomepagePage />} />
          <Route path="media" element={<AdminMediaPage />} />
          <Route path="expenses" element={<AdminExpensesPage />} />
          <Route path="expenses/categories" element={<AdminExpenseCategoriesPage />} />
          <Route path="expenses/reports" element={<AdminExpenseReportsPage />} />
          <Route path="inventory" element={<AdminInventoryPage />} />
          <Route path="customers" element={<AdminCustomersPage />} />
          <Route path="coupons" element={<AdminCouponsPage />} />
          <Route path="analytics" element={<AdminAnalyticsPage />} />
          <Route path="marketing" element={<AdminMarketingPage />} />
          <Route
            path="settings"
            element={
              <ProtectedRoute allowedRoles={['admin']}>
                <AdminSettingsPage />
              </ProtectedRoute>
            }
          />
          <Route path="*" element={<NotFoundPage />} />
        </Route>
      </Routes>
    </Suspense>
  )
}

export default App

// function App() {
//   return <Demo />
// }

// export default App
