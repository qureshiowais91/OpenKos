<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Settings\UpdateBranding;
use App\Actions\Settings\UpdateSettings;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateBrandingRequest;
use App\Models\Setting;
use App\Services\Localization\ApplicationLocale;
use App\Services\Settings\InstallationCurrencySettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class GeneralController extends Controller
{
    public function __construct(
        private UpdateSettings $updateSettings,
        private UpdateBranding $updateBranding,
        private ApplicationLocale $locale,
        private InstallationCurrencySettings $currencies,
    ) {}

    public function edit(): Response
    {
        $settings = Setting::some([
            'site_name',
            'country_code',
            'locale',
            'currency',
            'timezone',
            'lease_id_prefix',
            'invoice_id_prefix',
            'invoice_pdf_enabled',
        ]);
        $settings['locale'] = $this->locale->resolve($settings['locale'] ?? null);
        $settings['currency'] = $this->currencies->default();
        $settings['supported_currencies'] = $this->currencies->supported();

        return Inertia::render('settings/general', [
            'settings' => $settings,
            'regional' => config('regional'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        if (is_string($request->input('locale'))) {
            $normalizedLocale = $this->locale->normalize($request->input('locale'));

            if ($normalizedLocale !== null) {
                $request->merge(['locale' => $normalizedLocale]);
            }
        }

        $this->normalizeCurrencyInput($request);

        // Regional settings are pinned to config/regional.php; the validation
        // rules below only accept the India values, so nothing here can
        // override the application-wide configuration.
        return $this->persistUpdate($request);
    }

    private function persistUpdate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'site_name' => ['sometimes', 'required', 'string', 'max:255'],
            'country_code' => ['sometimes', 'required', 'string', 'size:2', 'in:IN'],
            'locale' => [
                'sometimes',
                'required',
                'string',
                'max:10',
                Rule::in(array_keys($this->locale->options())),
            ],
            'currency' => ['sometimes', 'required', 'string', 'size:3', 'in:INR'],
            'supported_currencies' => ['sometimes', 'required', 'array', 'list', 'min:1'],
            'supported_currencies.*' => ['required', 'string', 'size:3', 'in:INR', 'distinct:strict'],
            'timezone' => ['sometimes', 'required', 'string', 'in:Asia/Kolkata'],
            'lease_id_prefix' => ['sometimes', 'required', 'string', 'max:10', 'regex:/^[A-Z]+$/'],
            'invoice_id_prefix' => ['sometimes', 'required', 'string', 'max:10', 'regex:/^[A-Z]+$/'],
            'invoice_pdf_enabled' => ['sometimes', 'boolean'],
        ]);

        if (array_key_exists('locale', $validated)) {
            $validated['locale'] = $this->locale->normalize($validated['locale']);
        }

        $this->updateSettings->execute($validated, $request->user());

        $this->locale->apply($validated['locale'] ?? null);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('General settings updated.')]);

        return back();
    }

    private function normalizeCurrencyInput(Request $request): void
    {
        if ($request->exists('currency') && is_string($request->input('currency'))) {
            $request->merge(['currency' => strtoupper(trim($request->input('currency')))]);
        }

        if ($request->exists('supported_currencies') && is_array($request->input('supported_currencies'))) {
            $request->merge([
                'supported_currencies' => array_map(
                    static fn (mixed $currency): mixed => is_string($currency)
                        ? strtoupper(trim($currency))
                        : $currency,
                    $request->input('supported_currencies'),
                ),
            ]);
        }
    }

    public function updateBranding(UpdateBrandingRequest $request, string $asset): RedirectResponse
    {
        $this->updateBranding->execute($asset, $request->file('file'), $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':asset updated.', ['asset' => ucfirst($asset)])]);

        return back();
    }

    public function removeBranding(Request $request, string $asset): RedirectResponse
    {
        abort_unless($request->user()?->isOwner(), 403);

        $this->updateBranding->remove($asset, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Default :asset restored.', ['asset' => ucfirst($asset)])]);

        return back();
    }
}
