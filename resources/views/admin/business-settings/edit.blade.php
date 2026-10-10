<x-layouts.app title="Business settings">
    <div class="mx-auto max-w-5xl space-y-6">
        <x-page-heading title="Business settings" description="Manage the store details used throughout the application and on future receipts." />

        <form method="POST" action="{{ route('admin.business-settings.update') }}" enctype="multipart/form-data" class="space-y-6" x-data="{ submitting: false, preview: @js($logoUrl), removeLogo: {{ old('remove_logo') ? 'true' : 'false' }}, previewFile(event) { const file = event.target.files[0]; if (!file) return; this.removeLogo = false; const reader = new FileReader(); reader.onload = (loadEvent) => this.preview = loadEvent.target.result; reader.readAsDataURL(file); } }" @submit="submitting = true">
            @csrf
            @method('PUT')

            <x-responsive-card>
                <div class="space-y-6">
                <div>
                    <h2 class="text-lg font-bold text-slate-950">Store profile</h2>
                    <p class="mt-1 text-sm text-slate-600">The identity and contact details shown to staff and customers.</p>
                </div>

                <div class="grid gap-6 lg:grid-cols-[15rem_minmax(0,1fr)]">
                    <div>
                        <label for="logo" class="form-label">Business logo</label>
                        <div class="grid min-h-40 place-items-center overflow-hidden rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4">
                            <img x-cloak x-show="preview && !removeLogo" :src="preview" alt="Selected business logo preview" class="max-h-32 max-w-full object-contain">
                            <div x-show="!preview || removeLogo" class="text-center text-sm text-slate-500">
                                <svg viewBox="0 0 24 24" class="mx-auto mb-2 size-9" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M4 19V5a2 2 0 0 1 2-2h8l6 6v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2Z"/><path d="M14 3v6h6M8 16l2.5-3 2 2 1.5-2 2 3"/></svg>
                                No logo selected
                            </div>
                        </div>
                        <input id="logo" name="logo" type="file" accept="image/jpeg,image/png,image/webp" class="mt-3 block min-h-11 w-full cursor-pointer rounded-xl border border-slate-300 bg-white text-sm text-slate-600 file:mr-3 file:min-h-11 file:border-0 file:border-r file:border-slate-300 file:bg-slate-50 file:px-3 file:font-semibold file:text-slate-700 hover:file:bg-slate-100" @change="previewFile($event)" @if($errors->has('logo')) aria-invalid="true" aria-describedby="logo-error" @else aria-describedby="logo-help" @endif>
                        <p id="logo-help" class="mt-1.5 text-xs leading-5 text-slate-500">JPG, PNG, or WebP. Maximum {{ config('business.logo_max_kilobytes') }} KB and 3000 × 3000 pixels.</p>
                        @error('logo')<p id="logo-error" class="form-error">{{ $message }}</p>@enderror

                        @if($settings->logo_path)
                            <label class="mt-3 flex min-h-11 cursor-pointer items-center gap-3 rounded-xl border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700">
                                <input type="hidden" name="remove_logo" value="0">
                                <input type="checkbox" name="remove_logo" value="1" x-model="removeLogo" class="size-5 rounded border-slate-300 text-brand-700 focus:ring-brand-600">
                                Remove current logo
                            </label>
                        @else
                            <input type="hidden" name="remove_logo" value="0">
                        @endif
                    </div>

                    <div class="grid content-start gap-5 sm:grid-cols-2">
                        <div class="sm:col-span-2"><x-input-field name="business_name" label="Store display name" :value="$settings->business_name" required /></div>
                        <x-input-field name="phone" label="Business phone number" type="tel" :value="$settings->phone" required placeholder="+234 800 000 0000" />
                        <x-input-field name="email" label="Business email" type="email" :value="$settings->email" placeholder="store@example.com" />
                        <div class="sm:col-span-2"><x-textarea-field name="address" label="Business address" :value="$settings->address" required rows="4" /></div>
                    </div>
                </div>
                </div>
            </x-responsive-card>

            <div class="grid gap-6 lg:grid-cols-2">
                <x-responsive-card>
                    <div class="space-y-5">
                    <div>
                        <h2 class="text-lg font-bold text-slate-950">Receipt configuration</h2>
                        <p class="mt-1 text-sm text-slate-600">These details will be reused by the sales receipt module.</p>
                    </div>
                    <x-textarea-field name="receipt_footer_message" label="Receipt footer message" :value="$settings->receipt_footer_message" rows="4" help="For example, a thank-you message or return policy summary." />
                    <label class="flex min-h-11 cursor-pointer items-center gap-3 rounded-xl border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700">
                        <input type="hidden" name="show_logo_on_receipts" value="0">
                        <input type="checkbox" name="show_logo_on_receipts" value="1" @checked(old('show_logo_on_receipts', $settings->show_logo_on_receipts)) class="size-5 rounded border-slate-300 text-brand-700 focus:ring-brand-600">
                        Show the business logo on supported receipts
                    </label>
                    </div>
                </x-responsive-card>

                <x-responsive-card>
                    <div class="space-y-5">
                    <div>
                        <h2 class="text-lg font-bold text-slate-950">Currency and inventory defaults</h2>
                        <p class="mt-1 text-sm text-slate-600">Values are stored precisely and formatted only for display.</p>
                    </div>
                    <x-dropdown name="currency_code" label="Currency" :options="$currencies" :selected="$settings->currency_code" placeholder="Select currency" required />
                    <x-input-field name="default_low_stock_threshold" label="Default low-stock threshold" type="number" :value="$settings->default_low_stock_threshold" min="0" max="999999999.999" step="0.001" inputmode="decimal" required help="Products may override this default in a later module." />
                    </div>
                </x-responsive-card>
            </div>

            <div class="sticky bottom-3 z-20 flex justify-end rounded-2xl border border-slate-200 bg-white/95 p-3 shadow-lg backdrop-blur sm:static sm:border-0 sm:bg-transparent sm:p-0 sm:shadow-none">
                <x-button type="submit" class="w-full sm:w-auto" x-bind:disabled="submitting">
                    <span x-show="!submitting">Save business settings</span>
                    <span x-cloak x-show="submitting"><x-loading-indicator label="Saving…" /></span>
                </x-button>
            </div>
        </form>
    </div>
</x-layouts.app>
