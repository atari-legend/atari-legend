<div class="card mb-3 bg-light">
    <div class="card-body">

        <h2 class="card-title fs-4">Images</h2>

        @if (!isset($review))
            <p class="text-muted">Please save the review before adding images</p>
        @else
            <form action="{{ route('admin.reviews.reviews.image.update', $review) }}" id="update-images" method="post">
                @csrf
                @method('PUT')
            </form>

            <table class="table table-hover table-borderless">
                <tbody>
                    @foreach ($review->games->first()->screenshots as $screenshot)
                        <tr>
                            <td class="col-2">
                                <img class="w-100" src="{{ $screenshot->getUrl('game') }}">
                            </td>
                            <td>
                                <?php $reviewScreenshot = $review->screenshots->firstWhere('screenshot_id', $screenshot->getKey()); ?>
                                <label for="description-{{ $screenshot->getKey() }}"
                                    class="form-label">Description</label>
                                <textarea class="form-control" id="{{ $screenshot->getKey() }}" form="update-images"
                                    name="description-{{ $screenshot->getKey() }}">{{ old('description-' . $screenshot->getKey(), isset($reviewScreenshot) ? $reviewScreenshot->pivot->comment?->comment_text : '') }}</textarea>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <button class="btn btn-success" type="submit" form="update-images">Save changes</button>
        @endif
    </div>
</div>
