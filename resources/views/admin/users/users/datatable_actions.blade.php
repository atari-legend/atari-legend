<form action="{{ route('admin.users.users.destroy', $row) }}" method="POST"
    onsubmit="javascript:return confirm('This item will be permanently deleted')">
    @csrf
    @method('DELETE')
    @if ($row->is_deletable)
        <button title="Delete user '{{ $row->userid }}'" class="btn">
            <i class="fas fa-trash fa-fw text-danger" aria-hidden="true"></i>
        </button>
    @else
        {{--
            A user can only be deleted while nothing holds a RESTRICT on them -
            a game submission or a dump. The title goes on the wrapper rather
            than the button because Bootstrap gives .btn:disabled
            pointer-events: none, so a title on the button itself never shows
            on hover. A plain title rather than a Bootstrap tooltip: that needs
            JavaScript, and this has to work on a page whose scripts did not
            arrive.

            The button is an affordance, not a boundary - UserController checks
            again before it deletes anything.
        --}}
        <span class="d-inline-block" title="Cannot be deleted: this user still holds a game submission or a dump">
            <button type="submit" class="btn" disabled
                aria-label="Cannot delete user '{{ $row->userid }}': they still hold a game submission or a dump">
                <i class="fas fa-trash fa-fw text-danger" aria-hidden="true"></i>
            </button>
        </span>
    @endif
</form>
