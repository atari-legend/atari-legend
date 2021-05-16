@if ($sndhs->isNotEmpty())
    <div class="card bg-dark mb-4 card-music">
        <div class="card-header text-center">
            <h2 class="text-uppercase">Music</h2>
        </div>
        <div class="card-body p-2">
            <div id="aplayer"></div>

            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    new APlayer({
                        container: document.getElementById('aplayer'),
                        audio: @json($sndhs, JSON_PRETTY_PRINT |  JSON_UNESCAPED_SLASHES),
                        theme: '#00d3d3',
                    });
                });
            </script>
        </div>
        <div class="card-footer text-muted text-center">
            Songs gracefully provided by <a href="http://sndhrecord.atari.org">sndhrecord.atari.org</a>
        </div>
    </div>
@endif
