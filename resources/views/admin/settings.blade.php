<div class="wrap">
    <h1>{{ __('Pressbooks Borges Search Settings', 'pressbooks-borges') }}</h1>

    <form method="post" action="edit.php?action=pb_borges_save_settings">
        @csrf

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="typesense_nodes">{{ __('Typesense Nodes', 'pressbooks-borges') }}</label>
                </th>
                <td>
                    <input type="text"
                           name="pb_borges_settings[typesense_nodes]"
                           id="typesense_nodes"
                           value="{{ $settings['typesense_nodes'] ?? '' }}"
                           class="regular-text"
                           placeholder="search.example.com:443:https" />
                    <p class="description">
                        {{ __('Comma-separated list of host:port:protocol', 'pressbooks-borges') }}
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="typesense_admin_key">{{ __('Admin API Key', 'pressbooks-borges') }}</label>
                </th>
                <td>
                    <input type="password"
                           name="pb_borges_settings[typesense_admin_key]"
                           id="typesense_admin_key"
                           value="{{ $settings['typesense_admin_key'] ?? '' }}"
                           class="regular-text" />
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="typesense_search_key">{{ __('Search-Only API Key', 'pressbooks-borges') }}</label>
                </th>
                <td>
                    <input type="password"
                           name="pb_borges_settings[typesense_search_key]"
                           id="typesense_search_key"
                           value="{{ $settings['typesense_search_key'] ?? '' }}"
                           class="regular-text" />
                </td>
            </tr>
            <tr>
                <th scope="row">{{ __('Enable in Admin', 'pressbooks-borges') }}</th>
                <td>
                    <input type="checkbox"
                           name="pb_borges_settings[enabled_admin]"
                           value="1"
                           @if(!empty($settings['enabled_admin'])) checked @endif />
                </td>
            </tr>
            <tr>
                <th scope="row">{{ __('Enable in Webbook', 'pressbooks-borges') }}</th>
                <td>
                    <input type="checkbox"
                           name="pb_borges_settings[enabled_webbook]"
                           value="1"
                           @if(!empty($settings['enabled_webbook'])) checked @endif />
                </td>
            </tr>
            <tr>
                <th scope="row">{{ __('Index Private Books', 'pressbooks-borges') }}</th>
                <td>
                    <input type="checkbox"
                           name="pb_borges_settings[index_private_books]"
                           value="1"
                           @if(!empty($settings['index_private_books'])) checked @endif />
                    <p class="description">
                        {{ __('Content is still access-controlled at query time.', 'pressbooks-borges') }}
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">{{ __('Index Draft Content', 'pressbooks-borges') }}</th>
                <td>
                    <input type="checkbox"
                           name="pb_borges_settings[index_draft_content]"
                           value="1"
                           @if(!empty($settings['index_draft_content'])) checked @endif />
                </td>
            </tr>
            <tr>
                <th scope="row">{{ __('Max Retries', 'pressbooks-borges') }}</th>
                <td>
                    <input type="number"
                           name="pb_borges_settings[max_retries]"
                           value="{{ $settings['max_retries'] ?? 3 }}"
                           min="1" max="10" class="small-text" />
                </td>
            </tr>
            <tr>
                <th scope="row">{{ __('Batch Size', 'pressbooks-borges') }}</th>
                <td>
                    <input type="number"
                           name="pb_borges_settings[batch_size]"
                           value="{{ $settings['batch_size'] ?? 50 }}"
                           min="10" max="500" class="small-text" />
                    <p class="description">
                        {{ __('Jobs processed per cron run.', 'pressbooks-borges') }}
                    </p>
                </td>
            </tr>
        </table>

        <h2>{{ __('Index Management', 'pressbooks-borges') }}</h2>
        <p>
            <button type="button" class="button" id="pb-borges-reindex-all">
                {{ __('Reindex All Books', 'pressbooks-borges') }}
            </button>
            <button type="button" class="button" id="pb-borges-create-collections">
                {{ __('Create Collections', 'pressbooks-borges') }}
            </button>
        </p>

        @php
            $failed = app('db')->table('pressbooks_borges_index_jobs')
                ->where('status', 'failed')
                ->count();
        @endphp

        @if($failed > 0)
            <div class="notice notice-warning">
                <p>
                    {{ sprintf(__('%d failed indexing jobs. Check the error details in the jobs table.', 'pressbooks-borges'), $failed) }}
                </p>
            </div>
        @endif

        <?php submit_button(); ?>
    </form>
</div>
