export default {
    namespaced: true,

    state() {
        return {
            esdMedia: [],
            esdVideos: [],
            isLoadedEsdMedia: false
        };
    },

    mutations: {
        setEsdMedia(state, newEsdMedia) {
            state.esdMedia = newEsdMedia;
        },

        addEsdMedia(state, newEsdMedia) {
            state.esdMedia.push(newEsdMedia);
        },

        setEsdVideos(state, newEsdVideos) {
            state.esdVideos = newEsdVideos;
        },

        addEsdVideo(state, newEsdVideo) {
            state.esdVideos.push(newEsdVideo);
        },

        updateEsdVideo(state, newEsdVideo) {
            const i = state.esdVideos.filter((esdVideo, index) => {
                if (esdVideo.id === newEsdVideo.id) {
                    return index
                }
            });

            state.esdVideos[i] = newEsdVideo;
        },

        setIsLoadedEsdMedia(state, isLoadedEsdMedia) {
            state.isLoadedEsdMedia = isLoadedEsdMedia;
        }
    }
};
