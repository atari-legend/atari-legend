<div>
    @if (! $reviewing)
        {{-- ----------------------------------------------------------------
             Step 1 — upload
        ----------------------------------------------------------------- --}}
        <div class="card mb-3 bg-light">
            <div class="card-body">
                <h2 class="card-title fs-4">Upload your filled-in template</h2>

                <div class="mb-2">
                    <input type="file" wire:model="file"
                        class="form-control @error('file') is-invalid @enderror"
                        accept=".xlsx,.xls">
                    @error('file')
                        <span class="invalid-feedback d-block"><strong>{{ $message }}</strong></span>
                    @enderror
                </div>

                <div wire:loading wire:target="file" class="text-muted">
                    <i class="fas fa-spinner fa-spin fa-fw"></i> Reading the spreadsheet…
                </div>
            </div>
        </div>
    @else
        {{-- ----------------------------------------------------------------
             Step 2 — review & commit
        ----------------------------------------------------------------- --}}
        <div class="card mb-3 @if ($errorCount > 0) border-danger @else border-success @endif">
            <div class="card-body d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    @if ($errorCount > 0)
                        <span class="badge bg-danger">{{ $errorCount }}</span>
                        problem{{ $errorCount > 1 ? 's' : '' }} to resolve before importing.
                    @else
                        <i class="fas fa-check-circle text-success fa-fw"></i>
                        Everything looks good — {{ count($menus) }} menu{{ count($menus) > 1 ? 's' : '' }} ready to import.
                    @endif
                </div>
                <div>
                    <button type="button" class="btn btn-outline-secondary" wire:click="startOver"
                        wire:loading.attr="disabled" wire:target="runImport">
                        <i class="fas fa-undo fa-fw"></i> Start over
                    </button>
                    <button type="button" class="btn btn-success" wire:click="runImport"
                        wire:target="runImport" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="runImport">
                            <i class="fas fa-file-import fa-fw"></i> Import {{ count($menus) }} menu{{ count($menus) > 1 ? 's' : '' }}
                        </span>
                        <span wire:loading wire:target="runImport">
                            <i class="fas fa-spinner fa-spin fa-fw"></i> Importing…
                        </span>
                    </button>
                </div>
            </div>
            @if ($importAttempted && $errorCount > 0)
                <div class="card-footer text-danger">
                    <i class="fas fa-triangle-exclamation fa-fw"></i>
                    Please resolve the {{ $errorCount }} highlighted problem{{ $errorCount > 1 ? 's' : '' }} below before importing.
                </div>
            @endif
        </div>

        @foreach ($menus as $mi => $menu)
            @php($mainReleaseGameIds = $this->menuMainReleaseGameIds($mi))
            <div class="card mb-3" wire:key="menu-{{ $mi }}">
                <div class="card-header">
                    <div class="row g-3">
                        <div class="col-6 col-md-2">
                            <label class="form-label">Menu number</label>
                            <input type="number" class="form-control"
                                wire:model.live.debounce.500ms="menus.{{ $mi }}.number">
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label">Issue</label>
                            <input type="text" class="form-control"
                                wire:model.live.debounce.500ms="menus.{{ $mi }}.issue">
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label">Version</label>
                            <input type="text" class="form-control"
                                wire:model="menus.{{ $mi }}.version">
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label">Release date</label>
                            <input type="date" class="form-control"
                                wire:model="menus.{{ $mi }}.date">
                        </div>
                    </div>
                    @if ($menu['existing_menu_id'])
                        <div class="text-info mt-2">
                            <i class="fas fa-circle-info fa-fw"></i>
                            Merging into existing menu {{ $menu['existing_menu_label'] }} — content will be added; existing details are kept.
                        </div>
                    @endif
                </div>

                <div class="card-body">
                    @foreach ($menu['disks'] as $di => $disk)
                        <div class="border rounded p-3 mb-3 bg-light" wire:key="menu-{{ $mi }}-disk-{{ $di }}">
                            <div class="row g-3 mb-2">
                                <div class="col-6 col-md-2">
                                    <label class="form-label">Disk part</label>
                                    <input type="text" class="form-control"
                                        wire:model.live.debounce.500ms="menus.{{ $mi }}.disks.{{ $di }}.part">
                                    @if ($disk['existing_disk_id'])
                                        <div class="form-text text-info">
                                            <i class="fas fa-circle-info fa-fw"></i>
                                            Adding to existing disk {{ $disk['existing_disk_label'] }}
                                        </div>
                                    @else
                                        <div class="form-text">New disk — will be created</div>
                                    @endif
                                </div>
                                <div class="col-6 col-md-4">
                                    <label class="form-label">Condition</label>
                                    <select class="form-select @if (empty($disk['existing_disk_id']) && empty($disk['condition_id'])) is-invalid @endif"
                                        wire:model.live="menus.{{ $mi }}.disks.{{ $di }}.condition">
                                        <option value="">— choose —</option>
                                        @foreach ($conditions as $condition)
                                            <option value="{{ $condition->name }}">{{ $condition->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback">Pick a condition.</div>
                                </div>
                                <div class="col-md-6">
                                    @php($individualCompanion = "ind_{$mi}_{$di}")
                                    <label class="form-label">Donated by</label>
                                    <div class="input-group">
                                        <input class="autocomplete form-control @if ($disk['donated_by'] && empty($disk['donated_by_id'])) is-invalid @endif"
                                            type="search"
                                            data-autocomplete-endpoint="{{ route('ajax.individuals') }}"
                                            data-autocomplete-key="ind_name" data-autocomplete-id="ind_id"
                                            data-autocomplete-companion="{{ $individualCompanion }}"
                                            data-search-method="setDonatedBy" data-search-args="[{{ $mi }}, {{ $di }}]"
                                            value="{{ $disk['donated_by'] }}"
                                            placeholder="Type an individual name…" autocomplete="off">
                                        <button type="button" class="btn btn-outline-secondary js-clear-autocomplete"
                                            wire:click="clearIndividual({{ $mi }}, {{ $di }})">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </div>
                                    <input type="hidden" name="{{ $individualCompanion }}"
                                        wire:change="updateIndividual({{ $mi }}, {{ $di }}, $event.target.value)"
                                        value="{{ $disk['donated_by_id'] }}">
                                    @if ($disk['donated_by'] && empty($disk['donated_by_id']))
                                        <div class="text-danger small mt-1">Unknown individual — pick a match or clear the field.</div>
                                    @endif
                                </div>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Notes</label>
                                    <textarea rows="3" class="form-control"
                                        wire:model="menus.{{ $mi }}.disks.{{ $di }}.notes"></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Scrolltext</label>
                                    <textarea rows="3" class="form-control"
                                        wire:model="menus.{{ $mi }}.disks.{{ $di }}.scrolltext"></textarea>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table align-top mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width: 6rem">Order</th>
                                            <th style="width: 15rem">Link as</th>
                                            <th style="min-width: 18rem">Game / Software</th>
                                            <th style="width: 11rem">Sub-type</th>
                                            <th style="width: 9rem">Version</th>
                                            <th style="width: 11rem">Requirements</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($disk['contents'] as $ci => $content)
                                            @php($errors = $this->contentErrors($content, $mainReleaseGameIds))
                                            <tr wire:key="menu-{{ $mi }}-disk-{{ $di }}-content-{{ $ci }}" class="align-top">
                                                <td @class(['border-start border-danger border-3' => count($errors) > 0])>
                                                    <input type="text"
                                                        @class(['form-control', 'is-invalid' => $content['order'] === null || $content['order'] === '' || ! is_numeric($content['order'])])
                                                        wire:model.live.debounce.500ms="menus.{{ $mi }}.disks.{{ $di }}.contents.{{ $ci }}.order">
                                                </td>
                                                <td>
                                                    <select class="form-select"
                                                        wire:model.live="menus.{{ $mi }}.disks.{{ $di }}.contents.{{ $ci }}.link_mode">
                                                        @foreach ($linkModes as $value => $label)
                                                            <option value="{{ $value }}">{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td>
                                                    @if ($content['link_mode'] === 'software')
                                                        @php($companion = "sw_{$mi}_{$di}_{$ci}")
                                                        <div class="input-group">
                                                            <input class="autocomplete form-control @if (count($errors)) is-invalid @endif"
                                                                type="search"
                                                                data-autocomplete-endpoint="{{ route('ajax.software') }}"
                                                                data-autocomplete-key="name" data-autocomplete-id="id"
                                                                data-autocomplete-companion="{{ $companion }}"
                                                                data-search-method="setSoftwareSearch" data-search-args="[{{ $mi }}, {{ $di }}, {{ $ci }}]"
                                                                value="{{ $content['software_name'] ?? $content['query'] }}"
                                                                placeholder="Type a software name…" autocomplete="off">
                                                            <button type="button" class="btn btn-outline-secondary js-clear-autocomplete"
                                                                wire:click="updateSoftware({{ $mi }}, {{ $di }}, {{ $ci }}, null)">
                                                                <i class="fa-solid fa-xmark"></i>
                                                            </button>
                                                        </div>
                                                        <input type="hidden" name="{{ $companion }}"
                                                            wire:change="updateSoftware({{ $mi }}, {{ $di }}, {{ $ci }}, $event.target.value)"
                                                            value="{{ $content['software_id'] }}">
                                                    @else
                                                        @php($companion = "g_{$mi}_{$di}_{$ci}")
                                                        <div class="input-group">
                                                            <input class="autocomplete form-control @if (count($errors)) is-invalid @endif"
                                                                type="search"
                                                                data-autocomplete-endpoint="{{ route('admin.ajax.games') }}"
                                                                data-autocomplete-key="game_name" data-autocomplete-id="game_id"
                                                                data-autocomplete-companion="{{ $companion }}"
                                                                data-autocomplete-max="20"
                                                                data-search-method="setGameSearch" data-search-args="[{{ $mi }}, {{ $di }}, {{ $ci }}]"
                                                                value="{{ $content['game_name'] ?? $content['query'] }}"
                                                                placeholder="Type a game name…" autocomplete="off">
                                                            <button type="button" class="btn btn-outline-secondary js-clear-autocomplete"
                                                                wire:click="updateGame({{ $mi }}, {{ $di }}, {{ $ci }}, null)">
                                                                <i class="fa-solid fa-xmark"></i>
                                                            </button>
                                                        </div>
                                                        <input type="hidden" name="{{ $companion }}"
                                                            wire:change="updateGame({{ $mi }}, {{ $di }}, {{ $ci }}, $event.target.value)"
                                                            value="{{ $content['game_id'] }}">
                                                    @endif

                                                    @if ($content['name'])
                                                        <div class="form-text mt-1">From sheet: “{{ $content['name'] }}”</div>
                                                    @endif

                                                    @foreach ($errors as $error)
                                                        <div class="text-danger small">{{ $error }}</div>
                                                    @endforeach
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control"
                                                        wire:model.live.debounce.500ms="menus.{{ $mi }}.disks.{{ $di }}.contents.{{ $ci }}.subtype">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control"
                                                        wire:model="menus.{{ $mi }}.disks.{{ $di }}.contents.{{ $ci }}.version">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control"
                                                        wire:model="menus.{{ $mi }}.disks.{{ $di }}.contents.{{ $ci }}.requirements">
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-muted">This disk has no content.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    @endif
</div>
