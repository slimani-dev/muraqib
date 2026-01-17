@php
    $effectiveHeight = $getEffectiveHeight();
    $hasInlineLabel = $hasInlineLabel();
    $isConcealed = $isConcealed();
    $isDisabled = $isDisabled();
    $rows = $getRows();
    $shouldAutosize = $shouldAutosize();
    $statePath = $getStatePath();
    $aceUrl = $getUrl();
    $config = $getConfig();
    $enabledExtensions = $getEnabledExtensions();
    $editorOptions = $getEditorOptions();
    $completions = $getCompletions();
    $snippets = $getSnippets();
    $enableCustomCompletions = $isCustomCompletionsEnabled();
    $wordWrap = $getWordWrap();
    $hasToolbar = !empty($toolbarButtons);
    $toolbarButtons = method_exists($field, 'getToolbarButtons') ? $field->getToolbarButtons() : [];
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <x-slot name="label" @class([
        'sm:pt-1.5' => $hasInlineLabel,
    ])>
        <span id="rd-ace-editor-label-{{ $statePath }}">{{ $getLabel() }}</span>
    </x-slot>
    <div @class([
        'has-error' => $errors->has($statePath),
    ])>
        <div wire:ignore x-ignore x-load
             x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('filament-ace-editor', 'riodwanto/filament-ace-editor') }}"
             x-data="aceEditorComponent({
                state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')", isOptimisticallyLive: false) }},
                statePath: '{{ $statePath }}',
                placeholder: @js($getPlaceholder()),
                aceUrl: '{{ $aceUrl }}',
                extensions: @js($enabledExtensions),
                config: @js($config),
                options: @js($editorOptions),
                darkTheme: @js($getDarkTheme()),
                disableDarkTheme: @js($isDisableDarkTheme()),
                completions: @js($completions),
                snippets: @js($snippets),
                enableCustomCompletions: @js($enableCustomCompletions),
                toolbarButtons: @js($toolbarButtons),
                hasToolbar: @js($hasToolbar),
                showStatusBar: @js($isStatusBarEnabled()),
                statusBarOptions: @js($getStatusBarOptions()),
                extensionLoadingConfig: @js($getExtensionLoadingConfig()),
                keyboardAccessibility: @js($isKeyboardAccessibilityEnabled()),
                screenReaderSupport: @js($isScreenReaderSupportEnabled()),
                ariaLabels: @js($getAriaLabels()),
                currentFontSize: @js($getFontSize() ?? '14px'),
                currentWordWrap: @js($wordWrap ?? 'off'),
                overscrollOptions: @js($getOverscrollOptions()),
                isFullscreen: false,
                isDisabled: @js($isDisabled),
            })" x-ref="aceCodeEditor" @class([
                'rd-ace-editor rd-ace-transition',
                'has-error' => $errors->has($statePath),
                'rd-ace-editor-disabled' => $isDisabled,
            ]) role="group"
             aria-labelledby="rd-ace-editor-label-{{ $statePath }}" @if ($errors->has($statePath))
                 aria-describedby="rd-ace-editor-error-{{ $statePath }}"
             @endif @if ($isDisabled) aria-disabled="true" @endif
            {{ $getExtraInputAttributeBag()->merge([]) }}>
            <!-- Header bar  -->
            @if($isHeaderEnabled())
                <div class="rd-ace-editor-header">
                    <div class="rd-ace-editor-header-title" id="rd-ace-editor-header-title-{{ $statePath }}">
                        {{ $getHeaderTitle() }}
                    </div>
                    <div class="rd-ace-editor-header-actions">
                        <!-- Fullscreen action -->
                        <button type="button" @click="!isDisabled && toggleFullscreen()"
                                x-bind:title="isFullscreen ? 'Exit Fullscreen (Esc)' : 'Toggle Fullscreen (Ctrl+Shift+F)'"
                                x-bind:class="isFullscreen ? 'rd-ace-editor-header-fullscreen rd-ace-exit-fullscreen' : 'rd-ace-editor-header-fullscreen rd-ace-enter-fullscreen'"
                                x-bind:aria-label="isFullscreen ? 'Exit fullscreen mode (Escape key)' : 'Toggle fullscreen mode (Ctrl+Shift+F or Ctrl+F11)'"
                                :disabled="isDisabled" aria-controls="rd-ace-editor-content-{{ $statePath }}"
                                class="rd-ace-focus-ring">
                            <span class="sr-only">
                                <x-mdi-fullscreen></x-mdi-fullscreen>
                            </span>
                        </button>
                    </div>
                </div>
            @endif

            <!-- Toolbar -->
            <div x-show="hasToolbar" x-transition class="rd-ace-editor-toolbar"
                 :class="!{{ $isHeaderEnabled() ? 'true' : 'false' }} ? 'rd-ace-toolbar-no-header' : ''" role="toolbar"
                 aria-label="Editor toolbar">
                <div class="rd-ace-editor-toolbar-group" role="group" aria-label="Editor actions">
                    <template x-for="button in toolbarButtons" :key="button">
                        <button type="button" @click="!isDisabled && executeToolbarAction(button, $event)"
                                x-tooltip="{ content: getButtonTitle(button), theme: $store.theme }"
                                :class="`rd-ace-editor-btn rd-ace-btn-${button} rd-ace-variant-ghost rd-ace-size-sm ${getButtonActiveState(button) ? 'rd-ace-btn-active' : ''} rd-ace-focus-ring`"
                                :disabled="getButtonDisabled(button) || isDisabled" :aria-label="getButtonTitle(button)"
                                :data-button="button">
                            <span class="sr-only" x-text="getButtonTitle(button)"></span>
                        </button>
                    </template>
                </div>

                <div class="rd-ace-editor-toolbar-controls" role="group" aria-label="Editor settings">
                    <!-- Word wrap -->
                    <div class="rd-ace-editor-control">
                        <label for="word-wrap-select-{{ $statePath }}">Word Wrap</label>
                        <select id="word-wrap-select-{{ $statePath }}" x-model="currentWordWrap"
                                @change="!isDisabled && changeWordWrap($event.target.value)" :disabled="isDisabled"
                                class="rd-ace-focus-ring rd-ace-editor-select" aria-label="Select word wrap mode">
                            <option value="off" x-bind:selected="currentWordWrap === 'off'">Off</option>
                            <option value="soft" x-bind:selected="currentWordWrap === 'soft'">Soft</option>
                            <option value="hard" x-bind:selected="currentWordWrap === 'hard'">Hard</option>
                        </select>
                    </div>

                    <!-- Font size -->
                    <div class="rd-ace-editor-control">
                        <label for="font-size-select-{{ $statePath }}">Font Size</label>
                        <select id="font-size-select-{{ $statePath }}" x-model="currentFontSize"
                                @change="!isDisabled && changeFontSize($event.target.value)" :disabled="isDisabled"
                                class="rd-ace-focus-ring rd-ace-editor-select" aria-label="Select font size">
                            <option value="10px" x-bind:selected="currentFontSize === '10px'">10px</option>
                            <option value="12px" x-bind:selected="currentFontSize === '12px'">12px</option>
                            <option value="14px" x-bind:selected="currentFontSize === '14px'">14px</option>
                            <option value="16px" x-bind:selected="currentFontSize === '16px'">16px</option>
                            <option value="18px" x-bind:selected="currentFontSize === '18px'">18px</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Editor -->
            <div class="rd-ace-editor-wrapper">
                <div x-ref="aceCodeEditorInner" id="rd-ace-editor-content-{{ $statePath }}" @class([
                    'rd-ace-editor-content rd-ace-transition',
                    'rd-ace-content-with-header' => $isHeaderEnabled(),
                    'rd-ace-content-no-header' => !$isHeaderEnabled(),
                    'rd-ace-content-with-toolbar' => $hasToolbar,
                    'rd-ace-content-no-toolbar' => !$hasToolbar,
                    'rd-ace-content-without-status' => !$isStatusBarEnabled(),
                ]) @style([
    $effectiveHeight['height'] ? ('height: ' . $effectiveHeight['height']) : null,
    $effectiveHeight['min-height'] ? ('min-height: ' . $effectiveHeight['min-height']) : null,
    $effectiveHeight['max-height'] ? ('max-height: ' . $effectiveHeight['max-height']) : null,
]) role="application" aria-label="Code editor content"
                     aria-multiline="true" tabindex="0" {{ $getExtraInputAttributeBag() }}></div>


            </div>

            <!-- Validation Feedback -->
            @if ($errors->has($statePath))
                <div id="rd-ace-editor-error-{{ $statePath }}" class="rd-ace-editor-error" role="alert"
                     aria-live="polite">
                    {{ $errors->first($statePath) }}
                </div>
            @endif

        </div>
    </div>

</x-dynamic-component>
