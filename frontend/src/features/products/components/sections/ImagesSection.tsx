import { Images } from 'lucide-react'
import FormSection from '../FormSection'
import ProductImageUploader, { type ImageItem } from '../ProductImageUploader'
import { useProductForm } from '../ProductFormContext'

const ImagesSection = () => {
  const { form, set } = useProductForm()

  return (
    <FormSection
      title="Images"
      description="Upload to Cloudinary — drag & drop, reorder, replace or delete"
      icon={<Images className="h-4 w-4" />}
    >
      <ProductImageUploader images={form.images} onChange={(images: ImageItem[]) => set({ images })} />
    </FormSection>
  )
}

export default ImagesSection
