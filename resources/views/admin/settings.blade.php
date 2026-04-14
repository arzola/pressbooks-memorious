<div class="wrap">
    <h1>{{ __('Pressbooks Memorious Search Settings', 'pressbooks-memorious') }}</h1>

    <form method="post" action="{!! admin_url('admin.php') !!}">
        {!! wp_nonce_field('pb_memorious_save_settings', '_wpnonce', true, false) !!}
        <input type="hidden" name="action" value="pb_memorious_save_settings" />

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="typesense_nodes">{{ __('Typesense Nodes', 'pressbooks-memorious') }}</label>
                </th>
                <td>
                    <input type="text"
                           name="pb_memorious_settings[typesense_nodes]"
                           id="typesense_nodes"
                           value="{{ $settings['typesense_nodes'] ?? '' }}"
                           class="regular-text"
                           placeholder="search.example.com:443:https" />
                    <p class="description">
                        {{ __('Comma-separated list of host:port:protocol', 'pressbooks-memorious') }}
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="typesense_admin_key">{{ __('Admin API Key', 'pressbooks-memorious') }}</label>
                </th>
                <td>
                    <input type="password"
                           name="pb_memorious_settings[typesense_admin_key]"
                           id="typesense_admin_key"
                           value="{{ $settings['typesense_admin_key'] ?? '' }}"
                           class="regular-text" />
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="typesense_search_key">{{ __('Search-Only API Key', 'pressbooks-memorious') }}</label>
                </th>
                <td>
                    <input type="password"
                           name="pb_memorious_settings[typesense_search_key]"
                           id="typesense_search_key"
                           value="{{ $settings['typesense_search_key'] ?? '' }}"
                           class="regular-text" />
                </td>
            </tr>
            <tr>
                <th scope="row">{{ __('Enable in Admin', 'pressbooks-memorious') }}</th>
                <td>
                    <input type="checkbox"
                           name="pb_memorious_settings[enabled_admin]"
                           value="1"
                           {{ !empty($settings['enabled_admin']) ? 'checked' : '' }} />
                </td>
            </tr>
            <tr>
                <th scope="row">{{ __('Index Private Books', 'pressbooks-memorious') }}</th>
                <td>
                    <input type="checkbox"
                           name="pb_memorious_settings[index_private_books]"
                           value="1"
                           {{ !empty($settings['index_private_books']) ? 'checked' : '' }} />
                    <p class="description">
                        {{ __('Content is still access-controlled at query time.', 'pressbooks-memorious') }}
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">{{ __('Index Draft Content', 'pressbooks-memorious') }}</th>
                <td>
                    <input type="checkbox"
                           name="pb_memorious_settings[index_draft_content]"
                           value="1"
                           {{ !empty($settings['index_draft_content']) ? 'checked' : '' }} />
                </td>
            </tr>
            <tr>
                <th scope="row">{{ __('Search Theme', 'pressbooks-memorious') }}</th>
                <td>
                    <fieldset>
                        @php $themes = \PressbooksMemorious\Admin\SearchAdmin::getThemeLabels(); @endphp
                    @php $swatches = [
                        'scholarly' => 'linear-gradient(135deg, #f5f0e8 50%, #8b2232 50%)',
                        'modern' => 'linear-gradient(135deg, #f0f2f8 50%, #3b5bd5 50%)',
                        'pressbooks' => 'linear-gradient(135deg, #efefef 50%, #b40026 50%)',
                    ]; @endphp
                        @foreach ($themes as $value => $label)
                            <label style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                                <input type="radio"
                                       name="pb_memorious_settings[theme]"
                                       value="{{ $value }}"
                                       {{ ($settings['theme'] ?? 'scholarly') === $value ? 'checked' : '' }} />
                                <span style="display: inline-block; width: 24px; height: 24px; border-radius: 4px; background: {{ $swatches[$value] }}; border: 1px solid #ddd; flex-shrink: 0;"></span>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                        <p class="description">
                            {{ __('Visual theme for the search bar and results page.', 'pressbooks-memorious') }}
                        </p>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <th scope="row">{{ __('Max Retries', 'pressbooks-memorious') }}</th>
                <td>
                    <input type="number"
                           name="pb_memorious_settings[max_retries]"
                           value="{{ $settings['max_retries'] ?? 3 }}"
                           min="1" max="10" class="small-text" />
                </td>
            </tr>
            <tr>
                <th scope="row">{{ __('Batch Size', 'pressbooks-memorious') }}</th>
                <td>
                    <input type="number"
                           name="pb_memorious_settings[batch_size]"
                           value="{{ $settings['batch_size'] ?? 50 }}"
                           min="10" max="500" class="small-text" />
                    <p class="description">
                        {{ __('Jobs processed per cron run.', 'pressbooks-memorious') }}
                    </p>
                </td>
            </tr>
        </table>

        <h2>{{ __('Index Management', 'pressbooks-memorious') }}</h2>
        <p>
            <button type="button" class="button" id="pb-memorious-reindex-all">
                {{ __('Reindex All Books', 'pressbooks-memorious') }}
            </button>
            <button type="button" class="button" id="pb-memorious-create-collections">
                {{ __('Reset Collections', 'pressbooks-memorious') }}
            </button>
        </p>

        @php
            $failed = app('db')->table('pressbooks_memorious_index_jobs')
                ->where('status', 'failed')
                ->count();
        @endphp

        @if($failed > 0)
            <div class="notice notice-warning">
                <p>
                    {{ sprintf(__('%d failed indexing jobs. Check the error details in the jobs table.', 'pressbooks-memorious'), $failed) }}
                </p>
            </div>
        @endif

        <?php submit_button(); ?>
     </form>
</div>

<script>
(function() {
    var ajaxUrl = '{{ $ajax_url }}';
    var nonce = '{{ $nonce }}';

    document.getElementById('pb-memorious-reindex-all').addEventListener('click', function() {
        if (! confirm('Reindex all books? This may take a while.')) return;
        var btn = this;
        btn.disabled = true;
        btn.textContent = 'Queuing...';

        fetch(ajaxUrl + '?action=pb_memorious_reindex_all&_ajax_nonce=' + nonce)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    btn.textContent = data.data.message;
                } else {
                    btn.textContent = 'Error: ' + (data.data.message || 'Unknown error');
                }
                setTimeout(function() {
                    btn.disabled = false;
                    btn.textContent = 'Reindex All Books';
                }, 5000);
            })
            .catch(function(err) {
                btn.textContent = 'Request failed';
                setTimeout(function() {
                    btn.disabled = false;
                    btn.textContent = 'Reindex All Books';
                }, 3000);
            });
    });

    document.getElementById('pb-memorious-create-collections').addEventListener('click', function() {
        var btn = this;
        btn.disabled = true;
        btn.textContent = 'Creating...';

        fetch(ajaxUrl + '?action=pb_memorious_create_collections&_ajax_nonce=' + nonce)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    btn.textContent = data.data.message;
                } else {
                    btn.textContent = 'Error: ' + (data.data.message || 'Unknown error');
                }
                setTimeout(function() {
                    btn.disabled = false;
                    btn.textContent = 'Create Collections';
                }, 5000);
            })
            .catch(function(err) {
                btn.textContent = 'Request failed';
                setTimeout(function() {
                    btn.disabled = false;
                    btn.textContent = 'Create Collections';
                }, 3000);
            });
    });
})();
</script>
