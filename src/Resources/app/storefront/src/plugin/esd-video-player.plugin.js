import Plugin from 'src/plugin-system/plugin.class';
import Plyr from '@plyr';

export default class EsdVideoPlayer extends Plugin {
    static options = {
        closeEsdVideoPlayer: '.js-close-esd-video-player',
    };

    init() {
        this._registerEvents();
    }

    _registerEvents() {
        this._registerVideoPlayers();
        this._registerButtonCloseVideoPlayerEvents();
        this._registerKeydownEscVideoPlayerEvents();
    }

    _registerVideoPlayers() {
        Array.from(document.querySelectorAll('.js-esd-player')).map(p => new Plyr(p));
    }

    _registerButtonCloseVideoPlayerEvents() {
        const { closeEsdVideoPlayer } = this.options;
        Array.from(document.querySelectorAll(closeEsdVideoPlayer)).forEach(button => {
            button.addEventListener('click', this._onPauseAllVideo);
        });
    }

    _registerKeydownEscVideoPlayerEvents() {
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                this._onPauseAllVideo();
            }
        });
    }

    _onPauseAllVideo() {
        const medias = Array.prototype.slice.apply(document.querySelectorAll('audio,video'));
        medias.forEach(function(media) {
            media.pause();
        });
    }
}
