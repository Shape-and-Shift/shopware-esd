const { Component, Context } = Shopware;
const { Criteria } = Shopware.Data;

Component.override( 'sw-duplicated-media-v2', {
    inject: ['sasMediaService'],

    methods: {
        async updatePreviewData() {
            if (!this.currentTask) {
                this.existingMedia = null;
                this.suggestedName = '';
                return;
            }

            if (this.currentTask.plugin !== 'ESD') {
                this.$super('updatePreviewData');
                return;
            }

            this.existingMedia = await this.sasMediaService.getAdminSystemMedia(this.currentTask.fileName, this.currentTask.extension)
            const provided = await this.sasMediaService.provideName(this.currentTask.fileName, this.currentTask.extension);
            this.suggestedName = provided.fileName;
        },

        async renameFile(uploadTask) {
            if (uploadTask.plugin !== 'ESD') {
                this.$super('renameFile', uploadTask);
                return;
            }

            const newTask = Object.assign({}, uploadTask);

            const { fileName } = await this.sasMediaService.provideName(uploadTask.fileName, uploadTask.extension);
            newTask.fileName = fileName;

            this.mediaService.addUpload(newTask.uploadTag, newTask);
            await this.mediaService.runUploads(newTask.uploadTag);
        },

        async replaceFile(uploadTask) {
            if (uploadTask.plugin !== 'ESD') {
                this.$super('replaceFile', uploadTask);
                return;
            }

            const newTarget = await this.sasMediaService.getAdminSystemMedia(uploadTask.fileName, uploadTask.extension)

            if (!newTarget) {
                return;
            }

            const oldTargetId = uploadTask.targetId;
            uploadTask.targetId = newTarget.id;

            this.mediaService.addUpload(uploadTask.uploadTag, uploadTask);

            await this.mediaService.runUploads(uploadTask.uploadTag);

            const oldTarget = await this.sasMediaService.getAdminSystemMediaById(oldTargetId);

            if (!oldTarget.hasFile) {
                await this.mediaRepository.delete(oldTargetId, Context.api);
            }

            await this.sasMediaService.getAdminSystemMediaById(uploadTask.targetId);
        },
    },
});
