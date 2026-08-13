import { useCallback, useEffect, useState } from 'react'
import { ExternalLink, Loader2, Mail, RotateCcw, Save } from 'lucide-react'
import { toast } from 'sonner'
import AdminLayout from '@/components/layout/AdminLayout'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent } from '@/components/ui/card'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog'
import { Skeleton } from '@/components/ui/skeleton'
import { adminApi } from '@/lib/adminApi'
import HomepageLayoutBuilder from '@/features/homepage/admin/HomepageLayoutBuilder'
import HeroSlidesManager from '@/features/homepage/admin/HeroSlidesManager'
import ContentSettings from '@/features/homepage/admin/HomepageContentSettings'
import type {
  HomepageConfig,
  NewsletterSubscriber,
} from '@/types/homepage'
import { SEO } from '../../components/common/SEO'

const AdminHomepagePage = () => {
  const [config, setConfig] = useState<HomepageConfig | null>(null)
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [dirty, setDirty] = useState(false)
  const [resetOpen, setResetOpen] = useState(false)
  const [subscribers, setSubscribers] = useState<NewsletterSubscriber[]>([])
  const [activeTab, setActiveTab] = useState('layout')

  const load = useCallback(async () => {
    setLoading(true)
    try {
      const cfg = await adminApi.getHomepageConfig()
      setConfig(cfg)
      setDirty(false)
      try {
        setSubscribers(await adminApi.getNewsletterSubscribers())
      } catch {
        setSubscribers([])
      }
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Failed to load homepage config')
    } finally {
      setLoading(false)
    }
  }, [])

  useEffect(() => {
    load()
  }, [load])

  const patch = (patch: Partial<HomepageConfig>) => {
    setConfig((prev) => (prev ? { ...prev, ...patch } : prev))
    setDirty(true)
  }

  const handleSave = async () => {
    if (!config) return
    setSaving(true)
    try {
      await adminApi.saveHomepageConfig(config)
      toast.success('Homepage published — the storefront has been updated')
      setDirty(false)
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Save failed')
    } finally {
      setSaving(false)
    }
  }

  const handleReset = async () => {
    setResetOpen(false)
    try {
      const cfg = await adminApi.resetHomepageConfig()
      setConfig(cfg)
      setDirty(true)
      toast.success('Default layout restored — press Save to publish')
    } catch (err) {
      toast.error(err instanceof Error ? err.message : 'Reset failed')
    }
  }

  return (
    <AdminLayout>
      <SEO title="Homepage Layout" description="Configure homepage sections and layout." url="/admin/homepage" />
      <div className="space-y-4">
        <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
          <div>
            <h1 className="text-2xl font-bold tracking-tight">Homepage Builder</h1>
            <p className="text-sm text-muted-foreground">
              Design the storefront homepage — sections, hero slides, and content.
            </p>
          </div>
          <div className="flex items-center gap-2">
            <Button asChild className="hidden sm:inline-flex" variant="outline">
              <a href="/" rel="noreferrer" target="_blank">
                <ExternalLink className="h-4 w-4" /> View Storefront
              </a>
            </Button>
            <Button onClick={() => setResetOpen(true)} variant="outline">
              <RotateCcw className="h-4 w-4" /> Reset
            </Button>
            <Button disabled={saving || !dirty} onClick={handleSave}>
              {saving ? <Loader2 className="h-4 w-4 animate-spin" /> : <Save className="h-4 w-4" />}
              {dirty ? 'Publish Changes' : 'Published'}
            </Button>
          </div>
        </div>

        {dirty && (
          <div className="rounded-md border border-amber-300 bg-amber-50 px-4 py-2.5 text-sm font-medium text-amber-800">
            You have unpublished changes — press “Publish Changes” to apply them to the storefront.
          </div>
        )}

        {loading ? (
          <div className="space-y-4">
            {Array.from({ length: 5 }).map((_, i) => (
              <Skeleton key={i} className="h-20 w-full" />
            ))}
          </div>
        ) : !config ? (
          <Card>
            <CardContent className="py-16 text-center">
              <p className="text-sm text-muted-foreground">Could not load homepage configuration.</p>
            </CardContent>
          </Card>
        ) : (
          <Tabs onValueChange={setActiveTab} value={activeTab}>
            <TabsList>
              <TabsTrigger value="layout">Layout</TabsTrigger>
              <TabsTrigger value="hero">Hero Slides {config.heroSlides.length > 0 && <Badge className="ml-1.5">{config.heroSlides.length}</Badge>}</TabsTrigger>
              <TabsTrigger value="content">Content</TabsTrigger>
              <TabsTrigger value="subscribers">Subscribers {subscribers.length > 0 && <Badge className="ml-1.5">{subscribers.length}</Badge>}</TabsTrigger>
            </TabsList>

            <TabsContent className="pt-4" value="layout">
              <HomepageLayoutBuilder onChange={(sections) => patch({ sections })} sections={config.sections} />
            </TabsContent>

            <TabsContent className="pt-4" value="hero">
              <HeroSlidesManager onChange={(heroSlides) => patch({ heroSlides })} slides={config.heroSlides} />
            </TabsContent>

            <TabsContent className="pt-4" value="content">
              <ContentSettings
                announcement={config.announcement}
                flashSaleWindow={config.flashSaleWindow}
                newsletter={config.newsletter}
                onChange={patch}
                popularSearches={config.popularSearches}
                trustStrip={config.trustStrip}
                whyChooseUs={config.whyChooseUs}
              />
            </TabsContent>

            <TabsContent className="pt-4" value="subscribers">
              {subscribers.length === 0 ? (
                <Card>
                  <CardContent className="py-14 text-center">
                    <Mail className="mx-auto h-8 w-8 text-muted-foreground" />
                    <p className="mt-3 text-sm text-muted-foreground">No subscribers yet. Emails collected from the homepage newsletter form appear here.</p>
                  </CardContent>
                </Card>
              ) : (
                <Card>
                  <CardContent className="p-0">
                    <div className="divide-y">
                      {subscribers.map((sub) => (
                        <div className="flex items-center justify-between gap-3 px-4 py-3" key={sub.id}>
                          <div className="min-w-0">
                            <p className="truncate text-sm font-semibold">{sub.email}</p>
                            <p className="text-xs text-muted-foreground">
                              {new Date(sub.subscribedAt).toLocaleDateString()} · via {sub.source || 'homepage'}
                            </p>
                          </div>
                          <Badge variant={sub.status === 'subscribed' ? 'success' : 'muted'}>{sub.status}</Badge>
                        </div>
                      ))}
                    </div>
                  </CardContent>
                </Card>
              )}
            </TabsContent>
          </Tabs>
        )}
      </div>

      <AlertDialog open={resetOpen} onOpenChange={setResetOpen}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Reset homepage to defaults?</AlertDialogTitle>
            <AlertDialogDescription>
              This restores the default section layout and content. Your changes stay in the editor until you press Publish.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancel</AlertDialogCancel>
            <AlertDialogAction onClick={handleReset}>Reset</AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </AdminLayout>
  )
}

export default AdminHomepagePage
