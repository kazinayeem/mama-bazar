import { Link } from 'react-router-dom'
import { SEO } from '../components/common/SEO'

const NotFoundPage = () => {
  return (
    <main className="flex min-h-screen items-center justify-center bg-surface px-6">
      <SEO title="Page Not Found" description="The page you are looking for does not exist." noIndex />
      <div className="max-w-lg text-center">
        <p className="text-[10px] font-bold uppercase tracking-[0.3em] text-tertiary">404</p>
        <h1 className="mt-3 font-headline text-5xl font-extrabold tracking-tight">Page Not Found</h1>
        <p className="mt-4 text-on-surface-variant">The requested route does not exist in this storefront build.</p>
        <Link className="mt-8 inline-block bg-on-surface px-8 py-4 text-xs font-bold uppercase tracking-[0.25em] text-white" to="/">
          Back To Home
        </Link>
      </div>
    </main>
  )
}

export default NotFoundPage
