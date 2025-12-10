const Plugin = window.PluginBaseClass;


export default class EsdDownloadRemaining extends Plugin {

    static options = {
        esdDownloadSelector: '.js-esd-download-selector',
        esdDownloadHtml: '.account-esd-downloads'
    };

    init() {
        
        this._registerEvents();
    }

    _registerEvents() {
        this._registerDownloadRemainingEvents();
    }

    _registerDownloadRemainingEvents() {
        const { esdDownloadSelector } = this.options;
        Array.from(document.querySelectorAll(esdDownloadSelector)).forEach(button => {
            button.addEventListener('click', this._onClickDownload.bind(this));
        });
    }

    _onClickDownload() {
        this._sleep(1000).then(() => {
            const url = window.router['frontend.account.downloads.remaining'];

            fetch(url)
    .then(response => response.text())
    .then((response) => {
        {
                this._renderEsdDownloads(response);
                this._registerEvents();
            }
    });
        });
    }

    _renderEsdDownloads(html) {
        const { esdDownloadHtml } = this.options;
        this.el.querySelector(esdDownloadHtml).innerHTML = html;
    }

    _sleep(time) {
        return new Promise((resolve) => setTimeout(resolve, time));
    }
}
