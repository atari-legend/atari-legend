@switch(strtolower($action))
    @case('update')
    @case('edit')
        <i class="far fa-edit text-info fa-fw"></i>
        @break
    @case('insert')
    @case('add')
        <i class="far fa-plus-square text-success fa-fw"></i>
        @break
    @case('delete')
    @case('delete shot')
        <i class="far fa-minus-square text-danger fa-fw"></i>
        @break
    @default
        <i class="far fa-question-circle text-muted fa-fw"></i>
@endswitch
{{ $action }}
