import { lazy, Suspense } from 'react'
import { Navigate, Route, Routes } from 'react-router-dom'
import ProtectedRoute from './components/common/ProtectedRoute'
import MainLayout from './components/layout/MainLayout'
import UserDashboardLayout from './components/layout/UserDashboardLayout'
import HomePage from './pages/HomePage'
import ShopPage from './pages/ShopPage'
import ProductDetailsPage from './pages/ProductDetailsPage'
import CheckoutPage from './pages/CheckoutPage'
import OrderSuccessPage from './pages/OrderSuccessPage'
import LoginPage from './pages/LoginPage'
import RegisterPage from './pages/RegisterPage'
import ForgotPasswordPage from './pages/ForgotPasswordPage'
import PolicyPage from './pages/PolicyPage'
import NotFoundPage from './pages/NotFoundPage'
import UserDashboardPage from './pages/UserDashboardPage'

const AdminDashboardPage = lazy(() => import('./pages/admin/AdminDashboardPage'))
const AdminProductsPage = lazy(() => import('./pages/admin/AdminProductsPage'))
const AdminProductCreatePage = lazy(() => import('./features/products/pages/AdminProductCreatePage'))
const AdminProductEditPage = lazy(() => import('./features/products/pages/AdminProductEditPage'))
const AdminProductViewPage = lazy(() => import('./features/products/pages/AdminProductViewPage'))
const AdminOrdersPage = lazy(() => import('./pages/admin/AdminOrdersPage'))
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

const PageFallback = () => (
  <div className="flex min-h-[50vh] items-center justify-center">
    <div className="flex flex-col items-center gap-3">
      <span className="h-10 w-10 animate-spin rounded-full border-4 border-slate-200 border-t-primary" />
      <p className="text-sm font-semibold text-slate-400">Loading...</p>
    </div>
  </div>
)

const AdminRoute = ({ children }: { children: React.ReactNode }) => (
  <ProtectedRoute allowedRoles={['admin', 'manager']}>{children}</ProtectedRoute>
)

function App() {
  return (
    <Suspense fallback={<PageFallback />}>
      <Routes>
        <Route element={<MainLayout />}>
          <Route index element={<HomePage />} />
          <Route path="shop" element={<ShopPage />} />
          <Route path="products/:slug" element={<ProductDetailsPage />} />
          <Route path="checkout" element={<CheckoutPage />} />
          <Route path="order/success" element={<OrderSuccessPage />} />
          <Route path="auth/login" element={<LoginPage />} />
          <Route path="auth/register" element={<RegisterPage />} />
          <Route path="auth/forgot-password" element={<ForgotPasswordPage />} />
          <Route path="refund-policy" element={<PolicyPage slug="return-refund" />} />
          <Route path="return-refund-policy" element={<PolicyPage slug="return-refund" />} />
          <Route path="shipping-policy" element={<PolicyPage slug="shipping" />} />
          <Route path="privacy-policy" element={<PolicyPage slug="privacy" />} />
          <Route path="terms-and-conditions" element={<PolicyPage slug="terms" />} />
          <Route path="cookie-policy" element={<PolicyPage slug="cookie" />} />
          <Route path="payment-policy" element={<PolicyPage slug="payment" />} />
          <Route path="cancellation-policy" element={<PolicyPage slug="cancellation" />} />
          <Route path="warranty-policy" element={<PolicyPage slug="warranty" />} />
          <Route path="faq" element={<PolicyPage slug="faq" />} />
          <Route path="contact" element={<PolicyPage slug="contact" />} />
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

        {/* Admin Routes */}
        <Route path="admin">
          <Route index element={<Navigate replace to="dashboard" />} />
          <Route path="dashboard" element={<AdminRoute><AdminDashboardPage /></AdminRoute>} />
          <Route path="products" element={<AdminRoute><AdminProductsPage /></AdminRoute>} />
          <Route path="products/create" element={<AdminRoute><AdminProductCreatePage /></AdminRoute>} />
          <Route path="products/:id/edit" element={<AdminRoute><AdminProductEditPage /></AdminRoute>} />
          <Route path="products/:id" element={<AdminRoute><AdminProductViewPage /></AdminRoute>} />
          <Route path="orders" element={<AdminRoute><AdminOrdersPage /></AdminRoute>} />
          <Route path="shipping" element={<AdminRoute><AdminShippingPage /></AdminRoute>} />
          <Route path="payment-methods" element={<AdminRoute><AdminPaymentMethodsPage /></AdminRoute>} />
          <Route path="checkout-notices" element={<AdminRoute><AdminCheckoutNoticesPage /></AdminRoute>} />
          <Route path="categories" element={<AdminRoute><AdminCategoriesPage /></AdminRoute>} />
          <Route path="brands" element={<AdminRoute><AdminBrandsPage /></AdminRoute>} />
          <Route path="collections" element={<AdminRoute><AdminCollectionsPage /></AdminRoute>} />
          <Route path="colors" element={<AdminRoute><AdminColorsPage /></AdminRoute>} />
          <Route path="sizes" element={<AdminRoute><AdminSizesPage /></AdminRoute>} />
          <Route path="vendors" element={<AdminRoute><AdminVendorsPage /></AdminRoute>} />
          <Route path="suppliers" element={<AdminRoute><AdminSuppliersPage /></AdminRoute>} />
          <Route path="banners" element={<AdminRoute><AdminBannersPage /></AdminRoute>} />
          <Route path="policies" element={<AdminRoute><AdminPoliciesPage /></AdminRoute>} />
          <Route path="homepage" element={<AdminRoute><AdminHomepagePage /></AdminRoute>} />
          <Route path="media" element={<AdminRoute><AdminMediaPage /></AdminRoute>} />
          <Route path="expenses" element={<AdminRoute><AdminExpensesPage /></AdminRoute>} />
          <Route path="expenses/categories" element={<AdminRoute><AdminExpenseCategoriesPage /></AdminRoute>} />
          <Route path="expenses/reports" element={<AdminRoute><AdminExpenseReportsPage /></AdminRoute>} />
          <Route path="inventory" element={<AdminRoute><AdminInventoryPage /></AdminRoute>} />
          <Route path="customers" element={<AdminRoute><AdminCustomersPage /></AdminRoute>} />
          <Route path="coupons" element={<AdminRoute><AdminCouponsPage /></AdminRoute>} />
          <Route path="analytics" element={<AdminRoute><AdminAnalyticsPage /></AdminRoute>} />
          <Route path="marketing" element={<AdminRoute><AdminMarketingPage /></AdminRoute>} />
          <Route
            path="settings"
            element={
              <ProtectedRoute allowedRoles={['admin']}>
                <AdminSettingsPage />
              </ProtectedRoute>
            }
          />
        </Route>
      </Routes>
    </Suspense>
  )
}

export default App
