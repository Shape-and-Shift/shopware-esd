import EsdDownloadRemaining from "./plugin/esd-download-remaining.plugin";

const PluginManager = window.PluginManager;

PluginManager.register('EsdDownloadRemaining', EsdDownloadRemaining, '[data-esd-download-remaining]');
