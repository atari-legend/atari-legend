import init, {Ym2149Player} from 'ym2149-wasm';

document.addEventListener('DOMContentLoaded', async () => {

    // Chiptune Player Class adapted from https://github.com/slippyex/ym2149-rs
    class ChiptunePlayer {
        // After how many empty audio frames to stop playback
        static CONSECUTIVE_ZERO_FRAME_LIMIT = 30;

        constructor() {
            this.audioContext = null;
            this.player = null;
            this.scriptProcessor = null;
            this.consecutiveZeroFrames = 0;
            this.onCompletedCallback = null;
        }

        async init() {
            await init();
            if (this.audioContext === null) {
                this.audioContext = new AudioContext({sampleRate: 44100});
            }
        }

        async load(url) {
            if (this.player) {
                this.stop();
            }
            const response = await fetch(url);
            const fileData = new Uint8Array(await response.arrayBuffer());

            this.player = new Ym2149Player(new Uint8Array(fileData));
            return this.player.metadata;
        }

        setSubsong(index) {
            this.player.setSubsong(index);
        }

        play() {
            if (!this.player) return;

            this.consecutiveZeroFrames = 0;

            this.player.play();

            this.scriptProcessor = this.audioContext.createScriptProcessor(4096, 0, 1);
            this.scriptProcessor.onaudioprocess = (e) => {
                const output = e.outputBuffer.getChannelData(0);
                if (this.player.is_playing()) {
                    const samples = this.player.generateSamples(output.length);
                    output.set(samples);
                    // Count consecutive empty frames to auto-stop playback
                    if (!samples.some(value => value !== 0)) {
                        this.consecutiveZeroFrames++;
                    } else {
                        this.consecutiveZeroFrames = 0;
                    }

                    if (this.consecutiveZeroFrames > ChiptunePlayer.CONSECUTIVE_ZERO_FRAME_LIMIT) {
                        this.stop();
                        if (this.onCompletedCallback !== null) {
                            this.onCompletedCallback();
                        }
                    }
                } else {
                    output.fill(0);
                }
            };
            this.scriptProcessor.connect(this.audioContext.destination);
        }

        pause() {
            this.player?.pause();
        }

        stop() {
            this.player?.stop();
            this.scriptProcessor?.disconnect();
        }

        onCompleted(callback) {
            this.onCompletedCallback = callback;
        }

    }

    const player = new ChiptunePlayer();

    document.querySelectorAll('a.sndh-play').forEach(el => {

        function togglePlayIcon(iconElement, playing) {
            if (playing === true) {
                iconElement.classList.remove('fa-circle-play');
                iconElement.classList.add('fa-volume-high');
                iconElement.classList.add('fa-beat');
            } else {
                iconElement.classList.add('fa-circle-play');
                iconElement.classList.remove('fa-volume-high');
                iconElement.classList.remove('fa-beat');
            }
        }

        el.addEventListener('click', async (event) => {
            event.preventDefault();
            await player.init();

            var shouldStop = false;

            if (player.player?.is_playing()) {
                player.stop();
                // Find the song that is playing and change its icon back to stopped state
                el.closest('[data-music-player]').querySelectorAll('[data-play-icon]').forEach(icon => {
                    if (el.contains(icon) && icon.classList.contains('fa-volume-high')) {
                        // Case where we clicked the currently playing song - we want to stop it
                        shouldStop = true;
                    }
                    togglePlayIcon(icon, false);
                });
            }

            if (!shouldStop) {
                await player.load(el.dataset.sndhUrl);
                if (el.dataset.sndhSubtune) {
                    player.setSubsong(el.dataset.sndhSubtune);
                }
                player.onCompleted(() => {
                    el.querySelectorAll('[data-play-icon]').forEach(icon => togglePlayIcon(icon, false));
                });
                player.play();

                el.querySelectorAll('[data-play-icon]').forEach(icon => togglePlayIcon(icon, true));
            }

        });
    });
});
