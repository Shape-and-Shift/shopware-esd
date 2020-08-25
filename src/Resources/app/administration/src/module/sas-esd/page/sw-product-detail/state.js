export default {
    namespaced: true,

    state() {
        return {
            esdMedia: [],
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

        setIsLoadedEsdMedia(state, isLoadedEsdMedia) {
            state.isLoadedEsdMedia = isLoadedEsdMedia;
        }
    }
};
