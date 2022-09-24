<div class="magazine-index-editor">
    <form>
        <table class="table">
            <thead>
                <tr>
                    <th>Page</th>
                    <th>Type</th>
                    <th class="col-2">
                        Title
                        <small class="d-block text-muted fw-normal">
                            Leave blank for game & software reviews if there's no special article title.
                            The name of the game / software will be used automatically.
                        </small>
                    </th>
                    <th>Game</th>
                    <th>Software</th>
                    <th>Individual</th>
                    <th>Score</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($indices as $index => $i)
                    <tr wire:key="index-field-{{ $i->id }}">
                        <td>
                            <input type="number" wire:model.debounce.750ms="issue.indices.{{ $index }}.page"
                                class="form-control" value="{{ $i->page }}" size="4">
                        </td>
                        <td>
                            <select class="form-select" wire:model="issue.indices.{{ $index }}.magazine_index_type_id">
                                <option value="null">-</option>
                                @foreach ($indexTypes->sortBy('name') as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <input type="text" wire:model="issue.indices.{{ $index }}.title"
                                class="form-control">
                        </td>
                        <td>
                            <div class="input-group">
                                <input class="autocomplete form-control" name="game_name" type="search" required
                                    data-autocomplete-endpoint="{{ route('ajax.games') }}" data-autocomplete-key="game_name"
                                    data-autocomplete-id="game_id" data-autocomplete-companion="{{ $i->id }}_game_id"
                                    value="{{ $i->game?->game_name }}" placeholder="Type a game name..." autocomplete="off">
                                <button type="button" class="btn btn-outline-secondary"
                                    wire:click="updateGame({{ $i->id }}, null)">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                            <input type="hidden" name="{{ $i->id }}_game_id"
                                wire:change="updateGame({{ $i->id }}, $event.target.value)"
                                value="{{ $i->game_id }}">
                        </td>
                        <td>
                            <div class="input-group">
                                <input class="autocomplete form-control" name="{{ $i->id }}_software_name"
                                    type="search" required data-autocomplete-endpoint="{{ route('ajax.software') }}"
                                    data-autocomplete-key="name" data-autocomplete-id="id"
                                    data-autocomplete-companion="{{ $i->id }}_menu_software_id"
                                    value="{{ $i->menuSoftware?->name }}" placeholder="Type a software name..."
                                    autocomplete="off">
                                <button type="button" class="btn btn-outline-secondary"
                                    wire:click="updateSoftware({{ $i->id }}, null)">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                            <input type="hidden" name="{{ $i->id }}_menu_software_id"
                                wire:change="updateSoftware({{ $i->id }}, $event.target.value)"
                                value="{{ $i->menu_software_id }}">
                        </td>
                        <td>
                            <div class="input-group">
                                <input class="autocomplete form-control" name="{{ $i->id }}_individual_name"
                                    type="search" required data-autocomplete-endpoint="{{ route('ajax.individuals') }}"
                                    data-autocomplete-key="ind_name" data-autocomplete-id="ind_id"
                                    data-autocomplete-companion="{{ $i->id }}_individual_id"
                                    value="{{ $i->individual?->ind_name }}" placeholder="Type an individual name..."
                                    autocomplete="off">
                                <button type="button" class="btn btn-outline-secondary"
                                    wire:click="updateIndividual({{ $i->id }}, null)">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                            <input type="hidden" name="{{ $i->id }}_individual_id"
                                wire:change="updateIndividual({{ $i->id }}, $event.target.value)"
                                value="{{ $i->individual_id }}">
                        </td>
                        <td>
                            <input type="text" wire:model="issue.indices.{{ $index }}.score"
                                class="form-control" size="3">
                        </td>
                        <td>
                            <button title="Delete" class="btn" type="button"
                                wire:click="deleteRow({{ $i }})">
                                <i class="fas fa-trash fa-fw text-danger" aria-hidden="true"></i>
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <button class="btn btn-primary" type="button" wire:click="addRow">Add row</button>
        <button class="btn btn-success" type="button" wire:click="save">Save</button>
        <div class="form-check d-inline-block ms-3">
            <input class="form-check-input" type="checkbox" wire:model="sort" id="autosort">
            <label class="form-check-label" for="autosort">Auto-sort</label>
        </div>
    </form>

</div>
