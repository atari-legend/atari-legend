/*
 * Plays a disk in Hatari, the Atari ST emulator, compiled to WebAssembly.
 *
 * resources/js/emulator/hatari.js and hatari.wasm are the Hatari v2.4.0-devel
 * build of Atariaviary, the Atari ST demo browser by tIn/newline
 * (http://absencehq.de/atariaviary/), used as generated: there are no sources
 * for it here.
 */
import hatariUrl from '../emulator/hatari.js?url';
import wasmUrl from '../emulator/hatari.wasm?url';

// Hatari reads the TOS and the disks from Emscripten's in-memory filesystem, so
// they are downloaded first and written there.
const TOS_PATH = '/tos.img';

const DRIVE_A = 0;

const MEGABYTE = 1024 * 1024;

// The keys Hatari's cursor-key joystick is on, as the browser describes them
const JOYSTICK_KEYS = {
    up: {key: 'ArrowUp', code: 'ArrowUp', keyCode: 38, location: 0},
    down: {key: 'ArrowDown', code: 'ArrowDown', keyCode: 40, location: 0},
    left: {key: 'ArrowLeft', code: 'ArrowLeft', keyCode: 37, location: 0},
    right: {key: 'ArrowRight', code: 'ArrowRight', keyCode: 39, location: 0},
    fire: {key: 'Control', code: 'ControlRight', keyCode: 17, location: 2},
};

// Buttons of the browser's standard gamepad layout
const GAMEPAD_DPAD = {up: 12, down: 13, left: 14, right: 15};
const GAMEPAD_FIRE = [0, 1, 2, 3];
const GAMEPAD_STICK_THRESHOLD = 0.5;

/**
 * Download a file.
 *
 * @param {string} url URL of the file.
 * @param {AbortSignal} signal Signal abandoning the download.
 * @param {Function} onProgress Called with the bytes received so far and the
 *     total, which is 0 when the server does not announce it.
 * @returns {Promise<Uint8Array>} Content of the file.
 */
async function download(url, signal, onProgress = () => {}) {
    const response = await fetch(url, {signal});
    if (!response.ok) {
        throw new Error(`Could not download ${url} (${response.status})`);
    }

    const total = Number(response.headers.get('Content-Length')) || 0;
    const reader = response.body.getReader();
    const chunks = [];
    let received = 0;

    for (;;) {
        const {done, value} = await reader.read();
        if (done) {
            break;
        }
        chunks.push(value);
        received += value.length;
        onProgress(received, total);
    }

    const content = new Uint8Array(received);
    let offset = 0;
    for (const chunk of chunks) {
        content.set(chunk, offset);
        offset += chunk.length;
    }

    return content;
}

/**
 * Describe how far the download of the emulator has got.
 *
 * @param {number} received Bytes received so far.
 * @param {number} total Total bytes, 0 when unknown.
 * @returns {string} Progress message.
 */
function downloadProgress(received, total) {
    const megabytes = (bytes) => (bytes / MEGABYTE).toFixed(1);

    // A compressed response announces fewer bytes than it delivers
    if (total >= received) {
        return `Downloading the emulator: ${megabytes(received)} of ${megabytes(total)} MB`;
    }

    return `Downloading the emulator: ${megabytes(received)} MB`;
}

/**
 * Get where a disk is written in Hatari's filesystem.
 *
 * @param {string} dumpId Id of the dump.
 * @returns {string} Path of the disk.
 */
function diskPath(dumpId) {
    return `/disk-${dumpId}.zip`;
}

/**
 * Call a C function of Hatari's build.
 *
 * @param {string} name Name of the function.
 * @param {string[]} argTypes Emscripten types of the arguments.
 * @param {Array} args Arguments.
 * @returns {number} What the function returns.
 */
function hatari(name, argTypes = [], args = []) {
    return window.Module.ccall(name, 'number', argTypes, args);
}

const gains = new WeakMap();

/**
 * Mute or unmute Hatari.
 *
 * SDL connects its audio node straight to the speakers. A gain node put in
 * between silences it while the node keeps running, which suspending the audio
 * context would not do reliably: the context resumes itself on the next click.
 *
 * @param {boolean} muted Whether to mute.
 */
function setMuted(muted) {
    const context = window.Module.SDL2?.audioContext;
    const node = window.Module.SDL2?.audio?.scriptProcessorNode;
    if (!context || !node) {
        return;
    }

    let gain = gains.get(node);
    if (!gain) {
        gain = context.createGain();
        node.disconnect();
        node.connect(gain);
        gain.connect(context.destination);
        gains.set(node, gain);
    }
    gain.gain.value = muted ? 0 : 1;
}

/**
 * Read which joystick directions, and fire, the gamepads hold.
 *
 * @returns {Object<string, boolean>} Whether each of JOYSTICK_KEYS is held.
 */
function readGamepads() {
    const held = {up: false, down: false, left: false, right: false, fire: false};

    for (const gamepad of navigator.getGamepads()) {
        if (!gamepad) {
            continue;
        }
        const pressed = (index) => gamepad.buttons[index]?.pressed ?? false;
        const [x = 0, y = 0] = gamepad.axes;

        held.up ||= pressed(GAMEPAD_DPAD.up) || y < -GAMEPAD_STICK_THRESHOLD;
        held.down ||= pressed(GAMEPAD_DPAD.down) || y > GAMEPAD_STICK_THRESHOLD;
        held.left ||= pressed(GAMEPAD_DPAD.left) || x < -GAMEPAD_STICK_THRESHOLD;
        held.right ||= pressed(GAMEPAD_DPAD.right) || x > GAMEPAD_STICK_THRESHOLD;
        held.fire ||= GAMEPAD_FIRE.some(pressed);
    }

    return held;
}

/**
 * Drive Hatari's cursor-key joystick from a gamepad.
 *
 * The gamepad presses the joystick's keys on the screen rather than being
 * handed to Hatari as a joystick: the browser only reveals a gamepad once one
 * of its buttons is pressed on the page, and keys work whenever that happens.
 *
 * @param {HTMLCanvasElement} canvas Hatari's screen, where its keyboard listens.
 */
function followGamepads(canvas) {
    const connected = () => Array.from(navigator.getGamepads()).some(Boolean);
    const held = new Set();
    let polling = false;

    const send = (name, down) => {
        const key = JOYSTICK_KEYS[name];
        const event = new KeyboardEvent(down ? 'keydown' : 'keyup', {
            key: key.key,
            code: key.code,
            location: key.location,
            bubbles: true,
            cancelable: true,
        });
        // Emscripten reads the legacy key codes, which the constructor does not set
        Object.defineProperty(event, 'keyCode', {value: key.keyCode});
        Object.defineProperty(event, 'which', {value: key.keyCode});
        canvas.dispatchEvent(event);
    };

    const poll = () => {
        for (const [name, down] of Object.entries(readGamepads())) {
            if (down !== held.has(name)) {
                send(name, down);
                if (down) {
                    held.add(name);
                } else {
                    held.delete(name);
                }
            }
        }

        // When the last gamepad goes, the pass above has released its keys
        polling = connected();
        if (polling) {
            requestAnimationFrame(poll);
        }
    };

    const startPolling = () => {
        if (!polling) {
            polling = true;
            requestAnimationFrame(poll);
        }
    };

    window.addEventListener('gamepadconnected', startPolling);
    if (connected()) {
        startPolling();
    }
}

/**
 * Show Hatari's screen fullscreen, with the mouse captured for the Atari.
 *
 * Not Emscripten's Module.requestFullscreen(), which sizes the canvas through
 * its own bookkeeping, not SDL's, and leaves it at 0x0.
 *
 * @param {HTMLElement} screen Element around the canvas.
 * @param {HTMLCanvasElement} canvas Hatari's screen.
 */
function goFullscreen(screen, canvas) {
    screen.requestFullscreen()
        .then(() => canvas.requestPointerLock())
        .catch((error) => console.error(error));
}

/**
 * Wire the emulator's buttons, once Hatari runs.
 *
 * @param {HTMLElement} container Element carrying the emulator's data attributes.
 * @param {HTMLCanvasElement} canvas Hatari's screen.
 * @param {Function} showStatus Shows a message in the status line.
 */
function enableControls(container, canvas, showStatus) {
    const screen = container.querySelector('[data-emulator-screen]');
    const diskButtons = container.querySelectorAll('[data-emulator-disk]');
    const writtenDisks = new Set([diskPath(container.dataset.diskId)]);
    let muted = false;

    screen.addEventListener('fullscreenchange', () => {
        if (!document.fullscreenElement) {
            document.exitPointerLock();
        }
    });

    // A press on the screen or its bezel gives Hatari the keyboard. The browser
    // would not: Hatari's SDL layer cancels the press on the screen, and the
    // bezel would hand the focus to the page
    screen.addEventListener('mousedown', (event) => {
        event.preventDefault();
        canvas.focus({preventScroll: true});
    }, {capture: true});

    const reset = (resetFunction) => {
        // A paused Hatari would otherwise stay paused through the reset
        hatari('Main_UnPauseEmulation');
        hatari(resetFunction);
        setMuted(muted);
    };

    const actions = {
        'warm-reset': () => reset('Reset_Warm'),
        'cold-reset': () => reset('Reset_Cold'),
        'fullscreen': () => goFullscreen(screen, canvas),
        'mute': (button) => {
            muted = !muted;
            setMuted(muted);
            button.classList.toggle('active', muted);
            button.setAttribute('aria-pressed', String(muted));
            button.querySelector('i').classList.toggle('fa-volume-high', !muted);
            button.querySelector('i').classList.toggle('fa-volume-xmark', muted);
        },
    };

    const insertDisk = async (button) => {
        const path = diskPath(button.dataset.emulatorDisk);
        diskButtons.forEach((diskButton) => {
            diskButton.disabled = true;
        });

        try {
            if (!writtenDisks.has(path)) {
                showStatus('Downloading the disk…');
                const content = await download(button.dataset.diskUrl);
                window.Module.FS_createDataFile('/', path.substring(1), content, true, true, false);
                writtenDisks.add(path);
                showStatus('');
            }

            hatari('Floppy_EjectDiskFromDrive', ['number'], [DRIVE_A]);
            hatari('Floppy_SetDiskFileName', ['number', 'string', 'string'], [DRIVE_A, path, null]);
            hatari('Floppy_InsertDiskIntoDrive', ['number'], [DRIVE_A]);

            diskButtons.forEach((diskButton) => {
                diskButton.classList.toggle('active', diskButton === button);
                diskButton.setAttribute('aria-pressed', String(diskButton === button));
            });
        } catch (error) {
            console.error(error);
            showStatus(`The disk could not be inserted. ${error.message}`);
        } finally {
            diskButtons.forEach((diskButton) => {
                diskButton.disabled = false;
            });
        }
    };

    container.querySelectorAll('[data-emulator-action]').forEach((button) => {
        button.addEventListener('click', () => {
            actions[button.dataset.emulatorAction](button);
            canvas.focus({preventScroll: true});
        });
        button.disabled = false;
    });

    diskButtons.forEach((button) => {
        button.addEventListener('click', async () => {
            await insertDisk(button);
            canvas.focus({preventScroll: true});
        });
        button.disabled = false;
    });
}

/**
 * Download everything Hatari needs, then start it on the page's disk.
 *
 * @param {HTMLElement} container Element carrying the emulator's data attributes.
 */
async function start(container) {
    const canvas = container.querySelector('canvas');
    const status = container.querySelector('[data-emulator-status]');
    const showStatus = (text) => {
        status.textContent = text;
        status.hidden = text === '';
    };

    // The right mouse button belongs to the Atari
    canvas.addEventListener('contextmenu', (event) => event.preventDefault());

    const firstDiskPath = diskPath(container.dataset.diskId);
    const abort = new AbortController();

    let wasm, tos, disk;
    try {
        [wasm, tos, disk] = await Promise.all([
            download(wasmUrl, abort.signal, (received, total) => showStatus(downloadProgress(received, total))),
            download(container.dataset.tosUrl, abort.signal),
            download(container.dataset.diskUrl, abort.signal),
        ]);
    } catch (error) {
        abort.abort();
        console.error(error);
        showStatus(`The emulator could not be started. ${error.message}`);
        return;
    }

    showStatus('Starting the emulator…');

    window.Module = {
        canvas,
        wasmBinary: wasm,
        arguments: [
            '--machine', 'st',
            '--memsize', '1',
            '--tos', TOS_PATH,
            '--joy1', 'keys',
            // Hatari's status bar is not part of the Atari's picture
            '--statusbar', 'false',
            // The page scales the screen; a resizable window takes the canvas's
            // size on the page instead, and squashes the picture into it
            '--resizable', 'false',
            '--disk-a', firstDiskPath,
        ],
        preRun: [() => {
            // Hatari takes the keyboard only while its screen has the focus,
            // rather than every key pressed anywhere on the page
            window.ENV.SDL_EMSCRIPTEN_KEYBOARD_ELEMENT = '#canvas';

            window.Module.FS_createDataFile('/', TOS_PATH.substring(1), tos, true, false, false);
            window.Module.FS_createDataFile('/', firstDiskPath.substring(1), disk, true, true, false);
        }],
        postRun: [() => {
            showStatus('');
            enableControls(container, canvas, showStatus);
            followGamepads(canvas);
            canvas.focus({preventScroll: true});
        }],
        onAbort: (reason) => showStatus(`The emulator stopped. ${reason}`),
    };

    const script = document.createElement('script');
    script.src = hatariUrl;
    document.body.append(script);
}

document.addEventListener('DOMContentLoaded', () => {
    const container = document.querySelector('[data-emulator]');
    if (container) {
        start(container);
    }
});
