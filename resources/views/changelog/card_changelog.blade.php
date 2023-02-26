<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">Database changes</h2>
    </div>
    <div class="card-body">
        <p>
            This section lists the changes that were made to the Atari Legend database.
            You can select a specific section of the database you are interested in, and
            get an atom feed for your preferred filters.
            There are currently <strong>{{ $changes->total() }}</strong> changes since
            the first recorded one on {{ $firstChange->timestamp->format('F j, Y') }}.
        </p>
        <div>
            <form id="filter" action="{{ route('changelog.index') }}" class="mb-4">
                <label for="section" class="visually-hidden form-label">Section</label>
                <div class="input-group mb-2">
                    <select class="form-select" id="section" name="filter">
                        <option value="">All sections</option>
                        @foreach ($sections as $section => $subsections)
                            <optgroup label="{{ $section }}">
                                <option value="{{ $section }}" @selected($currentSection==$section &&
                                    $currentSubsection==null)>
                                    {{ $section }}: All sub-sections
                                </option>
                                @foreach ($subsections as $subsection)
                                    <option value="{{ $section }}:{{ $subsection->sub_section }}"
                                        @selected($currentSection==$section && $currentSubsection==$subsection->
                                        sub_section)>
                                        {{ $subsection->sub_section }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary">Filter</button>
                </div>
                <div class="text-muted text-end">
                    Get an <i class="fas fa-rss-square text-warning"></i>
                    <a href="{{ route('feeds.changelog', ['filter' => "{$currentSection}:{$currentSubsection}"]) }}">
                        atom feed
                    </a> for the current filter
                </div>
            </form>

            <table class="table">
                <thead>
                    <tr>
                        <th><span class="d-none d-sm-inline">Action</span></th>
                        <th>Section</th>
                        <th>Sub-section</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($changes as $change)
                        <tr>
                            <td class="text-center">
                                @switch(strtolower($change->action))
                                    @case('update')
                                    @case('edit')
                                        <i class="fas fa-fw fa-pencil-alt text-info" title="Update"></i>
                                    @break

                                    @case('insert')
                                    @case('add')
                                        <i class="fas fa-fw fa-plus text-success" title="Addition"></i>
                                    @break

                                    @case('delete')
                                    @case('delete shot')
                                        <i class="fas fa-fw fa-trash-alt text-danger" title="Deletion"></i>
                                    @break

                                    @default
                                        <i class="fas fa-fw fa-question text-muted"
                                            title="Unknown: {{ $change->action }}"></i>
                                @endswitch

                            </td>
                            <td>
                                <span class="text-muted">{{ $change->section }}: </span>
                                <span class="text-break">{{ $change->section_name }}</span>
                            </td>
                            <td>
                                <span class="text-muted">{{ $change->sub_section }}: </span>
                                <span class="text-break">{{ $change->sub_section_name }}</span>
                            </td>
                            <td >
                                <abbr title="{{ $change->timestamp->format('F j, Y H:i') }}">
                                    <span class="d-md-none">{{ $change->timestamp->diffForHumans() }}</span>
                                    <span class="d-none d-md-inline text-nowrap">{{ $change->timestamp->diffForHumans() }}</span>
                                </abbr>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div>{{ $changes->withQueryString()->fragment('filter')->links() }}</div>
        </div>
    </div>
</div>
