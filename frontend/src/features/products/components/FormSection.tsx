import { Card, CardContent, CardHeader } from '@/components/ui/card'

interface FormSectionProps {
  title: string
  description?: string
  icon?: React.ReactNode
  children: React.ReactNode
}

const FormSection = ({ title, description, icon, children }: FormSectionProps) => (
  <Card>
    <CardHeader className="border-b px-5 py-4">
      <div className="flex items-center gap-3">
        {icon && <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-muted text-muted-foreground">{icon}</div>}
        <div>
          <h2 className="text-base font-semibold tracking-tight">{title}</h2>
          {description && <p className="text-sm text-muted-foreground">{description}</p>}
        </div>
      </div>
    </CardHeader>
    <CardContent className="space-y-5 p-5">{children}</CardContent>
  </Card>
)

export default FormSection
