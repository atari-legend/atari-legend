var Module =
    typeof Module != 'undefined'
        ? Module
        : {
            // AL
            locateFile: function (path) {
                return '/js/emulator/' + path;
            },
            canvas: document.getElementById('canvas'),
        };
if (!Module.expectedDataFileDownloads) {
    Module.expectedDataFileDownloads = 0;
}
Module.expectedDataFileDownloads++;
(function () {
    if (Module['ENVIRONMENT_IS_PTHREAD']) return;
    var loadPackage = function (metadata) {
        var PACKAGE_PATH = '';
        if (typeof window === 'object') {
            PACKAGE_PATH = window['encodeURIComponent'](
                window.location.pathname
                    .toString()
                    .substring(
                        0,
                        window.location.pathname.toString().lastIndexOf('/')
                    ) + '/'
            );
        } else if (
            typeof process === 'undefined' &&
            typeof location !== 'undefined'
        ) {
            PACKAGE_PATH = encodeURIComponent(
                location.pathname
                    .toString()
                    .substring(
                        0,
                        location.pathname.toString().lastIndexOf('/')
                    ) + '/'
            );
        }
        var PACKAGE_NAME = 'hatari.data';
        var REMOTE_PACKAGE_BASE = 'hatari.data';
        if (
            typeof Module['locateFilePackage'] === 'function' &&
            !Module['locateFile']
        ) {
            Module['locateFile'] = Module['locateFilePackage'];
            err(
                'warning: you defined Module.locateFilePackage, that has been renamed to Module.locateFile '
                    + '(using your locateFilePackage for now)'
            );
        }
        var REMOTE_PACKAGE_NAME = Module['locateFile']
            ? Module['locateFile'](REMOTE_PACKAGE_BASE, '')
            : REMOTE_PACKAGE_BASE;
        var REMOTE_PACKAGE_SIZE = metadata['remote_package_size'];
        var PACKAGE_UUID = metadata['package_uuid'];
        function fetchRemotePackage(
            packageName,
            packageSize,
            callback,
            errback
        ) {
            if (
                typeof process === 'object' &&
                typeof process.versions === 'object' &&
                typeof process.versions.node === 'string'
            ) {
                require('fs').readFile(packageName, function (err, contents) {
                    if (err) {
                        errback(err);
                    } else {
                        callback(contents.buffer);
                    }
                });
                return;
            }
            var xhr = new XMLHttpRequest();
            xhr.open('GET', packageName, true);
            xhr.responseType = 'arraybuffer';
            xhr.onprogress = function (event) {
                var url = packageName;
                var size = packageSize;
                if (event.total) size = event.total;
                if (event.loaded) {
                    if (!xhr.addedTotal) {
                        xhr.addedTotal = true;
                        if (!Module.dataFileDownloads)
                            Module.dataFileDownloads = {};
                        Module.dataFileDownloads[url] = {
                            loaded: event.loaded,
                            total: size,
                        };
                    } else {
                        Module.dataFileDownloads[url].loaded = event.loaded;
                    }
                    var total = 0;
                    var loaded = 0;
                    var num = 0;
                    for (var download in Module.dataFileDownloads) {
                        var data = Module.dataFileDownloads[download];
                        total += data.total;
                        loaded += data.loaded;
                        num++;
                    }
                    total = Math.ceil(
                        (total * Module.expectedDataFileDownloads) / num
                    );
                    if (Module['setStatus'])
                        Module['setStatus'](
                            'Downloading data... (' + loaded + '/' + total + ')'
                        );
                } else if (!Module.dataFileDownloads) {
                    if (Module['setStatus'])
                        Module['setStatus']('Downloading data...');
                }
            };
            xhr.onerror = function (event) {
                throw new Error('NetworkError for: ' + packageName);
            };
            xhr.onload = function (event) {
                if (
                    xhr.status == 200 ||
                    xhr.status == 304 ||
                    xhr.status == 206 ||
                    (xhr.status == 0 && xhr.response)
                ) {
                    var packageData = xhr.response;
                    callback(packageData);
                } else {
                    throw new Error(xhr.statusText + ' : ' + xhr.responseURL);
                }
            };
            xhr.send(null);
        }
        function handleError(error) {
            console.error('package error:', error);
        }
        var fetchedCallback = null;
        var fetched = Module['getPreloadedPackage']
            ? Module['getPreloadedPackage'](
                REMOTE_PACKAGE_NAME,
                REMOTE_PACKAGE_SIZE
            )
            : null;
        if (!fetched)
            fetchRemotePackage(
                REMOTE_PACKAGE_NAME,
                REMOTE_PACKAGE_SIZE,
                function (data) {
                    if (fetchedCallback) {
                        fetchedCallback(data);
                        fetchedCallback = null;
                    } else {
                        fetched = data;
                    }
                },
                handleError
            );
        function runWithFS() {
            function assert(check, msg) {
                if (!check) throw msg + new Error().stack;
            }
            Module['FS_createPath']('/', 'share', true, true);
            Module['FS_createPath']('/share', 'hatari', true, true);
            Module['FS_createPath']('/share/hatari', '_hd', true, true);
            function DataRequest(start, end, audio) {
                this.start = start;
                this.end = end;
                this.audio = audio;
            }
            DataRequest.prototype = {
                requests: {},
                open: function (mode, name) {
                    this.name = name;
                    this.requests[name] = this;
                    Module['addRunDependency']('fp ' + this.name);
                },
                send: function () {},
                onload: function () {
                    var byteArray = this.byteArray.subarray(
                        this.start,
                        this.end
                    );
                    this.finish(byteArray);
                },
                finish: function (byteArray) {
                    var that = this;
                    Module['FS_createDataFile'](
                        this.name,
                        null,
                        byteArray,
                        true,
                        true,
                        true
                    );
                    Module['removeRunDependency']('fp ' + that.name);
                    this.requests[this.name] = null;
                },
            };
            var files = metadata['files'];
            for (var i = 0; i < files.length; ++i) {
                new DataRequest(
                    files[i]['start'],
                    files[i]['end'],
                    files[i]['audio'] || 0
                ).open('GET', files[i]['filename']);
            }
            function processPackageData(arrayBuffer) {
                assert(arrayBuffer, 'Loading data file failed.');
                assert(
                    arrayBuffer instanceof ArrayBuffer,
                    'bad input to processPackageData'
                );
                var byteArray = new Uint8Array(arrayBuffer);
                DataRequest.prototype.byteArray = byteArray;
                var files = metadata['files'];
                for (var i = 0; i < files.length; ++i) {
                    DataRequest.prototype.requests[files[i].filename].onload();
                }
                Module['removeRunDependency']('datafile_hatari.data');
            }
            Module['addRunDependency']('datafile_hatari.data');
            if (!Module.preloadResults) Module.preloadResults = {};
            Module.preloadResults[PACKAGE_NAME] = {fromCache: false};
            if (fetched) {
                processPackageData(fetched);
                fetched = null;
            } else {
                fetchedCallback = processPackageData;
            }
        }
        if (Module['calledRun']) {
            runWithFS();
        } else {
            if (!Module['preRun']) Module['preRun'] = [];
            Module['preRun'].push(runWithFS);
        }
    };
    loadPackage({
        files: [
            {filename: '/share/hatari/DESKTOP.INF', start: 0, end: 548},
            {filename: '/share/hatari/blank.msa', start: 548, end: 1537},
            {filename: '/share/hatari/boot.msa', start: 1537, end: 782555},
            {filename: '/share/hatari/tos.img', start: 782555, end: 979163},
            {
                filename: '/share/hatari/tos162.img',
                start: 979163,
                end: 1241307,
            },
            {
                filename: '/share/hatari/_hd/DESKTOP.INF',
                start: 1241307,
                end: 1241855,
            },
            {
                filename: '/share/hatari/_hd/readme.txt',
                start: 1241855,
                end: 1241872,
            },
        ],
        remote_package_size: 1241872,
        package_uuid: '6687357d-90be-49a4-b7b9-55c3e541a655',
    });
})();
let version = '0.0009';
let config = {
    useProxy: false,
    sortAlpha: false,
};
let audioCtx;
function unlockAudioContext3() {
    const audioElement = document.querySelector('audio');
    if (!audioCtx) {
        audioCtx = new AudioContext();
        track = audioCtx.createMediaElementSource(audioElement);
        track.connect(audioCtx.destination);
    }
    if (audioCtx.state === 'suspended') {
        audioCtx.resume();
    }
    audioElement.play();
}
window.counter = 0;
Module['arguments'] = [
    '--desktop',
    'false',
    // '-d',
    // '/share/hatari/_hd/',
    // '/share/hatari/boot.msa',
];

document.addEventListener('DOMContentLoaded', () => {

    let reader = new FileReader();
    function save_file(e) {
        let result = reader.result;
        const uint8_view = new Uint8Array(result);
        var fileName = '/share/hatari/boot.msa';
        if (
            uint8_view.length > 2 &&
            uint8_view[0] == 80 &&
            uint8_view[1] == 75
        ) {
            const directory = '/share/hatari/_hd/';
            prepareAndEmptyHdMount(directory).then(() => {
                fileName = '/share/hatari/boot.zip';
                FS.writeFile(fileName, uint8_view);
                fileName = unZipFile(uint8_view, directory, fileName).then(
                    (fileName) => {
                        resolve(fileName);
                    }
                );
            });
        } else {
            FS.writeFile(fileName, uint8_view);
        }
        Module.ccall('Floppy_EjectDiskFromDrive', 'number', ['number'], [0]);
        Module.ccall(
            'Floppy_SetDiskFileName',
            'number',
            ['number', 'string'],
            [0, fileName]
        );
        Module.ccall('Floppy_InsertDiskIntoDrive', 'number', ['number'], [0]);
        Module.ccall('Main_UnPauseEmulation');
        Module.ccall('Reset_Cold');
        Module.ccall('Statusbar_UpdateInfo');
    }
    function showState(text, timeout = false) {
        document.getElementById('status').innerHTML = text;
        if (timeout) {
            window.setTimeout(() => {
                document.getElementById('status').innerHTML = '';
            }, 5e3);
        }
    }
    function insertDemoIntoHatari(
        url,
        doReset = true
    ) {
        var fileName = '/share/hatari/boot.msa';
        showState(`<strong>Downloading</strong> <i>${url}</i>...`);
        function ready(fileName) {
            if (fileName != '/share/hatari/blank.msa') {
                showState(
                    `Download of <i>${url}</i> <strong>done</strong>`,
                    true
                );
                if (doReset) {
                    Module.ccall(
                        'Floppy_EjectDiskFromDrive',
                        'number',
                        ['number'],
                        [0]
                    );
                }
                if (
                    fileName
                        .substring(fileName.length - 4, fileName.length)
                        .toUpperCase() != '.TOS' &&
                    fileName
                        .substring(fileName.length - 4, fileName.length)
                        .toUpperCase() != '.PRG'
                ) {
                    Module.ccall(
                        'Floppy_SetDiskFileName',
                        'number',
                        ['number', 'string'],
                        [0, fileName]
                    );
                    Module.ccall(
                        'Floppy_InsertDiskIntoDrive',
                        'number',
                        ['number'],
                        [0]
                    );
                } else {
                    Module.ccall(
                        'Floppy_SetDiskFileName',
                        'number',
                        ['number', 'string'],
                        [0, '/share/hatari/blank.msa']
                    );
                    Module.ccall(
                        'Floppy_InsertDiskIntoDrive',
                        'number',
                        ['number'],
                        [0]
                    );
                }
                if (doReset) {
                    Module.ccall('Main_UnPauseEmulation');
                    Module.ccall('Reset_Cold');
                    Module.ccall('Statusbar_UpdateInfo');
                }
            } else if (!fileName) {
                showState(
                    `Download of <i>${url}</i> <strong style="color:#ff0000">failed</strong>`,
                    true
                );
            } else {
                showState(
                    `Download of <i>${url}</i> <strong>done</strong>`,
                    true
                );
                if (doReset) {
                    Module.ccall('Main_UnPauseEmulation');
                    Module.ccall('Reset_Cold');
                    Module.ccall('Statusbar_UpdateInfo');
                }
            }
        }

        fileName = new URL(url, window.location).pathname;
        const ext = fileName
            .substring(fileName.lastIndexOf('.') + 1)
            .toUpperCase();
        if (fileName.lastIndexOf('.')) {
            if (ext == 'MSA') {
                fileName = 'boot.msa';
            } else if (ext == 'ST') {
                fileName = 'boot.st';
            } else if (ext == 'STX') {
                fileName = 'boot.stx';
            } else if (ext == 'DIM') {
                fileName = 'boot.dim';
            } else {
                fileName = 'boot.zip';
            }
        } else {
            fileName = 'boot.zip';
        }
        let storagepath = '/share/hatari/' + fileName;
        fileName = getDiskImageFromServer(url, storagepath, storagepath).then(
            ready
        );
    }
    async function prepareAndEmptyHdMount(directory) {
        await clearFolder(directory);
        const data = FS.readFile('/share/hatari/DESKTOP.INF');
        FS.writeFile(directory + 'DESKTOP.INF', data);
    }
    async function clearFolder(directory) {
        files = FS.readdir(directory);
        for (const file of files) {
            if (file !== '.' && file !== '..') {
                const dirOrFile = directory + file;
                const info = FS.lookupPath(dirOrFile);
                if (info.node.isFolder) {
                    await clearFolder(dirOrFile + '/');
                    FS.rmdir(dirOrFile);
                } else {
                    FS.unlink(dirOrFile);
                }
            }
        }
    }
    async function createAutoBootDesktopInf(basePath, bootExec) {
        let desktopInf = `\n#a000000\n#b000000\n#c???000?000<000?00;;400;0;;;4440??0;;?0?;0;;;0??03111103\n#d                                             \n#Z 00 C:\\${bootExec}@ \n#E 18 11 \n#W 00 0C 00 01 28 17 09 C:*.*@\n#W 00 00 02 0B 26 09 00 @\n#W 00 00 0A 0F 1A 09 00 @\n#W 00 00 0E 01 1A 09 00 @\n#M 01 00 00 FF C HARD DISK@ @ \n#M 00 00 00 FF A DISKSTATION@ @ \n#M 00 01 00 FF B DISKSTATION@ @ \n#T 00 03 02 FF   PAPIERKORB@ @ \n#F FF 04   @ *.*@ \n#D FF 01   @ *.*@ \n#G 03 FF   *.APP@ @ \n#G 03 FF   *.PRG@ @ \n#P 03 FF   *.TTP@ @ \n#F 03 04   *.TOS@ @ \n#F 03 04   C:\\${bootExec}@ @`;
        desktopInf = desktopInf.replace(/^\s*[\r\n]/gm, '\r\n');
        FS.writeFile(basePath + 'DESKTOP.INF', desktopInf);
    }
    async function unZipFile(byteArray, memFsPath, fileName) {
        var zip = new JSZip();
        await zip.loadAsync(byteArray, {createFolders: true});
        var hasDiskImage = false;
        const basePath = '/share/hatari/_hd/';
        var executableCnt = 0;
        var lastExecFile = null;
        zip.forEach(function (relativePath, file) {
            if (
                relativePath.substring(
                    relativePath.length - 1,
                    relativePath.length
                ) == '/'
            ) {
                FS.mkdir(basePath + relativePath);
                return;
            }
            if (
                !relativePath.includes('\\') &&
                !relativePath.includes('/') &&
                (relativePath
                    .substring(relativePath.length - 4, relativePath.length)
                    .toUpperCase() == '.MSA' ||
                    relativePath
                        .substring(relativePath.length - 3, relativePath.length)
                        .toUpperCase() == '.ST' ||
                    relativePath
                        .substring(relativePath.length - 4, relativePath.length)
                        .toUpperCase() == '.STX')
            ) {
                hasDiskImage = true;
            }
            if (
                relativePath
                    .substring(relativePath.length - 4, relativePath.length)
                    .toUpperCase() == '.PRG' ||
                relativePath
                    .substring(relativePath.length - 4, relativePath.length)
                    .toUpperCase() == '.TOS'
            ) {
                executableCnt++;
                lastExecFile = relativePath;
            }
            zip.file(relativePath)
                .async('Uint8Array')
                .then(async function (byteArray) {
                    FS.writeFile(basePath + relativePath, byteArray);
                });
        });
        Module.ccall('Floppy_EjectDiskFromDrive', 'number', ['number'], [0]);
        if (!hasDiskImage) {
            fileName = '/share/hatari/blank.msa';
            if (executableCnt === 1) {
                const bootExec = lastExecFile.replace('/', '\\');
                await createAutoBootDesktopInf(basePath, bootExec);
            }
        }
        Module.ccall(
            'Floppy_SetDiskFileName',
            'number',
            ['number', 'string'],
            [0, fileName]
        );
        Module.ccall('Floppy_InsertDiskIntoDrive', 'number', ['number'], [0]);
        return fileName;
    }
    function getDiskImageFromServer(url, fileName, fileNameZip) {
        let fileNameByFileHeaderPromise = new Promise((resolve) => {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', url);
            xhr.responseType = 'arraybuffer';
            xhr.onload = function () {
                if (this.status != 200) {
                    resolve(undefined);
                    return;
                }
                var arrayBuffer = this.response;
                if (arrayBuffer) {
                    const directory = '/share/hatari/_hd/';
                    prepareAndEmptyHdMount(directory).then(() => {
                        var byteArray = new Uint8Array(arrayBuffer);
                        if (
                            fileNameZip &&
                            byteArray.length > 2 &&
                            byteArray[0] == 80 &&
                            byteArray[1] == 75
                        ) {
                            fileName = fileNameZip;
                        }
                        FS.writeFile(fileName, byteArray);
                        resolve(fileName);
                    });
                }
            };
            xhr.onerror = function () {
                resolve(undefined);
            };
            xhr.send();
        });
        return fileNameByFileHeaderPromise;
    }

    Module.preRun.push(() => {
        var elem = document.getElementById('output');
        var popupElement = document.getElementById('popup');
        var versionTexts = document.getElementsByClassName('version');
        for (var i = 0; i < versionTexts.length; i++) {
            versionTexts[i].innerHTML = version;
        }
        document.onclick = () => {
            popupElement.style.display = 'none';
        };
        popupElement.style.display = 'block';
        elem = document.getElementById('innerSelector');
        function updateUrlFromConfig() {
            const urlSearchParams = new URLSearchParams(window.location.search);
            const params = Object.fromEntries(urlSearchParams.entries());
            const button = ['1mb', '2mb', '4mb', 'ste', 'ffwd', 'hd'];
            // for (id of button) {
            //     const elem = document.getElementById(`button-${id}`);
            //     if (elem.classList.contains("pure-button-active")) {
            //         urlSearchParams.set(`c${id}`, "1");
            //     } else {
            //         urlSearchParams.set(`c${id}`, "0");
            //     }
            // }
            history.replaceState(null, null, '?' + urlSearchParams.toString());
        }
        function updateConfigFromUrl() {
            const urlSearchParams = new URLSearchParams(window.location.search);
            const params = Object.fromEntries(urlSearchParams.entries());
            const button = ['1mb', '2mb', '4mb', 'ste', 'ffwd', 'hd'];
            const setters = {
                c1mb: (s) => setHatariMem(document.getElementById('button-1mb'), 1024),
                c2mb: (s) => setHatariMem(
                    document.getElementById('button-2mb'),
                    1024 * 2
                ),
                c4mb: (s) => setHatariMem(
                    document.getElementById('button-4mb'),
                    1024 * 4
                ),
                cste: (s) => {
                    let system = 0;
                    if (s) {
                        system = 1;
                    }
                    Module.ccall('IoMem_UnInit');
                    Module.ccall(
                        'Configuration_ChangeSystem',
                        null,
                        ['number'],
                        [system]
                    );
                    if (system == 0) {
                        Module.ccall(
                            'Configuration_ChangeTos',
                            null,
                            ['string'],
                            ['/share/hatari/tos.img']
                        );
                    } else {
                        Module.ccall(
                            'Configuration_ChangeTos',
                            null,
                            ['string'],
                            ['/share/hatari/tos162.img']
                        );
                    }
                    Module.ccall(
                        'Configuration_Apply',
                        null,
                        ['boolean'],
                        [true]
                    );
                    Module.ccall('IoMem_Init');
                    Module.ccall('Main_UnPauseEmulation');
                    Module.ccall('Reset_Cold');
                    Module.ccall('Statusbar_UpdateInfo');
                },
                cffwd: (s) => Module.ccall(
                    'Configuration_ChangeFastForward',
                    null,
                    ['boolean'],
                    [s]
                ),
                chd: (s) => Module.ccall(
                    'Configuration_ChangeUseHardDiskDirectories',
                    null,
                    ['boolean'],
                    [s]
                ),
            };
            for (id of button) {
                const elem = document.getElementById(`button-${id}`);
                if (typeof params[`c${id}`] !== 'undefined') {
                    if (params[`c${id}`] && params[`c${id}`] === '1') {
                        elem.classList.add('pure-button-active');
                        setters[`c${id}`](true);
                    } else {
                        elem.classList.remove('pure-button-active');
                        setters[`c${id}`](false);
                    }
                }
            }
        }
        function setHatariMem(that, kb) {
            const elems = document.querySelectorAll('.config>button.mem');
            for (const el of elems) {
                el.classList.remove('pure-button-active');
            }
            that.classList.add('pure-button-active');
            Module.ccall('Configuration_ChangeMemory', null, ['number'], [kb]);
            Module.ccall('Main_UnPauseEmulation');
            Module.ccall('Reset_Cold');
            Module.ccall('Statusbar_UpdateInfo');
        }
        elem = document.getElementById('button-test');
        elem.onclick = (e) => {
            insertDemoIntoHatari(window.hatariDiskDownloadUrl, true);
        };
        document.load_file = () => {
            let files = document.getElementById('myfile').files;
            let file = files[0];
            reader.addEventListener('loadend', save_file);
            reader.readAsArrayBuffer(file);
        };
        document.ontouchstart = () => {
            unlockAudioContext3();
            document.getElementById('output').innerHTML = window.counter;
        };
    });
    Module.preInit = [
        () => {
            const temp = asmLibraryArg.emscripten_sleep;
            asmLibraryArg.emscripten_sleep = () => {
                document.getElementById('output').value = '' + window.counter++;
                temp();
            };
        },
    ];

    Module.onRuntimeInitialized = () => {
        // console.log('postInit!');
        insertDemoIntoHatari(window.hatariDiskDownloadUrl, true);
    };
});

/*
document.addEventListener('DOMContentLoaded', () => {
    // Module.insertDemoIntoHatari(window.hatariDiskDownloadUrl, true);
    console.log(Module);
});
*/
