import { router, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { AppearanceTabs } from '@/components/features';
import { InputError } from '@/components/shared';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { t } from '@/lib/i18n';
import {
    edit as editGeneral,
    update as updateGeneral,
} from '@/routes/settings/general';
import {
    destroy as destroyBranding,
    update as updateBranding,
} from '@/routes/settings/general/branding';

type BrandingAsset = 'logo' | 'favicon';

type Regional = {
    country?: {
        name?: string;
        code?: string;
        phone_code?: string;
        postal_code_label?: string;
        postal_code_pattern?: string;
    };
    currency?: { code?: string; symbol?: string; minor_units?: number };
    timezone?: string;
    locale?: {
        app?: string;
        intl?: string;
        fallback?: string;
        options?: Record<string, string>;
    };
    format?: {
        date_format?: string;
        time_format?: string;
        numbering?: string;
        measurement?: string;
    };
};

export default function General({
    settings,
    regional,
}: {
    settings: {
        site_name: string;
        country_code: string;
        locale: string;
        currency: string;
        supported_currencies: string[];
        timezone: string;
        lease_id_prefix: string;
        invoice_id_prefix: string;
        invoice_pdf_enabled: boolean;
    };
    regional: Regional;
}) {
    const { branding } = usePage<{
        branding: {
            logoUrl: string;
            faviconUrl: string;
            hasCustomLogo: boolean;
            hasCustomFavicon: boolean;
            hasConfiguredLogo: boolean;
            hasConfiguredFavicon: boolean;
        };
    }>().props;
    const [uploadingBranding, setUploadingBranding] =
        useState<BrandingAsset | null>(null);
    const [brandingErrors, setBrandingErrors] = useState<
        Record<string, string>
    >({});

    const siteForm = useForm({
        site_name: settings.site_name,
    });

    const referenceForm = useForm({
        lease_id_prefix: settings.lease_id_prefix,
        invoice_id_prefix: settings.invoice_id_prefix,
    });

    const invoicePdfForm = useForm({
        invoice_pdf_enabled: settings.invoice_pdf_enabled,
    });

    function uploadBranding(
        event: FormEvent<HTMLFormElement>,
        asset: BrandingAsset,
    ): void {
        event.preventDefault();

        const form = event.currentTarget;

        setUploadingBranding(asset);
        setBrandingErrors({});

        router.post(updateBranding.url({ asset }), new FormData(form), {
            preserveScroll: true,
            onError: (errors) => setBrandingErrors(errors),
            onFinish: () => {
                setUploadingBranding(null);
                form.reset();
            },
        });
    }

    function removeBranding(asset: BrandingAsset): void {
        setUploadingBranding(asset);
        setBrandingErrors({});

        router.delete(destroyBranding.url({ asset }), {
            preserveScroll: true,
            onError: (errors) => setBrandingErrors(errors),
            onFinish: () => setUploadingBranding(null),
        });
    }

    return (
        <div className="space-y-6">
            <div>
                <h2 className="text-lg font-medium">{t('General settings')}</h2>
                <p className="mt-1 text-sm text-muted-foreground">
                    {t('Manage application-wide settings.')}
                </p>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>{t('Branding')}</CardTitle>
                    <CardDescription>
                        {t(
                            'Customize the logo and favicon used by this installation.',
                        )}
                    </CardDescription>
                </CardHeader>
                <CardContent className="grid gap-6 lg:grid-cols-2">
                    {(
                        [
                            {
                                asset: 'logo',
                                title: 'Website logo',
                                description:
                                    'Shown in application navigation and authentication pages.',
                                url: branding.logoUrl,
                                hasCustom: branding.hasCustomLogo,
                                hasConfigured: branding.hasConfiguredLogo,
                                accept: '.jpg,.jpeg,.png,.webp',
                                formats: 'JPG, PNG, or WebP · 2 MB maximum',
                            },
                            {
                                asset: 'favicon',
                                title: 'Browser favicon',
                                description:
                                    'Shown in browser tabs and bookmarks.',
                                url: branding.faviconUrl,
                                hasCustom: branding.hasCustomFavicon,
                                hasConfigured: branding.hasConfiguredFavicon,
                                accept: '.png,.ico',
                                formats: 'PNG or ICO · 512 KB maximum',
                            },
                        ] as const
                    ).map((item) => (
                        <div
                            key={item.asset}
                            className="space-y-4 rounded-lg border p-4"
                        >
                            <div className="flex items-center gap-4">
                                <div className="flex size-20 items-center justify-center rounded-md border bg-muted/30 p-2">
                                    <img
                                        src={item.url}
                                        alt={t(`${item.title} preview`)}
                                        className="size-full object-contain"
                                    />
                                </div>
                                <div>
                                    <h3 className="font-medium">
                                        {t(item.title)}
                                    </h3>
                                    <p className="text-sm text-muted-foreground">
                                        {t(item.description)}
                                    </p>
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        {item.hasCustom
                                            ? t('Using custom asset')
                                            : t('Using bundled default')}
                                    </p>
                                </div>
                            </div>

                            <form
                                onSubmit={(event) =>
                                    uploadBranding(event, item.asset)
                                }
                                className="space-y-3"
                            >
                                <div className="grid gap-2">
                                    <Label htmlFor={`${item.asset}-file`}>
                                        {t('Upload replacement')}
                                    </Label>
                                    <Input
                                        id={`${item.asset}-file`}
                                        name="file"
                                        type="file"
                                        accept={item.accept}
                                        required
                                        aria-describedby={`${item.asset}-formats`}
                                    />
                                    <p
                                        id={`${item.asset}-formats`}
                                        className="text-xs text-muted-foreground"
                                    >
                                        {t(item.formats)}
                                    </p>
                                    <InputError message={brandingErrors.file} />
                                </div>

                                <div className="flex flex-wrap gap-2">
                                    <Button
                                        type="submit"
                                        disabled={uploadingBranding !== null}
                                    >
                                        {uploadingBranding === item.asset
                                            ? t('Uploading...')
                                            : t('Upload')}
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        disabled={
                                            !item.hasConfigured ||
                                            uploadingBranding !== null
                                        }
                                        onClick={() =>
                                            removeBranding(item.asset)
                                        }
                                    >
                                        {t('Restore default')}
                                    </Button>
                                </div>
                            </form>
                        </div>
                    ))}
                </CardContent>
            </Card>

            <div className="grid gap-6 lg:grid-cols-2 lg:items-start">
                <div className="space-y-6">
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            siteForm.submit(updateGeneral());
                        }}
                    >
                        <Card>
                            <CardHeader>
                                <CardTitle>{t('Site')}</CardTitle>
                                <CardDescription>
                                    {t(
                                        'The name displayed throughout the application.',
                                    )}
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid max-w-xs gap-2">
                                    <Label htmlFor="site_name">
                                        {t('Site name')}
                                    </Label>
                                    <Input
                                        id="site_name"
                                        name="site_name"
                                        value={siteForm.data.site_name}
                                        onChange={(e) =>
                                            siteForm.setData(
                                                'site_name',
                                                e.target.value,
                                            )
                                        }
                                        maxLength={255}
                                        placeholder="GrihaFlow"
                                        required
                                    />
                                    {siteForm.errors.site_name && (
                                        <p className="text-sm text-red-600">
                                            {siteForm.errors.site_name}
                                        </p>
                                    )}
                                </div>
                                <Button disabled={siteForm.processing}>
                                    {t('Save')}
                                </Button>
                            </CardContent>
                        </Card>
                    </form>

                    {/* India-only application: regional settings are pinned
                        in config/regional.php and shown here read-only. */}
                    <Card>
                        <CardHeader>
                            <CardTitle>{t('Localization')}</CardTitle>
                            <CardDescription>
                                {t(
                                    'GrihaFlow is an India-only application. Regional settings are fixed application-wide.',
                                )}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label>{t('Country')}</Label>
                                <Input
                                    readOnly
                                    value={regional?.country?.name ?? 'India'}
                                    className="bg-muted"
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label>{t('Currency')}</Label>
                                <Input
                                    readOnly
                                    value={
                                        regional?.currency?.code
                                            ? `${regional.currency.code} (${regional.currency.symbol ?? ''})`
                                            : 'INR (₹)'
                                    }
                                    className="bg-muted"
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label>{t('Timezone')}</Label>
                                <Input
                                    readOnly
                                    value={regional?.timezone ?? 'Asia/Kolkata'}
                                    className="bg-muted"
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label>{t('Locale')}</Label>
                                <Input
                                    readOnly
                                    value={regional?.locale?.intl ?? 'en-IN'}
                                    className="bg-muted"
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label>{t('Date format')}</Label>
                                <Input
                                    readOnly
                                    value={
                                        regional?.format?.date_format ??
                                        'DD/MM/YYYY'
                                    }
                                    className="bg-muted"
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label>{t('Phone code')}</Label>
                                <Input
                                    readOnly
                                    value={
                                        regional?.country?.phone_code ?? '+91'
                                    }
                                    className="bg-muted"
                                />
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>{t('Appearance')}</CardTitle>
                            <CardDescription>
                                {t('Choose how the application looks for you.')}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <AppearanceTabs />
                        </CardContent>
                    </Card>

                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            invoicePdfForm.transform((data) => ({
                                invoice_pdf_enabled: data.invoice_pdf_enabled
                                    ? '1'
                                    : '0',
                            }));
                            invoicePdfForm.submit(updateGeneral());
                        }}
                    >
                        <Card>
                            <CardHeader>
                                <CardTitle>{t('Invoice PDFs')}</CardTitle>
                                <CardDescription>
                                    {t(
                                        'PDF generation runs in the queue and needs a running worker when enabled.',
                                    )}
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="flex items-center gap-3">
                                    <Switch
                                        id="invoice_pdf_enabled"
                                        checked={
                                            invoicePdfForm.data
                                                .invoice_pdf_enabled
                                        }
                                        onCheckedChange={(checked) =>
                                            invoicePdfForm.setData(
                                                'invoice_pdf_enabled',
                                                checked,
                                            )
                                        }
                                    />
                                    <Label htmlFor="invoice_pdf_enabled">
                                        {invoicePdfForm.data.invoice_pdf_enabled
                                            ? t(
                                                  'Invoice PDF generation is enabled',
                                              )
                                            : t(
                                                  'Invoice PDF generation is disabled',
                                              )}
                                    </Label>
                                </div>
                                <p className="text-sm text-muted-foreground">
                                    {invoicePdfForm.data.invoice_pdf_enabled
                                        ? t(
                                              'Invoice PDFs are generated in the background, stored privately, and reused for downloads and supported reminders.',
                                          )
                                        : t(
                                              'Invoices remain available as web pages. Use the browser print or save-as-PDF flow; reminders include the invoice link without an attachment.',
                                          )}
                                </p>
                                <Button disabled={invoicePdfForm.processing}>
                                    {t('Save')}
                                </Button>
                            </CardContent>
                        </Card>
                    </form>

                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            referenceForm.submit(updateGeneral());
                        }}
                    >
                        <Card>
                            <CardHeader>
                                <CardTitle>{t('References')}</CardTitle>
                                <CardDescription>
                                    {t(
                                        'Prefixes used for auto-generated reference numbers.',
                                    )}
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid max-w-xs gap-2">
                                    <Label htmlFor="lease_id_prefix">
                                        {t('Lease prefix')}
                                    </Label>
                                    <Input
                                        id="lease_id_prefix"
                                        name="lease_id_prefix"
                                        value={
                                            referenceForm.data.lease_id_prefix
                                        }
                                        onChange={(e) =>
                                            referenceForm.setData(
                                                'lease_id_prefix',
                                                e.target.value,
                                            )
                                        }
                                        maxLength={10}
                                        className="font-mono uppercase"
                                        placeholder="LSX"
                                        required
                                    />
                                    <p className="text-xs text-muted-foreground">
                                        {t('Format:')}{' '}
                                        <code className="rounded bg-muted px-1.5 py-0.5 font-mono">
                                            {referenceForm.data.lease_id_prefix}
                                            20260001
                                        </code>
                                    </p>
                                    {referenceForm.errors.lease_id_prefix && (
                                        <p className="text-sm text-red-600">
                                            {
                                                referenceForm.errors
                                                    .lease_id_prefix
                                            }
                                        </p>
                                    )}
                                </div>

                                <div className="grid max-w-xs gap-2">
                                    <Label htmlFor="invoice_id_prefix">
                                        {t('Invoice prefix')}
                                    </Label>
                                    <Input
                                        id="invoice_id_prefix"
                                        name="invoice_id_prefix"
                                        value={
                                            referenceForm.data.invoice_id_prefix
                                        }
                                        onChange={(e) =>
                                            referenceForm.setData(
                                                'invoice_id_prefix',
                                                e.target.value.toUpperCase(),
                                            )
                                        }
                                        maxLength={10}
                                        className="font-mono uppercase"
                                        placeholder="INV"
                                        required
                                    />
                                    <p className="text-xs text-muted-foreground">
                                        {t('Format:')}{' '}
                                        <code className="rounded bg-muted px-1.5 py-0.5 font-mono">
                                            {
                                                referenceForm.data
                                                    .invoice_id_prefix
                                            }
                                            20260001
                                        </code>
                                    </p>
                                    {referenceForm.errors.invoice_id_prefix && (
                                        <p className="text-sm text-red-600">
                                            {
                                                referenceForm.errors
                                                    .invoice_id_prefix
                                            }
                                        </p>
                                    )}
                                </div>

                                <Button disabled={referenceForm.processing}>
                                    {t('Save')}
                                </Button>
                            </CardContent>
                        </Card>
                    </form>
                </div>
            </div>
        </div>
    );
}

General.layout = {
    breadcrumbs: [{ title: 'General settings', href: editGeneral() }],
};
