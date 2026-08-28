import { lazy, Suspense } from 'react'
import { Navigate, Route, Routes } from 'react-router-dom'
import { Analytics } from '@vercel/analytics/react'
import NetworkStatus from './components/common/NetworkStatus'
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
const AdminMembersPage = lazy(() => import('./pages/admin/AdminMembersPage'))
const AdminBackupPage = lazy(() => import('./pages/admin/AdminBackupPage'))

const DashboardOverviewPage = lazy(() => import('./pages/dashboard/DashboardOverviewPage'))
const DashboardOrdersPage = lazy(() => import('./pages/dashboard/DashboardOrdersPage'))
const DashboardProfilePage = lazy(() => import('./pages/dashboard/DashboardProfilePage'))
const DashboardAddressesPage = lazy(() => import('./pages/dashboard/DashboardAddressesPage'))
const DashboardSecurityPage = lazy(() => import('./pages/dashboard/DashboardSecurityPage'))

import AdminLayout from './components/layout/AdminLayout'
import { PermissionRoute } from './components/admin/PermissionRoute'

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

        {/* Admin Routes with Persistent AdminLayout & Permission Guards */}
        <Route
          path="admin"
          element={
            <AdminRoute>
              <AdminLayout />
            </AdminRoute>
          }
        >
          <Route index element={<Navigate replace to="dashboard" />} />
          <Route
            path="dashboard"
            element={
              <PermissionRoute requiredPermission="dashboard.view">
                <AdminDashboardPage />
              </PermissionRoute>
            }
          />
          <Route
            path="products"
            element={
              <PermissionRoute requiredPermission="products.view">
                <AdminProductsPage />
              </PermissionRoute>
            }
          />
          <Route
            path="products/create"
            element={
              <PermissionRoute requiredPermission="products.create">
                <AdminProductCreatePage />
              </PermissionRoute>
            }
          />
          <Route
            path="products/:id/edit"
            element={
              <PermissionRoute requiredPermission="products.update">
                <AdminProductEditPage />
              </PermissionRoute>
            }
          />
          <Route
            path="products/:id"
            element={
              <PermissionRoute requiredPermission="products.view">
                <AdminProductViewPage />
              </PermissionRoute>
            }
          />
          <Route
            path="orders"
            element={
              <PermissionRoute requiredPermission="orders.view">
                <AdminOrdersPage />
              </PermissionRoute>
            }
          />
          <Route
            path="orders/:id/invoice"
            element={
              <PermissionRoute requiredPermission="orders.view">
                <AdminOrderInvoicePage />
              </PermissionRoute>
            }
          />
          <Route
            path="shipping"
            element={
              <PermissionRoute requiredPermission="shipping.view">
                <AdminShippingPage />
              </PermissionRoute>
            }
          />
          <Route
            path="payment-methods"
            element={
              <PermissionRoute requiredPermission="payment_methods.view">
                <AdminPaymentMethodsPage />
              </PermissionRoute>
            }
          />
          <Route
            path="checkout-notices"
            element={
              <PermissionRoute requiredPermission="checkout_notices.view">
                <AdminCheckoutNoticesPage />
              </PermissionRoute>
            }
          />
          <Route
            path="categories"
            element={
              <PermissionRoute requiredPermission="categories.view">
                <AdminCategoriesPage />
              </PermissionRoute>
            }
          />
          <Route
            path="brands"
            element={
              <PermissionRoute requiredPermission="brands.view">
                <AdminBrandsPage />
              </PermissionRoute>
            }
          />
          <Route
            path="collections"
            element={
              <PermissionRoute requiredPermission="collections.view">
                <AdminCollectionsPage />
              </PermissionRoute>
            }
          />
          <Route
            path="colors"
            element={
              <PermissionRoute requiredPermission="colors.view">
                <AdminColorsPage />
              </PermissionRoute>
            }
          />
          <Route
            path="sizes"
            element={
              <PermissionRoute requiredPermission="sizes.view">
                <AdminSizesPage />
              </PermissionRoute>
            }
          />
          <Route
            path="vendors"
            element={
              <PermissionRoute requiredPermission="vendors.view">
                <AdminVendorsPage />
              </PermissionRoute>
            }
          />
          <Route
            path="suppliers"
            element={
              <PermissionRoute requiredPermission="suppliers.view">
                <AdminSuppliersPage />
              </PermissionRoute>
            }
          />
          <Route
            path="banners"
            element={
              <PermissionRoute requiredPermission="banners.view">
                <AdminBannersPage />
              </PermissionRoute>
            }
          />
          <Route
            path="policies"
            element={
              <PermissionRoute requiredPermission="policies.view">
                <AdminPoliciesPage />
              </PermissionRoute>
            }
          />
          <Route
            path="homepage"
            element={
              <PermissionRoute requiredPermission="homepage.view">
                <AdminHomepagePage />
              </PermissionRoute>
            }
          />
          <Route
            path="media"
            element={
              <PermissionRoute requiredPermission="media.view">
                <AdminMediaPage />
              </PermissionRoute>
            }
          />
          <Route
            path="expenses"
            element={
              <PermissionRoute requiredPermission="expenses.view">
                <AdminExpensesPage />
              </PermissionRoute>
            }
          />
          <Route
            path="expenses/categories"
            element={
              <PermissionRoute requiredPermission="expenses.view">
                <AdminExpenseCategoriesPage />
              </PermissionRoute>
            }
          />
          <Route
            path="expenses/reports"
            element={
              <PermissionRoute requiredPermission="reports.view">
                <AdminExpenseReportsPage />
              </PermissionRoute>
            }
          />
          <Route
            path="inventory"
            element={
              <PermissionRoute requiredPermission="inventory.view">
                <AdminInventoryPage />
              </PermissionRoute>
            }
          />
          <Route
            path="customers"
            element={
              <PermissionRoute requiredPermission="customers.view">
                <AdminCustomersPage />
              </PermissionRoute>
            }
          />
          <Route
            path="coupons"
            element={
              <PermissionRoute requiredPermission="coupons.view">
                <AdminCouponsPage />
              </PermissionRoute>
            }
          />
          <Route
            path="analytics"
            element={
              <PermissionRoute requiredPermission="analytics.view">
                <AdminAnalyticsPage />
              </PermissionRoute>
            }
          />
          <Route
            path="marketing"
            element={
              <PermissionRoute requiredPermission="marketing.view">
                <AdminMarketingPage />
              </PermissionRoute>
            }
          />
          <Route
            path="members"
            element={
              <PermissionRoute requiredPermission="members.view">
                <AdminMembersPage />
              </PermissionRoute>
            }
          />
          <Route
            path="backup"
            element={
              <PermissionRoute requiredPermission="backup.view">
                <AdminBackupPage />
              </PermissionRoute>
            }
          />
          <Route
            path="settings"
            element={
              <PermissionRoute requiredPermission="settings.manage">
                <AdminSettingsPage />
              </PermissionRoute>
            }
          />
        </Route>
      </Routes>
      <NetworkStatus />
    </Suspense>
  )
}

export default App

// function App() {
//   return <Demo />
// }

// export default App
