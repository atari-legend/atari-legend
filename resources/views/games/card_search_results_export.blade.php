<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">Games</h2>
    </div>
    <div class="card-body p-2">
        <p class="card-text">
             This is the search result page. Click a game to go to the main page. Click a Developer to do a new search.
             This list has sort functions, filters and you can use the download button to export the contents.
             For more info regarding this plug in, visit the <a href="http://tabulator.info/">Tabulator website</a>.
        </p>
    </div>

    <div class="card-body p-2">
        <button class="btn btn-primary me-2" id="download-csv">Download CSV</button>
        <button class="btn btn-primary me-2" id="download-json">Download JSON</button>
        <button class="btn btn-primary" id="download-xlsx">Download XLSX</button>

        <div id="tabulator-table" class="mt-3"></div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            var table = new Tabulator('#tabulator-table', {
                data: [
                    @foreach ($games as $game)
                        {
                            Name: @json($game->game_name),
                            Developer: @json(GameHelper::developers($game)),
                            Boxscan: @json(GameHelper::hasBoxscan($game)),
                            Screenshot: @json($game->screenshots->isNotEmpty()),
                            Music: @json($game->sndhs->isNotEmpty()),

                        }@if (!$loop->last),@endif
                    @endforeach
                ],
                autoColumnsDefinitions: {
                    Name: { headerFilter: true },
                    Developer: { headerFilter: true },
                    Boxscan: { width: 100, formatter: 'tickCross', hozAlign: 'center' },
                    Screenshot: { width: 120, formatter: 'tickCross', hozAlign: 'center' },
                    Music: { width: 80, formatter: 'tickCross', hozAlign: 'center' },
                },
                responsiveLayout: true,
                autoColumns: true,
                layout: 'fitColumns',
                paginationSize: 25,
                pagination: 'local',
            });

            document.querySelector('#download-csv').addEventListener('click', () => {
                table.download('csv', 'data.csv');
            });
            document.querySelector('#download-json').addEventListener('click', () => {
                table.download('json', 'data.json');
            });
            document.querySelector('#download-xlsx').addEventListener('click', () => {
                table.download('xlsx', 'data.xlsx');
            });
        });
    </script>
</div>
