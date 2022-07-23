<div class="magazine-index-editor">
    <form>
        <table class="table">
            <thead>
                <tr>
                    <th>Page</th>
                    <th>Type</th>
                    <th>Title</th>
                    <th>Game</th>
                    <th>Score</th>
                    <th>Software</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($indices as $index => $i)
                    <tr wire:key="index-field-{{ $i->id }}">
                        <td>
                            <input type="number" wire:model="issue.indexes.{{ $index }}.page"
                                class="form-control" value="{{ $i->page }}" size="3">
                        </td>
                        <td>
                            <select class="form-select">
                                <option>-</option>
                            </select>
                        </td>
                        <td>
                            <input type="text" wire:model="issue.indexes.{{ $index }}.title"
                                class="form-control">
                        </td>
                        <td>
                            <input class="autocomplete form-control"
                                name="game_name" type="search" required
                                data-autocomplete-endpoint="{{ route('ajax.games') }}"
                                data-autocomplete-key="game_name" data-autocomplete-id="game_id"
                                data-autocomplete-companion="{{$i->id }}_game_id" value="{{ $i->game?->game_name }}"
                                placeholder="Type a game name..." autocomplete="off">
                            <input type="hidden" name="{{$i->id }}_game_id"
                                wire:change="updateGame({{ $i }}, $event.target.value)"
                                value="{{ $i->game_id }}">
                        </td>
                        <td>
                            <input type="text" wire:model="issue.indexes.{{ $index }}.score"
                                class="form-control" size="4">
                        </td>
                        <td>
                            <input class="autocomplete form-control"
                                name="{{ $i->id }}_software_name" type="search" required
                                data-autocomplete-endpoint="{{ route('ajax.software') }}" data-autocomplete-key="name"
                                data-autocomplete-id="id" data-autocomplete-companion="{{ $i->id }}_menu_software_id"
                                value="{{ $i->menuSoftware?->name }}" placeholder="Type a software name..."
                                autocomplete="off">
                            <input type="hidden" name="{{$i->id }}_menu_software_id"
                                wire:change="updateSoftware({{ $i }}, $event.target.value)"
                                value="{{ $i->menu_software_id }}">
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
        <buttin class="btn btn-success" type="button" wire:click="save">Save</button>
    </form>

</div>
