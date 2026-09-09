import UnderConstruction from '../components/common/UnderConstruction'
import { SEO } from '../components/common/SEO'

const HomePage = () => {
  return (
    <main className="relative">
      <SEO
        title="Home"
        description="Our website is currently under construction. Please come back later."
        url="/"
      />
      <UnderConstruction />
    </main>
  )
}

export default HomePage
