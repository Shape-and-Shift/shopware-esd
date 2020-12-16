import EsdDownloadRemaining from "./plugin/esd-download-remaining.plugin";
import EsdVideoPlayer from "./plugin/esd-video-player.plugin";

const PluginManager = window.PluginManager;

PluginManager.register('EsdDownloadRemaining', EsdDownloadRemaining, '[data-esd-download-remaining]');
PluginManager.register('EsdVideoPlayer', EsdVideoPlayer, '[data-esd-video-player]');
